<?php

/**
 * Lookup Data API Routes – v1
 *
 * Centralized access to all lookup/reference tables:
 * - Marital Status
 * - Education Level
 * - Membership Status
 * - Phone Type
 * - Asset Condition & Status
 * - Communication Channel & Status
 * - Family Relationship
 * - Document Category
 * - Payment Method
 *
 * Provides both individual endpoints and combined /all endpoint
 * for efficient form population.
 *
 * @package  AliveChMS\Routes
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2026-January
 */

declare(strict_types=1);

require_once __DIR__ . '/../core/ResponseHelper.php';

class LookupRoutes extends BaseRoute
{
   public static function handle(): void
   {
      // Get route variables from global scope
      global $method, $path, $pathParts;

      self::rateLimit(maxAttempts: 100, windowSeconds: 60);

      match (true) {
         // GET ALL LOOKUP DATA (Combined endpoint for efficiency)
         $method === 'GET' && $path === 'lookups/all' => (function () {
            self::authenticate();

            try {
               $orm = new ORM();

               $result = [
                  'marital_statuses' => $orm->runQuery(
                     "SELECT StatusID as id, StatusName as name 
                             FROM marital_status 
                             WHERE IsActive = 1 
                             ORDER BY StatusName"
                  ),
                  'education_levels' => $orm->runQuery(
                     "SELECT LevelID as id, LevelName as name 
                             FROM education_level 
                             WHERE IsActive = 1 
                             ORDER BY LevelName"
                  ),
                  'membership_statuses' => $orm->runQuery(
                     "SELECT StatusID as id, StatusName as name 
                             FROM membership_status 
                             WHERE IsActive = 1 
                             ORDER BY StatusName"
                  ),
                  'phone_types' => $orm->runQuery(
                     "SELECT TypeID as id, TypeName as name 
                             FROM phone_type 
                             ORDER BY TypeName"
                  ),
                  'asset_conditions' => $orm->runQuery(
                     "SELECT ConditionID as id, ConditionName as name 
                             FROM asset_condition 
                             ORDER BY ConditionName"
                  ),
                  'asset_statuses' => $orm->runQuery(
                     "SELECT StatusID as id, StatusName as name 
                             FROM asset_status 
                             ORDER BY StatusName"
                  ),
                  'communication_channels' => $orm->runQuery(
                     "SELECT ChannelID as id, ChannelName as name 
                             FROM communication_channel 
                             ORDER BY ChannelName"
                  ),
                  'communication_statuses' => $orm->runQuery(
                     "SELECT StatusID as id, StatusName as name 
                             FROM communication_status 
                             ORDER BY StatusName"
                  ),
                  'family_relationships' => $orm->runQuery(
                     "SELECT RelationshipID as id, RelationshipName as name 
                             FROM family_relationship 
                             ORDER BY RelationshipName"
                  ),
                  'document_categories' => $orm->runQuery(
                     "SELECT CategoryID as id, CategoryName as name 
                             FROM document_category 
                             ORDER BY CategoryName"
                  ),
                  'payment_methods' => $orm->runQuery(
                     "SELECT PaymentMethodID as id, PaymentMethodName as name 
                             FROM payment_method 
                             WHERE IsActive = 1 
                             ORDER BY PaymentMethodName"
                  ),
                  'branches' => $orm->runQuery(
                     "SELECT BranchID as id, BranchName as name, BranchCode as code 
                             FROM branch 
                             WHERE IsActive = 1 
                             ORDER BY BranchName"
                  )
               ];

               ResponseHelper::success($result, 'All lookup data retrieved');
            } catch (Exception $e) {
               ResponseHelper::error($e->getMessage(), 500);
            }
         })(),

         // INDIVIDUAL LOOKUP ENDPOINTS

         // Marital Status
         $method === 'GET' && $path === 'lookups/marital-statuses' => (function () {
            self::authenticate();
            $orm = new ORM();
            $data = $orm->runQuery(
               "SELECT StatusID as id, StatusName as name 
                     FROM marital_status 
                     WHERE IsActive = 1 
                     ORDER BY StatusName"
            );
            ResponseHelper::success($data);
         })(),

         // Education Levels
         $method === 'GET' && $path === 'lookups/education-levels' => (function () {
            self::authenticate();
            $orm = new ORM();
            $data = $orm->runQuery(
               "SELECT LevelID as id, LevelName as name 
                     FROM education_level 
                     WHERE IsActive = 1 
                     ORDER BY LevelName"
            );
            ResponseHelper::success($data);
         })(),

         // Membership Statuses
         $method === 'GET' && $path === 'lookups/membership-statuses' => (function () {
            self::authenticate();
            $orm = new ORM();
            $data = $orm->runQuery(
               "SELECT StatusID as id, StatusName as name 
                     FROM membership_status 
                     WHERE IsActive = 1 
                     ORDER BY StatusName"
            );
            ResponseHelper::success($data);
         })(),

         // Phone Types
         $method === 'GET' && $path === 'lookups/phone-types' => (function () {
            self::authenticate();
            $orm = new ORM();
            $data = $orm->runQuery(
               "SELECT TypeID as id, TypeName as name 
                     FROM phone_type 
                     ORDER BY TypeName"
            );
            ResponseHelper::success($data);
         })(),

         // Payment Methods
         $method === 'GET' && $path === 'lookups/payment-methods' => (function () {
            self::authenticate();
            $orm = new ORM();
            $data = $orm->runQuery(
               "SELECT PaymentMethodID as id, PaymentMethodName as name 
                     FROM payment_method 
                     WHERE IsActive = 1 
                     ORDER BY PaymentMethodName"
            );
            ResponseHelper::success($data);
         })(),

         // Branches
         $method === 'GET' && $path === 'lookups/branches' => (function () {
            self::authenticate();
            $orm = new ORM();
            $data = $orm->runQuery(
               "SELECT BranchID as id, BranchName as name, BranchCode as code 
                     FROM branch 
                     WHERE IsActive = 1 
                     ORDER BY BranchName"
            );
            ResponseHelper::success($data);
         })(),

         // FALLBACK
         default => ResponseHelper::notFound('Lookup endpoint not found'),
      };
   }
}

// Dispatch
LookupRoutes::handle();
