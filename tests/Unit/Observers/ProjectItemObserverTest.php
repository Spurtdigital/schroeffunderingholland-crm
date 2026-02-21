<?php

use App\Models\Project;
use App\Models\ProjectItem;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->project = Project::factory()->create();
    $this->actingAs($this->user);
});

test('adding a project item logs an item_added activity on the project', function () {
    ProjectItem::factory()->for($this->project)->create([
        'description' => 'Test product',
        'quantity' => 2,
        'unit_price' => 50.00,
        'total' => 100.00,
    ]);

    $activity = Activity::where('subject_id', $this->project->id)
        ->where('subject_type', Project::class)
        ->where('event', 'item_added')
        ->latest()
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->properties['item_description'])->toBe('Test product')
        ->and((float) $activity->properties['item_quantity'])->toBe(2.0)
        ->and((float) $activity->properties['item_total'])->toBe(100.0);
});

test('updating a project item logs an item_updated activity on the project', function () {
    $item = ProjectItem::factory()->for($this->project)->create([
        'description' => 'Origineel',
        'quantity' => 1,
        'unit_price' => 50.00,
        'total' => 50.00,
    ]);

    $item->update([
        'quantity' => 3,
        'total' => 150.00,
    ]);

    $activity = Activity::where('subject_id', $this->project->id)
        ->where('subject_type', Project::class)
        ->where('event', 'item_updated')
        ->latest()
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->properties['old']['quantity'])->toBe('1.00')
        ->and($activity->properties['attributes']['quantity'])->toBe('3.00');
});

test('deleting a project item logs an item_deleted activity on the project', function () {
    $item = ProjectItem::factory()->for($this->project)->create([
        'description' => 'Te verwijderen item',
        'quantity' => 1,
        'unit_price' => 75.00,
        'total' => 75.00,
    ]);

    $item->delete();

    $activity = Activity::where('subject_id', $this->project->id)
        ->where('subject_type', Project::class)
        ->where('event', 'item_deleted')
        ->latest()
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->properties['item_description'])->toBe('Te verwijderen item');
});

test('updating sort_order only does not log item_updated activity', function () {
    $item = ProjectItem::factory()->for($this->project)->create();

    $countBefore = Activity::where('subject_id', $this->project->id)
        ->where('event', 'item_updated')
        ->count();

    $item->update(['sort_order' => 99]);

    $countAfter = Activity::where('subject_id', $this->project->id)
        ->where('event', 'item_updated')
        ->count();

    expect($countAfter)->toBe($countBefore);
});

test('item_added activity is linked to the authenticated user', function () {
    ProjectItem::factory()->for($this->project)->create();

    $activity = Activity::where('subject_id', $this->project->id)
        ->where('event', 'item_added')
        ->latest()
        ->first();

    expect($activity->causer_id)->toBe($this->user->id)
        ->and($activity->causer_type)->toBe(User::class);
});
