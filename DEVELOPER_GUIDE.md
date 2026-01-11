# AliveChMS Developer Guide

## Getting Started

### Prerequisites
- PHP 8.0 or higher
- MySQL 5.7 or higher
- Composer
- Web server (Apache/Nginx)

### Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd alivechms
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Environment setup**
   ```bash
   cp .env.example .env
   # Edit .env with your configuration
   ```

4. **Database setup**
   ```bash
   php migrate.php up
   ```

5. **Run tests**
   ```bash
   php run-tests.php
   ```

## Development Workflow

### 1. Setting Up Development Environment

#### Local Development
```bash
# Start local server
php -S localhost:8000 -t public

# Or use your preferred local server (XAMPP, WAMP, etc.)
```

#### Environment Configuration
```env
# Development settings
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database
DB_HOST=localhost
DB_NAME=alivechms_dev
DB_USER=root
DB_PASS=

# Cache (use memory for development)
CACHE_DRIVER=memory
CACHE_FALLBACK=true
```

### 2. Code Organization

#### Directory Structure
```
alivechms/
├── core/                   # Core framework classes
│   ├── Cache/             # Caching system
│   ├── Database/          # Database and migrations
│   ├── Events/            # Event system
│   ├── Http/              # HTTP handling
│   ├── Providers/         # Service providers
│   └── *.php              # Core classes
├── routes/                # Route definitions
├── migrations/            # Database migrations
├── tests/                 # Test files
├── public/                # Public web files
├── cache/                 # Cache storage
└── logs/                  # Log files
```

#### Naming Conventions
- **Classes**: PascalCase (`UserService`, `MemberController`)
- **Methods**: camelCase (`getUserById`, `createMember`)
- **Variables**: camelCase (`$userId`, `$memberData`)
- **Constants**: UPPER_SNAKE_CASE (`DEFAULT_TTL`, `MAX_FILE_SIZE`)
- **Files**: PascalCase for classes, lowercase for others

### 3. Creating New Features

#### Step 1: Create Migration (if needed)
```bash
# Create migration file
touch migrations/YYYY_MM_DD_HHMMSS_create_feature_table.php
```

```php
<?php
class CreateFeatureTable extends Migration
{
    public function up(): void
    {
        $this->schema->create('features', function($table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        $this->schema->dropIfExists('features');
    }
}
```

#### Step 2: Create Entity Class
```php
<?php
// core/Feature.php
class Feature extends ORM
{
    protected static string $table = 'features';
    protected static array $fillable = ['name', 'description', 'active'];

    public static function getActive(): array
    {
        return static::where('active', true)->get();
    }

    public function isActive(): bool
    {
        return $this->active;
    }
}
```

#### Step 3: Create Route Handler
```php
<?php
// routes/FeatureRoutes.php
class FeatureRoutes extends BaseHttpRoute
{
    public function index(Request $request): Response
    {
        $features = Feature::getActive();
        return Response::success($features);
    }

    public function show(Request $request): Response
    {
        $id = $request->getRouteParam('id');
        $feature = Feature::find($id);
        
        if (!$feature) {
            return Response::notFound('Feature not found');
        }
        
        return Response::success($feature);
    }

    public function store(Request $request): Response
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'active' => 'boolean'
        ]);

        $feature = Feature::create($validated);
        
        // Dispatch event
        EventDispatcher::fire('feature.created', ['feature' => $feature]);
        
        return Response::success($feature, 'Feature created successfully');
    }
}
```

#### Step 4: Register Routes
```php
// routes/api.php
$router->get('/features', [FeatureRoutes::class, 'index']);
$router->get('/features/{id}', [FeatureRoutes::class, 'show']);
$router->post('/features', [FeatureRoutes::class, 'store']);
```

#### Step 5: Create Tests
```php
<?php
// tests/Unit/FeatureTest.php
class FeatureTest
{
    public function testCreateFeature(): void
    {
        $feature = Feature::create([
            'name' => 'Test Feature',
            'description' => 'Test Description',
            'active' => true
        ]);
        
        $this->assert($feature->id > 0, 'Feature created with ID');
        $this->assert($feature->name === 'Test Feature', 'Feature name set correctly');
    }

    public function testGetActiveFeatures(): void
    {
        $activeFeatures = Feature::getActive();
        $this->assert(is_array($activeFeatures), 'Returns array of active features');
    }
}
```

### 4. Working with Services

#### Creating a Service
```php
<?php
// core/Services/FeatureService.php
class FeatureService
{
    private CacheInterface $cache;
    private EventDispatcher $events;

    public function __construct(CacheInterface $cache, EventDispatcher $events)
    {
        $this->cache = $cache;
        $this->events = $events;
    }

    public function getActiveFeatures(): array
    {
        return $this->cache->remember('features.active', function() {
            return Feature::getActive();
        }, 3600, ['features']);
    }

    public function createFeature(array $data): Feature
    {
        $feature = Feature::create($data);
        
        // Clear cache
        $this->cache->invalidateTag('features');
        
        // Dispatch event
        $this->events->dispatch('feature.created', ['feature' => $feature]);
        
        return $feature;
    }
}
```

#### Registering Service
```php
<?php
// core/Providers/FeatureServiceProvider.php
class FeatureServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->bind('FeatureService', function($container) {
            return new FeatureService(
                $container->resolve('cache'),
                $container->resolve('events')
            );
        });
    }
}
```

### 5. Event-Driven Development

#### Creating Custom Events
```php
<?php
// core/Events/FeatureEvents.php
class FeatureCreatedEvent extends Event
{
    public function __construct(Feature $feature)
    {
        parent::__construct([
            'feature_id' => $feature->id,
            'feature_name' => $feature->name,
            'created_by' => Auth::user()?->id
        ]);
    }

    public function getFeature(): Feature
    {
        return Feature::find($this->getData('feature_id'));
    }
}
```

#### Creating Event Listeners
```php
<?php
// core/Events/Listeners/FeatureEventListener.php
class FeatureEventListener implements EventListener
{
    public function handle(Event $event): void
    {
        if ($event instanceof FeatureCreatedEvent) {
            $this->handleFeatureCreated($event);
        }
    }

    private function handleFeatureCreated(FeatureCreatedEvent $event): void
    {
        // Log feature creation
        error_log("Feature created: " . $event->getData('feature_name'));
        
        // Send notification
        // NotificationService::send(...);
        
        // Update statistics
        // StatsService::increment('features.created');
    }

    public function getName(): string
    {
        return 'FeatureEventListener';
    }

    public function getPriority(): int
    {
        return 100;
    }

    public function shouldHandle(Event $event): bool
    {
        return $event instanceof FeatureCreatedEvent;
    }
}
```

### 6. Middleware Development

#### Creating Custom Middleware
```php
<?php
// core/Http/Middleware/FeatureMiddleware.php
class FeatureMiddleware extends Middleware
{
    public function handle(Request $request, callable $next): Response
    {
        $featureFlag = $request->getHeader('X-Feature-Flag');
        
        if ($featureFlag && !$this->isFeatureEnabled($featureFlag)) {
            return Response::error('Feature not available', 403);
        }
        
        return $next($request);
    }

    private function isFeatureEnabled(string $feature): bool
    {
        return Cache::remember("feature.{$feature}", function() use ($feature) {
            $featureObj = Feature::where('name', $feature)->first();
            return $featureObj && $featureObj->isActive();
        }, 300, ['features']);
    }

    public function getPriority(): int
    {
        return 150; // After auth, before rate limiting
    }
}
```

### 7. Database Best Practices

#### Query Optimization
```php
// Bad: N+1 query problem
$members = Member::getAll();
foreach ($members as $member) {
    $family = Family::find($member->family_id); // N queries
}

// Good: Eager loading
$members = Member::with('family')->getAll(); // 2 queries total
```

#### Using Query Builder
```php
$results = QueryBuilder::table('members')
    ->select(['id', 'name', 'email'])
    ->where('status', 'active')
    ->where('created_at', '>', '2024-01-01')
    ->orderBy('name')
    ->limit(50)
    ->get();
```

#### Transactions
```php
Database::transaction(function() {
    $member = Member::create($memberData);
    $family = Family::create($familyData);
    $member->update(['family_id' => $family->id]);
});
```

### 8. Caching Strategies

#### Cache Patterns
```php
// Cache-aside pattern
public function getUser(int $id): ?User
{
    $cacheKey = "user:{$id}";
    
    $user = Cache::get($cacheKey);
    if ($user === null) {
        $user = User::find($id);
        if ($user) {
            Cache::set($cacheKey, $user, 1800, ['users', "user:{$id}"]);
        }
    }
    
    return $user;
}

// Write-through pattern
public function updateUser(int $id, array $data): User
{
    $user = User::find($id);
    $user->update($data);
    
    // Update cache immediately
    Cache::set("user:{$id}", $user, 1800, ['users', "user:{$id}"]);
    
    return $user;
}

// Write-behind pattern (with events)
public function updateUser(int $id, array $data): User
{
    $user = User::find($id);
    $user->update($data);
    
    // Queue cache update
    EventDispatcher::queue('cache.update', [
        'key' => "user:{$id}",
        'data' => $user
    ]);
    
    return $user;
}
```

#### Cache Invalidation
```php
// Tag-based invalidation
Cache::invalidateTag('users'); // Invalidate all user-related cache

// Pattern-based invalidation
Cache::invalidatePattern('user:*'); // If supported by driver

// Event-driven invalidation
EventDispatcher::listen('user.updated', function($event) {
    $userId = $event->getData('user_id');
    Cache::delete("user:{$userId}");
    Cache::invalidateTag('users');
});
```

### 9. Testing Guidelines

#### Unit Testing
```php
class UserServiceTest
{
    private UserService $userService;
    private Container $container;

    public function setUp(): void
    {
        $this->container = Container::getInstance();
        $this->userService = $this->container->resolve('UserService');
    }

    public function testCreateUser(): void
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com'
        ];

        $user = $this->userService->createUser($userData);
        
        $this->assert($user->id > 0, 'User created with ID');
        $this->assert($user->email === 'test@example.com', 'Email set correctly');
    }

    public function tearDown(): void
    {
        // Clean up test data
        Database::query("DELETE FROM users WHERE email = 'test@example.com'");
    }
}
```

#### Integration Testing
```php
class UserApiTest
{
    public function testCreateUserEndpoint(): void
    {
        $request = new Request([
            'method' => 'POST',
            'uri' => '/api/users',
            'body' => json_encode([
                'name' => 'Test User',
                'email' => 'test@example.com'
            ]),
            'headers' => ['Content-Type' => 'application/json']
        ]);

        $response = $this->processRequest($request);
        
        $this->assert($response->getStatusCode() === 201, 'Returns 201 status');
        
        $data = json_decode($response->getBody(), true);
        $this->assert($data['success'] === true, 'Response indicates success');
        $this->assert(isset($data['data']['user']['id']), 'Returns user ID');
    }
}
```

### 10. Debugging and Logging

#### Debug Mode
```php
// Enable debug mode in .env
APP_DEBUG=true

// Debug information will be included in error responses
```

#### Custom Logging
```php
// Log to file
error_log("Debug message: " . json_encode($data));

// Log with context
Helpers::logError("User creation failed", [
    'user_data' => $userData,
    'error' => $exception->getMessage()
]);

// Event-based logging
EventDispatcher::listen('*', function($event) {
    if (APP_DEBUG) {
        error_log("Event: " . $event->getName() . " - " . json_encode($event->getData()));
    }
});
```

### 11. Performance Optimization

#### Database Optimization
- Use indexes on frequently queried columns
- Implement query caching for expensive operations
- Use LIMIT clauses for large result sets
- Avoid SELECT * queries

#### Caching Optimization
- Cache expensive computations
- Use appropriate TTL values
- Implement cache warming for critical data
- Monitor cache hit ratios

#### Memory Optimization
- Use memory cache for frequently accessed data
- Implement proper eviction policies
- Monitor memory usage
- Use lazy loading where appropriate

### 12. Security Considerations

#### Input Validation
```php
// Always validate input
$validated = $request->validate([
    'email' => 'required|email|max:255',
    'password' => 'required|min:8|max:255'
]);

// Sanitize output
$safeHtml = htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8');
```

#### SQL Injection Prevention
```php
// Use prepared statements
$users = Database::query(
    "SELECT * FROM users WHERE email = ? AND status = ?",
    [$email, $status]
);

// Or use query builder
$users = QueryBuilder::table('users')
    ->where('email', $email)
    ->where('status', $status)
    ->get();
```

#### Authentication & Authorization
```php
// Check authentication
if (!Auth::check()) {
    return Response::unauthorized();
}

// Check permissions
if (!Auth::hasPermission('users.create')) {
    return Response::forbidden();
}
```

## Common Patterns

### Repository Pattern
```php
interface UserRepositoryInterface
{
    public function find(int $id): ?User;
    public function create(array $data): User;
    public function update(int $id, array $data): User;
    public function delete(int $id): bool;
}

class UserRepository implements UserRepositoryInterface
{
    public function find(int $id): ?User
    {
        return Cache::remember("user:{$id}", function() use ($id) {
            return User::find($id);
        }, 1800, ['users']);
    }
    
    // ... other methods
}
```

### Service Layer Pattern
```php
class UserService
{
    private UserRepositoryInterface $userRepository;
    private EventDispatcher $events;

    public function __construct(UserRepositoryInterface $userRepository, EventDispatcher $events)
    {
        $this->userRepository = $userRepository;
        $this->events = $events;
    }

    public function createUser(array $data): User
    {
        $user = $this->userRepository->create($data);
        $this->events->dispatch('user.created', ['user' => $user]);
        return $user;
    }
}
```

## Troubleshooting

### Common Issues

1. **Container Resolution Errors**
   - Ensure services are properly registered
   - Check for circular dependencies
   - Verify constructor parameters

2. **Cache Issues**
   - Check cache directory permissions
   - Verify cache driver configuration
   - Monitor cache size and eviction

3. **Database Connection Issues**
   - Verify database credentials
   - Check database server status
   - Ensure proper database permissions

4. **Performance Issues**
   - Enable query logging to identify slow queries
   - Monitor cache hit ratios
   - Check memory usage

### Debug Tools

```php
// Container debugging
$bindings = Container::getInstance()->getBindings();
var_dump($bindings);

// Cache statistics
$stats = Cache::stats();
var_dump($stats);

// Event debugging
$history = EventDispatcher::getInstance()->getDispatchHistory();
var_dump($history);
```

This developer guide provides comprehensive information for working with the AliveChMS codebase. Follow these patterns and practices to maintain code quality and consistency.