<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\Actions\AddStockAction;
use App\Filament\Resources\Products\ProductResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            AddStockAction::make(),
            DeleteAction::make(),
        ];
    }

    /**
     * Load pivot data into the form's product_suppliers repeater.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['product_suppliers'] = $this->record->suppliers()
            ->get()
            ->map(fn ($supplier) => [
                'supplier_id' => $supplier->id,
                'supplier_sku' => $supplier->pivot->supplier_sku,
                'purchase_price' => (float) $supplier->pivot->purchase_price,
                'is_preferred' => (bool) $supplier->pivot->is_preferred,
            ])
            ->toArray();

        return $data;
    }

    /**
     * Sync pivot data from the form's product_suppliers repeater after save.
     */
    protected function afterSave(): void
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
