<?php

/**
 * Middleware System Tests
 *
 * Tests for the middleware pipeline system including base middleware,
 * pipeline execution, and specific middleware implementations.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../core/Http/Middleware.php';
require_once __DIR__ . '/../../core/Http/MiddlewarePipeline.php';
require_once __DIR__ . '/../../core/Http/Middleware/CorsMiddleware.php';
require_once __DIR__ . '/../../core/Http/Middleware/RateLimitMiddleware.php';
require_once __DIR__ . '/../../core/Http/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../core/Http/Middleware/LoggingMiddleware.php';
require_once __DIR__ . '/../../core/Http/Request.php';
require_once __DIR__ . '/../../core/Http/Response.php';

use PHPUnit\Framework\TestCase;

class MiddlewareTest extends TestCase
{
   private MiddlewarePipeline $pipeline;

   protected function setUp(): void
   {
      $this->pipeline = new MiddlewarePipeline();
   }

   public function testBasicMiddlewareExecution()
   {
      $middleware = new class extends Middleware {
         public function handle(Request $request, callable $next): Response
         {
            $response = $next($request);
            $response->header('X-Test-Middleware', 'executed');
            return $response;
         }
      };

      $this->pipeline->add($middleware);

      $request = Request::create('/test');
      $response = $this->pipeline->execute($request, function ($request) {
         return Response::success(['message' => 'test']);
      });

      $this->assertEquals('executed', $response->getHeader('X-Test-Middleware'));
      $this->assertEquals(200, $response->getStatusCode());
   }

   public function testMiddlewarePriority()
   {
      $order = [];

      $middleware1 = new class($order) extends Middleware {
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
            return 20;
         }
      };

      $middleware2 = new class($order) extends Middleware {
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

      $this->pipeline->add($middleware1);
      $this->pipeline->add($middleware2);

      $this->pipeline->execute(Request::create('/test'), function ($request) use (&$order) {
         $order[] = 'destination';
         return Response::success();
      });

      $this->assertEquals(['middleware2', 'middleware1', 'destination'], $order);
   }

   public function testMiddlewareCanModifyRequest()
   {
      $middleware = new class extends Middleware {
         public function handle(Request $request, callable $next): Response
         {
            $request->setRouteParams(['modified' => true]);
            return $next($request);
         }
      };

      $this->pipeline->add($middleware);

      $request = Request::create('/test');
      $this->pipeline->execute($request, function ($request) {
         $this->assertTrue($request->route('modified'));
         return Response::success();
      });
   }

   public function testMiddlewareErrorHandling()
   {
      $middleware = new class extends Middleware {
         public function handle(Request $request, callable $next): Response
         {
            throw new Exception('Middleware error');
         }
      };

      $this->pipeline->add($middleware);

      $request = Request::create('/test');
      $response = $this->pipeline->execute($request, function ($request) {
         return Response::success();
      });

      $this->assertEquals(500, $response->getStatusCode());
   }

   public function testConditionalMiddleware()
   {
      $middleware = new class extends Middleware {
         public function handle(Request $request, callable $next): Response
         {
            $response = $next($request);
            $response->header('X-Conditional', 'executed');
            return $response;
         }

         public function shouldExecute(Request $request): bool
         {
            return $request->getPath() === 'conditional';
         }
      };

      $this->pipeline->add($middleware);

      // Should execute
      $request1 = Request::create('/conditional');
      $response1 = $this->pipeline->execute($request1, function ($request) {
         return Response::success();
      });
      $this->assertEquals('executed', $response1->getHeader('X-Conditional'));

      // Should not execute
      $request2 = Request::create('/other');
      $response2 = $this->pipeline->execute($request2, function ($request) {
         return Response::success();
      });
      $this->assertNull($response2->getHeader('X-Conditional'));
   }

   public function testCorsMiddleware()
   {
      $corsMiddleware = new CorsMiddleware([
         'allowed_origins' => ['https://example.com'],
         'allowed_methods' => ['GET', 'POST'],
         'allowed_headers' => ['Content-Type', 'Authorization']
      ]);

      $this->pipeline->add($corsMiddleware);

      // Test preflight request
      $preflightRequest = Request::create('/test', 'OPTIONS', [], [
         'HTTP_ORIGIN' => 'https://example.com'
      ]);

      $response = $this->pipeline->execute($preflightRequest, function ($request) {
         return Response::success();
      });

      $this->assertEquals(200, $response->getStatusCode());
      $this->assertEquals('https://example.com', $response->getHeader('Access-Control-Allow-Origin'));
      $this->assertEquals('GET, POST', $response->getHeader('Access-Control-Allow-Methods'));
   }

   public function testRateLimitMiddleware()
   {
      $rateLimiter = $this->createMock(RateLimiter::class);
      $rateLimiter->method('tooManyAttempts')->willReturn(false);
      $rateLimiter->method('attempts')->willReturn(5);

      $rateLimitMiddleware = new RateLimitMiddleware($rateLimiter, 10, 1);
      $this->pipeline->add($rateLimitMiddleware);

      $request = Request::create('/test');
      $response = $this->pipeline->execute($request, function ($request) {
         return Response::success();
      });

      $this->assertEquals(200, $response->getStatusCode());
      $this->assertEquals('10', $response->getHeader('X-RateLimit-Limit'));
      $this->assertEquals('5', $response->getHeader('X-RateLimit-Remaining'));
   }

   public function testRateLimitExceeded()
   {
      $rateLimiter = $this->createMock(RateLimiter::class);
      $rateLimiter->method('tooManyAttempts')->willReturn(true);
      $rateLimiter->method('availableIn')->willReturn(60);

      $rateLimitMiddleware = new RateLimitMiddleware($rateLimiter, 10, 1);
      $this->pipeline->add($rateLimitMiddleware);

      $request = Request::create('/test');
      $response = $this->pipeline->execute($request, function ($request) {
         return Response::success();
      });

      $this->assertEquals(429, $response->getStatusCode());
      $this->assertEquals('60', $response->getHeader('Retry-After'));
   }

   public function testAuthMiddleware()
   {
      $auth = $this->createMock(Auth::class);
      $auth->method('validateToken')->willReturn(['id' => 1, 'username' => 'testuser']);

      $authMiddleware = new AuthMiddleware($auth);
      $this->pipeline->add($authMiddleware);

      $request = Request::create('/test', 'GET', [], [
         'HTTP_AUTHORIZATION' => 'Bearer valid-token'
      ]);

      $response = $this->pipeline->execute($request, function ($request) {
         $user = $request->route('authenticated_user');
         $this->assertEquals('testuser', $user['username']);
         return Response::success();
      });

      $this->assertEquals(200, $response->getStatusCode());
   }

   public function testAuthMiddlewareUnauthorized()
   {
      $auth = $this->createMock(Auth::class);
      $auth->method('validateToken')->willReturn(null);

      $authMiddleware = new AuthMiddleware($auth);
      $this->pipeline->add($authMiddleware);

      $request = Request::create('/test', 'GET', [], [
         'HTTP_AUTHORIZATION' => 'Bearer invalid-token'
      ]);

      $response = $this->pipeline->execute($request, function ($request) {
         return Response::success();
      });

      $this->assertEquals(401, $response->getStatusCode());
   }

   public function testOptionalAuthMiddleware()
   {
      $auth = $this->createMock(Auth::class);
      $authMiddleware = new AuthMiddleware($auth, ['optional' => true]);
      $this->pipeline->add($authMiddleware);

      $request = Request::create('/test');
      $response = $this->pipeline->execute($request, function ($request) {
         return Response::success();
      });

      $this->assertEquals(200, $response->getStatusCode());
   }

   public function testPipelineCloning()
   {
      $middleware = new class extends Middleware {
         public function handle(Request $request, callable $next): Response
         {
            return $next($request);
         }
      };

      $this->pipeline->add($middleware);
      $cloned = $this->pipeline->clone();

      $this->assertEquals($this->pipeline->count(), $cloned->count());
      $this->assertNotSame($this->pipeline, $cloned);
   }

   public function testConditionalPipeline()
   {
      $conditionalPipeline = $this->pipeline->when(function (Request $request) {
         return $request->getPath() === 'special';
      });

      $middleware = new class extends Middleware {
         public function handle(Request $request, callable $next): Response
         {
            $response = $next($request);
            $response->header('X-Special', 'true');
            return $response;
         }
      };

      $conditionalPipeline->add($middleware);

      // Should execute conditional middleware
      $request1 = Request::create('/special');
      $response1 = $conditionalPipeline->execute($request1, function ($request) {
         return Response::success();
      });
      $this->assertEquals('true', $response1->getHeader('X-Special'));

      // Should not execute conditional middleware
      $request2 = Request::create('/normal');
      $response2 = $conditionalPipeline->execute($request2, function ($request) {
         return Response::success();
      });
      $this->assertNull($response2->getHeader('X-Special'));
   }

   public function testPipelineWithTiming()
   {
      $middleware = new class extends Middleware {
         public function handle(Request $request, callable $next): Response
         {
            usleep(1000); // 1ms delay
            return $next($request);
         }
      };

      $this->pipeline->add($middleware);

      $request = Request::create('/test');
      $result = $this->pipeline->executeWithTiming($request, function ($request) {
         return Response::success();
      });

      $this->assertArrayHasKey('response', $result);
      $this->assertArrayHasKey('execution_time', $result);
      $this->assertArrayHasKey('memory_used', $result);
      $this->assertArrayHasKey('middleware_count', $result);

      $this->assertGreaterThan(0, $result['execution_time']);
      $this->assertEquals(1, $result['middleware_count']);
   }

   public function testMiddlewareRemoval()
   {
      $middleware1 = new class extends Middleware {
         public function handle(Request $request, callable $next): Response
         {
            return $next($request);
         }
      };

      $middleware2 = new class extends Middleware {
         public function handle(Request $request, callable $next): Response
         {
            return $next($request);
         }
      };

      $this->pipeline->add($middleware1);
      $this->pipeline->add($middleware2);

      $this->assertEquals(2, $this->pipeline->count());

      $this->pipeline->remove(get_class($middleware1));
      $this->assertEquals(1, $this->pipeline->count());

      $this->pipeline->clear();
      $this->assertEquals(0, $this->pipeline->count());
   }

   public function testGlobalMiddleware()
   {
      $globalMiddleware = new class extends Middleware {
         public function handle(Request $request, callable $next): Response
         {
            $response = $next($request);
            $response->header('X-Global', 'true');
            return $response;
         }
         public function getPriority(): int
         {
            return 5;
         }
      };

      $routeMiddleware = new class extends Middleware {
         public function handle(Request $request, callable $next): Response
         {
            $response = $next($request);
            $response->header('X-Route', 'true');
            return $response;
         }
         public function getPriority(): int
         {
            return 10;
         }
      };

      $this->pipeline->addGlobal($globalMiddleware);
      $this->pipeline->add($routeMiddleware);

      $request = Request::create('/test');
      $response = $this->pipeline->execute($request, function ($request) {
         return Response::success();
      });

      $this->assertEquals('true', $response->getHeader('X-Global'));
      $this->assertEquals('true', $response->getHeader('X-Route'));

      // Global middleware should execute first (lower priority)
      $executionOrder = $this->pipeline->getExecutionOrder();
      $this->assertStringContains('class@anonymous', $executionOrder[0]); // Global middleware
   }
}
