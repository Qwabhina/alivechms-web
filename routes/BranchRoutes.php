<?php

/**
 * Branch API Routes
 *
 * Basic branch management endpoints
 *
 * @package  AliveChMS\Routes
 * @version  1.0.0
 */

declare(strict_types=1);

require_once __DIR__ . '/../core/ResponseHelper.php';

class BranchRoutes extends BaseRoute
{
   public static function handle(): void
   {
      global $method, $path, $pathParts;

      self::rateLimit(maxAttempts: 60, windowSeconds: 60);

      match (true) {
         // LIST ALL BRANCHES
         $method === 'GET' && $path === 'branch/all' => (function () {
            self::authenticate();

            $orm = new ORM();
            [$page, $limit] = self::getPagination(100, 500);
            $offset = ($page - 1) * $limit;

            $branches = $orm->runQuery(
               "SELECT BranchID, BranchName, BranchAddress, BranchPhoneNumber, BranchEmailAddress 
                FROM branch ORDER BY BranchName ASC LIMIT :limit OFFSET :offset",
               [':limit' => $limit, ':offset' => $offset]
            );

            $total = $orm->runQuery("SELECT COUNT(*) AS total FROM branch")[0]['total'];

            ResponseHelper::paginated($branches, (int)$total, $page, $limit);
         })(),

         // VIEW SINGLE BRANCH
         $method === 'GET' && $pathParts[0] === 'branch' && ($pathParts[1] ?? '') === 'view' && isset($pathParts[2]) => (function () use ($pathParts) {
            self::authenticate();

            $branchId = self::getIdFromPath($pathParts, 2, 'Branch ID');
            $orm = new ORM();

            $branch = $orm->getWhere('branch', ['BranchID' => $branchId]);
            if (empty($branch)) {
               ResponseHelper::error('Branch not found', 404);
            }

            ResponseHelper::success($branch[0]);
         })(),

         default => ResponseHelper::notFound('Branch endpoint not found'),
      };
   }
}

BranchRoutes::handle();
