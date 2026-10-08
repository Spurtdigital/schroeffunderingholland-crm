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
use App\Notifications\CustomerQuoteNotification;
use App\Settings\GeneralSettings;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\LaravelSettings\Models\SettingsProperty;

beforeEach(function () {
    $this->seed();
    $this->actingAs(User::first());
    Notification::fake();
});

test('send quote action: happy path sets status to sent without creating stock reservations', function () {
    $product = Product::first();
    $product->update(['stock_quantity' => 10, 'min_stock_level' => 1]);

    $quote = Quote::factory()->create(['status' => QuoteStatus::Draft]);

    QuoteItem::factory()->create([
        'quote_id' => $quote->id,
        'itemable_type' => Product::class,
        'itemable_id' => $product->id,
        'type' => QuoteItemType::Product,
        'quantity' => 3,
        'unit_price' => $product->sales_price,
        'total' => $product->sales_price * 3,
    ]);

    Livewire::actingAs(User::first())
        ->test(ViewQuote::class, ['record' => $quote->getRouteKey()])
        ->callAction('sendQuote', data: [
            'email' => 'klant@example.com',
            'assumption_ids' => [],
        ])
        ->assertHasNoActionErrors();

    // Quote status must be Sent
    expect($quote->fresh()->status)->toBe(QuoteStatus::Sent)
        ->and($quote->fresh()->sent_at)->not->toBeNull();

    // Geen voorraadreservering bij versturen — pas bij goedkeuring
    expect(StockReservation::where('quote_id', $quote->id)->count())->toBe(0);
});

test('send quote action: does not send internal user emails on success', function () {
    $product = Product::first();
    $product->update(['stock_quantity' => 10]);
    $quote = Quote::factory()->create(['status' => QuoteStatus::Draft]);

    QuoteItem::factory()->create([
        'quote_id' => $quote->id,
        'itemable_type' => Product::class,
        'itemable_id' => $product->id,
        'type' => QuoteItemType::Product,
        'quantity' => 1,
        'unit_price' => $product->sales_price,
        'total' => $product->sales_price,
    ]);

    Livewire::actingAs(User::first())
        ->test(ViewQuote::class, ['record' => $quote->getRouteKey()])
        ->callAction('sendQuote', data: [
            'email' => 'klant@example.com',
            'assumption_ids' => [],
        ]);

    foreach (User::all() as $user) {
        Notification::assertNothingSentTo($user);
    }
});

test('send quote action: sends customer quote email to entered address', function () {
    $product = Product::first();
    $product->update(['stock_quantity' => 10]);
    $quote = Quote::factory()->create(['status' => QuoteStatus::Draft]);

    QuoteItem::factory()->create([
        'quote_id' => $quote->id,
        'itemable_type' => Product::class,
        'itemable_id' => $product->id,
        'type' => QuoteItemType::Product,
        'quantity' => 1,
        'unit_price' => $product->sales_price,
        'total' => $product->sales_price,
    ]);

    Livewire::actingAs(User::first())
        ->test(ViewQuote::class, ['record' => $quote->getRouteKey()])
        ->callAction('sendQuote', data: [
            'contact_person' => 'Jan Jansen',
            'email' => 'jan@example.com',
            'assumption_ids' => [],
        ]);

    Notification::assertSentOnDemand(CustomerQuoteNotification::class, function ($notification, array $channels, object $notifiable) {
        return $channels === ['mail']
            && $notifiable->routes['mail'] === 'jan@example.com'
            && $notification->contactPerson === 'Jan Jansen';
    });
});

test('customer quote email contains signed URL action link for viewing and signing', function () {
    $quote = Quote::factory()->create(['status' => QuoteStatus::Draft]);
    QuoteItem::factory()->create(['quote_id' => $quote->id]);

    $mail = (new CustomerQuoteNotification($quote, 'Jan Jansen'))->toMail(new stdClass);

    expect($mail->subject)->toBe('Offerte '.($quote->displayNumber() ?? 'Concept'))
        ->and($mail->actionText)->toBe('Offerte bekijken en ondertekenen')
        ->and($mail->actionUrl)->toContain('/offerte/'.$quote->id)
        ->and($mail->actionUrl)->toContain('signature=');
});

test('resend quote action sends customer email without changing status or stock reservations', function () {
    $product = Product::first();
    $product->update(['stock_quantity' => 10]);
    $sentAt = now()->subDay()->startOfSecond();

    $quote = Quote::factory()->create([
        'status' => QuoteStatus::Sent,
        'sent_at' => $sentAt,
    ]);

    QuoteItem::factory()->create([
        'quote_id' => $quote->id,
        'itemable_type' => Product::class,
        'itemable_id' => $product->id,
        'type' => QuoteItemType::Product,
        'quantity' => 2,
        'unit_price' => $product->sales_price,
        'total' => $product->sales_price * 2,
    ]);

    StockReservation::create([
        'product_id' => $product->id,
        'quote_id' => $quote->id,
        'quantity' => 2,
        'status' => StockReservationStatus::Active,
    ]);

    Livewire::actingAs(User::first())
        ->test(ViewQuote::class, ['record' => $quote->getRouteKey()])
        ->callAction('resendQuote', data: [
            'contact_person' => 'Piet Pietersen',
            'email' => 'piet@example.com',
        ])
        ->assertHasNoActionErrors();

    Notification::assertSentOnDemand(CustomerQuoteNotification::class, function ($notification, array $channels, object $notifiable) {
        return $channels === ['mail']
            && $notifiable->routes['mail'] === 'piet@example.com'
            && $notification->contactPerson === 'Piet Pietersen';
    });

    expect($quote->fresh()->status)->toBe(QuoteStatus::Sent)
        ->and($quote->fresh()->sent_at->toDateTimeString())->toBe($sentAt->toDateTimeString())
        ->and(StockReservation::where('quote_id', $quote->id)->count())->toBe(1);
});

test('send quote action: guard - no items prevents sending', function () {
    $quote = Quote::factory()->create(['status' => QuoteStatus::Draft]);

    Livewire::actingAs(User::first())
        ->test(ViewQuote::class, ['record' => $quote->getRouteKey()])
        ->callAction('sendQuote', data: [
            'email' => 'klant@example.com',
            'assumption_ids' => [],
        ]);

    // Status must remain Draft
    expect($quote->fresh()->status)->toBe(QuoteStatus::Draft);

    // No reservations must have been created
    expect(StockReservation::where('quote_id', $quote->id)->count())->toBe(0);

    // No notification must have been sent
    Notification::assertNothingSent();
});

test('send quote action: insufficient stock does not block sending', function () {
    // Stock is 0, but a quote can still be sent — no stock check on quotes
    $product = Product::first();
    $product->update(['stock_quantity' => 0, 'min_stock_level' => 0]);

    $quote = Quote::factory()->create(['status' => QuoteStatus::Draft]);

    QuoteItem::factory()->create([
        'quote_id' => $quote->id,
        'itemable_type' => Product::class,
        'itemable_id' => $product->id,
        'type' => QuoteItemType::Product,
        'quantity' => 5,
        'unit_price' => $product->sales_price,
        'total' => $product->sales_price * 5,
    ]);

    Livewire::actingAs(User::first())
        ->test(ViewQuote::class, ['record' => $quote->getRouteKey()])
        ->callAction('sendQuote', data: [
            'email' => 'klant@example.com',
            'assumption_ids' => [],
        ])
        ->assertHasNoActionErrors();

    // Status must be Sent
    expect($quote->fresh()->status)->toBe(QuoteStatus::Sent);

    // Geen reservering bij versturen — pas bij goedkeuring
    expect(StockReservation::where('quote_id', $quote->id)->count())->toBe(0);
});

test('send quote action: syncs selected assumptions to quote', function () {
    $product = Product::first();
    $product->update(['stock_quantity' => 10]);
    $quote = Quote::factory()->create(['status' => QuoteStatus::Draft]);

    QuoteItem::factory()->create([
        'quote_id' => $quote->id,
        'itemable_type' => Product::class,
        'itemable_id' => $product->id,
        'type' => QuoteItemType::Product,
        'quantity' => 1,
        'unit_price' => $product->sales_price,
        'total' => $product->sales_price,
    ]);

    // Create an assumption without variable input so the form works without
    // dynamic TextInput fields
    $assumption = \App\Models\QuoteAssumption::create([
        'title' => 'Test aanname',
        'content' => 'Test aanname inhoud.',
        'active' => true,
        'has_variable_input' => false,
        'sort_order' => 99,
    ]);

    Livewire::actingAs(User::first())
        ->test(ViewQuote::class, ['record' => $quote->getRouteKey()])
        ->callAction('sendQuote', data: [
            'email' => 'klant@example.com',
            'assumption_ids' => [$assumption->id],
        ]);

    // The assumption must be attached to the quote
    expect($quote->fresh()->assumptions()->where('quote_assumptions.id', $assumption->id)->exists())->toBeTrue();
});

test('send quote action: manual next quote number is used once and then auto-increments', function () {
    $settings = app(GeneralSettings::class);
    $settings->moneybird_enabled = false;
    $settings->quote_number_start = 1;
    $settings->next_quote_number = now()->year.'-0700';
    $settings->save();

    $product = Product::first();

    $makeDraftQuote = function () use ($product) {
        $quote = Quote::factory()->create(['status' => QuoteStatus::Draft]);

        QuoteItem::factory()->create([
            'quote_id' => $quote->id,
            'itemable_type' => Product::class,
            'itemable_id' => $product->id,
            'type' => QuoteItemType::Product,
            'quantity' => 1,
            'unit_price' => $product->sales_price,
            'total' => $product->sales_price,
        ]);

        return $quote;
    };

    $first = $makeDraftQuote();

    Livewire::actingAs(User::first())
        ->test(ViewQuote::class, ['record' => $first->getRouteKey()])
        ->callAction('sendQuote', data: [
            'email' => 'klant@example.com',
            'assumption_ids' => [],
        ])
        ->assertHasNoActionErrors();

    expect($first->fresh()->quote_number)->toBe(now()->year.'-0700');

    // De handmatige override is na gebruik gewist
    $payload = SettingsProperty::where('group', 'general')
        ->where('name', 'next_quote_number')
        ->value('payload');
    expect(json_decode($payload))->toBeNull();

    // De volgende offerte loopt automatisch door
    $second = $makeDraftQuote();

    Livewire::actingAs(User::first())
        ->test(ViewQuote::class, ['record' => $second->getRouteKey()])
        ->callAction('sendQuote', data: [
            'email' => 'klant@example.com',
            'assumption_ids' => [],
        ])
        ->assertHasNoActionErrors();

    expect($second->fresh()->quote_number)->toBe(now()->year.'-0701');
});

test('send quote action: is only visible for draft quotes', function () {
    $sentQuote = Quote::factory()->create(['status' => QuoteStatus::Sent]);

    Livewire::actingAs(User::first())
        ->test(ViewQuote::class, ['record' => $sentQuote->getRouteKey()])
        ->assertActionHidden('sendQuote');
});

test('resend quote action is visible for sent quotes and hidden for draft quotes', function () {
    $draftQuote = Quote::factory()->create(['status' => QuoteStatus::Draft]);
    $sentQuote = Quote::factory()->create(['status' => QuoteStatus::Sent]);

    Livewire::actingAs(User::first())
        ->test(ViewQuote::class, ['record' => $draftQuote->getRouteKey()])
        ->assertActionHidden('resendQuote');

    Livewire::actingAs(User::first())
        ->test(ViewQuote::class, ['record' => $sentQuote->getRouteKey()])
        ->assertActionVisible('resendQuote');
});
