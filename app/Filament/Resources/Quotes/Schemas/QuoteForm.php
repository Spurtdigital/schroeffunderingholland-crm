<?php

namespace App\Filament\Resources\Quotes\Schemas;

use App\Enums\QuoteProcessingMethod;
use App\Enums\QuoteStatus;
use App\Models\PaymentTerm;
use App\Models\QuoteClause;
use App\Settings\GeneralSettings;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
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

                // Clausules
                Section::make('Clausules sluitingstekst')
                    ->description('Selecteer welke clausules in de sluitingstekst van de Moneybird-offerte verschijnen. Standaard zijn alle actieve clausules aangevinkt.')
                    ->schema([
                        CheckboxList::make('clause_ids')
                            ->label('')
                            ->options(fn () => QuoteClause::where('active', true)->orderBy('sort_order')->pluck('title', 'id')->toArray())
                            ->default(fn () => QuoteClause::where('active', true)->orderBy('sort_order')->pluck('id')->toArray())
                            ->afterStateHydrated(function (CheckboxList $component, $record) {
                                if ($record) {
                                    $selected = $record->clauses()->pluck('quote_clauses.id')->toArray();
                                    if (empty($selected)) {
                                        $selected = QuoteClause::where('active', true)->orderBy('sort_order')->pluck('id')->toArray();
                                    }
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
                    ->helperText('Extra tekst die ná de clausules verschijnt in de Moneybird-offerte. Vaste slottekst (betalingsvoorwaarden, aansprakelijkheid etc.) wordt automatisch samengesteld uit de clausules hierboven.')
                    ->default(fn () => app(GeneralSettings::class)->default_post_text)
                    ->rows(4)
                    ->columnSpanFull(),

                Toggle::make('show_item_breakdown')
                    ->label('Regels tonen op PDF')
                    ->helperText('Uitgeschakeld: PDF toont alleen het totaalbedrag, zonder losse regels en subbedragen.')
                    ->default(true)
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

