<?php

use App\Enums\QuoteItemType;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\ProjectItem;
use App\Models\QuoteItem;
use App\Services\MoneybirdService;

beforeEach(function () {
    $this->seed();
});

test('an optional quoted item that is used on the project counts fully as meerwerk with its amount', function () {
    $company = Company::factory()->create();
    $project = Project::factory()->create(['company_id' => $company->id]);
    $invoice = Invoice::factory()->create(['project_id' => $project->id, 'company_id' => $company->id]);

    $quoteItem = QuoteItem::factory()->create(['quantity' => 0, 'unit_price' => 50]);

    ProjectItem::factory()->create([
        'project_id' => $project->id,
        'quote_item_id' => $quoteItem->id,
        'type' => QuoteItemType::Product,
        'quantity' => 7,
        'unit_price' => 50,
        'total' => 350,
    ]);

    $split = app(MoneybirdService::class)->splitInvoiceItems($invoice);

    expect($split['original'])->toBeEmpty()
        ->and($split['meerwerk'])->toHaveCount(1)
        ->and($split['meerwerk'][0]['quantity'])->toBe(7.0)
        ->and($split['meerwerk_total'])->toBe(350.0);
});

test('an item used exactly as quoted is shown as original without meerwerk', function () {
    $company = Company::factory()->create();
    $project = Project::factory()->create(['company_id' => $company->id]);
    $invoice = Invoice::factory()->create(['project_id' => $project->id, 'company_id' => $company->id]);

    $quoteItem = QuoteItem::factory()->create(['quantity' => 5, 'unit_price' => 100]);

    ProjectItem::factory()->create([
        'project_id' => $project->id,
        'quote_item_id' => $quoteItem->id,
        'type' => QuoteItemType::Product,
        'quantity' => 5,
        'unit_price' => 100,
        'total' => 500,
    ]);

    $split = app(MoneybirdService::class)->splitInvoiceItems($invoice);

    expect($split['original'])->toHaveCount(1)
        ->and($split['original'][0]['quantity'])->toBe(5.0)
        ->and($split['meerwerk'])->toBeEmpty()
        ->and($split['meerwerk_total'])->toBe(0.0);
});

test('an item increased above the quoted quantity is split into original and meerwerk', function () {
    $company = Company::factory()->create();
    $project = Project::factory()->create(['company_id' => $company->id]);
    $invoice = Invoice::factory()->create(['project_id' => $project->id, 'company_id' => $company->id]);

    $quoteItem = QuoteItem::factory()->create(['quantity' => 5, 'unit_price' => 100]);

    ProjectItem::factory()->create([
        'project_id' => $project->id,
        'quote_item_id' => $quoteItem->id,
        'type' => QuoteItemType::Product,
        'quantity' => 7,
        'unit_price' => 100,
        'total' => 700,
    ]);

    $split = app(MoneybirdService::class)->splitInvoiceItems($invoice);

    expect($split['original'])->toHaveCount(1)
        ->and($split['original'][0]['quantity'])->toBe(5.0)
        ->and($split['meerwerk'])->toHaveCount(1)
        ->and($split['meerwerk'][0]['quantity'])->toBe(2.0)
        ->and($split['meerwerk_total'])->toBe(200.0);
});

test('an item without a quote link is fully meerwerk', function () {
    $company = Company::factory()->create();
    $project = Project::factory()->create(['company_id' => $company->id]);
    $invoice = Invoice::factory()->create(['project_id' => $project->id, 'company_id' => $company->id]);

    ProjectItem::factory()->create([
        'project_id' => $project->id,
        'quote_item_id' => null,
        'type' => QuoteItemType::Product,
        'quantity' => 3,
        'unit_price' => 25,
        'total' => 75,
    ]);

    $split = app(MoneybirdService::class)->splitInvoiceItems($invoice);

    expect($split['original'])->toBeEmpty()
        ->and($split['meerwerk'])->toHaveCount(1)
        ->and($split['meerwerk'][0]['quantity'])->toBe(3.0)
        ->and($split['meerwerk_total'])->toBe(75.0);
});

test('discount and adjustment items are ignored', function () {
    $company = Company::factory()->create();
    $project = Project::factory()->create(['company_id' => $company->id]);
    $invoice = Invoice::factory()->create(['project_id' => $project->id, 'company_id' => $company->id]);

    ProjectItem::factory()->create([
        'project_id' => $project->id,
        'type' => QuoteItemType::Discount,
        'quantity' => 1,
        'unit_price' => 50,
        'total' => 50,
    ]);

    ProjectItem::factory()->create([
        'project_id' => $project->id,
        'type' => QuoteItemType::Adjustment,
        'quantity' => 1,
        'unit_price' => 18.57,
        'total' => 18.57,
    ]);

    $split = app(MoneybirdService::class)->splitInvoiceItems($invoice);

    expect($split['original'])->toBeEmpty()
        ->and($split['meerwerk'])->toBeEmpty()
        ->and($split['meerwerk_total'])->toBe(0.0);
});
