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
            $table->boolean('work_address_different')->default(false)->after('travel_total');
            $table->string('work_street')->nullable()->after('work_address_different');
            $table->string('work_house_number', 20)->nullable()->after('work_street');
            $table->string('work_postal_code', 10)->nullable()->after('work_house_number');
            $table->string('work_city')->nullable()->after('work_postal_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn([
                'work_address_different',
                'work_street',
                'work_house_number',
                'work_postal_code',
                'work_city',
            ]);
        });
    }
};
