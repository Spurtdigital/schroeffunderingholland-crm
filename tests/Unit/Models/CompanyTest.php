<?php

use App\Models\Company;
use App\Models\Lead;
use App\Models\Project;
use App\Models\Quote;

test('company can be created', function () {
    $company = Company::factory()->create();

    expect($company)->toBeInstanceOf(Company::class)
        ->and($company->name)->not->toBeEmpty()
        ->and($company->email)->not->toBeEmpty();
});

test('company has leads relationship', function () {
    $company = Company::factory()->create();
    Lead::factory()->count(3)->for($company)->create();

    expect($company->leads)->toHaveCount(3)
        ->and($company->leads->first())->toBeInstanceOf(Lead::class);
});

test('company has quotes relationship', function () {
    $company = Company::factory()->create();
    Quote::factory()->count(2)->for($company)->create();

    expect($company->quotes)->toHaveCount(2)
        ->and($company->quotes->first())->toBeInstanceOf(Quote::class);
});

test('company has projects relationship', function () {
    $company = Company::factory()->create();
    Project::factory()->count(2)->for($company)->create();

    expect($company->projects)->toHaveCount(2)
        ->and($company->projects->first())->toBeInstanceOf(Project::class);
});

test('company can be synced with moneybird', function () {
    $company = Company::factory()->withMoneybird()->create();

    expect($company->moneybird_contact_id)->not->toBeNull();
});
