<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\LaravelSettings\Models\SettingsProperty;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        SettingsProperty::firstOrCreate(
            ['group' => 'general', 'name' => 'default_pre_text'],
            ['locked' => false, 'payload' => json_encode(null)]
        );

        SettingsProperty::firstOrCreate(
            ['group' => 'general', 'name' => 'default_post_text'],
            ['locked' => false, 'payload' => json_encode(null)]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('settings')
            ->where('group', 'general')
            ->whereIn('name', ['default_pre_text', 'default_post_text'])
            ->delete();
    }
};
