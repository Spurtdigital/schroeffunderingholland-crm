<?php

use App\Models\Quote;
use App\Services\QuoteNumberService;
use App\Settings\GeneralSettings;

beforeEach(function () {
    $this->seed();
});

test('peekNextLocalNumber starts at quote_number_start when no quote exists for the year', function () {
    $settings = app(GeneralSettings::class);
    $settings->quote_number_start = 100;
    $settings->next_quote_number = null;
    $settings->save();

    expect(app(QuoteNumberService::class)->peekNextLocalNumber())
        ->toBe(now()->year.'-0100');
});

test('peekNextLocalNumber continues after the highest quote number', function () {
    $settings = app(GeneralSettings::class);
    $settings->quote_number_start = 1;
    $settings->next_quote_number = null;
    $settings->save();

    Quote::factory()->create([
        'quote_number' => now()->year.'-0042',
        'created_at' => now(),
    ]);

    expect(app(QuoteNumberService::class)->peekNextLocalNumber())
        ->toBe(now()->year.'-0043');
});

test('generateLocalNumber uses the manual override once and clears it', function () {
    $settings = app(GeneralSettings::class);
    $settings->quote_number_start = 1;
    $settings->next_quote_number = now()->year.'-0500';
    $settings->save();

    expect(app(QuoteNumberService::class)->generateLocalNumber())
        ->toBe(now()->year.'-0500');

    expect(app(GeneralSettings::class)->next_quote_number)->toBeNull();

    expect(app(QuoteNumberService::class)->generateLocalNumber())
        ->toBe(now()->year.'-0001');
});
