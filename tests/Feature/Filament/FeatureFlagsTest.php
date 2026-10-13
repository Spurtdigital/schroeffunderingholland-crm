<?php

use App\Filament\Pages\ManageSettings;
use App\Filament\Resources\Companies\CompanyResource;
use App\Filament\Resources\Companies\Pages\EditCompany;
use App\Filament\Resources\Companies\Pages\ListCompanies;
use App\Filament\Resources\Quotes\Pages\EditQuote;
use App\Filament\Resources\Quotes\Pages\ViewQuote;
use App\Filament\Resources\SerialNumbers\SerialNumberResource;
use App\Models\Company;
use App\Models\Quote;
use App\Models\User;
use Livewire\Livewire;

/*
 * De test suite draait met alle feature flags AAN (zie phpunit.xml).
 * Deze tests zetten ze expliciet uit en controleren dat de betreffende
 * onderdelen dan niet zichtbaar of bereikbaar zijn voor de klant.
 */

beforeEach(function () {
    $this->seed();
    $this->actingAs(User::first());
});

// ---------------------------------------------------------------- dupliceren

test('dupliceerknop is verborgen als de flag uitstaat', function () {
    config()->set('features.quote_duplicate', false);

    $quote = Quote::factory()->create();

    Livewire::actingAs(User::first())
        ->test(ViewQuote::class, ['record' => $quote->getRouteKey()])
        ->assertActionHidden('duplicateQuote');

    Livewire::actingAs(User::first())
        ->test(EditQuote::class, ['record' => $quote->getRouteKey()])
        ->assertActionHidden('duplicateQuote');
});

test('dupliceerknop is zichtbaar als de flag aanstaat', function () {
    config()->set('features.quote_duplicate', true);

    $quote = Quote::factory()->create();

    Livewire::actingAs(User::first())
        ->test(EditQuote::class, ['record' => $quote->getRouteKey()])
        ->assertActionVisible('duplicateQuote');
});

// -------------------------------------------------------------- serienummers

test('serienummers zijn onbereikbaar als de flag uitstaat', function () {
    config()->set('features.serial_numbers', false);

    expect(SerialNumberResource::canAccess())->toBeFalse()
        ->and(SerialNumberResource::shouldRegisterNavigation())->toBeFalse();

    $this->get('/admin/serial-numbers')->assertForbidden();
});

test('serienummers zijn bereikbaar als de flag aanstaat', function () {
    config()->set('features.serial_numbers', true);

    expect(SerialNumberResource::canAccess())->toBeTrue()
        ->and(SerialNumberResource::shouldRegisterNavigation())->toBeTrue();
});

// ----------------------------------------------------------- bedrijfsoverzicht

test('klantlinks wijzen naar de bewerkpagina als de flag uitstaat', function () {
    config()->set('features.company_overview', false);

    $company = Company::factory()->create();

    expect(CompanyResource::detailUrl($company))
        ->toBe(CompanyResource::getUrl('edit', ['record' => $company]));
});

test('klantlinks wijzen naar het overzicht als de flag aanstaat', function () {
    config()->set('features.company_overview', true);

    $company = Company::factory()->create();

    expect(CompanyResource::detailUrl($company))
        ->toBe(CompanyResource::getUrl('view', ['record' => $company]));
});

test('bedrijfsoverzicht is onbereikbaar als de flag uitstaat', function () {
    config()->set('features.company_overview', false);

    $company = Company::factory()->create();

    $this->get(CompanyResource::getUrl('view', ['record' => $company]))
        ->assertForbidden();
});

test('bekijkknop ontbreekt in de klantentabel als de flag uitstaat', function () {
    config()->set('features.company_overview', false);

    $company = Company::factory()->create();

    Livewire::actingAs(User::first())
        ->test(ListCompanies::class)
        ->assertTableActionHidden('view', $company);
});

test('opslaan van een klant blijft op de bewerkpagina als de flag uitstaat', function () {
    config()->set('features.company_overview', false);

    $company = Company::first();

    Livewire::actingAs(User::first())
        ->test(EditCompany::class, ['record' => $company->getRouteKey()])
        ->call('save')
        ->assertRedirect(CompanyResource::getUrl('edit', ['record' => $company]));
});

// ------------------------------------------------------ volgend offertenummer

test('veld volgend offertenummer is verborgen als de flag uitstaat', function () {
    config()->set('features.quote_number_override', false);

    Livewire::actingAs(User::first())
        ->test(ManageSettings::class)
        ->assertFormFieldHidden('next_quote_number');
});

test('opslaan wist een bestaande override niet als de flag uitstaat', function () {
    $settings = app(\App\Settings\GeneralSettings::class);
    $settings->next_quote_number = '2026-0999';
    $settings->save();

    config()->set('features.quote_number_override', false);

    Livewire::actingAs(User::first())
        ->test(ManageSettings::class)
        ->call('save');

    expect(app(\App\Settings\GeneralSettings::class)->next_quote_number)->toBe('2026-0999');
});
