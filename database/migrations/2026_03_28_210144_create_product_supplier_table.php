<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_supplier', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->string('supplier_sku', 100)->nullable()->comment('Artikelnummer bij leverancier');
            $table->decimal('purchase_price', 10, 2)->default(0)->comment('Inkoopprijs bij deze leverancier');
            $table->boolean('is_preferred')->default(false)->comment('Voorkeursleverancier voor dit product');
            $table->timestamps();

            $table->unique(['product_id', 'supplier_id']);
        });

        // Migrate existing data from products table into the pivot table.
        $products = \Illuminate\Support\Facades\DB::table('products')
            ->whereNotNull('supplier_id')
            ->get(['id', 'supplier_id', 'supplier_article_number', 'purchase_price']);

        foreach ($products as $product) {
            \Illuminate\Support\Facades\DB::table('product_supplier')->insert([
                'product_id' => $product->id,
                'supplier_id' => $product->supplier_id,
                'supplier_sku' => $product->supplier_article_number,
                'purchase_price' => $product->purchase_price,
                'is_preferred' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_supplier');
    }
};
