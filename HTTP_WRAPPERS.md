# HTTP Request/Response Wrapper Classes

## Overview

AliveChMS now includes clean, testable HTTP wrapper classes that provide a modern interface for handling HTTP requests and responses.

## Benefits

✅ **Clean API**: Intuitive methods for accessing request data  
✅ **Testable**: Easy to create mock requests for unit tests  
✅ **Type Safety**: Proper type hints and return types  
✅ **Consistent Responses**: Standardized JSON response format  
✅ **Better Error Handling**: Structured error responses  
✅ **Security**: Built-in input validation and sanitization

## Request Class

### Creating Requests

```php
// From globals (production)
$request = Request::createFromGlobals();

// For testing
$request = Request::create('/api/users?page=1', 'POST', ['name' => 'John']);
```

### Accessing Request Data

```php
// HTTP method and path
$method = $request->getMethod(); // GET, POST, PUT, DELETE
$path = $request->getPath(); // 'api/users'
$uri = $request->getUri(); // '/api/users?page=1'

// Query parameters
$page = $request->query('page'); // Get specific parameter
$allQuery = $request->query(); // Get all query parameters

// POST data
$name = $request->post('name');
$allPost = $request->post();

// Combined input (POST + JSON)
$name = $request->input('name');
$email = $request->input('email', 'default@example.com');

// Get all input data
$data = $request->all();

// Get only specific keys
$userData = $request->only(['name', 'email', 'age']);

// Get all except specific keys
$safeData = $request->except(['password', 'password_confirmation']);
```

### JSON Handling

```php
// Check if request contains JSON
if ($request->isJson()) {
    $data = $request->json(); // Parse JSON body
    $name = $request->input('name'); // Works with JSON data too
}
```

### Headers and Authentication

```php
// Get headers
$contentType = $request->header('content-type');
$userAgent = $request->userAgent();

// Bearer token extraction
$token = $request->bearerToken(); // Extracts from "Authorization: Bearer xxx"

// Client information
$ip = $request->ip(); // Proxy-aware IP detection
$isSecure = $request->isSecure(); // HTTPS check
$isAjax = $request->isAjax(); // XMLHttpRequest check
```

### File Uploads

```php
// Check for uploaded files
if ($request->hasFile('profile_picture')) {
    $file = $request->file('profile_picture');
    // Handle file upload
}

$allFiles = $request->files();
```

### Validation

```php
// Validate input with automatic error handling
try {
    $data = $request->validate([
        'name' => 'required|max:50',
        'email' => 'required|email',
        'age' => 'numeric|min:18'
    ]);
} catch (ValidationException $e) {
    // Handle validation errors
    $errors = $e->getErrors();
}
```

### Route Parameters

```php
// Set route parameters (typically done by router)
$request->setRouteParams(['id' => '123', 'slug' => 'example']);

// Access route parameters
$id = $request->route('id');
$slug = $request->route('slug', 'default');
```

## Response Class

### Creating Responses

```php
// Basic response
$response = Response::make('Hello World', 200);

// JSON response
$response = Response::json(['message' => 'Success'], 200);

// Success response (standardized format)
$response = Response::success($data, 'Operation completed', 201);

// Error response
$response = Response::error('Something went wrong', 400, $errors);
```

### Specialized Responses

```php
// Common HTTP responses
$response = Response::notFound('User not found');
$response = Response::unauthorized('Token required');
$response = Response::forbidden('Access denied');
$response = Response::validationError('Invalid input', $errors);

// Paginated response
$response = Response::paginated($data, $total, $page, $limit);

// Redirect response
$response = Response::redirect('https://example.com', 302);

// Rate limited response
$response = Response::rateLimited('Too many requests', 60);
```

### Response Modification

```php
$response = Response::success($data)
    ->header('X-Custom-Header', 'value')
    ->contentType('application/json')
    ->cookie('session_id', 'abc123', 3600)
    ->cors('https://example.com')
    ->noCache();
```

### Sending Responses

```php
// Send response
$response->send();

// Send and exit (common in routes)
$response->sendAndExit();

// Check if sent
if ($response->isSent()) {
    // Response already sent
}
```

## Using in Route Classes

### Old Way (Direct HTTP Handling)

```php
class MemberRoutes extends BaseRoute
{
    public static function handle(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $input = json_decode(file_get_contents('php://input'), true);

        if ($method === 'POST') {
            // Manual validation
            if (empty($input['name'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Name required']);
                exit;
            }

            // Process request...

            http_response_code(201);
            echo json_encode(['status' => 'success', 'data' => $result]);
            exit;
        }
    }
}
```

### New Way (Request/Response Objects)

```php
class MemberRoutes extends BaseHttpRoute
{
    public static function handle(): void
    {
        $request = self::request();

        match ($request->getMethod()) {
            'POST' => self::executeWithExceptionHandling(function () {
                // Clean validation
                $data = self::validate([
                    'name' => 'required|max:50',
                    'email' => 'required|email'
                ]);

                // Process request...

                // Clean response
                self::created($result, 'Member created')->sendAndExit();
            }),

            default => self::notFound()->sendAndExit()
        };
    }
}
```

## Testing with Request/Response Objects

### Testing Requests

```php
class MemberControllerTest extends TestCase
{
    public function testCreateMember(): void
    {
        // Create mock request
        $request = Request::create('/api/members', 'POST', [
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]);

        // Set request in route
        MemberRoutes::setRequest($request);

        // Test the route
        ob_start();
        MemberRoutes::handle();
        $output = ob_get_clean();

        $response = json_decode($output, true);
        $this->assertEquals('success', $response['status']);
    }
}
```

### Testing Responses

```php
public function testSuccessResponse(): void
{
    $data = ['id' => 1, 'name' => 'John'];
    $response = Response::success($data, 'User created', 201);

    $this->assertEquals(201, $response->getStatusCode());

    $content = json_decode($response->getContent(), true);
    $this->assertEquals('success', $content['status']);
    $this->assertEquals('User created', $content['message']);
    $this->assertEquals($data, $content['data']);
}
```

## Standard Response Format

All API responses follow a consistent format:

### Success Response

```json
{
  "status": "success",
  "message": "Operation completed",
  "data": { ... },
  "timestamp": "2025-01-01T10:30:00+00:00"
}
```

### Error Response

```json
{
  "status": "error",
  "message": "Something went wrong",
  "code": 400,
  "errors": {
    "field": ["Error message"]
  },
  "timestamp": "2025-01-01T10:30:00+00:00"
}
```

### Paginated Response

```json
{
  "status": "success",
  "message": "Success",
  "data": {
    "data": [ ... ],
    "pagination": {
      "page": 1,
      "limit": 10,
      "total": 100,
      "pages": 10
    }
  },
  "timestamp": "2025-01-01T10:30:00+00:00"
}
```

## Migration Strategy

### Phase 1: Gradual Adoption

- ✅ Request/Response classes implemented
- ✅ BaseHttpRoute created for new routes
- 🔄 **Current**: Existing routes still work (backward compatible)

### Phase 2: Route Refactoring

- Update route classes to extend BaseHttpRoute
- Replace direct $_GET/$\_POST access with Request methods
- Replace manual JSON responses with Response methods

### Phase 3: Full Migration

- Remove legacy BaseRoute class
- Update all routes to use Request/Response objects
- Add comprehensive integration tests

## Best Practices

1. **Use Request Methods**: Always use `$request->input()` instead of `$_POST`
2. **Validate Early**: Use `$request->validate()` at the start of routes
3. **Consistent Responses**: Use Response helper methods for consistent format
4. **Handle Exceptions**: Use `executeWithExceptionHandling()` for automatic error handling
5. **Test with Mocks**: Create Request objects for unit testing

## Advanced Features

### Custom Validation Rules

```php
// In a custom validator
$request->validate([
    'email' => 'required|email|unique:users',
    'password' => 'required|min:8|confirmed'
]);
```

### File Upload Handling

```php
if ($request->hasFile('avatar')) {
    $file = $request->file('avatar');

    // Validate file
    if ($file['size'] > 5 * 1024 * 1024) {
        return Response::error('File too large', 400);
    }

    // Process upload...
}
```

### CORS Handling

```php
// Handle preflight requests
if ($request->getMethod() === 'OPTIONS') {
    return Response::make('', 204)->cors()->sendAndExit();
}

// Add CORS to response
return Response::success($data)->cors('https://example.com');
```

The Request/Response wrapper classes provide a solid foundation for building clean, testable HTTP APIs while maintaining backward compatibility with existing code.
