<?php

namespace App\Filament\Resources\QuoteClauseResource\Pages;

use App\Filament\Resources\QuoteClauseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditQuoteClause extends EditRecord
{
    protected static string $resource = QuoteClauseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
