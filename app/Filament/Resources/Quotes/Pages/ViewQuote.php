<?php

namespace App\Filament\Resources\Quotes\Pages;

use App\Filament\Resources\Quotes\Actions\ApproveQuoteAction;
use App\Filament\Resources\Quotes\Actions\ConvertToProjectAction;
use App\Filament\Resources\Quotes\Actions\GeneratePdfAction;
use App\Filament\Resources\Quotes\Actions\SendQuoteAction;
use App\Filament\Resources\Quotes\Actions\SendToMoneybirdAction;
use App\Filament\Resources\Quotes\Actions\SyncFromMoneybirdAction;
use App\Filament\Resources\Quotes\Actions\UpdateMoneybirdAction;
use App\Filament\Resources\Quotes\QuoteResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewQuote extends ViewRecord
{
    protected static string $resource = QuoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->color('primary'),
            GeneratePdfAction::make(),
            SendQuoteAction::make(),
            ApproveQuoteAction::make(),
            ConvertToProjectAction::make(),
            SendToMoneybirdAction::make(),
            UpdateMoneybirdAction::make(),
            SyncFromMoneybirdAction::make(),
        ];
    }

    public function getRelationManagers(): array
    {
        return [];
    }
}
