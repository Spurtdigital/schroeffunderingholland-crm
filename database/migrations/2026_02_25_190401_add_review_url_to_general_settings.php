<?php

use Spatie\LaravelSettings\Models\SettingsProperty;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        if (! SettingsProperty::where('group', 'general')->where('name', 'review_url')->exists()) {
            SettingsProperty::create([
                'group'   => 'general',
                'name'    => 'review_url',
                'payload' => json_encode(null),
                'locked'  => false,
            ]);
        }
    }

    public function down(): void
    {
        SettingsProperty::where('group', 'general')->where('name', 'review_url')->delete();
    }
};
