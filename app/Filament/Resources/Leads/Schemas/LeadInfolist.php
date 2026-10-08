<?php

namespace App\Filament\Resources\Leads\Schemas;

use App\Services\DistanceCalculatorService;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LeadInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(4)
            ->components([

                // ── Rij 1: Lead-informatie – volledige breedte ────────────────
                Section::make('Lead-informatie')
                    ->columns(5)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('name')
                            ->label('Naam')
                            ->weight('bold')
                            ->size('lg'),

                        TextEntry::make('status')
                            ->label('Status')
                            ->badge(),

                        TextEntry::make('email')
                            ->label('E-mail')
                            ->placeholder('-'),

                        TextEntry::make('phone')
                            ->label('Telefoon')
                            ->placeholder('-'),

                        TextEntry::make('created_at')
                            ->label('Ontvangen op')
                            ->dateTime('d-m-Y H:i'),
                    ]),

                // ── Kolom links 3/4: Werklocatie + Notities + Formulier ───────
                Group::make()
                    ->columnSpan(3)
                    ->schema([
                        Section::make('Werklocatie')
                            ->columns(2)
                            ->schema([
                                TextEntry::make('full_address')
                                    ->label('Werklocatie')
                                    ->state(function ($record) {
                                        $address = trim(
                                            ($record->street ?? '').' '.
                                            ($record->house_number ?? '').', '.
                                            ($record->postal_code ?? '').' '.
                                            ($record->city ?? '')
                                        ) ?: null;

                                        if (! $address) {
                                            return '-';
                                        }

                                        $service = app(DistanceCalculatorService::class);
                                        if (! $service->isAvailable()) {
                                            return $address;
                                        }

                                        $result = $service->calculateDistance($address);
                                        if ($result['success']) {
                                            return $address.'||'.$result['distance_km'].'||'.$result['duration_text'];
                                        }

                                        return $address;
                                    })
                                    ->formatStateUsing(function (string $state): string {
                                        $parts = explode('||', $state);
                                        if (count($parts) === 3) {
                                            return e($parts[0]).' <span class="text-gray-500 text-sm font-medium">('.$parts[1].' km)</span>';
                                        }

                                        return e($state);
                                    })
                                    ->html()
                                    ->hintIcon(fn ($state) => str_contains($state, '||') ? 'heroicon-o-information-circle' : null)
                                    ->hintIconTooltip(function (string $state): ?string {
                                        $parts = explode('||', $state);
                                        if (count($parts) === 3) {
                                            return 'Rijafstand vanaf bedrijfslocatie: '.$parts[1].' km ('.$parts[2].')';
                                        }

                                        return null;
                                    })
                                    ->placeholder('-')
                                    ->columnSpan(1),

                                TextEntry::make('company.name')
                                    ->label('Bedrijf')
                                    ->weight('bold')
                                    ->url(fn ($record) => $record->company
                                        ? route('filament.admin.resources.companies.view', ['record' => $record->company_id])
                                        : null
                                    )
                                    ->openUrlInNewTab()
                                    ->columnSpan(1),
                            ]),

                        Section::make('Projectomschrijving')
                            ->schema([
                                TextEntry::make('notes')
                                    ->label('')
                                    ->placeholder('Geen omschrijving.')
                                    ->formatStateUsing(fn (?string $state): string => $state ? nl2br(e($state)) : '')
                                    ->html()
                                    ->columnSpanFull(),
                            ]),

                        Section::make('Interne notities')
                            ->schema([
                                TextEntry::make('internal_notes')
                                    ->label('')
                                    ->placeholder('Geen interne notities.')
                                    ->formatStateUsing(fn (?string $state): string => $state ? nl2br(e($state)) : '')
                                    ->html()
                                    ->columnSpanFull(),
                            ])
                            ->collapsed(fn ($record) => blank($record?->internal_notes))
                            ->collapsible(),

                        Section::make('Formulier gegevens (CF7)')
                            ->schema([
                                ViewEntry::make('request_data')
                                    ->label('')
                                    ->view('filament.components.lead-request-data'),
                            ])
                            ->collapsed()
                            ->collapsible(),
                    ]),

                // ── Kolom rechts 1/4: Klant + Offerte + Historie ──────────────
                Group::make()
                    ->columnSpan(1)
                    ->schema([
                        Section::make('Klant')
                            ->schema([
                                TextEntry::make('company.name')
                                    ->label('Bedrijf')
                                    ->weight('bold')
                                    ->url(fn ($record) => $record->company
                                        ? route('filament.admin.resources.companies.view', ['record' => $record->company_id])
                                        : null
                                    )
                                    ->openUrlInNewTab()
                                    ->placeholder('-'),

                                TextEntry::make('company.phone')
                                    ->label('Telefoon')
                                    ->placeholder('-'),

                                TextEntry::make('company.email')
                                    ->label('E-mail')
                                    ->placeholder('-'),
                            ]),

                        Section::make('Offerte')
                            ->schema([
                                TextEntry::make('convertedToQuote.quote_number')
                                    ->label('Offertenummer')
                                    ->formatStateUsing(fn ($state) => str_starts_with($state ?? '', 'CONCEPT-') ? 'Concept' : $state)
                                    ->color(fn ($state) => str_starts_with($state ?? '', 'CONCEPT-') ? 'warning' : null)
                                    ->url(fn ($record) => $record->convertedToQuote
                                        ? route('filament.admin.resources.quotes.view', ['record' => $record->converted_to_quote_id])
                                        : null
                                    )
                                    ->openUrlInNewTab()
                                    ->placeholder('Nog geen offerte'),

                                TextEntry::make('convertedToQuote.status')
                                    ->label('Offerte status')
                                    ->badge()
                                    ->placeholder('-'),

                                TextEntry::make('convertedToQuote.total')
                                    ->label('Totaal incl. BTW')
                                    ->money('EUR')
                                    ->placeholder('-'),
                            ]),

                        Section::make('Historie')
                            ->schema([
                                ViewEntry::make('activity')
                                    ->label('')
                                    ->view('filament.components.lead-activity'),
                            ]),
                    ]),
            ]);
    }
}
