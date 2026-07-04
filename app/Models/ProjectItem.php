<?php

namespace App\Models;

use App\Enums\QuoteItemType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ProjectItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'quote_item_id',
        'itemable_type',
        'itemable_id',
        'type',
        'description',
        'quantity',
        'unit_price',
        'total',
        'sort_order',
        'is_optional',
    ];

    protected $casts = [
        'type' => QuoteItemType::class,
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

        // Auto-set sort_order to next available position when creating
        static::creating(function ($item) {
            if (is_null($item->sort_order) || $item->sort_order === 0) {
                $max = static::where('project_id', $item->project_id)->max('sort_order');
                $item->sort_order = ($max ?? 0) + 1;
            }
        });
    }

    /**
     * Get the project this item belongs to.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the original quote item this was copied from.
     */
    public function quoteItem(): BelongsTo
    {
        return $this->belongsTo(QuoteItem::class);
    }

    /**
     * Get the itemable model (Product, etc.)
     */
    public function itemable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Calculate total from quantity and unit price.
     */
    public function calculateTotal(): void
    {
        $this->total = round($this->quantity * $this->unit_price, 2);
    }
}
