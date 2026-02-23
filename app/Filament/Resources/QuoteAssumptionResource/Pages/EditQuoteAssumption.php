<?php

namespace App\Filament\Resources\QuoteAssumptionResource\Pages;

use App\Filament\Resources\QuoteAssumptionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditQuoteAssumption extends EditRecord
{
    protected static string $resource = QuoteAssumptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
