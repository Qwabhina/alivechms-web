<?php

/**
 * Simple HTTP Classes Test
 * 
 * Tests basic Request/Response functionality
 */

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/core/Http/Request.php';
require_once __DIR__ . '/core/Http/Response.php';

echo "🧪 Testing HTTP Request/Response Classes...\n\n";

try {
   // Test 1: Request creation
   echo "✅ Testing Request creation...\n";
   $request = Request::create('/api/users?page=1', 'GET');
   assert($request->getMethod() === 'GET');
   assert($request->getPath() === 'api/users');
   echo "   ✓ Request creation works\n";

   // Test 2: Request with POST data
   echo "✅ Testing Request with POST data...\n";
   $request = Request::create('/api/users', 'POST', ['name' => 'John', 'email' => 'john@example.com']);
   assert($request->getMethod() === 'POST');
   assert($request->input('name') === 'John');
   assert($request->input('email') === 'john@example.com');
   echo "   ✓ POST data handling works\n";

   // Test 3: JSON Response
   echo "✅ Testing JSON Response...\n";
   $response = Response::json(['message' => 'Hello World'], 200);
   assert($response->getStatusCode() === 200);
   assert($response->getHeader('Content-Type') === 'application/json; charset=utf-8');
   $content = json_decode($response->getContent(), true);
   assert($content['message'] === 'Hello World');
   echo "   ✓ JSON response works\n";

   // Test 4: Success Response
   echo "✅ Testing Success Response...\n";
   $response = Response::success(['id' => 1, 'name' => 'John'], 'User created', 201);
   assert($response->getStatusCode() === 201);
   $content = json_decode($response->getContent(), true);
   assert($content['status'] === 'success');
   assert($content['message'] === 'User created');
   assert($content['data']['id'] === 1);
   echo "   ✓ Success response works\n";

   // Test 5: Error Response
   echo "✅ Testing Error Response...\n";
   $response = Response::error('Validation failed', 422, ['name' => ['Name is required']]);
   assert($response->getStatusCode() === 422);
   $content = json_decode($response->getContent(), true);
   assert($content['status'] === 'error');
   assert($content['message'] === 'Validation failed');
   assert($content['errors']['name'][0] === 'Name is required');
   echo "   ✓ Error response works\n";

   // Test 6: Headers
   echo "✅ Testing Headers...\n";
   $response = Response::make('Hello')
      ->header('X-Custom-Header', 'custom-value')
      ->contentType('text/plain');
   assert($response->getHeader('X-Custom-Header') === 'custom-value');
   assert($response->getHeader('Content-Type') === 'text/plain');
   echo "   ✓ Headers work\n";

   echo "\n🎉 All HTTP tests passed!\n";
   echo "🚀 Request/Response classes are working correctly!\n";
} catch (Exception $e) {
   echo "\n❌ Test failed: " . $e->getMessage() . "\n";
   echo "📍 File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
   exit(1);
}
