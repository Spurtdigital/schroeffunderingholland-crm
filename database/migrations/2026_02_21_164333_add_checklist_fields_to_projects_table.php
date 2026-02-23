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
        Schema::table('projects', function (Blueprint $table) {
            $table->boolean('hoogtemaat_klant')->default(false)->after('notes');
            $table->boolean('uitgezet_door_klant')->default(false)->after('hoogtemaat_klant');
            $table->boolean('kans_op_zetting')->default(false)->after('uitgezet_door_klant');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['hoogtemaat_klant', 'uitgezet_door_klant', 'kans_op_zetting']);
        });
    }
};
