<?php

use App\Enums\QuoteItemType;
use App\Models\Product;
use App\Models\Quote;
use App\Models\QuoteItem;

test('quote item can be created', function () {
    $item = QuoteItem::factory()->create();

    expect($item)->toBeInstanceOf(QuoteItem::class)
        ->and($item->total)->toBeGreaterThan(0);
});

test('quote item auto-calculates total', function () {
    $item = QuoteItem::factory()->create([
        'quantity' => 5,
        'unit_price' => 100,
    ]);

    expect($item->total)->toBe('500.00');
});

test('quote item can be a product', function () {
    $item = QuoteItem::factory()->product()->create();

    expect($item->isProduct())->toBeTrue()
        ->and($item->type)->toBe(QuoteItemType::Product)
        ->and($item->itemable)->toBeInstanceOf(Product::class);
});

test('quote item can be a free line', function () {
    $item = QuoteItem::factory()->create();

    expect($item->isFreeLine())->toBeTrue()
        ->and($item->type)->toBe(QuoteItemType::FreeLine);
});

test('quote item can be labor', function () {
    $item = QuoteItem::factory()->labor()->create();

    expect($item->isLabor())->toBeTrue()
        ->and($item->type)->toBe(QuoteItemType::Labor);
});

test('quote item can be mileage', function () {
    $item = QuoteItem::factory()->mileage()->create();

    expect($item->isMileage())->toBeTrue()
        ->and($item->type)->toBe(QuoteItemType::Mileage);
});

test('quote item belongs to quote', function () {
    $quote = Quote::factory()->create();
    $item = QuoteItem::factory()->for($quote)->create();

    expect($item->quote)->toBeInstanceOf(Quote::class)
        ->and($item->quote->id)->toBe($quote->id);
});
