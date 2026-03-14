<?php

use App\Models\Product;
use App\Models\Quote;
use App\Models\StockReservation;

test('product can be created', function () {
    $product = Product::factory()->create();

    expect($product)->toBeInstanceOf(Product::class)
        ->and($product->name)->not->toBeEmpty()
        ->and($product->sku)->not->toBeEmpty()
        ->and($product->is_active)->toBeTrue();
});

test('product has sales price higher than purchase price', function () {
    $product = Product::factory()->create();

    expect($product->sales_price)->toBeGreaterThan($product->purchase_price);
});

test('product can track stock quantity', function () {
    $product = Product::factory()->create(['stock_quantity' => 50]);

    expect($product->stock_quantity)->toBe(50);
});

test('product can calculate reserved quantity', function () {
    $product = Product::factory()->create();
    $quote = Quote::factory()->create();

    StockReservation::factory()->for($product)->for($quote)->create(['quantity' => 5]);
    StockReservation::factory()->for($product)->for($quote)->create(['quantity' => 3]);

    expect($product->reserved_quantity)->toBe(8);
});

test('product can calculate available stock', function () {
    $product = Product::factory()->create(['stock_quantity' => 50]);
    $quote = Quote::factory()->create();

    StockReservation::factory()->for($product)->for($quote)->create(['quantity' => 10]);

    expect($product->available_stock)->toBe(40);
});

test('product can check if low stock', function () {
    // Explicit values with unique SKUs — independent of catalog/seeder state.
    $lowStockProduct = Product::factory()->create([
        'sku'             => 'TEST-LOW-STOCK-' . uniqid(),
        'stock_quantity'  => 3,
        'min_stock_level' => 10,
    ]);
    $normalProduct = Product::factory()->create([
        'sku'             => 'TEST-NORMAL-STOCK-' . uniqid(),
        'stock_quantity'  => 100,
        'min_stock_level' => 10,
    ]);

    expect($lowStockProduct->isLowStock())->toBeTrue()
        ->and($normalProduct->isLowStock())->toBeFalse();
});

test('product can check if out of stock', function () {
    $outOfStockProduct = Product::factory()->outOfStock()->create();
    $inStockProduct = Product::factory()->create(['stock_quantity' => 10]);

    expect($outOfStockProduct->isOutOfStock())->toBeTrue()
        ->and($inStockProduct->isOutOfStock())->toBeFalse();
});

test('product can reserve stock', function () {
    $product = Product::factory()->create(['stock_quantity' => 50]);
    $quote = Quote::factory()->create();

    $reservation = $product->reserveStock($quote, 10);

    expect($reservation)->toBeInstanceOf(StockReservation::class)
        ->and($reservation->quantity)->toBe(10)
        ->and($product->reserved_quantity)->toBe(10);
});

test('product can add stock', function () {
    $product = Product::factory()->create(['stock_quantity' => 10]);

    $product->addStock(5);

    expect($product->fresh()->stock_quantity)->toBe(15);
});

test('product can consume stock', function () {
    $product = Product::factory()->create(['stock_quantity' => 10]);

    $product->consumeStock(3);

    expect($product->fresh()->stock_quantity)->toBe(7);
});

test('product can be inactive', function () {
    $product = Product::factory()->inactive()->create();

    expect($product->is_active)->toBeFalse();
});
