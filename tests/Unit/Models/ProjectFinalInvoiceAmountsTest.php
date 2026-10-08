<?php

use App\Enums\InvoicingType;
use App\Enums\InvoiceType;
use App\Enums\QuoteItemType;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\ProjectItem;
use App\Models\Quote;

beforeEach(function () {
    $this->seed();
});

test('final invoice amounts equal project items total when nothing invoiced yet', function () {
    $project = Project::factory()->create(['invoicing_type' => InvoicingType::Single]);

    ProjectItem::factory()->create([
        'project_id' => $project->id,
        'quantity' => 2,
        'unit_price' => 100.00,
        'total' => 200.00,
    ]);
    ProjectItem::factory()->create([
        'project_id' => $project->id,
        'quantity' => 1,
        'unit_price' => 50.00,
        'total' => 50.00,
    ]);

    $amounts = $project->getFinalInvoiceAmounts();

    expect($amounts['subtotal'])->toBe(250.00)
        ->and($amounts['vat_amount'])->toBe(52.50)
        ->and($amounts['total'])->toBe(302.50);
});

test('final invoice amounts include meerwerk items', function () {
    $project = Project::factory()->create(['invoicing_type' => InvoicingType::Single]);

    ProjectItem::factory()->create([
        'project_id' => $project->id,
        'quantity' => 1,
        'unit_price' => 100.00,
        'total' => 100.00,
    ]);
    ProjectItem::factory()->create([
        'project_id' => $project->id,
        'quantity' => 1,
        'unit_price' => 75.00,
        'total' => 75.00,
    ]);

    $amounts = $project->getFinalInvoiceAmounts();

    expect($amounts['subtotal'])->toBe(175.00)
        ->and($amounts['total'])->toBe(211.75);
});

test('final invoice amounts subtract already invoiced totals', function () {
    $quote = Quote::factory()->create(['subtotal' => 1000.00, 'vat_amount' => 210.00, 'total' => 1210.00]);
    $project = Project::factory()->create([
        'invoicing_type' => InvoicingType::Split5050,
        'quote_id' => $quote->id,
    ]);

    ProjectItem::factory()->create([
        'project_id' => $project->id,
        'quantity' => 1,
        'unit_price' => 1000.00,
        'total' => 1000.00,
    ]);

    Invoice::factory()->deposit()->create([
        'project_id' => $project->id,
        'company_id' => $project->company_id,
        'subtotal' => 500.00,
        'vat_amount' => 105.00,
        'total' => 605.00,
    ]);

    $amounts = $project->getFinalInvoiceAmounts();

    expect($amounts['subtotal'])->toBe(500.00)
        ->and($amounts['vat_amount'])->toBe(105.00)
        ->and($amounts['total'])->toBe(605.00);
});

test('final invoice amounts reflect minderwerk (reduced original items)', function () {
    $quote = Quote::factory()->create(['subtotal' => 1000.00, 'vat_amount' => 210.00, 'total' => 1210.00]);
    $project = Project::factory()->create([
        'invoicing_type' => InvoicingType::Split5050,
        'quote_id' => $quote->id,
    ]);

    $quoteItem = \App\Models\QuoteItem::factory()->create([
        'quote_id' => $quote->id,
        'type' => QuoteItemType::FreeLine,
        'quantity' => 1,
        'unit_price' => 1000.00,
        'total' => 1000.00,
    ]);

    // Origineel offerte-item is op het project verlaagd van €1000 naar €600 (minderwerk)
    ProjectItem::factory()->create([
        'project_id' => $project->id,
        'quote_item_id' => $quoteItem->id,
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

    $amounts = $project->getFinalInvoiceAmounts();

    // 600 (resterend project) - 500 (al gefactureerd) = 100 resterend
    expect($amounts['subtotal'])->toBe(100.00)
        ->and($amounts['vat_amount'])->toBe(21.00)
        ->and($amounts['total'])->toBe(121.00);
});

test('final invoice amounts never go negative', function () {
    $quote = Quote::factory()->create(['subtotal' => 100.00, 'vat_amount' => 21.00, 'total' => 121.00]);
    $project = Project::factory()->create([
        'invoicing_type' => InvoicingType::Split5050,
        'quote_id' => $quote->id,
    ]);

    ProjectItem::factory()->create([
        'project_id' => $project->id,
        'quantity' => 1,
        'unit_price' => 100.00,
        'total' => 100.00,
    ]);

    Invoice::factory()->deposit()->create([
        'project_id' => $project->id,
        'company_id' => $project->company_id,
        'subtotal' => 200.00,
        'vat_amount' => 42.00,
        'total' => 242.00,
    ]);

    $amounts = $project->getFinalInvoiceAmounts();

    expect($amounts['subtotal'])->toBe(0.00)
        ->and($amounts['vat_amount'])->toBe(0.00)
        ->and($amounts['total'])->toBe(0.00);
});
