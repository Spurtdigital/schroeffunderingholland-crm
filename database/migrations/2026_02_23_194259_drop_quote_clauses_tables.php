<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('quote_clause_quote');
        Schema::dropIfExists('quote_clauses');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Not restoring the clausules system - it has been replaced by GeneralSettings::closing_text
    }
};
