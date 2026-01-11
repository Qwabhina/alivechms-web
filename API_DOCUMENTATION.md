# AliveChMS API Documentation

## Overview

This document provides comprehensive API documentation for the AliveChMS (Church Management System) architecture. The system is built with modern PHP practices including dependency injection, middleware pipeline, event system, and enhanced caching.

## Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [Dependency Injection Container](#dependency-injection-container)
3. [HTTP Request/Response System](#http-requestresponse-system)
4. [Middleware Pipeline](#middleware-pipeline)
5. [Event System](#event-system)
6. [Caching System](#caching-system)
7. [Database Migration System](#database-migration-system)
8. [Core Entities](#core-entities)
9. [Authentication & Authorization](#authentication--authorization)
10. [API Endpoints](#api-endpoints)

---

## Architecture Overview

AliveChMS follows a modern, layered architecture with the following key components:

### Core Components

- **Application Bootstrap**: Manages application lifecycle and service providers
- **Dependency Injection Container**: Manages service dependencies and object creation
- **HTTP Layer**: Request/Response handling with middleware pipeline
- **Event System**: Event-driven architecture for loose coupling
- **Caching Layer**: Multi-driver caching with fallback and replication
- **Database Layer**: Migration system and ORM with query builder

### Design Patterns

- **Dependency Injection**: Constructor injection for loose coupling
- **Service Provider Pattern**: Modular service registration
- **Middleware Pattern**: Request/response processing pipeline
- **Observer Pattern**: Event-driven communication
- **Strategy Pattern**: Multiple cache drivers and eviction policies
- **Factory Pattern**: Object creation through container

---

## Dependency Injection Container

### Container Class

#### Methods

##### `getInstance(): Container`

Get singleton instance of the container.

```php
$container = Container::getInstance();
```

##### `bind(string $abstract, $concrete = null, bool $singleton = false): void`

Bind a service to the container.

```php
$container->bind('UserService', UserService::class);
$container->bind('Logger', function($container) {
    return new FileLogger('/path/to/log');
}, true); // singleton
```

##### `singleton(string $abstract, $concrete = null): void`

Bind a service as singleton.

```php
$container->singleton('Database', Database::class);
```

##### `instance(string $abstract, $instance): void`

Register an existing instance as singleton.

```php
$container->instance('Config', $configObject);
```

##### `resolve(string $abstract): mixed`

Resolve a service from the container.

```php
$userService = $container->resolve('UserService');
```

##### `alias(string $abstract, string $alias): void`

Create an alias for a service.

```php
$container->alias('UserService', 'users');
```

##### `bound(string $abstract): bool`

Check if service is bound.

```php
if ($container->bound('UserService')) {
    // Service is available
}
```

### Application Class

#### Methods

##### `getInstance(): Application`

Get singleton application instance.

##### `bootstrap(): void`

Bootstrap application with default service providers.

```php
$app = Application::getInstance();
$app->bootstrap();
```

##### `register(string $providerClass): void`

Register a service provider.

```php
$app->register(CustomServiceProvider::class);
```

##### `make(string $abstract): mixed`

Resolve service from container.

```php
$service = Application::resolve('ServiceName');
```

---

## HTTP Request/Response System

### Request Class

#### Properties

- `$method`: HTTP method (GET, POST, etc.)
- `$uri`: Request URI
- `$headers`: Request headers
- `$query`: Query parameters
- `$body`: Request body data
- `$files`: Uploaded files

#### Methods

##### `getMethod(): string`

Get HTTP method.

```php
$method = $request->getMethod(); // 'POST'
```

##### `getUri(): string`

Get request URI.

```php
$uri = $request->getUri(); // '/api/users'
```

##### `getHeader(string $name, string $default = null): ?string`

Get specific header.

```php
$contentType = $request->getHeader('Content-Type');
```

##### `getHeaders(): array`

Get all headers.

##### `input(string $key, $default = null): mixed`

Get input value from query or body.

```php
$name = $request->input('name', 'Anonymous');
```

##### `query(string $key, $default = null): mixed`

Get query parameter.

```php
$page = $request->query('page', 1);
```

##### `json(string $key = null, $default = null): mixed`

Get JSON data from request body.

```php
$data = $request->json(); // All JSON data
$name = $request->json('name'); // Specific field
```

##### `file(string $key): ?array`

Get uploaded file.

```php
$file = $request->file('avatar');
if ($file && $file['error'] === UPLOAD_ERR_OK) {
    // Process file
}
```

##### `validate(array $rules): array`

Validate request data.

```php
$validated = $request->validate([
    'name' => 'required|string|max:100',
    'email' => 'required|email',
    'age' => 'integer|min:18'
]);
```

### Response Class

#### Methods

##### `json(array $data, int $status = 200, array $headers = []): Response`

Create JSON response.

```php
return Response::json(['message' => 'Success'], 200);
```

##### `success(array $data = [], string $message = 'Success'): Response`

Create success response.

```php
return Response::success($userData, 'User created successfully');
```

##### `error(string $message, int $status = 400, array $errors = []): Response`

Create error response.

```php
return Response::error('Validation failed', 422, $validationErrors);
```

##### `notFound(string $message = 'Not Found'): Response`

Create 404 response.

```php
return Response::notFound('User not found');
```

##### `unauthorized(string $message = 'Unauthorized'): Response`

Create 401 response.

##### `forbidden(string $message = 'Forbidden'): Response`

Create 403 response.

##### `setHeader(string $name, string $value): Response`

Set response header.

```php
$response->setHeader('Cache-Control', 'no-cache');
```

##### `setCookie(string $name, string $value, array $options = []): Response`

Set cookie.

```php
$response->setCookie('session_id', $sessionId, [
    'expires' => time() + 3600,
    'httponly' => true,
    'secure' => true
]);
```

---

## Middleware Pipeline

### Middleware Base Class

#### Methods

##### `handle(Request $request, callable $next): Response`

Process the request. Must be implemented by concrete middleware.

```php
class AuthMiddleware extends Middleware
{
    public function handle(Request $request, callable $next): Response
    {
        if (!$this->isAuthenticated($request)) {
            return Response::unauthorized();
        }

        return $next($request);
    }
}
```

##### `getPriority(): int`

Get middleware priority (higher = earlier execution).

##### `shouldHandle(Request $request): bool`

Determine if middleware should handle the request.

### Built-in Middleware

#### CorsMiddleware

Handles Cross-Origin Resource Sharing.

```php
$corsMiddleware = new CorsMiddleware([
    'allowed_origins' => ['https://example.com'],
    'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE'],
    'allowed_headers' => ['Content-Type', 'Authorization']
]);
```

#### RateLimitMiddleware

Implements rate limiting.

```php
$rateLimitMiddleware = new RateLimitMiddleware([
    'max_requests' => 100,
    'window_minutes' => 60,
    'identifier' => 'ip' // or 'user'
]);
```

#### AuthMiddleware

Handles authentication.

```php
$authMiddleware = new AuthMiddleware([
    'required' => true,
    'roles' => ['admin', 'user']
]);
```

#### LoggingMiddleware

Logs requests and responses.

```php
$loggingMiddleware = new LoggingMiddleware([
    'log_requests' => true,
    'log_responses' => true,
    'log_level' => 'info'
]);
```

### MiddlewarePipeline

#### Methods

##### `add(Middleware $middleware, int $priority = 100): self`

Add middleware to pipeline.

```php
$pipeline->add(new AuthMiddleware(), 200)
         ->add(new CorsMiddleware(), 300)
         ->add(new RateLimitMiddleware(), 150);
```

##### `process(Request $request): Response`

Process request through middleware pipeline.

```php
$response = $pipeline->process($request);
```

---

## Event System

### Event Base Class

#### Properties

- `$data`: Event data
- `$timestamp`: Event timestamp
- `$eventId`: Unique event ID
- `$propagationStopped`: Whether propagation is stopped

#### Methods

##### `getData(string $key = null, $default = null): mixed`

Get event data.

```php
$userId = $event->getData('user_id');
$allData = $event->getData();
```

##### `setData(string $key, $value): self`

Set event data.

```php
$event->setData('processed', true);
```

##### `stopPropagation(): void`

Stop event propagation.

```php
$event->stopPropagation();
```

##### `isPropagationStopped(): bool`

Check if propagation is stopped.

### EventDispatcher

#### Methods

##### `getInstance(): EventDispatcher`

Get singleton instance.

##### `listen(string $eventName, $listener): self`

Register event listener.

```php
$dispatcher->listen('user.created', function($event) {
    // Handle user creation
});

$dispatcher->listen('user.*', $wildcardListener); // Wildcard
```

##### `dispatch($event, array $data = []): Event`

Dispatch an event.

```php
$event = $dispatcher->dispatch('user.created', ['user_id' => 123]);
$event = $dispatcher->dispatch(new UserCreatedEvent($user));
```

##### `queue($event, array $data = []): self`

Queue event for async dispatch.

```php
$dispatcher->queue('email.send', ['to' => 'user@example.com']);
```

##### `processQueue(): int`

Process queued events.

```php
$processed = $dispatcher->processQueue();
```

### Built-in Events

#### UserEvents

- `UserCreatedEvent`: User account created
- `UserUpdatedEvent`: User account updated
- `UserDeletedEvent`: User account deleted
- `UserLoginEvent`: User logged in
- `UserLogoutEvent`: User logged out

#### SystemEvents

- `ApplicationStartedEvent`: Application bootstrap completed
- `DatabaseQueryEvent`: Database query executed
- `CacheHitEvent`: Cache hit occurred
- `CacheMissEvent`: Cache miss occurred
- `ErrorEvent`: Error occurred
- `HttpRequestEvent`: HTTP request received
- `HttpResponseEvent`: HTTP response sent

---

## Caching System

### Cache Manager (Legacy Interface)

#### Methods

##### `get(string $key, $default = null): mixed`

Get cached value.

```php
$value = Cache::get('user:123', null);
```

##### `set(string $key, $value, int $ttl = 3600, array $tags = []): bool`

Store value in cache.

```php
Cache::set('user:123', $userData, 1800, ['users', 'user:123']);
```

##### `remember(string $key, callable $callback, int $ttl = 3600, array $tags = []): mixed`

Get from cache or execute callback.

```php
$user = Cache::remember('user:123', function() {
    return User::find(123);
}, 1800, ['users']);
```

##### `has(string $key): bool`

Check if key exists.

```php
if (Cache::has('user:123')) {
    // Key exists
}
```

##### `delete(string $key): bool`

Delete cached value.

```php
Cache::delete('user:123');
```

##### `flush(): int`

Clear all cache entries.

```php
$deleted = Cache::flush();
```

##### `invalidateTag(string $tag): int`

Invalidate entries by tag.

```php
$invalidated = Cache::invalidateTag('users');
```

### CacheManager (New System)

#### Methods

##### `driver(string $name = null): CacheInterface`

Get cache driver instance.

```php
$fileCache = Cache::driver('file');
$memoryCache = Cache::driver('memory');
```

##### `warm(array $data, int $ttl = 3600, array $tags = []): bool`

Warm cache with predefined data.

```php
Cache::warm([
    'config:app' => $appConfig,
    'config:db' => $dbConfig
], 7200, ['config']);
```

### Cache Drivers

#### FileDriver

File-based caching with atomic operations.

#### MemoryDriver

In-memory caching with eviction policies.

Configuration:

```php
$config = [
    'max_memory' => 50 * 1024 * 1024, // 50MB
    'eviction_policy' => 'lru' // lru, fifo, random
];
```

---

## Database Migration System

### Migration Base Class

#### Methods

##### `up(): void`

Run the migration. Must be implemented.

```php
class CreateUsersTable extends Migration
{
    public function up(): void
    {
        $this->schema->create('users', function($table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('created_at');
        });
    }
}
```

##### `down(): void`

Reverse the migration. Must be implemented.

```php
public function down(): void
{
    $this->schema->dropIfExists('users');
}
```

### SchemaBuilder

#### Methods

##### `create(string $table, callable $callback): void`

Create new table.

```php
$schema->create('posts', function($table) {
    $table->id();
    $table->string('title');
    $table->text('content');
    $table->foreignId('user_id')->references('id')->on('users');
    $table->timestamps();
});
```

##### `table(string $table, callable $callback): void`

Modify existing table.

```php
$schema->table('users', function($table) {
    $table->string('phone')->nullable();
    $table->index('email');
});
```

##### `drop(string $table): void`

Drop table.

##### `dropIfExists(string $table): void`

Drop table if exists.

### Column Types

```php
$table->id();                          // Auto-increment ID
$table->string('name', 100);           // VARCHAR(100)
$table->text('description');           // TEXT
$table->integer('count');              // INT
$table->decimal('price', 8, 2);        // DECIMAL(8,2)
$table->boolean('active');             // BOOLEAN
$table->timestamp('created_at');       // TIMESTAMP
$table->timestamps();                  // created_at, updated_at
$table->json('metadata');              // JSON column
```

### Indexes and Constraints

```php
$table->index('email');                // Regular index
$table->unique('email');               // Unique index
$table->primary(['user_id', 'role_id']); // Composite primary key
$table->foreign('user_id')->references('id')->on('users'); // Foreign key
```

---

## Core Entities

### Member Class

#### Properties

- `$id`: Member ID
- `$firstName`: First name
- `$lastName`: Last name
- `$email`: Email address
- `$phone`: Phone number
- `$dateOfBirth`: Date of birth
- `$membershipDate`: Membership start date
- `$status`: Member status (active, inactive, etc.)

#### Methods

##### `find(int $id): ?Member`

Find member by ID.

```php
$member = Member::find(123);
```

##### `findByEmail(string $email): ?Member`

Find member by email.

```php
$member = Member::findByEmail('john@example.com');
```

##### `getAll(array $filters = []): array`

Get all members with optional filters.

```php
$activeMembers = Member::getAll(['status' => 'active']);
```

##### `save(): bool`

Save member to database.

```php
$member = new Member();
$member->firstName = 'John';
$member->lastName = 'Doe';
$member->email = 'john@example.com';
$member->save();
```

##### `delete(): bool`

Delete member.

```php
$member->delete();
```

### Family Class

#### Methods

##### `getMembers(): array`

Get family members.

##### `addMember(Member $member): bool`

Add member to family.

### Group Class

#### Methods

##### `getMembers(): array`

Get group members.

##### `addMember(Member $member, string $role = 'member'): bool`

Add member to group.

---

## Authentication & Authorization

### Auth Class

#### Methods

##### `login(string $email, string $password): bool`

Authenticate user.

```php
if (Auth::login('user@example.com', 'password')) {
    // Login successful
}
```

##### `logout(): void`

Log out current user.

```php
Auth::logout();
```

##### `user(): ?Member`

Get current authenticated user.

```php
$currentUser = Auth::user();
```

##### `check(): bool`

Check if user is authenticated.

```php
if (Auth::check()) {
    // User is logged in
}
```

##### `hasRole(string $role): bool`

Check if user has specific role.

```php
if (Auth::hasRole('admin')) {
    // User is admin
}
```

##### `hasPermission(string $permission): bool`

Check if user has specific permission.

```php
if (Auth::hasPermission('users.create')) {
    // User can create users
}
```

---

## API Endpoints

### Authentication Endpoints

#### POST /api/auth/login

Authenticate user and create session.

**Request:**

```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

**Response:**

```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 123,
      "name": "John Doe",
      "email": "user@example.com"
    },
    "token": "session_token_here"
  }
}
```

#### POST /api/auth/logout

Log out current user.

**Response:**

```json
{
  "success": true,
  "message": "Logout successful"
}
```

### Member Endpoints

#### GET /api/members

Get list of members.

**Query Parameters:**

- `page`: Page number (default: 1)
- `limit`: Items per page (default: 20)
- `status`: Filter by status
- `search`: Search term

**Response:**

```json
{
  "success": true,
  "data": {
    "members": [
      {
        "id": 123,
        "firstName": "John",
        "lastName": "Doe",
        "email": "john@example.com",
        "status": "active"
      }
    ],
    "pagination": {
      "current_page": 1,
      "total_pages": 5,
      "total_items": 100
    }
  }
}
```

#### GET /api/members/{id}

Get specific member.

**Response:**

```json
{
  "success": true,
  "data": {
    "member": {
      "id": 123,
      "firstName": "John",
      "lastName": "Doe",
      "email": "john@example.com",
      "phone": "+1234567890",
      "dateOfBirth": "1990-01-01",
      "membershipDate": "2020-01-01",
      "status": "active"
    }
  }
}
```

#### POST /api/members

Create new member.

**Request:**

```json
{
  "firstName": "John",
  "lastName": "Doe",
  "email": "john@example.com",
  "phone": "+1234567890",
  "dateOfBirth": "1990-01-01"
}
```

**Response:**

```json
{
  "success": true,
  "message": "Member created successfully",
  "data": {
    "member": {
      "id": 124,
      "firstName": "John",
      "lastName": "Doe",
      "email": "john@example.com"
    }
  }
}
```

#### PUT /api/members/{id}

Update existing member.

#### DELETE /api/members/{id}

Delete member.

### Group Endpoints

#### GET /api/groups

Get list of groups.

#### GET /api/groups/{id}

Get specific group.

#### POST /api/groups

Create new group.

#### PUT /api/groups/{id}

Update existing group.

#### DELETE /api/groups/{id}

Delete group.

#### POST /api/groups/{id}/members

Add member to group.

#### DELETE /api/groups/{id}/members/{memberId}

Remove member from group.

---

## Error Handling

### Standard Error Response Format

```json
{
  "success": false,
  "message": "Error description",
  "errors": {
    "field_name": ["Validation error message"]
  },
  "code": "ERROR_CODE",
  "timestamp": "2025-01-01T12:00:00Z"
}
```

### HTTP Status Codes

- `200`: Success
- `201`: Created
- `400`: Bad Request
- `401`: Unauthorized
- `403`: Forbidden
- `404`: Not Found
- `422`: Validation Error
- `500`: Internal Server Error

---

## Configuration

### Environment Variables

```env
# Database
DB_HOST=localhost
DB_NAME=alivechms
DB_USER=username
DB_PASS=password

# Cache
CACHE_DRIVER=file
CACHE_DEFAULT_TTL=3600
CACHE_FALLBACK=true

# Application
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost

# Security
SESSION_LIFETIME=7200
BCRYPT_ROUNDS=12
```

### Service Providers

Register custom service providers in `Application::bootstrap()`:

```php
$app->register(CustomServiceProvider::class);
```

---

## Testing

### Running Tests

```bash
# All tests
php run-tests.php

# Specific test suites
php test-container-comprehensive.php
php test-http.php
php test-middleware.php
php test-events.php
php test-cache.php
php test-migrations.php
```

### Writing Tests

```php
class CustomTest
{
    public function testSomething(): void
    {
        $this->assert($condition, 'Test description');
    }

    private function assert(bool $condition, string $message): void
    {
        if ($condition) {
            echo "✓ {$message}\n";
        } else {
            echo "✗ {$message}\n";
        }
    }
}
```

---

## Performance Considerations

### Caching Strategy

- Use memory cache for frequently accessed data
- Use file cache for persistent data
- Implement cache warming for critical data
- Use tag-based invalidation for related data

### Database Optimization

- Use eager loading to prevent N+1 queries
- Implement proper indexing
- Use query builder for complex queries
- Cache expensive queries

### Memory Management

- Configure appropriate memory limits for cache drivers
- Use eviction policies to prevent memory exhaustion
- Monitor memory usage in production

---

## Security Best Practices

### Input Validation

- Always validate user input
- Use prepared statements for database queries
- Sanitize output data
- Implement CSRF protection

### Authentication

- Use secure password hashing (bcrypt)
- Implement session management
- Use HTTPS in production
- Implement rate limiting

### Authorization

- Use role-based access control
- Implement permission checks
- Validate user permissions on every request
- Use middleware for authentication/authorization

---

This documentation provides a comprehensive guide to the AliveChMS API and architecture. For specific implementation details, refer to the source code and individual component documentation files.
