<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class QuoteClause extends Model
{
    protected $fillable = [
        'title',
        'content',
        'active',
        'sort_order',
    ];

    protected $casts = [
        'active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get all quotes that have this clause selected.
     */
    public function quotes(): BelongsToMany
    {
        return $this->belongsToMany(Quote::class, 'quote_clause_quote');
    }
}
