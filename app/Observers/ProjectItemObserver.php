<?php

namespace App\Observers;

use App\Enums\QuoteItemType;
use App\Enums\StockMutationType;
use App\Enums\StockReservationStatus;
use App\Models\Product;
use App\Models\ProjectItem;
use Illuminate\Support\Facades\Auth;

class ProjectItemObserver
{
    public function created(ProjectItem $item): void
    {
        $this->syncReservationOnCreate($item);

        $project = $item->project;
        if (! $project) {
            return;
        }

        activity()
            ->performedOn($project)
            ->causedBy(auth()->user())
            ->withProperties([
                'item_description' => $item->description,
                'item_quantity' => $item->quantity,
                'item_unit_price' => $item->unit_price,
                'item_total' => $item->total,
                'item_type' => $item->type?->value,
            ])
            ->event('item_added')
            ->log('Item toegevoegd: '.$item->description);
    }

    public function updated(ProjectItem $item): void
    {
        $this->syncReservationOnUpdate($item);

        $project = $item->project;
        if (! $project) {
            return;
        }

        $changed = collect(['description', 'quantity', 'unit_price', 'total'])
            ->filter(fn ($field) => $item->wasChanged($field))
            ->all();

        if (empty($changed)) {
            return;
        }

        $old = [];
        $new = [];
        foreach ($changed as $field) {
            $old[$field] = $item->getOriginal($field);
            $new[$field] = $item->getAttribute($field);
        }

        activity()
            ->performedOn($project)
            ->causedBy(auth()->user())
            ->withProperties(['old' => $old, 'attributes' => $new])
            ->event('item_updated')
            ->log('Item bijgewerkt: '.$item->description);
    }

    public function deleted(ProjectItem $item): void
    {
        $this->releaseReservationOnDelete($item);

        $project = $item->project;
        if (! $project) {
            return;
        }

        activity()
            ->performedOn($project)
            ->causedBy(auth()->user())
            ->withProperties([
                'item_description' => $item->description,
                'item_quantity' => $item->quantity,
                'item_total' => $item->total,
            ])
            ->event('item_deleted')
            ->log('Item verwijderd: '.$item->description);
    }

    private function syncReservationOnCreate(ProjectItem $item): void
    {
        if ($item->type !== QuoteItemType::Product || $item->itemable_type !== Product::class || ! $item->itemable_id) {
            return;
        }

        // Skip returns (negative or zero quantity) — no reservation needed
        if ((int) $item->quantity <= 0) {
            return;
        }

        $project = $item->project;
        if (! $project) {
            return;
        }

        $product = Product::find($item->itemable_id);
        if (! $product) {
            return;
        }

        $reservation = $project->stockReservations()
            ->where('product_id', $product->id)
            ->where('status', StockReservationStatus::Active)
            ->first();

        if ($reservation) {
            $reservation->increment('quantity', (int) $item->quantity);
        } else {
            $project->stockReservations()->create([
                'product_id' => $product->id,
                'quote_id' => $project->quote?->id,
                'quantity' => (int) $item->quantity,
                'status' => StockReservationStatus::Active,
            ]);
        }
    }

    private function syncReservationOnUpdate(ProjectItem $item): void
    {
        if ($item->type !== QuoteItemType::Product || $item->itemable_type !== Product::class || ! $item->itemable_id) {
            return;
        }

        $project = $item->project;
        if (! $project || ! $item->wasChanged('quantity')) {
            return;
        }

        $oldQty = (int) $item->getOriginal('quantity');
        $newQty = (int) $item->quantity;

        // If the item became 0 or negative, release the full reservation (return)
        if ($newQty <= 0) {
            $reservation = $project->stockReservations()
                ->where('product_id', $item->itemable_id)
                ->where('status', StockReservationStatus::Active)
                ->first();
            if ($reservation) {
                $reservation->release();
            }
            return;
        }

        // If it was 0 or negative and now positive, there's nothing to adjust
        if ($oldQty <= 0) {
            return;
        }

        // Both old and new are positive — adjust reservation by the difference
        $diff = $oldQty - $newQty;
        if ($diff === 0) {
            return;
        }

        $reservation = $project->stockReservations()
            ->where('product_id', $item->itemable_id)
            ->where('status', StockReservationStatus::Active)
            ->first();

        if (! $reservation) {
            return;
        }

        if ($diff > 0) {
            $reservation->decrement('quantity', $diff);

            if ($reservation->fresh()->quantity <= 0) {
                $reservation->release();
            } else {
                $reservation->product->stockMutations()->create([
                    'user_id' => Auth::id(),
                    'quantity' => 0,
                    'quantity_before' => $reservation->product->stock_quantity,
                    'quantity_after' => $reservation->product->stock_quantity,
                    'type' => StockMutationType::Released,
                    'reason' => "Reservering verlaagd: project #{$project->id} regel, {$oldQty} → {$newQty}",
                    'reference_type' => ProjectItem::class,
                    'reference_id' => $item->id,
                ]);
            }
        } else {
            $reservation->increment('quantity', abs($diff));
        }
    }

    private function releaseReservationOnDelete(ProjectItem $item): void
    {
        if ($item->type !== QuoteItemType::Product || $item->itemable_type !== Product::class || ! $item->itemable_id) {
            return;
        }

        $project = $item->project;
        if (! $project) {
            return;
        }

        $reservation = $project->stockReservations()
            ->where('product_id', $item->itemable_id)
            ->where('status', StockReservationStatus::Active)
            ->first();

        if ($reservation) {
            $reservation->release();
        }
    }
}
