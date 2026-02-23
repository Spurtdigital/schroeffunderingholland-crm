<?php

namespace App\Filament\Resources\Quotes\Actions;

use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Services\MoneybirdService;
use App\Services\QuoteCalculationService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;

class SendToMoneybirdAction extends Action
{
    public static function make(?string $name = 'sendToMoneybird'): static
    {
        return parent::make($name)
            ->label('Verstuur naar Moneybird')
            ->icon('heroicon-o-paper-airplane')
            ->color('info')
            ->modalHeading('Versturen naar Moneybird')
            ->modalSubmitActionLabel('Ja, versturen naar Moneybird')
            ->modalWidth('2xl')
            ->visible(fn (Quote $record) => ! $record->moneybird_quote_id)
            ->modalContent(function (Quote $record): HtmlString {
                /** @var MoneybirdService $service */
                $service = app(MoneybirdService::class);
                $preText  = $service->buildPreText($record);
                $postText = $service->buildPostText($record);

                $preHtml  = $preText  ? nl2br(e($preText))  : '<em class="text-gray-400">Geen tekst</em>';
                $postHtml = $postText ? nl2br(e($postText)) : '<em class="text-gray-400">Geen tekst</em>';

                $html = '
                <div class="space-y-4 text-sm">
                    <p class="text-gray-600">Deze offerte wordt als concept naar Moneybird verstuurd. Controleer hieronder de tekst die wordt meegestuurd.</p>

                    <div>
                        <div class="font-semibold text-gray-700 mb-1">Aanhef + opening + aannames <span class="font-normal text-gray-400 text-xs">(pre_text)</span></div>
                        <div class="bg-gray-50 rounded-lg border border-gray-200 p-3 leading-relaxed whitespace-pre-wrap font-mono text-xs text-gray-800">'.$preHtml.'</div>
                    </div>

                    <div>
                        <div class="font-semibold text-gray-700 mb-1">Betalingsvoorwaarden + slottekst <span class="font-normal text-gray-400 text-xs">(post_text)</span></div>
                        <div class="bg-gray-50 rounded-lg border border-gray-200 p-3 leading-relaxed whitespace-pre-wrap font-mono text-xs text-gray-800">'.$postHtml.'</div>
                    </div>
                </div>';

                return new HtmlString($html);
            })
            ->action(function (Quote $record, MoneybirdService $moneybirdService, QuoteCalculationService $calculationService) {
                try {
                    // Check if Moneybird is configured
                    if (! $moneybirdService->isConfigured()) {
                        Notification::make()
                            ->danger()
                            ->title('Moneybird niet geconfigureerd')
                            ->body('Configureer eerst de Moneybird API instellingen.')
                            ->send();

                        return;
                    }

                    // Check if quote has items
                    if ($record->items()->count() === 0) {
                        Notification::make()
                            ->warning()
                            ->title('Geen regels')
                            ->body('Voeg eerst regels toe aan de offerte voordat je deze verstuurt naar Moneybird.')
                            ->send();

                        return;
                    }

                    // Ensure totals are up to date before sending
                    $calculationService->calculateTotals($record);
                    $record->refresh();

                    // Send quote to Moneybird
                    $moneybirdService->sendQuote($record);

                    // Update quote status
                    $record->update([
                        'status' => QuoteStatus::MoneybirdPrepared,
                    ]);

                    // Show success notification
                    Notification::make()
                        ->success()
                        ->title('Offerte verstuurd')
                        ->body("Offerte {$record->quote_number} is succesvol verstuurd naar Moneybird.")
                        ->send();
                } catch (\Exception $e) {
                    Notification::make()
                        ->danger()
                        ->title('Verzenden mislukt')
                        ->body($e->getMessage())
                        ->send();
                }
            });
    }
}
