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
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProject extends EditRecord
{
    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Hoofdactie: project afronden
            CompleteProjectAction::make(),

            // Facturatie
            GenerateInvoiceAction::make(),

            // Documenten dropdown
            ActionGroup::make([
                GenerateCompletionReportAction::make(),
                GeneratePackingSlipAction::make(),
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

            DeleteAction::make(),
        ];
    }
}
