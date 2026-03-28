<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class QuoteAssumption extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'category',
        'content',
        'trigger_sku',
        'has_variable_input',
        'variable_input_label',
        'variable_input_suffix',
        'active',
        'sort_order',
    ];

    protected $casts = [
        'active' => 'boolean',
        'has_variable_input' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Resolve the content with a pivot_value substituted for the {value} placeholder.
     */
    public function resolveContent(?string $pivotValue = null): string
    {
        if ($this->has_variable_input && $pivotValue !== null) {
            $display = trim($pivotValue . ($this->variable_input_suffix ? ' ' . $this->variable_input_suffix : ''));
            return str_replace('{value}', $display, $this->content);
        }

        return str_replace('{value}', '…', $this->content);
    }

    /**
     * Get the quotes that have this assumption selected.
     */
    public function quotes(): BelongsToMany
    {
        return $this->belongsToMany(Quote::class, 'quote_assumption_quote');
    }
}
