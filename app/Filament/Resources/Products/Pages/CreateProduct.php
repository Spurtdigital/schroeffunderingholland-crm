<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    /**
     * Sync pivot data from the form's product_suppliers repeater after create.
     */
    protected function afterCreate(): void
    {
        $this->syncSuppliers();
    }

    private function syncSuppliers(): void
    {
        $suppliers = collect($this->data['product_suppliers'] ?? []);

        $syncData = [];
        foreach ($suppliers as $entry) {
            if (empty($entry['supplier_id'])) {
                continue;
            }

            $syncData[$entry['supplier_id']] = [
                'supplier_sku' => $entry['supplier_sku'] ?? null,
                'purchase_price' => $entry['purchase_price'] ?? 0,
                'is_preferred' => $entry['is_preferred'] ?? false,
            ];
        }

        $this->record->suppliers()->sync($syncData);
    }
}
