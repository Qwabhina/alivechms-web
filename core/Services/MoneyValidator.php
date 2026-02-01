<?php

/**
 * MoneyValidator - Centralised Monetary Amount Validation
 *
 * Provides consistent validation and formatting for monetary amounts across
 * the application. Replaces duplicate validateAmount() methods in Contribution
 * and Expense classes.
 *
 * @package  AliveChMS\Core\Services
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2026-February
 */

declare(strict_types=1);

namespace AliveChMS\Core\Services;

class MoneyValidator
{
   private const MAX_AMOUNT = 999999999.99;
   private const MIN_AMOUNT = 0.01;

   /**
    * Validate and sanitize a monetary amount
    *
    * @param mixed  $amount    The amount to validate (string, int, float)
    * @param string $fieldName Field name for error messages
    * @return float Validated and sanitized amount
    * @throws \InvalidArgumentException If validation fails
    */
   public static function validateAmount($amount, string $fieldName = 'Amount'): float
   {
      // Check for null/empty
      if ($amount === null || $amount === '') {
         throw new \InvalidArgumentException("{$fieldName} is required");
      }

      // Handle string with currency symbols or formatting
      if (is_string($amount)) {
         // Remove currency symbols, commas, and whitespace
         $amount = preg_replace('/[^0-9.\-]/', '', trim($amount));

         if ($amount === '' || $amount === '-') {
            throw new \InvalidArgumentException("{$fieldName} must be a valid number");
         }
      }

      // Convert to float
      $amount = (float) $amount;

      // Validate range
      if ($amount < self::MIN_AMOUNT) {
         throw new \InvalidArgumentException("{$fieldName} must be at least " . self::MIN_AMOUNT);
      }

      if ($amount > self::MAX_AMOUNT) {
         throw new \InvalidArgumentException("{$fieldName} exceeds maximum allowed value of " . number_format(self::MAX_AMOUNT, 2));
      }

      // Check for valid number (not NaN or infinite)
      if (!is_finite($amount)) {
         throw new \InvalidArgumentException("{$fieldName} must be a valid number");
      }

      // Round to 2 decimal places
      return round($amount, 2);
   }

   /**
    * Validate amount allowing zero (for balance adjustments, etc.)
    *
    * @param mixed  $amount    The amount to validate
    * @param string $fieldName Field name for error messages
    * @return float Validated amount
    */
   public static function validateAmountAllowZero($amount, string $fieldName = 'Amount'): float
   {
      if ($amount === null || $amount === '') {
         throw new \InvalidArgumentException("{$fieldName} is required");
      }

      if (is_string($amount)) {
         $amount = preg_replace('/[^0-9.\-]/', '', trim($amount));
      }

      $amount = (float) $amount;

      if ($amount < 0) {
         throw new \InvalidArgumentException("{$fieldName} cannot be negative");
      }

      if ($amount > self::MAX_AMOUNT) {
         throw new \InvalidArgumentException("{$fieldName} exceeds maximum allowed value");
      }

      if (!is_finite($amount)) {
         throw new \InvalidArgumentException("{$fieldName} must be a valid number");
      }

      return round($amount, 2);
   }

   /**
    * Format amount for display with currency
    *
    * @param float  $amount   Amount to format
    * @param string $currency Currency code (default: GHS for Ghanaian Cedi)
    * @return string Formatted amount with currency
    */
   public static function format(float $amount, string $currency = 'GHS'): string
   {
      return $currency . ' ' . number_format($amount, 2);
   }

   /**
    * Format amount without currency symbol
    *
    * @param float $amount Amount to format
    * @return string Formatted amount
    */
   public static function formatPlain(float $amount): string
   {
      return number_format($amount, 2);
   }

   /**
    * Parse a formatted amount string back to float
    *
    * @param string $formattedAmount Formatted amount (e.g., "GHS 1,234.56")
    * @return float Parsed amount
    */
   public static function parse(string $formattedAmount): float
   {
      $cleaned = preg_replace('/[^0-9.\-]/', '', $formattedAmount);
      return (float) $cleaned;
   }
}
