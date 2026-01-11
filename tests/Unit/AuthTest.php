<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Mockery as m;

/**
 * Unit tests for Auth class
 */
class AuthTest extends TestCase
{
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

   public function testPasswordHashing(): void
   {
      $password = 'TestPassword123!';
      $hash = Auth::hashPassword($password);

      $this->assertIsString($hash);
      $this->assertNotEquals($password, $hash);
      $this->assertTrue(password_verify($password, $hash));
   }

   public function testPasswordVerification(): void
   {
      $password = 'TestPassword123!';
      $hash = password_hash($password, PASSWORD_DEFAULT);

      $this->assertTrue(Auth::verifyPassword($password, $hash));
      $this->assertFalse(Auth::verifyPassword('WrongPassword', $hash));
   }

   public function testValidatePasswordStrength(): void
   {
      // Strong password
      $this->assertTrue(Auth::validatePasswordStrength('StrongPass123!'));

      // Weak passwords
      $this->assertFalse(Auth::validatePasswordStrength('weak'));
      $this->assertFalse(Auth::validatePasswordStrength('12345678'));
      $this->assertFalse(Auth::validatePasswordStrength('NoNumbers!'));
      $this->assertFalse(Auth::validatePasswordStrength('nonumbers123'));
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
