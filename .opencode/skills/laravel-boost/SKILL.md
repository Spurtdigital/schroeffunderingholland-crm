---
name: laravel-boost
description: Laravel 12 development with modern best practices and performance optimization
license: MIT
compatibility: opencode
metadata:
  framework: laravel
  version: "12"
  language: php
---

## Wat ik doe

Ik help je met het bouwen van moderne Laravel applicaties volgens industry best practices:

- **Clean Architecture**: Implementeer Service classes, Actions, DTOs en repositories
- **Performance**: Query optimization, caching, eager loading, database indexing
- **Security**: CSRF, XSS, SQL injection prevention, authorization met policies
- **Testing**: Feature tests, Unit tests, Database testing, Mocking
- **API Development**: RESTful APIs, API Resources, Rate limiting, Versioning
- **Jobs & Queues**: Background processing, job batching, queue optimization
- **Events & Listeners**: Event-driven architecture implementatie
- **Notifications**: Multi-channel notifications (mail, SMS, Slack, database)
- **Middleware**: Custom middleware voor authentication, logging, rate limiting
- **Form Requests**: Validation met Form Request classes

## Best Practices die ik volg

### Controllers - Keep them thin
```php
// GOED ✅
class OrderController extends Controller
{
    public function store(StoreOrderRequest $request, CreateOrderAction $action)
    {
        $order = $action->execute($request->validated());
        
        return redirect()
            ->route('orders.show', $order)
            ->with('success', 'Order created successfully');
    }
}

// SLECHT ❌ - Te veel business logic in controller
class OrderController extends Controller
{
    public function store(Request $request)
    {
        // Validation here
        // Business logic here
        // Database queries here
        // Email sending here
        // etc...
    }
}
```

### Service/Action Classes
```php
class CreateOrderAction
{
    public function execute(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            $order = Order::create($data);
            
            event(new OrderCreated($order));
            
            dispatch(new SendOrderConfirmationJob($order));
            
            return $order;
        });
    }
}
```

### Query Optimization
```php
// GOED ✅ - Eager loading
$posts = Post::with(['author', 'comments.user'])->get();

// SLECHT ❌ - N+1 query problem
$posts = Post::all();
foreach ($posts as $post) {
    echo $post->author->name; // N+1!
}

// GOED ✅ - Select only needed columns
User::select('id', 'name', 'email')->get();

// GOED ✅ - Chunking voor grote datasets
User::chunk(200, function ($users) {
    foreach ($users as $user) {
        // Process user
    }
});
```

### DTOs (Data Transfer Objects)
```php
class OrderData
{
    public function __construct(
        public string $customerName,
        public string $email,
        public array $items,
        public float $total,
    ) {}
    
    public static function fromRequest(Request $request): self
    {
        return new self(
            customerName: $request->input('customer_name'),
            email: $request->input('email'),
            items: $request->input('items'),
            total: $request->input('total'),
        );
    }
}
```

### Form Requests
```php
class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Order::class);
    }
    
    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }
}
```

### Policies voor Authorization
```php
class OrderPolicy
{
    public function view(User $user, Order $order): bool
    {
        return $user->id === $order->user_id 
            || $user->isAdmin();
    }
    
    public function update(User $user, Order $order): bool
    {
        return $user->id === $order->user_id 
            && $order->status === OrderStatus::Pending;
    }
}
```

### Events & Listeners
```php
// Event
class OrderCreated
{
    public function __construct(public Order $order) {}
}

// Listener
class SendOrderConfirmation
{
    public function handle(OrderCreated $event): void
    {
        Mail::to($event->order->customer_email)
            ->send(new OrderConfirmationMail($event->order));
    }
}
```

### Caching Strategies
```php
// Cache query results
$users = Cache::remember('users.active', 3600, function () {
    return User::where('active', true)->get();
});

// Cache clearing
Cache::forget('users.active');
Cache::tags(['users'])->flush();
```

### API Resources
```php
class OrderResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'customer_name' => $this->customer_name,
            'total' => $this->total,
            'status' => $this->status->value,
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
```

### Enums (Laravel 11+)
```php
enum OrderStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    
    public function label(): string
    {
        return match($this) {
            self::Pending => 'In afwachting',
            self::Processing => 'Wordt verwerkt',
            self::Completed => 'Voltooid',
            self::Cancelled => 'Geannuleerd',
        };
    }
}
```

## Folder Structuur Best Practices

```
app/
├── Actions/           # Single-purpose action classes
├── DTOs/             # Data Transfer Objects
├── Enums/            # Enum classes
├── Events/           # Event classes
├── Listeners/        # Event listeners
├── Http/
│   ├── Controllers/  # Keep thin!
│   ├── Requests/     # Form validation
│   ├── Resources/    # API resources
│   └── Middleware/   # Custom middleware
├── Models/           # Eloquent models
├── Policies/         # Authorization
├── Repositories/     # Database abstraction (optional)
└── Services/         # Business logic services
```

## Wanneer mij te gebruiken

Gebruik deze skill wanneer je:
- Een nieuwe Laravel feature wilt bouwen
- Code wilt refactoren naar best practices
- Performance problemen hebt (N+1, slow queries)
- API endpoints moet implementeren
- Background jobs en queues wilt opzetten
- Authorization en security moet implementeren
- Twijfelt over Laravel design patterns
- Laravel upgrade wilt uitvoeren

## Laravel Versie Specifieke Features

### Laravel 12 (Latest - 2025)
- **Improved Query Performance**: Automatische query optimization
- **Enhanced Eloquent**: Verbeterde relationship handling en lazy loading
- **Better Type Safety**: Strengere type hints en return types
- **Native Async Support**: Verbeterde async/await patterns voor jobs
- **Improved Testing**: Snellere test execution en betere assertions
- **Enhanced CLI**: Nieuwe artisan commands en verbeterde output
- **Security Updates**: Laatste security patches en best practices

### Laravel 11
- Nieuwe application structuur (slimmer)
- Per-second rate limiting
- Health check endpoint
- Verbeterde queue monitoring
- Streamlined directory structure (minder boilerplate)

### Laravel 10
- Native type declarations
- Process layer voor externe processen
- Invokable validation rules

## Performance Tips

1. **Database**: Gebruik indices, eager loading, select only needed columns
2. **Caching**: Redis/Memcached voor session, cache en queues
3. **Queues**: Verplaats heavy work naar background jobs
4. **CDN**: Gebruik voor static assets
5. **Horizon**: Monitor en beheer queues
6. **Telescope**: Debug en monitor in development
