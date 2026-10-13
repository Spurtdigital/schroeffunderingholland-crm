<?php

use App\Models\Quote;
use App\Models\QuoteAssumption;
use App\Models\QuoteItem;
use App\Settings\GeneralSettings;

/*
 * De itemstabel van de offerte-PDF begint op een nieuwe pagina zodra er
 * inleidende inhoud boven staat (intro-tekst en/of voorwaarden). Staat daar
 * niets, dan blijft de tabel op pagina 1 zodat er geen bijna lege eerste
 * pagina ontstaat.
 *
 * Dit is al twee keer stukgegaan, vandaar deze test. De identieke conditie
 * staat in order-confirmation.blade.php; houd die gelijk.
 */

beforeEach(function () {
    $this->seed();
});

function renderQuotePdfHtml(Quote $quote): string
{
    return view('pdf.quote', [
        'quote' => $quote->load(['company', 'lead', 'items.machine', 'machine', 'assumptions']),
        'settings' => app(GeneralSettings::class),
    ])->render();
}

function quoteWithItems(array $attributes = []): Quote
{
    $quote = Quote::factory()->create($attributes);
    QuoteItem::factory()->create(['quote_id' => $quote->id]);

    return $quote;
}

function attachAssumption(Quote $quote): void
{
    $assumption = QuoteAssumption::create([
        'title' => 'Er is geen sondering aanwezig.',
        'content' => 'Er is geen sondering aanwezig.',
        'active' => true,
        'has_variable_input' => false,
        'sort_order' => 1,
    ]);

    $quote->assumptions()->attach($assumption->id, ['pivot_value' => null]);
}

test('tabel breekt naar een nieuwe pagina bij alleen voorwaarden', function () {
    $quote = quoteWithItems(['pre_text' => null, 'show_assumptions' => true]);
    attachAssumption($quote);

    expect(renderQuotePdfHtml($quote))->toContain('page-break-before: always');
});

test('tabel breekt naar een nieuwe pagina bij alleen intro-tekst', function () {
    $quote = quoteWithItems([
        'pre_text' => 'Hierbij onze aanbieding.',
        'show_assumptions' => true,
    ]);

    expect(renderQuotePdfHtml($quote))->toContain('page-break-before: always');
});

test('tabel blijft op pagina 1 zonder voorwaarden en zonder intro-tekst', function () {
    $quote = quoteWithItems(['pre_text' => null, 'show_assumptions' => true]);

    expect(renderQuotePdfHtml($quote))->not->toContain('page-break-before: always');
});

test('tabel blijft op pagina 1 als voorwaarden zijn uitgezet', function () {
    $quote = quoteWithItems(['pre_text' => null, 'show_assumptions' => false]);
    attachAssumption($quote);

    expect(renderQuotePdfHtml($quote))->not->toContain('page-break-before: always');
});

test('lege intro-tekst telt niet als inhoud boven de tabel', function () {
    $quote = quoteWithItems(['pre_text' => "   \n  ", 'show_assumptions' => true]);

    expect(renderQuotePdfHtml($quote))->not->toContain('page-break-before: always');
});

test('offerte en opdrachtbevestiging gebruiken dezelfde conditie', function () {
    // Statische controle: beide templates moeten de voorwaarden op dezelfde
    // manier meewegen. Gingen ze eerder uit elkaar lopen, dan brak precies
    // dit stilletjes in alleen de offerte.
    $conditie = "show_assumptions !== false && \$quote->assumptions->isNotEmpty()";

    expect(file_get_contents(resource_path('views/pdf/quote.blade.php')))
        ->toContain($conditie)
        ->and(file_get_contents(resource_path('views/pdf/order-confirmation.blade.php')))
        ->toContain($conditie);
});
