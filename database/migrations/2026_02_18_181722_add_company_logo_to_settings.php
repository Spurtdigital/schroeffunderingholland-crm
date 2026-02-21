<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\LaravelSettings\Models\SettingsProperty;

return new class extends Migration
{
    public function up(): void
    {
        SettingsProperty::firstOrCreate(
            ['group' => 'general', 'name' => 'company_logo'],
            ['payload' => json_encode(null), 'locked' => false]
        );
    }

    public function down(): void
    {
        SettingsProperty::where('group', 'general')->where('name', 'company_logo')->delete();
    }
};
