<?php

namespace App\Filament\Resources\Quotes\Pages;

use App\Filament\Resources\Quotes\QuoteResource;
use Filament\Resources\Pages\CreateRecord;

class CreateQuote extends CreateRecord
{
    protected static string $resource = QuoteResource::class;

    protected function afterCreate(): void
    {
        // Merge both checkbox lists (bullet clauses + section clauses) and sync
        $clauseIds = array_merge(
            $this->data['clause_ids'] ?? [],
            $this->data['section_clause_ids'] ?? [],
        );
        $this->getRecord()->clauses()->sync($clauseIds);
    }
}
