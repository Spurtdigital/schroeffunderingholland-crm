<?php

namespace App\Http\Controllers\Api;

use App\Enums\LeadStatus;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Lead;
use App\Services\PostcodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LeadWebhookController extends Controller
{
    /**
     * Handle incoming Contact Form 7 webhook
     *
     * POST /api/webhooks/cf7
     *
     * Accepts flexible field names and maps them to CRM fields.
     * All non-mapped fields are stored in request_data JSON.
     */
    public function store(Request $request)
    {
        // Log incoming request with all fields
        Log::info('CF7 Webhook received', $request->all());

        // Map flexible field names to standardized fields
        $mapped = $this->mapFields($request->all());

        // Validate only the email field (minimum requirement)
        if (empty($mapped['email'])) {
            Log::error('CF7 Webhook validation failed: email is required');

            return response()->json([
                'success' => false,
                'message' => 'E-mailadres is verplicht.',
            ], 422);
        }

        try {
            // Check if company exists by email
            $company = Company::where('email', $mapped['email'])->first();

            // Try postcode lookup if we have postal_code and house_number but missing street/city
            if ($mapped['postal_code'] && $mapped['house_number']) {
                if (! $mapped['street'] || ! $mapped['city']) {
                    $postcodeService = app(PostcodeService::class);
                    $addressData = $postcodeService->lookup($mapped['postal_code'], $mapped['house_number']);

                    if ($addressData) {
                        // Fill in missing data from postcode lookup
                        $mapped['street'] = $mapped['street'] ?: $addressData['street'];
                        $mapped['city'] = $mapped['city'] ?: $addressData['city'];

                        Log::info('Address auto-filled from postcode lookup', [
                            'postal_code' => $mapped['postal_code'],
                            'house_number' => $mapped['house_number'],
                            'street' => $addressData['street'],
                            'city' => $addressData['city'],
                        ]);
                    }
                }
            }

            // If company doesn't exist, create it
            if (! $company) {
                $company = Company::create([
                    'name' => $mapped['company_name'] ?: $mapped['contact_person'] ?: 'Onbekend bedrijf',
                    'email' => $mapped['email'],
                    'phone' => $mapped['phone'],
                    'street' => $mapped['street'],
                    'house_number' => $mapped['house_number'],
                    'postal_code' => $mapped['postal_code'],
                    'city' => $mapped['city'],
                ]);

                Log::info('New company created from webhook', ['company_id' => $company->id]);
            }

            // Build project description from all available info
            $description = $this->buildProjectDescription($mapped);

            // Create lead
            $lead = Lead::create([
                'company_id' => $company->id,
                'name' => $mapped['contact_person'] ?: $mapped['company_name'] ?: 'Onbekend',
                'email' => $mapped['email'],
                'phone' => $mapped['phone'] ?: $company->phone,
                'street' => $mapped['street'],
                'house_number' => $mapped['house_number'],
                'postal_code' => $mapped['postal_code'],
                'city' => $mapped['city'],
                'notes' => $description, // Store project description in notes
                'request_data' => $mapped['all_fields'], // Store all original fields as JSON
                'status' => LeadStatus::New,
            ]);

            Log::info('Lead created from webhook', [
                'lead_id' => $lead->id,
                'company_id' => $company->id,
                'mapped_fields' => array_keys(array_filter($mapped)),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Lead succesvol aangemaakt',
                'data' => [
                    'lead_id' => $lead->id,
                    'company_id' => $company->id,
                    'company_existed' => $company->wasRecentlyCreated === false,
                ],
            ], 201);

        } catch (\Exception $e) {
            Log::error('CF7 Webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Er is een fout opgetreden bij het verwerken van de aanvraag.',
            ], 500);
        }
    }

    /**
     * Map various CF7 field names to standardized CRM fields
     */
    private function mapFields(array $data): array
    {
        $mapped = [
            'company_name' => null,
            'contact_person' => null,
            'email' => null,
            'phone' => null,
            'street' => null,
            'house_number' => null,
            'postal_code' => null,
            'city' => null,
            'message' => null,
            'all_fields' => [], // Store ALL original fields
        ];

        // Field mapping rules (add more as needed)
        $fieldMap = [
            // Email variations
            'email' => ['email', 'emailadres', 'e-mail', 'e-mailadres', 'mail', 'your-email'],

            // Name variations
            'contact_person' => ['naam', 'name', 'contact_person', 'contactpersoon', 'voornaam', 'firstname', 'your-name'],

            // Company variations
            'company_name' => ['bedrijfsnaam', 'company_name', 'bedrijf', 'company'],

            // Phone variations
            'phone' => ['telefoon', 'phone', 'telefoonnummer', 'tel', 'telefoon_nummer', 'your-phone', 'tel-965'],

            // Street variations
            'street' => ['straatnaam', 'straat', 'street', 'your-street'],

            // House number variations
            'house_number' => ['huisnummer', 'house_number', 'nummer', 'number'],

            // Postal code variations
            'postal_code' => ['postcode', 'postal_code', 'zip', 'zipcode'],

            // City variations
            'city' => ['plaats', 'city', 'woonplaats', 'stad', 'your-city'],

            // Message/description variations
            'message' => [
                'omschrijving', 'message', 'bericht', 'aanvullend',
                'aanvullende', 'opmerking', 'opmerkingen', 'description',
                'gewicht', 'hoogte', 'startdatum', 'locatie', 'your-message',
            ],
        ];

        // Store ALL original fields for reference
        foreach ($data as $key => $value) {
            // Skip empty values and CF7 internal fields
            if (empty($value) || str_starts_with($key, '_')) {
                continue;
            }
            $mapped['all_fields'][$key] = $value;
        }

        // Map fields based on rules
        foreach ($fieldMap as $targetField => $possibleNames) {
            foreach ($possibleNames as $possibleName) {
                if (isset($data[$possibleName]) && ! empty($data[$possibleName])) {
                    if ($targetField === 'message') {
                        // Concatenate multiple message fields
                        $mapped[$targetField] = $mapped[$targetField]
                            ? $mapped[$targetField]."\n".$possibleName.': '.$data[$possibleName]
                            : $possibleName.': '.$data[$possibleName];
                    } else {
                        // Only set if not already set (first match wins)
                        if (empty($mapped[$targetField])) {
                            $mapped[$targetField] = $data[$possibleName];
                        }
                    }
                }
            }
        }

        // Split street and house number if they come combined (e.g., "Zuivelhof 6")
        if (! empty($mapped['street']) && empty($mapped['house_number'])) {
            // Try to extract house number from street field
            // Pattern: street name followed by optional space and number (with possible letter suffix)
            if (preg_match('/^(.+?)\s+(\d+[a-zA-Z]?(?:\s*-\s*\d+[a-zA-Z]?)?)$/', trim($mapped['street']), $matches)) {
                $mapped['street'] = trim($matches[1]);
                $mapped['house_number'] = trim($matches[2]);

                Log::info('Split street and house number', [
                    'original' => $data['straatnaam'] ?? $data['straat'] ?? 'unknown',
                    'street' => $mapped['street'],
                    'house_number' => $mapped['house_number'],
                ]);
            }
        }

        return $mapped;
    }

    /**
     * Build comprehensive project description from all available data
     */
    private function buildProjectDescription(array $mapped): ?string
    {
        $parts = [];

        // Add main message if exists
        if (! empty($mapped['message'])) {
            $parts[] = $mapped['message'];
        }

        // Add all extra fields that weren't mapped to standard fields
        $standardFields = ['company_name', 'contact_person', 'email', 'phone', 'address', 'postal_code', 'city', 'message'];
        foreach ($mapped['all_fields'] as $key => $value) {
            // Skip if already in message
            if (is_string($mapped['message']) && str_contains($mapped['message'], $value)) {
                continue;
            }

            // Add formatted extra info
            $parts[] = ucfirst(str_replace('_', ' ', $key)).': '.$value;
        }

        return ! empty($parts) ? implode("\n\n", $parts) : null;
    }
}
