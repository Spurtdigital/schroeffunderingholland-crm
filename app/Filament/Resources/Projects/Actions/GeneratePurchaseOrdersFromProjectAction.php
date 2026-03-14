<?php

namespace App\Filament\Resources\Projects\Actions;

use App\Enums\QuoteItemType;
use App\Models\Project;
use App\Models\PurchaseOrder;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
class GeneratePurchaseOrdersFromProjectAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'generatePurchaseOrders';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Genereer inkooporders');
        $this->icon('heroicon-o-sparkles');
        $this->color('success');
        $this->modalHeading('Inkooporders genereren vanuit projectitems');
        $this->modalWidth('lg');
        $this->modalSubmitActionLabel('Aanmaken');
        $this->modalDescription(
            fn (Project $record) => $this->buildModalDescription($record)
        );

        $this->form([]);

        $this->action(function (Project $record) {
            $created = $this->generateOrders($record);

            if ($created === 0) {
                Notification::make()
                    ->warning()
                    ->title('Geen inkooporders aangemaakt')
                    ->body('Er zijn geen projectitems met een gekende leverancier gevonden.')
                    ->send();

                return;
            }

            Notification::make()
                ->success()
                ->title("{$created} inkooporder(s) aangemaakt")
                ->body("De inkooporders zijn aangemaakt en gekoppeld aan project {$record->project_number}.")
                ->send();
        });

        $this->hidden(function (Project $record): bool {
            return ! $this->hasEligibleItems($record);
        });
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Check if the project has any product items with a known supplier.
     */
    private function hasEligibleItems(Project $record): bool
    {
        return $record->items()
            ->where('type', QuoteItemType::Product)
            ->whereNotNull('itemable_id')
            ->whereHasMorph('itemable', \App\Models\Product::class, fn ($q) => $q->whereNotNull('supplier_id'))
            ->exists();
    }

    /**
     * Build the modal description showing a preview of what will be created.
     */
    private function buildModalDescription(Project $record): \Illuminate\Support\HtmlString
    {
        $grouped = $this->groupItemsBySupplier($record);

        if ($grouped->isEmpty()) {
            return new \Illuminate\Support\HtmlString(
                '<p class="text-sm text-gray-500">Geen projectitems met een gekende leverancier gevonden.</p>'
            );
        }

        $lines = ['<p class="text-sm text-gray-600 mb-3">De volgende inkooporders worden aangemaakt:</p><ul class="space-y-1">'];

        foreach ($grouped as $supplierId => $items) {
            $supplier = $items->first()->itemable->supplier;
            $count = $items->count();
            $lines[] = sprintf(
                '<li class="text-sm"><span class="font-medium">%s</span> — %d regel%s</li>',
                e($supplier->name),
                $count,
                $count === 1 ? '' : 's'
            );
        }

        $lines[] = '</ul>';
        $lines[] = '<p class="text-sm text-gray-500 mt-3">Projectitems zonder leverancier worden overgeslagen.</p>';

        return new \Illuminate\Support\HtmlString(implode('', $lines));
    }

    /**
     * Group eligible project items by their product's supplier.
     *
     * @return \Illuminate\Support\Collection<int, \Illuminate\Support\Collection>
     */
    private function groupItemsBySupplier(Project $record): \Illuminate\Support\Collection
    {
        $items = $record->items()
            ->with(['itemable.supplier'])
            ->where('type', QuoteItemType::Product)
            ->whereNotNull('itemable_id')
            ->get()
            ->filter(fn ($item) => $item->itemable instanceof \App\Models\Product
                && $item->itemable->supplier_id !== null
            );

        return $items->groupBy(fn ($item) => $item->itemable->supplier_id);
    }

    /**
     * Create one PurchaseOrder per supplier, return the number of orders created.
     */
    private function generateOrders(Project $record): int
    {
        $grouped = $this->groupItemsBySupplier($record);

        if ($grouped->isEmpty()) {
            return 0;
        }

        $count = 0;

        foreach ($grouped as $supplierId => $items) {
            $supplier = $items->first()->itemable->supplier;

            $order = PurchaseOrder::create([
                'supplier_id' => $supplier->id,
                'project_id'  => $record->id,
                'language'    => $supplier->language ?? 'nl',
                'status'      => 'draft',
                'order_date'  => now(),
            ]);

            foreach ($items->values() as $index => $projectItem) {
                /** @var \App\Models\Product $product */
                $product  = $projectItem->itemable;
                $quantity = (float) $projectItem->quantity;
                $price    = (float) $product->purchase_price;

                $order->items()->create([
                    'product_id'              => $product->id,
                    'description'             => $projectItem->description ?: $product->name,
                    'supplier_article_number' => $product->supplier_article_number,
                    'quantity'                => $quantity,
                    'unit_price'              => $price,
                    'total'                   => round($quantity * $price, 2),
                    'sort_order'              => $index + 1,
                ]);
            }

            $order->recalculateTotals();
            $count++;
        }

        return $count;
    }
}
