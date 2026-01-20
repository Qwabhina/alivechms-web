<?php

/**
 * Document API Routes
 *
 * Complete document management system
 *
 * @package  AliveChMS\Routes
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2026-January
 */

declare(strict_types=1);

require_once __DIR__ . '/../core/Document.php';
require_once __DIR__ . '/../core/ResponseHelper.php';

class DocumentRoutes extends BaseRoute
{
   public static function handle(): void
   {
      global $method, $path, $pathParts;

      self::rateLimit(maxAttempts: 60, windowSeconds: 60);

      match (true) {
         // UPLOAD DOCUMENT
         $method === 'POST' && $path === 'document/upload' => (function () {
            self::authenticate();
            self::authorize('documents.upload');

            if (!isset($_FILES['file'])) {
               ResponseHelper::error('No file uploaded', 400);
            }

            $payload = self::getPayload();
            $result = Document::upload($payload, $_FILES['file']);
            ResponseHelper::created($result, 'Document uploaded');
         })(),

         // UPDATE DOCUMENT
         $method === 'PUT' && $pathParts[0] === 'document' && ($pathParts[1] ?? '') === 'update' && isset($pathParts[2]) => (function () use ($pathParts) {
            self::authenticate();
            self::authorize('documents.edit');

            $documentId = self::getIdFromPath($pathParts, 2, 'Document ID');
            $payload = self::getPayload();
            $result = Document::update($documentId, $payload);
            ResponseHelper::success($result, 'Document updated');
         })(),

         // DELETE DOCUMENT
         $method === 'DELETE' && $pathParts[0] === 'document' && ($pathParts[1] ?? '') === 'delete' && isset($pathParts[2]) => (function () use ($pathParts) {
            self::authenticate();
            self::authorize('documents.delete');

            $documentId = self::getIdFromPath($pathParts, 2, 'Document ID');
            $result = Document::delete($documentId);
            ResponseHelper::success($result, 'Document deleted');
         })(),

         // VIEW SINGLE DOCUMENT
         $method === 'GET' && $pathParts[0] === 'document' && ($pathParts[1] ?? '') === 'view' && isset($pathParts[2]) => (function () use ($pathParts) {
            self::authenticate();
            self::authorize('documents.view');

            $documentId = self::getIdFromPath($pathParts, 2, 'Document ID');
            $document = Document::get($documentId);
            ResponseHelper::success($document);
         })(),

         // DOWNLOAD DOCUMENT
         $method === 'GET' && $pathParts[0] === 'document' && ($pathParts[1] ?? '') === 'download' && isset($pathParts[2]) => (function () use ($pathParts) {
            self::authenticate();
            self::authorize('documents.download');

            $documentId = self::getIdFromPath($pathParts, 2, 'Document ID');
            Document::download($documentId);
            // Note: download() method exits after sending file
         })(),

         // LIST ALL DOCUMENTS
         $method === 'GET' && $path === 'document/all' => (function () {
            self::authenticate();
            self::authorize('documents.view');

            [$page, $limit] = self::getPagination(25, 100);

            $filters = [];
            if (!empty($_GET['branch_id'])) {
               $filters['branch_id'] = (int)$_GET['branch_id'];
            }
            if (!empty($_GET['category_id'])) {
               $filters['category_id'] = (int)$_GET['category_id'];
            }
            if (!empty($_GET['entity_type'])) {
               $filters['entity_type'] = $_GET['entity_type'];
            }
            if (!empty($_GET['entity_id'])) {
               $filters['entity_id'] = (int)$_GET['entity_id'];
            }
            if (!empty($_GET['search'])) {
               $filters['search'] = $_GET['search'];
            }

            $result = Document::getAll($page, $limit, $filters);
            ResponseHelper::paginated($result['data'], $result['pagination']['total'], $page, $limit);
         })(),

         // GET DOCUMENTS BY CATEGORY
         $method === 'GET' && $pathParts[0] === 'document' && ($pathParts[1] ?? '') === 'by-category' && isset($pathParts[2]) => (function () use ($pathParts) {
            self::authenticate();
            self::authorize('documents.view');

            $categoryId = self::getIdFromPath($pathParts, 2, 'Category ID');
            [$page, $limit] = self::getPagination(25, 100);

            $result = Document::getByCategory($categoryId, $page, $limit);
            ResponseHelper::paginated($result['data'], $result['pagination']['total'], $page, $limit);
         })(),

         // GET DOCUMENTS BY MEMBER
         $method === 'GET' && $pathParts[0] === 'document' && ($pathParts[1] ?? '') === 'by-member' && isset($pathParts[2]) => (function () use ($pathParts) {
            self::authenticate();
            self::authorize('documents.view');

            $memberId = self::getIdFromPath($pathParts, 2, 'Member ID');
            [$page, $limit] = self::getPagination(25, 100);

            $result = Document::getByMember($memberId, $page, $limit);
            ResponseHelper::paginated($result['data'], $result['pagination']['total'], $page, $limit);
         })(),

         // FALLBACK
         default => ResponseHelper::notFound('Document endpoint not found'),
      };
   }
}

DocumentRoutes::handle();
