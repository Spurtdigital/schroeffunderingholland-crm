<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')
            ->where('group', 'general')
            ->where('name', 'email_signature')
            ->delete();

        DB::table('settings')->insert([
            'group'   => 'general',
            'name'    => 'email_signature',
            'locked'  => false,
            'payload' => json_encode("Met vriendelijke groet,\n\nSchroeffundering Holland"),
        ]);
    }

    public function down(): void
    {
        DB::table('settings')
            ->where('group', 'general')
            ->where('name', 'email_signature')
            ->delete();
    }
};
