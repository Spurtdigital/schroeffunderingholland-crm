<?php

namespace App\Filament\Resources\Projects\Actions;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Models\Invoice;
use App\Models\Project;
use App\Services\MoneybirdService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;

class GenerateInvoiceAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'generateInvoice';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Nieuwe factuur');
        $this->icon('heroicon-o-document-plus');
        $this->color('success');

        $this->form(function (Project $record) {
            $options = [];

            // Check which invoice types are available
            $hasFirstInvoice = $record->invoices()->whereIn('invoice_type', [InvoiceType::Deposit, InvoiceType::Single])->exists();
            $hasSecondInvoice = $record->invoices()->where('invoice_type', InvoiceType::Final)->exists();

            $defaultPct = null;

            if (! $hasFirstInvoice) {
                if ($record->usesSplitInvoicing()) {
                    $pct = $record->getFirstInvoicePercentage();
                    $defaultPct = $pct;
                    $options['first'] = "Aanbetalingsfactuur ({$pct}% standaard, aanpasbaar)";
                } else {
                    $options['single'] = 'Volledige factuur (100%)';
                }
            }

            if ($hasFirstInvoice && ! $hasSecondInvoice && $record->usesSplitInvoicing()) {
                // Calculate actual remaining amount (project items - already invoiced)
                $projectSubtotal = $record->items()->sum('total');
                $projectTotal = round($projectSubtotal * 1.21, 2);
                $alreadyInvoicedTotal = $record->invoices()->sum('total');
                $remaining = max(0, round($projectTotal - $alreadyInvoicedTotal, 2));

                $options['second'] = 'Eindfactuur (resterend bedrag: €'.number_format($remaining, 2, ',', '.').' incl. BTW)';
            }

            // Always allow custom invoice
            $options['custom'] = 'Aangepaste factuur (vrije invoer)';

            return [
                Forms\Components\Select::make('invoice_type_choice')
                    ->label('Type factuur')
                    ->options($options)
                    ->required()
                    ->live()
                    ->default(array_key_first($options))
                    ->helperText(fn (Get $get) => match ($get('invoice_type_choice')) {
                        'second' => 'Bedrag = totaal project items (incl. meerwerk) minus al gefactureerd',
                        'first'  => 'Percentage van het subtotaal excl. BTW van de offerte',
                        default  => 'Selecteer welk type factuur je wilt aanmaken',
                    }),

                Forms\Components\TextInput::make('custom_percentage')
                    ->label('Percentage van subtotaal excl. BTW')
                    ->numeric()
                    ->suffix('%')
                    ->minValue(1)
                    ->maxValue(100)
                    ->required()
                    ->default($defaultPct)
                    ->visible(fn (Get $get) => in_array($get('invoice_type_choice'), ['first', 'custom']))
                    ->helperText(fn (Get $get) => $get('invoice_type_choice') === 'first'
                        ? 'Standaard ingesteld op het projectpercentage — pas aan indien nodig'
                        : 'Voer het percentage van het totaalbedrag in'),

                Forms\Components\Textarea::make('notes')
                    ->label('Notities (optioneel)')
                    ->rows(3)
                    ->placeholder('Eventuele opmerkingen voor deze factuur...'),

                Forms\Components\Toggle::make('send_to_moneybird')
                    ->label('Direct naar Moneybird sturen')
                    ->default(true)
                    ->helperText('Als je deze factuur direct naar Moneybird wilt sturen, laat dit dan aangevinkt'),
            ];
        });

        $this->action(function (array $data, Project $record, MoneybirdService $moneybirdService) {
            $quote = $record->quote;
            if (! $quote) {
                Notification::make()
                    ->danger()
                    ->title('Geen offerte gevonden')
                    ->body('Dit project heeft geen gekoppelde offerte.')
                    ->send();

                return;
            }

            // Determine invoice type and percentage
            $invoiceType = InvoiceType::Single;
            $percentage = 100;

            switch ($data['invoice_type_choice']) {
                case 'first':
                    $invoiceType = InvoiceType::Deposit;
                    $percentage = $data['custom_percentage'] ?? $record->getFirstInvoicePercentage();
                    break;
                case 'second':
                    $invoiceType = InvoiceType::Final;
                    $percentage = $record->getSecondInvoicePercentage();
                    break;
                case 'single':
                    $invoiceType = InvoiceType::Single;
                    $percentage = 100;
                    break;
                case 'custom':
                    $invoiceType = InvoiceType::Single;
                    $percentage = $data['custom_percentage'];
                    break;
            }

            // Calculate amounts
            if ($data['invoice_type_choice'] === 'second') {
                // Final invoice: project total (from project items) minus already invoiced
                $projectSubtotal = $record->items()->sum('total');
                $projectVat = round($projectSubtotal * 0.21, 2);
                $projectTotal = round($projectSubtotal + $projectVat, 2);

                $alreadyInvoicedSubtotal = $record->invoices()->sum('subtotal');
                $alreadyInvoicedVat = $record->invoices()->sum('vat_amount');
                $alreadyInvoicedTotal = $record->invoices()->sum('total');

                $subtotal = round($projectSubtotal - $alreadyInvoicedSubtotal, 2);
                $vatAmount = round($projectVat - $alreadyInvoicedVat, 2);
                $total = round($projectTotal - $alreadyInvoicedTotal, 2);

                // Ensure no negative amounts
                $subtotal = max(0, $subtotal);
                $vatAmount = max(0, $vatAmount);
                $total = max(0, $total);
            } else {
                $subtotal = ($quote->subtotal * $percentage) / 100;
                $vatAmount = ($quote->vat_amount * $percentage) / 100;
                $total = ($quote->total * $percentage) / 100;
            }

            // Generate notes
            $notes = $data['notes'] ?? '';
            if ($data['invoice_type_choice'] === 'custom') {
                $notes = "Aangepaste factuur ({$percentage}%) voor project {$record->project_number}"
                    .($notes ? "\n\n{$notes}" : '');
            } elseif (! $notes) {
                $notes = match ($data['invoice_type_choice']) {
                    'first'  => "Aanbetalingsfactuur ({$percentage}%) voor project {$record->project_number}",
                    'second' => "Eindfactuur voor project {$record->project_number} (resterend bedrag incl. meerwerk)",
                    'single' => "Volledige factuur voor project {$record->project_number}",
                    default  => ''
                };
            }

            // Create invoice
            $invoice = Invoice::create([
                'project_id' => $record->id,
                'company_id' => $record->company_id,
                'invoice_number' => Invoice::generateInvoiceNumber(),
                'invoice_date' => now(),
                'due_date' => now()->addDays(14),
                'invoice_type' => $invoiceType,
                'subtotal' => round($subtotal, 2),
                'vat_amount' => round($vatAmount, 2),
                'total' => round($total, 2),
                'status' => InvoiceStatus::Draft,
                'notes' => $notes,
                'deposit_percentage' => $data['invoice_type_choice'] === 'second' ? null : $percentage,
            ]);

            // Send to Moneybird if requested
            if ($data['send_to_moneybird']) {
                try {
                    if (! $moneybirdService->isConfigured()) {
                        Notification::make()
                            ->warning()
                            ->title('Factuur aangemaakt zonder Moneybird')
                            ->body("Factuur {$invoice->invoice_number} is aangemaakt, maar Moneybird is niet geconfigureerd. Je kunt de factuur later handmatig versturen.")
                            ->send();
                    } else {
                        $moneybirdService->sendInvoice($invoice);

                        Notification::make()
                            ->success()
                            ->title('Factuur aangemaakt en verstuurd')
                            ->body("Factuur {$invoice->invoice_number} is aangemaakt (€".number_format($total, 2, ',', '.').') en verstuurd naar Moneybird.')
                            ->send();
                    }
                } catch (\Exception $e) {
                    Notification::make()
                        ->warning()
                        ->title('Factuur aangemaakt, verzenden mislukt')
                        ->body("Factuur {$invoice->invoice_number} is aangemaakt, maar kon niet naar Moneybird worden verstuurd: {$e->getMessage()}")
                        ->send();
                }
            } else {
                Notification::make()
                    ->success()
                    ->title('Factuur aangemaakt')
                    ->body("Factuur {$invoice->invoice_number} is aangemaakt voor €".number_format($total, 2, ',', '.'))
                    ->send();
            }

            // Redirect to invoice
            return redirect()->route('filament.admin.resources.invoices.edit', ['record' => $invoice]);
        });
    }
}
