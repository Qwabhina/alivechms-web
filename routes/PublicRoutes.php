<?php

/**
 * Public API Routes
 *
 * Public endpoints that don't require authentication
 * Includes settings, system info, etc.
 *
 * @package  AliveChMS\Routes
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-December
 */

declare(strict_types=1);

require_once __DIR__ . '/../core/Settings.php';
require_once __DIR__ . '/../core/SettingsHelper.php';
require_once __DIR__ . '/../core/ResponseHelper.php';

class PublicRoutes extends BaseRoute
{
   public static function handle(): void
   {
      global $method, $path, $pathParts;

      self::rateLimit(maxAttempts: 100, windowSeconds: 60);

      match (true) {
         // GET PUBLIC SETTINGS (for frontend)
         $method === 'GET' && $path === 'public/settings' => (function () {
            // Return only public-safe settings
            $publicSettings = [
               'church_name' => SettingsHelper::getChurchName(),
               'church_motto' => SettingsHelper::getChurchMotto(),
               'church_website' => SettingsHelper::getChurchWebsite(),
               'church_logo' => SettingsHelper::getChurchLogoUrl(),
               'currency_symbol' => SettingsHelper::getCurrencySymbol(),
               'currency_code' => SettingsHelper::getCurrencyCode(),
               'date_format' => SettingsHelper::getDateFormat(),
               'time_format' => SettingsHelper::getTimeFormat(),
               'timezone' => SettingsHelper::getTimezone(),
               'language' => SettingsHelper::getLanguage(),
               'items_per_page' => SettingsHelper::getItemsPerPage(),
            ];

            ResponseHelper::success($publicSettings);
         })(),

         // GET SYSTEM INFO
         $method === 'GET' && $path === 'public/info' => (function () {
            $info = [
               'name' => SettingsHelper::getChurchName(),
               'version' => '1.0.0',
               'timezone' => SettingsHelper::getTimezone(),
               'maintenance_mode' => SettingsHelper::isMaintenanceMode(),
            ];

            ResponseHelper::success($info);
         })(),

         // FALLBACK
         default => ResponseHelper::notFound('Public endpoint not found'),
      };
   }
}

// Dispatch
PublicRoutes::handle();
