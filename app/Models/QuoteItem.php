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
        'quantity',
        'unit_price',
        'total',
        'sort_order',
    ];

    protected $casts = [
        'type' => QuoteItemType::class,
        'pile_length' => 'decimal:2',
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Automatically calculate total when quantity or unit_price changes
        static::saving(function ($item) {
            $item->total = round($item->quantity * $item->unit_price, 2);
        });

        // Auto-set sort_order to next available position when creating
        static::creating(function ($item) {
            if (is_null($item->sort_order) || $item->sort_order === 0) {
                $max = static::where('quote_id', $item->quote_id)->max('sort_order');
                $item->sort_order = ($max ?? 0) + 1;
            }
        });
    }

    /**
     * Get the quote this item belongs to.
     */
    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    /**
     * Get the polymorphic itemable model (Product or null for free lines).
     */
    public function itemable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Check if this item is a product.
     */
    public function isProduct(): bool
    {
        return $this->type === QuoteItemType::Product && $this->itemable_type === Product::class;
    }

    /**
     * Check if this item is a free line.
     */
    public function isFreeLine(): bool
    {
        return $this->type === QuoteItemType::FreeLine;
    }

    /**
     * Check if this item is labor.
     */
    public function isLabor(): bool
    {
        return $this->type === QuoteItemType::Labor;
    }

    /**
     * Check if this item is mileage.
     */
    public function isMileage(): bool
    {
        return $this->type === QuoteItemType::Mileage;
    }
}
