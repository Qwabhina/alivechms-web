<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Application class
 */
class ApplicationTest extends TestCase
{
   private Application $app;

   protected function setUp(): void
   {
      parent::setUp();
      $this->app = Application::getInstance();
   }

   protected function tearDown(): void
   {
      // Clean up container
      $this->app->getContainer()->flush();
      parent::tearDown();
   }

   public function testSingletonInstance(): void
   {
      $app1 = Application::getInstance();
      $app2 = Application::getInstance();

      $this->assertSame($app1, $app2);
   }

   public function testContainerAccess(): void
   {
      $container = $this->app->getContainer();

      $this->assertInstanceOf(Container::class, $container);
   }

   public function testBaseServicesRegistered(): void
   {
      $container = $this->app->getContainer();

      $this->assertTrue($container->bound('Container'));
      $this->assertTrue($container->bound('Application'));

      $this->assertSame($container, $container->resolve('Container'));
      $this->assertSame($this->app, $container->resolve('Application'));
   }

   public function testServiceProviderRegistration(): void
   {
      // Create a simple test service provider
      $testProvider = new class($this->app->getContainer()) extends ServiceProvider {
         public function register(): void
         {
            $this->container->bind('TestService', function () {
               return 'test_value';
            });
         }
      };

      $providerClass = get_class($testProvider);
      $this->app->register($providerClass);

      $this->assertTrue($this->app->bound('TestService'));
      $this->assertEquals('test_value', $this->app->resolve('TestService'));
   }

   public function testBootstrap(): void
   {
      $this->app->bootstrap();

      // Check that core services are registered
      $this->assertTrue($this->app->bound('Database'));
      $this->assertTrue($this->app->bound('ORM'));
      $this->assertTrue($this->app->bound('Auth'));
      $this->assertTrue($this->app->bound('Cache'));
   }

   public function testStaticMethods(): void
   {
      Application::bind('static_test', 'static_value');
      $result = Application::resolve('static_test');

      $this->assertEquals('static_value', $result);
   }

   public function testSingletonBinding(): void
   {
      Application::singleton('singleton_test', function () {
         return new stdClass();
      });

      $instance1 = Application::resolve('singleton_test');
      $instance2 = Application::resolve('singleton_test');

      $this->assertSame($instance1, $instance2);
   }
}
