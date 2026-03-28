<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Resources\Projects\Actions\CompleteProjectAction;
use App\Filament\Resources\Projects\Actions\CreatePurchaseOrderFromProjectAction;
use App\Filament\Resources\Projects\Actions\GeneratePurchaseOrdersFromProjectAction;
use App\Filament\Resources\Projects\Actions\GenerateCompletionReportAction;
use App\Filament\Resources\Projects\Actions\GenerateInvoiceAction;
use App\Filament\Resources\Projects\Actions\GeneratePackingSlipAction;
use App\Filament\Resources\Projects\Actions\ResendCompletionMailAction;
use App\Filament\Resources\Projects\ProjectResource;
use App\Filament\Resources\Projects\Widgets\ProjectNotesWidget;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewProject extends ViewRecord
{
    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CompleteProjectAction::make(),
            ResendCompletionMailAction::make(),
            GenerateCompletionReportAction::make(),
            GeneratePackingSlipAction::make(),
            GenerateInvoiceAction::make(),
            GeneratePurchaseOrdersFromProjectAction::make(),
            CreatePurchaseOrderFromProjectAction::make(),
            EditAction::make()->color('primary'),
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            ProjectNotesWidget::class,
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
