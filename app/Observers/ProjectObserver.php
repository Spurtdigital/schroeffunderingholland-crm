<?php

namespace App\Observers;

use App\Enums\ProjectStatus;
use App\Enums\QuoteItemType;
use App\Models\Product;
use App\Models\Project;

class ProjectObserver
{
    public function updating(Project $project): void
    {
        if (! $project->isDirty('status')) {
            return;
        }

        $newStatus = $project->status;
        $oldStatus = $project->getOriginal('status');

        if ($newStatus !== ProjectStatus::Completed || $oldStatus === ProjectStatus::Completed) {
            return;
        }

        if (! $project->completion_date) {
            $project->completion_date = now()->toDateString();
        }

        // 1. Consume all active stock reservations
        foreach ($project->stockReservations()->where('status', 'active')->get() as $reservation) {
            $reservation->consume();
        }

        // 2. Handle returns: items with negative quantity → credit stock back
        $project->load('items.itemable');
        foreach ($project->items as $item) {
            if (
                $item->type === QuoteItemType::Product
                && $item->itemable_type === Product::class
                && $item->itemable_id
                && $item->itemable
                && (int) $item->quantity < 0
            ) {
                $item->itemable->addStock(
                    abs((int) $item->quantity),
                    "Retour: project #{$project->id}",
                    null,
                    $project,
                );
            }
        }
    }
}
