<?php

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Models\Invoice;
use App\Models\Project;

test('invoice can be created', function () {
    $invoice = Invoice::factory()->create();

    expect($invoice)->toBeInstanceOf(Invoice::class)
        ->and($invoice->status)->toBe(InvoiceStatus::Draft)
        ->and($invoice->invoice_number)->not->toBeNull();
});

test('invoice auto-generates invoice number', function () {
    $invoice1 = Invoice::factory()->create();
    $invoice2 = Invoice::factory()->create();

    expect($invoice1->invoice_number)->toContain('INV')
        ->and($invoice1->invoice_number)->not->toBe($invoice2->invoice_number);
});

test('invoice belongs to project', function () {
    $project = Project::factory()->create();
    $invoice = Invoice::factory()->for($project)->create();

    expect($invoice->project)->toBeInstanceOf(Project::class)
        ->and($invoice->project->id)->toBe($project->id);
});

test('invoice can be a deposit', function () {
    $invoice = Invoice::factory()->deposit()->create();

    expect($invoice->isDeposit())->toBeTrue()
        ->and($invoice->invoice_type)->toBe(InvoiceType::Deposit)
        ->and($invoice->deposit_percentage)->not->toBeNull();
});

test('invoice can be a final invoice', function () {
    $invoice = Invoice::factory()->final()->create();

    expect($invoice->isFinal())->toBeTrue()
        ->and($invoice->invoice_type)->toBe(InvoiceType::Final);
});

test('invoice can be sent', function () {
    $invoice = Invoice::factory()->sent()->create();

    expect($invoice->isSent())->toBeTrue()
        ->and($invoice->sent_at)->not->toBeNull();
});

test('invoice can be paid', function () {
    $invoice = Invoice::factory()->paid()->create();

    expect($invoice->isPaid())->toBeTrue()
        ->and($invoice->paid_at)->not->toBeNull();
});

test('invoice can be synced with moneybird', function () {
    $invoice = Invoice::factory()->withMoneybird()->create();

    expect($invoice->moneybird_invoice_id)->not->toBeNull();
});
