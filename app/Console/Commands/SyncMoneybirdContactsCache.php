<?php

namespace App\Console\Commands;

use App\Services\MoneybirdService;
use Illuminate\Console\Command;

class SyncMoneybirdContactsCache extends Command
{
    protected $signature = 'sync:moneybird-contacts-cache';

    protected $description = 'Haal alle contacten op uit Moneybird en sla ze op in de lokale cache (JSON-bestand)';

    public function handle(MoneybirdService $moneybirdService): int
    {
        if (! $moneybirdService->isConfigured()) {
            $this->warn('Moneybird is niet geconfigureerd. Cache-sync overgeslagen.');

            return 0;
        }

        try {
            $count = $moneybirdService->syncAllContacts();
            $this->info("Moneybird contacten-cache bijgewerkt: {$count} contacten opgeslagen.");

            return 0;
        } catch (\Exception $e) {
            $this->error('Fout bij synchroniseren van Moneybird contacten: '.$e->getMessage());

            return 1;
        }
    }
}
