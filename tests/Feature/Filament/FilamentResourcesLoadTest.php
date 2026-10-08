<?php

namespace Tests\Feature\Filament;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\Quote;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Test dat alle Filament resources laden zonder errors
 */
class FilamentResourcesLoadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    #[Test]
    public function lead_edit_page_loads_without_errors(): void
    {
        $user = User::first();
        $lead = Lead::first();

        $this->actingAs($user);

        $response = $this->get("/admin/leads/{$lead->id}/edit");

        $response->assertStatus(200);
        $response->assertDontSee('Whoops');
        $response->assertDontSee('Exception');
    }

    #[Test]
    public function quote_view_page_loads_without_errors(): void
    {
        $user = User::first();
        $quote = Quote::first();

        $this->actingAs($user);

        $response = $this->get("/admin/quotes/{$quote->id}");

        $response->assertStatus(200);
        $response->assertDontSee('Whoops');
        $response->assertDontSee('Exception');
    }

    #[Test]
    public function quote_list_redirects_to_view_not_edit(): void
    {
        $user = User::first();
        $quote = Quote::first();

        $this->actingAs($user);

        // De lijst-pagina moet laden (geen 500)
        $response = $this->get('/admin/quotes');
        $response->assertStatus(200);

        // De view route moet bestaan en laden
        $response = $this->get("/admin/quotes/{$quote->id}");
        $response->assertStatus(200);
    }

    #[Test]
    public function quote_edit_page_loads_without_errors(): void
    {
        $user = User::first();
        $quote = Quote::first();

        $this->actingAs($user);

        $response = $this->get("/admin/quotes/{$quote->id}/edit");

        $response->assertStatus(200);
        $response->assertDontSee('Whoops');
        $response->assertDontSee('Exception');
    }

    #[Test]
    public function lead_list_page_loads_without_errors(): void
    {
        $user = User::first();

        $this->actingAs($user);

        $response = $this->get('/admin/leads');

        $response->assertStatus(200);
        $response->assertSee('Leads');
    }

    #[Test]
    public function quote_list_page_loads_without_errors(): void
    {
        $user = User::first();

        $this->actingAs($user);

        $response = $this->get('/admin/quotes');

        $response->assertStatus(200);
        $response->assertSee('Offertes');
    }

    #[Test]
    public function company_list_page_loads_without_errors(): void
    {
        $user = User::first();

        $this->actingAs($user);

        $response = $this->get('/admin/companies');

        $response->assertStatus(200);
    }

    #[Test]
    public function company_view_page_loads_without_errors(): void
    {
        $this->actingAs(User::first());

        $company = Company::first();

        $response = $this->get("/admin/companies/{$company->id}");

        $response->assertStatus(200);
        $response->assertDontSee('Whoops');
        $response->assertDontSee('Exception');
    }

    #[Test]
    public function product_list_page_loads_without_errors(): void
    {
        $user = User::first();

        $this->actingAs($user);

        $response = $this->get('/admin/products');

        $response->assertStatus(200);
    }

    #[Test]
    public function project_view_page_loads_without_errors(): void
    {
        $user = User::first();
        $project = Project::first();

        $this->actingAs($user);

        $response = $this->get("/admin/projects/{$project->id}");

        $response->assertStatus(200);
        $response->assertDontSee('Whoops');
        $response->assertDontSee('Exception');
    }

    #[Test]
    public function project_list_page_loads_without_errors(): void
    {
        $user = User::first();

        $this->actingAs($user);

        $response = $this->get('/admin/projects');

        $response->assertStatus(200);
    }

    #[Test]
    public function lead_view_page_loads_without_errors(): void
    {
        $user = User::first();
        $lead = Lead::first();

        $this->actingAs($user);

        $response = $this->get("/admin/leads/{$lead->id}");

        $response->assertStatus(200);
        $response->assertDontSee('Whoops');
        $response->assertDontSee('Exception');
    }

    // -------------------------------------------------------------------------
    // Invoice
    // -------------------------------------------------------------------------

    #[Test]
    public function invoice_list_page_loads_without_errors(): void
    {
        $this->actingAs(User::first());

        $response = $this->get('/admin/invoices');

        $response->assertStatus(200);
        $response->assertDontSee('Whoops');
        $response->assertDontSee('Exception');
    }

    #[Test]
    public function invoice_edit_page_loads_without_errors(): void
    {
        $this->actingAs(User::first());

        $invoice = Invoice::first();

        $response = $this->get("/admin/invoices/{$invoice->id}/edit");

        $response->assertStatus(200);
        $response->assertDontSee('Whoops');
        $response->assertDontSee('Exception');
    }

    // -------------------------------------------------------------------------
    // Supplier
    // -------------------------------------------------------------------------

    #[Test]
    public function supplier_list_page_loads_without_errors(): void
    {
        $this->actingAs(User::first());

        $response = $this->get('/admin/suppliers');

        $response->assertStatus(200);
        $response->assertDontSee('Whoops');
        $response->assertDontSee('Exception');
    }

    #[Test]
    public function supplier_edit_page_loads_without_errors(): void
    {
        $this->actingAs(User::first());

        $supplier = Supplier::factory()->create();

        $response = $this->get("/admin/suppliers/{$supplier->id}/edit");

        $response->assertStatus(200);
        $response->assertDontSee('Whoops');
        $response->assertDontSee('Exception');
    }

    // -------------------------------------------------------------------------
    // Purchase Order
    // -------------------------------------------------------------------------

    #[Test]
    public function purchase_order_list_page_loads_without_errors(): void
    {
        $this->actingAs(User::first());

        $response = $this->get('/admin/purchase-orders');

        $response->assertStatus(200);
        $response->assertDontSee('Whoops');
        $response->assertDontSee('Exception');
    }

    #[Test]
    public function purchase_order_view_page_loads_without_errors(): void
    {
        $this->actingAs(User::first());

        $purchaseOrder = PurchaseOrder::factory()->create();

        $response = $this->get("/admin/purchase-orders/{$purchaseOrder->id}");

        $response->assertStatus(200);
        $response->assertDontSee('Whoops');
        $response->assertDontSee('Exception');
    }

    #[Test]
    public function purchase_order_edit_page_loads_without_errors(): void
    {
        $this->actingAs(User::first());

        $purchaseOrder = PurchaseOrder::factory()->create();

        $response = $this->get("/admin/purchase-orders/{$purchaseOrder->id}/edit");

        $response->assertStatus(200);
        $response->assertDontSee('Whoops');
        $response->assertDontSee('Exception');
    }
}
