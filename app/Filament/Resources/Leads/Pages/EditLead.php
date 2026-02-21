<?php

namespace App\Filament\Resources\Leads\Pages;

use App\Filament\Resources\Leads\Actions\ConvertToQuoteAction;
use App\Filament\Resources\Leads\Actions\RequestMoreInfoAction;
use App\Filament\Resources\Leads\LeadResource;
use App\Filament\Resources\Leads\Widgets\LeadActivityWidget;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLead extends EditRecord
{
    protected static string $resource = LeadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ConvertToQuoteAction::make(),
            RequestMoreInfoAction::make(),
            DeleteAction::make()
                ->label('Verwijderen'),
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            LeadActivityWidget::class,
        ];
    }

    public function getFooterWidgetsColumns(): int|array
    {
        return 1;
    }

    protected function getFooterWidgetData(): array
    {
        return [
            'record' => $this->record,
        ];
    }
}
