<?php

/**
 * Document Management System
 *
 * Complete document storage and retrieval with:
 * - File upload with validation
 * - Category management
 * - Entity linking (members, events, expenses, etc.)
 * - Download tracking
 *
 * @package  AliveChMS\Core
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2026-January
 */

declare(strict_types=1);

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

   /**
    * Upload a new document
    *
    * @param array $data Document metadata
    * @param array $file Uploaded file from $_FILES
    * @return array ['status' => 'success', 'document_id' => int]
    */
   public static function upload(array $data, array $file): array
   {
      $orm = new ORM();

      // Validate file upload
      if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
         ResponseHelper::error('File upload failed', 400);
      }

      // Validate file size
      if ($file['size'] > self::MAX_FILE_SIZE) {
         ResponseHelper::error('File too large. Maximum size: 10MB', 400);
      }

      // Validate file type
      $finfo = new finfo(FILEINFO_MIME_TYPE);
      $mimeType = $finfo->file($file['tmp_name']);
      if (!in_array($mimeType, self::ALLOWED_TYPES, true)) {
         ResponseHelper::error('Invalid file type. Allowed: PDF, Word, Excel, Images, Text', 400);
      }

      // Validate required data
      Helpers::validateInput($data, [
         'document_name' => 'required|max:200',
         'category_id'   => 'required|numeric',
         'branch_id'     => 'required|numeric'
      ]);

      $branchId = (int)$data['branch_id'];
      self::validateBranch($branchId);

      // Validate category
      $categoryId = (int)$data['category_id'];
      $category = $orm->getWhere('document_category', ['CategoryID' => $categoryId, 'IsActive' => 1]);
      if (empty($category)) {
         ResponseHelper::error('Invalid document category', 400);
      }

      // Create upload directory if not exists
      $yearMonth = date('Y/m');
      $uploadPath = self::UPLOAD_DIR . $yearMonth . '/';
      if (!is_dir($uploadPath)) {
         mkdir($uploadPath, 0755, true);
      }

      // Generate unique filename
      $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
      $filename = 'doc_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
      $filepath = $uploadPath . $filename;
      $relativeURL = 'uploads/documents/' . $yearMonth . '/' . $filename;

      // Move uploaded file
      if (!move_uploaded_file($file['tmp_name'], $filepath)) {
         ResponseHelper::serverError('Failed to save uploaded file');
      }

      $orm->beginTransaction();
      try {
         $documentId = $orm->insert('document', [
            'DocumentName'      => trim($data['document_name']),
            'DocumentDescription' => $data['description'] ?? null,
            'CategoryID'        => $categoryId,
            'FileURL'           => $relativeURL,
            'FileType'          => $mimeType,
            'FileSize'          => $file['size'],
            'RelatedToType'     => $data['entity_type'] ?? null,
            'RelatedToID'       => !empty($data['entity_id']) ? (int)$data['entity_id'] : null,
            'BranchID'          => $branchId,
            'UploadedBy'        => Auth::getCurrentUserId(),
            'UploadedAt'        => date('Y-m-d H:i:s')
         ])['id'];

         $orm->commit();

         Helpers::logError("Document uploaded: ID $documentId | {$data['document_name']}");
         return ['status' => 'success', 'document_id' => $documentId, 'file_url' => $relativeURL];
      } catch (Exception $e) {
         $orm->rollBack();
         // Clean up uploaded file on error
         if (file_exists($filepath)) {
            unlink($filepath);
         }
         throw $e;
      }
   }

   /**
    * Update document metadata
    *
    * @param int   $documentId Document ID
    * @param array $data       Updated data
    * @return array ['status' => 'success', 'document_id' => int]
    */
   public static function update(int $documentId, array $data): array
   {
      $orm = new ORM();

      $document = $orm->getWhere('document', ['DocumentID' => $documentId]);
      if (empty($document)) {
         ResponseHelper::error('Document not found', 404);
      }

      $update = [];

      if (!empty($data['document_name'])) {
         $update['DocumentName'] = trim($data['document_name']);
      }
      if (isset($data['description'])) {
         $update['DocumentDescription'] = $data['description'];
      }
      if (!empty($data['category_id'])) {
         $categoryId = (int)$data['category_id'];
         $category = $orm->getWhere('document_category', ['CategoryID' => $categoryId, 'IsActive' => 1]);
         if (empty($category)) {
            ResponseHelper::error('Invalid document category', 400);
         }
         $update['CategoryID'] = $categoryId;
      }
      if (isset($data['entity_type'])) {
         $update['RelatedToType'] = $data['entity_type'];
      }
      if (isset($data['entity_id'])) {
         $update['RelatedToID'] = !empty($data['entity_id']) ? (int)$data['entity_id'] : null;
      }

      if (!empty($update)) {
         $orm->update('document', $update, ['DocumentID' => $documentId]);
      }

      return ['status' => 'success', 'document_id' => $documentId];
   }

   /**
    * Delete a document (hard delete)
    *
    * @param int $documentId Document ID
    * @return array ['status' => 'success']
    */
   public static function delete(int $documentId): array
   {
      $orm = new ORM();

      $document = $orm->getWhere('document', ['DocumentID' => $documentId]);
      if (empty($document)) {
         ResponseHelper::error('Document not found', 404);
      }

      // Delete physical file
      $filePath = __DIR__ . '/../public/' . $document[0]['FileURL'];
      if (file_exists($filePath)) {
         unlink($filePath);
      }

      // Delete database record
      $orm->delete('document', ['DocumentID' => $documentId]);

      return ['status' => 'success'];
   }

   /**
    * Get a single document with details
    *
    * @param int $documentId Document ID
    * @return array Document data
    */
   public static function get(int $documentId): array
   {
      $orm = new ORM();

      $result = $orm->selectWithJoin(
         baseTable: 'document d',
         joins: [
            ['table' => 'document_category dc', 'on' => 'd.CategoryID = dc.CategoryID', 'type' => 'LEFT'],
            ['table' => 'branch b', 'on' => 'd.BranchID = b.BranchID', 'type' => 'LEFT'],
            ['table' => 'churchmember m', 'on' => 'd.UploadedBy = m.MbrID', 'type' => 'LEFT']
         ],
         fields: [
            'd.*',
            'dc.CategoryName',
            'b.BranchName',
            'm.MbrFirstName AS UploaderFirstName',
            'm.MbrFamilyName AS UploaderLastName'
         ],
         conditions: ['d.DocumentID' => ':id'],
         params: [':id' => $documentId]
      );

      if (empty($result)) {
         ResponseHelper::error('Document not found', 404);
      }

      return $result[0];
   }

   /**
    * Get all documents with pagination and filters
    *
    * @param int   $page    Page number
    * @param int   $limit   Items per page
    * @param array $filters Optional filters
    * @return array Paginated result
    */
   public static function getAll(int $page = 1, int $limit = 25, array $filters = []): array
   {
      $orm = new ORM();
      $offset = ($page - 1) * $limit;

      $conditions = [];
      $params = [];

      if (!empty($filters['branch_id'])) {
         $conditions['d.BranchID'] = ':branch_id';
         $params[':branch_id'] = (int)$filters['branch_id'];
      }
      if (!empty($filters['category_id'])) {
         $conditions['d.CategoryID'] = ':category_id';
         $params[':category_id'] = (int)$filters['category_id'];
      }
      if (!empty($filters['entity_type'])) {
         $conditions['d.RelatedToType'] = ':entity_type';
         $params[':entity_type'] = $filters['entity_type'];
      }
      if (!empty($filters['entity_id'])) {
         $conditions['d.RelatedToID'] = ':entity_id';
         $params[':entity_id'] = (int)$filters['entity_id'];
      }
      if (!empty($filters['search'])) {
         $conditions['(d.DocumentName LIKE :search OR d.DocumentDescription LIKE :search)'] = '';
         $params[':search'] = '%' . $filters['search'] . '%';
      }

      $documents = $orm->selectWithJoin(
         baseTable: 'document d',
         joins: [
            ['table' => 'document_category dc', 'on' => 'd.CategoryID = dc.CategoryID', 'type' => 'LEFT'],
            ['table' => 'branch b', 'on' => 'd.BranchID = b.BranchID', 'type' => 'LEFT']
         ],
         fields: [
            'd.DocumentID',
            'd.DocumentName',
            'd.DocumentDescription',
            'd.FileURL',
            'd.FileSize',
            'd.FileType',
            'd.UploadedAt',
            'dc.CategoryName',
            'b.BranchName'
         ],
         conditions: $conditions,
         params: $params,
         orderBy: ['d.UploadedAt' => 'DESC'],
         limit: $limit,
         offset: $offset
      );

      $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', array_map(function ($k) {
         return strpos($k, '(') === 0 ? $k : "$k = " . (strpos($k, ':') !== false ? $k : ":$k");
      }, array_keys($conditions))) : '';

      $total = $orm->runQuery(
         "SELECT COUNT(*) AS total FROM document d $whereClause",
         $params
      )[0]['total'];

      return [
         'data' => $documents,
         'pagination' => [
            'page'  => $page,
            'limit' => $limit,
            'total' => (int)$total,
            'pages' => (int)ceil($total / $limit)
         ]
      ];
   }

   /**
    * Download a document
    *
    * @param int $documentId Document ID
    * @return void Sends file to browser
    */
   public static function download(int $documentId): void
   {
      $orm = new ORM();

      $document = $orm->getWhere('document', ['DocumentID' => $documentId]);
      if (empty($document)) {
         ResponseHelper::error('Document not found', 404);
      }

      $doc = $document[0];
      $filePath = __DIR__ . '/../public/' . $doc['FileURL'];

      if (!file_exists($filePath)) {
         ResponseHelper::error('File not found on server', 404);
      }

      // Extract filename from URL
      $filename = basename($doc['FileURL']);

      // Set headers for download
      header('Content-Type: ' . $doc['FileType']);
      header('Content-Disposition: attachment; filename="' . $filename . '"');
      header('Content-Length: ' . filesize($filePath));
      header('Cache-Control: no-cache, must-revalidate');
      header('Pragma: public');

      // Output file
      readfile($filePath);
      exit;
   }

   /**
    * Get documents by category
    *
    * @param int $categoryId Category ID
    * @param int $page       Page number
    * @param int $limit      Items per page
    * @return array Paginated result
    */
   public static function getByCategory(int $categoryId, int $page = 1, int $limit = 25): array
   {
      return self::getAll($page, $limit, ['category_id' => $categoryId]);
   }

   /**
    * Get documents by member
    *
    * @param int $memberId Member ID
    * @param int $page     Page number
    * @param int $limit    Items per page
    * @return array Paginated result
    */
   public static function getByMember(int $memberId, int $page = 1, int $limit = 25): array
   {
      return self::getAll($page, $limit, ['entity_type' => 'Member', 'entity_id' => $memberId]);
   }

   /** Private Helpers */

   private static function validateBranch(int $branchId): void
   {
      $orm = new ORM();
      $branch = $orm->getWhere('branch', ['BranchID' => $branchId]);
      if (empty($branch)) {
         ResponseHelper::error('Invalid branch ID', 400);
      }
      if ($branch[0]['IsActive'] != 1) {
         ResponseHelper::error('Branch is not active', 400);
      }
   }
}
