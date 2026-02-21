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
        Schema::table('quotes', function (Blueprint $table) {
            $table->string('processing_method')->default('moneybird_draft')->change();
        });

        // Update existing records that still have the old default
        DB::table('quotes')
            ->where('processing_method', 'system')
            ->update(['processing_method' => 'moneybird_draft']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->string('processing_method')->default('system')->change();
        });
    }
};
