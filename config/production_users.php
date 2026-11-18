<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Productiegebruikers
    |--------------------------------------------------------------------------
    |
    | Deze gebruikers worden aangemaakt door:
    |   php artisan app:create-production-users
    |
    | Laat het e-mailadres leeg om een gebruiker over te slaan. Zet nooit
    | echte wachtwoorden in de repository; gebruik de .env op de server.
    | Bestaande wachtwoorden worden nooit overschreven.
    |
    */

    'users' => [
        1 => [
            'name' => env('PRODUCTION_USER_1_NAME'),
            'email' => env('PRODUCTION_USER_1_EMAIL'),
            'password' => env('PRODUCTION_USER_1_PASSWORD'),
        ],
        2 => [
            'name' => env('PRODUCTION_USER_2_NAME'),
            'email' => env('PRODUCTION_USER_2_EMAIL'),
            'password' => env('PRODUCTION_USER_2_PASSWORD'),
        ],
        3 => [
            'name' => env('PRODUCTION_USER_3_NAME'),
            'email' => env('PRODUCTION_USER_3_EMAIL'),
            'password' => env('PRODUCTION_USER_3_PASSWORD'),
        ],
    ],

];
