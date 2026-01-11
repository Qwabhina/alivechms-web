<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Validator class
 */
class ValidatorTest extends TestCase
{
   public function testValidationPasses(): void
   {
      $data = [
         'name' => 'John Doe',
         'email' => 'john@example.com',
         'age' => 25
      ];

      $rules = [
         'name' => 'required|max:50',
         'email' => 'required|email',
         'age' => 'required|numeric|min:18'
      ];

      $validator = Validator::make($data, $rules);

      $this->assertTrue($validator->validate());
      $this->assertEmpty($validator->errors());
      $this->assertEquals($data, $validator->validated());
   }

   public function testValidationFails(): void
   {
      $data = [
         'name' => '',
         'email' => 'invalid-email',
         'age' => 'not-a-number'
      ];

      $rules = [
         'name' => 'required|max:50',
         'email' => 'required|email',
         'age' => 'required|numeric'
      ];

      $validator = Validator::make($data, $rules);

      $this->assertFalse($validator->validate());
      $this->assertNotEmpty($validator->errors());

      $errors = $validator->errors();
      $this->assertArrayHasKey('name', $errors);
      $this->assertArrayHasKey('email', $errors);
      $this->assertArrayHasKey('age', $errors);
   }

   public function testRequiredRule(): void
   {
      $validator = Validator::make(['name' => ''], ['name' => 'required']);
      $this->assertFalse($validator->validate());

      $validator = Validator::make(['name' => 'John'], ['name' => 'required']);
      $this->assertTrue($validator->validate());
   }

   public function testEmailRule(): void
   {
      $validator = Validator::make(['email' => 'invalid'], ['email' => 'email']);
      $this->assertFalse($validator->validate());

      $validator = Validator::make(['email' => 'test@example.com'], ['email' => 'email']);
      $this->assertTrue($validator->validate());
   }

   public function testNumericRule(): void
   {
      $validator = Validator::make(['age' => 'abc'], ['age' => 'numeric']);
      $this->assertFalse($validator->validate());

      $validator = Validator::make(['age' => '25'], ['age' => 'numeric']);
      $this->assertTrue($validator->validate());

      $validator = Validator::make(['age' => 25], ['age' => 'numeric']);
      $this->assertTrue($validator->validate());
   }

   public function testMinMaxRules(): void
   {
      $validator = Validator::make(['age' => 15], ['age' => 'min:18']);
      $this->assertFalse($validator->validate());

      $validator = Validator::make(['age' => 25], ['age' => 'min:18']);
      $this->assertTrue($validator->validate());

      $validator = Validator::make(['name' => 'Very long name that exceeds limit'], ['name' => 'max:10']);
      $this->assertFalse($validator->validate());

      $validator = Validator::make(['name' => 'Short'], ['name' => 'max:10']);
      $this->assertTrue($validator->validate());
   }

   public function testInRule(): void
   {
      $validator = Validator::make(['status' => 'invalid'], ['status' => 'in:active,inactive']);
      $this->assertFalse($validator->validate());

      $validator = Validator::make(['status' => 'active'], ['status' => 'in:active,inactive']);
      $this->assertTrue($validator->validate());
   }

   public function testDateRule(): void
   {
      $validator = Validator::make(['date' => 'invalid-date'], ['date' => 'date']);
      $this->assertFalse($validator->validate());

      $validator = Validator::make(['date' => '2025-01-01'], ['date' => 'date']);
      $this->assertTrue($validator->validate());
   }

   public function testPhoneRule(): void
   {
      $validator = Validator::make(['phone' => '123'], ['phone' => 'phone']);
      $this->assertFalse($validator->validate());

      $validator = Validator::make(['phone' => '+233241234567'], ['phone' => 'phone']);
      $this->assertTrue($validator->validate());
   }

   public function testCustomMessages(): void
   {
      $data = ['name' => ''];
      $rules = ['name' => 'required'];
      $messages = ['name.required' => 'Name is mandatory'];

      $validator = Validator::make($data, $rules, $messages);
      $validator->validate();

      $errors = $validator->errors();
      $this->assertEquals('Name is mandatory', $errors['name'][0]);
   }

   public function testRulesetRegistration(): void
   {
      $memberRules = [
         'first_name' => 'required|max:50',
         'email' => 'required|email',
         'phone' => 'required|phone'
      ];

      Validator::registerRuleset('member', $memberRules);
      $retrieved = Validator::getRuleset('member');

      $this->assertEquals($memberRules, $retrieved);
   }
}
