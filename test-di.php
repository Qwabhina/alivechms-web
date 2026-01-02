<?php

/**
 * Simple DI Container Test
 * 
 * Tests basic dependency injection functionality
 */

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/core/Container.php';
require_once __DIR__ . '/core/Application.php';

echo "🧪 Testing Dependency Injection Container...\n\n";

try {
   // Test 1: Basic container functionality
   echo "✅ Testing basic container binding...\n";
   $container = Container::getInstance();
   $container->bind('test_service', 'test_value');
   $resolved = $container->resolve('test_service');
   assert($resolved === 'test_value');
   echo "   ✓ Basic binding and resolution works\n";

   // Test 2: Singleton binding
   echo "✅ Testing singleton binding...\n";
   $container->singleton('singleton_test', function () {
      return new stdClass();
   });
   $instance1 = $container->resolve('singleton_test');
   $instance2 = $container->resolve('singleton_test');
   assert($instance1 === $instance2);
   echo "   ✓ Singleton binding works\n";

   // Test 3: Application bootstrap
   echo "✅ Testing application bootstrap...\n";
   $app = Application::getInstance();
   $app->bootstrap();
   echo "   ✓ Application bootstrapped successfully\n";

   // Test 4: Service resolution through application
   echo "✅ Testing service resolution...\n";
   $database = Application::resolve('Database');
   assert($database instanceof Database);
   echo "   ✓ Database service resolved\n";

   $orm = Application::resolve('ORM');
   assert($orm instanceof ORM);
   echo "   ✓ ORM service resolved\n";

   echo "\n🎉 All DI container tests passed!\n";
   echo "🚀 Dependency injection is working correctly!\n";
} catch (Exception $e) {
   echo "\n❌ Test failed: " . $e->getMessage() . "\n";
   echo "📍 File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
   exit(1);
}
