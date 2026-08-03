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
        Schema::create('serial_numbers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('serial_number');
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedInteger('quantity_reserved')->default(0);
            $table->foreignId('stock_mutation_id')->nullable()->constrained()->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Serienummer moet uniek zijn per product
            $table->unique(['product_id', 'serial_number']);
        });

        // Pivot table for project_item <-> serial_number with quantity
        Schema::create('project_item_serial_number', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('serial_number_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamps();

            $table->unique(['project_item_id', 'serial_number_id'], 'item_serial_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_item_serial_number');
        Schema::dropIfExists('serial_numbers');
    }
};
