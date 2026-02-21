<?php

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Filament\Resources\Invoices\Pages\ListInvoices;
use App\Models\Invoice;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed();
    $this->actingAs(User::first());
});

test('invoice list page loads', function () {
    $response = $this->get('/admin/invoices');
    $response->assertStatus(200);
});

test('invoice table renders total column with money format', function () {
    $invoice = Invoice::factory()->create(['total' => 1234.50]);

    Livewire::actingAs(User::first())
        ->test(ListInvoices::class)
        ->assertCanSeeTableRecords([$invoice])
        ->assertTableColumnExists('total');
});

test('invoice table renders moneybird_invoice_id column', function () {
    $invoice = Invoice::factory()->withMoneybird()->create();

    Livewire::actingAs(User::first())
        ->test(ListInvoices::class)
        ->assertCanSeeTableRecords([$invoice])
        ->assertTableColumnExists('moneybird_invoice_id');
});

test('invoice table renders invoice_number column', function () {
    $invoice = Invoice::factory()->create();

    Livewire::actingAs(User::first())
        ->test(ListInvoices::class)
        ->assertCanSeeTableRecords([$invoice])
        ->assertTableColumnExists('invoice_number');
});

test('invoice table renders status column', function () {
    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Draft]);

    Livewire::actingAs(User::first())
        ->test(ListInvoices::class)
        ->assertCanSeeTableRecords([$invoice])
        ->assertTableColumnExists('status');
});

test('invoice table renders invoice_type column', function () {
    $invoice = Invoice::factory()->create(['invoice_type' => InvoiceType::Single]);

    Livewire::actingAs(User::first())
        ->test(ListInvoices::class)
        ->assertCanSeeTableRecords([$invoice])
        ->assertTableColumnExists('invoice_type');
});

test('invoice table renders project number through relationship', function () {
    $invoice = Invoice::factory()->create();

    Livewire::actingAs(User::first())
        ->test(ListInvoices::class)
        ->assertCanSeeTableRecords([$invoice])
        ->assertTableColumnExists('project.project_number');
});

test('invoice table renders company name through relationship', function () {
    $invoice = Invoice::factory()->create();

    Livewire::actingAs(User::first())
        ->test(ListInvoices::class)
        ->assertCanSeeTableRecords([$invoice])
        ->assertTableColumnExists('project.company.name');
});

test('invoices can be sorted by total', function () {
    Invoice::factory()->create(['total' => 100.00]);
    Invoice::factory()->create(['total' => 500.00]);
    Invoice::factory()->create(['total' => 250.00]);

    Livewire::actingAs(User::first())
        ->test(ListInvoices::class)
        ->sortTable('total')
        ->assertCanSeeTableRecords(Invoice::all(), inOrder: false);
});

test('invoices can be searched by invoice number', function () {
    $invoice = Invoice::factory()->create();

    Livewire::actingAs(User::first())
        ->test(ListInvoices::class)
        ->searchTable($invoice->invoice_number)
        ->assertCanSeeTableRecords([$invoice]);
});
