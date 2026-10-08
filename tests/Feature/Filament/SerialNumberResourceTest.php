<?php

namespace Tests\Feature\Filament;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SerialNumberResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    #[Test]
    public function serial_numbers_list_page_loads_without_errors(): void
    {
        $this->actingAs(User::first());

        $response = $this->get('/admin/serial-numbers');

        $response->assertStatus(200);
        $response->assertSee('Serienummers');
        $response->assertDontSee('Whoops');
        $response->assertDontSee('Exception');
    }
}
