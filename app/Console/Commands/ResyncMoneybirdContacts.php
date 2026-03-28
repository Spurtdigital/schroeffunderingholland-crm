<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\MoneybirdService;
use Illuminate\Console\Command;

class ResyncMoneybirdContacts extends Command
{
    protected $signature = 'sync:moneybird-contacts {--dry-run : Toon wat er gesynchroniseerd zou worden zonder wijzigingen}';

    protected $description = 'Synchroniseer alle bedrijven met een Moneybird contact opnieuw (fix adres-formaat)';

    public function handle(MoneybirdService $moneybirdService): int
    {
        if (! $moneybirdService->isConfigured()) {
            $this->error('Moneybird is niet geconfigureerd!');

            return 1;
        }

        $companies = Company::whereNotNull('moneybird_contact_id')->get();

        if ($companies->isEmpty()) {
            $this->warn('Geen bedrijven gevonden met een Moneybird contact.');

            return 0;
        }

        $dryRun = $this->option('dry-run');
        $label = $dryRun ? '[DRY-RUN] ' : '';

        $this->info("{$label}Synchroniseren van {$companies->count()} bedrijf/bedrijven naar Moneybird...");
        $this->newLine();

        $synced = 0;
        $errors = 0;

        foreach ($companies as $company) {
            $address = trim(($company->street ?? '').' '.($company->house_number ?? ''));
            $this->line("{$label}{$company->name} → address1: \"{$address}\"");

            if ($dryRun) {
                $synced++;

                continue;
            }

            try {
                $moneybirdService->syncCompany($company);
                $this->info("  ✓ Gesynchroniseerd");
                $synced++;
            } catch (\Exception $e) {
                $this->error("  ✗ Fout: {$e->getMessage()}");
                $errors++;
            }
        }

        $this->newLine();
        $this->info("{$label}Klaar! {$synced} bedrijf/bedrijven gesynchroniseerd, {$errors} fout(en).");

        return $errors > 0 ? 1 : 0;
    }
}
