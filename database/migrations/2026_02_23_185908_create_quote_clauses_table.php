<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quote_clauses', function (Blueprint $table) {
            $table->id();
            $table->string('title')->comment('Korte naam voor in de beheerpagina (bijv. "Stroom 230V")');
            $table->text('content')->comment('De volledige clausuletekst die in de offerte verschijnt');
            $table->boolean('active')->default(true);
            $table->integer('sort_order')->default(0)->comment('Volgorde in de sluitingstekst');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_clauses');
    }
};
