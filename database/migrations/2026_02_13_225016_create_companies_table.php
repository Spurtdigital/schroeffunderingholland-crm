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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('house_number', 20)->nullable();
            $table->string('city')->nullable();
            $table->string('street')->nullable();
            $table->string('moneybird_contact_id')->nullable()->unique();
            $table->timestamps();
            
            $table->index('email');
            $table->index('postal_code');
            $table->index('moneybird_contact_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
