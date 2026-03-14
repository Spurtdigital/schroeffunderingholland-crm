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

test('updating a lead does not create extra activity entries', function () {
    // De LogsActivity trait is verwijderd van Lead; updates worden niet automatisch gelogd.
    // Alleen de LeadObserver::created() logt — bij updates verwachten we géén nieuwe entries.
    $lead = Lead::factory()->create(['status' => LeadStatus::New]);

    $countAfter = Activity::where('subject_id', $lead->id)
        ->where('subject_type', Lead::class)
        ->count();

    $lead->update(['status' => LeadStatus::Contacted]);
    $lead->update(['notes' => 'Aangepaste notitie']);

    $countAfterUpdates = Activity::where('subject_id', $lead->id)
        ->where('subject_type', Lead::class)
        ->count();

    // Geen nieuwe entries na updates — alleen de created entry
    expect($countAfterUpdates)->toBe($countAfter);
});
