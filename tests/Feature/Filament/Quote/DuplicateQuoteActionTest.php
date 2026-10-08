<?php

use App\Enums\QuoteItemType;
use App\Enums\QuoteStatus;
use App\Filament\Resources\Quotes\Pages\ViewQuote;
use App\Models\Product;
use App\Models\Quote;
use App\Models\QuoteAssumption;
use App\Models\QuoteItem;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed();
    $this->actingAs(User::first());
});

test('duplicate quote action: creates a draft copy with copied items', function () {
    $product = Product::first();

    $quote = Quote::factory()->approved()->create([
        'quote_number' => '2026-0042',
    ]);

    QuoteItem::factory()->create([
        'quote_id' => $quote->id,
        'itemable_type' => Product::class,
        'itemable_id' => $product->id,
        'type' => QuoteItemType::Product,
        'quantity' => 3,
        'unit_price' => 100.00,
        'total' => 300.00,
    ]);
    QuoteItem::factory()->labor()->create(['quote_id' => $quote->id]);

    Livewire::actingAs(User::first())
        ->test(ViewQuote::class, ['record' => $quote->getRouteKey()])
        ->callAction('duplicateQuote')
        ->assertHasNoActionErrors();

    $copy = Quote::where('id', '!=', $quote->id)
        ->where('company_id', $quote->company_id)
        ->latest('id')
        ->first();

    expect($copy)->not->toBeNull()
        ->and($copy->id)->not->toBe($quote->id)
        ->and($copy->status)->toBe(QuoteStatus::Draft)
        ->and($copy->quote_number)->toStartWith('CONCEPT-')
        ->and($copy->moneybird_quote_id)->toBeNull()
        ->and($copy->project_id)->toBeNull()
        ->and($copy->sent_at)->toBeNull()
        ->and($copy->approved_at)->toBeNull()
        ->and($copy->items()->count())->toBe(2);
});

test('duplicate quote action: copies the selected assumptions', function () {
    $quote = Quote::factory()->create();

    $assumption = QuoteAssumption::create([
        'title' => 'Test aanname',
        'content' => 'Inhoud',
        'active' => true,
        'has_variable_input' => false,
        'sort_order' => 1,
    ]);

    $quote->assumptions()->attach($assumption->id, ['pivot_value' => 'X']);

    Livewire::actingAs(User::first())
        ->test(ViewQuote::class, ['record' => $quote->getRouteKey()])
        ->callAction('duplicateQuote')
        ->assertHasNoActionErrors();

    $copy = Quote::where('id', '!=', $quote->id)
        ->where('company_id', $quote->company_id)
        ->latest('id')
        ->first();

    expect($copy->assumptions()->where('quote_assumptions.id', $assumption->id)->exists())->toBeTrue();
});
