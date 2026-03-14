<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quote_assumptions', function (Blueprint $table) {
            $table->boolean('has_variable_input')->default(false)->after('trigger_sku');
            $table->string('variable_input_label')->nullable()->after('has_variable_input'); // bijv. "Diepte"
            $table->string('variable_input_suffix')->nullable()->after('variable_input_label'); // bijv. "meter"
        });

        Schema::table('quote_assumption_quote', function (Blueprint $table) {
            $table->string('pivot_value')->nullable()->after('quote_assumption_id'); // opgeslagen waarde voor variabele aanname
        });
    }

    public function down(): void
    {
        Schema::table('quote_assumptions', function (Blueprint $table) {
            $table->dropColumn(['has_variable_input', 'variable_input_label', 'variable_input_suffix']);
        });

        Schema::table('quote_assumption_quote', function (Blueprint $table) {
            $table->dropColumn('pivot_value');
        });
    }
};
