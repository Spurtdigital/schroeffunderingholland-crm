<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    public ?string $company_name;

    public ?string $company_address;

    public ?string $company_postal_code;

    public ?string $company_city;

    public ?string $company_phone;

    public ?string $company_email;

    public ?string $company_kvk_number;

    public ?string $company_vat_number;

    public ?string $company_bank_account;

    public ?string $company_logo;

    public int $default_vat_percentage;

    public int $default_quote_validity_days;

    public float $default_travel_cost_per_km;

    public ?string $moneybird_api_token;

    public ?string $moneybird_administration_id;

    public bool $moneybird_enabled;

    public int $quote_number_start;

    public ?string $google_maps_api_key;

    public ?string $default_pre_text;

    public ?string $default_post_text;

    public ?string $closing_text;

    public ?string $review_url;

    public ?string $terms_pdf;

    public ?string $email_signature;

    public static function group(): string
    {
        return 'general';
    }
}
