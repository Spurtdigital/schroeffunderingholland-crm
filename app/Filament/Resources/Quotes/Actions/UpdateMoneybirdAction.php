<?php

namespace App\Filament\Resources\Quotes\Actions;

use App\Models\PaymentTerm;
use App\Models\Quote;
use App\Models\QuoteAssumption;
use App\Services\MoneybirdService;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;

class UpdateMoneybirdAction extends Action
{
    public static function make(?string $name = 'updateMoneybird'): static
    {
        return parent::make($name)
            ->label('Bijwerken in Moneybird')
            ->icon('heroicon-o-arrow-up-circle')
            ->color('info')
            ->modalHeading('Offerte bijwerken in Moneybird')
            ->modalDescription('Pas indien nodig de tekst aan. De regels en tekst worden overschreven in Moneybird met de huidige gegevens.')
            ->modalSubmitActionLabel('Bijwerken in Moneybird')
            ->modalWidth('3xl')
            ->visible(fn (Quote $record) => (bool) $record->moneybird_quote_id)
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
            ->action(function (Quote $record, array $data, MoneybirdService $moneybirdService) {
                try {
                    if (! $moneybirdService->isConfigured()) {
                        Notification::make()->danger()
                            ->title('Moneybird niet geconfigureerd')
                            ->body('Configureer eerst de Moneybird API instellingen.')
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

                    // Push to Moneybird (will auto-recreate if 404)
                    $hadId = (bool) $record->moneybird_quote_id;
                    $moneybirdService->updateQuote($record);
                    $record->refresh();

                    $isNew = $hadId && ! $record->moneybird_quote_id === false;
                    Notification::make()->success()
                        ->title('Offerte bijgewerkt in Moneybird')
                        ->body("Offerte {$record->quote_number} is succesvol bijgewerkt in Moneybird.")
                        ->send();
                } catch (\Exception $e) {
                    Notification::make()->danger()
                        ->title('Bijwerken mislukt')
                        ->body($e->getMessage())
                        ->send();
                }
            });
    }
}
