<?php

use App\Enums\QuoteStatus;
use App\Filament\Resources\Quotes\Pages\EditQuote;
use App\Filament\Resources\Quotes\QuoteResource;
use App\Models\Quote;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed();
    $this->actingAs(User::first());
});

test('edit quote page shows the send and duplicate actions for draft quotes', function () {
    $quote = Quote::factory()->create(['status' => QuoteStatus::Draft]);

    Livewire::actingAs(User::first())
        ->test(EditQuote::class, ['record' => $quote->getRouteKey()])
        ->assertActionVisible('sendQuote')
        ->assertActionVisible('duplicateQuote');
});

test('edit quote page redirects to the quote view after saving', function () {
    $quote = Quote::factory()->create(['status' => QuoteStatus::Draft]);

    Livewire::actingAs(User::first())
        ->test(EditQuote::class, ['record' => $quote->getRouteKey()])
        ->call('save')
        ->assertRedirect(QuoteResource::getUrl('view', ['record' => $quote]));
});
