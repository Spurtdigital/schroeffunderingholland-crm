<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'product_id',
        'description',
        'supplier_article_number',
        'quantity',
        'received_quantity',
        'unit_price',
        'total',
        'sort_order',
    ];

    protected $casts = [
        'quantity'          => 'decimal:2',
        'received_quantity' => 'decimal:2',
        'unit_price'        => 'decimal:2',
        'total'             => 'decimal:2',
    ];

    /**
     * Boot: auto-calculate total.
     */
    protected static function booted(): void
    {
        static::saving(function (self $item) {
            $item->total = round((float) $item->quantity * (float) $item->unit_price, 2);
        });
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
