<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add FK constraint to supplier_id if the column already exists without it
        if (Schema::hasColumn('products', 'supplier_id')) {
            DB::statement('ALTER TABLE products ADD CONSTRAINT products_supplier_id_foreign FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL');
        } else {
            Schema::table('products', function (Blueprint $table) {
                $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete()->after('supplier_name');
            });
        }

        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'supplier_article_number')) {
                $table->string('supplier_article_number')->nullable()->after('supplier_id');
            }
            if (! Schema::hasColumn('products', 'weight')) {
                $table->decimal('weight', 8, 3)->nullable()->after('supplier_article_number');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->dropColumn(['supplier_id', 'supplier_article_number', 'weight']);
        });
    }
};
