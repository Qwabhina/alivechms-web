<?php

/**
 * Settings Management
 *
 * Manages application-wide settings with categories and types.
 * Supports string, number, boolean, json, and array types.
 *
 * @package  AliveChMS\Core
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-November
 */

declare(strict_types=1);

require_once __DIR__ . '/ORM.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Helpers.php';

class Settings
{
   /**
    * Get a setting value by key
    *
    * @param string $key Setting key
    * @return mixed Setting value (typed)
    */
   public static function get(string $key)
   {
      $orm = new ORM();

      $result = $orm->getWhere('system_setting', ['SettingKey' => $key]);

      if (empty($result)) {
         return null;
      }

      $setting = $result[0];
      return self::castValue($setting['SettingValue'], $setting['SettingType']);
   }

   /**
    * Cast value based on type
    *
    * @param mixed $value Raw value
    * @param string $type Setting type
    * @return mixed Casted value
    */
   private static function castValue($value, string $type)
   {
      switch ($type) {
         case 'boolean':
            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
         case 'number':
            return is_numeric($value) ? (strpos($value, '.') !== false ? (float)$value : (int)$value) : 0;
         case 'json':
         case 'array':
            return json_decode($value, true);
         case 'string':
         default:
            return $value;
      }
   }

   /**
    * Set a setting value
    *
    * @param string $key Setting key
    * @param mixed $value Setting value
    * @param string $type Setting type (string, number, boolean, json, array)
    * @param string|null $category Category
    * @param string|null $description Optional description
    * @return array Success response
    */
   public static function set(string $key, $value, string $type = 'string', ?string $category = null, ?string $description = null): array
   {
      $orm = new ORM();

      $existing = $orm->getWhere('system_setting', ['SettingKey' => $key]);

      // Format value based on type
      $formattedValue = self::formatValue($value, $type);

      if (!empty($existing)) {
         $orm->update('system_setting', [
            'SettingValue' => $formattedValue,
            'SettingType' => $type,
            'Category' => $category,
            'Description' => $description,
            'UpdatedAt' => date('Y-m-d H:i:s')
         ], ['SettingKey' => $key]);
      } else {
         $orm->insert('system_setting', [
            'SettingKey' => $key,
            'SettingValue' => $formattedValue,
            'SettingType' => $type,
            'Category' => $category,
            'Description' => $description,
            'CreatedAt' => date('Y-m-d H:i:s'),
            'UpdatedAt' => date('Y-m-d H:i:s')
         ]);
      }

      Helpers::logError("Setting updated: $key");
      return ['status' => 'success'];
   }

   /**
    * Format value for storage based on type
    *
    * @param mixed $value Value to format
    * @param string $type Setting type
    * @return string Formatted value
    */
   private static function formatValue($value, string $type): string
   {
      switch ($type) {
         case 'boolean':
            return $value ? '1' : '0';
         case 'json':
         case 'array':
            return json_encode($value);
         case 'number':
         case 'string':
         default:
            return (string)$value;
      }
   }

   /**
    * Get all settings
    *
    * @param string|null $category Filter by category
    * @return array All settings with typed values
    */
   public static function getAll(?string $category = null): array
   {
      $orm = new ORM();

      $conditions = [];
      if ($category) {
         $conditions['Category'] = $category;
      }

      $settings = empty($conditions) ? $orm->getAll('system_setting') : $orm->getWhere('system_setting', $conditions);

      return array_map(function ($setting) {
         return [
            'id' => $setting['SettingID'],
            'key' => $setting['SettingKey'],
            'value' => self::castValue($setting['SettingValue'], $setting['SettingType']),
            'type' => $setting['SettingType'],
            'category' => $setting['Category'],
            'description' => $setting['Description'],
            'updated_at' => $setting['UpdatedAt']
         ];
      }, $settings);
   }

   /**
    * Get settings grouped by category
    *
    * @return array Settings grouped by category
    */
   public static function getByCategory(): array
   {
      $allSettings = self::getAll();
      $grouped = [];

      foreach ($allSettings as $setting) {
         $category = $setting['category'] ?? 'general';
         if (!isset($grouped[$category])) {
            $grouped[$category] = [];
         }
         $grouped[$category][] = $setting;
      }

      return $grouped;
   }

   /**
    * Update multiple settings at once
    *
    * @param array $settings Array of settings with key, value, type
    * @return array Success response
    */
   public static function updateBulk(array $settings): array
   {
      $orm = new ORM();

      $orm->beginTransaction();
      try {
         foreach ($settings as $setting) {
            $key = $setting['key'];
            $value = $setting['value'];
            $type = $setting['type'] ?? 'string';
            $category = $setting['category'] ?? null;
            $description = $setting['description'] ?? null;

            self::set($key, $value, $type, $category, $description);
         }
         $orm->commit();
         return ['status' => 'success', 'message' => 'Settings updated successfully'];
      } catch (Exception $e) {
         $orm->rollBack();
         throw $e;
      }
   }

   /**
    * Delete a setting
    *
    * @param string $key Setting key
    * @return array Success response
    */
   public static function delete(string $key): array
   {
      $orm = new ORM();

      $orm->delete('system_setting', ['SettingKey' => $key]);

      Helpers::logError("Setting deleted: $key");
      return ['status' => 'success'];
   }

   /**
    * Initialize default settings if they don't exist
    *
    * @return array Success response
    */
   public static function initializeDefaults(): array
   {
      $defaults = [
         // General Settings
         ['key' => 'church_name', 'value' => 'Alive Church', 'type' => 'string', 'category' => 'General', 'description' => 'Church name'],
         ['key' => 'church_motto', 'value' => 'Faith, Hope, and Love', 'type' => 'string', 'category' => 'General', 'description' => 'Church motto or tagline'],
         ['key' => 'church_email', 'value' => 'info@alivechurch.org', 'type' => 'string', 'category' => 'General', 'description' => 'Church email address'],
         ['key' => 'church_phone', 'value' => '+233 000 000 000', 'type' => 'string', 'category' => 'General', 'description' => 'Church phone number'],
         ['key' => 'church_address', 'value' => 'Accra, Ghana', 'type' => 'string', 'category' => 'General', 'description' => 'Church physical address'],
         ['key' => 'church_website', 'value' => 'https://alivechurch.org', 'type' => 'string', 'category' => 'General', 'description' => 'Church website URL'],
         ['key' => 'church_logo', 'value' => '', 'type' => 'string', 'category' => 'General', 'description' => 'Church logo path (relative to public folder)'],

         // Regional Settings
         ['key' => 'currency_symbol', 'value' => 'GH₵', 'type' => 'string', 'category' => 'Regional', 'description' => 'Currency symbol'],
         ['key' => 'currency_code', 'value' => 'GHS', 'type' => 'string', 'category' => 'Regional', 'description' => 'Currency code (ISO 4217)'],
         ['key' => 'date_format', 'value' => 'Y-m-d', 'type' => 'string', 'category' => 'Regional', 'description' => 'Date format'],
         ['key' => 'time_format', 'value' => 'H:i', 'type' => 'string', 'category' => 'Regional', 'description' => 'Time format'],
         ['key' => 'timezone', 'value' => 'Africa/Accra', 'type' => 'string', 'category' => 'Regional', 'description' => 'System timezone'],
         ['key' => 'language', 'value' => 'en', 'type' => 'string', 'category' => 'Regional', 'description' => 'Default language'],

         // Email Settings
         ['key' => 'enable_email', 'value' => 1, 'type' => 'boolean', 'category' => 'Email', 'description' => 'Enable email notifications'],
         ['key' => 'smtp_host', 'value' => '', 'type' => 'string', 'category' => 'Email', 'description' => 'SMTP server host'],
         ['key' => 'smtp_port', 'value' => 587, 'type' => 'number', 'category' => 'Email', 'description' => 'SMTP server port'],
         ['key' => 'smtp_username', 'value' => '', 'type' => 'string', 'category' => 'Email', 'description' => 'SMTP username'],
         ['key' => 'smtp_password', 'value' => '', 'type' => 'string', 'category' => 'Email', 'description' => 'SMTP password'],
         ['key' => 'smtp_encryption', 'value' => 'tls', 'type' => 'string', 'category' => 'Email', 'description' => 'SMTP encryption (tls/ssl)'],
         ['key' => 'email_from_name', 'value' => 'Alive Church', 'type' => 'string', 'category' => 'Email', 'description' => 'Email sender name'],
         ['key' => 'email_from_address', 'value' => 'noreply@alivechurch.org', 'type' => 'string', 'category' => 'Email', 'description' => 'Email sender address'],

         // SMS Settings
         ['key' => 'enable_sms', 'value' => 0, 'type' => 'boolean', 'category' => 'SMS', 'description' => 'Enable SMS notifications'],
         ['key' => 'sms_gateway', 'value' => '', 'type' => 'string', 'category' => 'SMS', 'description' => 'SMS gateway provider'],
         ['key' => 'sms_api_key', 'value' => '', 'type' => 'string', 'category' => 'SMS', 'description' => 'SMS API key'],
         ['key' => 'sms_sender_id', 'value' => 'AliveChurch', 'type' => 'string', 'category' => 'SMS', 'description' => 'SMS sender ID'],

         // System Settings
         ['key' => 'items_per_page', 'value' => 10, 'type' => 'number', 'category' => 'System', 'description' => 'Default items per page in tables'],
         ['key' => 'session_timeout', 'value' => 3600, 'type' => 'number', 'category' => 'System', 'description' => 'Session timeout in seconds'],
         ['key' => 'maintenance_mode', 'value' => 0, 'type' => 'boolean', 'category' => 'System', 'description' => 'Enable maintenance mode'],
         ['key' => 'enable_audit_log', 'value' => 1, 'type' => 'boolean', 'category' => 'System', 'description' => 'Enable audit logging'],
         ['key' => 'max_login_attempts', 'value' => 5, 'type' => 'number', 'category' => 'System', 'description' => 'Maximum login attempts before lockout'],
         ['key' => 'lockout_duration', 'value' => 900, 'type' => 'number', 'category' => 'System', 'description' => 'Account lockout duration in seconds'],

         // Backup Settings
         ['key' => 'backup_enabled', 'value' => 0, 'type' => 'boolean', 'category' => 'Backup', 'description' => 'Enable automatic backups'],
         ['key' => 'backup_frequency', 'value' => 'daily', 'type' => 'string', 'category' => 'Backup', 'description' => 'Backup frequency (daily/weekly/monthly)'],
         ['key' => 'backup_retention_days', 'value' => 30, 'type' => 'number', 'category' => 'Backup', 'description' => 'Number of days to retain backups'],

         // Notification Settings
         ['key' => 'notify_new_member', 'value' => 1, 'type' => 'boolean', 'category' => 'Notifications', 'description' => 'Notify on new member registration'],
         ['key' => 'notify_contribution', 'value' => 1, 'type' => 'boolean', 'category' => 'Notifications', 'description' => 'Notify on new contribution'],
         ['key' => 'notify_event_reminder', 'value' => 1, 'type' => 'boolean', 'category' => 'Notifications', 'description' => 'Send event reminders'],
         ['key' => 'event_reminder_days', 'value' => 1, 'type' => 'number', 'category' => 'Notifications', 'description' => 'Days before event to send reminder'],

         // Financial Settings
         ['key' => 'default_fiscal_year', 'value' => 1, 'type' => 'number', 'category' => 'Financial', 'description' => 'Default fiscal year ID'],
         ['key' => 'require_receipt_number', 'value' => 1, 'type' => 'boolean', 'category' => 'Financial', 'description' => 'Require receipt number for contributions'],
         ['key' => 'auto_generate_receipt', 'value' => 1, 'type' => 'boolean', 'category' => 'Financial', 'description' => 'Auto-generate receipt numbers'],
      ];

      foreach ($defaults as $setting) {
         if (self::get($setting['key']) === null) {
            self::set($setting['key'], $setting['value'], $setting['type'], $setting['category'], $setting['description']);
         }
      }

      return ['status' => 'success', 'message' => 'Default settings initialized'];
   }
}
