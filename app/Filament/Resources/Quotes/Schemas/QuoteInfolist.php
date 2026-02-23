<?php

namespace App\Filament\Resources\Quotes\Schemas;

use App\Filament\Resources\Quotes\Pages\ViewQuote;
use App\Filament\Resources\Quotes\RelationManagers\ItemsRelationManager;
use App\Services\DistanceCalculatorService;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class QuoteInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(4)
            ->components([

                // ── Rij 1: Offerte-informatie – volledige breedte ─────────────
                Section::make('Offerte-informatie')
                    ->columns(5)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('quote_number')
                            ->label('Offertenummer')
                            ->weight('bold')
                            ->size('lg'),

                        TextEntry::make('status')
                            ->label('Status')
                            ->badge(),

                        TextEntry::make('total')
                            ->label('Totaal incl. BTW')
                            ->money('EUR'),

                        TextEntry::make('valid_until')
                            ->label('Geldig tot')
                            ->date('d-m-Y')
                            ->placeholder('-'),

                        TextEntry::make('sent_at')
                            ->label('Verzonden op')
                            ->dateTime('d-m-Y H:i')
                            ->placeholder('-'),
                    ]),

                // ── Kolom links 3/4: Regels ───────────────────────────────────
                Group::make()
                    ->columnSpan(3)
                    ->schema([
                        Livewire::make(ItemsRelationManager::class, fn ($record) => [
                            'ownerRecord' => $record,
                            'pageClass' => ViewQuote::class,
                        ]),
                    ]),

                // ── Kolom rechts 1/4: Klant + Lead + Historie ─────────────────
                Group::make()
                    ->columnSpan(1)
                    ->schema([
                        Section::make('Klant')
                            ->schema([
                                TextEntry::make('company.name')
                                    ->label('Bedrijf')
                                    ->weight('bold')
                                    ->url(fn ($record) => $record->company
                                        ? route('filament.admin.resources.companies.edit', ['record' => $record->company_id])
                                        : null
                                    )
                                    ->openUrlInNewTab(),

                                TextEntry::make('company.contact_person')
                                    ->label('Contactpersoon')
                                    ->placeholder('-'),

                                TextEntry::make('company_address')
                                    ->label('Adres')
                                    ->state(fn ($record) => $record->company
                                        ? trim(($record->company->street ?? '').' '.($record->company->house_number ?? ''))
                                        : '-'
                                    )
                                    ->placeholder('-'),

                                TextEntry::make('company_city')
                                    ->label('Plaats')
                                    ->state(fn ($record) => $record->company
                                        ? trim(($record->company->postal_code ?? '').' '.($record->company->city ?? ''))
                                        : '-'
                                    )
                                    ->placeholder('-'),

                                TextEntry::make('company.phone')
                                    ->label('Telefoon')
                                    ->placeholder('-'),

                                TextEntry::make('company.email')
                                    ->label('E-mail')
                                    ->placeholder('-'),
                            ]),

                        Section::make('Lead & Werklocatie')
                            ->schema([
                                TextEntry::make('lead.name')
                                    ->label('Lead')
                                    ->url(fn ($record) => $record->lead
                                        ? route('filament.admin.resources.leads.view', ['record' => $record->lead_id])
                                        : null
                                    )
                                    ->openUrlInNewTab()
                                    ->placeholder('-'),

                                TextEntry::make('lead.full_address')
                                    ->label('Werklocatie')
                                    ->state(function ($record) {
                                        $address = $record->lead?->full_address ?? null;

                                        if (! $address) {
                                            return null;
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
                                    ->formatStateUsing(function (?string $state): string {
                                        if (! $state) {
                                            return '-';
                                        }
                                        $parts = explode('||', $state);
                                        if (count($parts) === 3) {
                                            return e($parts[0]).' <span class="text-gray-500 text-sm font-medium">('.$parts[1].' km)</span>';
                                        }

                                        return e($state);
                                    })
                                    ->html()
                                    ->hintIcon(fn ($state) => $state && str_contains($state, '||') ? 'heroicon-o-information-circle' : null)
                                    ->hintIconTooltip(function (?string $state): ?string {
                                        if (! $state) {
                                            return null;
                                        }
                                        $parts = explode('||', $state);
                                        if (count($parts) === 3) {
                                            return 'Rijafstand vanaf bedrijfslocatie: '.$parts[1].' km ('.$parts[2].')';
                                        }

                                        return null;
                                    })
                                    ->placeholder('-'),

                                TextEntry::make('subtotal')
                                    ->label('Subtotaal excl. BTW')
                                    ->money('EUR'),

                                TextEntry::make('vat_amount')
                                    ->label('BTW')
                                    ->money('EUR'),
                            ]),

                        Section::make('Betalingsvoorwaarden')
                            ->schema([
                                TextEntry::make('paymentTerm.title')
                                    ->label('Betalingsvoorwaarde')
                                    ->placeholder('-'),
                                TextEntry::make('paymentTerm.content')
                                    ->label('Tekst')
                                    ->placeholder('-'),
                            ])
                            ->collapsible()
                            ->collapsed(fn ($record) => $record->payment_term_id === null),

                        Section::make('Historie')
                            ->schema([
                                ViewEntry::make('activity')
                                    ->label('')
                                    ->view('filament.components.quote-activity'),
                            ]),
                    ]),
            ]);
    }
}
