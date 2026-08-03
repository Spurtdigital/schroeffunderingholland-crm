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
            $table->timestamp('signed_at')->nullable()->after('approved_at');
            $table->string('signed_ip', 45)->nullable()->after('signed_at');
            $table->string('signed_by_name')->nullable()->after('signed_ip');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn(['signed_at', 'signed_ip', 'signed_by_name']);
        });
    }
};
