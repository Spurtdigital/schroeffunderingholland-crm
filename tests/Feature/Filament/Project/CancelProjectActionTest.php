<?php

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\ProjectStatus;
use App\Enums\StockReservationStatus;
use App\Filament\Resources\Projects\Pages\ViewProject;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Project;
use App\Models\Quote;
use App\Models\StockReservation;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed();
    $this->actingAs(User::first());
});

test('cancel project action: is visible for active projects', function () {
    $project = Project::factory()->create(['status' => ProjectStatus::Active]);

    Livewire::test(ViewProject::class, ['record' => $project->getRouteKey()])
        ->assertActionVisible('cancelProject');
});

test('cancel project action: is visible for on-hold projects', function () {
    $project = Project::factory()->create(['status' => ProjectStatus::OnHold]);

    Livewire::test(ViewProject::class, ['record' => $project->getRouteKey()])
        ->assertActionVisible('cancelProject');
});

test('cancel project action: is hidden for completed projects', function () {
    $project = Project::factory()->create(['status' => ProjectStatus::Completed]);

    Livewire::test(ViewProject::class, ['record' => $project->getRouteKey()])
        ->assertActionHidden('cancelProject');
});

test('cancel project action: is hidden for already cancelled projects', function () {
    $project = Project::factory()->create(['status' => ProjectStatus::Cancelled]);

    Livewire::test(ViewProject::class, ['record' => $project->getRouteKey()])
        ->assertActionHidden('cancelProject');
});

test('cancel project action: sets status to cancelled', function () {
    $project = Project::factory()->create(['status' => ProjectStatus::Active]);

    Livewire::test(ViewProject::class, ['record' => $project->getRouteKey()])
        ->callAction('cancelProject', data: [
            'charge_cancellation_fee' => false,
            'cancellation_reason' => 'Klant wil niet doorgaan',
        ])
        ->assertHasNoActionErrors();

    expect($project->fresh()->status)->toBe(ProjectStatus::Cancelled);
});

test('cancel project action: creates cancellation invoice with percentage', function () {
    $quote = Quote::factory()->create(['total' => 1210.00]); // 1000 + 21% BTW
    $project = Project::factory()->create([
        'status' => ProjectStatus::Active,
        'quote_id' => $quote->id,
    ]);

    Livewire::test(ViewProject::class, ['record' => $project->getRouteKey()])
        ->callAction('cancelProject', data: [
            'charge_cancellation_fee' => true,
            'fee_type' => 'percentage',
            'percentage' => 10,
            'cancellation_reason' => 'Klant annuleert',
            'send_to_moneybird' => false,
        ])
        ->assertHasNoActionErrors();

    $invoice = Invoice::where('project_id', $project->id)
        ->where('invoice_type', InvoiceType::Cancellation)
        ->first();

    expect($invoice)->not->toBeNull()
        ->and($invoice->invoice_type)->toBe(InvoiceType::Cancellation)
        ->and((float) $invoice->subtotal)->toBe(100.00) // 10% van 1000
        ->and((float) $invoice->vat_amount)->toBe(21.00)
        ->and((float) $invoice->total)->toBe(121.00)
        ->and($invoice->status)->toBe(InvoiceStatus::Draft);
});

test('cancel project action: creates cancellation invoice with fixed amount', function () {
    $project = Project::factory()->create(['status' => ProjectStatus::Active]);

    Livewire::test(ViewProject::class, ['record' => $project->getRouteKey()])
        ->callAction('cancelProject', data: [
            'charge_cancellation_fee' => true,
            'fee_type' => 'fixed',
            'fixed_amount' => 250.00,
            'cancellation_reason' => 'Klant annuleert',
            'send_to_moneybird' => false,
        ])
        ->assertHasNoActionErrors();

    $invoice = Invoice::where('project_id', $project->id)
        ->where('invoice_type', InvoiceType::Cancellation)
        ->first();

    expect($invoice)->not->toBeNull()
        ->and((float) $invoice->subtotal)->toBe(250.00)
        ->and((float) $invoice->vat_amount)->toBe(52.50) // 21% van 250
        ->and((float) $invoice->total)->toBe(302.50);
});

test('cancel project action: does not create invoice when fee is disabled', function () {
    $project = Project::factory()->create(['status' => ProjectStatus::Active]);

    Livewire::test(ViewProject::class, ['record' => $project->getRouteKey()])
        ->callAction('cancelProject', data: [
            'charge_cancellation_fee' => false,
            'cancellation_reason' => 'Klant annuleert',
        ])
        ->assertHasNoActionErrors();

    expect(Invoice::where('project_id', $project->id)->count())->toBe(0);
});

test('cancel project action: stores cancellation reason in project notes', function () {
    $project = Project::factory()->create([
        'status' => ProjectStatus::Active,
        'notes' => null,
    ]);

    Livewire::test(ViewProject::class, ['record' => $project->getRouteKey()])
        ->callAction('cancelProject', data: [
            'charge_cancellation_fee' => false,
            'cancellation_reason' => 'Klant wil niet meer',
        ])
        ->assertHasNoActionErrors();

    expect($project->fresh()->notes)->toContain('Klant wil niet meer');
});

test('cancel project action: releases stock reservations', function () {
    $product = Product::first();
    $product->update(['stock_quantity' => 20]);
    
    $project = Project::factory()->create(['status' => ProjectStatus::Active]);

    $reservation = StockReservation::factory()->create([
        'product_id' => $product->id,
        'project_id' => $project->id,
        'quantity' => 5,
        'status' => StockReservationStatus::Active,
    ]);

    Livewire::test(ViewProject::class, ['record' => $project->getRouteKey()])
        ->callAction('cancelProject', data: [
            'charge_cancellation_fee' => false,
        ])
        ->assertHasNoActionErrors();

    expect($reservation->fresh()->status)->toBe(StockReservationStatus::Released);
});

test('cancel project action: invoice notes contain percentage info', function () {
    $quote = Quote::factory()->create(['total' => 1210.00]);
    $projectNumber = 'P2026-'.str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
    $project = Project::factory()->create([
        'status' => ProjectStatus::Active,
        'quote_id' => $quote->id,
        'project_number' => $projectNumber,
    ]);

    Livewire::test(ViewProject::class, ['record' => $project->getRouteKey()])
        ->callAction('cancelProject', data: [
            'charge_cancellation_fee' => true,
            'fee_type' => 'percentage',
            'percentage' => 15,
            'cancellation_reason' => 'Test reden',
            'send_to_moneybird' => false,
        ])
        ->assertHasNoActionErrors();

    $invoice = Invoice::where('project_id', $project->id)->first();

    expect($invoice->notes)->toContain($projectNumber)
        ->and($invoice->notes)->toContain('15%')
        ->and($invoice->notes)->toContain('Test reden');
});
