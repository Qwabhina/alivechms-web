<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Mockery as m;
use AliveChMS\Core\Auth;

/**
 * Unit tests for Auth class
 * @covers \AliveChMS\Core\Auth
 */
class AuthTest extends TestCase
{
   protected function setUp(): void
   {
      parent::setUp();
      $_ENV['JWT_SECRET'] = 'test_secret_key_1234567890';
      $_ENV['JWT_REFRESH_SECRET'] = 'test_refresh_secret_key_1234567890';
   }

   protected function tearDown(): void
   {
      m::close();
      parent::tearDown();
   }

   public function testGenerateAccessToken(): void
   {
      $user = [
         'MbrID' => 1,
         'Username' => 'testuser',
         'Role' => ['admin']
      ];

      $token = Auth::generateAccessToken($user);

      $this->assertIsString($token);
      $this->assertNotEmpty($token);

      // Token should have 3 parts (header.payload.signature)
      $parts = explode('.', $token);
      $this->assertCount(3, $parts);
   }

   public function testGenerateRefreshToken(): void
   {
      $user = [
         'MbrID' => 1,
         'Username' => 'testuser'
      ];

      $token = Auth::generateRefreshToken($user);

      $this->assertIsString($token);
      $this->assertNotEmpty($token);

      // Token should have 3 parts
      $parts = explode('.', $token);
      $this->assertCount(3, $parts);
   }

   public function testVerifyValidToken(): void
   {
      $user = [
         'MbrID' => 1,
         'Username' => 'testuser',
         'Role' => ['member']
      ];

      $token = Auth::generateAccessToken($user);
      $decoded = Auth::verify($token);

      $this->assertIsArray($decoded);
      $this->assertEquals(1, $decoded['user_id']);
      $this->assertEquals('testuser', $decoded['username']);
      $this->assertEquals(['member'], $decoded['role']);
   }

   public function testVerifyInvalidToken(): void
   {
      $invalidToken = 'invalid.token.here';
      $result = Auth::verify($invalidToken);

      $this->assertFalse($result);
   }

   public function testGetBearerTokenFromHeader(): void
   {
      // Mock Authorization header
      $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer test-token-123';

      $token = Auth::getBearerToken();
      $this->assertEquals('test-token-123', $token);

      // Clean up
      unset($_SERVER['HTTP_AUTHORIZATION']);
   }

   public function testGetBearerTokenMissing(): void
   {
      // Ensure no Authorization header
      unset($_SERVER['HTTP_AUTHORIZATION']);

      $token = Auth::getBearerToken();
      $this->assertNull($token);
   }

   public function testTokenExpiration(): void
   {
      // Create a token that expires immediately (for testing)
      $user = ['MbrID' => 1, 'Username' => 'test'];

      // We can't easily test expiration without modifying the class
      // This would require dependency injection or making TTL configurable
      $this->markTestSkipped('Token expiration testing requires refactoring Auth class');
   }
}
