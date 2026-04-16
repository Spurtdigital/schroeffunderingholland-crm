<?php

namespace App\Observers;

use App\Models\Company;

class CompanyObserver
{
    /**
     * Handle the Company "created" event.
     * Logt de volledige initiële staat zodat het bedrijf herstelbaar is bij dataverlies.
     */
    public function created(Company $company): void
    {
        activity()
            ->performedOn($company)
            ->withProperties([
                'attributes' => $this->snapshot($company),
            ])
            ->event('created')
            ->log('Bedrijf aangemaakt');
    }

    /**
     * Handle the Company "updated" event.
     * Logt gewijzigde velden met old/new waarden — inclusief moneybird_contact_id.
     */
    public function updated(Company $company): void
    {
        $dirty = $company->getDirty();

        $ignore = ['updated_at'];
        $changed = array_diff_key($dirty, array_flip($ignore));

        if (empty($changed)) {
            return;
        }

        $old = [];
        $new = [];

        foreach ($changed as $field => $newValue) {
            $old[$field] = $company->getOriginal($field);
            $new[$field] = $newValue;
        }

        activity()
            ->performedOn($company)
            ->withProperties([
                'attributes' => $new,
                'old'        => $old,
            ])
            ->event('updated')
            ->log('Bedrijf bijgewerkt');
    }

    /**
     * Handle the Company "deleted" event.
     * Logt de volledige staat op moment van verwijdering — volledig herstelbaar.
     */
    public function deleted(Company $company): void
    {
        activity()
            ->performedOn($company)
            ->withProperties([
                'old' => $this->snapshot($company),
            ])
            ->event('deleted')
            ->log('Bedrijf verwijderd');
    }

    /**
     * Bouw een volledige snapshot van alle bedrijfsvelden.
     */
    private function snapshot(Company $company): array
    {
        return [
            'name'                 => $company->name,
            'contact_person'       => $company->contact_person,
            'salutation'           => $company->salutation,
            'email'                => $company->email,
            'phone'                => $company->phone,
            'street'               => $company->street,
            'house_number'         => $company->house_number,
            'postal_code'          => $company->postal_code,
            'city'                 => $company->city,
            'moneybird_contact_id' => $company->moneybird_contact_id,
        ];
    }
}
