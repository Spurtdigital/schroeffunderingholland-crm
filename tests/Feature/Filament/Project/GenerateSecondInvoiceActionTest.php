<?php

use App\Enums\InvoicingType;
use App\Enums\InvoiceType;
use App\Enums\QuoteItemType;
use App\Filament\Resources\Projects\Pages\ViewProject;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\ProjectItem;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed();
    $this->actingAs(User::first());
});

test('generate invoice (eindfactuur): includes meerwerk items in the amount', function () {
    $quote = Quote::factory()->create(['subtotal' => 1000.00, 'vat_amount' => 210.00, 'total' => 1210.00]);
    $project = Project::factory()->create([
        'invoicing_type' => InvoicingType::Split5050,
        'quote_id' => $quote->id,
        'company_id' => $quote->company_id,
    ]);

    // Origineel offerte-item
    $quoteItem = QuoteItem::factory()->create([
        'quote_id' => $quote->id,
        'type' => QuoteItemType::FreeLine,
        'quantity' => 1,
        'unit_price' => 1000.00,
        'total' => 1000.00,
    ]);

    ProjectItem::factory()->create([
        'project_id' => $project->id,
        'quote_item_id' => $quoteItem->id,
        'type' => QuoteItemType::FreeLine,
        'quantity' => 1,
        'unit_price' => 1000.00,
        'total' => 1000.00,
    ]);

    // Eerste factuur (50% van offerte)
    Invoice::factory()->deposit()->create([
        'project_id' => $project->id,
        'company_id' => $project->company_id,
        'subtotal' => 500.00,
        'vat_amount' => 105.00,
        'total' => 605.00,
    ]);

    // Meerwerk op het project
    ProjectItem::factory()->create([
        'project_id' => $project->id,
        'type' => QuoteItemType::FreeLine,
        'quantity' => 1,
        'unit_price' => 200.00,
        'total' => 200.00,
    ]);

    Livewire::test(ViewProject::class, ['record' => $project->getRouteKey()])
        ->callAction('generateInvoice', data: [
            'invoice_type_choice' => 'second',
            'custom_percentage' => null,
            'notes' => null,
            'include_terms' => true,
            'send_to_moneybird' => false,
        ])
        ->assertHasNoActionErrors();

    $invoice = Invoice::where('project_id', $project->id)
        ->where('invoice_type', InvoiceType::Final)
        ->firstOrFail();

    // 1000 (origineel) + 200 (meerwerk) - 500 (al gefactureerd) = 700
    expect((float) $invoice->subtotal)->toBe(700.00)
        ->and((float) $invoice->vat_amount)->toBe(147.00)
        ->and((float) $invoice->total)->toBe(847.00);
});

test('generate invoice (eindfactuur): reflects minderwerk (reduced original items)', function () {
    $quote = Quote::factory()->create(['subtotal' => 1000.00, 'vat_amount' => 210.00, 'total' => 1210.00]);
    $project = Project::factory()->create([
        'invoicing_type' => InvoicingType::Split5050,
        'quote_id' => $quote->id,
        'company_id' => $quote->company_id,
    ]);

    // Origineel offerte-item verlaagd op project van €1000 naar €600 (minderwerk)
    $quoteItem = QuoteItem::factory()->create([
        'quote_id' => $quote->id,
        'type' => QuoteItemType::FreeLine,
        'quantity' => 1,
        'unit_price' => 1000.00,
        'total' => 1000.00,
    ]);

    ProjectItem::factory()->create([
        'project_id' => $project->id,
        'quote_item_id' => $quoteItem->id,
        'type' => QuoteItemType::FreeLine,
        'quantity' => 1,
        'unit_price' => 600.00,
        'total' => 600.00,
    ]);

    Invoice::factory()->deposit()->create([
        'project_id' => $project->id,
        'company_id' => $project->company_id,
        'subtotal' => 500.00,
        'vat_amount' => 105.00,
        'total' => 605.00,
    ]);

    Livewire::test(ViewProject::class, ['record' => $project->getRouteKey()])
        ->callAction('generateInvoice', data: [
            'invoice_type_choice' => 'second',
            'custom_percentage' => null,
            'notes' => null,
            'include_terms' => true,
            'send_to_moneybird' => false,
        ])
        ->assertHasNoActionErrors();

    $invoice = Invoice::where('project_id', $project->id)
        ->where('invoice_type', InvoiceType::Final)
        ->firstOrFail();

    // 600 (resterend origineel) - 500 (al gefactureerd) = 100
    expect((float) $invoice->subtotal)->toBe(100.00)
        ->and((float) $invoice->vat_amount)->toBe(21.00)
        ->and((float) $invoice->total)->toBe(121.00);
});
