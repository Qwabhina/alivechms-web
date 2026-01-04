# Dependency Injection Container Guide

## Overview

AliveChMS now includes a lightweight dependency injection (DI) container that helps reduce tight coupling between classes and improves testability.

## Benefits of Dependency Injection

✅ **Reduced Coupling**: Classes don't directly instantiate their dependencies  
✅ **Improved Testability**: Easy to mock dependencies in unit tests  
✅ **Better Maintainability**: Changes to dependencies don't require code changes  
✅ **Centralized Configuration**: All service bindings in one place  
✅ **Automatic Resolution**: Container automatically resolves constructor dependencies

## Basic Usage

### 1. Binding Services

```php
// Basic binding
Application::bind('ServiceName', ServiceClass::class);

// Singleton binding (same instance every time)
Application::singleton('Database', Database::class);

// Closure binding
Application::bind('CustomService', function ($container) {
    return new CustomService($container->resolve('Database'));
});

// Instance binding (existing object)
$instance = new MyService();
Application::instance('MyService', $instance);
```

### 2. Resolving Services

```php
// Resolve a service
$database = Application::resolve('Database');
$orm = Application::resolve('ORM');

// Services are automatically injected into constructors
class MyController {
    public function __construct(Database $db, ORM $orm) {
        // Dependencies automatically injected
    }
}
```

## Service Providers

Service providers organize related service registrations:

```php
class MyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->singleton('MyService', function ($container) {
            return new MyService($container->resolve('Database'));
        });
    }

    public function boot(): void
    {
        // Called after all providers are registered
        // Use for initialization that depends on other services
    }
}
```

## Pre-Registered Services

The following services are automatically registered:

| Service        | Type      | Description              |
| -------------- | --------- | ------------------------ |
| `Database`     | Singleton | Database connection      |
| `ORM`          | Factory   | Object-relational mapper |
| `QueryBuilder` | Factory   | Fluent query builder     |
| `Auth`         | Singleton | Authentication service   |
| `Cache`        | Singleton | Caching service          |
| `RateLimiter`  | Singleton | Rate limiting service    |
| `Settings`     | Singleton | Application settings     |
| `Validator`    | Factory   | Input validation         |
| `AuditLog`     | Factory   | Audit logging            |

## Using DI in Route Classes

### Old Way (Tight Coupling)

```php
class MemberRoutes extends BaseRoute
{
    public static function handle(): void
    {
        // Direct instantiation - hard to test
        $orm = new ORM();
        $member = new Member();

        // Hard-coded dependencies
        $result = $member->getAll();
        self::success($result);
    }
}
```

### New Way (Dependency Injection)

```php
class MemberRoutes extends BaseRouteWithDI
{
    public static function handle(): void
    {
        // Resolve from container - easy to test
        $memberService = Application::resolve('Member');

        // Dependencies automatically injected
        $result = $memberService->getAll();
        self::success($result);
    }
}
```

## Testing with DI

DI makes testing much easier:

```php
class MemberServiceTest extends TestCase
{
    public function testGetAllMembers(): void
    {
        // Mock dependencies
        $mockORM = $this->createMock(ORM::class);
        $mockORM->method('getAll')->willReturn(['test_data']);

        // Bind mock to container
        Application::bind('ORM', $mockORM);

        // Test the service
        $memberService = Application::resolve('Member');
        $result = $memberService->getAll();

        $this->assertEquals(['test_data'], $result);
    }
}
```

## Migration Strategy

### Phase 1: Gradual Adoption

- ✅ DI container implemented
- ✅ Service providers created
- ✅ Core services registered
- 🔄 **Current**: Existing code still works (backward compatible)

### Phase 2: Route Refactoring (Next)

- Update route classes to use `BaseRouteWithDI`
- Replace direct instantiation with container resolution
- Add constructor injection to entity classes

### Phase 3: Full Migration

- Remove legacy includes
- Update all classes to use DI
- Add comprehensive test coverage

## Advanced Features

### Automatic Constructor Injection

```php
class UserService
{
    public function __construct(
        private Database $db,
        private Cache $cache,
        private AuditLog $audit
    ) {
        // Dependencies automatically injected
    }
}

// Register and resolve
Application::bind('UserService', UserService::class);
$userService = Application::resolve('UserService'); // All dependencies injected
```

### Interface Binding

```php
// Bind interface to implementation
Application::bind('LoggerInterface', FileLogger::class);

class MyService
{
    public function __construct(LoggerInterface $logger) {
        // FileLogger will be injected
    }
}
```

### Circular Dependency Detection

The container automatically detects and prevents circular dependencies:

```php
// This will throw an exception
Application::bind('A', function ($c) { return $c->resolve('B'); });
Application::bind('B', function ($c) { return $c->resolve('A'); });
Application::resolve('A'); // Exception: Circular dependency detected
```

## Best Practices

1. **Use Interfaces**: Bind interfaces to implementations for better flexibility
2. **Prefer Constructor Injection**: Let the container handle dependency injection
3. **Use Singletons Wisely**: Only for stateless services (Database, Cache, etc.)
4. **Organize with Service Providers**: Group related services together
5. **Test with Mocks**: Use DI to inject mocks in tests

## Troubleshooting

### Common Issues

**Service Not Found**

```php
// Error: Service 'MyService' not bound
$service = Application::resolve('MyService');

// Solution: Bind the service first
Application::bind('MyService', MyService::class);
```

**Constructor Parameter Cannot Be Resolved**

```php
// Error: Cannot resolve parameter 'config'
class MyService {
    public function __construct(array $config) {} // No type hint for array
}

// Solution: Use default value or bind explicitly
class MyService {
    public function __construct(array $config = []) {} // Default value
}
```

**Circular Dependencies**

```php
// Error: Circular dependency detected
// Solution: Refactor to remove circular dependency or use setter injection
```

## Performance Considerations

- **Singleton Services**: Resolved once, reused multiple times
- **Factory Services**: New instance created each time
- **Lazy Loading**: Services only created when needed
- **Minimal Overhead**: Container adds negligible performance impact

The DI container provides a solid foundation for building maintainable, testable applications while maintaining backward compatibility with existing code.
