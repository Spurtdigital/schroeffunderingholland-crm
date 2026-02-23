<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the type column from quote_clauses (sections are now a single closing_text in settings)
        if (Schema::hasColumn('quote_clauses', 'type')) {
            Schema::table('quote_clauses', function (Blueprint $table) {
                $table->dropColumn('type');
            });
        }

        // Add closing_text setting row if it doesn't exist yet
        $exists = DB::table('settings')
            ->where('group', 'general')
            ->where('name', 'closing_text')
            ->exists();

        if (! $exists) {
            DB::table('settings')->insert([
                'group' => 'general',
                'name' => 'closing_text',
                'payload' => json_encode(null),
                'locked' => false,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('quote_clauses', function (Blueprint $table) {
            $table->string('type')->default('bullet')->after('active');
        });

        DB::table('settings')
            ->where('group', 'general')
            ->where('name', 'closing_text')
            ->delete();
    }
};
