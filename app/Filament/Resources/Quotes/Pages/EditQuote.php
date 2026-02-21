<?php

namespace App\Filament\Resources\Quotes\Pages;

use App\Filament\Resources\Quotes\Actions\ApproveQuoteAction;
use App\Filament\Resources\Quotes\Actions\ConvertToProjectAction;
use App\Filament\Resources\Quotes\Actions\SendToMoneybirdAction;
use App\Filament\Resources\Quotes\Actions\SyncFromMoneybirdAction;
use App\Filament\Resources\Quotes\Actions\UpdateMoneybirdAction;
use App\Filament\Resources\Quotes\QuoteResource;
use App\Services\QuoteCalculationService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditQuote extends EditRecord
{
    protected static string $resource = QuoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            SendToMoneybirdAction::make(),
            UpdateMoneybirdAction::make(),
            SyncFromMoneybirdAction::make(),
            ApproveQuoteAction::make(),
            ConvertToProjectAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        app(QuoteCalculationService::class)->calculateTotals($this->getRecord());
    }
}
