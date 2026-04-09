<?php

/**
 * FileUploadService - Centralised File Upload Handling
 *
 * Provides reusable file upload functionality with:
 * - MIME type and extension validation
 * - File size limits
 * - Secure file naming
 * - Directory management
 *
 * @package  AliveChMS\Core\Services
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2026-February
 */

declare(strict_types=1);

namespace AliveChMS\Core\Services;

class FileUploadService
{
   private const ALLOWED_IMAGE_MIMES = [
      'image/jpeg',
      'image/png',
      'image/gif',
      'image/webp'
   ];

   private const ALLOWED_IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

   private const ALLOWED_DOCUMENT_MIMES = [
      'application/pdf',
      'application/msword',
      'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
      'application/vnd.ms-excel',
      'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
   ];

   private const ALLOWED_DOCUMENT_EXTENSIONS = ['pdf', 'doc', 'docx', 'xls', 'xlsx'];

   private const MAX_IMAGE_SIZE = 5 * 1024 * 1024;     // 5MB
   private const MAX_DOCUMENT_SIZE = 10 * 1024 * 1024; // 10MB

   /**
    * Handle profile image upload
    *
    * @param array  $file      $_FILES array element
    * @param string $subfolder Subfolder within uploads directory
    * @return string|null      Relative path to uploaded file, or null if no file
    * @throws \InvalidArgumentException If file validation fails
    * @throws \RuntimeException         If file move fails
    */
   public static function handleProfileImage(array $file, string $subfolder = 'members'): ?string
   {
      if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
         if ($file['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
         }
         throw new \InvalidArgumentException(self::getUploadErrorMessage($file['error']));
      }

      self::validateImage($file);

      return self::moveUploadedFile($file, $subfolder);
   }

   /**
    * Handle document upload (PDF, Word, Excel)
    *
    * @param array  $file      $_FILES array element
    * @param string $subfolder Subfolder within uploads directory
    * @return string|null      Relative path to uploaded file
    */
   public static function handleDocument(array $file, string $subfolder = 'documents'): ?string
   {
      if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
         if ($file['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
         }
         throw new \InvalidArgumentException(self::getUploadErrorMessage($file['error']));
      }

      self::validateDocument($file);

      return self::moveUploadedFile($file, $subfolder);
   }

   /**
    * Validate image file
    */
   public static function validateImage(array $file): void
   {
      // Validate MIME type using fileinfo
      $finfo = new \finfo(FILEINFO_MIME_TYPE);
      $mimeType = $finfo->file($file['tmp_name']);

      if (!in_array($mimeType, self::ALLOWED_IMAGE_MIMES, true)) {
         throw new \InvalidArgumentException(
            'Invalid file type. Only JPG, PNG, GIF, and WEBP images are allowed.'
         );
      }

      // Validate extension
      $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
      if (!in_array($extension, self::ALLOWED_IMAGE_EXTENSIONS, true)) {
         throw new \InvalidArgumentException(
            'Invalid file extension. Only .jpg, .png, .gif, and .webp are allowed.'
         );
      }

      // Validate size
      if ($file['size'] > self::MAX_IMAGE_SIZE) {
         throw new \InvalidArgumentException('Image size must not exceed 5MB.');
      }

      // Verify it's actually an image
      if (!getimagesize($file['tmp_name'])) {
         throw new \InvalidArgumentException('File is not a valid image.');
      }
   }

   /**
    * Validate document file
    */
   public static function validateDocument(array $file): void
   {
      $finfo = new \finfo(FILEINFO_MIME_TYPE);
      $mimeType = $finfo->file($file['tmp_name']);

      if (!in_array($mimeType, self::ALLOWED_DOCUMENT_MIMES, true)) {
         throw new \InvalidArgumentException(
            'Invalid file type. Only PDF, Word, and Excel documents are allowed.'
         );
      }

      $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
      if (!in_array($extension, self::ALLOWED_DOCUMENT_EXTENSIONS, true)) {
         throw new \InvalidArgumentException(
            'Invalid file extension. Only .pdf, .doc, .docx, .xls, .xlsx are allowed.'
         );
      }

      if ($file['size'] > self::MAX_DOCUMENT_SIZE) {
         throw new \InvalidArgumentException('Document size must not exceed 10MB.');
      }
   }

   /**
    * Move uploaded file to destination
    */
   private static function moveUploadedFile(array $file, string $subfolder): string
   {
      $uploadDir = dirname(__DIR__, 2) . '/uploads/' . $subfolder . '/';

      if (!is_dir($uploadDir)) {
         if (!mkdir($uploadDir, 0755, true)) {
            throw new \RuntimeException('Failed to create upload directory');
         }
      }

      $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
      $fileName = uniqid($subfolder . '_', true) . '.' . $extension;
      $filePath = $uploadDir . $fileName;

      if (!move_uploaded_file($file['tmp_name'], $filePath)) {
         throw new \RuntimeException('Failed to move uploaded file');
      }

      return 'uploads/' . $subfolder . '/' . $fileName;
   }

   /**
    * Delete a previously uploaded file
    *
    * @param string $relativePath Path relative to public directory
    * @return bool True if file was deleted
    */
   public static function deleteFile(string $relativePath): bool
   {
      if (empty($relativePath)) {
         return false;
      }

      $fullPath = dirname(__DIR__, 2) . '/' . $relativePath;

      if (file_exists($fullPath) && is_file($fullPath)) {
         return unlink($fullPath);
      }

      return false;
   }

   /**
    * Get human-readable upload error message
    */
   private static function getUploadErrorMessage(int $errorCode): string
   {
      return match ($errorCode) {
         UPLOAD_ERR_INI_SIZE   => 'File exceeds the maximum upload size allowed by the server.',
         UPLOAD_ERR_FORM_SIZE  => 'File exceeds the maximum size specified in the form.',
         UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
         UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
         UPLOAD_ERR_NO_TMP_DIR => 'Server is missing a temporary folder.',
         UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
         UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the file upload.',
         default               => 'Unknown upload error occurred.'
      };
   }
}
