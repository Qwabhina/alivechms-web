<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Integration tests for ORM class
 * These tests require a test database connection
 */
class ORMTest extends TestCase
{
   private ORM $orm;
   private static bool $setupComplete = false;

   protected function setUp(): void
   {
      parent::setUp();

      $this->orm = new ORM();

      // Create test table if not exists (only once)
      if (!self::$setupComplete) {
         $this->createTestTable();
         self::$setupComplete = true;
      }

      // Clean test data before each test
      $this->cleanTestData();
   }

   protected function tearDown(): void
   {
      $this->cleanTestData();
      parent::tearDown();
   }

   private function createTestTable(): void
   {
      $sql = "
            CREATE TABLE IF NOT EXISTS test_users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(100) UNIQUE NOT NULL,
                age INT DEFAULT NULL,
                active TINYINT(1) DEFAULT 1,
                deleted TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ";

      try {
         $this->orm->runQuery($sql);
      } catch (Exception $e) {
         $this->markTestSkipped('Could not create test table: ' . $e->getMessage());
      }
   }

   private function cleanTestData(): void
   {
      try {
         $this->orm->runQuery("DELETE FROM test_users WHERE name LIKE 'Test%'");
      } catch (Exception $e) {
         // Ignore cleanup errors
      }
   }

   public function testInsert(): void
   {
      $data = [
         'name' => 'Test User',
         'email' => 'test@example.com',
         'age' => 25
      ];

      $result = $this->orm->insert('test_users', $data);

      $this->assertIsArray($result);
      $this->assertArrayHasKey('id', $result);
      $this->assertGreaterThan(0, $result['id']);
   }

   public function testGetWhere(): void
   {
      // Insert test data
      $data = [
         'name' => 'Test User 2',
         'email' => 'test2@example.com',
         'age' => 30
      ];
      $this->orm->insert('test_users', $data);

      // Test getWhere
      $results = $this->orm->getWhere('test_users', ['name' => 'Test User 2']);

      $this->assertIsArray($results);
      $this->assertCount(1, $results);
      $this->assertEquals('Test User 2', $results[0]['name']);
      $this->assertEquals('test2@example.com', $results[0]['email']);
      $this->assertEquals(30, $results[0]['age']);
   }

   public function testUpdate(): void
   {
      // Insert test data
      $data = [
         'name' => 'Test User 3',
         'email' => 'test3@example.com',
         'age' => 25
      ];
      $result = $this->orm->insert('test_users', $data);
      $userId = $result['id'];

      // Update the record
      $updateData = ['age' => 26, 'name' => 'Test User 3 Updated'];
      $affectedRows = $this->orm->update('test_users', $updateData, ['id' => $userId]);

      $this->assertEquals(1, $affectedRows);

      // Verify the update
      $updated = $this->orm->getWhere('test_users', ['id' => $userId]);
      $this->assertEquals('Test User 3 Updated', $updated[0]['name']);
      $this->assertEquals(26, $updated[0]['age']);
   }

   public function testDelete(): void
   {
      // Insert test data
      $data = [
         'name' => 'Test User 4',
         'email' => 'test4@example.com'
      ];
      $result = $this->orm->insert('test_users', $data);
      $userId = $result['id'];

      // Delete the record
      $affectedRows = $this->orm->delete('test_users', ['id' => $userId]);
      $this->assertEquals(1, $affectedRows);

      // Verify deletion
      $deleted = $this->orm->getWhere('test_users', ['id' => $userId]);
      $this->assertEmpty($deleted);
   }

   public function testSoftDelete(): void
   {
      // Insert test data
      $data = [
         'name' => 'Test User 5',
         'email' => 'test5@example.com'
      ];
      $result = $this->orm->insert('test_users', $data);
      $userId = $result['id'];

      // Soft delete the record
      $affectedRows = $this->orm->softDelete('test_users', $userId);
      $this->assertEquals(1, $affectedRows);

      // Verify soft deletion (record exists but marked as deleted)
      $softDeleted = $this->orm->runQuery("SELECT * FROM test_users WHERE id = ?", [$userId]);
      $this->assertCount(1, $softDeleted);
      $this->assertEquals(1, $softDeleted[0]['deleted']);

      // Verify it's excluded from normal queries
      $normalQuery = $this->orm->getWhere('test_users', ['id' => $userId]);
      $this->assertEmpty($normalQuery);
   }

   public function testCount(): void
   {
      // Insert multiple test records
      $this->orm->insert('test_users', ['name' => 'Test Count 1', 'email' => 'count1@example.com']);
      $this->orm->insert('test_users', ['name' => 'Test Count 2', 'email' => 'count2@example.com']);
      $this->orm->insert('test_users', ['name' => 'Test Count 3', 'email' => 'count3@example.com']);

      $count = $this->orm->count('test_users', ['name' => 'Test Count 1']);
      $this->assertEquals(1, $count);

      $totalCount = $this->orm->count('test_users');
      $this->assertGreaterThanOrEqual(3, $totalCount);
   }

   public function testExists(): void
   {
      // Insert test data
      $data = [
         'name' => 'Test Exists',
         'email' => 'exists@example.com'
      ];
      $this->orm->insert('test_users', $data);

      $this->assertTrue($this->orm->exists('test_users', ['email' => 'exists@example.com']));
      $this->assertFalse($this->orm->exists('test_users', ['email' => 'nonexistent@example.com']));
   }

   public function testGetAll(): void
   {
      // Insert multiple test records
      $this->orm->insert('test_users', ['name' => 'Test GetAll 1', 'email' => 'getall1@example.com']);
      $this->orm->insert('test_users', ['name' => 'Test GetAll 2', 'email' => 'getall2@example.com']);
      $this->orm->insert('test_users', ['name' => 'Test GetAll 3', 'email' => 'getall3@example.com']);

      // Test without limit
      $all = $this->orm->getAll('test_users');
      $this->assertGreaterThanOrEqual(3, count($all));

      // Test with limit
      $limited = $this->orm->getAll('test_users', 2);
      $this->assertCount(2, $limited);

      // Test with limit and offset
      $offset = $this->orm->getAll('test_users', 2, 1);
      $this->assertCount(2, $offset);
   }

   public function testTransactions(): void
   {
      $this->orm->beginTransaction();

      try {
         // Insert first record
         $result1 = $this->orm->insert('test_users', [
            'name' => 'Test Transaction 1',
            'email' => 'transaction1@example.com'
         ]);

         // Insert second record
         $result2 = $this->orm->insert('test_users', [
            'name' => 'Test Transaction 2',
            'email' => 'transaction2@example.com'
         ]);

         $this->orm->commit();

         // Verify both records exist
         $this->assertTrue($this->orm->exists('test_users', ['id' => $result1['id']]));
         $this->assertTrue($this->orm->exists('test_users', ['id' => $result2['id']]));
      } catch (Exception $e) {
         $this->orm->rollBack();
         throw $e;
      }
   }

   public function testTransactionRollback(): void
   {
      $this->orm->beginTransaction();

      try {
         // Insert a record
         $result = $this->orm->insert('test_users', [
            'name' => 'Test Rollback',
            'email' => 'rollback@example.com'
         ]);

         // Simulate an error and rollback
         $this->orm->rollBack();

         // Verify record doesn't exist after rollback
         $this->assertFalse($this->orm->exists('test_users', ['id' => $result['id']]));
      } catch (Exception $e) {
         $this->orm->rollBack();
         throw $e;
      }
   }

   public function testSelectWithJoin(): void
   {
      // This test would require a more complex setup with related tables
      // For now, we'll test a simple join with the same table (self-join)

      $this->orm->insert('test_users', ['name' => 'Test Join 1', 'email' => 'join1@example.com', 'age' => 25]);
      $this->orm->insert('test_users', ['name' => 'Test Join 2', 'email' => 'join2@example.com', 'age' => 25]);

      $results = $this->orm->selectWithJoin(
         baseTable: 'test_users u1',
         joins: [
            [
               'table' => 'test_users u2',
               'on' => 'u1.age = u2.age AND u1.id != u2.id',
               'type' => 'INNER'
            ]
         ],
         fields: ['u1.name as name1', 'u2.name as name2'],
         conditions: ['u1.age' => ':age'],
         params: [':age' => 25]
      );

      $this->assertIsArray($results);
      // Should find records with same age but different IDs
      $this->assertGreaterThanOrEqual(1, count($results));
   }
}
