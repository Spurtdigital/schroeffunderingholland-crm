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
            // Check if contact already exists locally
            if ($company->moneybird_contact_id) {
                // Update existing contact
                $contact = $moneybird->contact()->find($company->moneybird_contact_id);
                $contact->company_name = $company->name;
                $contact->email = $company->email;
                $contact->phone = $company->phone;
                $contact->address1 = $company->street ?? '';
                $contact->address2 = $company->house_number ?? '';
                $contact->zipcode = $company->postal_code;
                $contact->city = $company->city;
                $contact->save($contact);
            } else {
                // Search Moneybird first to avoid creating duplicates
                $existingContactId = $this->findMoneybirdContactId($moneybird, $company);

                if ($existingContactId) {
                    // Link to the existing Moneybird contact without overwriting their data
                    $company->update(['moneybird_contact_id' => $existingContactId]);
                } else {
                    // No match found — create a new contact
                    $contact = $moneybird->contact();
                    $contact->company_name = $company->name;
                    $contact->email = $company->email;
                    $contact->phone = $company->phone;
                    $contact->address1 = $company->street ?? '';
                    $contact->address2 = $company->house_number ?? '';
                    $contact->zipcode = $company->postal_code;
                    $contact->city = $company->city;
                    $contact->save();

                    // Store Moneybird contact ID
                    $company->update([
                        'moneybird_contact_id' => $contact->id,
                    ]);
                }
            }

            return true;
        } catch (ApiException $e) {
            $message = $e->getMessage();

            // Detect email domain validation error and give a clear actionable message
            if (str_contains($message, 'email_domain_unreachable') || str_contains($message, 'send_invoices_to_email')) {
                throw new \Exception(
                    "Moneybird weigert het e-mailadres van \"{$company->name}\" ({$company->email}): het domein kan geen e-mail ontvangen. "
                    . "Pas het e-mailadres van dit bedrijf aan in de CRM en probeer opnieuw."
                );
            }

            throw new \Exception('Moneybird API fout: '.$message);
        }
    }

    /**
     * Search Moneybird for an existing contact matching the given company.
     * Searches by company name, then optionally by e-mail.
     * Returns the Moneybird contact ID string when a match is found, null otherwise.
     */
    protected function findMoneybirdContactId(Moneybird $moneybird, Company $company): ?string
    {
        try {
            // Search by company name
            if (! empty($company->name)) {
                $results = $moneybird->contact()->search($company->name);

                foreach ($results as $contact) {
                    $mbName = $contact->company_name
                        ?? trim(($contact->firstname ?? '').' '.($contact->lastname ?? ''));

                    if (strcasecmp(trim($mbName), trim($company->name)) === 0) {
                        return (string) $contact->id;
                    }
                }
            }

            // Fallback: search by e-mail
            if (! empty($company->email)) {
                $results = $moneybird->contact()->search($company->email);

                foreach ($results as $contact) {
                    if (! empty($contact->email) && strtolower($contact->email) === strtolower($company->email)) {
                        return (string) $contact->id;
                    }
                }
            }
        } catch (ApiException $e) {
            // If search fails (e.g. network issue) we fall through and create a new contact
        }

        return null;
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
            $mbInvoice->invoice_date = $invoice->invoice_date->format('Y-m-d');

            // Build the quote/order reference for kenmerk
            $quoteNumber = $invoice->project->quote?->displayNumber();
            $orderRef = $quoteNumber ?? $invoice->project->project_number;

            // Set reference (kenmerk) based on invoice type
            $mbInvoice->reference = match ($invoice->invoice_type) {
                \App\Enums\InvoiceType::Deposit => "Aanbetaling order {$orderRef}",
                \App\Enums\InvoiceType::Final => "Order {$orderRef} | eindfactuur",
                \App\Enums\InvoiceType::Single => "Order {$orderRef}",
            };

            // Set subject based on invoice type
            $subject = match ($invoice->invoice_type) {
                \App\Enums\InvoiceType::Deposit => "Aanbetaling offerte {$orderRef}",
                \App\Enums\InvoiceType::Final => "Eindfactuur order {$orderRef}",
                \App\Enums\InvoiceType::Single => "Factuur order {$orderRef}",
            };

            // Add percentage to subject if applicable
            if ($invoice->deposit_percentage && $invoice->deposit_percentage != 100) {
                $subject .= " — {$invoice->deposit_percentage}% van totaalbedrag excl. BTW";
            }

            $mbInvoice->subject = $subject;

            // Build details array
            $details = [];
            $taxRateId = $this->getTaxRateId(21.0);

            if ($invoice->invoice_type === \App\Enums\InvoiceType::Deposit) {
                // Aanbetaling: één samenvattingsregel
                $detail = $moneybird->salesInvoiceDetail();
                $detail->description = $subject;
                $detail->price = (string) round($invoice->subtotal, 2);
                $detail->amount = '1';
                $detail->tax_rate_id = $taxRateId;
                $details[] = $detail;
            } else {
                // Opleverings- of enkelvoudige factuur: items met unit_price als priced rows,
                // de rest als omschrijvingsregels. Één totaalregel voor het resterende bedrag.
                $items = $invoice->project->items()->orderBy('sort_order')->get();

                $pricedSubtotal = 0.0;

                foreach ($items as $item) {
                    $detail = $moneybird->salesInvoiceDetail();
                    $detail->description = $this->projectItemDescription($item);
                    $detail->amount = $this->formatQuantity($item->quantity).' x';

                    if ((float) $item->unit_price > 0) {
                        $detail->price = (string) round((float) $item->unit_price, 2);
                        $detail->tax_rate_id = $taxRateId;
                        $pricedSubtotal += round((float) $item->unit_price * (float) $item->quantity, 2);
                    }

                    $details[] = $detail;
                }

                // Totaalregel voor het resterende bedrag (alles zonder stuksprijs)
                $remainingSubtotal = round((float) $invoice->subtotal - $pricedSubtotal, 2);
                if ($remainingSubtotal > 0) {
                    $totalDetail = $moneybird->salesInvoiceDetail();
                    $totalDetail->description = $subject;
                    $totalDetail->price = (string) $remainingSubtotal;
                    $totalDetail->amount = '1';
                    $totalDetail->tax_rate_id = $taxRateId;
                    $details[] = $totalDetail;
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
            $mbEstimate->estimate_date = $quote->created_at->format('Y-m-d');
            $mbEstimate->pre_text = $this->buildPreText($quote);
            $mbEstimate->post_text = $this->buildPostText($quote);

            // Kenmerk (reference)
            if (! empty($quote->reference)) {
                $mbEstimate->reference = $quote->reference;
            }

            // Build details and assign
            $mbEstimate->details = $this->buildEstimateDetails($moneybird, $quote);

            $mbEstimate->save();

            // Moneybird assigns its own estimate number (e.g. "E2026-0012") after saving.
            // Use that as the canonical quote number so both systems stay in sync.
            $moneybirdEstimateNumber = $mbEstimate->estimate_id ?? null;

            if (! $moneybirdEstimateNumber) {
                \Illuminate\Support\Facades\Log::warning('Moneybird did not return an estimate_id after saving estimate', [
                    'quote_id' => $quote->id,
                    'moneybird_id' => $mbEstimate->id,
                    'quote_number' => $quote->quote_number,
                ]);
            }

            // Persist the Moneybird estimate ID, the new quote number and the sent timestamp.
            // Always require a valid Moneybird estimate number to replace the CONCEPT number.
            if (! $mbEstimate->id) {
                throw new \Exception('Moneybird heeft geen ID teruggegeven na het opslaan van de offerte. Probeer het opnieuw.');
            }

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
            if (! empty($quote->reference)) {
                $mbEstimate->reference = $quote->reference;
            }
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

        // 3. Free pre_text (opening zin, e.g. "Hierbij ontvangt u van ons...")
        $preText = trim($quote->pre_text ?? '');
        if (! empty($preText)) {
            $parts[] = $preText;
        }

        // 4. Aannames bullet list — grouped by category
        $assumptions = $quote->assumptions()->withPivot('pivot_value')->orderBy('sort_order')->get();
        if ($assumptions->isNotEmpty()) {
            $parts[] = '';
            $parts[] = 'In deze offerte gaan wij uit van het onderstaande:';

            $grouped = $assumptions->groupBy(fn ($a) => $a->category ?? '');

            $isFirst = true;
            foreach ($grouped as $category => $items) {
                if (! $isFirst) {
                    $parts[] = '';
                }
                $isFirst = false;
                foreach ($items as $assumption) {
                    $parts[] = '* '.$assumption->resolveContent($assumption->pivot->pivot_value);
                }
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

            // Description-only lines — skip Adjustment items (included in subtotal, not shown separately)
            foreach ($items as $item) {
                if ($item->type === \App\Enums\QuoteItemType::Adjustment) {
                    continue;
                }
                $detail = $moneybird->salesInvoiceDetail();
                $detail->description = $this->itemDescription($item);
                $detail->amount = $this->formatQuantity($item->quantity).' x';
                $details[] = $detail;
            }

            // Travel costs as description line if applicable
            if ($quote->travel_distance && $quote->travel_total) {
                $originAddress = trim($this->settings->company_address ?? '');
                $destAddress = trim(str_replace("\n", ', ', $quote->workFullAddress() ?? ''));
                $travelDesc = $originAddress && $destAddress
                    ? "Reiskosten: {$originAddress} → {$destAddress} en retour ({$quote->travel_distance} km × 2)"
                    : "Reiskosten ({$quote->travel_distance} km)";

                $detail = $moneybird->salesInvoiceDetail();
                $detail->description = $travelDesc;
                $detail->amount = '1';
                $details[] = $detail;
            }

            // Single total line
            $totalDetail = $moneybird->salesInvoiceDetail();
            $totalDetail->description = 'Totaalprijs excl. BTW';
            $totalDetail->price = (string) round($subtotal, 2);
            $totalDetail->tax_rate_id = $taxRateId;
            $details[] = $totalDetail;

            return $details;
        }

        foreach ($items as $item) {
            // Skip adjustment items — they are included in the total, not as separate lines
            if ($item->type === \App\Enums\QuoteItemType::Adjustment) {
                continue;
            }
            $detail = $moneybird->salesInvoiceDetail();
            $detail->description = $this->itemDescription($item);
            $detail->price = (string) $item->unit_price;
            $detail->amount = $this->formatQuantity($item->quantity).' x';
            $detail->tax_rate_id = $taxRateId;

            $details[] = $detail;
        }

        return $details;
    }

    /**
     * Build the display description for a project item.
     */
    protected function projectItemDescription(\App\Models\ProjectItem $item): string
    {
        $parts = [];

        // Prepend SKU for product items
        if ($item->itemable instanceof \App\Models\Product && $item->itemable->sku) {
            $parts[] = $item->itemable->sku;
        }

        $parts[] = $item->description;

        return implode("\n", $parts);
    }

    /**
     * Build the display description for a quote item.
     * Prepends SKU for product items, appends pile length when set.
     * e.g. "Artikelnummer: SKU1234 Schroefpaal 76mm (lengte: 3,5 m)".
     */
    protected function itemDescription(\App\Models\QuoteItem $item): string
    {
        $parts = [];

        // Prepend SKU for product items
        if ($item->isProduct() && $item->itemable?->sku) {
            $parts[] = $item->itemable->sku;
        }

        $parts[] = $item->description;

        $desc = implode("\n", $parts);

        if ($item->pile_length) {
            $length = rtrim(rtrim(number_format((float) $item->pile_length, 2, ',', ''), '0'), ',');
            $desc .= ' (lengte: '.$length.' m)';
        }

        return $desc;
    }

    /**
     * Format a quantity value: show whole numbers without decimals (12.00 → 12),
     * keep decimals when meaningful (3.50 → 3.5).
     */
    protected function formatQuantity(float|string $quantity): string
    {
        $qty = (float) $quantity;

        if ($qty == (int) $qty) {
            return (string) (int) $qty;
        }

        return rtrim(rtrim(number_format($qty, 4, '.', ''), '0'), '.');
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

            // Parse street and house number from Moneybird address1
            // Moneybird stores full address in address1 (e.g. "Dorpstraat 42a")
            [$parsedStreet, $parsedHouseNumber] = self::parseAddress($contact->address1);

            if (! $company) {
                // Create new company
                $company = Company::create([
                    'name' => $contact->company_name ?: 'Onbekend',
                    'email' => $contact->email,
                    'phone' => $contact->phone,
                    'street' => $parsedStreet,
                    'house_number' => $parsedHouseNumber,
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
                    'street' => $parsedStreet ?: $company->street,
                    'house_number' => $parsedHouseNumber ?: $company->house_number,
                    'postal_code' => $contact->zipcode ?: $company->postal_code,
                    'city' => $contact->city ?: $company->city,
                ]);
            }

            return $company;
        } catch (ApiException $e) {
            throw new \Exception('Moneybird API fout: '.$e->getMessage());
        }
    }

    /**
     * Parse a Dutch address string into street and house number.
     * Examples: "Dorpstraat 42" -> ["Dorpstraat", "42"]
     *           "Dorpstraat 42a" -> ["Dorpstraat", "42a"]
     *           "Lange Voorhout 12-14" -> ["Lange Voorhout", "12-14"]
     *           "Dorpstraat" -> ["Dorpstraat", null]
     *
     * @return array{0: string|null, 1: string|null}
     */
    public static function parseAddress(?string $address): array
    {
        if (blank($address)) {
            return [null, null];
        }

        $address = trim($address);

        // Match: everything up to the last space before a number, then the number (with optional suffix)
        // e.g. "Dorpstraat 42a", "Lange Voorhout 12-14", "Kerkweg 3 bis"
        if (preg_match('/^(.+?)\s+(\d[\w\-\s]*)$/u', $address, $matches)) {
            return [trim($matches[1]), trim($matches[2])];
        }

        // No house number found — return full string as street
        return [$address, null];
    }
}
