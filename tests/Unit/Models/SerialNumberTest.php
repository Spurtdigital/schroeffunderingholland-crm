<?php

use App\Enums\QuoteItemType;
use App\Models\Company;
use App\Models\Product;
use App\Models\Project;
use App\Models\ProjectItem;
use App\Models\SerialNumber;

beforeEach(function () {
    $this->seed();
});

test('deliveryLocations groups linked projects with quantity and company', function () {
    $product = Product::first();

    $company = Company::factory()->create(['name' => 'Jasper wierper']);

    $project = Project::factory()->create([
        'company_id' => $company->id,
        'project_number' => 'P2026-0056',
    ]);

    $item = ProjectItem::factory()->create([
        'project_id' => $project->id,
        'itemable_type' => Product::class,
        'itemable_id' => $product->id,
        'type' => QuoteItemType::Product,
        'quantity' => 9,
        'unit_price' => 100,
        'total' => 900,
    ]);

    $serial = SerialNumber::create([
        'product_id' => $product->id,
        'serial_number' => 'EA1234',
        'quantity' => 9,
        'quantity_reserved' => 9,
    ]);

    $serial->projectItems()->attach($item->id, ['quantity' => 9]);

    $locations = $serial->deliveryLocations();

    expect($locations)->toHaveCount(1)
        ->and($locations[0]['project_number'])->toBe('P2026-0056')
        ->and($locations[0]['company'])->toBe('Jasper wierper')
        ->and($locations[0]['quantity'])->toBe(9);
});

test('deliveryLocations combines multiple items within the same project', function () {
    $product = Product::first();

    $project = Project::factory()->create(['project_number' => 'P2026-0100']);

    $firstItem = ProjectItem::factory()->create([
        'project_id' => $project->id,
        'itemable_type' => Product::class,
        'itemable_id' => $product->id,
        'type' => QuoteItemType::Product,
        'quantity' => 4,
    ]);

    $secondItem = ProjectItem::factory()->create([
        'project_id' => $project->id,
        'itemable_type' => Product::class,
        'itemable_id' => $product->id,
        'type' => QuoteItemType::Product,
        'quantity' => 5,
    ]);

    $serial = SerialNumber::create([
        'product_id' => $product->id,
        'serial_number' => 'EA5678',
        'quantity' => 9,
        'quantity_reserved' => 9,
    ]);

    $serial->projectItems()->attach($firstItem->id, ['quantity' => 4]);
    $serial->projectItems()->attach($secondItem->id, ['quantity' => 5]);

    $locations = $serial->deliveryLocations();

    expect($locations)->toHaveCount(1)
        ->and($locations[0]['project_number'])->toBe('P2026-0100')
        ->and($locations[0]['quantity'])->toBe(9);
});
