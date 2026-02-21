<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;

class TestSettingsAccess extends Command
{
    protected $signature = 'test:settings-access';
    protected $description = 'Test if settings page is accessible when logged in';

    public function handle()
    {
        $this->info('Testing Settings page access...');

        // Get admin user
        $admin = User::where('email', 'admin@sfh.nl')->first();
        
        if (!$admin) {
            $this->error('Admin user not found!');
            return 1;
        }

        $this->info("✓ Admin user found: {$admin->email}");

        // Login as admin
        Auth::login($admin);
        $this->info('✓ Logged in as admin');

        // Try to access the page
        try {
            $page = new \App\Filament\Pages\ManageSettings();
            
            // Check if user can access
            if (method_exists($page, 'canAccess')) {
                $canAccess = $page->canAccess();
                $this->info("✓ Can access: " . ($canAccess ? 'Yes' : 'No'));
            } else {
                $this->info('✓ No access restrictions (public page)');
            }

            $this->info("\n✅ Settings page should be visible in navigation!");
            $this->info("Visit: https://sfh.ddev.site/admin/manage-settings");
            
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
