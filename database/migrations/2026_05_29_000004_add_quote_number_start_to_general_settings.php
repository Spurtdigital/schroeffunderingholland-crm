<?php

use Spatie\LaravelSettings\Models\SettingsProperty;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        SettingsProperty::firstOrCreate(
            ['group' => 'general', 'name' => 'quote_number_start'],
            ['payload' => json_encode(1), 'locked' => false]
        );
    }

    public function down(): void
    {
        SettingsProperty::where('group', 'general')->where('name', 'quote_number_start')->delete();
    }
};
