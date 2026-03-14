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
            ->components([
                // Algemene informatie
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
                Select::make('status')
                    ->label('Status')
                    ->options(QuoteStatus::class)
                    ->default('draft')
                    ->required()
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

                // Bedragen (disabled - worden automatisch berekend)
                TextInput::make('subtotal')
                    ->label('Subtotaal (excl. BTW)')
                    ->numeric()
                    ->prefix('€')
                    ->disabled()
                    ->dehydrated()
                    ->default(0.0)
                    ->columnSpan(1),
                TextInput::make('vat_amount')
                    ->label('BTW bedrag')
                    ->numeric()
                    ->prefix('€')
                    ->disabled()
                    ->dehydrated()
                    ->default(0.0)
                    ->columnSpan(1),
                TextInput::make('total')
                    ->label('Totaal (incl. BTW)')
                    ->numeric()
                    ->prefix('€')
                    ->disabled()
                    ->dehydrated()
                    ->default(0.0)
                    ->columnSpan(1),

                // Opmerkingen
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

                // Offerte-aannames
                Section::make('Offerte-aannames')
                    ->description('Selecteer welke aannames van toepassing zijn. Worden als bulletlijst opgenomen in de offerte. Aannames met een SKU-koppeling worden automatisch aangevinkt op basis van de producten in de offerte.')
                    ->schema([
                        CheckboxList::make('assumption_ids')
                            ->label('')
                            ->options(fn () => QuoteAssumption::where('active', true)->orderBy('sort_order')->pluck('title', 'id')->toArray())
                            ->default([])
                            ->afterStateHydrated(function (CheckboxList $component, $record) {
                                if ($record) {
                                    $selected = $record->assumptions()->pluck('quote_assumptions.id')->toArray();
                                    $component->state($selected);
                                }
                            })
                            ->columns(2)
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),

                // Betalingsvoorwaarden
                Section::make('Betalingsvoorwaarden')
                    ->schema([
                        Select::make('payment_term_id')
                            ->label('Betalingsvoorwaarde')
                            ->options(fn () => PaymentTerm::where('active', true)->orderBy('sort_order')->pluck('title', 'id')->toArray())
                            ->default(fn () => PaymentTerm::where('active', true)->where('is_default', true)->value('id'))
                            ->placeholder('Selecteer betalingsvoorwaarde')
                            ->searchable()
                            ->nullable()
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),

                Textarea::make('post_text')
                    ->label('Nawoord vrije tekst (Moneybird)')
                    ->helperText('Extra tekst die ná de betalingsvoorwaarden verschijnt in de Moneybird-offerte. De vaste sluitingstekst wordt automatisch toegevoegd vanuit de instellingen.')
                    ->default(fn () => app(GeneralSettings::class)->default_post_text)
                    ->rows(4)
                    ->columnSpanFull(),

                // Reiskosten
                Section::make('Reiskosten')
                    ->description('Optioneel: reiskosten worden bij het subtotaal opgeteld.')
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
                    ])
                    ->columns(3)
                    ->collapsible()
                    ->collapsed(fn ($record) => blank($record?->travel_distance))
                    ->columnSpanFull(),

                // Werkadres
                Section::make('Werkadres')
                    ->description('Vul in als het werkadres afwijkt van het factuuradres van de klant.')
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
                    ])
                    ->columns(3)
                    ->collapsible()
                    ->collapsed(fn ($record) => ! $record?->work_address_different)
                    ->columnSpanFull(),

                // Metadata (disabled)
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
            ])
            ->columns(3);
    }
}

