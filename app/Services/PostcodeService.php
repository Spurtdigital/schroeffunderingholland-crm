<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PostcodeService
{
    /**
     * Lookup address information by postal code and house number
     * Uses free PDOK Locatieserver API (official Dutch government API)
     * 
     * @param string $postalCode Dutch postal code (e.g. "1234AB" or "1234 AB")
     * @param string $houseNumber House number (e.g. "123" or "123A")
     * @return array|null Returns ['street' => '...', 'city' => '...'] or null on failure
     */
    public function lookup(string $postalCode, string $houseNumber): ?array
    {
        // Clean up postal code (remove spaces, convert to uppercase)
        $postalCode = strtoupper(str_replace(' ', '', $postalCode));
        
        // Validate Dutch postal code format (1234AB)
        if (!preg_match('/^[0-9]{4}[A-Z]{2}$/', $postalCode)) {
            Log::warning('Invalid postal code format', ['postal_code' => $postalCode]);
            return null;
        }

        // Clean up house number (remove extra spaces)
        $houseNumber = trim($houseNumber);
        
        if (empty($houseNumber)) {
            return null;
        }

        try {
            // Call PDOK Locatieserver (free, official Dutch government API)
            // URL format: https://api.pdok.nl/bzk/locatieserver/search/v3_1/free?q=postcode:1234AB AND huisnummer:1
            $response = Http::timeout(5)
                ->get('https://api.pdok.nl/bzk/locatieserver/search/v3_1/free', [
                    'q' => "postcode:{$postalCode} AND huisnummer:{$houseNumber}",
                ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // Check if we got valid data
                if (isset($data['response']['docs'][0])) {
                    $address = $data['response']['docs'][0];
                    
                    return [
                        'street' => $address['straatnaam'] ?? null,
                        'city' => $address['woonplaatsnaam'] ?? null,
                        'municipality' => $address['gemeentenaam'] ?? null,
                        'province' => $address['provincienaam'] ?? null,
                    ];
                }
            }

            Log::info('Postcode lookup failed', [
                'postal_code' => $postalCode,
                'house_number' => $houseNumber,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Postcode API error', [
                'postal_code' => $postalCode,
                'house_number' => $houseNumber,
                'error' => $e->getMessage(),
            ]);
            
            return null;
        }
    }

    /**
     * Validate if a postal code has the correct Dutch format
     */
    public function isValidPostalCode(string $postalCode): bool
    {
        $postalCode = strtoupper(str_replace(' ', '', $postalCode));
        return preg_match('/^[0-9]{4}[A-Z]{2}$/', $postalCode) === 1;
    }
}
