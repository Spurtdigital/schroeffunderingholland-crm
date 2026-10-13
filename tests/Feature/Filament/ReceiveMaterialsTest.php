<?php

use App\Enums\PurchaseOrderStatus;
use App\Filament\Resources\PurchaseOrders\Pages\ViewPurchaseOrder;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed();
    $this->actingAs(User::first());
});

test('receive materials action adds stock and creates mutation', function () {
    $product = Product::factory()->create(['stock_quantity' => 0, 'sku' => 'TEST-REV-'.uniqid()]);

    $po = PurchaseOrder::factory()->create(['status' => PurchaseOrderStatus::Draft]);

    $item = PurchaseOrderItem::create([
        'purchase_order_id' => $po->id,
        'product_id' => $product->id,
        'description' => $product->name,
        'quantity' => 5,
        'received_quantity' => 0,
        'unit_price' => 10,
        'total' => 50,
        'sort_order' => 1,
    ]);

    Livewire::actingAs(User::first())
        ->test(ViewPurchaseOrder::class, ['record' => $po->getRouteKey()])
        ->assertActionVisible('receiveMaterials')
        ->callAction('receiveMaterials', [
            'items' => [
                [
                    'id' => $item->id,
                    'received_quantity' => 5,
                ],
            ],
        ])
        ->assertHasNoActionErrors();

    $product->refresh();
    $item->refresh();
    $po->refresh();

    expect((float) $item->received_quantity)->toBe(5.0)
        ->and($product->stock_quantity)->toBe(5)
        ->and($po->status)->toBe(PurchaseOrderStatus::Delivered);

    $mutation = \App\Models\StockMutation::where('product_id', $product->id)
        ->where('type', \App\Enums\StockMutationType::Purchase)
        ->first();

    expect($mutation)->not->toBeNull()
        ->and($mutation->quantity)->toBe(5)
        ->and($mutation->reference_type)->toBe(PurchaseOrder::class)
        ->and($mutation->reference_id)->toBe($po->id);
});
