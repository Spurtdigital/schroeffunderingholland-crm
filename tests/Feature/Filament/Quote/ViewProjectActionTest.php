<?php

use App\Enums\QuoteStatus;
use App\Filament\Resources\Quotes\Pages\ViewQuote;
use App\Models\Project;
use App\Models\Quote;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed();
    $this->actingAs(User::first());
});

test('view project action is visible when quote has a project', function () {
    $quote = Quote::factory()->create(['status' => QuoteStatus::Converted]);
    $project = Project::factory()->create(['quote_id' => $quote->id]);
    $quote->update(['project_id' => $project->id]);

    Livewire::actingAs(User::first())
        ->test(ViewQuote::class, ['record' => $quote->getRouteKey()])
        ->assertActionVisible('viewProject');
});

test('view project action is hidden when quote has no project', function () {
    $quote = Quote::factory()->create(['status' => QuoteStatus::Sent]);

    Livewire::actingAs(User::first())
        ->test(ViewQuote::class, ['record' => $quote->getRouteKey()])
        ->assertActionHidden('viewProject');
});
