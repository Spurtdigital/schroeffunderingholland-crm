<?php

namespace App\Console\Commands;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class CreateProductionUsers extends Command
{
    protected $signature = 'app:create-production-users
                            {--force : Overschrijf bestaande gebruikers}';

    protected $description = 'Maak de twee productie-gebruikers aan met super_admin rol.';

    public function handle(): int
    {
        $users = [
            [
                'name'     => 'Schroeffundering Holland',
                'email'    => 'info@schroeffunderingholland.nl',
                'password' => '20CRM26!',
            ],
            [
                'name'     => 'Spurt Digital',
                'email'    => 'info@spurt.digital',
                'password' => '95Kevin03!',
            ],
            [
                'name'     => 'Kevin (Spurt Digital)',
                'email'    => 'kevin@spurt.digital',
                'password' => '95Kevin03!',
            ],
        ];

        $role = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

        foreach ($users as $data) {
            $existing = User::where('email', $data['email'])->first();

            if ($existing && ! $this->option('force')) {
                $this->warn("Gebruiker bestaat al: {$data['email']} (gebruik --force om te overschrijven)");
                continue;
            }

            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name'              => $data['name'],
                    'password'          => Hash::make($data['password']),
                    'email_verified_at' => Carbon::now(),
                ]
            );

            $user->syncRoles([$role]);

            $this->info("Aangemaakt: {$data['email']} (super_admin)");
        }

        $this->newLine();
        $this->info('Klaar!');

        return self::SUCCESS;
    }
}
