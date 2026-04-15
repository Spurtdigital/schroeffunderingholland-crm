<?php

use App\Enums\LeadStatus;
use App\Models\Lead;
use Spatie\Activitylog\Models\Activity;

test('creating a lead via website logs correct description', function () {
    $lead = Lead::factory()->create([
        'request_data' => ['email' => 'test@example.com'],
    ]);

    $activity = Activity::where('subject_id', $lead->id)
        ->where('subject_type', Lead::class)
        ->where('event', 'created')
        ->where('description', 'Lead aangemaakt via website')
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->description)->toBe('Lead aangemaakt via website');
});

test('creating a lead manually logs correct description', function () {
    $lead = Lead::factory()->create([
        'request_data' => null,
    ]);

    $activity = Activity::where('subject_id', $lead->id)
        ->where('subject_type', Lead::class)
        ->where('event', 'created')
        ->where('description', 'Lead handmatig aangemaakt')
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->description)->toBe('Lead handmatig aangemaakt');
});

test('creating a lead logs the full snapshot in properties', function () {
    $lead = Lead::factory()->create([
        'status' => LeadStatus::New,
        'name' => 'Test Persoon',
        'email' => 'test@example.com',
        'postal_code' => '1234AB',
    ]);

    $activity = Activity::where('subject_id', $lead->id)
        ->where('subject_type', Lead::class)
        ->where('event', 'created')
        ->first();

    expect($activity->properties['attributes'])
        ->toHaveKey('status', LeadStatus::New->value)
        ->toHaveKey('name', 'Test Persoon')
        ->toHaveKey('email', 'test@example.com')
        ->toHaveKey('postal_code', '1234AB')
        ->toHaveKey('company_id')
        ->toHaveKey('street')
        ->toHaveKey('house_number');
});

test('updating a lead logs a new activity entry with old and new values', function () {
    $lead = Lead::factory()->create(['status' => LeadStatus::New]);

    $countAfterCreate = Activity::where('subject_id', $lead->id)
        ->where('subject_type', Lead::class)
        ->count();

    $lead->update(['status' => LeadStatus::Contacted]);

    $countAfterUpdate = Activity::where('subject_id', $lead->id)
        ->where('subject_type', Lead::class)
        ->count();

    expect($countAfterUpdate)->toBe($countAfterCreate + 1);

    $updateActivity = Activity::where('subject_id', $lead->id)
        ->where('subject_type', Lead::class)
        ->where('event', 'updated')
        ->latest()
        ->first();

    expect($updateActivity)->not->toBeNull()
        ->and($updateActivity->properties['attributes']['status'])->toBe(LeadStatus::Contacted->value)
        ->and($updateActivity->properties['old']['status'])->toBe(LeadStatus::New->value);
});

test('updating only updated_at does not create an activity entry', function () {
    $lead = Lead::factory()->create(['status' => LeadStatus::New]);

    $countAfterCreate = Activity::where('subject_id', $lead->id)
        ->where('subject_type', Lead::class)
        ->count();

    // Touch alleen updated_at — geen inhoudelijke wijziging
    $lead->touch();

    $countAfterTouch = Activity::where('subject_id', $lead->id)
        ->where('subject_type', Lead::class)
        ->count();

    expect($countAfterTouch)->toBe($countAfterCreate);
});

test('deleting a lead logs a deleted activity with full snapshot', function () {
    $lead = Lead::factory()->create([
        'status' => LeadStatus::New,
        'name' => 'Te Verwijderen',
        'email' => 'delete@example.com',
    ]);

    $leadId = $lead->id;
    $lead->delete();

    $deleteActivity = Activity::where('subject_id', $leadId)
        ->where('subject_type', Lead::class)
        ->where('event', 'deleted')
        ->first();

    expect($deleteActivity)->not->toBeNull()
        ->and($deleteActivity->properties['old'])
        ->toHaveKey('name', 'Te Verwijderen')
        ->toHaveKey('email', 'delete@example.com')
        ->toHaveKey('status', LeadStatus::New->value);
});
