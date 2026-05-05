<?php

namespace App\Filament\Resources\Quotes\Pages;

use App\Filament\Resources\Quotes\Actions\ApproveQuoteAction;
use App\Filament\Resources\Quotes\Actions\ConvertToProjectAction;
use App\Filament\Resources\Quotes\Actions\GeneratePdfAction;
use App\Filament\Resources\Quotes\Actions\SendQuoteAction;
use App\Filament\Resources\Quotes\Actions\SendToMoneybirdAction;
use App\Filament\Resources\Quotes\Actions\SyncFromMoneybirdAction;
use App\Filament\Resources\Quotes\Actions\UpdateMoneybirdAction;
use App\Filament\Resources\Quotes\QuoteResource;
use App\Filament\Resources\Quotes\Schemas\QuoteForm;
use App\Models\Machine;
use App\Models\PaymentTerm;
use App\Models\Quote;
use App\Models\QuoteAssumption;
use App\Services\QuoteCalculationService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section as FormSection;
use Filament\Schemas\Components\Utilities\Get;
use Livewire\Attributes\On;

class ViewQuote extends ViewRecord
{
    protected static string $resource = QuoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->color('primary')
                ->modalHeading('Offerte bewerken')
                ->modalWidth('5xl')
                ->form(fn ($form) => QuoteForm::configure($form))
                ->after(function (Quote $record, array $data) {
                    app(QuoteCalculationService::class)->calculateTotals($record);

                    $assumptionIds = $data['assumption_ids'] ?? [];
                    $record->assumptions()->sync($assumptionIds);

                    $this->autoSyncSkuAssumptions($record);

                    $this->refreshFormData([
                        'status', 'subtotal', 'vat_amount', 'total',
                        'pre_text', 'post_text', 'payment_term_id',
                        'valid_until', 'notes', 'sent_at',
                    ]);
                }),

            SendQuoteAction::make(),
            ApproveQuoteAction::make(),
            ConvertToProjectAction::make(),

            // PDF dropdown
            ActionGroup::make([
                GeneratePdfAction::make(),

                Action::make('toggleItemBreakdown')
                    ->label(fn (Quote $record) => $record->show_item_breakdown ? 'Regels verbergen op PDF' : 'Regels tonen op PDF')
                    ->icon(fn (Quote $record) => $record->show_item_breakdown ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                    ->action(function (Quote $record) {
                        $record->update(['show_item_breakdown' => ! $record->show_item_breakdown]);
                        $this->refreshFormData(['show_item_breakdown']);
                    }),
            ])
                ->label('PDF')
                ->icon('heroicon-o-document-text')
                ->color('info')
                ->button(),

            // Snel bewerken dropdown
            ActionGroup::make([
                Action::make('editTexts')
                    ->label('Teksten bewerken')
                    ->icon('heroicon-o-document-text')
                    ->fillForm(fn (Quote $record) => [
                        'reference' => $record->reference,
                        'notes' => $record->notes,
                        'pre_text' => $record->pre_text,
                        'post_text' => $record->post_text,
                    ])
                    ->form([
                        TextInput::make('reference')
                            ->label('Kenmerk (Moneybird)')
                            ->helperText('Bijv. "Overkapping Markelo" — wordt als kenmerk zichtbaar in Moneybird.')
                            ->maxLength(255)
                            ->nullable(),
                        Textarea::make('notes')
                            ->label('Opmerkingen')
                            ->rows(4),
                        Textarea::make('pre_text')
                            ->label('Voorwoord (Moneybird)')
                            ->helperText('Tekst die boven de regelitems verschijnt in de Moneybird-offerte.')
                            ->rows(4),
                        Textarea::make('post_text')
                            ->label('Nawoord vrije tekst (Moneybird)')
                            ->helperText('Extra tekst die na de betalingsvoorwaarden verschijnt.')
                            ->rows(4),
                    ])
                    ->action(function (Quote $record, array $data) {
                        $record->update([
                            'reference' => $data['reference'],
                            'notes' => $data['notes'],
                            'pre_text' => $data['pre_text'],
                            'post_text' => $data['post_text'],
                        ]);
                        $this->record = $record->fresh();
                        Notification::make()->success()->title('Teksten opgeslagen')->send();
                    }),

                Action::make('editAssumptions')
                    ->label('Aannames bewerken')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->fillForm(fn (Quote $record) => [
                        'assumption_ids' => $record->assumptions()->pluck('quote_assumptions.id')->toArray(),
                    ])
                    ->form([
                        CheckboxList::make('assumption_ids')
                            ->label('Offerte-aannames')
                            ->options(fn () => QuoteAssumption::where('active', true)->orderBy('sort_order')->pluck('title', 'id')->toArray())
                            ->columns(2),
                    ])
                    ->action(function (Quote $record, array $data) {
                        $record->assumptions()->sync($data['assumption_ids'] ?? []);
                        $this->autoSyncSkuAssumptions($record);
                        $this->refreshFormData(['assumptions']);
                        Notification::make()->success()->title('Aannames opgeslagen')->send();
                    }),

                Action::make('editPaymentTerm')
                    ->label('Betalingsvoorwaarde')
                    ->icon('heroicon-o-banknotes')
                    ->fillForm(fn (Quote $record) => [
                        'payment_term_id' => $record->payment_term_id,
                    ])
                    ->form([
                        Select::make('payment_term_id')
                            ->label('Betalingsvoorwaarde')
                            ->options(fn () => PaymentTerm::where('active', true)->orderBy('sort_order')->pluck('title', 'id')->toArray())
                            ->placeholder('Geen betalingsvoorwaarde')
                            ->searchable()
                            ->nullable(),
                    ])
                    ->action(function (Quote $record, array $data) {
                        $record->update(['payment_term_id' => $data['payment_term_id']]);
                        $this->refreshFormData(['payment_term_id']);
                        Notification::make()->success()->title('Betalingsvoorwaarde opgeslagen')->send();
                    }),

                Action::make('editTravel')
                    ->label('Reiskosten bewerken')
                    ->icon('heroicon-o-truck')
                    ->fillForm(fn (Quote $record) => [
                        'travel_distance' => $record->travel_distance,
                        'travel_cost_per_km' => $record->travel_cost_per_km,
                    ])
                    ->form([
                        TextInput::make('travel_distance')
                            ->label('Reisafstand (km)')
                            ->numeric()
                            ->suffix('km')
                            ->minValue(0),
                        TextInput::make('travel_cost_per_km')
                            ->label('Kosten per km')
                            ->numeric()
                            ->prefix('€')
                            ->minValue(0),
                    ])
                    ->action(function (Quote $record, array $data) {
                        $record->update([
                            'travel_distance' => $data['travel_distance'],
                            'travel_cost_per_km' => $data['travel_cost_per_km'],
                        ]);
                        app(QuoteCalculationService::class)->calculateTotals($record);
                        $this->refreshFormData(['travel_distance', 'travel_cost_per_km', 'travel_total', 'subtotal', 'vat_amount', 'total']);
                        Notification::make()->success()->title('Reiskosten opgeslagen')->send();
                    }),
            ])
                ->label('Snel bewerken')
                ->icon('heroicon-o-pencil-square')
                ->color('warning')
                ->button(),

            // Instellingen dropdown
            ActionGroup::make([
                Action::make('editWorkAddress')
                    ->label('Werkadres & machine')
                    ->icon('heroicon-o-map-pin')
                    ->fillForm(fn (Quote $record) => [
                        'work_address_different' => $record->work_address_different,
                        'work_street' => $record->work_street,
                        'work_house_number' => $record->work_house_number,
                        'work_postal_code' => $record->work_postal_code,
                        'work_city' => $record->work_city,
                        'machine_id' => $record->machine_id,
                    ])
                    ->form([
                        Select::make('machine_id')
                            ->label('Montage machine')
                            ->options(fn () => Machine::where('active', true)->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->nullable()
                            ->placeholder('Geen machine geselecteerd'),

                        Toggle::make('work_address_different')
                            ->label('Werkadres wijkt af van factuuradres')
                            ->live(),

                        FormSection::make()
                            ->columns(3)
                            ->schema([
                                TextInput::make('work_street')
                                    ->label('Straat')
                                    ->visible(fn (Get $get) => $get('work_address_different'))
                                    ->columnSpan(2),

                                TextInput::make('work_house_number')
                                    ->label('Huisnummer')
                                    ->visible(fn (Get $get) => $get('work_address_different'))
                                    ->columnSpan(1),

                                TextInput::make('work_postal_code')
                                    ->label('Postcode')
                                    ->visible(fn (Get $get) => $get('work_address_different'))
                                    ->columnSpan(1),

                                TextInput::make('work_city')
                                    ->label('Stad')
                                    ->visible(fn (Get $get) => $get('work_address_different'))
                                    ->columnSpan(2),
                            ])
                            ->visible(fn (Get $get) => $get('work_address_different'))
                            ->extraAttributes(['style' => 'padding:0;box-shadow:none;border:none;']),
                    ])
                    ->action(function (Quote $record, array $data) {
                        $record->update([
                            'machine_id' => $data['machine_id'],
                            'work_address_different' => $data['work_address_different'],
                            'work_street' => $data['work_street'] ?? null,
                            'work_house_number' => $data['work_house_number'] ?? null,
                            'work_postal_code' => $data['work_postal_code'] ?? null,
                            'work_city' => $data['work_city'] ?? null,
                        ]);

                        Notification::make()
                            ->success()
                            ->title('Opgeslagen')
                            ->send();

                        $this->refreshFormData(['machine_id', 'work_address_different', 'work_street', 'work_house_number', 'work_postal_code', 'work_city']);
                    }),
            ])
                ->label('Instellingen')
                ->icon('heroicon-o-cog-6-tooth')
                ->color('gray')
                ->button(),

            // Moneybird dropdown
            ActionGroup::make([
                SendToMoneybirdAction::make(),
                UpdateMoneybirdAction::make(),
                SyncFromMoneybirdAction::make(),
            ])
                ->label('Moneybird')
                ->icon('heroicon-o-cloud-arrow-up')
                ->color('gray')
                ->button(),
        ];
    }

    public function getRelationManagers(): array
    {
        return [];
    }

    #[On('quote-totals-updated')]
    public function refreshRecord(): void
    {
        $this->record = $this->record->fresh();
    }

    /**
     * Check quote items for SKU matches and auto-add triggered assumptions.
     */
    protected function autoSyncSkuAssumptions(Quote $record): void
    {
        $skus = $record->items()
            ->with('itemable')
            ->get()
            ->filter(fn ($item) => $item->itemable && method_exists($item->itemable, 'getAttribute'))
            ->map(fn ($item) => strtoupper((string) $item->itemable->sku))
            ->filter()
            ->unique()
            ->values();

        if ($skus->isEmpty()) {
            return;
        }

        $triggered = QuoteAssumption::where('active', true)
            ->whereNotNull('trigger_sku')
            ->get()
            ->filter(function (QuoteAssumption $assumption) use ($skus) {
                $trigger = strtoupper(trim($assumption->trigger_sku));

                return $skus->contains(fn ($sku) => str_contains($sku, $trigger));
            })
            ->pluck('id');

        if ($triggered->isNotEmpty()) {
            $record->assumptions()->syncWithoutDetaching($triggered->toArray());
        }
    }
}
