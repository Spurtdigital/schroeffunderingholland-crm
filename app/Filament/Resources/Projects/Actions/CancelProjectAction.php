<?php

namespace App\Filament\Resources\Projects\Actions;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\ProjectStatus;
use App\Models\Invoice;
use App\Models\Project;
use App\Services\MoneybirdService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;

class CancelProjectAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'cancelProject';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Project annuleren');
        $this->icon('heroicon-o-x-circle');
        $this->color('danger');

        // Alleen zichtbaar voor actieve of on-hold projecten
        $this->visible(fn (Project $record) => in_array($record->status, [
            ProjectStatus::Active,
            ProjectStatus::OnHold,
        ]));

        $this->modalHeading('Project annuleren');
        $this->modalDescription('Annuleer dit project en factureer optioneel annuleringskosten aan de klant.');
        $this->modalSubmitActionLabel('Project annuleren');
        $this->modalIcon('heroicon-o-x-circle');
        $this->modalWidth('lg');

        $this->form(function (Project $record) {
            $quote = $record->quote;
            $projectTotal = $quote?->total ?? $record->items()->sum('total') * 1.21;

            return [
                Forms\Components\Toggle::make('charge_cancellation_fee')
                    ->label('Annuleringskosten in rekening brengen')
                    ->default(true)
                    ->live()
                    ->helperText('Maak een factuur aan voor annuleringskosten'),

                Forms\Components\Select::make('fee_type')
                    ->label('Type annuleringskosten')
                    ->options([
                        'percentage' => 'Percentage van orderwaarde',
                        'fixed' => 'Vast bedrag',
                    ])
                    ->default('percentage')
                    ->live()
                    ->visible(fn (Get $get) => $get('charge_cancellation_fee'))
                    ->required(fn (Get $get) => $get('charge_cancellation_fee')),

                Forms\Components\TextInput::make('percentage')
                    ->label('Percentage')
                    ->numeric()
                    ->suffix('%')
                    ->minValue(1)
                    ->maxValue(100)
                    ->default(10)
                    ->visible(fn (Get $get) => $get('charge_cancellation_fee') && $get('fee_type') === 'percentage')
                    ->required(fn (Get $get) => $get('charge_cancellation_fee') && $get('fee_type') === 'percentage')
                    ->helperText(fn () => 'Orderwaarde: €'.number_format($projectTotal, 2, ',', '.').' incl. BTW'),

                Forms\Components\TextInput::make('fixed_amount')
                    ->label('Vast bedrag (excl. BTW)')
                    ->numeric()
                    ->prefix('€')
                    ->minValue(0.01)
                    ->visible(fn (Get $get) => $get('charge_cancellation_fee') && $get('fee_type') === 'fixed')
                    ->required(fn (Get $get) => $get('charge_cancellation_fee') && $get('fee_type') === 'fixed')
                    ->helperText('BTW (21%) wordt automatisch toegevoegd'),

                Forms\Components\Textarea::make('cancellation_reason')
                    ->label('Reden van annulering')
                    ->rows(3)
                    ->placeholder('Optioneel: noteer de reden van annulering...')
                    ->helperText('Wordt opgeslagen bij het project en op de factuur'),

                Forms\Components\Toggle::make('send_to_moneybird')
                    ->label('Factuur direct naar Moneybird sturen')
                    ->default(true)
                    ->visible(fn (Get $get) => $get('charge_cancellation_fee'))
                    ->helperText('Verstuur de annuleringsfactuur automatisch naar Moneybird'),
            ];
        });

        $this->action(function (array $data, Project $record, MoneybirdService $moneybirdService) {
            $quote = $record->quote;
            $projectTotal = $quote?->total ?? $record->items()->sum('total') * 1.21;

            $invoice = null;

            // Maak annuleringsfactuur als gewenst
            if ($data['charge_cancellation_fee'] ?? false) {
                if ($data['fee_type'] === 'percentage') {
                    $percentage = (float) $data['percentage'];
                    $subtotal = round(($projectTotal / 1.21) * ($percentage / 100), 2);
                } else {
                    $subtotal = round((float) $data['fixed_amount'], 2);
                }

                $vatAmount = round($subtotal * 0.21, 2);
                $total = round($subtotal + $vatAmount, 2);

                // Bouw notities
                $notes = "Annuleringskosten voor project {$record->project_number}";
                if ($data['fee_type'] === 'percentage') {
                    $notes .= " ({$data['percentage']}% van orderwaarde)";
                }
                if (! empty($data['cancellation_reason'])) {
                    $notes .= "\n\nReden: {$data['cancellation_reason']}";
                }

                $invoice = Invoice::create([
                    'project_id' => $record->id,
                    'company_id' => $record->company_id,
                    'invoice_number' => Invoice::generateInvoiceNumber(),
                    'invoice_date' => now(),
                    'due_date' => now()->addDays(14),
                    'invoice_type' => InvoiceType::Cancellation,
                    'subtotal' => $subtotal,
                    'vat_amount' => $vatAmount,
                    'total' => $total,
                    'status' => InvoiceStatus::Draft,
                    'notes' => $notes,
                ]);

                // Verstuur naar Moneybird indien gewenst
                if ($data['send_to_moneybird'] ?? false) {
                    try {
                        if ($moneybirdService->isConfigured()) {
                            $moneybirdService->sendInvoice($invoice);
                        }
                    } catch (\Exception $e) {
                        Notification::make()
                            ->warning()
                            ->title('Factuur aangemaakt, verzenden mislukt')
                            ->body("Annuleringsfactuur is aangemaakt, maar kon niet naar Moneybird worden verstuurd: {$e->getMessage()}")
                            ->send();
                    }
                }
            }

            // Sla annuleringsreden op bij project
            $record->update([
                'status' => ProjectStatus::Cancelled,
                'notes' => $record->notes
                    ? $record->notes."\n\n---\nANNULERING: ".($data['cancellation_reason'] ?? 'Geen reden opgegeven')
                    : 'ANNULERING: '.($data['cancellation_reason'] ?? 'Geen reden opgegeven'),
            ]);

            // Bouw notificatie
            if ($invoice) {
                Notification::make()
                    ->success()
                    ->title('Project geannuleerd')
                    ->body("Project {$record->project_number} is geannuleerd. Annuleringsfactuur {$invoice->invoice_number} aangemaakt voor €".number_format($invoice->total, 2, ',', '.'))
                    ->send();

                // Redirect naar de factuur
                return redirect()->route('filament.admin.resources.invoices.edit', ['record' => $invoice]);
            }

            Notification::make()
                ->success()
                ->title('Project geannuleerd')
                ->body("Project {$record->project_number} is geannuleerd zonder annuleringskosten.")
                ->send();
        });

        $this->requiresConfirmation()
            ->modalDescription('Weet je zeker dat je dit project wilt annuleren? Deze actie kan niet ongedaan worden gemaakt.');
    }
}
