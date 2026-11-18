<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_approved')->default(false)->after('password');
            $table->timestamp('approved_at')->nullable()->after('is_approved');
            $table->unsignedBigInteger('approved_by')->nullable()->after('approved_at');
            $table->text('app_authentication_secret')->nullable()->after('approved_by');
        });

        // Bestaande gebruikers zijn al in gebruik en worden automatisch
        // goedgekeurd. Nieuwe gebruikers moeten eerst door een admin worden
        // goedgekeurd voordat ze kunnen inloggen.
        DB::table('users')->update([
            'is_approved' => true,
            'approved_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'is_approved',
                'approved_at',
                'approved_by',
                'app_authentication_secret',
            ]);
        });
    }
};
