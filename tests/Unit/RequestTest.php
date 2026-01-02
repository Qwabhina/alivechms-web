<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Request class
 */
class RequestTest extends TestCase
{
   public function testCreateFromGlobals(): void
   {
      $request = Request::createFromGlobals();

      $this->assertInstanceOf(Request::class, $request);
   }

   public function testCreateForTesting(): void
   {
      $request = Request::create('/test', 'POST', ['name' => 'John']);

      $this->assertEquals('POST', $request->getMethod());
      $this->assertEquals('test', $request->getPath());
      $this->assertEquals('John', $request->input('name'));
   }

   public function testGetMethod(): void
   {
      $request = Request::create('/', 'GET');
      $this->assertEquals('GET', $request->getMethod());

      $request = Request::create('/', 'POST');
      $this->assertEquals('POST', $request->getMethod());
   }

   public function testGetPath(): void
   {
      $request = Request::create('/api/users');
      $this->assertEquals('api/users', $request->getPath());

      $request = Request::create('/api/users?page=1');
      $this->assertEquals('api/users', $request->getPath());
   }

   public function testQueryParameters(): void
   {
      $request = Request::create('/?page=1&limit=10');

      $this->assertEquals('1', $request->query('page'));
      $this->assertEquals('10', $request->query('limit'));
      $this->assertEquals('default', $request->query('nonexistent', 'default'));

      $allQuery = $request->query();
      $this->assertIsArray($allQuery);
   }

   public function testPostData(): void
   {
      $request = Request::create('/', 'POST', ['name' => 'John', 'email' => 'john@example.com']);

      $this->assertEquals('John', $request->post('name'));
      $this->assertEquals('john@example.com', $request->post('email'));
      $this->assertNull($request->post('nonexistent'));
   }

   public function testInputData(): void
   {
      $request = Request::create('/', 'POST', ['name' => 'John']);

      $this->assertEquals('John', $request->input('name'));
      $this->assertEquals('default', $request->input('nonexistent', 'default'));
   }

   public function testJsonData(): void
   {
      $jsonData = json_encode(['name' => 'John', 'age' => 30]);
      $request = new Request(
         [],
         [],
         ['REQUEST_METHOD' => 'POST', 'CONTENT_TYPE' => 'application/json'],
         [],
         [],
         $jsonData
      );

      $this->assertTrue($request->isJson());
      $this->assertEquals(['name' => 'John', 'age' => 30], $request->json());
      $this->assertEquals('John', $request->input('name'));
   }

   public function testHasAndFilled(): void
   {
      $request = Request::create('/', 'POST', ['name' => 'John', 'empty' => '']);

      $this->assertTrue($request->has('name'));
      $this->assertTrue($request->has('empty'));
      $this->assertFalse($request->has('nonexistent'));

      $this->assertTrue($request->filled('name'));
      $this->assertFalse($request->filled('empty'));
      $this->assertFalse($request->filled('nonexistent'));
   }

   public function testOnlyAndExcept(): void
   {
      $request = Request::create('/', 'POST', ['name' => 'John', 'email' => 'john@example.com', 'age' => 30]);

      $only = $request->only(['name', 'email']);
      $this->assertEquals(['name' => 'John', 'email' => 'john@example.com'], $only);

      $except = $request->except(['age']);
      $this->assertEquals(['name' => 'John', 'email' => 'john@example.com'], $except);
   }

   public function testHeaders(): void
   {
      $request = new Request(
         [],
         [],
         [
            'HTTP_AUTHORIZATION' => 'Bearer token123',
            'HTTP_CONTENT_TYPE' => 'application/json',
            'CONTENT_TYPE' => 'application/json'
         ]
      );

      $this->assertEquals('Bearer token123', $request->header('authorization'));
      $this->assertEquals('application/json', $request->header('content-type'));
      $this->assertNull($request->header('nonexistent'));
   }

   public function testBearerToken(): void
   {
      $request = new Request(
         [],
         [],
         ['HTTP_AUTHORIZATION' => 'Bearer token123']
      );

      $this->assertEquals('token123', $request->bearerToken());
   }

   public function testBearerTokenMissing(): void
   {
      $request = new Request();
      $this->assertNull($request->bearerToken());
   }

   public function testIpAddress(): void
   {
      $request = new Request([], [], ['REMOTE_ADDR' => '192.168.1.100']);
      $this->assertEquals('192.168.1.100', $request->ip());

      // Test with forwarded IP
      $request = new Request([], [], [
         'HTTP_X_FORWARDED_FOR' => '203.0.113.1, 192.168.1.100',
         'REMOTE_ADDR' => '192.168.1.100'
      ]);
      $this->assertEquals('203.0.113.1', $request->ip());
   }

   public function testIsSecure(): void
   {
      $request = new Request([], [], ['HTTPS' => 'on']);
      $this->assertTrue($request->isSecure());

      $request = new Request([], [], ['HTTPS' => 'off']);
      $this->assertFalse($request->isSecure());

      $request = new Request([], [], ['SERVER_PORT' => 443]);
      $this->assertTrue($request->isSecure());
   }

   public function testIsAjax(): void
   {
      $request = new Request([], [], ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);
      $this->assertTrue($request->isAjax());

      $request = new Request();
      $this->assertFalse($request->isAjax());
   }

   public function testRouteParams(): void
   {
      $request = Request::create('/');
      $request->setRouteParams(['id' => '123', 'slug' => 'test']);

      $this->assertEquals('123', $request->route('id'));
      $this->assertEquals('test', $request->route('slug'));
      $this->assertNull($request->route('nonexistent'));
      $this->assertEquals(['id' => '123', 'slug' => 'test'], $request->routeParams());
   }

   public function testToArray(): void
   {
      $request = Request::create('/test?page=1', 'POST', ['name' => 'John']);
      $array = $request->toArray();

      $this->assertIsArray($array);
      $this->assertEquals('POST', $array['method']);
      $this->assertEquals('test', $array['path']);
      $this->assertArrayHasKey('query', $array);
      $this->assertArrayHasKey('post', $array);
   }
}
