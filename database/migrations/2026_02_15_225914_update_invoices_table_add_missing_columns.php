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
        Schema::table('invoices', function (Blueprint $table) {
            // Rename 'type' to 'invoice_type' for clarity
            $table->renameColumn('type', 'invoice_type');
            
            // Add missing columns
            $table->foreignId('company_id')->after('project_id')->nullable()->constrained()->nullOnDelete();
            $table->date('invoice_date')->after('invoice_number')->nullable();
            $table->date('due_date')->after('invoice_date')->nullable();
            $table->decimal('subtotal', 10, 2)->after('amount')->default(0);
            $table->decimal('vat_amount', 10, 2)->after('subtotal')->default(0);
            $table->decimal('total', 10, 2)->after('vat_amount')->default(0);
            $table->text('notes')->after('total')->nullable();
            
            // Remove old amount column (we use subtotal/vat_amount/total now)
            $table->dropColumn('amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->renameColumn('invoice_type', 'type');
            $table->dropForeign(['company_id']);
            $table->dropColumn(['company_id', 'invoice_date', 'due_date', 'subtotal', 'vat_amount', 'total', 'notes']);
            $table->decimal('amount', 10, 2)->after('invoice_number');
        });
    }
};
