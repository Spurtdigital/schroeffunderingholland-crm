<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')->insert([
            'group' => 'general',
            'name' => 'default_travel_cost_per_km',
            'locked' => false,
            'payload' => json_encode(0.23),
        ]);
    }

    public function down(): void
    {
        DB::table('settings')
            ->where('group', 'general')
            ->where('name', 'default_travel_cost_per_km')
            ->delete();
    }
};
