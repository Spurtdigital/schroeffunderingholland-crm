<?php

namespace App\Observers;

use App\Models\Lead;

class LeadObserver
{
    /**
     * Handle the Lead "created" event.
     * Logs een duidelijke beschrijving afhankelijk van de bron (webhook of handmatig).
     */
    public function created(Lead $lead): void
    {
        $isFromWebsite = ! empty($lead->request_data);

        activity()
            ->performedOn($lead)
            ->withProperties(['attributes' => ['status' => $lead->status?->value]])
            ->event('created')
            ->log($isFromWebsite ? 'Lead aangemaakt via website' : 'Lead handmatig aangemaakt');
    }
}
