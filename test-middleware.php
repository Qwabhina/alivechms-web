<?php

/**
 * Middleware Pipeline System Test
 * 
 * Comprehensive test of the middleware pipeline system including
 * various middleware types, execution order, and integration.
 */

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/core/Application.php';
require_once __DIR__ . '/core/Http/MiddlewarePipeline.php';
require_once __DIR__ . '/core/Http/Middleware/CorsMiddleware.php';
require_once __DIR__ . '/core/Http/Middleware/RateLimitMiddleware.php';
require_once __DIR__ . '/core/Http/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/core/Http/Middleware/LoggingMiddleware.php';
require_once __DIR__ . '/examples/MiddlewareExampleRoute.php';

echo "🔧 Middleware Pipeline System Test\n";
echo "=" . str_repeat("=", 50) . "\n\n";

$testsPassed = 0;
$testsTotal = 0;

function runTest(string $description, callable $test): void
{
   global $testsPassed, $testsTotal;
   $testsTotal++;

   try {
      $test();
      echo "✅ $description\n";
      $testsPassed++;
   } catch (Exception $e) {
      echo "❌ $description\n";
      echo "   Error: " . $e->getMessage() . "\n";
   }
}

// Initialize application
$app = Application::getInstance();
$app->bootstrap();

// Test 1: Basic Middleware Pipeline
runTest("Basic middleware pipeline creation and execution", function () {
   $pipeline = new MiddlewarePipeline();

   $testMiddleware = new class extends Middleware {
      public function handle(Request $request, callable $next): Response
      {
         $response = $next($request);
         $response->header('X-Test', 'middleware-executed');
         return $response;
      }
   };

   $pipeline->add($testMiddleware);

   $request = Request::create('/test');
   $response = $pipeline->execute($request, function ($request) {
      return Response::success(['message' => 'Hello World']);
   });

   assert($response->getHeader('X-Test') === 'middleware-executed', 'Middleware not executed');
   assert($response->getStatusCode() === 200, 'Wrong status code');
});

// Test 2: Middleware Priority and Execution Order
runTest("Middleware priority and execution order", function () {
   $pipeline = new MiddlewarePipeline();
   $executionOrder = [];

   $middleware1 = new class($executionOrder) extends Middleware {
      private array $order;
      public function __construct(array &$order)
      {
         $this->order = &$order;
      }
      public function handle(Request $request, callable $next): Response
      {
         $this->order[] = 'middleware1';
         return $next($request);
      }
      public function getPriority(): int
      {
         return 30;
      }
   };

   $middleware2 = new class($executionOrder) extends Middleware {
      private array $order;
      public function __construct(array &$order)
      {
         $this->order = &$order;
      }
      public function handle(Request $request, callable $next): Response
      {
         $this->order[] = 'middleware2';
         return $next($request);
      }
      public function getPriority(): int
      {
         return 10;
      }
   };

   $middleware3 = new class($executionOrder) extends Middleware {
      private array $order;
      public function __construct(array &$order)
      {
         $this->order = &$order;
      }
      public function handle(Request $request, callable $next): Response
      {
         $this->order[] = 'middleware3';
         return $next($request);
      }
      public function getPriority(): int
      {
         return 20;
      }
   };

   $pipeline->add($middleware1);
   $pipeline->add($middleware2);
   $pipeline->add($middleware3);

   $request = Request::create('/test');
   $pipeline->execute($request, function ($request) use (&$executionOrder) {
      $executionOrder[] = 'destination';
      return Response::success();
   });

   assert(
      $executionOrder === ['middleware2', 'middleware3', 'middleware1', 'destination'],
      'Wrong execution order: ' . implode(', ', $executionOrder)
   );
});

// Test 3: CORS Middleware
runTest("CORS middleware functionality", function () {
   $pipeline = new MiddlewarePipeline();

   $corsMiddleware = new CorsMiddleware([
      'allowed_origins' => ['https://example.com'],
      'allowed_methods' => ['GET', 'POST', 'PUT'],
      'allowed_headers' => ['Content-Type', 'Authorization']
   ]);

   $pipeline->add($corsMiddleware);

   // Test preflight request
   $preflightRequest = Request::create('/api/test', 'OPTIONS', [], [
      'HTTP_ORIGIN' => 'https://example.com'
   ]);

   $response = $pipeline->execute($preflightRequest, function ($request) {
      return Response::success();
   });

   assert($response->getStatusCode() === 200, 'Preflight failed');
   assert($response->getHeader('Access-Control-Allow-Origin') === 'https://example.com', 'CORS origin not set');
   assert(str_contains($response->getHeader('Access-Control-Allow-Methods'), 'GET'), 'CORS methods not set');
});

// Test 4: Rate Limiting Middleware
runTest("Rate limiting middleware functionality", function () {
   $pipeline = new MiddlewarePipeline();

   $rateLimitMiddleware = new RateLimitMiddleware(null, 5, 1);

   $pipeline->add($rateLimitMiddleware);

   $request = Request::create('/api/test');
   $response = $pipeline->execute($request, function ($request) {
      return Response::success(['message' => 'API call successful']);
   });

   assert($response->getStatusCode() === 200, 'Rate limit failed');
   assert($response->getHeader('X-RateLimit-Limit') === '5', 'Rate limit header not set');
   assert(is_numeric($response->getHeader('X-RateLimit-Remaining')), 'Rate limit remaining not set');
});

// Test 5: Authentication Middleware (Mock)
runTest("Authentication middleware functionality", function () {
   $pipeline = new MiddlewarePipeline();

   // Create mock auth that always validates successfully
   $mockAuth = new class {
      public function validateToken($token)
      {
         if ($token === 'valid-token') {
            return ['id' => 1, 'username' => 'testuser', 'roles' => ['user']];
         }
         return null;
      }
   };

   $authMiddleware = new AuthMiddleware($mockAuth);
   $pipeline->add($authMiddleware);

   // Test with valid token
   $request = Request::create('/api/protected', 'GET', [], [
      'HTTP_AUTHORIZATION' => 'Bearer valid-token'
   ]);

   $response = $pipeline->execute($request, function ($request) {
      $user = $request->route('authenticated_user');
      assert($user['username'] === 'testuser', 'User not authenticated');
      return Response::success(['user' => $user['username']]);
   });

   assert($response->getStatusCode() === 200, 'Authentication failed');
});

// Test 6: Authentication Middleware - Unauthorized
runTest("Authentication middleware - unauthorized access", function () {
   $pipeline = new MiddlewarePipeline();

   $mockAuth = new class {
      public function validateToken($token)
      {
         return null; // Always fail
      }
   };

   $authMiddleware = new AuthMiddleware($mockAuth);
   $pipeline->add($authMiddleware);

   $request = Request::create('/api/protected', 'GET', [], [
      'HTTP_AUTHORIZATION' => 'Bearer invalid-token'
   ]);

   $response = $pipeline->execute($request, function ($request) {
      return Response::success();
   });

   assert($response->getStatusCode() === 401, 'Should be unauthorized');
});

// Test 7: Multiple Middleware Integration
runTest("Multiple middleware integration", function () {
   $pipeline = new MiddlewarePipeline();

   // Add CORS (priority 10)
   $corsMiddleware = new CorsMiddleware();
   $pipeline->add($corsMiddleware);

   // Add rate limiting (priority 20)
   $rateLimitMiddleware = new RateLimitMiddleware(null, 10, 1);
   $pipeline->add($rateLimitMiddleware);

   // Add custom middleware (priority 50)
   $customMiddleware = new class extends Middleware {
      public function handle(Request $request, callable $next): Response
      {
         $response = $next($request);
         $response->header('X-Custom', 'processed');
         return $response;
      }
      public function getPriority(): int
      {
         return 50;
      }
   };
   $pipeline->add($customMiddleware);

   $request = Request::create('/api/test', 'GET', [], [
      'HTTP_ORIGIN' => 'http://localhost'
   ]);

   $response = $pipeline->execute($request, function ($request) {
      return Response::success(['message' => 'All middleware executed']);
   });

   assert($response->getStatusCode() === 200, 'Pipeline failed');
   assert($response->getHeader('X-Custom') === 'processed', 'Custom middleware not executed');
   assert($response->getHeader('X-RateLimit-Limit') === '10', 'Rate limit middleware not executed');
   assert($response->getHeader('Access-Control-Allow-Origin') === 'http://localhost', 'CORS middleware not executed');
});

// Test 8: Conditional Middleware
runTest("Conditional middleware execution", function () {
   $pipeline = new MiddlewarePipeline();

   $conditionalMiddleware = new class extends Middleware {
      public function handle(Request $request, callable $next): Response
      {
         $response = $next($request);
         $response->header('X-Conditional', 'executed');
         return $response;
      }

      public function shouldExecute(Request $request): bool
      {
         return str_contains($request->getPath(), 'special');
      }
   };

   $pipeline->add($conditionalMiddleware);

   // Should execute
   $request1 = Request::create('/api/special/endpoint');
   $response1 = $pipeline->execute($request1, function ($request) {
      return Response::success();
   });
   assert($response1->getHeader('X-Conditional') === 'executed', 'Conditional middleware should execute');

   // Should not execute
   $request2 = Request::create('/api/normal/endpoint');
   $response2 = $pipeline->execute($request2, function ($request) {
      return Response::success();
   });
   assert($response2->getHeader('X-Conditional') === null, 'Conditional middleware should not execute');
});

// Test 9: Pipeline Timing and Performance
runTest("Pipeline timing and performance monitoring", function () {
   $pipeline = new MiddlewarePipeline();

   // Add some middleware with artificial delays
   $slowMiddleware = new class extends Middleware {
      public function handle(Request $request, callable $next): Response
      {
         usleep(1000); // 1ms delay
         return $next($request);
      }
   };

   $pipeline->add($slowMiddleware);

   $request = Request::create('/test');
   $result = $pipeline->executeWithTiming($request, function ($request) {
      usleep(500); // 0.5ms delay in destination
      return Response::success();
   });

   assert(isset($result['response']), 'Response not returned');
   assert(isset($result['execution_time']), 'Execution time not measured');
   assert(isset($result['memory_used']), 'Memory usage not measured');
   assert(isset($result['middleware_count']), 'Middleware count not returned');

   assert($result['execution_time'] > 0, 'Execution time should be positive');
   assert($result['middleware_count'] === 1, 'Wrong middleware count');
});

// Test 10: Enhanced HTTP Route Integration
runTest("Enhanced HTTP route with middleware integration", function () {
   // Create a test route
   $route = new class extends EnhancedHttpRoute {
      protected function registerMiddleware(): void
      {
         $this->enableCors(['allowed_origins' => ['*']]);
         $this->enableRateLimit(100, 1);
      }

      public function handle(): Response
      {
         return $this->success([
            'message' => 'Enhanced route working',
            'middleware_count' => $this->getMiddleware()->count(),
            'middleware_order' => $this->getMiddlewareExecutionOrder()
         ]);
      }
   };

   // Execute with timing
   $result = $route->executeWithTiming();

   assert(isset($result['response']), 'Response not returned');
   assert($result['response']->getStatusCode() === 200, 'Wrong status code');
   assert($result['middleware_count'] > 0, 'No middleware executed');

   $content = json_decode($result['response']->getContent(), true);
   assert($content['status'] === 'success', 'Wrong response format');
   assert(isset($content['data']['middleware_count']), 'Middleware count not in response');
});

// Test 11: Error Handling in Middleware
runTest("Error handling in middleware pipeline", function () {
   $pipeline = new MiddlewarePipeline();

   $errorMiddleware = new class extends Middleware {
      public function handle(Request $request, callable $next): Response
      {
         throw new Exception('Middleware error test');
      }
   };

   $pipeline->add($errorMiddleware);

   $request = Request::create('/test');
   $response = $pipeline->execute($request, function ($request) {
      return Response::success();
   });

   assert($response->getStatusCode() === 500, 'Error not handled properly');
});

// Test 12: Global vs Route Middleware
runTest("Global vs route middleware execution", function () {
   $pipeline = new MiddlewarePipeline();
   $executionOrder = [];

   $globalMiddleware = new class($executionOrder) extends Middleware {
      private array $order;
      public function __construct(array &$order)
      {
         $this->order = &$order;
      }
      public function handle(Request $request, callable $next): Response
      {
         $this->order[] = 'global';
         return $next($request);
      }
      public function getPriority(): int
      {
         return 5;
      }
   };

   $routeMiddleware = new class($executionOrder) extends Middleware {
      private array $order;
      public function __construct(array &$order)
      {
         $this->order = &$order;
      }
      public function handle(Request $request, callable $next): Response
      {
         $this->order[] = 'route';
         return $next($request);
      }
      public function getPriority(): int
      {
         return 10;
      }
   };

   $pipeline->addGlobal($globalMiddleware);
   $pipeline->add($routeMiddleware);

   $request = Request::create('/test');
   $pipeline->execute($request, function ($request) use (&$executionOrder) {
      $executionOrder[] = 'destination';
      return Response::success();
   });

   assert(
      $executionOrder === ['global', 'route', 'destination'],
      'Wrong execution order: ' . implode(', ', $executionOrder)
   );
});

echo "\n" . str_repeat("=", 60) . "\n";
echo "📊 Test Results: $testsPassed/$testsTotal tests passed\n";

if ($testsPassed === $testsTotal) {
   echo "🎉 All middleware tests passed! Middleware pipeline system is working perfectly!\n";
   echo "\n✅ Key Features Verified:\n";
   echo "   • Basic middleware pipeline execution\n";
   echo "   • Priority-based middleware ordering\n";
   echo "   • CORS middleware functionality\n";
   echo "   • Rate limiting middleware\n";
   echo "   • Authentication middleware\n";
   echo "   • Multiple middleware integration\n";
   echo "   • Conditional middleware execution\n";
   echo "   • Performance monitoring and timing\n";
   echo "   • Enhanced HTTP route integration\n";
   echo "   • Error handling in pipeline\n";
   echo "   • Global vs route middleware\n";
   echo "   • Request/response modification\n";
   echo "\n🚀 Phase 2 Task 4 (Middleware Pipeline) completed successfully!\n";
   echo "\n📋 Next Steps:\n";
   echo "   • Phase 2 Task 5: Event System\n";
   echo "   • Phase 2 Task 6: Caching Layer Improvements\n";
   echo "   • Phase 2 Task 7: API Documentation\n";
} else {
   echo "⚠️  Some tests failed. Please review the issues above.\n";
   exit(1);
}
