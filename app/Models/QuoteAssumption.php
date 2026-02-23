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
        'content',
        'trigger_sku',
        'active',
        'sort_order',
    ];

    protected $casts = [
        'active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get the quotes that have this assumption selected.
     */
    public function quotes(): BelongsToMany
    {
        return $this->belongsToMany(Quote::class, 'quote_assumption_quote');
    }
}
