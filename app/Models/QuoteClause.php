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
        'type',
        'sort_order',
    ];

    protected $casts = [
        'active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Is this clause a free-form section (heading + paragraph) rather than a bullet point?
     */
    public function isSection(): bool
    {
        return $this->type === 'section';
    }

    /**
     * Get all quotes that have this clause selected.
     */
    public function quotes(): BelongsToMany
    {
        return $this->belongsToMany(Quote::class, 'quote_clause_quote');
    }
}
