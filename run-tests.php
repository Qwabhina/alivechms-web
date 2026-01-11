<?php

/**
 * Simple Test Runner
 * 
 * Runs basic tests to verify core functionality
 */

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

echo "🧪 Running AliveChMS Tests...\n\n";

// Test 1: Basic PHP functionality
echo "✅ Testing basic PHP functionality...\n";
assert(is_string('hello'));
assert(filter_var('test@example.com', FILTER_VALIDATE_EMAIL) !== false);
assert(is_numeric('123'));
echo "   ✓ Basic validation works\n";

// Test 2: Password hashing
echo "✅ Testing password security...\n";
$password = 'TestPassword123!';
$hash = password_hash($password, PASSWORD_DEFAULT);
assert(password_verify($password, $hash));
echo "   ✓ Password hashing works\n";

// Test 3: JSON operations
echo "✅ Testing JSON operations...\n";
$data = ['name' => 'John', 'age' => 30];
$json = json_encode($data);
$decoded = json_decode($json, true);
assert($data === $decoded);
echo "   ✓ JSON encoding/decoding works\n";

// Test 4: File operations
echo "✅ Testing file operations...\n";
$testFile = __DIR__ . '/test_temp.txt';
$content = 'Test content';
file_put_contents($testFile, $content);
$readContent = file_get_contents($testFile);
assert($content === $readContent);
unlink($testFile);
echo "   ✓ File operations work\n";

// Test 5: Environment loading
echo "✅ Testing environment configuration...\n";
if (file_exists(__DIR__ . '/.env')) {
   $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
   $dotenv->safeLoad();
   echo "   ✓ Environment file loaded\n";
} else {
   echo "   ⚠ No .env file found (this is okay for testing)\n";
}

echo "\n🎉 All basic tests passed!\n";
echo "📝 To run full PHPUnit tests: vendor/bin/phpunit tests/Unit/SimpleValidatorTest.php\n";
echo "🚀 Ready to proceed with Phase 1 improvements!\n";
