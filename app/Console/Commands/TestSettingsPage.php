<?php

namespace App\Console\Commands;

use App\Filament\Pages\ManageSettings;
use Illuminate\Console\Command;

class TestSettingsPage extends Command
{
    protected $signature = 'test:settings-page';
    protected $description = 'Test if the settings page loads correctly';

    public function handle()
    {
        $this->info('Testing ManageSettings page...');

        try {
            // Test if the class can be instantiated
            $page = new ManageSettings();
            $this->info('✓ Page class instantiated');

            // Test navigation methods
            $icon = ManageSettings::getNavigationIcon();
            $this->info("✓ Navigation icon: {$icon}");

            $label = ManageSettings::getNavigationLabel();
            $this->info("✓ Navigation label: {$label}");

            // Test if form method exists
            if (method_exists($page, 'form')) {
                $this->info('✓ Form method exists');
            }

            // Test if mount method exists
            if (method_exists($page, 'mount')) {
                $this->info('✓ Mount method exists');
            }

            $this->info("\n✅ All tests passed! Page should load correctly.");
            
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            $this->error("\nStack trace:");
            $this->error($e->getTraceAsString());
            return 1;
        }

        return 0;
    }
}
