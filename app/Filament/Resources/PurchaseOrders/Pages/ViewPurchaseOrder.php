<?php

namespace App\Filament\Resources\PurchaseOrders\Pages;

use App\Filament\Resources\PurchaseOrders\Actions\GeneratePurchaseOrderPdfAction;
use App\Filament\Resources\PurchaseOrders\Actions\ReceiveMaterialsAction;
use App\Filament\Resources\PurchaseOrders\PurchaseOrderResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPurchaseOrder extends ViewRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ReceiveMaterialsAction::make(),
            GeneratePurchaseOrderPdfAction::make(),
            EditAction::make()->label('Bewerken'),
        ];
    }
}
