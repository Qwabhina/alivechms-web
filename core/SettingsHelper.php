<?php

/**
 * Settings Helper
 *
 * Provides cached, easy access to settings throughout the application.
 * Automatically loads settings on first access and caches them for performance.
 *
 * @package  AliveChMS\Core
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-December
 */

declare(strict_types=1);

class SettingsHelper
{
   private static ?array $cache = null;
   private static ?array $defaults = null;

   /**
    * Get default values for settings
    */
   private static function getDefaults(): array
   {
      if (self::$defaults !== null) {
         return self::$defaults;
      }

      self::$defaults = [
         'church_name' => 'AliveChMS Church',
         'church_motto' => 'Faith, Hope, and Love',
         'church_email' => 'info@alivechms.org',
         'church_phone' => '+233 24 123 4567',
         'church_address' => 'Accra, Ghana',
         'church_website' => 'https://www.alivechms.org',
         'currency_symbol' => 'GH₵',
         'currency_code' => 'GHS',
         'date_format' => 'Y-m-d',
         'time_format' => 'H:i',
         'timezone' => 'Africa/Accra',
         'language' => 'en',
         'items_per_page' => 10,
      ];

      return self::$defaults;
   }

   /**
    * Load all settings into cache
    */
   private static function loadSettings(): void
   {
      if (self::$cache !== null) {
         return;
      }

      self::$cache = [];

      try {
         // Check if Settings class exists
         if (!class_exists('Settings')) {
            require_once __DIR__ . '/Settings.php';
         }

         $settings = Settings::getAll();

         foreach ($settings as $setting) {
            self::$cache[$setting['key']] = $setting['value'];
         }
      } catch (Exception $e) {
         // If settings table doesn't exist or error occurs, use defaults
         if (class_exists('Helpers')) {
            Helpers::logError("Settings load error: " . $e->getMessage());
         }
         self::$cache = self::getDefaults();
      }
   }

   /**
    * Get a setting value with fallback to default
    *
    * @param string $key Setting key
    * @param mixed $default Default value if setting not found
    * @return mixed Setting value
    */
   public static function get(string $key, $default = null)
   {
      self::loadSettings();

      if (isset(self::$cache[$key])) {
         return self::$cache[$key];
      }

      // Try defaults
      $defaults = self::getDefaults();
      if (isset($defaults[$key])) {
         return $defaults[$key];
      }

      return $default;
   }

   /**
    * Get church name
    */
   public static function getChurchName(): string
   {
      return self::get('church_name', 'AliveChMS Church');
   }

   /**
    * Get church motto
    */
   public static function getChurchMotto(): string
   {
      return self::get('church_motto', 'Faith, Hope, and Love');
   }

   /**
    * Get church email
    */
   public static function getChurchEmail(): string
   {
      return self::get('church_email', 'info@alivechms.org');
   }

   /**
    * Get church phone
    */
   public static function getChurchPhone(): string
   {
      return self::get('church_phone', '+233 24 123 4567');
   }

   /**
    * Get church address
    */
   public static function getChurchAddress(): string
   {
      return self::get('church_address', 'Accra, Ghana');
   }

   /**
    * Get church website
    */
   public static function getChurchWebsite(): string
   {
      return self::get('church_website', 'https://www.alivechms.org');
   }

   /**
    * Get church logo path
    */
   public static function getChurchLogo(): ?string
   {
      return self::get('church_logo', null);
   }

   /**
    * Check if church logo exists
    */
   public static function hasChurchLogo(): bool
   {
      $logo = self::getChurchLogo();
      return !empty($logo) && file_exists(__DIR__ . '/../public/' . $logo);
   }

   /**
    * Get church logo URL
    */
   public static function getChurchLogoUrl(): ?string
   {
      $logo = self::getChurchLogo();
      if (empty($logo)) {
         return null;
      }
      return '/public/' . $logo;
   }

   /**
    * Get currency symbol
    */
   public static function getCurrencySymbol(): string
   {
      return self::get('currency_symbol', 'GH₵');
   }

   /**
    * Get currency code
    */
   public static function getCurrencyCode(): string
   {
      return self::get('currency_code', 'GHS');
   }

   /**
    * Get date format
    */
   public static function getDateFormat(): string
   {
      return self::get('date_format', 'Y-m-d');
   }

   /**
    * Get time format
    */
   public static function getTimeFormat(): string
   {
      return self::get('time_format', 'H:i');
   }

   /**
    * Get timezone
    */
   public static function getTimezone(): string
   {
      return self::get('timezone', 'Africa/Accra');
   }

   /**
    * Get language
    */
   public static function getLanguage(): string
   {
      return self::get('language', 'en');
   }

   /**
    * Format currency amount
    *
    * @param float $amount Amount to format
    * @param bool $includeSymbol Include currency symbol
    * @return string Formatted amount
    */
   public static function formatCurrency(float $amount, bool $includeSymbol = true): string
   {
      $formatted = number_format($amount, 2);
      return $includeSymbol ? self::getCurrencySymbol() . ' ' . $formatted : $formatted;
   }

   /**
    * Format date according to settings
    *
    * @param string|int $date Date string or timestamp
    * @return string Formatted date
    */
   public static function formatDate($date): string
   {
      if (is_numeric($date)) {
         return date(self::getDateFormat(), $date);
      }
      return date(self::getDateFormat(), strtotime($date));
   }

   /**
    * Format time according to settings
    *
    * @param string|int $time Time string or timestamp
    * @return string Formatted time
    */
   public static function formatTime($time): string
   {
      if (is_numeric($time)) {
         return date(self::getTimeFormat(), $time);
      }
      return date(self::getTimeFormat(), strtotime($time));
   }

   /**
    * Format datetime according to settings
    *
    * @param string|int $datetime Datetime string or timestamp
    * @return string Formatted datetime
    */
   public static function formatDateTime($datetime): string
   {
      $format = self::getDateFormat() . ' ' . self::getTimeFormat();
      if (is_numeric($datetime)) {
         return date($format, $datetime);
      }
      return date($format, strtotime($datetime));
   }

   /**
    * Clear settings cache (useful after settings update)
    */
   public static function clearCache(): void
   {
      self::$cache = null;
   }

   /**
    * Get all settings as array
    */
   public static function getAll(): array
   {
      self::loadSettings();
      return self::$cache;
   }

   /**
    * Check if email is enabled
    */
   public static function isEmailEnabled(): bool
   {
      return (bool)self::get('email_enabled', true);
   }

   /**
    * Check if SMS is enabled
    */
   public static function isSmsEnabled(): bool
   {
      return (bool)self::get('sms_enabled', false);
   }

   /**
    * Check if maintenance mode is enabled
    */
   public static function isMaintenanceMode(): bool
   {
      return (bool)self::get('maintenance_mode', false);
   }

   /**
    * Get items per page for pagination
    */
   public static function getItemsPerPage(): int
   {
      return (int)self::get('items_per_page', 10);
   }
}
