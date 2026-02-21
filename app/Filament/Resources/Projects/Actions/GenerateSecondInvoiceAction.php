<?php

namespace App\Filament\Resources\Projects\Actions;

use App\Enums\InvoiceType;
use App\Models\Invoice;
use App\Models\Project;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class GenerateSecondInvoiceAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'generateSecondInvoice';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(fn (Project $record) => "Tweede factuur genereren ({$record->getSecondInvoicePercentage()}%)"
        );

        $this->icon('heroicon-o-document-plus');

        $this->color('success');

        $this->requiresConfirmation();

        $this->modalHeading(fn (Project $record) => "Tweede factuur genereren ({$record->getSecondInvoicePercentage()}%)"
        );

        $this->modalDescription(fn (Project $record) => "Deze actie maakt de tweede (eind)factuur aan met {$record->getSecondInvoicePercentage()}% van het totaalbedrag."
        );

        $this->visible(function (Project $record) {
            // Only visible for split invoicing
            if (! $record->usesSplitInvoicing()) {
                return false;
            }

            // Check if first invoice exists
            $hasFirstInvoice = $record->invoices()
                ->where('invoice_type', InvoiceType::Deposit)
                ->exists();

            // Check if second invoice doesn't exist yet
            $hasSecondInvoice = $record->invoices()
                ->where('invoice_type', InvoiceType::Final)
                ->exists();

            return $hasFirstInvoice && ! $hasSecondInvoice;
        });

        $this->action(function (Project $record) {
            // Calculate invoice amount
            $quote = $record->quote;
            if (! $quote) {
                Notification::make()
                    ->danger()
                    ->title('Geen offerte gevonden')
                    ->body('Dit project heeft geen gekoppelde offerte.')
                    ->send();

                return;
            }

            $percentage = $record->getSecondInvoicePercentage();
            $subtotal = ($quote->subtotal * $percentage) / 100;
            $vatAmount = ($quote->vat_amount * $percentage) / 100;
            $total = ($quote->total * $percentage) / 100;

            // Create invoice
            $invoice = Invoice::create([
                'project_id' => $record->id,
                'company_id' => $record->company_id,
                'invoice_number' => Invoice::generateInvoiceNumber(),
                'invoice_date' => now(),
                'due_date' => now()->addDays(14),
                'invoice_type' => InvoiceType::Final,
                'subtotal' => round($subtotal, 2),
                'vat_amount' => round($vatAmount, 2),
                'total' => round($total, 2),
                'status' => \App\Enums\InvoiceStatus::Draft,
                'notes' => "Eindfactuur ({$percentage}%) voor project {$record->project_number}",
            ]);

            Notification::make()
                ->success()
                ->title('Factuur aangemaakt')
                ->body("Factuur {$invoice->invoice_number} is aangemaakt voor €".number_format($total, 2, ',', '.'))
                ->send();

            // Redirect to invoice
            return redirect()->route('filament.admin.resources.invoices.edit', ['record' => $invoice]);
        });
    }
}
