<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'contact_name',
        'email',
        'phone',
        'website',
        'notes',
        'is_active',
        'language',
        'address',
        'postal_code',
        'city',
        'country',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get all products from this supplier (legacy single-supplier relationship).
     *
     * @deprecated Use suppliedProducts() BelongsToMany instead.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get all products supplied by this supplier (multi-supplier via pivot table).
     */
    public function suppliedProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_supplier')
            ->withPivot(['supplier_sku', 'purchase_price', 'is_preferred'])
            ->withTimestamps();
    }

    /**
     * Get all purchase orders for this supplier.
     */
    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }
}
