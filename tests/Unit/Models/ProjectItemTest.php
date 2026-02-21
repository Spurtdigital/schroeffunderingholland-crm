<?php

use App\Enums\QuoteItemType;
use App\Models\Product;
use App\Models\Project;
use App\Models\ProjectItem;

test('project item can be created', function () {
    $item = ProjectItem::factory()->create();

    expect($item)->toBeInstanceOf(ProjectItem::class)
        ->and($item->id)->not->toBeNull();
});

test('project item belongs to project', function () {
    $project = Project::factory()->create();
    $item = ProjectItem::factory()->for($project)->create();

    expect($item->project)->toBeInstanceOf(Project::class)
        ->and($item->project->id)->toBe($project->id);
});

test('project item can be a product type', function () {
    $product = Product::factory()->create();
    $item = ProjectItem::factory()->create([
        'type' => QuoteItemType::Product,
        'itemable_type' => Product::class,
        'itemable_id' => $product->id,
    ]);

    expect($item->type)->toBe(QuoteItemType::Product)
        ->and($item->itemable)->toBeInstanceOf(Product::class)
        ->and($item->itemable->id)->toBe($product->id);
});

test('project item can be a free line type', function () {
    $item = ProjectItem::factory()->create([
        'type' => QuoteItemType::FreeLine,
        'itemable_type' => null,
        'itemable_id' => null,
    ]);

    expect($item->type)->toBe(QuoteItemType::FreeLine)
        ->and($item->itemable)->toBeNull();
});

test('project item calculateTotal sets correct total', function () {
    $item = ProjectItem::factory()->create([
        'quantity' => 3,
        'unit_price' => 50.00,
        'total' => 0,
    ]);

    $item->calculateTotal();

    expect((float) $item->total)->toBe(150.0);
});

test('project item auto-sets sort_order on creation', function () {
    $project = Project::factory()->create();

    $item1 = ProjectItem::factory()->for($project)->create(['sort_order' => 0]);
    $item2 = ProjectItem::factory()->for($project)->create(['sort_order' => 0]);

    expect($item1->sort_order)->toBe(1)
        ->and($item2->sort_order)->toBe(2);
});

test('project item total is stored with two decimal places', function () {
    $item = ProjectItem::factory()->create([
        'quantity' => 1,
        'unit_price' => 99.999,
        'total' => 99.999,
    ]);

    expect(round((float) $item->fresh()->total, 2))->toBe(100.0);
});
