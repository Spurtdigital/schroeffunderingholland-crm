<?php

namespace App\Filament\Resources\Projects\Actions;

use App\Enums\InvoiceType;
use App\Models\Invoice;
use App\Models\Project;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class GenerateFirstInvoiceAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'generateFirstInvoice';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(fn (Project $record) => 
            $record->usesSplitInvoicing() 
                ? "Eerste factuur genereren ({$record->getFirstInvoicePercentage()}%)"
                : "Factuur genereren (100%)"
        );

        $this->icon('heroicon-o-document-plus');

        $this->color('success');
        
        $this->requiresConfirmation();
        
        $this->modalHeading(fn (Project $record) => 
            "Eerste factuur genereren ({$record->getFirstInvoicePercentage()}%)"
        );
        
        $this->modalDescription(fn (Project $record) => 
            $record->usesSplitInvoicing()
                ? "Deze actie maakt de eerste factuur aan met {$record->getFirstInvoicePercentage()}% van het totaalbedrag."
                : "Deze actie maakt een factuur aan voor het volledige bedrag."
        );
        
        $this->visible(function (Project $record) {
            // Check if first invoice doesn't exist yet
            return !$record->invoices()->where('invoice_type', InvoiceType::Deposit)->exists()
                && !$record->invoices()->where('invoice_type', InvoiceType::Single)->exists();
        });

        $this->action(function (Project $record) {
            // Calculate invoice amount
            $quote = $record->quote;
            if (!$quote) {
                Notification::make()
                    ->danger()
                    ->title('Geen offerte gevonden')
                    ->body('Dit project heeft geen gekoppelde offerte.')
                    ->send();
                return;
            }
            
            $percentage = $record->getFirstInvoicePercentage();
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
                'invoice_type' => $record->usesSingleInvoice() ? InvoiceType::Single : InvoiceType::Deposit,
                'subtotal' => round($subtotal, 2),
                'vat_amount' => round($vatAmount, 2),
                'total' => round($total, 2),
                'status' => \App\Enums\InvoiceStatus::Draft,
                'notes' => $record->usesSingleInvoice() 
                    ? "Volledige factuur voor project {$record->project_number}"
                    : "Eerste factuur ({$percentage}%) voor project {$record->project_number}",
            ]);
            
            Notification::make()
                ->success()
                ->title('Factuur aangemaakt')
                ->body("Factuur {$invoice->invoice_number} is aangemaakt voor €" . number_format($total, 2, ',', '.'))
                ->send();
                
            // Redirect to invoice
            return redirect()->route('filament.admin.resources.invoices.edit', ['record' => $invoice]);
        });
    }
}
