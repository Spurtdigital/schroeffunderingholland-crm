<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('quote_clauses', function (Blueprint $table) {
            // 'bullet' = bullet-punt in de voorwaarden-lijst
            // 'section' = vrije alinea met eigen kopje (Extra info, Weer, Klic, Wachttijd etc.)
            $table->string('type')->default('bullet')->after('active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quote_clauses', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
