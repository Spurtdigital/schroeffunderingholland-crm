<?php

namespace App\Filament\Resources\Quotes\Schemas;

use App\Enums\QuoteProcessingMethod;
use App\Enums\QuoteStatus;
use App\Models\Machine;
use App\Models\PaymentTerm;
use App\Models\QuoteAssumption;
use App\Settings\GeneralSettings;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class QuoteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([

                // ── Linker kolom (2/3): offerte-informatie ──
                Section::make('Offerte-informatie')
                    ->icon('heroicon-o-information-circle')
                    ->columns(2)
                    ->columnSpan(2)
                    ->schema([
                        TextInput::make('quote_number')
                            ->label('Offertenummer')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn ($state) => str_starts_with($state ?? '', 'CONCEPT-')
                                ? 'Concept (nog niet verstuurd naar Moneybird)'
                                : $state
                            )
                            ->placeholder('Wordt automatisch gegenereerd')
                            ->columnSpan(1),

                        Select::make('status')
                            ->label('Status')
                            ->options(QuoteStatus::class)
                            ->default('draft')
                            ->required()
                            ->columnSpan(1),

                        Select::make('company_id')
                            ->label('Bedrijf')
                            ->relationship('company', 'name', fn ($query) => $query->orderBy('name'))
                            ->searchable()
                            ->required()
                            ->columnSpan(1),

                        Select::make('lead_id')
                            ->label('Lead')
                            ->relationship('lead', 'name')
                            ->searchable()
                            ->preload()
                            ->columnSpan(1),

                        Select::make('machine_id')
                            ->label('Montage machine')
                            ->options(fn () => Machine::where('active', true)->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->nullable()
                            ->placeholder('Geen machine geselecteerd')
                            ->columnSpan(1),

                        DatePicker::make('valid_until')
                            ->label('Geldig tot')
                            ->default(now()->addDays(30))
                            ->required()
                            ->columnSpan(1),

                        Select::make('processing_method')
                            ->label('Verwerkingsmethode')
                            ->options(QuoteProcessingMethod::class)
                            ->default(QuoteProcessingMethod::System)
                            ->required()
                            ->helperText('Kies of de offerte via het systeem of als concept in Moneybird wordt verwerkt.')
                            ->columnSpan(1),

                        Toggle::make('show_item_breakdown')
                            ->label('Regels tonen op PDF')
                            ->helperText('Uitgeschakeld: PDF toont alleen het totaalbedrag, zonder losse regels en subbedragen.')
                            ->default(true)
                            ->columnSpan(1),

                        TextInput::make('reference')
                            ->label('Kenmerk (Moneybird)')
                            ->helperText('Bijv. "Overkapping Markelo" — wordt als kenmerk zichtbaar in Moneybird.')
                            ->maxLength(255)
                            ->nullable()
                            ->columnSpanFull(),
                    ]),

                // ── Rechter kolom (1/3): bedragen (read-only) ──
                Section::make('Bedragen')
                    ->icon('heroicon-o-calculator')
                    ->columnSpan(1)
                    ->schema([
                        TextInput::make('subtotal')
                            ->label('Subtotaal (excl. BTW)')
                            ->numeric()
                            ->prefix('€')
                            ->disabled()
                            ->dehydrated()
                            ->default(0.0),
                        TextInput::make('vat_amount')
                            ->label('BTW bedrag')
                            ->numeric()
                            ->prefix('€')
                            ->disabled()
                            ->dehydrated()
                            ->default(0.0),
                        TextInput::make('total')
                            ->label('Totaal (incl. BTW)')
                            ->numeric()
                            ->prefix('€')
                            ->disabled()
                            ->dehydrated()
                            ->default(0.0),
                    ]),

                // ── Teksten ──
                Section::make('Teksten')
                    ->icon('heroicon-o-document-text')
                    ->columns(1)
                    ->columnSpanFull()
                    ->collapsible()
                    ->schema([
                        Textarea::make('notes')
                            ->label('Opmerkingen')
                            ->rows(4)
                            ->columnSpanFull(),
                        Textarea::make('pre_text')
                            ->label('Voorwoord (Moneybird)')
                            ->helperText('Tekst die boven de regelitems verschijnt in de Moneybird-offerte.')
                            ->default(fn () => app(GeneralSettings::class)->default_pre_text)
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),

                // ── Offerte-aannames ──
                Section::make('Offerte-aannames')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->description('Selecteer welke aannames van toepassing zijn. Worden als bulletlijst opgenomen in de offerte. Aannames met een SKU-koppeling worden automatisch aangevinkt op basis van de producten in de offerte.')
                    ->collapsible()
                    ->columnSpanFull()
                    ->schema([
                        Toggle::make('show_assumptions')
                            ->label('Toon uitgangspunten op offerte PDF')
                            ->default(true)
                            ->live()
                            ->helperText('Zet dit uit om de uitgangspunten/aannames niet op de offerte te tonen')
                            ->columnSpanFull(),

                        CheckboxList::make('assumption_ids')
                            ->hiddenLabel()
                            ->options(fn () => QuoteAssumption::where('active', true)->orderBy('sort_order')->pluck('title', 'id')->toArray())
                            ->default([])
                            ->afterStateHydrated(function (CheckboxList $component, $record) {
                                if ($record) {
                                    $selected = $record->assumptions()->pluck('quote_assumptions.id')->toArray();
                                    $component->state($selected);
                                }
                            })
                            ->columns(2)
                            ->columnSpanFull()
                            ->visible(fn (Get $get) => $get('show_assumptions') !== false),
                    ]),

                // ── Betalingsvoorwaarden ──
                Section::make('Betalingsvoorwaarden')
                    ->icon('heroicon-o-banknotes')
                    ->collapsible()
                    ->columnSpanFull()
                    ->schema([
                        Select::make('payment_term_id')
                            ->label('Betalingsvoorwaarde')
                            ->options(fn () => PaymentTerm::where('active', true)->orderBy('sort_order')->pluck('title', 'id')->toArray())
                            ->default(fn () => PaymentTerm::where('active', true)->where('is_default', true)->value('id'))
                            ->placeholder('Selecteer betalingsvoorwaarde')
                            ->searchable()
                            ->nullable()
                            ->columnSpanFull(),

                        Textarea::make('post_text')
                            ->label('Nawoord vrije tekst (Moneybird)')
                            ->helperText('Extra tekst die ná de betalingsvoorwaarden verschijnt in de Moneybird-offerte. De vaste sluitingstekst wordt automatisch toegevoegd vanuit de instellingen.')
                            ->default(fn () => app(GeneralSettings::class)->default_post_text)
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),

                // ── Reiskosten ──
                Section::make('Reiskosten')
                    ->icon('heroicon-o-truck')
                    ->description('Optioneel: reiskosten worden bij het subtotaal opgeteld.')
                    ->columns(3)
                    ->collapsible()
                    ->collapsed(fn ($record) => blank($record?->travel_distance))
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('travel_distance')
                            ->label('Reisafstand (km)')
                            ->numeric()
                            ->suffix('km')
                            ->minValue(0)
                            ->columnSpan(1),
                        TextInput::make('travel_cost_per_km')
                            ->label('Kosten per km')
                            ->numeric()
                            ->prefix('€')
                            ->minValue(0)
                            ->columnSpan(1),
                        TextInput::make('travel_total')
                            ->label('Totale reiskosten')
                            ->numeric()
                            ->prefix('€')
                            ->disabled()
                            ->dehydrated()
                            ->helperText('Wordt automatisch berekend (afstand × kosten/km).')
                            ->columnSpan(1),
                    ]),

                // ── Werkadres ──
                Section::make('Werkadres')
                    ->icon('heroicon-o-map-pin')
                    ->description('Vul in als het werkadres afwijkt van het factuuradres van de klant.')
                    ->columns(3)
                    ->collapsible()
                    ->collapsed(fn ($record) => ! $record?->work_address_different)
                    ->columnSpanFull()
                    ->schema([
                        Toggle::make('work_address_different')
                            ->label('Werkadres wijkt af van factuuradres')
                            ->live()
                            ->columnSpanFull(),
                        TextInput::make('work_street')
                            ->label('Straat')
                            ->columnSpan(2)
                            ->visible(fn (Get $get) => $get('work_address_different')),
                        TextInput::make('work_house_number')
                            ->label('Huisnummer')
                            ->columnSpan(1)
                            ->visible(fn (Get $get) => $get('work_address_different')),
                        TextInput::make('work_postal_code')
                            ->label('Postcode')
                            ->columnSpan(1)
                            ->visible(fn (Get $get) => $get('work_address_different')),
                        TextInput::make('work_city')
                            ->label('Stad')
                            ->columnSpan(2)
                            ->visible(fn (Get $get) => $get('work_address_different')),
                    ]),

                // ── Metadata (read-only) ──
                Section::make('Metadata')
                    ->icon('heroicon-o-clock')
                    ->columns(3)
                    ->collapsible()
                    ->collapsed()
                    ->columnSpanFull()
                    ->schema([
                        DateTimePicker::make('sent_at')
                            ->label('Verzonden op')
                            ->disabled()
                            ->columnSpan(1),
                        DateTimePicker::make('approved_at')
                            ->label('Goedgekeurd op')
                            ->disabled()
                            ->columnSpan(1),
                        TextInput::make('moneybird_quote_id')
                            ->label('Moneybird ID')
                            ->disabled()
                            ->columnSpan(1),
                    ]),
            ]);
    }
}
