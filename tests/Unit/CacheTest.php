<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Cache class
 */
class CacheTest extends TestCase
{
   private string $testCacheDir;

   protected function setUp(): void
   {
      parent::setUp();

      // Use a test-specific cache directory
      $this->testCacheDir = __DIR__ . '/../../cache/test';
      if (!is_dir($this->testCacheDir)) {
         mkdir($this->testCacheDir, 0755, true);
      }

      // Clear any existing test cache
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

   public function testSetAndGet(): void
   {
      $key = 'test_key';
      $value = 'test_value';

      $result = Cache::set($key, $value, 3600);
      $this->assertTrue($result);

      $retrieved = Cache::get($key);
      $this->assertEquals($value, $retrieved);
   }

   public function testGetNonExistentKey(): void
   {
      $result = Cache::get('non_existent_key', 'default_value');
      $this->assertEquals('default_value', $result);
   }

   public function testCacheExpiration(): void
   {
      $key = 'expiring_key';
      $value = 'expiring_value';

      // Set cache with 1 second TTL
      Cache::set($key, $value, 1);

      // Should be available immediately
      $this->assertEquals($value, Cache::get($key));

      // Wait for expiration
      sleep(2);

      // Should return default value after expiration
      $this->assertEquals('default', Cache::get($key, 'default'));
   }

   public function testCacheWithArrayData(): void
   {
      $key = 'array_key';
      $value = ['name' => 'John', 'age' => 30, 'active' => true];

      Cache::set($key, $value);
      $retrieved = Cache::get($key);

      $this->assertEquals($value, $retrieved);
      $this->assertIsArray($retrieved);
   }

   public function testCacheWithObjectData(): void
   {
      $key = 'object_key';
      $value = (object) ['name' => 'John', 'age' => 30];

      Cache::set($key, $value);
      $retrieved = Cache::get($key);

      $this->assertEquals($value, $retrieved);
      $this->assertIsObject($retrieved);
   }

   public function testDeleteCache(): void
   {
      $key = 'delete_test';
      $value = 'to_be_deleted';

      Cache::set($key, $value);
      $this->assertEquals($value, Cache::get($key));

      $result = Cache::delete($key);
      $this->assertTrue($result);

      $this->assertEquals('default', Cache::get($key, 'default'));
   }

   public function testCacheExists(): void
   {
      $key = 'exists_test';

      $this->assertFalse(Cache::exists($key));

      Cache::set($key, 'value');
      $this->assertTrue(Cache::exists($key));

      Cache::delete($key);
      $this->assertFalse(Cache::exists($key));
   }

   public function testCacheWithTags(): void
   {
      $tags = ['users', 'members'];

      Cache::set('user_1', 'John', 3600, $tags);
      Cache::set('user_2', 'Jane', 3600, $tags);
      Cache::set('setting_1', 'value', 3600, ['settings']);

      // All should exist initially
      $this->assertEquals('John', Cache::get('user_1'));
      $this->assertEquals('Jane', Cache::get('user_2'));
      $this->assertEquals('value', Cache::get('setting_1'));

      // Invalidate by tag
      Cache::invalidateTag('users');

      // Tagged items should be gone
      $this->assertEquals('default', Cache::get('user_1', 'default'));
      $this->assertEquals('default', Cache::get('user_2', 'default'));

      // Non-tagged item should remain
      $this->assertEquals('value', Cache::get('setting_1'));
   }

   public function testCacheForever(): void
   {
      $key = 'forever_key';
      $value = 'forever_value';

      // TTL of 0 means forever
      Cache::set($key, $value, 0);
      $retrieved = Cache::get($key);

      $this->assertEquals($value, $retrieved);
   }

   public function testClearAllCache(): void
   {
      Cache::set('key1', 'value1');
      Cache::set('key2', 'value2');
      Cache::set('key3', 'value3');

      $this->assertEquals('value1', Cache::get('key1'));
      $this->assertEquals('value2', Cache::get('key2'));
      $this->assertEquals('value3', Cache::get('key3'));

      Cache::clear();

      $this->assertEquals('default', Cache::get('key1', 'default'));
      $this->assertEquals('default', Cache::get('key2', 'default'));
      $this->assertEquals('default', Cache::get('key3', 'default'));
   }
}
