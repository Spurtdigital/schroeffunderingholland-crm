<?php

use App\Enums\PurchaseOrderStatus;
use App\Filament\Resources\Projects\Pages\ViewProject;
use App\Models\Product;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed();
    $this->actingAs(User::first());
});

/**
 * Helper: bouw een standaard items-array voor callAction.
 * Filament Repeater verwacht items als associatieve array met UUID-sleutels.
 * In tests volstaat een numerieke array — Filament handelt dit correct af.
 */
function defaultItems(array $overrides = []): array
{
    return [
        array_merge([
            'product_id' => null,
            'description' => 'Testmateriaal',
            'supplier_article_number' => null,
            'quantity' => '2',
            'unit_price' => '50.00',
        ], $overrides),
    ];
}

test('create purchase order action: is visible on the project view page', function () {
    $project = Project::factory()->create();

    Livewire::actingAs(User::first())
        ->test(ViewProject::class, ['record' => $project->getRouteKey()])
        ->assertActionExists('createPurchaseOrder');
});

test('create purchase order action: creates a purchase order linked to the project', function () {
    $project = Project::factory()->create();
    $supplier = Supplier::factory()->create(['language' => 'nl']);

    Livewire::actingAs(User::first())
        ->test(ViewProject::class, ['record' => $project->getRouteKey()])
        ->callAction('createPurchaseOrder', data: [
            'supplier_id' => $supplier->id,
            'language' => 'nl',
            'order_date' => now()->format('Y-m-d'),
            'expected_delivery_date' => null,
            'items' => defaultItems(),
        ]);

    expect(PurchaseOrder::where('project_id', $project->id)->exists())->toBeTrue();
});

test('create purchase order action: sets correct fields on the purchase order', function () {
    $project = Project::factory()->create();
    $supplier = Supplier::factory()->create(['language' => 'nl']);

    Livewire::actingAs(User::first())
        ->test(ViewProject::class, ['record' => $project->getRouteKey()])
        ->callAction('createPurchaseOrder', data: [
            'supplier_id' => $supplier->id,
            'language' => 'nl',
            'order_date' => now()->format('Y-m-d'),
            'expected_delivery_date' => null,
            'items' => defaultItems(),
        ]);

    $order = PurchaseOrder::where('project_id', $project->id)->firstOrFail();

    expect($order->project_id)->toBe($project->id)
        ->and($order->supplier_id)->toBe($supplier->id)
        ->and($order->language)->toBe('nl')
        ->and($order->status)->toBe(PurchaseOrderStatus::Draft);
});

test('create purchase order action: creates items with correct totals', function () {
    $project = Project::factory()->create();
    $supplier = Supplier::factory()->create();

    Livewire::actingAs(User::first())
        ->test(ViewProject::class, ['record' => $project->getRouteKey()])
        ->callAction('createPurchaseOrder', data: [
            'supplier_id' => $supplier->id,
            'language' => 'nl',
            'order_date' => now()->format('Y-m-d'),
            'expected_delivery_date' => null,
            'items' => [
                [
                    'product_id' => null,
                    'description' => 'Item A',
                    'supplier_article_number' => 'ART-001',
                    'quantity' => '3',
                    'unit_price' => '20.00',
                ],
                [
                    'product_id' => null,
                    'description' => 'Item B',
                    'supplier_article_number' => null,
                    'quantity' => '1',
                    'unit_price' => '100.00',
                ],
            ],
        ]);

    $order = PurchaseOrder::where('project_id', $project->id)->firstOrFail();

    expect($order->items()->count())->toBe(2);

    $itemA = $order->items()->where('description', 'Item A')->firstOrFail();
    expect((float) $itemA->quantity)->toBe(3.0)
        ->and((float) $itemA->unit_price)->toBe(20.0)
        ->and((float) $itemA->total)->toBe(60.0)
        ->and($itemA->supplier_article_number)->toBe('ART-001');
});

test('create purchase order action: recalculates totals (NL supplier = 21% BTW)', function () {
    $project = Project::factory()->create();
    $supplier = Supplier::factory()->create(['language' => 'nl', 'btw' => true]);

    Livewire::actingAs(User::first())
        ->test(ViewProject::class, ['record' => $project->getRouteKey()])
        ->callAction('createPurchaseOrder', data: [
            'supplier_id' => $supplier->id,
            'language' => 'nl',
            'order_date' => now()->format('Y-m-d'),
            'expected_delivery_date' => null,
            'items' => [
                [
                    'product_id' => null,
                    'description' => 'Item',
                    'supplier_article_number' => null,
                    'quantity' => '4',
                    'unit_price' => '25.00',
                ],
            ],
        ]);

    $order = PurchaseOrder::where('project_id', $project->id)->firstOrFail();

    // Subtotal = 100, BTW 21% = 21, total = 121
    expect((float) $order->subtotal)->toBe(100.0)
        ->and((float) $order->vat_amount)->toBe(21.0)
        ->and((float) $order->total)->toBe(121.0);
});

test('create purchase order action: foreign supplier results in zero vat', function () {
    $project = Project::factory()->create();
    $supplier = Supplier::factory()->create(['language' => 'de']);

    Livewire::actingAs(User::first())
        ->test(ViewProject::class, ['record' => $project->getRouteKey()])
        ->callAction('createPurchaseOrder', data: [
            'supplier_id' => $supplier->id,
            'language' => 'de',
            'order_date' => now()->format('Y-m-d'),
            'expected_delivery_date' => null,
            'items' => [
                [
                    'product_id' => null,
                    'description' => 'Uitlandse levering',
                    'supplier_article_number' => null,
                    'quantity' => '1',
                    'unit_price' => '200.00',
                ],
            ],
        ]);

    $order = PurchaseOrder::where('project_id', $project->id)->firstOrFail();

    expect((float) $order->vat_amount)->toBe(0.0)
        ->and((float) $order->total)->toBe(200.0);
});

test('create purchase order action: links product when product_id is provided', function () {
    $project = Project::factory()->create();
    $supplier = Supplier::factory()->create();
    $product = Product::first();

    Livewire::actingAs(User::first())
        ->test(ViewProject::class, ['record' => $project->getRouteKey()])
        ->callAction('createPurchaseOrder', data: [
            'supplier_id' => $supplier->id,
            'language' => 'nl',
            'order_date' => now()->format('Y-m-d'),
            'expected_delivery_date' => null,
            'items' => [
                [
                    'product_id' => $product->id,
                    'description' => $product->name,
                    'supplier_article_number' => null,
                    'quantity' => '1',
                    'unit_price' => (string) $product->purchase_price,
                ],
            ],
        ]);

    $order = PurchaseOrder::where('project_id', $project->id)->firstOrFail();
    $item = $order->items()->first();

    expect($item->product_id)->toBe($product->id);
});

test('project has many purchase orders relation works', function () {
    $project = Project::factory()->create();
    $supplier = Supplier::factory()->create();

    PurchaseOrder::factory()->count(3)->create([
        'project_id' => $project->id,
        'supplier_id' => $supplier->id,
    ]);

    expect($project->purchaseOrders()->count())->toBe(3);
});
