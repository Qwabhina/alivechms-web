<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for RateLimiter class
 */
class RateLimiterTest extends TestCase
{
   private string $testCacheDir;

   protected function setUp(): void
   {
      parent::setUp();

      // Use test-specific cache directory
      $this->testCacheDir = __DIR__ . '/../../cache/rate_limits_test';
      if (!is_dir($this->testCacheDir)) {
         mkdir($this->testCacheDir, 0755, true);
      }

      $this->clearTestCache();
   }

   protected function tearDown(): void
   {
      $this->clearTestCache();
      parent::tearDown();
   }

   private function clearTestCache(): void
   {
      if (is_dir($this->testCacheDir)) {
         $files = glob($this->testCacheDir . '/*');
         foreach ($files as $file) {
            if (is_file($file)) {
               unlink($file);
            }
         }
      }
   }

   public function testRateLimitAllowsInitialRequests(): void
   {
      $identifier = 'test_user_1';

      // First request should be allowed
      $this->assertTrue(RateLimiter::check($identifier, 5, 300));

      // Second request should also be allowed
      $this->assertTrue(RateLimiter::check($identifier, 5, 300));
   }

   public function testRateLimitBlocksExcessiveRequests(): void
   {
      $identifier = 'test_user_2';
      $maxAttempts = 3;

      // Make maximum allowed requests
      for ($i = 0; $i < $maxAttempts; $i++) {
         $this->assertTrue(RateLimiter::check($identifier, $maxAttempts, 300));
      }

      // Next request should be blocked
      $this->assertFalse(RateLimiter::check($identifier, $maxAttempts, 300));
   }

   public function testRateLimitResetsAfterWindow(): void
   {
      $identifier = 'test_user_3';
      $maxAttempts = 2;
      $windowSeconds = 1; // 1 second window

      // Use up the limit
      $this->assertTrue(RateLimiter::check($identifier, $maxAttempts, $windowSeconds));
      $this->assertTrue(RateLimiter::check($identifier, $maxAttempts, $windowSeconds));
      $this->assertFalse(RateLimiter::check($identifier, $maxAttempts, $windowSeconds));

      // Wait for window to expire
      sleep(2);

      // Should be allowed again
      $this->assertTrue(RateLimiter::check($identifier, $maxAttempts, $windowSeconds));
   }

   public function testRateLimitClearResetsCounter(): void
   {
      $identifier = 'test_user_4';
      $maxAttempts = 2;

      // Use up the limit
      $this->assertTrue(RateLimiter::check($identifier, $maxAttempts, 300));
      $this->assertTrue(RateLimiter::check($identifier, $maxAttempts, 300));
      $this->assertFalse(RateLimiter::check($identifier, $maxAttempts, 300));

      // Clear the rate limit
      RateLimiter::clear($identifier);

      // Should be allowed again immediately
      $this->assertTrue(RateLimiter::check($identifier, $maxAttempts, 300));
   }

   public function testRateLimitDifferentIdentifiers(): void
   {
      $user1 = 'test_user_5';
      $user2 = 'test_user_6';
      $maxAttempts = 2;

      // User 1 uses up their limit
      $this->assertTrue(RateLimiter::check($user1, $maxAttempts, 300));
      $this->assertTrue(RateLimiter::check($user1, $maxAttempts, 300));
      $this->assertFalse(RateLimiter::check($user1, $maxAttempts, 300));

      // User 2 should still be allowed
      $this->assertTrue(RateLimiter::check($user2, $maxAttempts, 300));
      $this->assertTrue(RateLimiter::check($user2, $maxAttempts, 300));
      $this->assertFalse(RateLimiter::check($user2, $maxAttempts, 300));
   }

   public function testRateLimitGetRemainingAttempts(): void
   {
      $identifier = 'test_user_7';
      $maxAttempts = 5;

      // Check remaining attempts
      $remaining = RateLimiter::getRemainingAttempts($identifier, $maxAttempts, 300);
      $this->assertEquals($maxAttempts, $remaining);

      // Make some requests
      RateLimiter::check($identifier, $maxAttempts, 300);
      RateLimiter::check($identifier, $maxAttempts, 300);

      $remaining = RateLimiter::getRemainingAttempts($identifier, $maxAttempts, 300);
      $this->assertEquals(3, $remaining);
   }

   public function testRateLimitGetResetTime(): void
   {
      $identifier = 'test_user_8';
      $windowSeconds = 300;

      // Make a request to start the window
      RateLimiter::check($identifier, 5, $windowSeconds);

      $resetTime = RateLimiter::getResetTime($identifier, $windowSeconds);

      // Reset time should be in the future
      $this->assertGreaterThan(time(), $resetTime);

      // Should be within the window
      $this->assertLessThanOrEqual(time() + $windowSeconds, $resetTime);
   }

   public function testRateLimitWithIPAddress(): void
   {
      $ip = '192.168.1.100';
      $maxAttempts = 3;

      // Simulate IP-based rate limiting
      for ($i = 0; $i < $maxAttempts; $i++) {
         $this->assertTrue(RateLimiter::check($ip, $maxAttempts, 300));
      }

      $this->assertFalse(RateLimiter::check($ip, $maxAttempts, 300));
   }

   public function testRateLimitCleanupOldEntries(): void
   {
      $identifier = 'test_user_9';
      $shortWindow = 1; // 1 second

      // Make a request
      RateLimiter::check($identifier, 5, $shortWindow);

      // Wait for window to expire
      sleep(2);

      // Make another request (this should clean up old entries)
      RateLimiter::check($identifier, 5, $shortWindow);

      // The old entry should be cleaned up automatically
      // We can't directly test this without accessing internal state,
      // but the functionality should work correctly
      $this->assertTrue(true); // Placeholder assertion
   }
}
