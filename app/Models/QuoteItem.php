<?php

namespace App\Models;

use App\Enums\QuoteItemType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class QuoteItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'quote_id',
        'itemable_type',
        'itemable_id',
        'type',
        'description',
        'pile_length',
        'machine_id',
        'quantity',
        'unit_price',
        'total',
        'sort_order',
        'is_optional',
    ];

    protected $casts = [
        'type' => QuoteItemType::class,
        'pile_length' => 'decimal:2',
        'machine_id' => 'integer',
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total' => 'decimal:2',
        'sort_order' => 'integer',
        'is_optional' => 'boolean',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Automatically calculate total when quantity or unit_price changes
        // Optional items always have total = 0 (excluded from quote totals)
        static::saving(function ($item) {
            $item->total = $item->is_optional ? 0 : round($item->quantity * $item->unit_price, 2);
        });

        // Auto-set sort_order to next available position when creating
        static::creating(function ($item) {
            if (is_null($item->sort_order) || $item->sort_order === 0) {
                $max = static::where('quote_id', $item->quote_id)->max('sort_order');
                $item->sort_order = ($max ?? 0) + 1;
            }
        });
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }

    public function itemable(): MorphTo
    {
        return $this->morphTo();
    }

    public function isProduct(): bool
    {
        return $this->type === QuoteItemType::Product && $this->itemable_type === Product::class;
    }

    public function isFreeLine(): bool
    {
        return $this->type === QuoteItemType::FreeLine;
    }

    public function isLabor(): bool
    {
        return $this->type === QuoteItemType::Labor;
    }

    public function isMileage(): bool
    {
        return $this->type === QuoteItemType::Mileage;
    }
}
