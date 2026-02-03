<?php

/**
 * Document Management Service
 *
 * Orchestrates file metadata persistence and delegates database operations to DocumentRepository.
 * Note: File I/O remains in this service as it's a side effect / business concern.
 *
 * @package  AliveChMS\Core
 * @version  2.0.0
 */

declare(strict_types=1);

namespace AliveChMS\Core\Operations;

use AliveChMS\Core\Operations\DocumentRepository;
use AliveChMS\Core\System\Helpers;
use AliveChMS\Core\System\ResponseHelper;
use AliveChMS\Core\Identity\Auth;
use Exception;
use finfo;

class Document
{
   private const UPLOAD_DIR = __DIR__ . '/../public/uploads/documents/';
   private const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB
   private const ALLOWED_TYPES = [
      'application/pdf',
      'application/msword',
      'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
      'application/vnd.ms-excel',
      'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
      'image/jpeg',
      'image/png',
      'image/gif',
      'text/plain'
   ];

   public static function upload(array $data, array $file): array
   {
      $repo = new DocumentRepository();

      // Validate file side effects
      if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK)
         ResponseHelper::error('File upload failed', 400);
      if ($file['size'] > self::MAX_FILE_SIZE)
         ResponseHelper::error('File too large', 400);

      $finfo = new finfo(FILEINFO_MIME_TYPE);
      $mimeType = $finfo->file($file['tmp_name']);
      if (!in_array($mimeType, self::ALLOWED_TYPES, true))
         ResponseHelper::error('Invalid file type', 400);

      Helpers::validateInput($data, ['document_name' => 'required|max:200', 'category_id' => 'required|numeric', 'branch_id' => 'required|numeric']);

      if (!$repo->isValidCategory((int) $data['category_id']))
         ResponseHelper::error('Invalid document category', 400);

      // File I/O logic
      $yearMonth = date('Y/m');
      $uploadPath = self::UPLOAD_DIR . $yearMonth . '/';
      if (!is_dir($uploadPath))
         mkdir($uploadPath, 0755, true);

      $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
      $filename = 'doc_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
      $relativeURL = 'uploads/documents/' . $yearMonth . '/' . $filename;

      if (!move_uploaded_file($file['tmp_name'], $uploadPath . $filename))
         ResponseHelper::serverError('Failed to save file');

      try {
         $documentId = $repo->create([
            'DocumentName'      => trim($data['document_name']),
            'DocumentDescription' => $data['description'] ?? null,
            'CategoryID' => (int) $data['category_id'],
            'FileURL'           => $relativeURL,
            'FileType'          => $mimeType,
            'FileSize'          => $file['size'],
            'RelatedToType'     => $data['entity_type'] ?? null,
            'RelatedToID'       => !empty($data['entity_id']) ? (int)$data['entity_id'] : null,
            'BranchID' => (int) $data['branch_id'],
            'UploadedBy'        => Auth::getCurrentUserId(),
            'UploadedAt'        => date('Y-m-d H:i:s')
         ]);

         return ['status' => 'success', 'document_id' => $documentId, 'file_url' => $relativeURL];
      } catch (Exception $e) {
         if (file_exists($uploadPath . $filename))
            unlink($uploadPath . $filename);
         throw $e;
      }
   }

   public static function update(int $documentId, array $data): array
   {
      $repo = new DocumentRepository();
      $update = [];
      if (!empty($data['document_name']))
         $update['DocumentName'] = trim($data['document_name']);
      if (isset($data['description']))
         $update['DocumentDescription'] = $data['description'];

      if (!empty($update))
         $repo->update($documentId, $update);
      return ['status' => 'success'];
   }

   public static function delete(int $documentId): array
   {
      $repo = new DocumentRepository();
      $doc = $repo->findById($documentId);
      if (!$doc)
         ResponseHelper::error('Document not found', 404);

      $filePath = __DIR__ . '/../public/' . $doc['FileURL'];
      if (file_exists($filePath))
         unlink($filePath);

      $repo->delete($documentId);
      return ['status' => 'success'];
   }

   public static function get(int $documentId): array
   {
      $repo = new DocumentRepository();
      $doc = $repo->findById($documentId);
      if (!$doc)
         ResponseHelper::error('Document not found', 404);
      return $doc;
   }

   public static function getAll(int $page = 1, int $limit = 25, array $filters = []): array
   {
      $repo = new DocumentRepository();
      $offset = ($page - 1) * $limit;
      $result = $repo->findAll($limit, $offset, $filters);

      return [
         'data' => $result['data'],
         'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $result['total'],
            'pages' => (int) ceil($result['total'] / $limit)
         ]
      ];
   }
}
