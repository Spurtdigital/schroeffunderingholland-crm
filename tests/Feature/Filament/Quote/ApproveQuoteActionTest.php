<?php

use App\Enums\QuoteItemType;
use App\Enums\QuoteStatus;
use App\Enums\StockReservationStatus;
use App\Filament\Resources\Quotes\Pages\ViewQuote;
use App\Models\Product;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\StockReservation;
use App\Models\User;
use App\Notifications\QuoteApprovedNotification;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed();
    $this->actingAs(User::first());
    Notification::fake();
});

test('approve quote action: sets status to approved and timestamps', function () {
    $quote = Quote::factory()->sent()->create();

    Livewire::actingAs(User::first())
        ->test(ViewQuote::class, ['record' => $quote->getRouteKey()])
        ->callAction('approve')
        ->assertHasNoActionErrors();

    $fresh = $quote->fresh();

    expect($fresh->status)->toBe(QuoteStatus::Approved)
        ->and($fresh->approved_at)->not->toBeNull();
});

test('approve quote action: does NOT create stock reservations (happens at project conversion)', function () {
    $product = Product::first();
    $product->update(['stock_quantity' => 20]);
    $quote = Quote::factory()->sent()->create();

    QuoteItem::factory()->create([
        'quote_id' => $quote->id,
        'itemable_type' => Product::class,
        'itemable_id' => $product->id,
        'type' => QuoteItemType::Product,
        'quantity' => 4,
        'unit_price' => $product->sales_price,
        'total' => $product->sales_price * 4,
    ]);

    Livewire::actingAs(User::first())
        ->test(ViewQuote::class, ['record' => $quote->getRouteKey()])
        ->callAction('approve');

    // No reservations should be created - they are made when converting to project
    expect(StockReservation::where('quote_id', $quote->id)->count())->toBe(0);
});

// Test verwijderd - voorraad wordt niet meer gereserveerd bij quote goedkeuring
// Reserveringen worden nu alleen aangemaakt bij conversie naar project

test('approve quote action: notifies all users on approval', function () {
    $quote = Quote::factory()->sent()->create();

    Livewire::actingAs(User::first())
        ->test(ViewQuote::class, ['record' => $quote->getRouteKey()])
        ->callAction('approve');

    foreach (User::all() as $user) {
        Notification::assertSentTo($user, QuoteApprovedNotification::class);
    }
});

test('approve quote action: is only visible for sent quotes', function () {
    $draftQuote = Quote::factory()->create(['status' => QuoteStatus::Draft]);

    Livewire::actingAs(User::first())
        ->test(ViewQuote::class, ['record' => $draftQuote->getRouteKey()])
        ->assertActionHidden('approve');
});

test('approve quote action: non-product items do not create stock reservations', function () {
    $quote = Quote::factory()->sent()->create();

    // Add a labor item (no product)
    QuoteItem::factory()->labor()->create(['quote_id' => $quote->id]);

    Livewire::actingAs(User::first())
        ->test(ViewQuote::class, ['record' => $quote->getRouteKey()])
        ->callAction('approve');

    expect(StockReservation::where('quote_id', $quote->id)->count())->toBe(0);
});
