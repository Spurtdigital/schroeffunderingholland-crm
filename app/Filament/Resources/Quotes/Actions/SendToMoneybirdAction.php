<?php

namespace App\Filament\Resources\Quotes\Actions;

use App\Enums\QuoteStatus;
use App\Models\PaymentTerm;
use App\Models\Quote;
use App\Models\QuoteAssumption;
use App\Services\MoneybirdService;
use App\Services\QuoteCalculationService;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;

class SendToMoneybirdAction extends Action
{
    public static function make(?string $name = 'sendToMoneybird'): static
    {
        return parent::make($name)
            ->label('Verstuur naar Moneybird')
            ->icon('heroicon-o-paper-airplane')
            ->color('info')
            ->modalHeading('Versturen naar Moneybird')
            ->modalDescription('Controleer en pas indien nodig de tekst aan. De offerte wordt als concept naar Moneybird verstuurd.')
            ->modalSubmitActionLabel('Versturen naar Moneybird')
            ->modalWidth('3xl')
            ->visible(fn (Quote $record) => ! $record->moneybird_quote_id)
            ->fillForm(function (Quote $record): array {
                return [
                    'pre_text'        => $record->pre_text,
                    'assumption_ids'  => $record->assumptions()->pluck('quote_assumptions.id')->toArray(),
                    'payment_term_id' => $record->payment_term_id,
                    'post_text'       => $record->post_text,
                ];
            })
            ->form([
                Textarea::make('pre_text')
                    ->label('Opening zin')
                    ->helperText('Verschijnt na de aanhef en vóór de aannames in Moneybird.')
                    ->rows(3)
                    ->columnSpanFull(),

                Section::make('Offerte-aannames')
                    ->description('Worden als bulletlijst opgenomen onder de opening.')
                    ->schema([
                        CheckboxList::make('assumption_ids')
                            ->label('')
                            ->options(fn () => QuoteAssumption::where('active', true)->orderBy('sort_order')->pluck('title', 'id')->toArray())
                            ->columns(2)
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),

                Select::make('payment_term_id')
                    ->label('Betalingsvoorwaarde')
                    ->options(fn () => PaymentTerm::where('active', true)->orderBy('sort_order')->pluck('title', 'id')->toArray())
                    ->placeholder('Geen betalingsvoorwaarde')
                    ->searchable()
                    ->nullable()
                    ->columnSpanFull(),

                Textarea::make('post_text')
                    ->label('Vrije nawoord tekst')
                    ->helperText('Verschijnt ná de betalingsvoorwaarden. De vaste sluitingstekst uit de instellingen wordt automatisch toegevoegd.')
                    ->rows(3)
                    ->columnSpanFull(),
            ])
            ->action(function (Quote $record, array $data, MoneybirdService $moneybirdService, QuoteCalculationService $calculationService) {
                try {
                    if (! $moneybirdService->isConfigured()) {
                        Notification::make()->danger()
                            ->title('Moneybird niet geconfigureerd')
                            ->body('Configureer eerst de Moneybird API instellingen.')
                            ->send();
                        return;
                    }

                    if ($record->items()->count() === 0) {
                        Notification::make()->warning()
                            ->title('Geen regels')
                            ->body('Voeg eerst regels toe aan de offerte voordat je deze verstuurt naar Moneybird.')
                            ->send();
                        return;
                    }

                    // Persist form changes to the quote first
                    $record->update([
                        'pre_text'        => $data['pre_text'],
                        'post_text'       => $data['post_text'],
                        'payment_term_id' => $data['payment_term_id'],
                    ]);
                    $record->assumptions()->sync($data['assumption_ids'] ?? []);
                    $record->refresh();

                    // Recalculate totals
                    $calculationService->calculateTotals($record);
                    $record->refresh();

                    // Send to Moneybird
                    $moneybirdService->sendQuote($record);

                    $record->update(['status' => QuoteStatus::MoneybirdPrepared]);

                    Notification::make()->success()
                        ->title('Offerte verstuurd')
                        ->body("Offerte {$record->quote_number} is succesvol verstuurd naar Moneybird.")
                        ->send();
                } catch (\Exception $e) {
                    Notification::make()->danger()
                        ->title('Verzenden mislukt')
                        ->body($e->getMessage())
                        ->send();
                }
            });
    }
}
