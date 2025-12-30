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

            // Clear settings cache
            if (class_exists('SettingsHelper')) {
               SettingsHelper::clearCache();
            }

            self::success($result, 'Settings updated successfully');
         })(),

         // UPLOAD CHURCH LOGO
         $method === 'POST' && $path === 'settings/upload-logo' => (function () {
            self::authenticate();
            self::authorize('manage_settings');

            // Check if file was uploaded
            if (!isset($_FILES['logo']) || $_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
               self::error('No file uploaded or upload error occurred', 400);
            }

            $file = $_FILES['logo'];

            // Validate file type
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($file['tmp_name']);
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml', 'image/webp'];

            if (!in_array($mimeType, $allowedTypes)) {
               self::error('Invalid file type. Allowed: JPG, PNG, GIF, SVG, WebP', 400);
            }

            // Validate file size (max 2MB)
            if ($file['size'] > 2 * 1024 * 1024) {
               self::error('File too large. Maximum size: 2MB', 400);
            }

            // Create upload directory if not exists
            $uploadDir = __DIR__ . '/../public/uploads/logos/';
            if (!is_dir($uploadDir)) {
               mkdir($uploadDir, 0755, true);
            }

            // Generate unique filename
            $extension = match ($mimeType) {
               'image/jpeg' => 'jpg',
               'image/png' => 'png',
               'image/gif' => 'gif',
               'image/svg+xml' => 'svg',
               'image/webp' => 'webp',
               default => 'jpg'
            };
            $filename = 'church_logo_' . time() . '.' . $extension;
            $filepath = $uploadDir . $filename;
            $relativePath = 'uploads/logos/' . $filename;

            // Delete old logo if exists
            $oldLogo = Settings::get('church_logo');
            if ($oldLogo && file_exists(__DIR__ . '/../public/' . $oldLogo)) {
               unlink(__DIR__ . '/../public/' . $oldLogo);
            }

            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $filepath)) {
               self::error('Failed to save uploaded file', 500);
            }

            // Update setting
            Settings::set('church_logo', $relativePath, 'string', 'general', 'Church logo path');

            // Clear settings cache
            if (class_exists('SettingsHelper')) {
               SettingsHelper::clearCache();
            }

            Helpers::logError("Church logo uploaded: $relativePath");

            self::success([
               'path' => $relativePath,
               'url' => '/public/' . $relativePath
            ], 'Logo uploaded successfully');
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
