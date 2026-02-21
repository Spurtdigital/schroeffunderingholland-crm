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

test('creating a lead logs the initial status in properties', function () {
    $lead = Lead::factory()->create(['status' => LeadStatus::New]);

    $activity = Activity::where('subject_id', $lead->id)
        ->where('subject_type', Lead::class)
        ->where('event', 'created')
        ->first();

    expect($activity->properties['attributes']['status'])->toBe(LeadStatus::New->value);
});

test('updating a lead status logs an updated activity', function () {
    $lead = Lead::factory()->create(['status' => LeadStatus::New]);

    $lead->update(['status' => LeadStatus::Contacted]);

    $activity = Activity::where('subject_id', $lead->id)
        ->where('subject_type', Lead::class)
        ->where('event', 'updated')
        ->latest()
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->properties['old']['status'])->toBe(LeadStatus::New->value)
        ->and($activity->properties['attributes']['status'])->toBe(LeadStatus::Contacted->value);
});

test('updating a lead field that is not logged does not create extra activity', function () {
    $lead = Lead::factory()->create();

    $countBefore = Activity::where('subject_id', $lead->id)->count();

    // 'converted_to_quote_id' is not in logOnly, so should not log
    $lead->update(['converted_to_quote_id' => null]);

    $countAfter = Activity::where('subject_id', $lead->id)->count();

    expect($countAfter)->toBe($countBefore);
});
