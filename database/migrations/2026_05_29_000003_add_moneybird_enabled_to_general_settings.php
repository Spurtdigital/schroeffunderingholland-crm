<?php

use Spatie\LaravelSettings\Models\SettingsProperty;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        SettingsProperty::firstOrCreate(
            ['group' => 'general', 'name' => 'moneybird_enabled'],
            ['payload' => json_encode(true), 'locked' => false]
        );
    }

    public function down(): void
    {
        SettingsProperty::where('group', 'general')->where('name', 'moneybird_enabled')->delete();
    }
};
