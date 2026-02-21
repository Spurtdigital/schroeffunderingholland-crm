<?php

namespace App\Models;

use App\Enums\StockMutationType;
use App\Enums\StockReservationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockReservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'quote_id',
        'quantity',
        'status',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'status' => StockReservationStatus::class,
    ];

    /**
     * Get the product this reservation is for.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the quote this reservation is for.
     */
    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    /**
     * Check if the reservation is active.
     */
    public function isActive(): bool
    {
        return $this->status === StockReservationStatus::Active;
    }

    /**
     * Release the reservation (returns stock to available) and log the mutation.
     */
    public function release(): void
    {
        if (! $this->isActive()) {
            return;
        }

        $this->update(['status' => StockReservationStatus::Released]);

        $product = $this->product;
        $product->stockMutations()->create([
            'user_id' => null,
            'quantity' => 0,
            'quantity_before' => $product->stock_quantity,
            'quantity_after' => $product->stock_quantity,
            'type' => StockMutationType::Released,
            'reason' => 'Reservering vrijgegeven',
            'reference_type' => self::class,
            'reference_id' => $this->id,
        ]);
    }

    /**
     * Consume the reservation (actually removes stock when project completes) and log the mutation.
     */
    public function consume(): void
    {
        if (! $this->isActive()) {
            return;
        }

        $this->product->consumeStock($this->quantity, $this);
        $this->update(['status' => StockReservationStatus::Consumed]);
    }
}
