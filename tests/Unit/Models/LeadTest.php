<?php

use App\Enums\LeadStatus;
use App\Models\Company;
use App\Models\Lead;
use App\Models\Quote;

test('lead can be created', function () {
    $lead = Lead::factory()->create();

    expect($lead)->toBeInstanceOf(Lead::class)
        ->and($lead->name)->not->toBeEmpty()
        ->and($lead->email)->not->toBeEmpty()
        ->and($lead->status)->toBe(LeadStatus::New);
});

test('lead belongs to company', function () {
    $company = Company::factory()->create();
    $lead = Lead::factory()->for($company)->create();

    expect($lead->company)->toBeInstanceOf(Company::class)
        ->and($lead->company->id)->toBe($company->id);
});

test('lead has full address attribute', function () {
    $lead = Lead::factory()->create([
        'street' => 'Hoofdstraat',
        'house_number' => '123',
        'postal_code' => '1234 AB',
        'city' => 'Amsterdam',
    ]);

    expect($lead->full_address)->toBe('Hoofdstraat 123, 1234 AB Amsterdam');
});

test('lead can be converted to quote', function () {
    $lead = Lead::factory()->converted()->create();

    expect($lead->isConverted())->toBeTrue()
        ->and($lead->status)->toBe(LeadStatus::Converted);
});

test('lead has quotes relationship', function () {
    $lead = Lead::factory()->create();
    Quote::factory()->count(2)->for($lead)->create();

    expect($lead->quotes)->toHaveCount(2);
});

test('lead can have different statuses', function () {
    $newLead = Lead::factory()->create();
    $contactedLead = Lead::factory()->contacted()->create();
    $rejectedLead = Lead::factory()->rejected()->create();

    expect($newLead->status)->toBe(LeadStatus::New)
        ->and($contactedLead->status)->toBe(LeadStatus::Contacted)
        ->and($rejectedLead->status)->toBe(LeadStatus::Rejected);
});
