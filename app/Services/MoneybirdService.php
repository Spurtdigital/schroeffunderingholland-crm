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
                $contact->address1 = trim(($company->street ?? '') . ' ' . ($company->house_number ?? ''));
                $contact->address2 = null;
                $contact->zipcode = $company->postal_code;
                $contact->city = $company->city;
                $contact->save($contact);
            } else {
                // Create new contact
                $contact = $moneybird->contact();
                $contact->company_name = $company->name;
                $contact->email = $company->email;
                $contact->phone = $company->phone;
                $contact->address1 = trim(($company->street ?? '') . ' ' . ($company->house_number ?? ''));
                $contact->address2 = null;
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

            // Build details array — always a single line per invoice
            $details = [];
            $taxRateId = $this->getTaxRateId(21.0);

            $detail = $moneybird->salesInvoiceDetail();
            $detail->description = $subject;
            $detail->price = (string) round($invoice->subtotal, 2);
            $detail->amount = '1';
            $detail->tax_rate_id = $taxRateId;
            $details[] = $detail;

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
            $mbEstimate->pre_text = $this->buildPreText($quote);
            $mbEstimate->post_text = $this->buildPostText($quote);

            // Build details and assign
            $mbEstimate->details = $this->buildEstimateDetails($moneybird, $quote);

            $mbEstimate->save();

            // Moneybird assigns its own estimate number (e.g. "E2026-0012") after saving.
            // Use that as the canonical quote number so both systems stay in sync.
            $moneybirdEstimateNumber = $mbEstimate->estimate_id ?? null;

            // Persist the Moneybird estimate ID, the new quote number and the sent timestamp.
            $updateData = [
                'moneybird_quote_id' => $mbEstimate->id,
                'sent_at' => now(),
            ];

            if ($moneybirdEstimateNumber) {
                $updateData['quote_number'] = $moneybirdEstimateNumber;
            }

            $quote->update($updateData);

            // If the quote number changed and a project was already created from this
            // quote, keep the project number in sync (same sequence, P-prefix).
            if ($moneybirdEstimateNumber && $quote->project_id) {
                $quote->load('project');
                if ($quote->project) {
                    $newProjectNumber = \App\Models\Project::projectNumberFromQuote($moneybirdEstimateNumber);
                    if ($newProjectNumber) {
                        $quote->project->update(['project_number' => $newProjectNumber]);
                    }
                }
            }

            // Keep the reference field in Moneybird in sync with the new number.
            if ($moneybirdEstimateNumber && $mbEstimate->reference !== $moneybirdEstimateNumber) {
                $mbEstimate->reference = $moneybirdEstimateNumber;
                $mbEstimate->save();
            }

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
            try {
                $mbEstimate = $moneybird->estimate()->find($quote->moneybird_quote_id);
            } catch (ApiException $e) {
                // If the estimate no longer exists in Moneybird (404), clear the local ID
                // and fall through to create a fresh one.
                if (str_contains($e->getMessage(), '404') || str_contains($e->getMessage(), 'not_found')) {
                    $quote->update(['moneybird_quote_id' => null]);
                    $quote->refresh();

                    return $this->sendQuote($quote);
                }

                throw new \Exception('Moneybird API fout: '.$e->getMessage());
            }

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
            $mbEstimate->pre_text = $this->buildPreText($quote);
            $mbEstimate->post_text = $this->buildPostText($quote);
            $mbEstimate->save();

            return true;
        } catch (ApiException $e) {
            throw new \Exception('Moneybird API fout: '.$e->getMessage());
        }
    }

    /**
     * Build the pre_text for a Moneybird estimate:
     *  1. Aanhef         → "Beste [contact_person]," from company
     *  2. Opening zin    → from Quote::pre_text (free field, set from GeneralSettings::default_pre_text)
     *  3. Aannames       → "In deze offerte gaan wij uit van het onderstaande:" + bullet list
     */
    public function buildPreText(Quote $quote): string
    {
        $parts = [];

        // 1. Aanhef — voornaam contactpersoon, anders bedrijfsnaam
        $company = $quote->company;
        if ($company) {
            $name = $company->contact_person ?: $company->name;
            $parts[] = 'Beste '.$name.',';
            $parts[] = '';
        }

        // 2. Machine (indien gekoppeld)
        if ($quote->machine) {
            $parts[] = 'Montage machine: '.$quote->machine->name;
            $parts[] = '';
        }

        // 3. Free pre_text (opening zin, e.g. "Hierbij ontvangt u van ons...")
        $preText = trim($quote->pre_text ?? '');
        if (! empty($preText)) {
            $parts[] = $preText;
        }

        // 4. Aannames bullet list
        $assumptions = $quote->assumptions()->withPivot('pivot_value')->orderBy('sort_order')->get();
        if ($assumptions->isNotEmpty()) {
            $parts[] = '';
            $parts[] = 'In deze offerte gaan wij uit van het onderstaande:';
            foreach ($assumptions as $assumption) {
                $parts[] = '* ' . $assumption->resolveContent($assumption->pivot->pivot_value);
            }
        }

        return implode("\n", $parts);
    }

    /**
     * Build the post_text for a Moneybird estimate:
     *  1. Payment term    → "*Betalingsvoorwaarden:*" + bullet
     *  2. Free post_text  → per-quote extra text
     *  3. Closing text    → fixed blob from GeneralSettings::closing_text
     */
    public function buildPostText(Quote $quote): string
    {
        $parts = [];

        // 1. Payment term
        if ($quote->paymentTerm) {
            $parts[] = '*Betalingsvoorwaarden:*';
            $parts[] = '* '.$quote->paymentTerm->content;
        }

        // 2. Free post_text (per-quote extra notes)
        if (! empty(trim($quote->post_text ?? ''))) {
            $parts[] = '';
            $parts[] = trim($quote->post_text);
        }

        // 3. Fixed closing text from settings
        $closingText = trim($this->settings->closing_text ?? '');
        if (! empty($closingText)) {
            $parts[] = '';
            $parts[] = $this->markdownToMoneybird($closingText);
        }

        return implode("\n", $parts);
    }

    /**
     * Convert standard Markdown to Moneybird's formatting syntax.
     * Moneybird uses *text* for bold/italic (single asterisk), not **text**.
     */
    protected function markdownToMoneybird(string $text): string
    {
        // **bold** → *bold*
        $text = preg_replace('/\*\*(.+?)\*\*/s', '*$1*', $text);

        // __bold__ → *bold*
        $text = preg_replace('/__(.+?)__/s', '*$1*', $text);

        // _italic_ or *italic* (single) → keep as-is, already valid in Moneybird

        return $text;
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

            // Description-only lines (aantal wel, bedrag niet naar Moneybird)
            foreach ($items as $item) {
                $detail = $moneybird->salesInvoiceDetail();
                $detail->description = $this->itemDescription($item);
                $detail->amount = (string) $item->quantity;
                $details[] = $detail;
            }

            // Travel costs as description line if applicable
            if ($quote->travel_distance && $quote->travel_total) {
                $originAddress = trim($this->settings->company_address ?? '');
                $destAddress   = trim($quote->workFullAddress() ?? '');
                $travelDesc    = $originAddress && $destAddress
                    ? "Reiskosten: {$originAddress} → {$destAddress} en retour ({$quote->travel_distance} km × 2)"
                    : "Reiskosten ({$quote->travel_distance} km)";

                $detail = $moneybird->salesInvoiceDetail();
                $detail->description = $travelDesc;
                $detail->amount = '1';
                $details[] = $detail;
            }

            // Single total line
            $displayNumber = $quote->project?->project_number ?? $quote->quote_number;
            $totalDetail = $moneybird->salesInvoiceDetail();
            $totalDetail->description = $displayNumber;
            $totalDetail->price = (string) round($subtotal, 2);
            $totalDetail->amount = '1';
            $totalDetail->tax_rate_id = $taxRateId;
            $details[] = $totalDetail;

            return $details;
        }

        foreach ($items as $item) {
            $detail = $moneybird->salesInvoiceDetail();
            $detail->description = $this->itemDescription($item);
            $detail->price = (string) $item->unit_price;
            $detail->amount = (string) $item->quantity;
            $detail->tax_rate_id = $taxRateId;

            $details[] = $detail;
        }

        return $details;
    }

    /**
     * Build the display description for a quote item.
     * Appends pile length when set, e.g. "Schroefpaal 76mm (lengte: 3,5 m)".
     */
    protected function itemDescription(\App\Models\QuoteItem $item): string
    {
        $desc = $item->description;

        if ($item->pile_length) {
            $length = rtrim(rtrim(number_format((float) $item->pile_length, 2, ',', ''), '0'), ',');
            $desc .= ' (lengte: '.$length.' m)';
        }

        return $desc;
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
