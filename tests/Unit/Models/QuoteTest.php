<?php

use App\Enums\QuoteStatus;
use App\Models\Company;
use App\Models\Lead;
use App\Models\Quote;
use App\Models\QuoteItem;

test('quote can be created', function () {
    $quote = Quote::factory()->create();

    expect($quote)->toBeInstanceOf(Quote::class)
        ->and($quote->status)->toBe(QuoteStatus::Draft)
        ->and($quote->quote_number)->not->toBeNull();
});

test('quote auto-generates a unique concept quote number', function () {
    $quote1 = Quote::factory()->create();
    $quote2 = Quote::factory()->create();

    // Before syncing with Moneybird, the quote number is a temporary concept placeholder.
    expect($quote1->quote_number)->toStartWith('CONCEPT-')
        ->and($quote1->quote_number)->not->toBe($quote2->quote_number);
});

test('quote belongs to company', function () {
    $company = Company::factory()->create();
    $quote = Quote::factory()->for($company)->create();

    expect($quote->company)->toBeInstanceOf(Company::class)
        ->and($quote->company->id)->toBe($company->id);
});

test('quote belongs to lead', function () {
    $lead = Lead::factory()->create();
    $quote = Quote::factory()->for($lead)->create();

    expect($quote->lead)->toBeInstanceOf(Lead::class)
        ->and($quote->lead->id)->toBe($lead->id);
});

test('quote has items relationship', function () {
    $quote = Quote::factory()->create();
    QuoteItem::factory()->count(3)->for($quote)->create();

    expect($quote->items)->toHaveCount(3)
        ->and($quote->items->first())->toBeInstanceOf(QuoteItem::class);
});

test('quote can calculate totals', function () {
    $quote = Quote::factory()->create(['subtotal' => 1000, 'vat_amount' => 0, 'total' => 0]);
    
    QuoteItem::factory()->for($quote)->create(['quantity' => 2, 'unit_price' => 100]);
    QuoteItem::factory()->for($quote)->create(['quantity' => 1, 'unit_price' => 300]);

    $quote->recalculateTotals();

    expect($quote->subtotal)->toBe('500.00')
        ->and($quote->vat_amount)->toBe('105.00')
        ->and($quote->total)->toBe('605.00');
});

test('quote can be sent', function () {
    $quote = Quote::factory()->sent()->create();

    expect($quote->isSent())->toBeTrue()
        ->and($quote->sent_at)->not->toBeNull();
});

test('quote can be approved', function () {
    $quote = Quote::factory()->approved()->create();

    expect($quote->isApproved())->toBeTrue()
        ->and($quote->approved_at)->not->toBeNull();
});

test('quote can check if expired', function () {
    $validQuote = Quote::factory()->create(['valid_until' => now()->addDays(30)]);
    $expiredQuote = Quote::factory()->create(['valid_until' => now()->subDays(1)]);

    expect($validQuote->isExpired())->toBeFalse()
        ->and($expiredQuote->isExpired())->toBeTrue();
});

test('work address lines splits work address into street, postal code and city', function () {
    $quote = Quote::factory()->create([
        'work_address_different' => true,
        'work_street' => "'s-Gravenweg",
        'work_house_number' => '28',
        'work_postal_code' => '2911CG',
        'work_city' => 'Nieuwerkerk aan den IJssel',
    ]);

    expect($quote->workAddressLines())->toBe([
        "'s-Gravenweg 28",
        '2911CG',
        'Nieuwerkerk aan den IJssel',
    ]);
});

test('work address lines falls back to company address when no work address is set', function () {
    $company = Company::factory()->create([
        'street' => 'Voorstraat',
        'house_number' => '10',
        'postal_code' => '1234AB',
        'city' => 'Amsterdam',
    ]);
    $quote = Quote::factory()->for($company)->create(['work_address_different' => false]);

    expect($quote->workAddressLines())->toBe([
        'Voorstraat 10',
        '1234AB',
        'Amsterdam',
    ]);
});

test('work address lines keeps combined postal and city when postal code is not Dutch', function () {
    $quote = Quote::factory()->create([
        'work_address_different' => true,
        'work_street' => 'Hauptstraße',
        'work_house_number' => '5',
        'work_postal_code' => '10115',
        'work_city' => 'Berlin',
    ]);

    expect($quote->workAddressLines())->toBe([
        'Hauptstraße 5',
        '10115 Berlin',
    ]);
});
