<?php

use App\Enums\LeadStatus;
use App\Enums\QuoteItemType;
use App\Enums\QuoteStatus;
use App\Enums\StockReservationStatus;
use App\Models\Lead;
use App\Models\Product;
use App\Models\Quote;
use App\Models\QuoteItem;
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

// ─── Lead-status synchronisatie ──────────────────────────────────────────────

test('quote observer: sending a quote sets linked lead status to quoted', function () {
    $lead = Lead::factory()->create(['status' => LeadStatus::New]);
    $quote = Quote::factory()->create([
        'status' => QuoteStatus::Draft,
        'lead_id' => $lead->id,
    ]);

    $quote->update(['status' => QuoteStatus::Sent]);

    expect($lead->fresh()->status)->toBe(LeadStatus::Quoted);
});

test('quote observer: approving a quote sets linked lead status to quoted', function () {
    $lead = Lead::factory()->create(['status' => LeadStatus::New]);
    $quote = Quote::factory()->create([
        'status' => QuoteStatus::Sent,
        'lead_id' => $lead->id,
    ]);

    $quote->update(['status' => QuoteStatus::Approved]);

    expect($lead->fresh()->status)->toBe(LeadStatus::Quoted);
});

test('quote observer: converting a quote sets linked lead status to converted', function () {
    $lead = Lead::factory()->create(['status' => LeadStatus::Quoted]);
    $quote = Quote::factory()->create([
        'status' => QuoteStatus::Approved,
        'lead_id' => $lead->id,
    ]);

    $quote->update(['status' => QuoteStatus::Converted]);

    expect($lead->fresh()->status)->toBe(LeadStatus::Converted);
});

test('quote observer: rejecting a quote sets linked lead to rejected when no other active quotes', function () {
    $lead = Lead::factory()->create(['status' => LeadStatus::Quoted]);
    $quote = Quote::factory()->create([
        'status' => QuoteStatus::Sent,
        'lead_id' => $lead->id,
    ]);

    $quote->update(['status' => QuoteStatus::Rejected]);

    expect($lead->fresh()->status)->toBe(LeadStatus::Rejected);
});

test('quote observer: rejecting a quote does not change lead status when other active quotes exist', function () {
    $lead = Lead::factory()->create(['status' => LeadStatus::Quoted]);

    $quote1 = Quote::factory()->create([
        'status' => QuoteStatus::Sent,
        'lead_id' => $lead->id,
    ]);
    Quote::factory()->create([
        'status' => QuoteStatus::Sent,
        'lead_id' => $lead->id,
    ]);

    $quote1->update(['status' => QuoteStatus::Rejected]);

    // Lead blijft Quoted omdat er nog een andere actieve offerte is
    expect($lead->fresh()->status)->toBe(LeadStatus::Quoted);
});

test('quote observer: converted lead status is never downgraded', function () {
    $lead = Lead::factory()->create(['status' => LeadStatus::Converted]);
    $quote = Quote::factory()->create([
        'status' => QuoteStatus::Sent,
        'lead_id' => $lead->id,
    ]);

    // Zelfs afwijzen zet een geconverteerde lead niet terug
    $quote->update(['status' => QuoteStatus::Rejected]);

    expect($lead->fresh()->status)->toBe(LeadStatus::Converted);
});

test('approving a quote does NOT create stock reservations (reservations happen at project conversion)', function () {
    $product = Product::factory()->create(['stock_quantity' => 10]);
    $quote = Quote::factory()->create(['status' => QuoteStatus::Sent]);
    QuoteItem::factory()->create([
        'quote_id' => $quote->id,
        'type' => QuoteItemType::Product,
        'itemable_type' => Product::class,
        'itemable_id' => $product->id,
        'quantity' => 3,
    ]);

    $quote->update(['status' => QuoteStatus::Approved]);

    // No reservations should be created - they are made when converting to project
    expect(
        StockReservation::where('quote_id', $quote->id)
            ->where('product_id', $product->id)
            ->exists()
    )->toBeFalse();
});

test('sending a quote does NOT create stock reservations', function () {
    $product = Product::factory()->create(['stock_quantity' => 10]);
    $quote = Quote::factory()->create(['status' => QuoteStatus::Draft]);
    QuoteItem::factory()->create([
        'quote_id' => $quote->id,
        'type' => QuoteItemType::Product,
        'itemable_type' => Product::class,
        'itemable_id' => $product->id,
        'quantity' => 3,
    ]);

    $quote->update(['status' => QuoteStatus::Sent]);

    expect(
        StockReservation::where('quote_id', $quote->id)
            ->where('product_id', $product->id)
            ->exists()
    )->toBeFalse();
});

// Test verwijderd - voorraad wordt niet meer gereserveerd bij quote goedkeuring
// Reserveringen worden nu alleen aangemaakt bij conversie naar project

test('quote observer: quote without lead does not throw on status change', function () {
    $quote = Quote::factory()->create([
        'status' => QuoteStatus::Draft,
        'lead_id' => null,
    ]);

    expect(fn () => $quote->update(['status' => QuoteStatus::Sent]))
        ->not->toThrow(Exception::class);
});

// ─── Delete stock release ────────────────────────────────────────────────────

test('deleting a quote releases all active stock reservations', function () {
    $product = Product::factory()->create(['stock_quantity' => 20]);
    $quote = Quote::factory()->create(['status' => QuoteStatus::Approved]);

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

    // Verify reservations exist and are active before delete
    expect($reservation1->status)->toBe(StockReservationStatus::Active)
        ->and($reservation2->status)->toBe(StockReservationStatus::Active);

    $quote->delete();

    // After delete, reservations are cascade-deleted but the release() was called first
    // We verify by checking that stock mutations were created for the releases
    $releaseMutations = $product->stockMutations()
        ->where('type', \App\Enums\StockMutationType::Released)
        ->count();
    
    expect($releaseMutations)->toBe(2);
});

test('deleting a quote does not affect already consumed reservations', function () {
    $product = Product::factory()->create(['stock_quantity' => 10]);
    $quote = Quote::factory()->create(['status' => QuoteStatus::Approved]);

    StockReservation::factory()->create([
        'product_id' => $product->id,
        'quote_id' => $quote->id,
        'quantity' => 4,
        'status' => StockReservationStatus::Consumed,
    ]);

    $quote->delete();

    // Consumed reservations should not create release mutations
    $releaseMutations = $product->stockMutations()
        ->where('type', \App\Enums\StockMutationType::Released)
        ->count();
    
    expect($releaseMutations)->toBe(0);
});
