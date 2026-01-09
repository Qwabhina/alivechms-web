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
                ResponseHelper::error('Password does not meet requirements', 400, ['password' => $passwordCheck['errors']]);
            }

            // Uniqueness check for username
            if (!empty($orm->getWhere('userauthentication', ['Username' => $data['username']]))) {
                ResponseHelper::error('Username already exists', 400);
            }
        }

        // Email uniqueness check
        if (!empty($orm->getWhere('churchmember', ['MbrEmailAddress' => $data['email_address']]))) {
            ResponseHelper::error('Email address already in use', 400);
        }

        $orm->beginTransaction();
        try {
            $memberData = [
                'MbrFirstName'         => $data['first_name'],
                'MbrFamilyName'        => $data['family_name'],
                'MbrOtherNames'        => $data['other_names'] ?? null,
                'MbrFullName'          => $data['first_name']($data['other_names'] ? ' ' . $data['other_names'] : '') . ' ' . $data['family_name'],
                'MbrGender'            => $data['gender'] ?? 'Other',
                'MbrEmailAddress'      => $data['email_address'],
                'MbrResidentialAddress' => $data['address'] ?? null,
                'MbrDateOfBirth'       => $data['date_of_birth'] ?? null,
                'MbrOccupation'        => $data['occupation'] ?? 'Not Specified',
                'MbrRegistrationDate'  => date('Y-m-d'),
                'MbrHighestEducationLevel' => $data['education_level'] ?? 'Not Specified',
                'MbrMembershipStatus'  => 'Active',
                'MbrProfilePicture'    => $data['profile_picture'] ?? null,
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
                'ChurchRoleID' => $data['member_role'] ?? 6
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
            ResponseHelper::error('Email address already in use by another member', 400);
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
            'MbrHighestEducationLevel' => $data['education_level'] ?? 'Not Specified',
            'BranchID'             => !empty($data['branch_id']) ? (int)$data['branch_id'] : null,
            'FamilyID'             => !empty($data['family_id']) ? (int)$data['family_id'] : null,
        ];

        // Handle profile picture update or removal
        if (array_key_exists('profile_picture', $data)) {
            // Get current member data to delete old picture if needed
            $currentMember = $orm->getWhere('churchmember', ['MbrID' => $mbrId]);
            $oldPicture = $currentMember[0]['MbrProfilePicture'] ?? null;

            if ($data['profile_picture'] === null) {
                // Remove profile picture
                $updateData['MbrProfilePicture'] = null;

                // Delete old file if exists
                if ($oldPicture && file_exists(__DIR__ . '/../public/' . $oldPicture)) {
                    unlink(__DIR__ . '/../public/' . $oldPicture);
                    Helpers::logError("Deleted old profile picture for MbrID $mbrId: $oldPicture");
                }
            } elseif ($data['profile_picture'] !== $oldPicture) {
                // New picture uploaded
                $updateData['MbrProfilePicture'] = $data['profile_picture'];

                // Delete old file if exists
                if ($oldPicture && file_exists(__DIR__ . '/../public/' . $oldPicture)) {
                    unlink(__DIR__ . '/../public/' . $oldPicture);
                    Helpers::logError("Replaced profile picture for MbrID $mbrId");
                }
            }
        }

        $orm->beginTransaction();
        try {
            $orm->update('churchmember', $updateData, ['MbrID' => $mbrId]);

            // Replace phone numbers if provided (handles both array and empty array)
            if (isset($data['phone_numbers'])) {
                $phoneNumbers = $data['phone_numbers'];

                // Handle JSON string if not already decoded
                if (is_string($phoneNumbers)) {
                    $phoneNumbers = json_decode($phoneNumbers, true) ?? [];
                }

                // Ensure it's an array
                if (is_array($phoneNumbers)) {
                    // Delete existing phone numbers first
                    $orm->delete('member_phone', ['MbrID' => $mbrId]);

                    // Insert new phone numbers
                    foreach ($phoneNumbers as $index => $phone) {
                        $phone = is_string($phone) ? trim($phone) : '';
                        if ($phone === '') {
                            continue;
                        }
                        $isPrimary = $index === 0 ? 1 : 0;
                        $orm->insert('member_phone', [
                            'MbrID'       => $mbrId,
                            'PhoneNumber' => $phone,
                            'IsPrimary'   => $isPrimary
                        ]);
                    }
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
            ResponseHelper::error('Member not found or already deleted', 404);
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
            ResponseHelper::error('Member not found', 404);
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
            $whereConditions[] = '(c.MbrFirstName LIKE :search OR c.MbrFamilyName LIKE :search2 OR c.MbrOtherNames LIKE :search3 OR c.MbrEmailAddress LIKE :search4 OR p.PhoneNumber LIKE :search5)';
            $params[':search'] = $searchTerm;
            $params[':search2'] = $searchTerm;
            $params[':search3'] = $searchTerm;
            $params[':search4'] = $searchTerm;
            $params[':search5'] = $searchTerm;
        }

        $whereClause = implode(' AND ', $whereConditions);

        // Build ORDER BY clause with sorting support
        $orderBy = 'c.MbrRegistrationDate DESC'; // Default
        if (!empty($filters['sort_by'])) {
            $sortColumn = $filters['sort_by'];
            $sortDir = strtoupper($filters['sort_dir'] ?? 'DESC');

            // Map frontend column names to database columns
            $columnMap = [
                'MbrFirstName' => 'c.MbrFirstName',
                'MbrFamilyName' => 'c.MbrFamilyName',
                'MbrRegistrationDate' => 'c.MbrRegistrationDate',
                'MbrEmailAddress' => 'c.MbrEmailAddress',
                'MbrMembershipStatus' => 'c.MbrMembershipStatus',
                'name' => 'c.MbrFirstName',
                'email' => 'c.MbrEmailAddress',
                'status' => 'c.MbrMembershipStatus',
                'date' => 'c.MbrRegistrationDate'
            ];

            if (isset($columnMap[$sortColumn])) {
                $orderBy = $columnMap[$sortColumn] . ' ' . ($sortDir === 'ASC' ? 'ASC' : 'DESC');
            }
        }

        // FIXED: Use single query with JOIN to get members and phones together (eliminates N+1 problem)
        $members = $orm->runQuery(
            "SELECT c.*, f.FamilyName,
                    GROUP_CONCAT(DISTINCT p.PhoneNumber ORDER BY p.IsPrimary DESC, p.PhoneNumber SEPARATOR ',') AS PhoneNumbers,
                    MAX(CASE WHEN p.IsPrimary = 1 THEN p.PhoneNumber END) AS PrimaryPhone
             FROM `churchmember` c
             LEFT JOIN `family` f ON c.FamilyID = f.FamilyID
             LEFT JOIN `member_phone` p ON c.MbrID = p.MbrID
             WHERE $whereClause
             GROUP BY c.MbrID
             ORDER BY $orderBy
             LIMIT :limit OFFSET :offset",
            array_merge($params, [
                ':limit' => $limit,
                ':offset' => $offset
            ])
        );

        // Get total count (also optimized to avoid N+1)
        $totalResult = $orm->runQuery(
            "SELECT COUNT(DISTINCT c.MbrID) AS total 
             FROM `churchmember` c
             LEFT JOIN `member_phone` p ON c.MbrID = p.MbrID
             WHERE $whereClause",
            $params
        );

        $total = (int)($totalResult[0]['total'] ?? 0);

        // Process phone numbers for display (no more N+1 queries!)
        foreach ($members as &$member) {
            // Convert comma-separated phone numbers to array
            $phoneNumbers = !empty($member['PhoneNumbers']) ? explode(',', $member['PhoneNumbers']) : [];
            $member['PhoneNumbers'] = $phoneNumbers;

            // Create phones array with structure for backward compatibility
            $member['phones'] = [];
            foreach ($phoneNumbers as $index => $phone) {
                $member['phones'][] = [
                    'PhoneNumber' => $phone,
                    'IsPrimary' => ($index === 0) ? 1 : 0
                ];
            }

            // Ensure PrimaryPhone is set
            if (empty($member['PrimaryPhone']) && !empty($phoneNumbers)) {
                $member['PrimaryPhone'] = $phoneNumbers[0];
            }
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

    /**
     * Get member statistics
     *
     * @return array Statistics data
     */
    public static function getStats(): array
    {
        $orm = new ORM();

        // Get counts by status
        $statusCounts = $orm->runQuery(
            "SELECT 
                MbrMembershipStatus,
                COUNT(*) AS count
             FROM churchmember
             WHERE Deleted = 0
             GROUP BY MbrMembershipStatus"
        );

        $total = 0;
        $active = 0;
        $inactive = 0;
        foreach ($statusCounts as $row) {
            $count = (int)$row['count'];
            $total += $count;
            if ($row['MbrMembershipStatus'] === 'Active') {
                $active = $count;
            } elseif ($row['MbrMembershipStatus'] === 'Inactive') {
                $inactive += $count;
            } else {
            }
        }

        // Get new members this month
        $monthStart = date('Y-m-01');
        $newThisMonth = $orm->runQuery(
            "SELECT COUNT(*) AS count 
             FROM churchmember 
             WHERE Deleted = 0 
             AND MbrRegistrationDate >= :month_start",
            [':month_start' => $monthStart]
        )[0]['count'] ?? 0;

        // Get gender distribution
        $genderCounts = $orm->runQuery(
            "SELECT 
                MbrGender,
                COUNT(*) AS count
             FROM churchmember 
             WHERE Deleted = 0 AND MbrMembershipStatus = 'Active'
             GROUP BY MbrGender"
        );

        $genderDistribution = [];
        foreach ($genderCounts as $row) {
            $genderDistribution[$row['MbrGender'] ?? 'Unknown'] = (int)$row['count'];
        }

        // Get age distribution
        $ageGroups = $orm->runQuery(
            "SELECT 
                CASE 
                    WHEN TIMESTAMPDIFF(YEAR, MbrDateOfBirth, CURDATE()) < 18 THEN 'Under 18'
                    WHEN TIMESTAMPDIFF(YEAR, MbrDateOfBirth, CURDATE()) BETWEEN 18 AND 30 THEN '18-30'
                    WHEN TIMESTAMPDIFF(YEAR, MbrDateOfBirth, CURDATE()) BETWEEN 31 AND 45 THEN '31-45'
                    WHEN TIMESTAMPDIFF(YEAR, MbrDateOfBirth, CURDATE()) BETWEEN 46 AND 60 THEN '46-60'
                    WHEN TIMESTAMPDIFF(YEAR, MbrDateOfBirth, CURDATE()) > 60 THEN 'Over 60'
                    ELSE 'Unknown'
                END AS age_group,
                COUNT(*) AS count
             FROM churchmember 
             WHERE Deleted = 0 AND MbrMembershipStatus = 'Active'
             GROUP BY age_group
             ORDER BY 
                CASE age_group
                    WHEN 'Under 18' THEN 1
                    WHEN '18-30' THEN 2
                    WHEN '31-45' THEN 3
                    WHEN '46-60' THEN 4
                    WHEN 'Over 60' THEN 5
                    ELSE 6
                END"
        );

        $ageDistribution = [];
        foreach ($ageGroups as $row) {
            $ageDistribution[$row['age_group']] = (int)$row['count'];
        }

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $inactive,
            'new_this_month' => (int)$newThisMonth,
            'gender_distribution' => $genderDistribution,
            'age_distribution' => $ageDistribution
        ];
    }
}