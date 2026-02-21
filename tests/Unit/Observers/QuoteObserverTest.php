<?php

use App\Enums\QuoteStatus;
use App\Enums\StockReservationStatus;
use App\Models\Product;
use App\Models\Quote;
use App\Models\StockReservation;

test('rejecting a quote releases all active stock reservations', function () {
    $product = Product::factory()->create(['stock_quantity' => 20]);
    $quote = Quote::factory()->create(['status' => QuoteStatus::Sent]);

    $reservation1 = StockReservation::factory()->create([
        'product_id' => $product->id,
        'quote_id' => $quote->id,
        'quantity' => 3,
        'status' => StockReservationStatus::Active,
    ]);
    $reservation2 = StockReservation::factory()->create([
        'product_id' => $product->id,
        'quote_id' => $quote->id,
        'quantity' => 2,
        'status' => StockReservationStatus::Active,
    ]);

    $quote->update(['status' => QuoteStatus::Rejected]);

    expect($reservation1->fresh()->status)->toBe(StockReservationStatus::Released)
        ->and($reservation2->fresh()->status)->toBe(StockReservationStatus::Released);
});

test('rejecting a quote does not release already consumed reservations', function () {
    $product = Product::factory()->create(['stock_quantity' => 10]);
    $quote = Quote::factory()->create(['status' => QuoteStatus::Sent]);

    $consumed = StockReservation::factory()->create([
        'product_id' => $product->id,
        'quote_id' => $quote->id,
        'quantity' => 4,
        'status' => StockReservationStatus::Consumed,
    ]);

    $quote->update(['status' => QuoteStatus::Rejected]);

    expect($consumed->fresh()->status)->toBe(StockReservationStatus::Consumed);
});

test('rejecting a quote does not change raw stock_quantity (release only frees reservation)', function () {
    $product = Product::factory()->create(['stock_quantity' => 10]);
    $quote = Quote::factory()->create(['status' => QuoteStatus::Sent]);

    StockReservation::factory()->create([
        'product_id' => $product->id,
        'quote_id' => $quote->id,
        'quantity' => 5,
        'status' => StockReservationStatus::Active,
    ]);

    $quote->update(['status' => QuoteStatus::Rejected]);

    // stock_quantity does not change on release — only available_stock (computed) increases
    expect($product->fresh()->stock_quantity)->toBe(10);
});

test('changing quote to a non-rejected status does not release reservations', function () {
    $product = Product::factory()->create(['stock_quantity' => 10]);
    $quote = Quote::factory()->create(['status' => QuoteStatus::Draft]);

    $reservation = StockReservation::factory()->create([
        'product_id' => $product->id,
        'quote_id' => $quote->id,
        'quantity' => 3,
        'status' => StockReservationStatus::Active,
    ]);

    $quote->update(['status' => QuoteStatus::Sent]);

    expect($reservation->fresh()->status)->toBe(StockReservationStatus::Active);
});

test('quote with no reservations can be rejected without errors', function () {
    $quote = Quote::factory()->create(['status' => QuoteStatus::Sent]);

    expect(fn () => $quote->update(['status' => QuoteStatus::Rejected]))
        ->not->toThrow(Exception::class);
});
