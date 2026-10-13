<?php

namespace App\Filament\Resources\Quotes\Actions;

use App\Enums\QuoteStatus;
use App\Filament\Resources\Quotes\QuoteResource;
use App\Services\QuoteCalculationService;
use App\Settings\GeneralSettings;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

class DuplicateQuoteAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'duplicateQuote';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Dupliceren');

        // Zichtbaar via de feature flag FEATURE_QUOTE_DUPLICATE.
        // Staat die uit, dan verdwijnt de knop in één keer uit QuotesTable,
        // EditQuote en ViewQuote.
        $this->visible(fn (): bool => (bool) config('features.quote_duplicate'));

        $this->icon('heroicon-o-document-duplicate');

        $this->color('gray');

        $this->requiresConfirmation();

        $this->modalHeading('Offerte dupliceren');

        $this->modalDescription('Er wordt een nieuwe concept-offerte aangemaakt met dezelfde gegevens, regels en aannames.');

        $this->modalSubmitActionLabel('Dupliceren');

        $this->action(function (Model $record) {
            // Clone the quote, resetting all lifecycle fields so the copy starts
            // as a fresh concept (new number, no Moneybird link, not signed/sent).
            $newQuote = $record->replicate();
            $newQuote->quote_number = null;
            $newQuote->status = QuoteStatus::Draft;
            $newQuote->project_id = null;
            $newQuote->moneybird_quote_id = null;
            $newQuote->sent_at = null;
            $newQuote->approved_at = null;
            $newQuote->signed_at = null;
            $newQuote->signed_ip = null;
            $newQuote->signed_by_name = null;
            $newQuote->valid_until = now()->addDays(
                max(1, (int) (app(GeneralSettings::class)->default_quote_validity_days ?? 30))
            );
            $newQuote->save();

            // Copy all quote items
            foreach ($record->items as $item) {
                $newItem = $item->replicate();
                $newItem->quote_id = $newQuote->id;
                $newItem->save();
            }

            // Copy the selected assumptions (including their pivot values)
            foreach ($record->assumptions as $assumption) {
                $newQuote->assumptions()->attach($assumption->id, [
                    'pivot_value' => $assumption->pivot->pivot_value,
                ]);
            }

            app(QuoteCalculationService::class)->calculateTotals($newQuote);

            Notification::make()
                ->success()
                ->title('Offerte gedupliceerd')
                ->body('Er is een nieuwe concept-offerte aangemaakt.')
                ->send();

            return redirect(QuoteResource::getUrl('edit', ['record' => $newQuote]));
        });
    }
}
