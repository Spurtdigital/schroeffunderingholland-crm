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
use Filament\Schemas\Schema;

class ProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                // Projectinformatie
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
                        $url = route('filament.admin.resources.quotes.edit', ['record' => $quote->id]);
                        $total = number_format($quote->total, 2, ',', '.');

                        return new \Illuminate\Support\HtmlString(
                            "<a href='{$url}' class='text-primary-600 hover:underline' target='_blank'>
                                {$quote->quote_number} - €{$total}
                            </a>"
                        );
                    })
                    ->columnSpan(1),

                // Hidden field to maintain the relationship
                Select::make('quote_id')
                    ->relationship('quote', 'quote_number')
                    ->hidden()
                    ->dehydrated(),

                // NAW Gegevens
                Placeholder::make('company_details')
                    ->label('Klantgegevens')
                    ->content(function ($record) {
                        if (! $record || ! $record->company) {
                            return '-';
                        }

                        $company = $record->company;
                        $html = "<div class='space-y-1'>";
                        $html .= "<div class='font-semibold'>{$company->name}</div>";

                        if ($company->street && $company->house_number) {
                            $html .= "<div>{$company->street} {$company->house_number}</div>";
                        }

                        if ($company->postal_code && $company->city) {
                            $html .= "<div>{$company->postal_code} {$company->city}</div>";
                        }

                        if ($company->email) {
                            $html .= "<div class='text-gray-600'>✉ {$company->email}</div>";
                        }

                        if ($company->phone) {
                            $html .= "<div class='text-gray-600'>☎ {$company->phone}</div>";
                        }

                        $html .= '</div>';

                        return new \Illuminate\Support\HtmlString($html);
                    })
                    ->columnSpanFull(),

                // Planning
                DatePicker::make('completion_date')
                    ->label('Verwachte opleverdatum')
                    ->native(false)
                    ->displayFormat('d-m-Y')
                    ->columnSpanFull(),

                // Facturatie
                Select::make('invoicing_type')
                    ->label('Facturatiemethode')
                    ->options(InvoicingType::class)
                    ->default(InvoicingType::Split5050)
                    ->required()
                    ->live()
                    ->helperText(fn ($state) => $state instanceof InvoicingType ? $state->getDescription() : null)
                    ->columnSpanFull(),

                // Opmerkingen
                Textarea::make('notes')
                    ->label('Notities')
                    ->rows(4)
                    ->columnSpanFull(),

                // Projectfoto's
                SpatieMediaLibraryFileUpload::make('project-photos')
                    ->label('Projectfoto\'s')
                    ->collection('project-photos')
                    ->multiple()
                    ->image()
                    ->imagePreviewHeight('150')
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif'])
                    ->maxSize(10240)
                    ->columnSpanFull(),
            ]);
    }
}
