<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class CreateProductionUsers extends Command
{
    protected $signature = 'app:create-production-users
                            {--force : Wijs de super_admin-rol opnieuw toe aan bestaande gebruikers (wachtwoorden worden nooit gereset)}';

    protected $description = 'Maak productiegebruikers aan op basis van PRODUCTION_USER_* uit de .env. Bestaande wachtwoorden worden nooit overschreven.';

    public function handle(): int
    {
        $users = collect(config('production_users.users', []))
            ->filter(fn (array $user): bool => filled($user['email'] ?? null));

        if ($users->isEmpty()) {
            $this->warn('Geen PRODUCTION_USER_* gevonden in de .env. Er zijn geen gebruikers aangemaakt.');
            $this->line('Zet bijvoorbeeld PRODUCTION_USER_1_NAME, PRODUCTION_USER_1_EMAIL en PRODUCTION_USER_1_PASSWORD (min. 12 tekens).');

            return self::SUCCESS;
        }

        $role = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

        foreach ($users as $data) {
            $email = (string) $data['email'];
            $existing = User::where('email', $email)->first();

            if ($existing) {
                if ($this->option('force')) {
                    $existing->syncRoles([$role]);
                }

                $existing->forceFill([
                    'is_approved' => true,
                    'approved_at' => $existing->approved_at ?? now(),
                ])->save();

                $this->warn("Bestaat al, wachtwoord ongewijzigd: {$email}");

                continue;
            }

            $password = (string) ($data['password'] ?? '');

            if (strlen($password) < 12) {
                $this->error("Overgeslagen: {$email} heeft geen wachtwoord van minimaal 12 tekens (PRODUCTION_USER_*_PASSWORD).");

                continue;
            }

            $user = new User([
                'name' => $data['name'] ?: $email,
                'email' => $email,
                'password' => Hash::make($password),
            ]);
            $user->forceFill([
                'email_verified_at' => now(),
                'is_approved' => true,
                'approved_at' => now(),
            ])->save();
            $user->syncRoles([$role]);

            $this->info("Aangemaakt: {$email} (super_admin)");
        }

        $this->newLine();
        $this->info('Klaar!');

        return self::SUCCESS;
    }
}
