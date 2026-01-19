<?php

/**
 * JWT Token Test Script
 * 
 * Tests token generation and verification to diagnose signature issues.
 * Run this from the command line: php test-jwt.php
 */

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

// Load environment
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/ORM.php';
require_once __DIR__ . '/core/Helpers.php';
require_once __DIR__ . '/core/Auth.php';

echo "=== JWT Token Test ===\n\n";

// Check environment variables
echo "1. Checking environment variables...\n";
$jwtSecret = $_ENV['JWT_SECRET'] ?? null;
$jwtRefreshSecret = $_ENV['JWT_REFRESH_SECRET'] ?? null;

if (!$jwtSecret || !$jwtRefreshSecret) {
   echo "   ERROR: JWT secrets not found in environment!\n";
   exit(1);
}

echo "   JWT_SECRET length: " . strlen($jwtSecret) . "\n";
echo "   JWT_SECRET preview: " . substr($jwtSecret, 0, 10) . "...\n";
echo "   JWT_REFRESH_SECRET length: " . strlen($jwtRefreshSecret) . "\n";
echo "   JWT_REFRESH_SECRET preview: " . substr($jwtRefreshSecret, 0, 10) . "...\n";

// Check for whitespace
if (trim($jwtSecret) !== $jwtSecret) {
   echo "   WARNING: JWT_SECRET has leading/trailing whitespace!\n";
}
if (trim($jwtRefreshSecret) !== $jwtRefreshSecret) {
   echo "   WARNING: JWT_REFRESH_SECRET has leading/trailing whitespace!\n";
}

echo "\n2. Testing access token generation and verification...\n";

$testUser = [
   'MbrID' => 1,
   'Username' => 'testuser',
   'Role' => ['Admin']
];

try {
   // Generate access token
   $accessToken = Auth::generateAccessToken($testUser);
   echo "   Generated access token length: " . strlen($accessToken) . "\n";
   echo "   Token preview: " . substr($accessToken, 0, 50) . "...\n";

   // Count token parts
   $parts = explode('.', $accessToken);
   echo "   Token parts: " . count($parts) . " (should be 3)\n";

   // Verify the token
   $decoded = Auth::verify($accessToken);

   if ($decoded) {
      echo "   ✓ Access token verified successfully!\n";
      echo "   Decoded user_id: " . ($decoded['user_id'] ?? 'N/A') . "\n";
      echo "   Decoded username: " . ($decoded['username'] ?? 'N/A') . "\n";
   } else {
      echo "   ✗ Access token verification FAILED!\n";
   }
} catch (Exception $e) {
   echo "   ERROR: " . $e->getMessage() . "\n";
}

echo "\n3. Testing refresh token generation and verification...\n";

try {
   // Generate refresh token
   $refreshToken = Auth::generateRefreshToken($testUser);
   echo "   Generated refresh token length: " . strlen($refreshToken) . "\n";
   echo "   Token preview: " . substr($refreshToken, 0, 50) . "...\n";

   // Verify with refresh secret (need to access private method)
   // We'll use reflection to test this
   $reflection = new ReflectionClass('Auth');
   $method = $reflection->getMethod('verify');

   // Get the refresh secret
   $secretProp = $reflection->getProperty('refreshSecretKey');
   $secretProp->setAccessible(true);

   // Initialize keys first
   Auth::generateAccessToken($testUser); // This calls initKeys()

   $refreshSecret = $secretProp->getValue();

   // Verify refresh token
   $decoded = Auth::verify($refreshToken, $refreshSecret);

   if ($decoded) {
      echo "   ✓ Refresh token verified successfully!\n";
      echo "   Decoded user_id: " . ($decoded['user_id'] ?? 'N/A') . "\n";
   } else {
      echo "   ✗ Refresh token verification FAILED!\n";
   }
} catch (Exception $e) {
   echo "   ERROR: " . $e->getMessage() . "\n";
}

echo "\n4. Testing token storage in sessionStorage simulation...\n";

try {
   $accessToken = Auth::generateAccessToken($testUser);

   // Simulate what JavaScript does
   $sessionData = json_encode([
      'accessToken' => $accessToken,
      'user' => $testUser,
      'timestamp' => time() * 1000
   ]);

   // Decode it back
   $restored = json_decode($sessionData, true);
   $restoredToken = $restored['accessToken'];

   echo "   Original token length: " . strlen($accessToken) . "\n";
   echo "   Restored token length: " . strlen($restoredToken) . "\n";
   echo "   Tokens match: " . ($accessToken === $restoredToken ? 'YES' : 'NO') . "\n";

   // Verify restored token
   $decoded = Auth::verify($restoredToken);

   if ($decoded) {
      echo "   ✓ Restored token verified successfully!\n";
   } else {
      echo "   ✗ Restored token verification FAILED!\n";
   }
} catch (Exception $e) {
   echo "   ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
