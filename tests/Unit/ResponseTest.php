<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Response class
 */
class ResponseTest extends TestCase
{
   public function testMakeResponse(): void
   {
      $response = Response::make('Hello World', 200);

      $this->assertEquals('Hello World', $response->getContent());
      $this->assertEquals(200, $response->getStatusCode());
   }

   public function testJsonResponse(): void
   {
      $data = ['name' => 'John', 'age' => 30];
      $response = Response::json($data);

      $this->assertEquals(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $response->getContent());
      $this->assertEquals(200, $response->getStatusCode());
      $this->assertEquals('application/json; charset=utf-8', $response->getHeader('Content-Type'));
   }

   public function testSuccessResponse(): void
   {
      $data = ['id' => 1, 'name' => 'John'];
      $response = Response::success($data, 'User created', 201);

      $this->assertEquals(201, $response->getStatusCode());

      $content = json_decode($response->getContent(), true);
      $this->assertEquals('success', $content['status']);
      $this->assertEquals('User created', $content['message']);
      $this->assertEquals($data, $content['data']);
      $this->assertArrayHasKey('timestamp', $content);
   }

   public function testErrorResponse(): void
   {
      $errors = ['email' => ['Email is required']];
      $response = Response::error('Validation failed', 422, $errors);

      $this->assertEquals(422, $response->getStatusCode());

      $content = json_decode($response->getContent(), true);
      $this->assertEquals('error', $content['status']);
      $this->assertEquals('Validation failed', $content['message']);
      $this->assertEquals(422, $content['code']);
      $this->assertEquals($errors, $content['errors']);
   }

   public function testPaginatedResponse(): void
   {
      $data = [['id' => 1], ['id' => 2]];
      $response = Response::paginated($data, 50, 1, 10);

      $content = json_decode($response->getContent(), true);
      $this->assertEquals('success', $content['status']);
      $this->assertEquals($data, $content['data']['data']);

      $pagination = $content['data']['pagination'];
      $this->assertEquals(1, $pagination['page']);
      $this->assertEquals(10, $pagination['limit']);
      $this->assertEquals(50, $pagination['total']);
      $this->assertEquals(5, $pagination['pages']);
   }

   public function testRedirectResponse(): void
   {
      $response = Response::redirect('https://example.com', 302);

      $this->assertEquals(302, $response->getStatusCode());
      $this->assertEquals('https://example.com', $response->getHeader('Location'));
   }

   public function testNotFoundResponse(): void
   {
      $response = Response::notFound('User not found');

      $this->assertEquals(404, $response->getStatusCode());

      $content = json_decode($response->getContent(), true);
      $this->assertEquals('error', $content['status']);
      $this->assertEquals('User not found', $content['message']);
   }

   public function testUnauthorizedResponse(): void
   {
      $response = Response::unauthorized('Token required');

      $this->assertEquals(401, $response->getStatusCode());

      $content = json_decode($response->getContent(), true);
      $this->assertEquals('error', $content['status']);
      $this->assertEquals('Token required', $content['message']);
   }

   public function testForbiddenResponse(): void
   {
      $response = Response::forbidden('Access denied');

      $this->assertEquals(403, $response->getStatusCode());

      $content = json_decode($response->getContent(), true);
      $this->assertEquals('error', $content['status']);
      $this->assertEquals('Access denied', $content['message']);
   }

   public function testValidationErrorResponse(): void
   {
      $errors = ['name' => ['Name is required'], 'email' => ['Email is invalid']];
      $response = Response::validationError('Validation failed', $errors);

      $this->assertEquals(422, $response->getStatusCode());

      $content = json_decode($response->getContent(), true);
      $this->assertEquals('error', $content['status']);
      $this->assertEquals('Validation failed', $content['message']);
      $this->assertEquals($errors, $content['errors']);
   }

   public function testRateLimitedResponse(): void
   {
      $response = Response::rateLimited('Too many requests', 60);

      $this->assertEquals(429, $response->getStatusCode());
      $this->assertEquals('60', $response->getHeader('Retry-After'));

      $content = json_decode($response->getContent(), true);
      $this->assertEquals('error', $content['status']);
      $this->assertEquals('Too many requests', $content['message']);
   }

   public function testSetContent(): void
   {
      $response = Response::make();
      $response->setContent('New content');

      $this->assertEquals('New content', $response->getContent());
   }

   public function testSetStatusCode(): void
   {
      $response = Response::make();
      $response->setStatusCode(404);

      $this->assertEquals(404, $response->getStatusCode());
   }

   public function testHeaders(): void
   {
      $response = Response::make();
      $response->header('X-Custom-Header', 'custom-value');

      $this->assertEquals('custom-value', $response->getHeader('X-Custom-Header'));

      $response->withHeaders(['X-Another' => 'another-value']);
      $this->assertEquals('another-value', $response->getHeader('X-Another'));
   }

   public function testCookies(): void
   {
      $response = Response::make();
      $response->cookie('session_id', 'abc123', 3600);

      $cookies = $response->getCookies();
      $this->assertCount(1, $cookies);
      $this->assertEquals('session_id', $cookies[0]['name']);
      $this->assertEquals('abc123', $cookies[0]['value']);
   }

   public function testContentType(): void
   {
      $response = Response::make();
      $response->contentType('text/plain');

      $this->assertEquals('text/plain', $response->getHeader('Content-Type'));
   }

   public function testNoCache(): void
   {
      $response = Response::make();
      $response->noCache();

      $this->assertEquals('no-cache, no-store, must-revalidate', $response->getHeader('Cache-Control'));
      $this->assertEquals('no-cache', $response->getHeader('Pragma'));
      $this->assertEquals('0', $response->getHeader('Expires'));
   }

   public function testCors(): void
   {
      $response = Response::make();
      $response->cors('https://example.com', 'GET,POST', 'Content-Type');

      $this->assertEquals('https://example.com', $response->getHeader('Access-Control-Allow-Origin'));
      $this->assertEquals('GET,POST', $response->getHeader('Access-Control-Allow-Methods'));
      $this->assertEquals('Content-Type', $response->getHeader('Access-Control-Allow-Headers'));
   }

   public function testToArray(): void
   {
      $response = Response::success(['id' => 1], 'Success', 201);
      $array = $response->toArray();

      $this->assertEquals(201, $array['status_code']);
      $this->assertArrayHasKey('headers', $array);
      $this->assertArrayHasKey('content', $array);
      $this->assertEquals('success', $array['content']['status']);
   }

   public function testToString(): void
   {
      $response = Response::make('Hello World');
      $this->assertEquals('Hello World', (string)$response);
   }

   public function testIsSent(): void
   {
      $response = Response::make('Hello');
      $this->assertFalse($response->isSent());

      // Note: We can't actually test send() without output buffering
      // as it would interfere with PHPUnit
   }
}
