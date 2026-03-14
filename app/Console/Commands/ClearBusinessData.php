<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ClearBusinessData extends Command
{
    protected $signature = 'app:clear-business-data
                            {--force : Sla bevestiging over}';

    protected $description = 'Verwijder alle testdata (projecten, offertes, leads, klanten). Instellingen en e-mail templates blijven bewaard.';

    public function handle(): int
    {
        if (! $this->option('force')) {
            $this->warn('LET OP: Dit verwijdert ALLE klanten, leads, offertes, projecten en facturen!');
            $this->warn('Instellingen, e-mail templates, betaaltermijnen, uitgangspunten en producten blijven bewaard.');

            if (! $this->confirm('Weet je het zeker?')) {
                $this->info('Geannuleerd.');
                return self::SUCCESS;
            }
        }

        $this->info('Bezig met opschonen...');

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        $tables = [
            'activity_log',
            'media',
            'stock_mutations',
            'stock_reservations',
            'invoice_items',
            'invoices',
            'project_items',
            'projects',
            'quote_assumption_quote',
            'quote_items',
            'quotes',
            'leads',
            'companies',
        ];

        foreach ($tables as $table) {
            if (DB::getSchemaBuilder()->hasTable($table)) {
                DB::table($table)->truncate();
                $this->line("  Leeg: {$table}");
            } else {
                $this->line("  Overgeslagen (bestaat niet): {$table}");
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // Reset auto-increment tellers
        $resetTables = ['companies', 'leads', 'quotes', 'projects', 'invoices', 'quote_items', 'project_items'];
        foreach ($resetTables as $table) {
            if (DB::getSchemaBuilder()->hasTable($table)) {
                DB::statement("ALTER TABLE `{$table}` AUTO_INCREMENT = 1");
            }
        }

        $this->newLine();
        $this->info('Klaar! Alle business data is verwijderd.');
        $this->info('Bewaard: settings, email_templates, payment_terms, quote_assumptions, products, machines, suppliers, users, permissions, roles');

        return self::SUCCESS;
    }
}
