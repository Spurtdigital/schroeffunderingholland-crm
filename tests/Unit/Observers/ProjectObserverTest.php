<?php

use App\Enums\ProjectStatus;
use App\Enums\StockReservationStatus;
use App\Models\Product;
use App\Models\Project;
use App\Models\Quote;
use App\Models\StockReservation;

test('completing a project consumes all active stock reservations', function () {
    $product = Product::factory()->create(['stock_quantity' => 20]);
    $quote = Quote::factory()->create();
    $project = Project::factory()->for($quote)->create(['status' => ProjectStatus::Active]);

    // Create two active reservations on the quote
    $reservation1 = StockReservation::factory()->create([
        'product_id' => $product->id,
        'quote_id' => $quote->id,
        'quantity' => 3,
        'status' => StockReservationStatus::Active,
    ]);
    $reservation2 = StockReservation::factory()->create([
        'product_id' => $product->id,
        'quote_id' => $quote->id,
        'quantity' => 5,
        'status' => StockReservationStatus::Active,
    ]);

    // Complete the project
    $project->update(['status' => ProjectStatus::Completed]);

    expect($reservation1->fresh()->status)->toBe(StockReservationStatus::Consumed)
        ->and($reservation2->fresh()->status)->toBe(StockReservationStatus::Consumed);
});

test('completing a project decreases product stock quantity', function () {
    $product = Product::factory()->create(['stock_quantity' => 20]);
    $quote = Quote::factory()->create();
    $project = Project::factory()->for($quote)->create(['status' => ProjectStatus::Active]);

    StockReservation::factory()->create([
        'product_id' => $product->id,
        'quote_id' => $quote->id,
        'quantity' => 7,
        'status' => StockReservationStatus::Active,
    ]);

    $project->update(['status' => ProjectStatus::Completed]);

    expect($product->fresh()->stock_quantity)->toBe(13);
});

test('completing a project does not consume already released reservations', function () {
    $product = Product::factory()->create(['stock_quantity' => 20]);
    $quote = Quote::factory()->create();
    $project = Project::factory()->for($quote)->create(['status' => ProjectStatus::Active]);

    $released = StockReservation::factory()->create([
        'product_id' => $product->id,
        'quote_id' => $quote->id,
        'quantity' => 5,
        'status' => StockReservationStatus::Released,
    ]);

    $project->update(['status' => ProjectStatus::Completed]);

    // Released reservation should remain released, not consumed
    expect($released->fresh()->status)->toBe(StockReservationStatus::Released);
    // Stock should not have been decremented
    expect($product->fresh()->stock_quantity)->toBe(20);
});

test('completing a project does not consume already consumed reservations', function () {
    $product = Product::factory()->create(['stock_quantity' => 10]);
    $quote = Quote::factory()->create();
    $project = Project::factory()->for($quote)->create(['status' => ProjectStatus::Active]);

    $consumed = StockReservation::factory()->create([
        'product_id' => $product->id,
        'quote_id' => $quote->id,
        'quantity' => 5,
        'status' => StockReservationStatus::Consumed,
    ]);

    $project->update(['status' => ProjectStatus::Completed]);

    // Status should remain consumed and stock should be unchanged
    expect($consumed->fresh()->status)->toBe(StockReservationStatus::Consumed)
        ->and($product->fresh()->stock_quantity)->toBe(10);
});

test('project without a quote does not throw when completed', function () {
    // Make a quote but unlink it after project creation to simulate orphaned state
    // The schema requires quote_id NOT NULL, so test that the observer handles
    // a quote-less scenario gracefully by using a quote with no reservations
    $quote = Quote::factory()->create();
    $project = Project::factory()->for($quote)->create([
        'status' => ProjectStatus::Active,
    ]);

    // Should not throw an exception when completing
    expect(fn () => $project->update(['status' => ProjectStatus::Completed]))->not->toThrow(\Exception::class);
});

test('completing an already completed project does not double-consume stock', function () {
    $product = Product::factory()->create(['stock_quantity' => 20]);
    $quote = Quote::factory()->create();
    $project = Project::factory()->for($quote)->create(['status' => ProjectStatus::Active]);

    StockReservation::factory()->create([
        'product_id' => $product->id,
        'quote_id' => $quote->id,
        'quantity' => 4,
        'status' => StockReservationStatus::Active,
    ]);

    // Complete once — consumes stock
    $project->update(['status' => ProjectStatus::Completed]);
    $stockAfterFirst = $product->fresh()->stock_quantity;

    // Saving again as Completed should not trigger consumption again
    $project->update(['notes' => 'Updated note']);

    expect($product->fresh()->stock_quantity)->toBe($stockAfterFirst);
});

test('changing project status to Active does not consume stock', function () {
    $product = Product::factory()->create(['stock_quantity' => 20]);
    $quote = Quote::factory()->create();
    $project = Project::factory()->for($quote)->create(['status' => ProjectStatus::Active]);

    StockReservation::factory()->create([
        'product_id' => $product->id,
        'quote_id' => $quote->id,
        'quantity' => 6,
        'status' => StockReservationStatus::Active,
    ]);

    // Status stays Active
    $project->update(['notes' => 'Some note']);

    expect($product->fresh()->stock_quantity)->toBe(20);
});
