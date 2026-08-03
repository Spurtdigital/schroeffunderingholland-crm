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

        // Handle project completion
        if ($newStatus === ProjectStatus::Completed && $oldStatus !== ProjectStatus::Completed) {
            $this->handleProjectCompleted($project);

            return;
        }

        // Handle project cancellation - release stock reservations
        if ($newStatus === ProjectStatus::Cancelled && $oldStatus !== ProjectStatus::Cancelled) {
            $this->handleProjectCancelled($project);

            return;
        }
    }

    /**
     * Handle project completion: consume stock and process returns.
     */
    private function handleProjectCompleted(Project $project): void
    {
        if (! $project->completion_date) {
            $project->completion_date = now()->toDateString();
        }

        // 1. Consume all active stock reservations
        foreach ($project->stockReservations()->where('status', 'active')->get() as $reservation) {
            $reservation->consume();
        }

        // 2. Consume all reserved serial numbers (reduce quantity)
        $project->load('items.serialNumbers');
        foreach ($project->items as $item) {
            foreach ($item->serialNumbers as $serialNumber) {
                $serialNumber->consume($item);
            }
        }

        // 3. Handle returns: items with negative quantity → credit stock back
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

    /**
     * Handle project cancellation: release all active stock reservations and serial numbers.
     */
    private function handleProjectCancelled(Project $project): void
    {
        foreach ($project->stockReservations()->where('status', 'active')->get() as $reservation) {
            $reservation->release();
        }

        // Release all reserved serial numbers back to available stock
        $project->load('items.serialNumbers');
        foreach ($project->items as $item) {
            foreach ($item->serialNumbers as $serialNumber) {
                $serialNumber->release($item);
            }
        }
    }

    /**
     * Handle the Project "deleting" event.
     * Release all active stock reservations before deleting the project.
     */
    public function deleting(Project $project): void
    {
        // Release all active stock reservations
        foreach ($project->stockReservations()->where('status', 'active')->get() as $reservation) {
            $reservation->release();
        }

        // Release all reserved serial numbers
        $project->load('items.serialNumbers');
        foreach ($project->items as $item) {
            foreach ($item->serialNumbers as $serialNumber) {
                $serialNumber->release($item);
            }
        }
    }
}
