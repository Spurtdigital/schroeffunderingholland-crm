<?php

namespace App\Observers;

use App\Enums\ProjectStatus;
use App\Models\Project;

class ProjectObserver
{
    /**
     * Handle the Project "updating" event.
     * Consume stock when project status changes to Completed
     */
    public function updating(Project $project): void
    {
        // Check if status is changing to Completed
        if ($project->isDirty('status')) {
            $newStatus = $project->status;
            $oldStatus = $project->getOriginal('status');

            // If moving to Completed, consume all stock reservations from the quote
            if ($newStatus === ProjectStatus::Completed && $oldStatus !== ProjectStatus::Completed) {
                // Auto-fill completion_date if not already set
                if (! $project->completion_date) {
                    $project->completion_date = now()->toDateString();
                }

                $quote = $project->quote;

                if ($quote) {
                    // Consume all active stock reservations
                    foreach ($quote->stockReservations()->where('status', 'active')->get() as $reservation) {
                        $reservation->consume();
                    }
                }
            }
        }
    }
}
