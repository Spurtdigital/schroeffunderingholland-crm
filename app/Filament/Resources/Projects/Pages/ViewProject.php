<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Resources\Projects\Actions\CancelProjectAction;
use App\Filament\Resources\Projects\Actions\CompleteProjectAction;
use App\Filament\Resources\Projects\Actions\CreatePurchaseOrderFromProjectAction;
use App\Filament\Resources\Projects\Actions\GenerateCompletionReportAction;
use App\Filament\Resources\Projects\Actions\GenerateInvoiceAction;
use App\Filament\Resources\Projects\Actions\GenerateOrderConfirmationAction;
use App\Filament\Resources\Projects\Actions\GeneratePackingSlipAction;
use App\Filament\Resources\Projects\Actions\GenerateWorkTicketAction;
use App\Filament\Resources\Projects\Actions\PrintPackingSlipAction;
use App\Filament\Resources\Projects\Actions\PrintWorkTicketAction;
use App\Filament\Resources\Projects\Actions\GeneratePurchaseOrdersFromProjectAction;
use App\Filament\Resources\Projects\Actions\ResendCompletionMailAction;
use App\Filament\Resources\Projects\ProjectResource;
use App\Filament\Resources\Projects\Widgets\ProjectNotesWidget;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewProject extends ViewRecord
{
    protected static string $resource = ProjectResource::class;

    /**
     * Return empty array to prevent relation manager tabs at the bottom
     * of the view page — they are already embedded in the infolist
     * via Livewire::make() in ProjectInfolist.
     */
    protected function getAllRelationManagers(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
    {
        return [
            // Hoofdactie: project afronden
            CompleteProjectAction::make(),

            // Facturatie
            GenerateInvoiceAction::make(),

            // Documenten dropdown
            ActionGroup::make([
                GenerateOrderConfirmationAction::make(),
                GenerateCompletionReportAction::make(),
                GeneratePackingSlipAction::make(),
                PrintPackingSlipAction::make(),
                GenerateWorkTicketAction::make(),
                PrintWorkTicketAction::make(),
                ResendCompletionMailAction::make(),
            ])
                ->label('Documenten')
                ->icon('heroicon-o-document-text')
                ->color('info')
                ->button(),

            // Inkoop dropdown
            ActionGroup::make([
                GeneratePurchaseOrdersFromProjectAction::make(),
                CreatePurchaseOrderFromProjectAction::make(),
            ])
                ->label('Inkoop')
                ->icon('heroicon-o-shopping-cart')
                ->color('warning')
                ->button(),

            // Project annuleren
            CancelProjectAction::make(),

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
