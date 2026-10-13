<?php

use App\Models\Company;
use App\Models\Project;
use App\Models\Quote;
use App\Settings\GeneralSettings;

/*
 * De slottekst (de eisen aan de klant: stroom, vrij van obstakels, Klic,
 * wachttijd) komt uit de instelling closing_text en hoort op zowel de
 * offerte als de opdrachtbevestiging te staan.
 *
 * Op de opdrachtbevestiging ontbrak hij lange tijd volledig, waardoor de
 * klant tekende voor een document zonder die eisen. Vandaar deze test.
 */

beforeEach(function () {
    $this->seed();

    $settings = app(GeneralSettings::class);
    $settings->closing_text = "**Bij deze aanbieding gaat Schroeffundering Holland B.V. ervan uit dat:**\n\n".
        "* Er op locatie een 230 Volt stroomaansluiting aanwezig is voor onze machines.\n\n".
        "**Klic melding:**\n\nWij vragen voor aanvang een klic melding aan bij het Kadaster.";
    $settings->save();
});

function renderOrderConfirmation(Project $project): string
{
    $quote = $project->quote?->load(['company', 'lead', 'items.machine', 'assumptions', 'paymentTerm']);

    return view('pdf.order-confirmation', [
        'quote' => $quote,
        'company' => $quote?->company ?? $project->load('company')->company,
        'project' => $project,
        'settings' => app(GeneralSettings::class),
    ])->render();
}

function projectWithQuote(array $quoteAttributes = []): Project
{
    $company = Company::factory()->create();
    $quote = Quote::factory()->create(array_merge(['company_id' => $company->id], $quoteAttributes));

    return Project::factory()->create([
        'company_id' => $company->id,
        'quote_id' => $quote->id,
    ]);
}

test('opdrachtbevestiging toont de slottekst', function () {
    $html = renderOrderConfirmation(projectWithQuote());

    expect($html)
        ->toContain('Bij deze aanbieding gaat Schroeffundering Holland B.V. ervan uit dat')
        ->toContain('230 Volt stroomaansluiting')
        ->toContain('Klic melding');
});

test('slottekst op de opdrachtbevestiging staat los van de voorwaarden-schakelaar', function () {
    // show_assumptions stuurt alleen de voorwaarden aan. De eisen aan de klant
    // horen op het document dat getekend wordt altijd zichtbaar te zijn.
    $html = renderOrderConfirmation(projectWithQuote(['show_assumptions' => false]));

    expect($html)->toContain('230 Volt stroomaansluiting');
});

test('kopjes in de slottekst worden vet weergegeven', function () {
    $html = renderOrderConfirmation(projectWithQuote());

    expect($html)
        ->toContain('<strong>Bij deze aanbieding gaat Schroeffundering Holland B.V. ervan uit dat:</strong>')
        ->toContain('<strong>Klic melding:</strong>');
});

test('zonder ingestelde slottekst blijft het blok weg', function () {
    $settings = app(GeneralSettings::class);
    $settings->closing_text = null;
    $settings->save();

    // Let op: 'closing-section' staat ook in de CSS, dus toets op de div zelf.
    expect(renderOrderConfirmation(projectWithQuote()))
        ->not->toContain('<div class="closing-section">');
});
