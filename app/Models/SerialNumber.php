<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SerialNumber extends Model
{
    protected $fillable = [
        'product_id',
        'serial_number',
        'quantity',
        'quantity_reserved',
        'stock_mutation_id',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'quantity_reserved' => 'integer',
    ];

    /**
     * Get the product this serial number belongs to.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the stock mutation that created this serial number.
     */
    public function stockMutation(): BelongsTo
    {
        return $this->belongsTo(StockMutation::class);
    }

    /**
     * Get the project items this serial number is assigned to.
     */
    public function projectItems(): BelongsToMany
    {
        return $this->belongsToMany(ProjectItem::class, 'project_item_serial_number')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    /**
     * Get available quantity (total minus reserved).
     */
    public function getAvailableQuantityAttribute(): int
    {
        return $this->quantity - $this->quantity_reserved;
    }

    /**
     * Get the locations (projects/customers) this serial number is linked to,
     * grouped per project. Used to trace where the product was delivered.
     *
     * @return array<int, array<string, mixed>>
     */
    public function deliveryLocations(): array
    {
        return $this->projectItems
            ->groupBy('project_id')
            ->map(function ($items) {
                $project = $items->first()->project;

                return [
                    'project_id' => $project?->id,
                    'project_number' => $project?->project_number,
                    'company_id' => $project?->company_id,
                    'company' => $project?->company?->name,
                    'quantity' => (int) $items->sum(fn ($item) => (int) $item->pivot->quantity),
                    'status' => $project?->status,
                    'completion_date' => $project?->completion_date,
                ];
            })
            ->sortByDesc('quantity')
            ->values()
            ->all();
    }

    /**
     * Check if there's available stock.
     */
    public function hasAvailableStock(): bool
    {
        return $this->available_quantity > 0;
    }

    /**
     * Reserve a quantity for a project item.
     */
    public function reserve(ProjectItem $projectItem, int $quantity): void
    {
        if ($quantity > $this->available_quantity) {
            throw new \InvalidArgumentException("Cannot reserve {$quantity}, only {$this->available_quantity} available.");
        }

        // Update or create pivot record
        $this->projectItems()->syncWithoutDetaching([
            $projectItem->id => ['quantity' => $quantity],
        ]);

        // Update reserved quantity
        $this->increment('quantity_reserved', $quantity);
    }

    /**
     * Release reserved quantity back to available stock.
     */
    public function release(ProjectItem $projectItem): void
    {
        $pivotRecord = $this->projectItems()->where('project_item_id', $projectItem->id)->first();

        if ($pivotRecord) {
            $reservedQty = $pivotRecord->pivot->quantity;
            $this->decrement('quantity_reserved', $reservedQty);
            $this->projectItems()->detach($projectItem->id);
        }
    }

    /**
     * Consume reserved stock (when project is completed).
     * Reduces the actual quantity.
     */
    public function consume(ProjectItem $projectItem): void
    {
        $pivotRecord = $this->projectItems()->where('project_item_id', $projectItem->id)->first();

        if ($pivotRecord) {
            $consumedQty = $pivotRecord->pivot->quantity;
            $this->decrement('quantity', $consumedQty);
            $this->decrement('quantity_reserved', $consumedQty);
        }
    }

    /**
     * Scope to get serial numbers with available stock.
     */
    public function scopeAvailable($query)
    {
        return $query->whereRaw('quantity > quantity_reserved');
    }

    /**
     * Scope to get serial numbers for a specific product.
     */
    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }
}
