<?php

namespace App\Filament\Resources\QuoteClauseResource\Pages;

use App\Filament\Resources\QuoteClauseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListQuoteClauses extends ListRecords
{
    protected static string $resource = QuoteClauseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
