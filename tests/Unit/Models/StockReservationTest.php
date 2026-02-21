<?php

use App\Enums\StockReservationStatus;
use App\Models\Product;
use App\Models\Quote;
use App\Models\StockReservation;

test('stock reservation can be created', function () {
    $reservation = StockReservation::factory()->create();

    expect($reservation)->toBeInstanceOf(StockReservation::class)
        ->and($reservation->status)->toBe(StockReservationStatus::Active)
        ->and($reservation->quantity)->toBeGreaterThan(0);
});

test('stock reservation belongs to product', function () {
    $product = Product::factory()->create();
    $reservation = StockReservation::factory()->for($product)->create();

    expect($reservation->product)->toBeInstanceOf(Product::class)
        ->and($reservation->product->id)->toBe($product->id);
});

test('stock reservation belongs to quote', function () {
    $quote = Quote::factory()->create();
    $reservation = StockReservation::factory()->for($quote)->create();

    expect($reservation->quote)->toBeInstanceOf(Quote::class)
        ->and($reservation->quote->id)->toBe($quote->id);
});

test('stock reservation can be released', function () {
    $reservation = StockReservation::factory()->create();
    
    $reservation->release();

    expect($reservation->fresh()->status)->toBe(StockReservationStatus::Released)
        ->and($reservation->isActive())->toBeFalse();
});

test('stock reservation can be consumed', function () {
    $product = Product::factory()->create(['stock_quantity' => 100]);
    $reservation = StockReservation::factory()->for($product)->create(['quantity' => 10]);

    $reservation->consume();

    expect($reservation->fresh()->status)->toBe(StockReservationStatus::Consumed)
        ->and($product->fresh()->stock_quantity)->toBe(90);
});

test('stock reservation only consumes when active', function () {
    $product = Product::factory()->create(['stock_quantity' => 100]);
    $reservation = StockReservation::factory()->released()->for($product)->create(['quantity' => 10]);

    $reservation->consume();

    // Should not consume because it's already released
    expect($product->fresh()->stock_quantity)->toBe(100);
});
