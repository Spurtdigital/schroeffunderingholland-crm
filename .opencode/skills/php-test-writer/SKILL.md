---
name: php-test-writer
description: Write comprehensive PHPUnit and Pest tests for Laravel applications
license: MIT
compatibility: opencode
metadata:
  framework: laravel
  testing: phpunit, pest
  language: php
---

## Wat ik doe

Ik help je met het schrijven van kwalitatieve tests voor Laravel applicaties:

- **Feature Tests**: Test complete user flows en HTTP requests
- **Unit Tests**: Test individuele classes en methods in isolatie
- **Database Testing**: Factories, seeders, database transactions
- **Pest Tests**: Moderne, elegante test syntax
- **Mocking**: Mock external services, jobs, notifications
- **API Testing**: Test RESTful API endpoints
- **Authentication Tests**: Test login, registration, permissions
- **Test Coverage**: Maximize code coverage en test quality
- **TDD**: Test-Driven Development best practices

## Test Types en Wanneer ze te Gebruiken

### Feature Tests
Voor het testen van complete features en user workflows:
```php
// PHPUnit style
class OrderTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_user_can_create_order(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['price' => 100]);
        
        $response = $this->actingAs($user)
            ->post('/orders', [
                'product_id' => $product->id,
                'quantity' => 2,
            ]);
        
        $response->assertRedirect('/orders');
        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'total' => 200,
        ]);
    }
}

// Pest style (MODERN ✨)
use function Pest\Laravel\{actingAs, assertDatabaseHas, post};

it('allows user to create order', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['price' => 100]);
    
    actingAs($user)
        ->post('/orders', [
            'product_id' => $product->id,
            'quantity' => 2,
        ])
        ->assertRedirect('/orders');
    
    assertDatabaseHas('orders', [
        'user_id' => $user->id,
        'total' => 200,
    ]);
});
```

### Unit Tests
Voor het testen van individuele classes:
```php
// PHPUnit
class OrderCalculatorTest extends TestCase
{
    public function test_calculates_total_with_tax(): void
    {
        $calculator = new OrderCalculator(taxRate: 0.21);
        
        $total = $calculator->calculateTotal(
            subtotal: 100,
            discount: 10
        );
        
        $this->assertEquals(108.9, $total);
    }
}

// Pest
test('calculates total with tax', function () {
    $calculator = new OrderCalculator(taxRate: 0.21);
    
    $total = $calculator->calculateTotal(
        subtotal: 100,
        discount: 10
    );
    
    expect($total)->toBe(108.9);
});
```

## Best Practices

### 1. Gebruik Database Factories
```php
// database/factories/UserFactory.php
class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => bcrypt('password'),
        ];
    }
    
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
        ]);
    }
}

// In tests
$user = User::factory()->create();
$admin = User::factory()->admin()->create();
$users = User::factory()->count(10)->create();
```

### 2. Arrange-Act-Assert Pattern
```php
test('user can update their profile', function () {
    // Arrange - Setup
    $user = User::factory()->create(['name' => 'John']);
    
    // Act - Perform action
    actingAs($user)
        ->patch("/profile/{$user->id}", [
            'name' => 'Jane',
        ]);
    
    // Assert - Verify result
    expect($user->fresh()->name)->toBe('Jane');
});
```

### 3. Test Validation
```php
test('order creation requires valid data', function () {
    actingAs(User::factory()->create())
        ->post('/orders', [
            'product_id' => 'invalid',
            'quantity' => -1,
        ])
        ->assertSessionHasErrors(['product_id', 'quantity']);
});

test('order creation succeeds with valid data', function () {
    $product = Product::factory()->create();
    
    actingAs(User::factory()->create())
        ->post('/orders', [
            'product_id' => $product->id,
            'quantity' => 2,
        ])
        ->assertRedirect();
});
```

### 4. Mock External Services
```php
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

test('sends notification after order', function () {
    Notification::fake();
    
    $user = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $user->id]);
    
    event(new OrderCreated($order));
    
    Notification::assertSentTo($user, OrderCreatedNotification::class);
});

test('fetches data from external API', function () {
    Http::fake([
        'api.example.com/*' => Http::response([
            'data' => ['id' => 1, 'name' => 'Test'],
        ], 200),
    ]);
    
    $service = new ExternalApiService();
    $result = $service->fetchData();
    
    expect($result['name'])->toBe('Test');
    
    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.example.com/data';
    });
});
```

### 5. Test Jobs
```php
use Illuminate\Support\Facades\Queue;

test('dispatches job after order creation', function () {
    Queue::fake();
    
    $order = Order::factory()->create();
    
    event(new OrderCreated($order));
    
    Queue::assertPushed(SendOrderConfirmationJob::class, function ($job) use ($order) {
        return $job->order->id === $order->id;
    });
});
```

### 6. Test Policies
```php
test('user can view their own order', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $user->id]);
    
    expect($user->can('view', $order))->toBeTrue();
});

test('user cannot view other users order', function () {
    $user = User::factory()->create();
    $otherOrder = Order::factory()->create();
    
    expect($user->can('view', $otherOrder))->toBeFalse();
});
```

### 7. API Testing
```php
test('api returns paginated orders', function () {
    Order::factory()->count(15)->create();
    
    $response = actingAs(User::factory()->create())
        ->getJson('/api/orders');
    
    $response
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'total', 'status', 'created_at']
            ],
            'meta' => ['current_page', 'total'],
        ])
        ->assertJsonCount(10, 'data');
});
```

### 8. Database Testing
```php
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('soft deletes order', function () {
    $order = Order::factory()->create();
    
    $order->delete();
    
    assertSoftDeleted('orders', ['id' => $order->id]);
});

test('creates order with relationships', function () {
    $order = Order::factory()
        ->has(OrderItem::factory()->count(3))
        ->create();
    
    expect($order->items)->toHaveCount(3);
    assertDatabaseCount('order_items', 3);
});
```

## Pest Specific Features

### Datasets
```php
test('validates email format', function ($email, $valid) {
    $response = post('/register', ['email' => $email]);
    
    if ($valid) {
        $response->assertSessionDoesntHaveErrors('email');
    } else {
        $response->assertSessionHasErrors('email');
    }
})->with([
    ['test@example.com', true],
    ['invalid-email', false],
    ['test@', false],
]);
```

### Higher Order Tests
```php
it('has required fields')
    ->expect(fn() => User::factory()->create())
    ->toHaveKeys(['id', 'name', 'email', 'created_at']);
```

### Architectural Testing
```php
arch('models extend base model')
    ->expect('App\Models')
    ->toExtend('Illuminate\Database\Eloquent\Model');

arch('controllers are in correct namespace')
    ->expect('App\Http\Controllers')
    ->toBeClasses()
    ->toExtend('App\Http\Controllers\Controller');
```

## Test Coverage Tips

1. **Test Happy Path**: Normale, verwachte flows
2. **Test Edge Cases**: Lege data, nulls, maximale waarden
3. **Test Validation**: Zowel succes als failure scenarios
4. **Test Authorization**: Wie mag wat doen
5. **Test Error Handling**: Wat gebeurt er bij fouten
6. **Gebruik Code Coverage**: `php artisan test --coverage` (Pest/PHPUnit)

## Wanneer mij te gebruiken

Gebruik deze skill wanneer je:
- Tests wilt schrijven voor nieuwe features
- Bestaande code wilt testen (legacy code)
- Test coverage wilt verhogen
- Van PHPUnit naar Pest wilt migreren
- API endpoints wilt testen
- Complex business logic wilt testen
- TDD wilt toepassen
- Twijfelt over wat/hoe te testen

## Test Commandos

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/OrderTest.php

# Run with coverage
php artisan test --coverage

# Run parallel (sneller)
php artisan test --parallel

# Filter by name
php artisan test --filter=test_user_can_create_order
```
