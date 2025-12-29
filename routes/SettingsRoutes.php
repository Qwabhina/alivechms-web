<?php

/**
 * Settings API Routes
 *
 * Manages application-wide settings
 *
 * @package  AliveChMS\Routes
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-December
 */

declare(strict_types=1);

require_once __DIR__ . '/../core/Settings.php';

class SettingsRoutes extends BaseRoute
{
   public static function handle(): void
   {
      global $method, $path, $pathParts;

      self::rateLimit(maxAttempts: 60, windowSeconds: 60);

      match (true) {
         // GET ALL SETTINGS
         $method === 'GET' && $path === 'settings/all' => (function () {
            self::authenticate();
            self::authorize('manage_settings');

            $settings = Settings::getAll();
            self::success(['data' => $settings]);
         })(),

         // GET SETTINGS BY CATEGORY
         $method === 'GET' && $pathParts[0] === 'settings' && ($pathParts[1] ?? '') === 'category' => (function () {
            self::authenticate();
            self::authorize('manage_settings');

            $settings = Settings::getByCategory();
            self::success(['data' => $settings]);
         })(),

         // GET SINGLE SETTING
         $method === 'GET' && $pathParts[0] === 'settings' && ($pathParts[1] ?? '') === 'get' && isset($pathParts[2]) => (function () use ($pathParts) {
            self::authenticate();
            self::authorize('manage_settings');

            $key = $pathParts[2];
            $value = Settings::get($key);
            self::success(['key' => $key, 'value' => $value]);
         })(),

         // UPDATE SETTINGS (BULK)
         $method === 'POST' && $path === 'settings/update' => (function () {
            self::authenticate();
            self::authorize('manage_settings');

            $payload = self::getPayload();

            if (empty($payload['settings']) || !is_array($payload['settings'])) {
               Helpers::sendFeedback('Settings array is required', 400);
            }

            $result = Settings::updateBulk($payload['settings']);
            self::success($result, 'Settings updated successfully');
         })(),

         // INITIALIZE DEFAULT SETTINGS
         $method === 'POST' && $path === 'settings/initialize' => (function () {
            self::authenticate();
            self::authorize('manage_settings');

            $result = Settings::initializeDefaults();
            self::success($result);
         })(),

         // DELETE SETTING
         $method === 'DELETE' && $pathParts[0] === 'settings' && ($pathParts[1] ?? '') === 'delete' && isset($pathParts[2]) => (function () use ($pathParts) {
            self::authenticate();
            self::authorize('manage_settings');

            $key = $pathParts[2];
            $result = Settings::delete($key);
            self::success($result, 'Setting deleted');
         })(),

         // FALLBACK
         default => self::error('Settings endpoint not found', 404),
      };
   }
}

// Dispatch
SettingsRoutes::handle();
