<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Feature flags
    |--------------------------------------------------------------------------
    |
    | Onderdelen die wel in de codebase zitten maar (nog) niet zijn afgenomen
    | of nog niet af zijn. Standaard staan ze UIT, zodat productie ze niet
    | toont zonder dat daar iets voor in de .env hoeft te staan.
    |
    | Aanzetten doe je per omgeving in .env, bijvoorbeeld:
    |   FEATURE_QUOTE_DUPLICATE=true
    |
    | Let op: lees deze waarden altijd via config('features.x') en nooit via
    | env() buiten dit bestand, anders breken ze zodra de config gecached is.
    |
    */

    // Knop 'Dupliceren' op de offerte-tabel, bewerk- en bekijkpagina.
    'quote_duplicate' => env('FEATURE_QUOTE_DUPLICATE', false),

    // Menu-item en overzicht 'Serienummers' onder Administratie.
    'serial_numbers' => env('FEATURE_SERIAL_NUMBERS', false),

    // Bedrijfsoverzicht: de aparte bekijkpagina van een klant met facturen
    // en projecten. Staat dit uit, dan gaan alle klantlinks naar de
    // bewerkpagina en is de bekijkpagina niet bereikbaar.
    'company_overview' => env('FEATURE_COMPANY_OVERVIEW', false),

    // Handmatig volgend offertenummer instellen bij Instellingen > Moneybird.
    // De automatische nummering blijft hoe dan ook werken.
    'quote_number_override' => env('FEATURE_QUOTE_NUMBER_OVERRIDE', false),

];
