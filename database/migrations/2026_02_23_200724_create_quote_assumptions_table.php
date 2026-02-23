<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quote_assumptions', function (Blueprint $table) {
            $table->id();
            $table->string('title');           // beheernaam (bijv. "Verlengbaar fundament 3.5m")
            $table->text('content');            // de bullet-tekst in de offerte
            $table->string('trigger_sku')->nullable(); // SKU-fragment dat deze aanname auto-aanvinkt
            $table->boolean('active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('quote_assumption_quote', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quote_assumption_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_assumption_quote');
        Schema::dropIfExists('quote_assumptions');
    }
};
