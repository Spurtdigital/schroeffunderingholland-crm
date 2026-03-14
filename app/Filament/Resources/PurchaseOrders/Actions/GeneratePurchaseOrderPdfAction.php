<?php

namespace App\Filament\Resources\PurchaseOrders\Actions;

use App\Models\PurchaseOrder;
use Filament\Actions\Action;

class GeneratePurchaseOrderPdfAction extends Action
{
    public static function make(?string $name = 'generatePdf'): static
    {
        return parent::make($name)
            ->label('PDF downloaden')
            ->icon('heroicon-o-document-arrow-down')
            ->color('info')
            ->url(fn (PurchaseOrder $record) => route('purchase-orders.pdf', $record))
            ->openUrlInNewTab();
    }
}
