<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class RotateUserPasswords extends Command
{
    protected $signature = 'app:rotate-user-passwords
                            {--force : Bevestig dat alle gebruikers een nieuw wachtwoord krijgen}';

    protected $description = 'Genereer nieuwe wachtwoorden voor alle gebruikers en verwijder het legacy admin@sfh.nl account.';

    public function handle(): int
    {
        if (! $this->option('force')) {
            $this->warn('Dit roteert de wachtwoorden van ALLE gebruikers. Gebruik --force om te bevestigen.');

            return self::FAILURE;
        }

        $legacy = User::where('email', 'admin@sfh.nl')->first();

        if ($legacy) {
            $legacy->delete();
            $this->warn('Legacy account verwijderd: admin@sfh.nl');
        }

        $users = User::orderBy('id')->get();

        if ($users->isEmpty()) {
            $this->warn('Geen gebruikers gevonden om te roteren.');

            return self::SUCCESS;
        }

        $rows = [];

        foreach ($users as $user) {
            $password = Str::password(24);

            $user->password = $password;
            $user->save();

            $rows[] = [$user->email, $password];
        }

        $this->newLine();
        $this->info('Nieuwe wachtwoorden (eenmalig zichtbaar, niet opgeslagen):');
        $this->table(['E-mail', 'Nieuw wachtwoord'], $rows);
        $this->warn('Deel deze wachtwoorden via een veilig kanaal en sla ze niet op in de repo of logs.');

        return self::SUCCESS;
    }
}
