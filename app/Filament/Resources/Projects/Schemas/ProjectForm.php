<?php

namespace App\Filament\Resources\Projects\Schemas;

use App\Enums\InvoicingType;
use App\Enums\ProjectStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([

                // ── Linker kolom (2/3): bewerkbare velden ──
                Section::make('Projectinformatie')
                    ->icon('heroicon-o-information-circle')
                    ->columns(2)
                    ->columnSpan(2)
                    ->schema([
                        TextInput::make('project_number')
                            ->label('Projectnummer')
                            ->required()
                            ->disabled()
                            ->dehydrated()
                            ->columnSpan(1),

                        Select::make('status')
                            ->label('Status')
                            ->options(ProjectStatus::class)
                            ->default(ProjectStatus::Active)
                            ->required()
                            ->columnSpan(1),

                        Select::make('company_id')
                            ->label('Bedrijf')
                            ->relationship('company', 'name', fn ($query) => $query->orderBy('name'))
                            ->required()
                            ->searchable()
                            ->columnSpan(1),

                        Placeholder::make('quote_link')
                            ->label('Offerte')
                            ->content(function ($record) {
                                if (! $record || ! $record->quote) {
                                    return '-';
                                }

                                $quote = $record->quote;
                                $url = route('filament.admin.resources.quotes.view', ['record' => $quote->id]);
                                $total = number_format($quote->total, 2, ',', '.');
                                $displayNum = $quote->displayNumber() ?? 'Concept';

                                return new \Illuminate\Support\HtmlString(
                                    "<a href='{$url}' class='text-primary-600 hover:underline' target='_blank'>
                                        {$displayNum} - &euro;{$total}
                                    </a>"
                                );
                            })
                            ->columnSpan(1),

                        // Hidden field to maintain the relationship
                        Select::make('quote_id')
                            ->relationship('quote', 'quote_number')
                            ->hidden()
                            ->dehydrated(),
                    ]),

                // ── Rechter kolom (1/3): klantgegevens (read-only) ──
                Section::make('Klant')
                    ->icon('heroicon-o-building-office')
                    ->columnSpan(1)
                    ->schema([
                        Placeholder::make('company_details')
                            ->hiddenLabel()
                            ->content(function ($record) {
                                if (! $record || ! $record->company) {
                                    return '-';
                                }

                                $company = $record->company;
                                $html = "<div class='space-y-1'>";
                                $html .= "<div class='font-semibold'>{$company->name}</div>";

                                if ($company->contact_person) {
                                    $html .= "<div class='text-sm text-gray-500'>{$company->contact_person}</div>";
                                }

                                if ($company->street && $company->house_number) {
                                    $html .= "<div>{$company->street} {$company->house_number}</div>";
                                }

                                if ($company->postal_code && $company->city) {
                                    $html .= "<div>{$company->postal_code} {$company->city}</div>";
                                }

                                if ($company->phone) {
                                    $html .= "<div class='text-gray-600 mt-2'>{$company->phone}</div>";
                                }

                                if ($company->email) {
                                    $html .= "<div class='text-gray-600'>{$company->email}</div>";
                                }

                                $html .= '</div>';

                                return new \Illuminate\Support\HtmlString($html);
                            }),

                        Placeholder::make('work_address')
                            ->label('Werklocatie')
                            ->content(fn ($record) => $record?->quote?->workFullAddress() ?: '-'),
                    ]),

                // ── Planning & Facturatie ──
                Section::make('Planning & Facturatie')
                    ->icon('heroicon-o-calendar-days')
                    ->columns(3)
                    ->columnSpan(2)
                    ->schema([
                        DatePicker::make('start_date')
                            ->label('Montagedatum')
                            ->native(false)
                            ->displayFormat('d-m-Y')
                            ->columnSpan(1),

                        DatePicker::make('completion_date')
                            ->label('Verwachte opleverdatum')
                            ->native(false)
                            ->displayFormat('d-m-Y')
                            ->columnSpan(1),

                        Select::make('invoicing_type')
                            ->label('Facturatiemethode')
                            ->options(InvoicingType::class)
                            ->default(InvoicingType::Split5050)
                            ->required()
                            ->live()
                            ->helperText(fn ($state) => $state instanceof InvoicingType ? $state->getDescription() : null)
                            ->columnSpan(1),
                    ]),

                // ── Notities ──
                Section::make('Notities')
                    ->icon('heroicon-o-chat-bubble-bottom-center-text')
                    ->columnSpan(2)
                    ->collapsible()
                    ->schema([
                        Textarea::make('notes')
                            ->hiddenLabel()
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),

                // ── Foto's ──
                Section::make("Foto's")
                    ->icon('heroicon-o-photo')
                    ->columnSpanFull()
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('project-photos')
                            ->hiddenLabel()
                            ->collection('project-photos')
                            ->multiple()
                            ->image()
                            ->imagePreviewHeight('150')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif'])
                            ->maxSize(10240)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
