<?php

namespace App\Filament\Resources\Projects\Schemas;

use App\Filament\Resources\Projects\Pages\ViewProject;
use App\Filament\Resources\Projects\RelationManagers\InvoicesRelationManager;
use App\Filament\Resources\Projects\RelationManagers\ItemsRelationManager;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProjectInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(4)
            ->components([

                // ── Rij 1: Projectinformatie – volledige breedte ──────────────
                Section::make('Projectinformatie')
                    ->columns(5)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('project_number')
                            ->label('Projectnummer')
                            ->weight('bold')
                            ->size('lg'),

                        TextEntry::make('status')
                            ->label('Status')
                            ->badge(),

                        TextEntry::make('invoicing_type')
                            ->label('Facturatiemethode'),

                        TextEntry::make('start_date')
                            ->label('Startdatum')
                            ->date('d-m-Y')
                            ->placeholder('-'),

                        TextEntry::make('completion_date')
                            ->label('Opleverdatum')
                            ->date('d-m-Y')
                            ->placeholder('-'),
                    ]),

                // ── Kolom links 3/4: Items + Facturen ────────────────────────
                Group::make()
                    ->columnSpan(3)
                    ->schema([
                        Livewire::make(ItemsRelationManager::class, fn ($record) => [
                            'ownerRecord' => $record,
                            'pageClass' => ViewProject::class,
                        ]),

                        Livewire::make(InvoicesRelationManager::class, fn ($record) => [
                            'ownerRecord' => $record,
                            'pageClass' => ViewProject::class,
                        ]),
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

                        Section::make('Offerte & Werklocatie')
                            ->schema([
                                TextEntry::make('quote.quote_number')
                                    ->label('Offertenummer')
                                    ->url(fn ($record) => $record->quote
                                        ? route('filament.admin.resources.quotes.view', ['record' => $record->quote_id])
                                        : null
                                    )
                                    ->openUrlInNewTab()
                                    ->placeholder('-'),

                                TextEntry::make('quote.total')
                                    ->label('Offertewaarde')
                                    ->money('EUR')
                                    ->placeholder('-'),

                                TextEntry::make('quote.lead.full_address')
                                    ->label('Werklocatie')
                                    ->placeholder('-'),
                            ]),

                        Section::make('Historie')
                            ->schema([
                                ViewEntry::make('activity')
                                    ->label('')
                                    ->view('filament.components.project-activity'),
                            ]),

                        Section::make("Foto's")
                            ->schema([
                                ViewEntry::make('photos')
                                    ->label('')
                                    ->view('filament.components.project-photos'),
                            ]),
                    ]),

            ]);
    }
}
