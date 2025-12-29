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
                // No auth required for registration
                self::authenticate(false);

                $payload = self::getPayload([
                    'first_name'     => 'required|max:50',
                    'family_name'    => 'required|max:50',
                    'email_address'  => 'required|email',
                    'username'       => 'nullable|max:50',
                    'password'       => 'nullable',
                    'gender'         => 'in:Male,Female,Other|nullable',
                    'date_of_birth'  => 'nullable',
                    'address'        => 'nullable',
                    'phone_numbers'  => 'nullable',
                    'occupation'     => 'nullable|max:100',
                    'marital_status' => 'nullable|max:50',
                    'education_level' => 'nullable|max:100',
                    'other_names'    => 'nullable|max:50',
                    'family_id'      => 'nullable',
                    'family_role'    => 'max:50|nullable',
                    'branch_id'      => 'numeric|nullable'
                ]);

                try {
                    $result = Member::register($payload);
                    self::success($result, 'Member registered', 201);
                } catch (Exception $e) {
                    self::error($e->getMessage(), 400);
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
                    $userAuth = $orm->getWhere('userauthentication', ['MbrID' => $currentUserId]);
                    if (!empty($userAuth) && $userAuth[0]['MbrID'] == $memberId) {
                        $isOwnProfile = true;
                    }
                }

                // If not own profile, require edit_members permission
                if (!$isOwnProfile) {
                    self::authorize('edit_members');
                }

                try {
                    $result = Member::uploadProfilePicture($memberId);
                    self::success($result, 'Profile picture uploaded');
                } catch (Exception $e) {
                    self::error($e->getMessage(), 400);
                }
            })(),

            // VIEW SINGLE MEMBER
            $method === 'GET' && $pathParts[0] === 'member' && ($pathParts[1] ?? '') === 'view' && isset($pathParts[2]) => (function () use ($pathParts) {
                self::authenticate();
                self::authorize('view_members');

                $memberId = self::getIdFromPath($pathParts, 2, 'Member ID');

                $member = Member::get($memberId);
                self::success($member);
            })(),

            // UPDATE MEMBER
            $method === 'PUT' && $pathParts[0] === 'member' && ($pathParts[1] ?? '') === 'update' && isset($pathParts[2]) => (function () use ($pathParts) {
                self::authenticate();
                self::authorize('view_members');

                $memberId = self::getIdFromPath($pathParts, 2, 'Member ID');

                $payload = self::getPayload([
                    'first_name'     => 'max:50|nullable',
                    'family_name'    => 'max:50|nullable',
                    'email_address'  => 'email|nullable',
                    'gender'         => 'in:Male,Female,Other|nullable',
                    'date_of_birth'  => 'nullable',
                    'address'        => 'nullable',
                    'phone_numbers'  => 'nullable',
                    'occupation'     => 'nullable|max:100',
                    'marital_status' => 'nullable|max:50',
                    'education_level' => 'nullable|max:100',
                    'other_names'    => 'nullable|max:50',
                    'family_id'      => 'nullable',
                    'family_role'    => 'max:50|nullable',
                    'branch_id'      => 'numeric|nullable'
                ]);

                $result = Member::update($memberId, $payload);
                self::success($result, 'Member updated');
            })(),

            // SOFT DELETE MEMBER
            $method === 'DELETE' && $pathParts[0] === 'member' && ($pathParts[1] ?? '') === 'delete' && isset($pathParts[2]) => (function () use ($pathParts) {
                self::authenticate();
                self::authorize('delete_members');

                $memberId = self::getIdFromPath($pathParts, 2, 'Member ID');

                $result = Member::delete($memberId);
                self::success($result, 'Member deleted');
            })(),

            // LIST ALL MEMBERS (PAGINATED)
            $method === 'GET' && $path === 'member/all' => (function () {
                self::authenticate();
                self::authorize('view_members');

                [$page, $limit] = self::getPagination(25, 100);

                $filters = self::getFilters(['status', 'family_id', 'date_from', 'date_to', 'search']);

                $result = Member::getAll($page, $limit, $filters);
                self::paginated($result['data'], $result['pagination']['total'], $page, $limit);
            })(),

            // RECENT MEMBERS (LAST 10)
            $method === 'GET' && $path === 'member/recent' => (function () {
                self::authenticate();
                self::authorize('view_members');

                $members = Member::getRecent();
                self::success(['data' => $members]);
            })(),

            // FALLBACK
            default => self::error('Member endpoint not found', 404),
        };
    }
}

// Dispatch
MemberRoutes::handle();