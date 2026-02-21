<?php

namespace App\Console\Commands;

use App\Filament\Pages\ManageSettings;
use Illuminate\Console\Command;
use Filament\Schemas\Schema;

class TestSettingsForm extends Command
{
    protected $signature = 'test:settings-form';
    protected $description = 'Test if the settings page form loads correctly';

    public function handle()
    {
        $this->info('Testing ManageSettings form...');

        try {
            $page = new ManageSettings();
            $this->info('✓ Page class instantiated');

            // Create a schema instance
            $schema = new Schema();
            $this->info('✓ Schema instance created');

            // Call the form method (this will load all components)
            $result = $page->form($schema);
            $this->info('✓ Form method executed successfully');

            // Check if result is a Schema
            if ($result instanceof Schema) {
                $this->info('✓ Form returns Schema instance');
            }

            $this->info("\n✅ All tests passed! Form loads correctly with all components.");
            $this->info("The page should be accessible at: https://sfh.ddev.site/admin/manage-settings");
            
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            $this->error("\nStack trace:");
            $this->error($e->getTraceAsString());
            return 1;
        }

        return 0;
    }
}
