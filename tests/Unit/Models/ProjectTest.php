<?php

use App\Enums\ProjectStatus;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\Quote;

test('project can be created', function () {
    $project = Project::factory()->create();

    expect($project)->toBeInstanceOf(Project::class)
        ->and($project->status)->toBe(ProjectStatus::Active)
        ->and($project->project_number)->not->toBeNull();
});

test('project auto-generates project number', function () {
    $project1 = Project::factory()->create();
    $project2 = Project::factory()->create();

    expect($project1->project_number)->toContain('P')
        ->and($project1->project_number)->not->toBe($project2->project_number);
});

test('project belongs to company', function () {
    $company = Company::factory()->create();
    $project = Project::factory()->for($company)->create();

    expect($project->company)->toBeInstanceOf(Company::class)
        ->and($project->company->id)->toBe($company->id);
});

test('project belongs to quote', function () {
    $quote = Quote::factory()->create();
    $project = Project::factory()->for($quote)->create();

    expect($project->quote)->toBeInstanceOf(Quote::class)
        ->and($project->quote->id)->toBe($quote->id);
});

test('project has invoices relationship', function () {
    $project = Project::factory()->create();
    Invoice::factory()->count(2)->for($project)->create();

    expect($project->invoices)->toHaveCount(2)
        ->and($project->invoices->first())->toBeInstanceOf(Invoice::class);
});

test('project can be completed', function () {
    $project = Project::factory()->completed()->create();

    expect($project->isCompleted())->toBeTrue()
        ->and($project->completion_date)->not->toBeNull();
});

test('project can check if active', function () {
    $activeProject = Project::factory()->create();
    $completedProject = Project::factory()->completed()->create();

    expect($activeProject->isActive())->toBeTrue()
        ->and($completedProject->isActive())->toBeFalse();
});

test('project can use split invoicing', function () {
    $project = Project::factory()->splitInvoicing()->create();

    expect($project->usesSplitInvoicing())->toBeTrue();
});
