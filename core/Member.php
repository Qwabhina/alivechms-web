<?php

/**
 * Member Management
 *
 * Handles registration, profile updates, soft deletion,
 * retrieval (single + paginated), and related phone/family data.
 *
 * All operations are fully validated, transactional, and audited.
 *
 * @package  AliveChMS\Core
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-November
 */

declare(strict_types=1);

class Member
{
    private const UPLOAD_DIR = __DIR__ . '/../uploads/members/';
    private const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    private const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB

    /**
     * Upload profile picture for a member
     *
     * @param int $mbrId Member ID
     * @return array ['status' => 'success', 'path' => string]
     * @throws Exception On upload failure
     */
    public static function uploadProfilePicture(int $mbrId): array
    {
        $orm = new ORM();

        // Verify member exists
        $member = $orm->getWhere('churchmember', ['MbrID' => $mbrId, 'Deleted' => 0]);
        if (empty($member)) {
            throw new Exception('Member not found');
        }

        // Check if file was uploaded
        if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => 'File exceeds server limit',
                UPLOAD_ERR_FORM_SIZE => 'File exceeds form limit',
                UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            ];
            $error = $_FILES['profile_picture']['error'] ?? UPLOAD_ERR_NO_FILE;
            throw new Exception($errorMessages[$error] ?? 'Unknown upload error');
        }

        $file = $_FILES['profile_picture'];

        // Validate file type
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        if (!in_array($mimeType, self::ALLOWED_TYPES)) {
            throw new Exception('Invalid file type. Allowed: JPG, PNG, GIF, WebP');
        }

        // Validate file size
        if ($file['size'] > self::MAX_FILE_SIZE) {
            throw new Exception('File too large. Maximum size: 5MB');
        }

        // Create upload directory if not exists
        if (!is_dir(self::UPLOAD_DIR)) {
            mkdir(self::UPLOAD_DIR, 0755, true);
        }

        // Generate unique filename
        $extension = match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            default => 'jpg'
        };
        $filename = 'member_' . $mbrId . '_' . time() . '.' . $extension;
        $filepath = self::UPLOAD_DIR . $filename;
        $relativePath = 'uploads/members/' . $filename;

        // Delete old profile picture if exists
        $oldPicture = $member[0]['MbrProfilePicture'] ?? null;
        if ($oldPicture && file_exists(__DIR__ . '/../' . $oldPicture)) {
            unlink(__DIR__ . '/../' . $oldPicture);
        }

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            throw new Exception('Failed to save uploaded file');
        }

        // Update database
        $orm->update('churchmember', ['MbrProfilePicture' => $relativePath], ['MbrID' => $mbrId]);

        Helpers::logError("Profile picture uploaded for MbrID $mbrId: $relativePath");

        return [
            'status' => 'success',
            'path' => $relativePath,
            'url' => '/' . $relativePath
        ];
    }

    /**
     * Register a new church member with authentication credentials
     *
     * @param array $data Registration payload
     * @return array ['status' => 'success', 'mbr_id' => int]
     * @throws Exception On validation or database failure
     */
    public static function register(array $data): array
    {
        $orm = new ORM();

        Helpers::validateInput($data, [
            'first_name'     => 'required|max:50',
            'family_name'    => 'required|max:50',
            'email_address'  => 'required|email',
            'gender'         => 'in:Male,Female,Other|nullable',
            'branch_id'      => 'numeric|nullable',
        ]);

        // Validate username/password only if provided
        $createAuth = !empty($data['username']) && !empty($data['password']);

        if ($createAuth) {
            $passwordCheck = Helpers::validatePasswordStrength($data['password']);
            if (!$passwordCheck['valid']) {
                Helpers::sendError('Password does not meet requirements', 400, ['password' => $passwordCheck['errors']]);
            }

            // Uniqueness check for username
            if (!empty($orm->getWhere('userauthentication', ['Username' => $data['username']]))) {
                Helpers::sendFeedback('Username already exists', 400);
            }
        }

        // Email uniqueness check
        if (!empty($orm->getWhere('churchmember', ['MbrEmailAddress' => $data['email_address']]))) {
            Helpers::sendFeedback('Email address already in use', 400);
        }

        $orm->beginTransaction();
        try {
            $memberData = [
                'MbrFirstName'         => $data['first_name'],
                'MbrFamilyName'        => $data['family_name'],
                'MbrOtherNames'        => $data['other_names'] ?? null,
                'MbrGender'            => $data['gender'] ?? 'Other',
                'MbrEmailAddress'      => $data['email_address'],
                'MbrResidentialAddress' => $data['address'] ?? null,
                'MbrDateOfBirth'       => $data['date_of_birth'] ?? null,
                'MbrOccupation'        => $data['occupation'] ?? 'Not Specified',
                'MbrRegistrationDate'  => date('Y-m-d'),
                'MbrMembershipStatus'  => 'Active',
                'BranchID'             => (int)($data['branch_id'] ?? 1),
                'FamilyID'             => !empty($data['family_id']) ? (int)$data['family_id'] : null,
                'Deleted'              => 0
            ];

            $mbrId = $orm->insert('churchmember', $memberData)['id'];

            // Handle phone numbers
            if (!empty($data['phone_numbers']) && is_array($data['phone_numbers'])) {
                foreach ($data['phone_numbers'] as $index => $phone) {
                    $phone = trim($phone);
                    if ($phone === '') {
                        continue;
                    }
                    $isPrimary = $index === 0 ? 1 : 0;
                    $orm->insert('member_phone', [
                        'MbrID'      => $mbrId,
                        'PhoneNumber' => $phone,
                        'IsPrimary'  => $isPrimary
                    ]);
                }
            }

            // Create authentication record only if username/password provided
            if ($createAuth) {
                $orm->insert('userauthentication', [
                    'MbrID'        => $mbrId,
                    'Username'     => $data['username'],
                    'PasswordHash' => password_hash($data['password'], PASSWORD_DEFAULT),
                    'CreatedAt'    => date('Y-m-d H:i:s')
                ]);
            }

            // Assign default "Member" role (RoleID 6)
            $orm->insert('memberrole', [
                'MbrID'        => $mbrId,
                'ChurchRoleID' => 6
            ]);

            $orm->commit();

            Helpers::logError("New member registered: MbrID $mbrId" . ($createAuth ? " ({$data['username']})" : ""));
            return ['status' => 'success', 'mbr_id' => $mbrId];
        } catch (Exception $e) {
            $orm->rollBack();
            Helpers::logError("Member registration failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update an existing member profile
     *
     * @param int   $mbrId Member ID
     * @param array $data  Updated data
     * @return array ['status' => 'success', 'mbr_id' => int]
     * @throws Exception On validation or database failure
     */
    public static function update(int $mbrId, array $data): array
    {
        $orm = new ORM();

        Helpers::validateInput($data, [
            'first_name'     => 'required|max:50',
            'family_name'    => 'required|max:50',
            'email_address'  => 'required|email',
            'gender'         => 'in:Male,Female,Other|nullable',
            'branch_id'      => 'numeric|nullable',
        ]);

        // Prevent email conflict
        $conflict = $orm->runQuery(
            "SELECT MbrID FROM churchmember WHERE MbrEmailAddress = :email AND MbrID != :id AND Deleted = 0",
            [':email' => $data['email_address'], ':id' => $mbrId]
        );
        if (!empty($conflict)) {
            Helpers::sendFeedback('Email address already in use by another member', 400);
        }

        $updateData = [
            'MbrFirstName'         => $data['first_name'],
            'MbrFamilyName'        => $data['family_name'],
            'MbrOtherNames'        => $data['other_names'] ?? null,
            'MbrGender'            => $data['gender'] ?? 'Other',
            'MbrEmailAddress'      => $data['email_address'],
            'MbrResidentialAddress' => $data['address'] ?? null,
            'MbrDateOfBirth'       => $data['date_of_birth'] ?? null,
            'MbrOccupation'        => $data['occupation'] ?? 'Not Specified',
            'BranchID'             => !empty($data['branch_id']) ? (int)$data['branch_id'] : null,
            'FamilyID'             => !empty($data['family_id']) ? (int)$data['family_id'] : null,
        ];

        $orm->beginTransaction();
        try {
            $orm->update('churchmember', $updateData, ['MbrID' => $mbrId]);

            // Replace phone numbers if provided
            if (isset($data['phone_numbers']) && is_array($data['phone_numbers'])) {
                $orm->delete('member_phone', ['MbrID' => $mbrId]);
                foreach ($data['phone_numbers'] as $index => $phone) {
                    $phone = trim($phone);
                    if ($phone === '') {
                        continue;
                    }
                    $isPrimary = $index === 0 ? 1 : 0;
                    $orm->insert('member_phone', [
                        'MbrID'      => $mbrId,
                        'PhoneNumber' => $phone,
                        'PhoneType'  => 'Mobile',
                        'IsPrimary'  => $isPrimary
                    ]);
                }
            }

            $orm->commit();
            return ['status' => 'success', 'mbr_id' => $mbrId];
        } catch (Exception $e) {
            $orm->rollBack();
            Helpers::logError("Member update failed for MbrID $mbrId: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Soft delete a member
     *
     * @param int $mbrId Member ID
     * @return array ['status' => 'success']
     */
    public static function delete(int $mbrId): array
    {
        $orm = new ORM();

        $affected = $orm->softDelete('churchmember', $mbrId, 'MbrID');
        if ($affected === 0) {
            Helpers::sendFeedback('Member not found or already deleted', 404);
        }

        Helpers::logError("Member soft-deleted: MbrID $mbrId");
        return ['status' => 'success'];
    }

    /**
     * Retrieve a single member with phones and family
     *
     * @param int $mbrId Member ID
     * @return array Member data
     */
    public static function get(int $mbrId): array
    {
        $orm = new ORM();

        // Get member basic info
        $member = $orm->selectWithJoin(
            baseTable: 'churchmember c',
            joins: [
                ['table' => 'family f', 'on' => 'c.FamilyID = f.FamilyID', 'type' => 'LEFT']
            ],
            fields: ['c.*', 'f.FamilyName'],
            conditions: ['c.MbrID' => ':id', 'c.Deleted' => 0],
            params: [':id' => $mbrId]
        );

        if (empty($member)) {
            Helpers::sendFeedback('Member not found', 404);
        }

        $memberData = $member[0];

        // Get phone numbers separately
        $phones = $orm->getWhere('member_phone', ['MbrID' => $mbrId]);
        $memberData['phones'] = $phones;

        // Get phone numbers as array for backward compatibility
        $phoneNumbers = array_column($phones, 'PhoneNumber');
        $memberData['PhoneNumbers'] = $phoneNumbers;
        $memberData['PrimaryPhone'] = !empty($phoneNumbers) ? $phoneNumbers[0] : null;

        return $memberData;
    }

    /**
     * Get recent active members (last 10)
     *
     * @return array Recent members
     */
    public static function getRecent(): array
    {
        $qb = new QueryBuilder();
        return $qb->table('churchmember c')
            ->select([
                'c.MbrID',
                'c.MbrFirstName',
                'c.MbrFamilyName',
                'c.MbrEmailAddress',
                'c.MbrRegistrationDate',
                "GROUP_CONCAT(DISTINCT p.PhoneNumber) AS PhoneNumbers",
                'u.Username',
                'u.LastLoginAt'
            ])
            ->leftJoin('member_phone p', 'c.MbrID', '=', 'p.MbrID')
            ->leftJoin('userauthentication u', 'c.MbrID', '=', 'u.MbrID')
            ->where('c.MbrMembershipStatus', 'Active')
            ->where('c.Deleted', 0)
            ->groupBy('c.MbrID')
            ->orderBy('c.MbrRegistrationDate', 'DESC')
            ->limit(10)
            ->cache(600, ['members_list', 'recent_members']) // Cache for 10 mins
            ->get();
    }

    /**
     * Retrieve paginated list of active members
     *
     * @param int $page  Page number (1-based)
     * @param int $limit Items per page
     * @return array Paginated result
     */
    public static function getAll(int $page = 1, int $limit = 10, array $filters = []): array
    {
        $orm = new ORM();
        $offset = ($page - 1) * $limit;

        // Build WHERE conditions
        $whereConditions = ['c.Deleted = 0'];
        $params = [];

        // Apply status filter
        if (!empty($filters['status'])) {
            $whereConditions[] = 'c.MbrMembershipStatus = :status';
            $params[':status'] = $filters['status'];
        } else {
            $whereConditions[] = 'c.MbrMembershipStatus = :status';
            $params[':status'] = 'Active';
        }

        // Apply family_id filter
        if (!empty($filters['family_id'])) {
            $whereConditions[] = 'c.FamilyID = :family_id';
            $params[':family_id'] = (int)$filters['family_id'];
        }

        // Apply date filters
        if (!empty($filters['date_from'])) {
            $whereConditions[] = 'c.MbrRegistrationDate >= :date_from';
            $params[':date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $whereConditions[] = 'c.MbrRegistrationDate <= :date_to';
            $params[':date_to'] = $filters['date_to'];
        }

        // Apply search filter (searches name, email, phone)
        if (!empty($filters['search'])) {
            $searchTerm = '%' . $filters['search'] . '%';
            $whereConditions[] = '(c.MbrFirstName LIKE :search OR c.MbrFamilyName LIKE :search2 OR c.MbrOtherNames LIKE :search3 OR c.MbrEmailAddress LIKE :search4)';
            $params[':search'] = $searchTerm;
            $params[':search2'] = $searchTerm;
            $params[':search3'] = $searchTerm;
            $params[':search4'] = $searchTerm;
        }

        $whereClause = implode(' AND ', $whereConditions);

        // Get members with pagination
        $members = $orm->runQuery(
            "SELECT c.*, f.FamilyName
         FROM `churchmember` c
         LEFT JOIN `family` f ON c.FamilyID = f.FamilyID
         WHERE $whereClause
         ORDER BY c.MbrRegistrationDate DESC
         LIMIT :limit OFFSET :offset",
            array_merge($params, [
                ':limit' => $limit,
                ':offset' => $offset
            ])
        );

        // Get total count
        $totalResult = $orm->runQuery(
            "SELECT COUNT(DISTINCT c.MbrID) AS total 
         FROM `churchmember` c
         WHERE $whereClause",
            $params
        );

        $total = (int)($totalResult[0]['total'] ?? 0);

        // Process phone numbers for display
        foreach ($members as &$member) {
            // Get primary phone
            $phones = $orm->getWhere('member_phone', ['MbrID' => $member['MbrID']]);
            $member['phones'] = $phones;
            $phoneNumbers = array_column($phones, 'PhoneNumber');
            $member['PhoneNumbers'] = $phoneNumbers;
            $member['PrimaryPhone'] = !empty($phoneNumbers) ? $phoneNumbers[0] : null;
        }

        return [
            'data' => $members,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => (int)ceil($total / $limit)
            ]
        ];
    }
}