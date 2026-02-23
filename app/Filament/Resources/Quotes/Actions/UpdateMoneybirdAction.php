<?php

namespace App\Filament\Resources\Quotes\Actions;

use App\Models\Quote;
use App\Services\MoneybirdService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;

class UpdateMoneybirdAction extends Action
{
    public static function make(?string $name = 'updateMoneybird'): static
    {
        return parent::make($name)
            ->label('Bijwerken in Moneybird')
            ->icon('heroicon-o-arrow-up-circle')
            ->color('info')
            ->modalHeading('Offerte bijwerken in Moneybird')
            ->modalSubmitActionLabel('Ja, bijwerken in Moneybird')
            ->modalWidth('2xl')
            ->visible(fn (Quote $record) => (bool) $record->moneybird_quote_id)
            ->modalContent(function (Quote $record): HtmlString {
                /** @var MoneybirdService $service */
                $service = app(MoneybirdService::class);
                $preText  = $service->buildPreText($record);
                $postText = $service->buildPostText($record);

                $preHtml  = $preText  ? nl2br(e($preText))  : '<em class="text-gray-400">Geen tekst</em>';
                $postHtml = $postText ? nl2br(e($postText)) : '<em class="text-gray-400">Geen tekst</em>';

                $html = '
                <div class="space-y-4 text-sm">
                    <p class="text-gray-600">De regels en tekst van deze offerte worden overschreven in Moneybird met de huidige gegevens. Dit kan niet ongedaan worden gemaakt.</p>

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
            ->action(function (Quote $record, MoneybirdService $moneybirdService) {
                try {
                    if (! $moneybirdService->isConfigured()) {
                        Notification::make()
                            ->danger()
                            ->title('Moneybird niet geconfigureerd')
                            ->body('Configureer eerst de Moneybird API instellingen.')
                            ->send();

                        return;
                    }

                    $moneybirdService->updateQuote($record);

                    Notification::make()
                        ->success()
                        ->title('Offerte bijgewerkt')
                        ->body("Offerte {$record->quote_number} is bijgewerkt in Moneybird.")
                        ->send();
                } catch (\Exception $e) {
                    Notification::make()
                        ->danger()
                        ->title('Bijwerken mislukt')
                        ->body($e->getMessage())
                        ->send();
                }
            });
    }
}
