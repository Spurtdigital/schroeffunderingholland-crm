<?php

use App\Enums\InvoicingType;
use App\Enums\LeadStatus;
use App\Enums\QuoteItemType;
use App\Enums\QuoteStatus;
use App\Filament\Resources\Quotes\Pages\ViewQuote;
use App\Models\Lead;
use App\Models\Product;
use App\Models\Project;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\User;
use App\Notifications\OrderConfirmationNotification;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed();
    $this->actingAs(User::first());
    Notification::fake();
});

test('convert to project action: creates a project from the quote', function () {
    $quote = Quote::factory()->approved()->create([
        'quote_number' => 'E2026-0042',
    ]);

    Livewire::actingAs(User::first())
        ->test(ViewQuote::class, ['record' => $quote->getRouteKey()])
        ->callAction('convertToProject', data: [
            'invoicing_type' => InvoicingType::Split5050,
            'start_date' => null,
            'expected_completion_date' => now()->addWeeks(2)->format('Y-m-d'),
            'send_order_confirmation' => false,
            'notes' => null,
        ])
        ->assertHasNoActionErrors();

    expect(Project::where('quote_id', $quote->id)->exists())->toBeTrue();
});

test('convert to project action: derives project number from quote number', function () {
    $quote = Quote::factory()->approved()->create([
        'quote_number' => 'E2026-0042',
    ]);

    Livewire::actingAs(User::first())
        ->test(ViewQuote::class, ['record' => $quote->getRouteKey()])
        ->callAction('convertToProject', data: [
            'invoicing_type' => InvoicingType::Split5050,
            'start_date' => null,
            'expected_completion_date' => now()->addWeeks(2)->format('Y-m-d'),
            'send_order_confirmation' => false,
            'notes' => null,
        ]);

    $project = Project::where('quote_id', $quote->id)->firstOrFail();

    expect($project->project_number)->toBe('P2026-0042');
});

test('convert to project action: copies quote items to project items', function () {
    $product = Product::first();
    $quote = Quote::factory()->approved()->create();

    QuoteItem::factory()->create([
        'quote_id' => $quote->id,
        'itemable_type' => Product::class,
        'itemable_id' => $product->id,
        'type' => QuoteItemType::Product,
        'description' => 'Test product',
        'quantity' => 5,
        'unit_price' => 100.00,
        'total' => 500.00,
    ]);

    QuoteItem::factory()->labor()->create(['quote_id' => $quote->id]);

    Livewire::actingAs(User::first())
        ->test(ViewQuote::class, ['record' => $quote->getRouteKey()])
        ->callAction('convertToProject', data: [
            'invoicing_type' => InvoicingType::Split5050,
            'start_date' => null,
            'expected_completion_date' => now()->addWeeks(2)->format('Y-m-d'),
            'send_order_confirmation' => false,
            'notes' => null,
        ]);

    $project = Project::where('quote_id', $quote->id)->firstOrFail();

    expect($project->items()->count())->toBe(2);

    $productItem = $project->items()->where('itemable_type', Product::class)->first();
    expect((int) $productItem->quantity)->toBe(5)
        ->and((float) $productItem->unit_price)->toBe(100.00);
});

test('convert to project action: sets quote status to converted and links project', function () {
    $quote = Quote::factory()->approved()->create();

    Livewire::actingAs(User::first())
        ->test(ViewQuote::class, ['record' => $quote->getRouteKey()])
        ->callAction('convertToProject', data: [
            'invoicing_type' => InvoicingType::Split5050,
            'start_date' => null,
            'expected_completion_date' => now()->addWeeks(2)->format('Y-m-d'),
            'send_order_confirmation' => false,
            'notes' => null,
        ]);

    $fresh = $quote->fresh();

    expect($fresh->status)->toBe(QuoteStatus::Converted)
        ->and($fresh->project_id)->not->toBeNull();
});

test('convert to project action: sets lead status to converted when quote has a lead', function () {
    $lead = Lead::factory()->create(['status' => LeadStatus::Converted]);
    $quote = Quote::factory()->approved()->create(['lead_id' => $lead->id]);

    Livewire::actingAs(User::first())
        ->test(ViewQuote::class, ['record' => $quote->getRouteKey()])
        ->callAction('convertToProject', data: [
            'invoicing_type' => InvoicingType::Split5050,
            'start_date' => null,
            'expected_completion_date' => now()->addWeeks(2)->format('Y-m-d'),
            'send_order_confirmation' => false,
            'notes' => null,
        ]);

    expect($lead->fresh()->status)->toBe(LeadStatus::Converted);
});

test('convert to project action: sends order confirmation when requested', function () {
    $quote = Quote::factory()->approved()->create();

    Livewire::actingAs(User::first())
        ->test(ViewQuote::class, ['record' => $quote->getRouteKey()])
        ->callAction('convertToProject', data: [
            'invoicing_type' => InvoicingType::Split5050,
            'start_date' => null,
            'expected_completion_date' => now()->addWeeks(2)->format('Y-m-d'),
            'send_order_confirmation' => true,
            'notes' => null,
        ]);

    $company = $quote->fresh()->company;

    Notification::assertSentTo($company, OrderConfirmationNotification::class);
});

test('convert to project action: does not send order confirmation when not requested', function () {
    $quote = Quote::factory()->approved()->create();

    Livewire::actingAs(User::first())
        ->test(ViewQuote::class, ['record' => $quote->getRouteKey()])
        ->callAction('convertToProject', data: [
            'invoicing_type' => InvoicingType::Split5050,
            'start_date' => null,
            'expected_completion_date' => now()->addWeeks(2)->format('Y-m-d'),
            'send_order_confirmation' => false,
            'notes' => null,
        ]);

    Notification::assertNothingSent();
});

test('convert to project action: stores invoicing type on project', function () {
    $quote = Quote::factory()->approved()->create();

    Livewire::actingAs(User::first())
        ->test(ViewQuote::class, ['record' => $quote->getRouteKey()])
        ->callAction('convertToProject', data: [
            'invoicing_type' => InvoicingType::Split5050,
            'start_date' => null,
            'expected_completion_date' => now()->addWeeks(2)->format('Y-m-d'),
            'send_order_confirmation' => false,
            'notes' => null,
        ]);

    $project = Project::where('quote_id', $quote->id)->firstOrFail();

    expect($project->invoicing_type)->toBe(InvoicingType::Split5050);
});

test('convert to project action: is only visible for approved quotes', function () {
    $sentQuote = Quote::factory()->sent()->create();

    Livewire::actingAs(User::first())
        ->test(ViewQuote::class, ['record' => $sentQuote->getRouteKey()])
        ->assertActionHidden('convertToProject');
});
