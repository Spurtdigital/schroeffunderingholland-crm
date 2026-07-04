<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')
            ->where('group', 'general')
            ->where('name', 'terms_pdf')
            ->delete();

        DB::table('settings')->insert([
            'group'   => 'general',
            'name'    => 'terms_pdf',
            'locked'  => false,
            'payload' => json_encode(null),
        ]);
    }

    public function down(): void
    {
        DB::table('settings')
            ->where('group', 'general')
            ->where('name', 'terms_pdf')
            ->delete();
    }
};
