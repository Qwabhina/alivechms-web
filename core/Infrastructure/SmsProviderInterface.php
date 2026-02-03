<?php
declare(strict_types=1);

namespace AliveChMS\Core\Infrastructure;



/**
 * SMS Delivery Gateway – Pluggable Multi-Provider Architecture
 *
 * Fully abstracted SMS delivery system supporting multiple providers.
 * Designed for extensibility: any developer can add a new provider
 * by implementing the simple SmsProviderInterface.
 *
 * Default providers included:
 * - Hubtel (recommended for Ghana 2025)
 * - TextMe Ghana
 * - Generic HTTP (for any API with JSON POST)
 *
 * Provider is selected via .env: SMS_PROVIDER=hubtel|textme|generic
 *
 * @package  AliveChMS\Core
 * @version  1.0.2
 * @author   Benjamin Ebo Yankson
 * @since    2025-November
 */


/**
 * Interface for SMS providers.
 */
interface SmsProviderInterface
{
   /**
    * Send an SMS message.
    *
    * @param string $to Recipient phone number.
    * @param string $message Message content.
    * @return bool True on success.
    */
   public function send(string $to, string $message): bool;

   /**
    * Retrieve the last error message.
    *
    * @return string|null
    */
   public function getLastError(): ?string;
}
