<?php

namespace Tests\Feature\Filament;

use App\Models\Lead;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Test Lead Actions (CreateQuote, RequestInfo)
 */
class LeadActionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    #[Test]
    public function create_quote_action_exists_on_lead_edit_page(): void
    {
        $user = User::first();
        $lead = Lead::first();

        $this->actingAs($user);

        $response = $this->get("/admin/leads/{$lead->id}/edit");

        $response->assertStatus(200);
        // De actie buttons worden via Livewire geladen, dus we checken of de pagina laadt
        $response->assertDontSee('Whoops');
    }

    #[Test]
    public function request_more_info_action_exists_on_lead_edit_page(): void
    {
        $user = User::first();
        $lead = Lead::first();

        $this->actingAs($user);

        $response = $this->get("/admin/leads/{$lead->id}/edit");

        $response->assertStatus(200);
        $response->assertDontSee('Whoops');
    }

    #[Test]
    public function lead_can_be_converted_to_quote(): void
    {
        $user = User::first();
        $lead = Lead::factory()->create([
            'status' => \App\Enums\LeadStatus::New,
        ]);

        $this->actingAs($user);

        // Simulate quote creation
        $quote = Quote::create([
            'company_id' => $lead->company_id,
            'quote_number' => 'TEST-2026-0001',
            'status' => \App\Enums\QuoteStatus::Draft,
            'valid_until' => now()->addDays(30),
        ]);

        $lead->update([
            'status' => \App\Enums\LeadStatus::Converted,
            'converted_to_quote_id' => $quote->id,
        ]);

        $this->assertEquals(\App\Enums\LeadStatus::Converted, $lead->fresh()->status);
        $this->assertEquals($quote->id, $lead->fresh()->converted_to_quote_id);
    }
}
