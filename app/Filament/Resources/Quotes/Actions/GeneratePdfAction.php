<?php

namespace App\Filament\Resources\Quotes\Actions;

use App\Models\Quote;
use Filament\Actions\Action;

class GeneratePdfAction extends Action
{
    public static function make(?string $name = 'generatePdf'): static
    {
        return parent::make($name)
            ->label('Genereer PDF')
            ->icon('heroicon-o-document-arrow-down')
            ->color('info')
            ->url(fn (Quote $record) => route('quotes.pdf', $record))
            ->openUrlInNewTab();
    }
}
