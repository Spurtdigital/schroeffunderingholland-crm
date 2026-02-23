<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\Quote;
use App\Settings\GeneralSettings;
use Picqer\Financials\Moneybird\Connection;
use Picqer\Financials\Moneybird\Exceptions\ApiException;
use Picqer\Financials\Moneybird\Moneybird;

class MoneybirdService
{
    protected ?Moneybird $moneybird = null;

    protected GeneralSettings $settings;

    public function __construct(GeneralSettings $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Get Moneybird API instance (public accessor)
     */
    public function getMoneybirdInstance(): ?Moneybird
    {
        return $this->getMoneybird();
    }

    /**
     * Get Moneybird API instance
     */
    protected function getMoneybird(): ?Moneybird
    {
        if ($this->moneybird) {
            return $this->moneybird;
        }

        $apiToken = $this->settings->moneybird_api_token;
        $administrationId = $this->settings->moneybird_administration_id;

        if (! $apiToken || ! $administrationId) {
            return null;
        }

        $connection = new Connection;
        $connection->setAccessToken($apiToken);
        $connection->setAdministrationId($administrationId);

        $this->moneybird = new Moneybird($connection);

        return $this->moneybird;
    }

    /**
     * Check if Moneybird is configured
     */
    public function isConfigured(): bool
    {
        return $this->settings->moneybird_api_token
            && $this->settings->moneybird_administration_id;
    }

    /**
     * Get the tax rate ID for a specific percentage
     * Caches the result to avoid multiple API calls
     */
    protected function getTaxRateId(float $percentage = 21.0): ?string
    {
        static $taxRates = null;

        if ($taxRates === null) {
            $moneybird = $this->getMoneybird();
            if (! $moneybird) {
                return null;
            }

            try {
                $taxRates = $moneybird->taxRate()->get();
            } catch (ApiException $e) {
                // If we can't fetch tax rates, fall back to hardcoded common IDs
                // In most Dutch Moneybird accounts:
                // ID 1 = 21% (hoog tarief)
                // ID 2 = 9% (laag tarief)
                // ID 3 = 0% (geen BTW)
                return match ($percentage) {
                    21.0 => '1',
                    9.0 => '2',
                    0.0 => '3',
                    default => '1',
                };
            }
        }

        // Find the tax rate with matching percentage
        foreach ($taxRates as $taxRate) {
            if (abs((float) $taxRate->percentage - $percentage) < 0.01) {
                return (string) $taxRate->id;
            }
        }

        // Fallback: return the first active sales tax rate
        foreach ($taxRates as $taxRate) {
            if ($taxRate->tax_rate_type === 'sales_invoice' && $taxRate->active) {
                return (string) $taxRate->id;
            }
        }

        // Last resort fallback
        return '1';
    }

    /**
     * Test Moneybird connection
     */
    public function testConnection(): array
    {
        $moneybird = $this->getMoneybird();

        if (! $moneybird) {
            return [
                'success' => false,
                'message' => 'Moneybird is niet geconfigureerd. Vul API Token en Administratie ID in.',
            ];
        }

        try {
            // Try to fetch contacts to verify connection (simpler API call)
            $contacts = $moneybird->contact()->get();

            $count = is_array($contacts) ? count($contacts) : $contacts->count();

            return [
                'success' => true,
                'message' => "Connectie succesvol! Administratie ID: {$this->settings->moneybird_administration_id}. Er zijn {$count} contacten gevonden.",
            ];
        } catch (ApiException $e) {
            return [
                'success' => false,
                'message' => 'Connectie mislukt: '.$e->getMessage(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Onbekende fout: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Sync company to Moneybird as contact
     */
    public function syncCompany(Company $company): bool
    {
        $moneybird = $this->getMoneybird();

        if (! $moneybird) {
            throw new \Exception('Moneybird is niet geconfigureerd. Controleer de API instellingen.');
        }

        try {
            // Check if contact already exists
            if ($company->moneybird_contact_id) {
                // Update existing contact
                $contact = $moneybird->contact()->find($company->moneybird_contact_id);
                $contact->company_name = $company->name;
                $contact->email = $company->email;
                $contact->phone = $company->phone;
                $contact->address1 = $company->street;
                $contact->address2 = $company->house_number;
                $contact->zipcode = $company->postal_code;
                $contact->city = $company->city;
                $contact->save($contact);
            } else {
                // Create new contact
                $contact = $moneybird->contact();
                $contact->company_name = $company->name;
                $contact->email = $company->email;
                $contact->phone = $company->phone;
                $contact->address1 = $company->street;
                $contact->address2 = $company->house_number;
                $contact->zipcode = $company->postal_code;
                $contact->city = $company->city;
                $contact->save();

                // Store Moneybird contact ID
                $company->update([
                    'moneybird_contact_id' => $contact->id,
                ]);
            }

            return true;
        } catch (ApiException $e) {
            throw new \Exception('Moneybird API fout: '.$e->getMessage());
        }
    }

    /**
     * Send invoice to Moneybird
     */
    public function sendInvoice(Invoice $invoice): bool
    {
        $moneybird = $this->getMoneybird();

        if (! $moneybird) {
            throw new \Exception('Moneybird is niet geconfigureerd. Controleer de API instellingen.');
        }

        // First, ensure the company is synced
        if (! $invoice->project->company->moneybird_contact_id) {
            $this->syncCompany($invoice->project->company);
        }

        try {
            // Check if invoice already exists
            if ($invoice->moneybird_invoice_id) {
                // Invoice already sent, skip
                return true;
            }

            // Create new invoice in Moneybird
            $mbInvoice = $moneybird->salesInvoice();
            $mbInvoice->contact_id = $invoice->project->company->moneybird_contact_id;
            $mbInvoice->reference = $invoice->invoice_number;
            $mbInvoice->invoice_date = $invoice->invoice_date->format('Y-m-d');

            // Set subject based on invoice type
            $subject = match ($invoice->invoice_type) {
                \App\Enums\InvoiceType::Deposit => "Aanbetaling project {$invoice->project->project_number}",
                \App\Enums\InvoiceType::Final => "Eindfactuur project {$invoice->project->project_number}",
                \App\Enums\InvoiceType::Single => "Factuur project {$invoice->project->project_number}",
            };

            // Add percentage to subject if applicable
            if ($invoice->deposit_percentage && $invoice->deposit_percentage != 100) {
                $subject .= " ({$invoice->deposit_percentage}%)";
            }

            $mbInvoice->subject = $subject;

            // Build details array first (avoid overloaded property modification issue)
            $details = [];
            $quote = $invoice->project->quote;
            if ($quote) {
                // Get the appropriate tax rate ID (21% BTW)
                $taxRateId = $this->getTaxRateId(21.0);

                foreach ($quote->items as $item) {
                    $detail = $moneybird->salesInvoiceDetail();
                    $detail->description = $item->description;
                    $detail->price = (string) $item->unit_price;
                    $detail->amount = (string) $item->quantity;
                    $detail->tax_rate_id = $taxRateId;

                    $details[] = $detail;
                }
            }

            // Assign all details at once
            $mbInvoice->details = $details;

            $mbInvoice->save();

            // Store Moneybird invoice ID
            $invoice->update([
                'moneybird_invoice_id' => $mbInvoice->id,
            ]);

            return true;
        } catch (ApiException $e) {
            throw new \Exception('Moneybird API fout: '.$e->getMessage());
        }
    }

    /**
     * Send quote to Moneybird as estimate
     */
    public function sendQuote(Quote $quote): bool
    {
        $moneybird = $this->getMoneybird();

        if (! $moneybird) {
            throw new \Exception('Moneybird is niet geconfigureerd. Controleer de API instellingen.');
        }

        // First, ensure the company is synced
        if (! $quote->company->moneybird_contact_id) {
            $this->syncCompany($quote->company);
        }

        try {
            // Check if quote already exists
            if ($quote->moneybird_quote_id) {
                // Quote already sent, skip
                return true;
            }

            // Create new estimate in Moneybird
            $mbEstimate = $moneybird->estimate();
            $mbEstimate->contact_id = $quote->company->moneybird_contact_id;
            $mbEstimate->reference = $quote->quote_number;
            $mbEstimate->estimate_date = $quote->created_at->format('Y-m-d');
            $mbEstimate->pre_text = $quote->pre_text;
            $mbEstimate->post_text = $this->buildPostText($quote);

            // Build details and assign
            $mbEstimate->details = $this->buildEstimateDetails($moneybird, $quote);

            $mbEstimate->save();

            // Store Moneybird estimate ID and timestamp
            $quote->update([
                'moneybird_quote_id' => $mbEstimate->id,
                'sent_at' => now(),
            ]);

            return true;
        } catch (ApiException $e) {
            throw new \Exception('Moneybird API fout: '.$e->getMessage());
        }
    }

    /**
     * Update an existing estimate in Moneybird with the current quote data
     */
    public function updateQuote(Quote $quote): bool
    {
        $moneybird = $this->getMoneybird();

        if (! $moneybird) {
            throw new \Exception('Moneybird is niet geconfigureerd. Controleer de API instellingen.');
        }

        if (! $quote->moneybird_quote_id) {
            throw new \Exception('Deze offerte is nog niet naar Moneybird verstuurd.');
        }

        try {
            $mbEstimate = $moneybird->estimate()->find($quote->moneybird_quote_id);

            // Remove nested fields that should not be sent in the PATCH request.
            // The library always includes all multipleNestedEntities in the payload,
            // even if not modified. Attachments in particular cause a 400 error when
            // re-submitted (Moneybird expects a file hash, not the existing objects).
            $reflection = new \ReflectionObject($mbEstimate);
            $attributesProp = $reflection->getProperty('attributes');
            $attributesProp->setAccessible(true);
            $attrs = $attributesProp->getValue($mbEstimate);
            foreach (['attachments', 'notes', 'events', 'tax_totals', 'custom_fields'] as $field) {
                unset($attrs[$field]);
            }
            $attributesProp->setValue($mbEstimate, $attrs);

            // Mark all existing details for deletion
            $destroyDetails = [];
            foreach ($mbEstimate->details as $existingDetail) {
                $detail = $moneybird->salesInvoiceDetail();
                $detail->id = $existingDetail->id;
                $detail->_destroy = true;
                $destroyDetails[] = $detail;
            }

            // Build new details
            $newDetails = $this->buildEstimateDetails($moneybird, $quote);

            // Send destroy + new in one PATCH
            $mbEstimate->details = array_merge($destroyDetails, $newDetails);
            $mbEstimate->pre_text = $quote->pre_text;
            $mbEstimate->post_text = $this->buildPostText($quote);
            $mbEstimate->save();

            return true;
        } catch (ApiException $e) {
            throw new \Exception('Moneybird API fout: '.$e->getMessage());
        }
    }

    /**
     * Build the post_text for a Moneybird estimate by combining selected clauses
     * (in sort_order) with any free-form post_text on the quote.
     *
     * Structure:
     *  1. Bullet clauses  → "Bij deze aanbieding gaat X ervan uit dat:" + bullet list
     *  2. Payment term    → "*Betalingsvoorwaarden:*" + content
     *  3. Section clauses → "*Titel:*" + paragraph (in sort_order, interleaved with bullets)
     *  4. Free post_text
     *  5. Fixed closing text
     */
    protected function buildPostText(Quote $quote): string
    {
        $parts = [];

        // Load all selected clauses ordered by sort_order (with type)
        $clauses = $quote->clauses()->orderBy('sort_order')->get();

        // Split into bullets and sections
        $bullets = $clauses->where('type', 'bullet');
        $sections = $clauses->where('type', 'section');

        // 1. Bullet list
        if ($bullets->isNotEmpty()) {
            $companyName = $this->settings->company_name ?? 'Schroeffundering Holland B.V.';
            $parts[] = 'Bij deze aanbieding gaat '.$companyName.' ervan uit dat:';
            foreach ($bullets as $clause) {
                $parts[] = '* '.$clause->content;
            }
        }

        // 2. Payment term
        if ($quote->paymentTerm) {
            $parts[] = '';
            $parts[] = '*Betalingsvoorwaarden:*';
            $parts[] = '* '.$quote->paymentTerm->content;
        }

        // 3. Section clauses (Extra info, Weersvoorspelling, Klic, Wachttijd, etc.)
        foreach ($sections as $clause) {
            $parts[] = '';
            $parts[] = '*'.$clause->title.':*';
            $parts[] = $clause->content;
        }

        // 4. Free post_text
        if (! empty(trim($quote->post_text ?? ''))) {
            $parts[] = '';
            $parts[] = trim($quote->post_text);
        }

        // 5. Fixed closing text
        $parts[] = '';
        $parts[] = 'We hopen u hiermee een passende aanbieding te hebben gedaan en hopen u hiermee voldoende geïnformeerd te hebben. Mochten er nog vragen zijn over deze aanbieding, dan horen wij dat heel graag.';
        $parts[] = '';
        $parts[] = 'Met vriendelijke groet,';
        $parts[] = '';
        $parts[] = ($this->settings->company_name ?? 'Schroeffundering Holland B.V.');

        return implode("\n", $parts);
    }

    /**
     * Build the details array for a Moneybird estimate.
     * When show_item_breakdown is false, sends a single summary line instead of individual items.
     */
    protected function buildEstimateDetails(Moneybird $moneybird, Quote $quote): array
    {
        $taxRateId = $this->getTaxRateId(21.0);
        $details = [];

        // Always load fresh items from DB
        $items = $quote->items()->get();

        if (! $quote->show_item_breakdown) {
            // Calculate subtotal directly from items to avoid stale model values
            $subtotal = $items->sum(fn ($item) => $item->quantity * $item->unit_price);

            if ($quote->travel_total) {
                $subtotal += $quote->travel_total;
            }

            // Description-only lines (quantity shown, no price shown in Moneybird)
            foreach ($items as $item) {
                $detail = $moneybird->salesInvoiceDetail();
                $detail->description = $item->description;
                $detail->amount = (string) $item->quantity;
                $details[] = $detail;
            }

            // Travel costs as description line if applicable
            if ($quote->travel_distance && $quote->travel_total) {
                $detail = $moneybird->salesInvoiceDetail();
                $detail->description = "Reiskosten ({$quote->travel_distance} km)";
                $details[] = $detail;
            }

            // Single total line
            $totalDetail = $moneybird->salesInvoiceDetail();
            $totalDetail->description = "Totaal project {$quote->quote_number}";
            $totalDetail->price = (string) round($subtotal, 2);
            $totalDetail->amount = '1';
            $totalDetail->tax_rate_id = $taxRateId;
            $details[] = $totalDetail;

            return $details;
        }

        foreach ($items as $item) {
            $detail = $moneybird->salesInvoiceDetail();
            $detail->description = $item->description;
            $detail->price = (string) $item->unit_price;
            $detail->amount = (string) $item->quantity;
            $detail->tax_rate_id = $taxRateId;

            $details[] = $detail;
        }

        return $details;
    }

    /**
     * Get contact from Moneybird by ID
     */
    public function getContact(string $contactId): ?array
    {
        $moneybird = $this->getMoneybird();

        if (! $moneybird) {
            return null;
        }

        try {
            $contact = $moneybird->contact()->find($contactId);

            return $contact->attributes();
        } catch (ApiException $e) {
            return null;
        }
    }

    /**
     * Get invoice from Moneybird by ID
     */
    public function getInvoice(string $invoiceId): ?array
    {
        $moneybird = $this->getMoneybird();

        if (! $moneybird) {
            return null;
        }

        try {
            $invoice = $moneybird->salesInvoice()->find($invoiceId);

            return $invoice->attributes();
        } catch (ApiException $e) {
            return null;
        }
    }

    /**
     * Fetch all Moneybird contacts and store them in a local JSON cache file.
     * Returns the number of contacts saved.
     */
    public function syncAllContacts(): int
    {
        $moneybird = $this->getMoneybird();

        if (! $moneybird) {
            throw new \Exception('Moneybird is niet geconfigureerd. Controleer de API instellingen.');
        }

        try {
            $contacts = $moneybird->contact()->getAll();
        } catch (ApiException $e) {
            throw new \Exception('Moneybird API fout: '.$e->getMessage());
        }

        $data = [];

        foreach ($contacts as $contact) {
            $name = $contact->company_name ?: trim($contact->firstname.' '.$contact->lastname) ?: 'Onbekend';

            $data[] = [
                'id' => $contact->id,
                'name' => $name,
                'email' => $contact->email ?? '',
            ];
        }

        // Sort by name
        usort($data, fn ($a, $b) => strcasecmp($a['name'], $b['name']));

        file_put_contents(
            storage_path('app/moneybird_contacts.json'),
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        return count($data);
    }

    /**
     * Load cached Moneybird contacts from the local JSON file.
     * Returns an array of ['id' => ..., 'name' => ..., 'email' => ...]
     */
    public static function getCachedContacts(): array
    {
        $path = storage_path('app/moneybird_contacts.json');

        if (! file_exists($path)) {
            return [];
        }

        return json_decode(file_get_contents($path), true) ?? [];
    }

    /**
     * Import a single contact from Moneybird
     */
    public function importContact(string $contactId): ?Company
    {
        $moneybird = $this->getMoneybird();

        if (! $moneybird) {
            throw new \Exception('Moneybird is niet geconfigureerd. Controleer de API instellingen.');
        }

        try {
            $contact = $moneybird->contact()->find($contactId);

            // Check if company already exists
            $company = Company::where('moneybird_contact_id', $contactId)->first();

            if (! $company) {
                // Create new company
                $company = Company::create([
                    'name' => $contact->company_name ?: 'Onbekend',
                    'email' => $contact->email,
                    'phone' => $contact->phone,
                    'street' => $contact->address1,
                    'house_number' => $contact->address2,
                    'postal_code' => $contact->zipcode,
                    'city' => $contact->city,
                    'moneybird_contact_id' => $contact->id,
                ]);
            } else {
                // Update existing company
                $company->update([
                    'name' => $contact->company_name ?: $company->name,
                    'email' => $contact->email ?: $company->email,
                    'phone' => $contact->phone ?: $company->phone,
                    'street' => $contact->address1 ?: $company->street,
                    'house_number' => $contact->address2 ?: $company->house_number,
                    'postal_code' => $contact->zipcode ?: $company->postal_code,
                    'city' => $contact->city ?: $company->city,
                ]);
            }

            return $company;
        } catch (ApiException $e) {
            throw new \Exception('Moneybird API fout: '.$e->getMessage());
        }
    }
}
