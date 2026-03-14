<?php

namespace App\Models;

use App\Enums\PurchaseOrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'supplier_id',
        'project_id',
        'status',
        'language',
        'order_date',
        'expected_delivery_date',
        'reference',
        'notes',
        'delivery_name',
        'delivery_street',
        'delivery_postal_code',
        'delivery_city',
        'delivery_country',
        'subtotal',
        'vat_amount',
        'total',
        'product_id',
    ];

    protected $casts = [
        'status' => PurchaseOrderStatus::class,
        'order_date' => 'date',
        'expected_delivery_date' => 'date',
        'subtotal' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    /**
     * Boot: auto-generate order number.
     */
    protected static function booted(): void
    {
        static::creating(function (self $order) {
            if (empty($order->order_number)) {
                $year = now()->year;
                $count = self::whereYear('created_at', $year)->count() + 1;
                $order->order_number = 'INK'.$year.'-'.str_pad($count, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class)->orderBy('sort_order');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Recalculate subtotal, VAT and total from items.
     *
     * BTW (VAT) is only applied for domestic (NL) orders. Foreign suppliers
     * (language 'en' or 'de') use 0% VAT — the price is shown excl. VAT and
     * no VAT is charged.
     */
    public function recalculateTotals(): void
    {
        $subtotal = $this->items()->sum('total');

        // Only charge VAT for Dutch orders; foreign orders are 0% BTW.
        $vatPercentage = $this->language === 'nl' ? 21 : 0;
        $vat = round($subtotal * ($vatPercentage / 100), 2);

        $this->update([
            'subtotal' => $subtotal,
            'vat_amount' => $vat,
            'total' => $subtotal + $vat,
        ]);
    }
}
