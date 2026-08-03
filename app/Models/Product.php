<?php

namespace App\Models;

use App\Enums\StockMutationType;
use App\Enums\StockReservationStatus;
use App\Notifications\LowStockNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'short_description',
        'description',
        'sku',
        'supplier_name',
        'supplier_id',
        'supplier_article_number',
        'weight',
        'purchase_price',
        'sales_price',
        'stock_quantity',
        'min_stock_level',
        'is_active',
    ];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'sales_price' => 'decimal:2',
        'weight' => 'decimal:3',
        'stock_quantity' => 'integer',
        'min_stock_level' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the price attribute (alias for sales_price).
     */
    public function getPriceAttribute(): float
    {
        return (float) $this->sales_price;
    }

    /**
     * Get the supplier this product belongs to (legacy single-supplier relationship).
     *
     * @deprecated Use suppliers() BelongsToMany instead.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get all suppliers for this product (multi-supplier via pivot table).
     */
    public function suppliers(): BelongsToMany
    {
        return $this->belongsToMany(Supplier::class, 'product_supplier')
            ->withPivot(['supplier_sku', 'purchase_price', 'is_preferred'])
            ->withTimestamps();
    }

    /**
     * Get the preferred supplier for this product.
     */
    public function preferredSupplier(): ?Supplier
    {
        return $this->suppliers()
            ->wherePivot('is_preferred', true)
            ->first()
            ?? $this->suppliers()->first();
    }

    /**
     * Get the purchase price for a specific supplier (from pivot).
     * Falls back to the product's own purchase_price if no pivot record found.
     */
    public function purchasePriceForSupplier(?int $supplierId): float
    {
        if ($supplierId) {
            $supplier = $this->suppliers()->where('supplier_id', $supplierId)->first();
            if ($supplier) {
                return (float) $supplier->pivot->purchase_price;
            }
        }

        // Fall back to preferred supplier or product-level purchase_price
        $preferred = $this->preferredSupplier();

        return $preferred ? (float) $preferred->pivot->purchase_price : (float) $this->purchase_price;
    }

    /**
     * Get the supplier SKU for a specific supplier (from pivot).
     * Falls back to the legacy supplier_article_number if no pivot record found.
     */
    public function supplierSkuFor(?int $supplierId): ?string
    {
        if ($supplierId) {
            $supplier = $this->suppliers()->where('supplier_id', $supplierId)->first();
            if ($supplier) {
                return $supplier->pivot->supplier_sku;
            }
        }

        // Fall back to preferred supplier or legacy field
        $preferred = $this->preferredSupplier();

        return $preferred ? $preferred->pivot->supplier_sku : $this->supplier_article_number;
    }

    /**
     * Get all quote items that use this product.
     */
    public function quoteItems(): MorphMany
    {
        return $this->morphMany(QuoteItem::class, 'itemable');
    }

    /**
     * Get all stock reservations for this product.
     */
    public function stockReservations(): HasMany
    {
        return $this->hasMany(StockReservation::class);
    }

    /**
     * Get all stock mutations for this product.
     */
    public function stockMutations(): HasMany
    {
        return $this->hasMany(StockMutation::class)->latest();
    }

    /**
     * Get the total quantity currently reserved.
     */
    public function getReservedQuantityAttribute(): int
    {
        return $this->stockReservations()
            ->where('status', StockReservationStatus::Active)
            ->sum('quantity');
    }

    /**
     * Get the available stock quantity (physical stock minus reservations).
     */
    public function getAvailableStockAttribute(): int
    {
        return $this->stock_quantity - $this->reserved_quantity;
    }

    /**
     * Check if the product is low on stock.
     */
    public function isLowStock(): bool
    {
        return $this->available_stock <= $this->min_stock_level;
    }

    /**
     * Check if the product is out of stock.
     */
    public function isOutOfStock(): bool
    {
        return $this->available_stock <= 0;
    }

    /**
     * Check if a specific quantity is available.
     */
    public function hasAvailableStock(int $quantity): bool
    {
        return $this->available_stock >= $quantity;
    }

    /**
     * Reserve stock for a quote.
     */
    public function reserveStock(Quote $quote, int $quantity): StockReservation
    {
        return $this->stockReservations()->create([
            'quote_id' => $quote->id,
            'quantity' => $quantity,
            'status' => StockReservationStatus::Active,
        ]);
    }

    /**
     * Add stock to inventory and log the mutation.
     */
    public function addStock(int $quantity, ?string $reason = null, ?\App\Models\User $user = null, ?\Illuminate\Database\Eloquent\Model $reference = null): void
    {
        $before = $this->stock_quantity;
        $this->increment('stock_quantity', $quantity);
        $this->refresh();

        $this->stockMutations()->create([
            'user_id' => $user?->id ?? Auth::id(),
            'quantity' => $quantity,
            'quantity_before' => $before,
            'quantity_after' => $this->stock_quantity,
            'type' => StockMutationType::Purchase,
            'reason' => $reason,
            'reference_type' => $reference ? get_class($reference) : null,
            'reference_id' => $reference?->id,
        ]);
    }

    /**
     * Consume reserved stock (when project completes) and log the mutation.
     */
    public function consumeStock(int $quantity, ?StockReservation $reservation = null): void
    {
        $before = $this->stock_quantity;
        $this->decrement('stock_quantity', $quantity);
        $this->refresh();

        $this->stockMutations()->create([
            'user_id' => null,
            'quantity' => -$quantity,
            'quantity_before' => $before,
            'quantity_after' => $this->stock_quantity,
            'type' => StockMutationType::Consumed,
            'reason' => null,
            'reference_type' => $reservation ? get_class($reservation) : null,
            'reference_id' => $reservation?->id,
        ]);

        // Stuur een melding als de voorraad onder het minimumniveau komt
        if ($this->min_stock_level > 0 && $this->available_stock <= $this->min_stock_level) {
            NotificationFacade::route('mail', 'bob@schroeffunderingholland.nl')
                ->notify(new LowStockNotification($this));
        }
    }

    /**
     * Credit (reduce) stock and log the mutation.
     * Use this for returns to supplier, write-offs, damage, etc.
     */
    public function creditStock(int $quantity, ?string $reason = null, ?\App\Models\User $user = null, ?\Illuminate\Database\Eloquent\Model $reference = null): void
    {
        $before = $this->stock_quantity;
        $this->decrement('stock_quantity', $quantity);
        $this->refresh();

        $this->stockMutations()->create([
            'user_id' => $user?->id ?? Auth::id(),
            'quantity' => -$quantity,
            'quantity_before' => $before,
            'quantity_after' => $this->stock_quantity,
            'type' => StockMutationType::Credit,
            'reason' => $reason,
            'reference_type' => $reference ? get_class($reference) : null,
            'reference_id' => $reference?->id,
        ]);
    }

    /**
     * Manually correct the stock quantity and log the mutation.
     */
    public function correctStock(int $newQuantity, string $reason, ?\App\Models\User $user = null): void
    {
        $before = $this->stock_quantity;
        $diff = $newQuantity - $before;

        $this->update(['stock_quantity' => $newQuantity]);

        $this->stockMutations()->create([
            'user_id' => $user?->id ?? Auth::id(),
            'quantity' => $diff,
            'quantity_before' => $before,
            'quantity_after' => $newQuantity,
            'type' => StockMutationType::Correction,
            'reason' => $reason,
        ]);
    }

    /**
     * Get all serial numbers for this product.
     */
    public function serialNumbers(): HasMany
    {
        return $this->hasMany(SerialNumber::class);
    }

    /**
     * Get available serial numbers (in stock).
     */
    public function availableSerialNumbers(): HasMany
    {
        return $this->serialNumbers()->where('status', \App\Enums\SerialNumberStatus::InStock);
    }

    /**
     * Get all purchase order items for this product.
     */
    public function purchaseOrderItems(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }
}
