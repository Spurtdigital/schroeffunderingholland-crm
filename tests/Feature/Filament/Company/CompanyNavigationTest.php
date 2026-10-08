<?php

use App\Filament\Resources\Companies\CompanyResource;
use App\Filament\Resources\Companies\Pages\EditCompany;
use App\Filament\Resources\Companies\Pages\ViewCompany;
use App\Filament\Resources\Companies\RelationManagers\QuotesRelationManager;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\Quote;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed();
    $this->actingAs(User::first());
});

test('company edit page redirects to the view after saving', function () {
    $company = Company::first();

    Livewire::actingAs(User::first())
        ->test(EditCompany::class, ['record' => $company->getRouteKey()])
        ->call('save')
        ->assertRedirect(CompanyResource::getUrl('view', ['record' => $company]));
});

test('company view page loads with projects and invoices', function () {
    $company = Company::factory()->create();
    $project = Project::factory()->create(['company_id' => $company->id]);
    Invoice::factory()->create(['company_id' => $company->id, 'project_id' => $project->id]);

    $response = $this->get(CompanyResource::getUrl('view', ['record' => $company]));

    $response->assertStatus(200);
    $response->assertDontSee('Whoops');
    $response->assertDontSee('Exception');
});

test('company quotes tab shows the total excluding vat', function () {
    $company = Company::factory()->create();

    $quote = Quote::factory()->create([
        'company_id' => $company->id,
        'subtotal' => 1215.00,
        'vat_amount' => 255.15,
        'total' => 1470.15,
    ]);

    Livewire::test(QuotesRelationManager::class, [
        'ownerRecord' => $company,
        'pageClass' => ViewCompany::class,
    ])
        ->assertCanSeeTableRecords([$quote])
        ->assertTableColumnStateSet('subtotal', '1215.00', $quote);
});
