<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Container (Dependency Injection)
 */
class ContainerTest extends TestCase
{
   private Container $container;

   protected function setUp(): void
   {
      parent::setUp();
      $this->container = new Container();
   }

   protected function tearDown(): void
   {
      $this->container->flush();
      parent::tearDown();
   }

   public function testBasicBinding(): void
   {
      $this->container->bind('test', 'TestValue');
      $resolved = $this->container->resolve('test');

      $this->assertEquals('TestValue', $resolved);
   }

   public function testSingletonBinding(): void
   {
      $this->container->singleton('test', function () {
         return new stdClass();
      });

      $instance1 = $this->container->resolve('test');
      $instance2 = $this->container->resolve('test');

      $this->assertSame($instance1, $instance2);
   }

   public function testFactoryBinding(): void
   {
      $this->container->bind('test', function () {
         return new stdClass();
      });

      $instance1 = $this->container->resolve('test');
      $instance2 = $this->container->resolve('test');

      $this->assertNotSame($instance1, $instance2);
   }

   public function testInstanceBinding(): void
   {
      $instance = new stdClass();
      $instance->value = 'test';

      $this->container->instance('test', $instance);
      $resolved = $this->container->resolve('test');

      $this->assertSame($instance, $resolved);
      $this->assertEquals('test', $resolved->value);
   }

   public function testAutomaticConstructorInjection(): void
   {
      // Create a simple test class
      $testClass = new class {
         public $dependency;

         public function __construct(stdClass $dependency = null)
         {
            $this->dependency = $dependency ?? new stdClass();
         }
      };

      $className = get_class($testClass);
      $this->container->bind('TestDependency', stdClass::class);
      $this->container->bind('TestClass', $className);

      $resolved = $this->container->resolve('TestClass');

      $this->assertInstanceOf($className, $resolved);
      $this->assertInstanceOf(stdClass::class, $resolved->dependency);
   }

   public function testBoundCheck(): void
   {
      $this->assertFalse($this->container->bound('nonexistent'));

      $this->container->bind('test', 'value');
      $this->assertTrue($this->container->bound('test'));
   }

   public function testForgetBinding(): void
   {
      $this->container->bind('test', 'value');
      $this->assertTrue($this->container->bound('test'));

      $this->container->forget('test');
      $this->assertFalse($this->container->bound('test'));
   }

   public function testFlushBindings(): void
   {
      $this->container->bind('test1', 'value1');
      $this->container->bind('test2', 'value2');

      $this->assertTrue($this->container->bound('test1'));
      $this->assertTrue($this->container->bound('test2'));

      $this->container->flush();

      $this->assertFalse($this->container->bound('test1'));
      $this->assertFalse($this->container->bound('test2'));
   }

   public function testCircularDependencyDetection(): void
   {
      $this->expectException(Exception::class);
      $this->expectExceptionMessage('Circular dependency detected');

      // Create circular dependency scenario
      $this->container->bind('A', function ($container) {
         return $container->resolve('B');
      });

      $this->container->bind('B', function ($container) {
         return $container->resolve('A');
      });

      $this->container->resolve('A');
   }

   public function testClosureResolution(): void
   {
      $this->container->bind('test', function ($container) {
         return 'closure_result';
      });

      $result = $this->container->resolve('test');
      $this->assertEquals('closure_result', $result);
   }

   public function testStaticMethods(): void
   {
      Container::bindStatic('static_test', 'static_value');
      $result = Container::resolveStatic('static_test');

      $this->assertEquals('static_value', $result);
   }

   public function testGetBindings(): void
   {
      $this->container->bind('test1', 'value1');
      $this->container->singleton('test2', 'value2');

      $bindings = $this->container->getBindings();

      $this->assertArrayHasKey('test1', $bindings);
      $this->assertArrayHasKey('test2', $bindings);
      $this->assertFalse($bindings['test1']['singleton']);
      $this->assertTrue($bindings['test2']['singleton']);
   }
}
