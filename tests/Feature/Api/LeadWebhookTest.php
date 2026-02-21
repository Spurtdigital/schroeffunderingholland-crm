<?php

use App\Enums\LeadStatus;
use App\Models\Company;
use App\Models\Lead;
use App\Services\PostcodeService;

test('webhook rejects request without email', function () {
    $response = $this->postJson('/api/webhooks/cf7', [
        'naam' => 'Jan de Vries',
        'telefoon' => '0612345678',
    ]);

    $response->assertStatus(422)
        ->assertJson(['success' => false]);
});

test('webhook creates lead with email only', function () {
    $response = $this->postJson('/api/webhooks/cf7', [
        'email' => 'test@example.nl',
    ]);

    $response->assertStatus(201)
        ->assertJson(['success' => true]);

    $this->assertDatabaseHas('leads', [
        'email' => 'test@example.nl',
        'status' => LeadStatus::New->value,
    ]);
});

test('webhook creates company when email not known', function () {
    $this->postJson('/api/webhooks/cf7', [
        'email' => 'nieuw@bedrijf.nl',
        'bedrijfsnaam' => 'Test BV',
        'naam' => 'Klaas Klaassen',
    ]);

    $this->assertDatabaseHas('companies', [
        'email' => 'nieuw@bedrijf.nl',
    ]);
});

test('webhook reuses existing company matched by email', function () {
    $existing = Company::factory()->create(['email' => 'bestaand@bedrijf.nl']);

    $this->postJson('/api/webhooks/cf7', [
        'email' => 'bestaand@bedrijf.nl',
        'naam' => 'Pietje Puk',
    ]);

    // No new company should be created
    $this->assertDatabaseCount('companies', 1);
    $this->assertDatabaseHas('leads', [
        'company_id' => $existing->id,
        'email' => 'bestaand@bedrijf.nl',
    ]);
});

test('webhook maps dutch field names correctly', function () {
    $this->postJson('/api/webhooks/cf7', [
        'email' => 'test@example.nl',
        'naam' => 'Henk Janssen',
        'telefoon' => '0612345678',
        'postcode' => '1234AB',
        'huisnummer' => '10',
        'straat' => 'Teststraat',
        'plaats' => 'Amsterdam',
        'bedrijfsnaam' => 'Janssen Bouw',
    ]);

    $this->assertDatabaseHas('leads', [
        'email' => 'test@example.nl',
        'name' => 'Henk Janssen',
        'phone' => '0612345678',
        'postal_code' => '1234AB',
        'house_number' => '10',
        'street' => 'Teststraat',
        'city' => 'Amsterdam',
    ]);

    $this->assertDatabaseHas('companies', [
        'name' => 'Janssen Bouw',
        'email' => 'test@example.nl',
    ]);
});

test('webhook maps english field names correctly', function () {
    $this->postJson('/api/webhooks/cf7', [
        'email' => 'eng@example.com',
        'name' => 'John Smith',
        'phone' => '0698765432',
        'postal_code' => '5678CD',
        'house_number' => '5',
        'street' => 'Mainstreet',
        'city' => 'Rotterdam',
    ]);

    $this->assertDatabaseHas('leads', [
        'email' => 'eng@example.com',
        'name' => 'John Smith',
        'postal_code' => '5678CD',
        'house_number' => '5',
    ]);
});

test('webhook splits combined street and house number', function () {
    $this->postJson('/api/webhooks/cf7', [
        'email' => 'split@example.nl',
        'straat' => 'Zuivelhof 6',
    ]);

    $this->assertDatabaseHas('leads', [
        'email' => 'split@example.nl',
        'street' => 'Zuivelhof',
        'house_number' => '6',
    ]);
});

test('webhook splits combined street with letter suffix', function () {
    $this->postJson('/api/webhooks/cf7', [
        'email' => 'letter@example.nl',
        'straat' => 'Kerkstraat 12B',
    ]);

    $this->assertDatabaseHas('leads', [
        'email' => 'letter@example.nl',
        'street' => 'Kerkstraat',
        'house_number' => '12B',
    ]);
});

test('webhook stores all fields in request_data json', function () {
    $this->postJson('/api/webhooks/cf7', [
        'email' => 'json@example.nl',
        'naam' => 'Data Test',
        'omschrijving' => 'Testopdracht in Almere',
    ]);

    $lead = Lead::where('email', 'json@example.nl')->first();
    expect($lead)->not->toBeNull()
        ->and($lead->request_data)->toHaveKey('email')
        ->and($lead->request_data)->toHaveKey('naam');
});

test('webhook uses postcode service when street and city are missing', function () {
    $mockService = Mockery::mock(PostcodeService::class);
    $mockService->shouldReceive('lookup')
        ->with('9876ZZ', '42')
        ->once()
        ->andReturn(['street' => 'Postlaan', 'city' => 'Utrecht']);

    app()->instance(PostcodeService::class, $mockService);

    $this->postJson('/api/webhooks/cf7', [
        'email' => 'postcode@example.nl',
        'postcode' => '9876ZZ',
        'huisnummer' => '42',
    ]);

    $this->assertDatabaseHas('leads', [
        'email' => 'postcode@example.nl',
        'street' => 'Postlaan',
        'city' => 'Utrecht',
        'postal_code' => '9876ZZ',
        'house_number' => '42',
    ]);
});

test('webhook skips postcode lookup when street and city already filled', function () {
    $mockService = Mockery::mock(PostcodeService::class);
    $mockService->shouldNotReceive('lookup');

    app()->instance(PostcodeService::class, $mockService);

    $this->postJson('/api/webhooks/cf7', [
        'email' => 'nolookup@example.nl',
        'postcode' => '1234AB',
        'huisnummer' => '1',
        'straat' => 'Al Ingevuld',
        'plaats' => 'Haarlem',
    ]);

    $this->assertDatabaseHas('leads', [
        'email' => 'nolookup@example.nl',
        'street' => 'Al Ingevuld',
        'city' => 'Haarlem',
    ]);
});

test('webhook ignores CF7 internal fields prefixed with underscore', function () {
    $this->postJson('/api/webhooks/cf7', [
        'email' => 'underscore@example.nl',
        '_wpcf7' => '123',
        '_wpcf7_version' => '5.7',
        '_wpcf7_locale' => 'nl_NL',
        'naam' => 'CF7 Test',
    ]);

    $lead = Lead::where('email', 'underscore@example.nl')->first();
    expect($lead)->not->toBeNull();
    // Internal CF7 fields should not be in request_data
    expect($lead->request_data)->not->toHaveKey('_wpcf7');
});

test('webhook creates lead with New status', function () {
    $this->postJson('/api/webhooks/cf7', [
        'email' => 'status@example.nl',
        'naam' => 'Status Test',
    ]);

    $lead = Lead::where('email', 'status@example.nl')->first();
    expect($lead->status)->toBe(LeadStatus::New);
});

test('webhook returns lead_id and company_id in response', function () {
    $response = $this->postJson('/api/webhooks/cf7', [
        'email' => 'response@example.nl',
        'naam' => 'Response Test',
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => ['lead_id', 'company_id', 'company_existed'],
        ]);
});

test('webhook marks company_existed as false for new company', function () {
    $response = $this->postJson('/api/webhooks/cf7', [
        'email' => 'newco@example.nl',
    ]);

    $response->assertStatus(201)
        ->assertJson(['data' => ['company_existed' => false]]);
});

test('webhook marks company_existed as true for existing company', function () {
    Company::factory()->create(['email' => 'oldco@example.nl']);

    $response = $this->postJson('/api/webhooks/cf7', [
        'email' => 'oldco@example.nl',
    ]);

    $response->assertStatus(201)
        ->assertJson(['data' => ['company_existed' => true]]);
});
