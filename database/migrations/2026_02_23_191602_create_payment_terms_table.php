<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_terms', function (Blueprint $table) {
            $table->id();
            $table->string('title')->comment('Korte naam, bijv. "30 dagen"');
            $table->text('content')->comment('Volledige betalingsvoorwaarde tekst');
            $table->boolean('active')->default(true);
            $table->boolean('is_default')->default(false)->comment('Standaard geselecteerd bij nieuwe offerte');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_terms');
    }
};
