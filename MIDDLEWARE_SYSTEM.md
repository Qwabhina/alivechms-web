# Middleware Pipeline System

The AliveChMS middleware pipeline system provides a flexible, powerful way to filter HTTP requests and responses. It implements the chain of responsibility pattern, allowing you to compose request processing logic in a clean, reusable manner.

## Overview

The middleware system consists of several key components:

- **Middleware Base Class**: Abstract base for all middleware
- **MiddlewarePipeline**: Orchestrates middleware execution
- **Built-in Middleware**: Common middleware implementations
- **EnhancedHttpRoute**: Route class with middleware integration

## Core Components

### Middleware Base Class

All middleware extends the `Middleware` abstract class:

```php
abstract class Middleware
{
    abstract public function handle(Request $request, callable $next): Response;

    public function shouldExecute(Request $request): bool { return true; }
    public function getPriority(): int { return 100; }
    public function getName(): string { return static::class; }
}
```

### MiddlewarePipeline

The pipeline manages middleware execution:

```php
$pipeline = new MiddlewarePipeline();
$pipeline->add($middleware);
$pipeline->addGlobal($globalMiddleware);

$response = $pipeline->execute($request, $destination);
```

## Built-in Middleware

### CorsMiddleware

Handles Cross-Origin Resource Sharing:

```php
$corsMiddleware = new CorsMiddleware([
    'allowed_origins' => ['https://app.example.com'],
    'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE'],
    'allowed_headers' => ['Content-Type', 'Authorization'],
    'allow_credentials' => true
]);
```

### RateLimitMiddleware

Implements rate limiting using the existing RateLimiter:

```php
$rateLimiter = Application::resolve('RateLimiter');
$rateLimitMiddleware = new RateLimitMiddleware($rateLimiter, 60, 1); // 60 requests per minute
```

### AuthMiddleware

Handles authentication and authorization:

```php
$auth = Application::resolve('Auth');
$authMiddleware = new AuthMiddleware($auth, [
    'roles' => ['user', 'admin'],
    'permissions' => ['api.access']
]);
```

### LoggingMiddleware

Logs HTTP requests and responses:

```php
$loggingMiddleware = new LoggingMiddleware([
    'log_requests' => true,
    'log_responses' => true,
    'log_headers' => false,
    'sanitize_sensitive' => true
]);
```

## Usage Examples

### Basic Middleware

Create a simple middleware:

```php
class TimingMiddleware extends Middleware
{
    public function handle(Request $request, callable $next): Response
    {
        $start = microtime(true);

        $response = $next($request);

        $duration = microtime(true) - $start;
        $response->header('X-Response-Time', $duration . 'ms');

        return $response;
    }
}
```

### Enhanced HTTP Route

Use middleware in routes:

```php
class ApiRoute extends EnhancedHttpRoute
{
    protected function registerMiddleware(): void
    {
        $this->enableCors()
            ->enableRateLimit(100, 1)
            ->requireAuth(['roles' => ['user']])
            ->enableLogging();
    }

    public function handle(): Response
    {
        return $this->success(['message' => 'API endpoint']);
    }
}
```

### Conditional Middleware

Execute middleware based on conditions:

```php
class ConditionalMiddleware extends Middleware
{
    public function shouldExecute(Request $request): bool
    {
        return str_contains($request->getPath(), 'admin');
    }

    public function handle(Request $request, callable $next): Response
    {
        // Only executes for admin paths
        return $next($request);
    }
}
```

### Pipeline Configuration

Configure complex pipelines:

```php
$pipeline = new MiddlewarePipeline();

// Global middleware (executes for all requests)
$pipeline->addGlobal(new CorsMiddleware());
$pipeline->addGlobal(new LoggingMiddleware());

// Route-specific middleware
$pipeline->add(new RateLimitMiddleware($rateLimiter, 30, 1));
$pipeline->add(new AuthMiddleware($auth));

// Conditional middleware
$conditionalPipeline = $pipeline->when(function (Request $request) {
    return $request->isAjax();
});
$conditionalPipeline->add(new AjaxMiddleware());
```

## Middleware Priority

Middleware executes in priority order (lower numbers first):

```php
class HighPriorityMiddleware extends Middleware
{
    public function getPriority(): int
    {
        return 10; // Executes early
    }
}

class LowPriorityMiddleware extends Middleware
{
    public function getPriority(): int
    {
        return 90; // Executes late
    }
}
```

## Error Handling

Middleware can handle errors:

```php
class ErrorHandlingMiddleware extends Middleware
{
    public function handle(Request $request, callable $next): Response
    {
        try {
            return $next($request);
        } catch (ValidationException $e) {
            return Response::validationError('Validation failed', $e->getErrors());
        } catch (Exception $e) {
            return Response::error('Internal server error', 500);
        }
    }
}
```

## Performance Monitoring

Monitor middleware performance:

```php
$result = $pipeline->executeWithTiming($request, $destination);

echo "Execution time: " . $result['execution_time'] . "s\n";
echo "Memory used: " . $result['memory_used'] . " bytes\n";
echo "Middleware count: " . $result['middleware_count'] . "\n";
```

## Best Practices

### 1. Keep Middleware Focused

Each middleware should have a single responsibility:

```php
// Good: Focused on authentication
class AuthMiddleware extends Middleware { ... }

// Bad: Doing too much
class AuthAndLoggingAndCorsMiddleware extends Middleware { ... }
```

### 2. Use Appropriate Priorities

Set priorities based on dependencies:

```php
class CorsMiddleware extends Middleware
{
    public function getPriority(): int { return 10; } // Early
}

class AuthMiddleware extends Middleware
{
    public function getPriority(): int { return 30; } // After CORS
}

class LoggingMiddleware extends Middleware
{
    public function getPriority(): int { return 90; } // Late
}
```

### 3. Handle Errors Gracefully

Always handle potential errors:

```php
class SafeMiddleware extends Middleware
{
    public function handle(Request $request, callable $next): Response
    {
        try {
            return $next($request);
        } catch (Exception $e) {
            return $this->handleError($e, $request);
        }
    }
}
```

### 4. Use Conditional Execution

Avoid unnecessary processing:

```php
class ApiOnlyMiddleware extends Middleware
{
    public function shouldExecute(Request $request): bool
    {
        return str_starts_with($request->getPath(), 'api/');
    }
}
```

## Integration with Existing Code

The middleware system integrates seamlessly with existing AliveChMS components:

### With Authentication

```php
$authMiddleware = new AuthMiddleware(Application::resolve('Auth'));
```

### With Rate Limiting

```php
$rateLimitMiddleware = new RateLimitMiddleware(Application::resolve('RateLimiter'));
```

### With Dependency Injection

```php
class CustomMiddleware extends Middleware
{
    private SomeService $service;

    public function __construct()
    {
        $this->service = Application::resolve('SomeService');
    }
}
```

## Testing

Test middleware in isolation:

```php
public function testCustomMiddleware()
{
    $middleware = new CustomMiddleware();
    $request = Request::create('/test');

    $response = $middleware->execute($request, function ($request) {
        return Response::success();
    });

    $this->assertEquals(200, $response->getStatusCode());
}
```

## Migration from Existing Code

To migrate existing routes to use middleware:

### Before (BaseHttpRoute)

```php
class OldRoute extends BaseHttpRoute
{
    public static function handle()
    {
        self::authenticate();
        self::rateLimit();

        // Route logic
        return self::success(['data' => 'result']);
    }
}
```

### After (EnhancedHttpRoute)

```php
class NewRoute extends EnhancedHttpRoute
{
    protected function registerMiddleware(): void
    {
        $this->requireAuth()
            ->enableRateLimit();
    }

    public function handle(): Response
    {
        // Route logic
        return $this->success(['data' => 'result']);
    }
}
```

## Conclusion

The middleware pipeline system provides:

- **Separation of Concerns**: Each middleware handles one aspect
- **Reusability**: Middleware can be shared across routes
- **Flexibility**: Easy to add, remove, or reorder middleware
- **Testability**: Middleware can be tested in isolation
- **Performance**: Conditional execution and monitoring
- **Integration**: Works with existing AliveChMS components

This system makes HTTP request processing more modular, maintainable, and powerful while maintaining backward compatibility with existing code.
