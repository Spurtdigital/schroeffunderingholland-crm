<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\LaravelSettings\Models\SettingsProperty;

return new class extends Migration
{
    public function up(): void
    {
        SettingsProperty::firstOrCreate(
            ['group' => 'general', 'name' => 'next_quote_number'],
            ['payload' => json_encode(null), 'locked' => false]
        );
    }

    public function down(): void
    {
        SettingsProperty::where('group', 'general')->where('name', 'next_quote_number')->delete();
    }
};
