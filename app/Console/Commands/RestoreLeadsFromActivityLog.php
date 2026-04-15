<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RestoreLeadsFromActivityLog extends Command
{
    protected $signature = 'app:restore-leads
                            {--dry-run : Toon wat er hersteld zou worden, schrijf niets weg}
                            {--force : Sla bevestiging over}';

    protected $description = 'Herstel verwijderde leads vanuit de activity_log.';

    public function handle(): int
    {
        $this->info('==> Leads herstellen vanuit activity_log...');
        $this->newLine();

        // Haal alle lead-IDs op die in de activity_log voorkomen
        $allLeadIds = DB::table('activity_log')
            ->where('subject_type', 'like', '%Lead%')
            ->distinct()
            ->pluck('subject_id')
            ->sort()
            ->values();

        // Welke leads bestaan al in de database?
        $existingIds = DB::table('leads')->pluck('id');

        // Alleen de echt verdwenen leads herstellen
        $missingIds = $allLeadIds->diff($existingIds)->values();

        $this->line("Leads in activity_log:  {$allLeadIds->count()}");
        $this->line("Bestaande leads in DB:  {$existingIds->count()}");
        $this->line("Te herstellen leads:    {$missingIds->count()}");
        $this->newLine();

        if ($missingIds->isEmpty()) {
            $this->info('Geen leads te herstellen — alles is al aanwezig.');
            return self::SUCCESS;
        }

        // Fallback company voor leads waarvan de company_id onbekend is
        $fallbackCompanyId = DB::table('companies')->orderBy('id')->value('id');
        if (! $fallbackCompanyId) {
            $this->error('Geen enkele company gevonden in de database. Kan leads niet herstellen.');
            return self::FAILURE;
        }

        // Bouw per lead de beste beschikbare data op
        $toRestore = [];

        foreach ($missingIds as $leadId) {
            $entries = DB::table('activity_log')
                ->where('subject_type', 'like', '%Lead%')
                ->where('subject_id', $leadId)
                ->orderBy('id')
                ->get(['id', 'description', 'properties', 'created_at']);

            // Startpunt: lege attributen
            $attrs = [];
            $createdAt = null;
            $updatedAt = null;

            foreach ($entries as $entry) {
                $props = json_decode($entry->properties, true) ?? [];

                // Sla created_at op van de vroegste entry
                if ($createdAt === null) {
                    $createdAt = $entry->created_at;
                }
                $updatedAt = $entry->created_at;

                if ($entry->description === 'created') {
                    // created.attributes bevat de volledige initiële staat
                    $attrs = array_merge($attrs, $props['attributes'] ?? []);
                }

                if ($entry->description === 'updated') {
                    // Samenvoegen: meest recente waarden winnen
                    $attrs = array_merge($attrs, $props['attributes'] ?? []);
                }

                if ($entry->description === 'deleted') {
                    // deleted.old is de meest complete snapshot
                    $attrs = array_merge($attrs, $props['old'] ?? []);
                }
            }

            // Zorg dat verplichte velden aanwezig zijn
            $lead = [
                'id'                    => $leadId,
                'company_id'            => $attrs['company_id'] ?? $fallbackCompanyId,
                'converted_to_quote_id' => $attrs['converted_to_quote_id'] ?? null,
                'name'                  => $attrs['name'] ?? '(hersteld — naam onbekend)',
                'email'                 => $attrs['email'] ?? null,
                'phone'                 => $attrs['phone'] ?? null,
                'postal_code'           => $attrs['postal_code'] ?? null,
                'house_number'          => $attrs['house_number'] ?? null,
                'city'                  => $attrs['city'] ?? null,
                'street'                => $attrs['street'] ?? null,
                'request_data'          => isset($attrs['request_data'])
                    ? (is_string($attrs['request_data']) ? $attrs['request_data'] : json_encode($attrs['request_data']))
                    : null,
                'status'                => $attrs['status'] ?? 'new',
                'notes'                 => $attrs['notes'] ?? null,
                'internal_notes'        => $attrs['internal_notes'] ?? null,
                'created_at'            => $createdAt,
                'updated_at'            => $updatedAt,
            ];

            $toRestore[] = $lead;
        }

        // Toon preview
        $this->table(
            ['ID', 'Naam', 'Email', 'Status', 'Company ID', 'Aangemaakt'],
            collect($toRestore)->map(fn ($l) => [
                $l['id'],
                $l['name'],
                $l['email'] ?? '-',
                $l['status'],
                $l['company_id'],
                $l['created_at'],
            ])->toArray()
        );

        $this->newLine();

        // Hoeveel leads hebben een placeholder naam?
        $placeholderCount = collect($toRestore)
            ->where('name', '(hersteld — naam onbekend)')
            ->count();

        if ($placeholderCount > 0) {
            $this->warn("{$placeholderCount} leads hebben onvoldoende data in de activity_log en krijgen een placeholder naam.");
            $this->warn("Deze leads zijn gekoppeld aan company_id {$fallbackCompanyId} (fallback).");
        }

        if ($this->option('dry-run')) {
            $this->newLine();
            $this->info('[dry-run] Niets weggeschreven.');
            return self::SUCCESS;
        }

        if (! $this->option('force')) {
            if (! $this->confirm("Wil je {$missingIds->count()} leads terugzetten?")) {
                $this->info('Geannuleerd.');
                return self::SUCCESS;
            }
        }

        // Terugzetten — AUTO_INCREMENT bewust tijdelijk omzeilen via directe INSERT met ID
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        $restored = 0;
        $skipped  = 0;

        foreach ($toRestore as $lead) {
            try {
                DB::table('leads')->insert($lead);
                $restored++;
            } catch (\Exception $e) {
                $this->warn("Lead {$lead['id']} overgeslagen: {$e->getMessage()}");
                $skipped++;
            }
        }

        // Herstel AUTO_INCREMENT naar juiste waarde
        $maxId = DB::table('leads')->max('id') ?? 0;
        DB::statement("ALTER TABLE `leads` AUTO_INCREMENT = " . ($maxId + 1));

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->newLine();
        $this->info("Hersteld: {$restored} leads.");
        if ($skipped > 0) {
            $this->warn("Overgeslagen: {$skipped} leads (zie waarschuwingen hierboven).");
        }

        return self::SUCCESS;
    }
}
