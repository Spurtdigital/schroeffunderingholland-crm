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
        Schema::table('leads', function (Blueprint $table) {
            $table->foreign('converted_to_quote_id')
                ->references('id')
                ->on('quotes')
                ->nullOnDelete();
        });

        Schema::table('quotes', function (Blueprint $table) {
            $table->foreign('project_id')
                ->references('id')
                ->on('projects')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropForeign(['converted_to_quote_id']);
        });

        Schema::table('quotes', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
        });
    }
};
