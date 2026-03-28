<?php

namespace App\Filament\Resources\Leads\Actions;

use App\Enums\LeadStatus;
use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Settings\GeneralSettings;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

class ConvertToQuoteAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'convertToQuote';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Offerte maken');

        $this->icon('heroicon-o-document-text');

        $this->color('success');

        $this->visible(fn (Model $record): bool => $record->status !== LeadStatus::Converted &&
            $record->status !== LeadStatus::Quoted &&
            $record->company_id !== null
        );

        $this->action(function (Model $record) {
            $settings = app(GeneralSettings::class);

            // Create quote from lead with all info
            $notes = "Offerte aangemaakt vanuit lead: {$record->name}";
            if ($record->notes) {
                $notes .= "\n\nProjectomschrijving:\n{$record->notes}";
            }

            $quote = Quote::create([
                'company_id' => $record->company_id,
                'lead_id' => $record->id,
                'quote_number' => Quote::generateQuoteNumber(),
                'status' => QuoteStatus::Draft,
                'valid_until' => now()->addDays($settings->default_quote_validity_days ?? 30),
                'subtotal' => 0,
                'vat_amount' => 0,
                'total' => 0,
                'notes' => $notes,
            ]);

            // Update lead status
            $record->update([
                'status' => LeadStatus::Quoted,
                'converted_to_quote_id' => $quote->id,
            ]);

            Notification::make()
                ->success()
                ->title('Offerte aangemaakt')
                ->body("Offerte ".($quote->displayNumber() ?? 'Concept')." is aangemaakt. Je kunt nu producten toevoegen.")
                ->send();

            // Redirect to quote view page
            return redirect()->route('filament.admin.resources.quotes.view', ['record' => $quote]);
        });
    }
}
