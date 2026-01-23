<?php

/**
 * Member API Routes – v1
 *
 * Comprehensive member management:
 * - Public registration
 * - Authenticated CRUD operations
 * - Paginated listing + recent members
 * - Full permission enforcement
 *
 * @package  AliveChMS\Routes
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-November
 */

declare(strict_types=1);

require_once __DIR__ . '/../core/Member.php';
require_once __DIR__ . '/../core/ResponseHelper.php';

class MemberRoutes extends BaseRoute
{
    public static function handle(): void
    {
        // Get route variables from global scope
        global $method, $path, $pathParts;

        self::rateLimit(maxAttempts: 50, windowSeconds: 60);

        match (true) {
            // PUBLIC: CREATE/REGISTER
            $method === 'POST' && $path === 'member/create' => (function () {
                // No auth required for registration, but rate limit to prevent spam
                self::authenticate(false);

                // Strict rate limit: 5 registrations per 5 minutes per IP
                self::rateLimit(maxAttempts: 5, windowSeconds: 300);

                // Check if this is multipart/form-data (file upload)
                $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
                $isMultipart = strpos($contentType, 'multipart/form-data') !== false;

                if ($isMultipart) {
                    // Handle FormData submission with file upload
                    $payload = $_POST;

                    // Parse phone_numbers if it's a JSON string
                    if (isset($payload['phone_numbers']) && is_string($payload['phone_numbers'])) {
                        // Decode HTML entities first (FormData can encode quotes as &quot;)
                        $phoneJson = html_entity_decode($payload['phone_numbers'], ENT_QUOTES, 'UTF-8');
                        $decoded = json_decode($phoneJson, true);
                        $payload['phone_numbers'] = is_array($decoded) ? $decoded : [];
                    }

                    // Handle file upload if present
                    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                        $uploadDir = __DIR__ . '/../public/uploads/members/';
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0755, true);
                        }

                        // Validate MIME type first (more secure)
                        $finfo = new finfo(FILEINFO_MIME_TYPE);
                        $mimeType = $finfo->file($_FILES['profile_picture']['tmp_name']);
                        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif'];

                        if (!in_array($mimeType, $allowedMimes)) {
                            ResponseHelper::error('Invalid file type. Only JPG, PNG, and GIF images are allowed.', 400);
                        }

                        // Also validate file extension
                        $fileExtension = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
                        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];

                        if (!in_array($fileExtension, $allowedExtensions)) {
                            ResponseHelper::error('Invalid file extension. Only .jpg, .png, and .gif are allowed.', 400);
                        }

                        // Validate file size (5MB max)
                        if ($_FILES['profile_picture']['size'] > 5 * 1024 * 1024) {
                            ResponseHelper::error('File size must not exceed 5MB.', 400);
                        }

                        $fileName = uniqid('member_') . '.' . $fileExtension;
                        $filePath = $uploadDir . $fileName;

                        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $filePath)) {
                            // Store path relative to public folder (without 'public/' prefix)
                            $payload['profile_picture'] = 'uploads/members/' . $fileName;
                            Helpers::logError("Member create - Profile picture uploaded: " . $payload['profile_picture']);
                        }
                    }
                } else {
                    // Handle JSON submission
                    $payload = self::getPayload([
                        'first_name'     => 'required|max:100',
                        'family_name'    => 'required|max:100',
                        'email_address'  => 'required|email',
                        'username'       => 'nullable|max:50',
                        'password'       => 'nullable',
                        'gender'         => 'in:Male,Female,Other|nullable',
                        'date_of_birth'  => 'nullable',
                        'address'        => 'nullable',
                        'phone_numbers'  => 'nullable',
                        'occupation'     => 'nullable|max:150',
                        'marital_status_id' => 'numeric|nullable',
                        'education_level_id' => 'numeric|nullable',
                        'membership_status_id' => 'numeric|nullable',
                        'other_names'    => 'nullable|max:150',
                        'family_id'      => 'nullable',
                        'branch_id'      => 'numeric|nullable'
                    ]);
                }

                // Validate required fields for both multipart and JSON
                if (empty($payload['first_name'])) {
                    ResponseHelper::error('First name is required', 400);
                }
                if (empty($payload['family_name'])) {
                    ResponseHelper::error('Family name is required', 400);
                }
                if (empty($payload['email_address'])) {
                    ResponseHelper::error('Email address is required', 400);
                }

                try {
                    $result = Member::register($payload);
                    ResponseHelper::created($result, 'Member registered');
                } catch (Exception $e) {
                    ResponseHelper::error($e->getMessage(), 400);
                }
            })(),

            // UPLOAD PROFILE PICTURE
            $method === 'POST' && $pathParts[0] === 'member' && ($pathParts[1] ?? '') === 'upload-photo' && isset($pathParts[2]) => (function () use ($pathParts) {
                self::authenticate();

                $memberId = self::getIdFromPath($pathParts, 2, 'Member ID');

                // Allow users to upload their own photo OR require edit_members permission for others
                $currentUserId = self::getCurrentUserId();
                $isOwnProfile = false;

                // Check if this is the user's own profile
                if ($currentUserId) {
                    $orm = new ORM();
                    $userAuth = $orm->getWhere('user_authentication', ['MbrID' => $currentUserId]);
                    if (!empty($userAuth) && $userAuth[0]['MbrID'] == $memberId) {
                        $isOwnProfile = true;
                    }
                }

                // If not own profile, require edit_members permission
                if (!$isOwnProfile) {
                    self::authorize('members.edit');
                }

                try {
                    $result = Member::uploadProfilePicture($memberId);
                    ResponseHelper::success($result, 'Profile picture uploaded');
                } catch (Exception $e) {
                    ResponseHelper::error($e->getMessage(), 400);
                }
            })(),

            // VIEW SINGLE MEMBER
            $method === 'GET' && $pathParts[0] === 'member' && ($pathParts[1] ?? '') === 'view' && isset($pathParts[2]) => (function () use ($pathParts) {
                self::authenticate();
                self::authorize('members.view');

                // Cache for 5 minutes
                self::setCacheHeaders(300, false, true);

                $memberId = self::getIdFromPath($pathParts, 2, 'Member ID');

                $member = Member::get($memberId);
                ResponseHelper::success($member);
            })(),

            // UPDATE MEMBER
            $method === 'PUT' && $pathParts[0] === 'member' && ($pathParts[1] ?? '') === 'update' && isset($pathParts[2]) => (function () use ($pathParts) {
                self::authenticate();
                self::authorize('members.edit');

                $memberId = self::getIdFromPath($pathParts, 2, 'Member ID');

                $payload = self::getPayload([
                    'first_name'     => 'max:100|nullable',
                    'family_name'    => 'max:100|nullable',
                    'email_address'  => 'email|nullable',
                    'gender'         => 'in:Male,Female,Other|nullable',
                    'date_of_birth'  => 'nullable',
                    'address'        => 'nullable',
                    'phone_numbers'  => 'nullable',
                    'occupation'     => 'nullable|max:150',
                    'marital_status_id' => 'numeric|nullable',
                    'education_level_id' => 'numeric|nullable',
                    'membership_status_id' => 'numeric|nullable',
                    'other_names'    => 'nullable|max:150',
                    'family_id'      => 'nullable',
                    'branch_id'      => 'numeric|nullable'
                ]);

                $result = Member::update($memberId, $payload);
                ResponseHelper::success($result, 'Member updated');
            })(),

            // UPDATE MEMBER (POST with file upload support)
            $method === 'POST' && $pathParts[0] === 'member' && ($pathParts[1] ?? '') === 'update' && isset($pathParts[2]) => (function () use ($pathParts) {
                self::authenticate();
                self::authorize('members.edit');

                $memberId = self::getIdFromPath($pathParts, 2, 'Member ID');

                // Check if this is multipart/form-data (file upload)
                $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
                $isMultipart = strpos($contentType, 'multipart/form-data') !== false;

                if ($isMultipart) {
                    // Handle FormData submission with file upload
                    $payload = $_POST;

                    // Parse phone_numbers if it's a JSON string
                    if (isset($payload['phone_numbers']) && is_string($payload['phone_numbers'])) {
                        // Decode HTML entities first (FormData can encode quotes as &quot;)
                        $phoneJson = html_entity_decode($payload['phone_numbers'], ENT_QUOTES, 'UTF-8');
                        $decoded = json_decode($phoneJson, true);
                        $payload['phone_numbers'] = is_array($decoded) ? $decoded : [];
                    }

                    // Check if profile picture should be removed (explicit removal flag)
                    if (isset($payload['remove_profile_picture']) && $payload['remove_profile_picture'] === 'true') {
                        $payload['profile_picture'] = null; // Will clear the profile picture
                    }
                    // Handle file upload if present
                    elseif (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                        $uploadDir = __DIR__ . '/../public/uploads/members/';
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0755, true);
                        }

                        // Validate MIME type first (more secure)
                        $finfo = new finfo(FILEINFO_MIME_TYPE);
                        $mimeType = $finfo->file($_FILES['profile_picture']['tmp_name']);
                        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif'];

                        if (!in_array($mimeType, $allowedMimes)) {
                            ResponseHelper::error('Invalid file type. Only JPG, PNG, and GIF images are allowed.', 400);
                        }

                        // Also validate file extension
                        $fileExtension = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
                        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];

                        if (!in_array($fileExtension, $allowedExtensions)) {
                            ResponseHelper::error('Invalid file extension. Only .jpg, .png, and .gif are allowed.', 400);
                        }

                        // Validate file size (5MB max)
                        if ($_FILES['profile_picture']['size'] > 5 * 1024 * 1024) {
                            ResponseHelper::error('File size must not exceed 5MB.', 400);
                        }

                        $fileName = uniqid('member_') . '.' . $fileExtension;
                        $filePath = $uploadDir . $fileName;

                        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $filePath)) {
                            $payload['profile_picture'] = 'uploads/members/' . $fileName;
                            Helpers::logError("Member update - Profile picture uploaded: " . $payload['profile_picture']);
                        }
                    }
                } else {
                    // Handle JSON submission
                    $payload = self::getPayload([
                        'first_name'     => 'max:100|nullable',
                        'family_name'    => 'max:100|nullable',
                        'email_address'  => 'email|nullable',
                        'gender'         => 'in:Male,Female,Other|nullable',
                        'date_of_birth'  => 'nullable',
                        'address'        => 'nullable',
                        'phone_numbers'  => 'nullable',
                        'occupation'     => 'nullable|max:150',
                        'marital_status_id' => 'numeric|nullable',
                        'education_level_id' => 'numeric|nullable',
                        'membership_status_id' => 'numeric|nullable',
                        'other_names'    => 'nullable|max:150',
                        'family_id'      => 'nullable',
                        'branch_id'      => 'numeric|nullable'
                    ]);
                }

                $result = Member::update($memberId, $payload);
                ResponseHelper::success($result, 'Member updated');
            })(),

            // SOFT DELETE MEMBER
            $method === 'DELETE' && $pathParts[0] === 'member' && ($pathParts[1] ?? '') === 'delete' && isset($pathParts[2]) => (function () use ($pathParts) {
                self::authenticate();
                self::authorize('members.delete');

                $memberId = self::getIdFromPath($pathParts, 2, 'Member ID');

                $result = Member::delete($memberId);
                ResponseHelper::success($result, 'Member deleted');
            })(),

            // LIST ALL MEMBERS (PAGINATED)
            $method === 'GET' && $path === 'member/all' => (function () {
                self::authenticate();
                self::authorize('members.view');

                [$page, $limit] = self::getPagination(25, 100);

                $filters = self::getFilters(['status', 'family_id', 'date_from', 'date_to', 'search']);

                // Get sorting parameters with allowed columns
                [$sortBy, $sortDir] = self::getSorting(
                    'MbrRegistrationDate',
                    'DESC',
                    ['MbrFirstName', 'MbrFamilyName', 'MbrRegistrationDate', 'MbrEmailAddress', 'MembershipStatusName']
                );
                $filters['sort_by'] = $sortBy;
                $filters['sort_dir'] = $sortDir;

                $result = Member::getAll($page, $limit, $filters);

                // Validate page number
                $totalPages = ceil($result['pagination']['total'] / $limit);
                if ($page > $totalPages && $totalPages > 0) {
                    ResponseHelper::error("Page $page exceeds total pages ($totalPages)", 400);
                }

                ResponseHelper::paginated($result['data'], $result['pagination']['total'], $page, $limit);
            })(),

            // RECENT MEMBERS (LAST 10)
            $method === 'GET' && $path === 'member/recent' => (function () {
                self::authenticate();
                self::authorize('members.view');

                $members = Member::getRecent();
                ResponseHelper::success(['data' => $members]);
            })(),

            // MEMBER STATISTICS
            $method === 'GET' && $path === 'member/stats' => (function () {
                self::authenticate();
                self::authorize('members.view');

                $stats = Member::getStats();
                ResponseHelper::success($stats);
            })(),

            // GET MEMBER BY UNIQUE ID (NEW)
            $method === 'GET' && $pathParts[0] === 'member' && ($pathParts[1] ?? '') === 'by-unique-id' && isset($pathParts[2]) => (function () use ($pathParts) {
                self::authenticate();
                self::authorize('members.view');

                $uniqueId = $pathParts[2];

                try {
                    $orm = new ORM();
                    $member = $orm->getWhere('churchmember', [
                        'MbrUniqueID' => $uniqueId,
                        'Deleted' => 0
                    ]);

                    if (empty($member)) {
                        ResponseHelper::error('Member not found', 404);
                    }

                    $memberData = Member::get((int)$member[0]['MbrID']);
                    ResponseHelper::success($memberData);
                } catch (Exception $e) {
                    ResponseHelper::error($e->getMessage(), 400);
                }
            })(),

            // GET LOOKUP DATA FOR MEMBER FORMS (NEW)
            $method === 'GET' && $path === 'member/lookup-data' => (function () {
                self::authenticate();
                self::authorize('members.view');

                try {
                    $orm = new ORM();

                    $result = [
                        'marital_statuses' => $orm->runQuery(
                            "SELECT StatusID as id, StatusName as name, DisplayOrder 
                             FROM marital_status 
                             WHERE IsActive = 1 
                             ORDER BY DisplayOrder"
                        ),
                        'education_levels' => $orm->runQuery(
                            "SELECT LevelID as id, LevelName as name, DisplayOrder 
                             FROM education_level 
                             WHERE IsActive = 1 
                             ORDER BY DisplayOrder"
                        ),
                        'membership_statuses' => $orm->runQuery(
                            "SELECT StatusID as id, StatusName as name, DisplayOrder 
                             FROM membership_status 
                             WHERE IsActive = 1 
                             ORDER BY DisplayOrder"
                        ),
                        'phone_types' => $orm->runQuery(
                            "SELECT TypeID as id, TypeName as name, DisplayOrder 
                             FROM phone_type 
                             ORDER BY DisplayOrder"
                        ),
                        'branches' => $orm->runQuery(
                            "SELECT BranchID as id, BranchName as name, BranchCode as code 
                             FROM branch 
                             WHERE IsActive = 1 
                             ORDER BY BranchName"
                        )
                    ];

                    ResponseHelper::success($result, 'Lookup data retrieved');
                } catch (Exception $e) {
                    ResponseHelper::error($e->getMessage(), 500);
                }
            })(),

            // FALLBACK
            default => ResponseHelper::notFound('Member endpoint not found'),
        };
    }
}

// Dispatch
MemberRoutes::handle();