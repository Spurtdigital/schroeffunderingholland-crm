<?php

use Spatie\LaravelSettings\Models\SettingsProperty;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Insert default general settings
        $settings = [
            'company_name' => null,
            'company_address' => null,
            'company_postal_code' => null,
            'company_city' => null,
            'company_phone' => null,
            'company_email' => null,
            'company_kvk_number' => null,
            'company_vat_number' => null,
            'company_bank_account' => null,
            'default_vat_percentage' => 21,
            'default_quote_validity_days' => 30,
            'moneybird_api_token' => null,
            'moneybird_administration_id' => null,
            'google_maps_api_key' => null,
        ];

        foreach ($settings as $name => $value) {
            SettingsProperty::create([
                'group' => 'general',
                'name' => $name,
                'payload' => json_encode($value),
                'locked' => false,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        SettingsProperty::where('group', 'general')->delete();
    }
};
