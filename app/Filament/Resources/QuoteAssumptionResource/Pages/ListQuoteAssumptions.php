<?php

namespace App\Filament\Resources\QuoteAssumptionResource\Pages;

use App\Filament\Resources\QuoteAssumptionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListQuoteAssumptions extends ListRecords
{
    protected static string $resource = QuoteAssumptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
