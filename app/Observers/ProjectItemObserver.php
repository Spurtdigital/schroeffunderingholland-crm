<?php

namespace App\Observers;

use App\Models\ProjectItem;

class ProjectItemObserver
{
    /**
     * Handle the ProjectItem "created" event.
     */
    public function created(ProjectItem $item): void
    {
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

    /**
     * Handle the ProjectItem "updated" event.
     */
    public function updated(ProjectItem $item): void
    {
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

    /**
     * Handle the ProjectItem "deleted" event.
     */
    public function deleted(ProjectItem $item): void
    {
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
}
