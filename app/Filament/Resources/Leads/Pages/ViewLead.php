<?php

namespace App\Filament\Resources\Leads\Pages;

use App\Filament\Resources\Leads\Actions\ConvertToQuoteAction;
use App\Filament\Resources\Leads\Actions\RequestMoreInfoAction;
use App\Filament\Resources\Leads\LeadResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewLead extends ViewRecord
{
    protected static string $resource = LeadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->color('primary'),
            ConvertToQuoteAction::make(),
            RequestMoreInfoAction::make(),
        ];
    }

    public function getRelationManagers(): array
    {
        return [];
    }
}
