<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Project;
use App\Models\Quote;
use App\Models\User;
use App\Notifications\InfoRequiredNotification;
use App\Notifications\OrderConfirmationNotification;
use App\Notifications\QuoteApprovedNotification;
use App\Notifications\QuoteSentNotification;
use Illuminate\Console\Command;

class TestEmailNotifications extends Command
{
    protected $signature = 'test:emails {type?}';

    protected $description = 'Test all email notifications';

    public function handle()
    {
        $type = $this->argument('type');
        
        if (!$type) {
            $type = $this->choice(
                'Welke email wil je testen?',
                ['all', 'quote_sent', 'quote_approved', 'info_required', 'order_confirmation'],
                0
            );
        }
        
        $testEmail = $this->ask('Naar welk email adres wil je testen?', 'test@example.com');
        
        $this->info("📧 Emails worden verstuurd naar: {$testEmail}");
        $this->newLine();
        
        match($type) {
            'all' => $this->testAll($testEmail),
            'quote_sent' => $this->testQuoteSent($testEmail),
            'quote_approved' => $this->testQuoteApproved($testEmail),
            'info_required' => $this->testInfoRequired($testEmail),
            'order_confirmation' => $this->testOrderConfirmation($testEmail),
            default => $this->error('Onbekend type')
        };
        
        return 0;
    }
    
    private function testAll(string $email): void
    {
        $this->testQuoteSent($email);
        $this->testQuoteApproved($email);
        $this->testInfoRequired($email);
        $this->testOrderConfirmation($email);
    }
    
    private function testQuoteSent(string $email): void
    {
        $this->info('🔵 Test: Quote Sent Notification (Interne notificatie)');
        
        try {
            // Create test data
            $company = Company::first() ?? Company::factory()->create();
            $quote = Quote::first() ?? Quote::factory()->create([
                'company_id' => $company->id,
                'quote_number' => 'TEST-' . now()->format('YmdHis'),
            ]);
            
            // Create test user
            $user = User::first() ?? User::factory()->create([
                'email' => $email,
                'name' => 'Test User',
            ]);
            
            // Send notification
            $user->notify(new QuoteSentNotification($quote));
            
            $this->line("   ✅ Verzonden naar: {$email}");
            $this->line("   📋 Quote: {$quote->quote_number}");
            $this->line("   💰 Bedrag: €" . number_format($quote->total, 2, ',', '.'));
            
        } catch (\Exception $e) {
            $this->error("   ❌ Fout: " . $e->getMessage());
        }
        
        $this->newLine();
    }
    
    private function testQuoteApproved(string $email): void
    {
        $this->info('🟢 Test: Quote Approved Notification (Interne notificatie)');
        
        try {
            $company = Company::first() ?? Company::factory()->create();
            $quote = Quote::first() ?? Quote::factory()->create([
                'company_id' => $company->id,
                'quote_number' => 'TEST-' . now()->format('YmdHis'),
                'approved_at' => now(),
            ]);
            
            $user = User::first() ?? User::factory()->create([
                'email' => $email,
                'name' => 'Test User',
            ]);
            
            $user->notify(new QuoteApprovedNotification($quote));
            
            $this->line("   ✅ Verzonden naar: {$email}");
            $this->line("   📋 Quote: {$quote->quote_number}");
            $this->line("   💰 Bedrag: €" . number_format($quote->total, 2, ',', '.'));
            
        } catch (\Exception $e) {
            $this->error("   ❌ Fout: " . $e->getMessage());
        }
        
        $this->newLine();
    }
    
    private function testInfoRequired(string $email): void
    {
        $this->info('🟡 Test: Info Required Notification (Email naar klant)');
        
        try {
            $lead = Lead::first();
            
            if (!$lead) {
                $company = Company::first() ?? Company::factory()->create();
                $lead = Lead::factory()->create([
                    'company_id' => $company->id,
                    'name' => 'Test Klant',
                    'email' => $email,
                ]);
            } else {
                // Temporarily override email for testing
                $originalEmail = $lead->email;
                $lead->email = $email;
            }
            
            $message = "Voor het opstellen van een offerte hebben wij de volgende informatie nodig:\n\n";
            $message .= "• Constructierapport\n";
            $message .= "• Bouwtekening / technische tekening\n";
            $message .= "• Grondinformatie / bodemrapport\n\n";
            $message .= "Dit is een test email.";
            
            $lead->notify(new InfoRequiredNotification($lead, $message));
            
            $this->line("   ✅ Verzonden naar: {$email}");
            $this->line("   👤 Lead: {$lead->name}");
            $this->line("   🏢 Bedrijf: {$lead->company_name}");
            
            // Restore original email
            if (isset($originalEmail)) {
                $lead->email = $originalEmail;
            }
            
        } catch (\Exception $e) {
            $this->error("   ❌ Fout: " . $e->getMessage());
            $this->error("   Stack trace: " . $e->getTraceAsString());
        }
        
        $this->newLine();
    }
    
    private function testOrderConfirmation(string $email): void
    {
        $this->info('🟣 Test: Order Confirmation Notification (Email naar klant)');
        
        try {
            $company = Company::first();
            
            if (!$company) {
                $company = Company::factory()->create([
                    'email' => $email,
                    'contact_name' => 'Test Klant',
                ]);
            } else {
                // Temporarily override email
                $originalEmail = $company->email;
                $company->email = $email;
            }
            
            $project = Project::first();
            
            if (!$project) {
                $quote = Quote::first() ?? Quote::factory()->create(['company_id' => $company->id]);
                $project = Project::factory()->create([
                    'company_id' => $company->id,
                    'quote_id' => $quote->id,
                    'project_number' => 'TEST-' . now()->format('YmdHis'),
                ]);
            }
            
            $company->notify(new OrderConfirmationNotification($project));
            
            $this->line("   ✅ Verzonden naar: {$email}");
            $this->line("   📋 Project: {$project->project_number}");
            $this->line("   🏢 Bedrijf: {$company->name}");
            
            // Restore original email
            if (isset($originalEmail)) {
                $company->email = $originalEmail;
            }
            
        } catch (\Exception $e) {
            $this->error("   ❌ Fout: " . $e->getMessage());
        }
        
        $this->newLine();
    }
}
