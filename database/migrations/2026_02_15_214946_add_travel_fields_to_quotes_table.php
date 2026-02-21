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
            $table->integer('travel_distance')->nullable()->after('notes')->comment('Distance in kilometers');
            $table->decimal('travel_cost_per_km', 8, 2)->nullable()->after('travel_distance')->comment('Cost per kilometer');
            $table->decimal('travel_total', 10, 2)->nullable()->after('travel_cost_per_km')->comment('Total travel costs');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn(['travel_distance', 'travel_cost_per_km', 'travel_total']);
        });
    }
};
