<?php

use App\Filament\Resources\Leads\Pages\EditLead;
use App\Models\Lead;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed();
    $this->actingAs(User::first());
});

test('lead edit page loads without errors', function () {
    $lead = Lead::first();
    $response = $this->get("/admin/leads/{$lead->id}/edit");
    $response->assertStatus(200);
    $response->assertDontSee('Exception');
});

test('lead form contains internal_notes field', function () {
    $lead = Lead::factory()->create();

    Livewire::actingAs(User::first())
        ->test(EditLead::class, ['record' => $lead->getRouteKey()])
        ->assertFormFieldExists('internal_notes');
});

test('lead form shows existing internal_notes value', function () {
    $lead = Lead::factory()->create([
        'internal_notes' => 'Klant heeft gebeld over levertijden.',
    ]);

    Livewire::actingAs(User::first())
        ->test(EditLead::class, ['record' => $lead->getRouteKey()])
        ->assertFormSet(['internal_notes' => 'Klant heeft gebeld over levertijden.']);
});

test('lead form shows empty internal_notes when none set', function () {
    $lead = Lead::factory()->create(['internal_notes' => null]);

    Livewire::actingAs(User::first())
        ->test(EditLead::class, ['record' => $lead->getRouteKey()])
        ->assertFormSet(['internal_notes' => null]);
});

test('lead form contains notes field', function () {
    $lead = Lead::factory()->create();

    Livewire::actingAs(User::first())
        ->test(EditLead::class, ['record' => $lead->getRouteKey()])
        ->assertFormFieldExists('notes');
});

test('lead form contains status field', function () {
    $lead = Lead::factory()->create();

    Livewire::actingAs(User::first())
        ->test(EditLead::class, ['record' => $lead->getRouteKey()])
        ->assertFormFieldExists('status');
});

test('lead form contains request_data field', function () {
    $lead = Lead::factory()->create();

    Livewire::actingAs(User::first())
        ->test(EditLead::class, ['record' => $lead->getRouteKey()])
        ->assertFormFieldExists('request_data');
});

test('internal_notes is stored when set via model', function () {
    $lead = Lead::factory()->create(['internal_notes' => null]);

    // Simulate what RequestMoreInfoAction does
    $lead->update(['internal_notes' => 'Meer info gevraagd op: '.now()->format('d-m-Y H:i')]);

    expect($lead->fresh()->internal_notes)->not->toBeNull()
        ->and($lead->fresh()->internal_notes)->toContain('Meer info gevraagd');
});

test('internal_notes field is visible in lead form when set', function () {
    $lead = Lead::factory()->create([
        'internal_notes' => 'Aanvullende info gevraagd.',
    ]);

    $response = $this->get("/admin/leads/{$lead->id}/edit");
    $response->assertStatus(200);
    $response->assertSee('Interne notities');
});
