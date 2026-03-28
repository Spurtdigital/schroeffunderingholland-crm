<?php

use App\Enums\PurchaseOrderStatus;
use App\Enums\QuoteItemType;
use App\Filament\Resources\Projects\Pages\ViewProject;
use App\Models\Product;
use App\Models\Project;
use App\Models\ProjectItem;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed();
    $this->actingAs(User::first());
});

/**
 * Helper: create a product linked to a supplier, with a unique SKU.
 * Creates both the legacy supplier_id field AND a pivot table record.
 */
function makeProductWithSupplier(Supplier $supplier, array $overrides = []): Product
{
    $product = Product::factory()->create(array_merge([
        'sku'            => 'TST-' . uniqid(),
        'supplier_id'    => $supplier->id,
        'purchase_price' => 50.00,
    ], $overrides));

    // Also create pivot record for the multi-supplier system
    $product->suppliers()->attach($supplier->id, [
        'supplier_sku'   => $overrides['supplier_article_number'] ?? null,
        'purchase_price' => $overrides['purchase_price'] ?? 50.00,
        'is_preferred'   => true,
    ]);

    return $product;
}

/**
 * Helper: create a product ProjectItem linked to a product.
 */
function makeProductItem(Project $project, Product $product, array $overrides = []): ProjectItem
{
    return ProjectItem::factory()->create(array_merge([
        'project_id'    => $project->id,
        'itemable_type' => Product::class,
        'itemable_id'   => $product->id,
        'type'          => QuoteItemType::Product,
        'description'   => $product->name,
        'quantity'      => 2,
        'unit_price'    => $product->sales_price,
        'total'         => round(2 * $product->sales_price, 2),
    ], $overrides));
}

// ──────────────────────────────────────────────────────────────────────────────
// Visibility
// ──────────────────────────────────────────────────────────────────────────────

test('generate purchase orders action: is visible when project has eligible product items', function () {
    $project  = Project::factory()->create();
    $supplier = Supplier::factory()->create();
    $product  = makeProductWithSupplier($supplier);

    makeProductItem($project, $product);

    Livewire::actingAs(User::first())
        ->test(ViewProject::class, ['record' => $project->getRouteKey()])
        ->assertActionVisible('generatePurchaseOrders');
});

test('generate purchase orders action: is hidden when project has no eligible items', function () {
    $project = Project::factory()->create();

    // Only a free-line item (no product / supplier)
    ProjectItem::factory()->create([
        'project_id' => $project->id,
        'type'       => QuoteItemType::FreeLine,
    ]);

    Livewire::actingAs(User::first())
        ->test(ViewProject::class, ['record' => $project->getRouteKey()])
        ->assertActionHidden('generatePurchaseOrders');
});

test('generate purchase orders action: is hidden when product items have no supplier', function () {
    $project = Project::factory()->create();
    $product = Product::factory()->create([
        'sku'         => 'TST-' . uniqid(),
        'supplier_id' => null,
    ]);

    makeProductItem($project, $product);

    Livewire::actingAs(User::first())
        ->test(ViewProject::class, ['record' => $project->getRouteKey()])
        ->assertActionHidden('generatePurchaseOrders');
});

// ──────────────────────────────────────────────────────────────────────────────
// Order creation
// ──────────────────────────────────────────────────────────────────────────────

test('generate purchase orders action: creates one order for one supplier', function () {
    $project  = Project::factory()->create();
    $supplier = Supplier::factory()->create(['language' => 'nl']);
    $product  = makeProductWithSupplier($supplier);

    makeProductItem($project, $product);

    Livewire::actingAs(User::first())
        ->test(ViewProject::class, ['record' => $project->getRouteKey()])
        ->callAction('generatePurchaseOrders')
        ->assertHasNoActionErrors();

    expect(PurchaseOrder::where('project_id', $project->id)->count())->toBe(1);
});

test('generate purchase orders action: creates separate orders for two suppliers', function () {
    $project   = Project::factory()->create();
    $supplierA = Supplier::factory()->create(['language' => 'nl']);
    $supplierB = Supplier::factory()->create(['language' => 'de']);
    $productA  = makeProductWithSupplier($supplierA);
    $productB  = makeProductWithSupplier($supplierB);

    makeProductItem($project, $productA);
    makeProductItem($project, $productB);

    Livewire::actingAs(User::first())
        ->test(ViewProject::class, ['record' => $project->getRouteKey()])
        ->callAction('generatePurchaseOrders')
        ->assertHasNoActionErrors();

    expect(PurchaseOrder::where('project_id', $project->id)->count())->toBe(2);

    expect(
        PurchaseOrder::where('project_id', $project->id)
            ->where('supplier_id', $supplierA->id)
            ->exists()
    )->toBeTrue();

    expect(
        PurchaseOrder::where('project_id', $project->id)
            ->where('supplier_id', $supplierB->id)
            ->exists()
    )->toBeTrue();
});

// ──────────────────────────────────────────────────────────────────────────────
// Correct fields on generated orders
// ──────────────────────────────────────────────────────────────────────────────

test('generate purchase orders action: sets correct project_id, supplier_id and language on order', function () {
    $project  = Project::factory()->create();
    $supplier = Supplier::factory()->create(['language' => 'nl']);
    $product  = makeProductWithSupplier($supplier);

    makeProductItem($project, $product);

    Livewire::actingAs(User::first())
        ->test(ViewProject::class, ['record' => $project->getRouteKey()])
        ->callAction('generatePurchaseOrders');

    $order = PurchaseOrder::where('project_id', $project->id)->firstOrFail();

    expect($order->project_id)->toBe($project->id)
        ->and($order->supplier_id)->toBe($supplier->id)
        ->and($order->language)->toBe('nl')
        ->and($order->status)->toBe(PurchaseOrderStatus::Draft);
});

test('generate purchase orders action: copies language from supplier', function () {
    $project  = Project::factory()->create();
    $supplier = Supplier::factory()->create(['language' => 'de']);
    $product  = makeProductWithSupplier($supplier);

    makeProductItem($project, $product);

    Livewire::actingAs(User::first())
        ->test(ViewProject::class, ['record' => $project->getRouteKey()])
        ->callAction('generatePurchaseOrders');

    $order = PurchaseOrder::where('project_id', $project->id)->firstOrFail();

    expect($order->language)->toBe('de');
});

// ──────────────────────────────────────────────────────────────────────────────
// Correct fields on generated order items
// ──────────────────────────────────────────────────────────────────────────────

test('generate purchase orders action: creates order items with correct fields', function () {
    $project  = Project::factory()->create();
    $supplier = Supplier::factory()->create(['language' => 'nl']);
    $product  = makeProductWithSupplier($supplier, [
        'purchase_price'          => 40.00,
        'supplier_article_number' => 'ART-XYZ',
    ]);

    makeProductItem($project, $product, [
        'description' => 'Mijn omschrijving',
        'quantity'    => 3,
    ]);

    Livewire::actingAs(User::first())
        ->test(ViewProject::class, ['record' => $project->getRouteKey()])
        ->callAction('generatePurchaseOrders');

    $order = PurchaseOrder::where('project_id', $project->id)->firstOrFail();

    expect($order->items()->count())->toBe(1);

    $item = $order->items()->first();

    expect($item->product_id)->toBe($product->id)
        ->and($item->description)->toBe('Mijn omschrijving')
        ->and($item->supplier_article_number)->toBe('ART-XYZ')
        ->and((float) $item->quantity)->toBe(3.0)
        ->and((float) $item->unit_price)->toBe(40.0)
        ->and((float) $item->total)->toBe(120.0);
});

test('generate purchase orders action: uses product purchase_price (not sales_price) for unit_price', function () {
    $project  = Project::factory()->create();
    $supplier = Supplier::factory()->create(['language' => 'nl']);
    $product  = makeProductWithSupplier($supplier, [
        'purchase_price' => 30.00,
        'sales_price'    => 99.00,
    ]);

    makeProductItem($project, $product, ['quantity' => 1]);

    Livewire::actingAs(User::first())
        ->test(ViewProject::class, ['record' => $project->getRouteKey()])
        ->callAction('generatePurchaseOrders');

    $item = PurchaseOrder::where('project_id', $project->id)->firstOrFail()->items()->first();

    expect((float) $item->unit_price)->toBe(30.0);
});

// ──────────────────────────────────────────────────────────────────────────────
// Skipping ineligible items
// ──────────────────────────────────────────────────────────────────────────────

test('generate purchase orders action: skips free-line items', function () {
    $project  = Project::factory()->create();
    $supplier = Supplier::factory()->create(['language' => 'nl']);
    $product  = makeProductWithSupplier($supplier, ['purchase_price' => 10.00]);

    makeProductItem($project, $product, ['quantity' => 1]);

    // Also add a free-line item — should be skipped
    ProjectItem::factory()->create([
        'project_id'  => $project->id,
        'type'        => QuoteItemType::FreeLine,
        'description' => 'Vrije regel',
        'quantity'    => 5,
    ]);

    Livewire::actingAs(User::first())
        ->test(ViewProject::class, ['record' => $project->getRouteKey()])
        ->callAction('generatePurchaseOrders');

    $order = PurchaseOrder::where('project_id', $project->id)->firstOrFail();

    // Only one item (the free line is skipped)
    expect($order->items()->count())->toBe(1);
});

test('generate purchase orders action: skips product items without supplier', function () {
    $project        = Project::factory()->create();
    $supplier       = Supplier::factory()->create(['language' => 'nl']);
    $productWith    = makeProductWithSupplier($supplier, ['purchase_price' => 10.00]);
    $productWithout = Product::factory()->create([
        'sku'         => 'TST-' . uniqid(),
        'supplier_id' => null,
    ]);

    makeProductItem($project, $productWith, ['quantity' => 1]);
    makeProductItem($project, $productWithout, ['quantity' => 2]);

    Livewire::actingAs(User::first())
        ->test(ViewProject::class, ['record' => $project->getRouteKey()])
        ->callAction('generatePurchaseOrders');

    // Only one order (for the supplier), with one item
    expect(PurchaseOrder::where('project_id', $project->id)->count())->toBe(1);

    $order = PurchaseOrder::where('project_id', $project->id)->firstOrFail();
    expect($order->items()->count())->toBe(1);
});

// ──────────────────────────────────────────────────────────────────────────────
// Totals recalculation
// ──────────────────────────────────────────────────────────────────────────────

test('generate purchase orders action: recalculates totals with 21% VAT for NL supplier', function () {
    $project  = Project::factory()->create();
    $supplier = Supplier::factory()->create(['language' => 'nl']);
    $product  = makeProductWithSupplier($supplier, ['purchase_price' => 100.00]);

    makeProductItem($project, $product, ['quantity' => 1]);

    Livewire::actingAs(User::first())
        ->test(ViewProject::class, ['record' => $project->getRouteKey()])
        ->callAction('generatePurchaseOrders');

    $order = PurchaseOrder::where('project_id', $project->id)->firstOrFail();

    // subtotal = 100, vat 21% = 21, total = 121
    expect((float) $order->subtotal)->toBe(100.0)
        ->and((float) $order->vat_amount)->toBe(21.0)
        ->and((float) $order->total)->toBe(121.0);
});

test('generate purchase orders action: recalculates totals with 0% VAT for foreign supplier', function () {
    $project  = Project::factory()->create();
    $supplier = Supplier::factory()->create(['language' => 'de']);
    $product  = makeProductWithSupplier($supplier, ['purchase_price' => 200.00]);

    makeProductItem($project, $product, ['quantity' => 1]);

    Livewire::actingAs(User::first())
        ->test(ViewProject::class, ['record' => $project->getRouteKey()])
        ->callAction('generatePurchaseOrders');

    $order = PurchaseOrder::where('project_id', $project->id)->firstOrFail();

    expect((float) $order->vat_amount)->toBe(0.0)
        ->and((float) $order->total)->toBe(200.0);
});
