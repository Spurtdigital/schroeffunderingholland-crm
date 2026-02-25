<?php

namespace App\Services;

use App\Models\Quote;
use App\Settings\GeneralSettings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DistanceCalculatorService
{
    protected GeneralSettings $settings;

    public function __construct(GeneralSettings $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Calculate distance from company location to destination address.
     *
     * @param string $destinationAddress Full address (street, postal code, city)
     * @return array ['success' => bool, 'distance_km' => int|null, 'distance_text' => string|null, 'duration_text' => string|null, 'error' => string|null]
     */
    public function calculateDistance(string $destinationAddress): array
    {
        // Check if API key is configured
        if (!$this->settings->google_maps_api_key) {
            Log::warning('Google Maps API key not configured');
            return [
                'success' => false,
                'error' => 'Google Maps API key niet geconfigureerd',
                'distance_km' => null,
                'distance_text' => null,
                'duration_text' => null,
            ];
        }

        // Build origin address from company settings
        $origin = $this->getCompanyAddress();
        if (!$origin) {
            Log::warning('Company address not configured in settings');
            return [
                'success' => false,
                'error' => 'Bedrijfsadres niet volledig ingevuld in instellingen',
                'distance_km' => null,
                'distance_text' => null,
                'duration_text' => null,
            ];
        }

        try {
            $response = Http::get('https://maps.googleapis.com/maps/api/distancematrix/json', [
                'origins' => $origin,
                'destinations' => $destinationAddress,
                'key' => $this->settings->google_maps_api_key,
                'units' => 'metric',
                'language' => 'nl',
            ]);

            if (!$response->successful()) {
                Log::error('Google Maps API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return [
                    'success' => false,
                    'error' => 'Google Maps API verzoek mislukt (HTTP ' . $response->status() . ')',
                    'distance_km' => null,
                    'distance_text' => null,
                    'duration_text' => null,
                ];
            }

            $data = $response->json();

            // Check API response status
            if ($data['status'] !== 'OK') {
                $errorMessage = $data['error_message'] ?? 'Onbekende fout';
                
                // Provide user-friendly error messages
                if ($data['status'] === 'REQUEST_DENIED') {
                    $errorMessage = 'Google Maps API toegang geweigerd. Controleer of Distance Matrix API is ingeschakeld in Google Cloud Console.';
                } elseif ($data['status'] === 'INVALID_REQUEST') {
                    $errorMessage = 'Ongeldig verzoek. Controleer of de adressen correct zijn.';
                }
                
                Log::error('Google Maps API returned error', [
                    'status' => $data['status'],
                    'error_message' => $data['error_message'] ?? 'Unknown error',
                ]);
                
                return [
                    'success' => false,
                    'error' => $errorMessage,
                    'distance_km' => null,
                    'distance_text' => null,
                    'duration_text' => null,
                ];
            }

            // Check if we have results
            if (empty($data['rows'][0]['elements'][0])) {
                Log::warning('No distance results from Google Maps API');
                return [
                    'success' => false,
                    'error' => 'Geen resultaten van Google Maps',
                    'distance_km' => null,
                    'distance_text' => null,
                    'duration_text' => null,
                ];
            }

            $element = $data['rows'][0]['elements'][0];

            // Check element status
            if ($element['status'] !== 'OK') {
                Log::warning('Google Maps element status not OK', [
                    'status' => $element['status'],
                    'origin' => $origin,
                    'destination' => $destinationAddress,
                ]);
                return [
                    'success' => false,
                    'error' => 'Kan route niet berekenen. Controleer of de adressen geldig zijn.',
                    'distance_km' => null,
                    'distance_text' => null,
                    'duration_text' => null,
                ];
            }

            // Extract distance and duration
            $distanceMeters = $element['distance']['value'];
            $distanceKm = (int) ceil($distanceMeters / 1000); // Round up to nearest km
            $distanceText = $element['distance']['text'];
            $durationText = $element['duration']['text'];

            Log::info('Distance calculated successfully', [
                'origin' => $origin,
                'destination' => $destinationAddress,
                'distance_km' => $distanceKm,
                'distance_text' => $distanceText,
                'duration_text' => $durationText,
            ]);

            return [
                'success' => true,
                'distance_km' => $distanceKm,
                'distance_text' => $distanceText,
                'duration_text' => $durationText,
                'error' => null,
            ];
        } catch (\Exception $e) {
            Log::error('Exception calculating distance', [
                'message' => $e->getMessage(),
                'origin' => $origin,
                'destination' => $destinationAddress,
            ]);
            return [
                'success' => false,
                'error' => 'Fout bij berekenen afstand: ' . $e->getMessage(),
                'distance_km' => null,
                'distance_text' => null,
                'duration_text' => null,
            ];
        }
    }

    /**
     * Build company address string from settings.
     */
    protected function getCompanyAddress(): ?string
    {
        $parts = array_filter([
            $this->settings->company_address,
            $this->settings->company_postal_code,
            $this->settings->company_city,
        ]);

        return !empty($parts) ? implode(', ', $parts) : null;
    }

    /**
     * Build address string from company model.
     */
    public function buildAddressFromCompany($company): ?string
    {
        $parts = array_filter([
            trim(($company->street ?? '') . ' ' . ($company->house_number ?? '')),
            $company->postal_code,
            $company->city,
        ]);

        return !empty($parts) ? implode(', ', $parts) : null;
    }

    /**
     * Build address string from a quote (uses work address if different, else company address).
     */
    public function buildAddressFromQuote(Quote $quote): ?string
    {
        $address = $quote->workFullAddress();

        return !empty($address) ? $address : null;
    }

    /**
     * Check if distance calculation is available (API key configured).
     */
    public function isAvailable(): bool
    {
        return !empty($this->settings->google_maps_api_key) && !empty($this->getCompanyAddress());
    }
}
