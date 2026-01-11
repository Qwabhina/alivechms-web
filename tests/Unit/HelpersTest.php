<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Helpers class
 */
class HelpersTest extends TestCase
{
   protected function setUp(): void
   {
      parent::setUp();

      // Capture output for response testing
      ob_start();
   }

   protected function tearDown(): void
   {
      // Clean output buffer
      if (ob_get_level()) {
         ob_end_clean();
      }
      parent::tearDown();
   }

   public function testSanitizeString(): void
   {
      $input = '<script>alert("xss")</script>Hello World';
      $expected = 'Hello World';

      $result = Helpers::sanitizeString($input);
      $this->assertEquals($expected, $result);
   }

   public function testSanitizeEmail(): void
   {
      $validEmail = 'test@example.com';
      $invalidEmail = 'test@<script>example.com';

      $this->assertEquals($validEmail, Helpers::sanitizeEmail($validEmail));
      $this->assertEquals('test@example.com', Helpers::sanitizeEmail($invalidEmail));
   }

   public function testSanitizePhone(): void
   {
      $phone = '+233 (24) 123-4567';
      $expected = '+233241234567';

      $result = Helpers::sanitizePhone($phone);
      $this->assertEquals($expected, $result);
   }

   public function testValidateEmail(): void
   {
      $this->assertTrue(Helpers::validateEmail('test@example.com'));
      $this->assertTrue(Helpers::validateEmail('user.name+tag@domain.co.uk'));

      $this->assertFalse(Helpers::validateEmail('invalid-email'));
      $this->assertFalse(Helpers::validateEmail('test@'));
      $this->assertFalse(Helpers::validateEmail('@example.com'));
   }

   public function testValidatePhone(): void
   {
      // Valid phone numbers
      $this->assertTrue(Helpers::validatePhone('+233241234567'));
      $this->assertTrue(Helpers::validatePhone('0241234567'));
      $this->assertTrue(Helpers::validatePhone('+1-555-123-4567'));

      // Invalid phone numbers
      $this->assertFalse(Helpers::validatePhone('123'));
      $this->assertFalse(Helpers::validatePhone('abc123'));
      $this->assertFalse(Helpers::validatePhone(''));
   }

   public function testValidateDate(): void
   {
      $this->assertTrue(Helpers::validateDate('2025-01-01'));
      $this->assertTrue(Helpers::validateDate('2025-12-31'));
      $this->assertTrue(Helpers::validateDate('01/01/2025'));

      $this->assertFalse(Helpers::validateDate('invalid-date'));
      $this->assertFalse(Helpers::validateDate('2025-13-01'));
      $this->assertFalse(Helpers::validateDate('2025-01-32'));
   }

   public function testGetClientIp(): void
   {
      // Test with REMOTE_ADDR
      $_SERVER['REMOTE_ADDR'] = '192.168.1.100';
      $this->assertEquals('192.168.1.100', Helpers::getClientIp());

      // Test with HTTP_X_FORWARDED_FOR
      $_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.1, 192.168.1.100';
      $this->assertEquals('203.0.113.1', Helpers::getClientIp());

      // Test with HTTP_CLIENT_IP
      unset($_SERVER['HTTP_X_FORWARDED_FOR']);
      $_SERVER['HTTP_CLIENT_IP'] = '203.0.113.2';
      $this->assertEquals('203.0.113.2', Helpers::getClientIp());

      // Clean up
      unset($_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_CLIENT_IP']);
   }

   public function testGenerateSlug(): void
   {
      $this->assertEquals('hello-world', Helpers::generateSlug('Hello World'));
      $this->assertEquals('test-with-special-chars', Helpers::generateSlug('Test with @#$% special chars!'));
      $this->assertEquals('accented-characters', Helpers::generateSlug('Accénted Chàracters'));
      $this->assertEquals('multiple-spaces', Helpers::generateSlug('Multiple    Spaces'));
   }

   public function testGenerateRandomString(): void
   {
      $length = 16;
      $result = Helpers::generateRandomString($length);

      $this->assertEquals($length, strlen($result));
      $this->assertMatchesRegularExpression('/^[a-zA-Z0-9]+$/', $result);

      // Test that two calls return different strings
      $result2 = Helpers::generateRandomString($length);
      $this->assertNotEquals($result, $result2);
   }

   public function testFormatBytes(): void
   {
      $this->assertEquals('1 KB', Helpers::formatBytes(1024));
      $this->assertEquals('1 MB', Helpers::formatBytes(1024 * 1024));
      $this->assertEquals('1 GB', Helpers::formatBytes(1024 * 1024 * 1024));
      $this->assertEquals('500 B', Helpers::formatBytes(500));
   }

   public function testIsValidUrl(): void
   {
      $this->assertTrue(Helpers::isValidUrl('https://example.com'));
      $this->assertTrue(Helpers::isValidUrl('http://example.com/path?query=1'));
      $this->assertTrue(Helpers::isValidUrl('https://subdomain.example.com'));

      $this->assertFalse(Helpers::isValidUrl('not-a-url'));
      $this->assertFalse(Helpers::isValidUrl('ftp://example.com'));
      $this->assertFalse(Helpers::isValidUrl('javascript:alert(1)'));
   }

   public function testTruncateText(): void
   {
      $text = 'This is a long text that needs to be truncated';

      $this->assertEquals('This is a long...', Helpers::truncateText($text, 15));
      $this->assertEquals('This is a long text that needs to be truncated', Helpers::truncateText($text, 100));
      $this->assertEquals('This is a long text...', Helpers::truncateText($text, 20, '...'));
   }

   public function testArrayToXml(): void
   {
      $array = [
         'name' => 'John Doe',
         'age' => 30,
         'active' => true
      ];

      $xml = Helpers::arrayToXml($array, 'user');

      $this->assertStringContainsString('<user>', $xml);
      $this->assertStringContainsString('<name>John Doe</name>', $xml);
      $this->assertStringContainsString('<age>30</age>', $xml);
      $this->assertStringContainsString('<active>1</active>', $xml);
      $this->assertStringContainsString('</user>', $xml);
   }

   public function testLogError(): void
   {
      $message = 'Test error message';

      // This method writes to file, so we'll just test it doesn't throw
      $this->expectNotToPerformAssertions();
      Helpers::logError($message);
   }

   public function testLogInfo(): void
   {
      $message = 'Test info message';

      // This method writes to file, so we'll just test it doesn't throw
      $this->expectNotToPerformAssertions();
      Helpers::logInfo($message);
   }

   public function testEscapeHtml(): void
   {
      $input = '<script>alert("xss")</script>';
      $expected = '&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;';

      $result = Helpers::escapeHtml($input);
      $this->assertEquals($expected, $result);
   }

   public function testStripHtml(): void
   {
      $input = '<p>Hello <strong>World</strong>!</p>';
      $expected = 'Hello World!';

      $result = Helpers::stripHtml($input);
      $this->assertEquals($expected, $result);
   }

   public function testIsJson(): void
   {
      $this->assertTrue(Helpers::isJson('{"name": "John", "age": 30}'));
      $this->assertTrue(Helpers::isJson('[1, 2, 3]'));
      $this->assertTrue(Helpers::isJson('"string"'));

      $this->assertFalse(Helpers::isJson('not json'));
      $this->assertFalse(Helpers::isJson('{invalid json}'));
      $this->assertFalse(Helpers::isJson(''));
   }

   public function testStartsWith(): void
   {
      $this->assertTrue(Helpers::startsWith('Hello World', 'Hello'));
      $this->assertTrue(Helpers::startsWith('Hello World', 'H'));

      $this->assertFalse(Helpers::startsWith('Hello World', 'World'));
      $this->assertFalse(Helpers::startsWith('Hello World', 'hello')); // Case sensitive
   }

   public function testEndsWith(): void
   {
      $this->assertTrue(Helpers::endsWith('Hello World', 'World'));
      $this->assertTrue(Helpers::endsWith('Hello World', 'd'));

      $this->assertFalse(Helpers::endsWith('Hello World', 'Hello'));
      $this->assertFalse(Helpers::endsWith('Hello World', 'world')); // Case sensitive
   }
}
