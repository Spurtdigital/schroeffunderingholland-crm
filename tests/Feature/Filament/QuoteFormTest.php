<?php

use App\Enums\QuoteProcessingMethod;
use App\Filament\Resources\Quotes\Pages\EditQuote;
use App\Models\Quote;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed();
    $this->actingAs(User::first());
});

test('quote edit page loads without errors', function () {
    $quote = Quote::first();
    $response = $this->get("/admin/quotes/{$quote->id}/edit");
    $response->assertStatus(200);
    $response->assertDontSee('Exception');
});

test('quote form contains processing_method field', function () {
    $quote = Quote::factory()->create();

    Livewire::actingAs(User::first())
        ->test(EditQuote::class, ['record' => $quote->getRouteKey()])
        ->assertFormFieldExists('processing_method');
});

test('quote processing_method defaults to System', function () {
    $quote = Quote::factory()->create([
        'processing_method' => QuoteProcessingMethod::System,
    ]);

    Livewire::actingAs(User::first())
        ->test(EditQuote::class, ['record' => $quote->getRouteKey()])
        ->assertFormSet(['processing_method' => QuoteProcessingMethod::System]);
});

test('quote processing_method can be set to MoneybirdDraft', function () {
    $quote = Quote::factory()->create([
        'processing_method' => QuoteProcessingMethod::MoneybirdDraft,
    ]);

    Livewire::actingAs(User::first())
        ->test(EditQuote::class, ['record' => $quote->getRouteKey()])
        ->assertFormSet(['processing_method' => QuoteProcessingMethod::MoneybirdDraft]);
});

test('quote form contains travel_distance field', function () {
    $quote = Quote::factory()->create();

    Livewire::actingAs(User::first())
        ->test(EditQuote::class, ['record' => $quote->getRouteKey()])
        ->assertFormFieldExists('travel_distance');
});

test('quote form contains travel_cost_per_km field', function () {
    $quote = Quote::factory()->create();

    Livewire::actingAs(User::first())
        ->test(EditQuote::class, ['record' => $quote->getRouteKey()])
        ->assertFormFieldExists('travel_cost_per_km');
});

test('quote form contains travel_total field', function () {
    $quote = Quote::factory()->create();

    Livewire::actingAs(User::first())
        ->test(EditQuote::class, ['record' => $quote->getRouteKey()])
        ->assertFormFieldExists('travel_total');
});

test('quote form shows existing travel data', function () {
    $quote = Quote::factory()->create([
        'travel_distance' => 50,
        'travel_cost_per_km' => 0.23,
        'travel_total' => 11.50,
    ]);

    Livewire::actingAs(User::first())
        ->test(EditQuote::class, ['record' => $quote->getRouteKey()])
        ->assertFormSet([
            'travel_distance' => 50,
            'travel_cost_per_km' => '0.23',
            'travel_total' => '11.50',
        ]);
});

test('quote form saves processing_method', function () {
    $quote = Quote::factory()->create([
        'processing_method' => QuoteProcessingMethod::System,
    ]);

    Livewire::actingAs(User::first())
        ->test(EditQuote::class, ['record' => $quote->getRouteKey()])
        ->fillForm(['processing_method' => QuoteProcessingMethod::MoneybirdDraft->value])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($quote->fresh()->processing_method)->toBe(QuoteProcessingMethod::MoneybirdDraft);
});

test('quote form saves travel distance and cost per km', function () {
    $quote = Quote::factory()->create([
        'travel_distance' => null,
        'travel_cost_per_km' => null,
    ]);

    Livewire::actingAs(User::first())
        ->test(EditQuote::class, ['record' => $quote->getRouteKey()])
        ->fillForm([
            'travel_distance' => 100,
            'travel_cost_per_km' => 0.30,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($quote->fresh()->travel_distance)->toBe(100)
        ->and((float) $quote->fresh()->travel_cost_per_km)->toBe(0.30);
});
