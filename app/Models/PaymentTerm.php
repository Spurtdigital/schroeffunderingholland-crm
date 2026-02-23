<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentTerm extends Model
{
    protected $fillable = [
        'title',
        'content',
        'active',
        'is_default',
        'sort_order',
    ];

    protected $casts = [
        'active' => 'boolean',
        'is_default' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }
}
