<?php

namespace App\Observers;

use App\Models\Lead;

class LeadObserver
{
    /**
     * Handle the Lead "created" event.
     * Logt de volledige initiële staat zodat de lead herstelbaar is bij dataverlies.
     */
    public function created(Lead $lead): void
    {
        $isFromWebsite = ! empty($lead->request_data);

        activity()
            ->performedOn($lead)
            ->withProperties([
                'attributes' => $this->snapshot($lead),
            ])
            ->event('created')
            ->log($isFromWebsite ? 'Lead aangemaakt via website' : 'Lead handmatig aangemaakt');
    }

    /**
     * Handle the Lead "updated" event.
     * Logt gewijzigde velden met old/new waarden.
     */
    public function updated(Lead $lead): void
    {
        $dirty = $lead->getDirty();

        // Sorteer-velden en interne timestamps overslaan
        $ignore = ['updated_at'];
        $changed = array_diff_key($dirty, array_flip($ignore));

        if (empty($changed)) {
            return;
        }

        $old = [];
        $new = [];

        foreach ($changed as $field => $newValue) {
            $old[$field] = $lead->getOriginal($field);
            $new[$field] = $newValue;
        }

        activity()
            ->performedOn($lead)
            ->withProperties([
                'attributes' => $new,
                'old'        => $old,
            ])
            ->event('updated')
            ->log('Lead bijgewerkt');
    }

    /**
     * Handle the Lead "deleted" event.
     * Logt de volledige staat op moment van verwijdering — volledig herstelbaar.
     */
    public function deleted(Lead $lead): void
    {
        activity()
            ->performedOn($lead)
            ->withProperties([
                'old' => $this->snapshot($lead),
            ])
            ->event('deleted')
            ->log('Lead verwijderd');
    }

    /**
     * Bouw een volledige snapshot van alle lead-velden.
     * request_data wordt als array opgeslagen (niet als JSON-string).
     */
    private function snapshot(Lead $lead): array
    {
        return [
            'company_id'            => $lead->company_id,
            'converted_to_quote_id' => $lead->converted_to_quote_id,
            'name'                  => $lead->name,
            'email'                 => $lead->email,
            'phone'                 => $lead->phone,
            'street'                => $lead->street,
            'house_number'          => $lead->house_number,
            'postal_code'           => $lead->postal_code,
            'city'                  => $lead->city,
            'status'                => $lead->status?->value,
            'notes'                 => $lead->notes,
            'internal_notes'        => $lead->internal_notes,
            'request_data'          => $lead->request_data,
        ];
    }
}
