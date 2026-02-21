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

test('quote auto-generates quote number', function () {
    $quote1 = Quote::factory()->create();
    $quote2 = Quote::factory()->create();

    expect($quote1->quote_number)->toContain('Q')
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
