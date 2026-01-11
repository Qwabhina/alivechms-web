<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Simple unit tests for basic validation functionality
 */
class SimpleValidatorTest extends TestCase
{
   public function testBasicValidation(): void
   {
      // Test basic string validation
      $this->assertTrue(is_string('hello'));
      $this->assertFalse(is_string(123));

      // Test email validation
      $this->assertTrue(filter_var('test@example.com', FILTER_VALIDATE_EMAIL) !== false);
      $this->assertFalse(filter_var('invalid-email', FILTER_VALIDATE_EMAIL) !== false);

      // Test numeric validation
      $this->assertTrue(is_numeric('123'));
      $this->assertTrue(is_numeric(123));
      $this->assertFalse(is_numeric('abc'));
   }

   public function testPasswordHashing(): void
   {
      $password = 'TestPassword123!';
      $hash = password_hash($password, PASSWORD_DEFAULT);

      $this->assertIsString($hash);
      $this->assertNotEquals($password, $hash);
      $this->assertTrue(password_verify($password, $hash));
      $this->assertFalse(password_verify('WrongPassword', $hash));
   }

   public function testArrayOperations(): void
   {
      $data = ['name' => 'John', 'age' => 30];

      $this->assertArrayHasKey('name', $data);
      $this->assertArrayHasKey('age', $data);
      $this->assertEquals('John', $data['name']);
      $this->assertEquals(30, $data['age']);
   }

   public function testStringOperations(): void
   {
      $text = 'Hello World';

      $this->assertEquals(11, strlen($text));
      $this->assertTrue(str_contains($text, 'World'));
      $this->assertFalse(str_contains($text, 'PHP'));
      $this->assertEquals('hello world', strtolower($text));
      $this->assertEquals('HELLO WORLD', strtoupper($text));
   }

   public function testDateOperations(): void
   {
      $date = '2025-01-01';
      $timestamp = strtotime($date);

      $this->assertNotFalse($timestamp);
      $this->assertEquals('2025-01-01', date('Y-m-d', $timestamp));

      // Test invalid date
      $invalidDate = '2025-13-01';
      $this->assertFalse(strtotime($invalidDate));
   }

   public function testJsonOperations(): void
   {
      $data = ['name' => 'John', 'age' => 30];
      $json = json_encode($data);

      $this->assertIsString($json);
      $this->assertStringContainsString('John', $json);

      $decoded = json_decode($json, true);
      $this->assertEquals($data, $decoded);
   }

   public function testFileOperations(): void
   {
      $testFile = __DIR__ . '/test_file.txt';
      $content = 'Test content';

      // Write file
      $result = file_put_contents($testFile, $content);
      $this->assertNotFalse($result);

      // Read file
      $readContent = file_get_contents($testFile);
      $this->assertEquals($content, $readContent);

      // Check file exists
      $this->assertTrue(file_exists($testFile));

      // Clean up
      unlink($testFile);
      $this->assertFalse(file_exists($testFile));
   }

   public function testRegexOperations(): void
   {
      $email = 'test@example.com';
      $emailPattern = '/^[^\s@]+@[^\s@]+\.[^\s@]+$/';

      $this->assertEquals(1, preg_match($emailPattern, $email));
      $this->assertEquals(0, preg_match($emailPattern, 'invalid-email'));

      $phone = '+233241234567';
      $phonePattern = '/^\+?[1-9]\d{1,14}$/';

      $this->assertEquals(1, preg_match($phonePattern, $phone));
      $this->assertEquals(0, preg_match($phonePattern, 'abc123'));
   }
}
