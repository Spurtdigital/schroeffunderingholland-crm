<?php

namespace Tests\Feature\Filament;

use App\Models\Product;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Test Quote Items RelationManager
 */
class QuoteItemsRelationManagerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    #[Test]
    public function quote_edit_page_with_items_relation_loads(): void
    {
        $user = User::first();
        $quote = Quote::first();

        $this->actingAs($user);

        $response = $this->get("/admin/quotes/{$quote->id}/edit");

        $response->assertStatus(200);
        $response->assertDontSee('Class "Filament\Forms\Components\Grid" not found');
        $response->assertDontSee('Class "Filament\Forms\Components\Group" not found');
        $response->assertDontSee('Whoops');
    }

    #[Test]
    public function quote_items_can_be_created(): void
    {
        $quote = Quote::first();
        $product = Product::first();

        $item = QuoteItem::create([
            'quote_id' => $quote->id,
            'type' => \App\Enums\QuoteItemType::Product,
            'itemable_type' => Product::class,
            'itemable_id' => $product->id,
            'description' => $product->name,
            'quantity' => 10,
            'unit_price' => 100,
        ]);

        $this->assertDatabaseHas('quote_items', [
            'id' => $item->id,
            'quote_id' => $quote->id,
            'quantity' => 10,
        ]);
    }

    #[Test]
    public function quote_items_total_price_is_calculated(): void
    {
        $quote = Quote::first();

        $item = QuoteItem::create([
            'quote_id' => $quote->id,
            'type' => \App\Enums\QuoteItemType::FreeLine,
            'description' => 'Test item',
            'quantity' => 5,
            'unit_price' => 25.50,
        ]);

        $this->assertEquals('127.50', $item->total); // total is auto-calculated via boot()
    }
}
