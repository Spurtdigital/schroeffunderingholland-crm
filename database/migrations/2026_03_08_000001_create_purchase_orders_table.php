<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('draft'); // draft, sent, confirmed, delivered, cancelled
            $table->string('language')->default('nl'); // nl, en, de
            $table->date('order_date');
            $table->date('expected_delivery_date')->nullable();
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            // Delivery address (can differ from company address)
            $table->string('delivery_name')->nullable();
            $table->string('delivery_street')->nullable();
            $table->string('delivery_postal_code')->nullable();
            $table->string('delivery_city')->nullable();
            $table->string('delivery_country')->nullable();
            // Totals
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('vat_amount', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            // Optional link to a product (for quick-order from product page)
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
