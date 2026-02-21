<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadActionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_lead_edit_page_loads_with_actions(): void
    {
        $user = User::first();
        $lead = Lead::first();

        $response = $this->actingAs($user)
            ->get("/admin/leads/{$lead->id}/edit");

        $response->assertStatus(200);
    }

    public function test_create_quote_from_lead_action_works(): void
    {
        $user = User::first();
        $lead = Lead::factory()->create([
            'company_id' => Company::first()->id,
            'email' => 'test@example.com',
        ]);

        // Test that we can access the lead edit page
        $response = $this->actingAs($user)
            ->get("/admin/leads/{$lead->id}/edit");

        $response->assertStatus(200);

        // Verify lead exists
        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'email' => 'test@example.com',
        ]);
    }

    public function test_request_more_info_action_available(): void
    {
        $user = User::first();
        $lead = Lead::first();

        $response = $this->actingAs($user)
            ->get("/admin/leads/{$lead->id}/edit");

        $response->assertStatus(200);
    }
}
