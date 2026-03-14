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

test('approve quote action: creates stock reservations for product items', function () {
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

    $this->assertDatabaseHas('stock_reservations', [
        'product_id' => $product->id,
        'quote_id' => $quote->id,
        'status' => StockReservationStatus::Active->value,
    ]);
});

test('approve quote action: skips duplicate reservation when active one already exists', function () {
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

    // Pre-create an active reservation for this product
    StockReservation::factory()->create([
        'product_id' => $product->id,
        'quote_id' => $quote->id,
        'quantity' => 4,
        'status' => StockReservationStatus::Active,
    ]);

    Livewire::actingAs(User::first())
        ->test(ViewQuote::class, ['record' => $quote->getRouteKey()])
        ->callAction('approve');

    // Still exactly one reservation — no duplicate
    expect(
        StockReservation::where('quote_id', $quote->id)
            ->where('product_id', $product->id)
            ->where('status', StockReservationStatus::Active->value)
            ->count()
    )->toBe(1);
});

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
