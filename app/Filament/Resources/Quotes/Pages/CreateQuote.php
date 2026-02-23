<?php

namespace App\Filament\Resources\Quotes\Pages;

use App\Filament\Resources\Quotes\QuoteResource;
use Filament\Resources\Pages\CreateRecord;

class CreateQuote extends CreateRecord
{
    protected static string $resource = QuoteResource::class;

    protected function afterCreate(): void
    {
        // Sync selected clausules
        $clauseIds = $this->data['clause_ids'] ?? [];
        $this->getRecord()->clauses()->sync($clauseIds);
    }
}
