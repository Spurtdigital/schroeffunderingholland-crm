<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Tweestapsverificatie
    |--------------------------------------------------------------------------
    |
    | Wanneer dit aan staat, moet elke gebruiker na het inloggen direct een
    | authenticator-app (TOTP) instellen voordat het paneel toegankelijk is.
    | Testomgevingen zetten dit uit via phpunit.xml.
    |
    */

    'mfa_required' => (bool) env('MFA_REQUIRED', true),

];
