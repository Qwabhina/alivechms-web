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

namespace AliveChMS\Core\People;

use AliveChMS\Core\People\MemberRepository;
use AliveChMS\Core\People\MemberStats;
use AliveChMS\Core\System\Helpers;
use AliveChMS\Core\System\ResponseHelper;
use AliveChMS\Core\Identity\Auth;
use AliveChMS\Core\System\ORM;
use Exception;
use finfo;

class Member
{
    private const UPLOAD_DIR = __DIR__ . '/../../uploads/members/';
    private const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    private const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB

    /**
     * Validate Ghana phone number format
     */
    private static function isValidGhanaPhone(string $phone): bool
    {
        return preg_match('/^(\+?233|0)[2-5][0-9]{8}$/', $phone) === 1;
    }

    /**
     * Upload profile picture for a member
     */
    public static function uploadProfilePicture(int $mbrId): array
    {
        $repo = new MemberRepository();

        // Verify member exists
        $member = $repo->findById($mbrId);
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
        $oldPicture = $member['MbrProfilePicture'] ?? null;
        if ($oldPicture && file_exists(__DIR__ . '/../../' . $oldPicture)) {
            unlink(__DIR__ . '/../../' . $oldPicture);
        }

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            throw new Exception('Failed to save uploaded file');
        }

        // Update database
        $repo->update($mbrId, ['MbrProfilePicture' => $relativePath]);

        Helpers::logError("Profile picture uploaded for MbrID $mbrId: $relativePath");

        return [
            'status' => 'success',
            'path' => $relativePath,
            'url' => '/' . $relativePath
        ];
    }

    /**
     * Register a new church member with authentication credentials
     */
    public static function register(array $data): array
    {
        $repo = new MemberRepository();
        $orm = new ORM(); // Still need ORM for Auth creation until AuthRepository exists

        Helpers::validateInput($data, [
            'first_name'     => 'required|max:100',
            'family_name'    => 'required|max:100',
            'email_address'  => 'required|email',
            'gender'         => 'in:Male,Female,Other|nullable',
            'branch_id'      => 'numeric|nullable',
            'marital_status_id' => 'numeric|nullable',
            'education_level_id' => 'numeric|nullable',
        ]);

        // Validate username/password only if provided
        $createAuth = !empty($data['username']) && !empty($data['password']);

        if ($createAuth) {
            $passwordCheck = Helpers::validatePasswordStrength($data['password']);
            if (!$passwordCheck['valid']) {
                ResponseHelper::error('Password does not meet requirements', 400, ['password' => $passwordCheck['errors']]);
            }

            // Uniqueness check for username
            if (!empty($orm->getWhere('user_authentication', ['Username' => $data['username']]))) {
                ResponseHelper::error('Username already exists', 400);
            }
        }

        // Email uniqueness check
        if ($repo->emailExists($data['email_address'])) {
            ResponseHelper::error('Email address already in use', 400);
        }

        // Validate phone numbers if provided
        if (!empty($data['phone_numbers']) && is_array($data['phone_numbers'])) {
            foreach ($data['phone_numbers'] as $phoneData) {
                if (is_string($phoneData)) {
                    $phone = trim($phoneData);
                } else {
                    $phone = trim($phoneData['number'] ?? $phoneData['PhoneNumber'] ?? '');
                }

                if ($phone !== '' && !self::isValidGhanaPhone($phone)) {
                    throw new Exception('Invalid Ghana phone number format: ' . $phone);
                }
            }
        }

        $repo->beginTransaction();
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
                'MbrMaritalStatusID'   => !empty($data['marital_status_id']) ? (int)$data['marital_status_id'] : null,
                'MbrEducationLevelID'  => !empty($data['education_level_id']) ? (int)$data['education_level_id'] : null,
                'MbrMembershipStatusID' => !empty($data['membership_status_id']) ? (int)$data['membership_status_id'] : 1, // Default to Active
                'MbrProfilePicture'    => $data['profile_picture'] ?? null,
                'BranchID'             => (int)($data['branch_id'] ?? 1),
                'FamilyID'             => !empty($data['family_id']) ? (int)$data['family_id'] : null,
                'Deleted'              => 0
            ];

            if (!empty($data['unique_id'])) {
                $memberData['MbrUniqueID'] = $data['unique_id'];
            }

            $mbrId = $repo->create($memberData);

            // Handle phone numbers
            if (!empty($data['phone_numbers']) && is_array($data['phone_numbers'])) {
                foreach ($data['phone_numbers'] as $index => $phoneData) {
                    if (is_string($phoneData)) {
                        $phone = trim($phoneData);
                        $phoneTypeId = 1;
                    } else {
                        $phone = trim($phoneData['number'] ?? $phoneData['PhoneNumber'] ?? '');
                        $phoneTypeId = (int)($phoneData['type_id'] ?? $phoneData['PhoneTypeID'] ?? 1);
                    }

                    if ($phone === '')
                        continue;

                    $isPrimary = $index === 0 ? 1 : 0;
                    $repo->addPhone($mbrId, $phone, $phoneTypeId, $isPrimary);
                }
            }

            // Create authentication record only if username/password provided
            if ($createAuth) {
                // TODO: Move to AuthRepository
                $orm->insert('user_authentication', [
                    'MbrID'        => $mbrId,
                    'Username'     => $data['username'],
                    'Email' => $data['email_address'],
                    'PasswordHash' => password_hash($data['password'], PASSWORD_DEFAULT),
                    'EmailVerified' => 0,
                    'IsActive'     => 1,
                    'CreatedAt'    => date('Y-m-d H:i:s')
                ]);
            }

            // Assign default "Member" role (RoleID 6)
            $orm->insert('member_role', [
                'MbrID'        => $mbrId,
                'RoleID'       => $data['member_role'] ?? 6,
                'IsActive'     => 1,
                'AssignedBy'   => Auth::getCurrentUserId(),
                'AssignedAt'   => date('Y-m-d H:i:s')
            ]);

            $repo->commit();

            Helpers::logError("New member registered: MbrID $mbrId" . ($createAuth ? " ({$data['username']})" : ""));
            return ['status' => 'success', 'mbr_id' => $mbrId];
        } catch (Exception $e) {
            $repo->rollBack();
            Helpers::logError("Member registration failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update an existing member profile
     */
    public static function update(int $mbrId, array $data): array
    {
        $repo = new MemberRepository();
        $orm = new ORM();

        Helpers::validateInput($data, [
            'first_name'     => 'required|max:100',
            'family_name'    => 'required|max:100',
            'email_address'  => 'required|email',
            'gender'         => 'in:Male,Female,Other|nullable',
            'branch_id'      => 'numeric|nullable',
            'marital_status_id' => 'numeric|nullable',
            'education_level_id' => 'numeric|nullable',
            'membership_status_id' => 'numeric|nullable',
            'member_role' => 'numeric|nullable',
        ]);

        if ($repo->emailExists($data['email_address'], $mbrId)) {
            ResponseHelper::error('Email address already in use by another member', 400);
        }

        // Validate phone numbers if provided
        if (!empty($data['phone_numbers'])) {
            $phoneNumbers = $data['phone_numbers'];
            if (is_string($phoneNumbers)) {
                $phoneJson = html_entity_decode($phoneNumbers, ENT_QUOTES, 'UTF-8');
                $decoded = json_decode($phoneJson, true);
                $phoneNumbers = is_array($decoded) ? $decoded : [];
            }

            if (is_array($phoneNumbers)) {
                foreach ($phoneNumbers as $phoneData) {
                    if (is_string($phoneData)) {
                        $phone = trim($phoneData);
                    } else {
                        $phone = trim($phoneData['number'] ?? $phoneData['PhoneNumber'] ?? '');
                    }
                    if ($phone !== '' && !self::isValidGhanaPhone($phone)) {
                        throw new Exception('Invalid Ghana phone number format: ' . $phone);
                    }
                }
            }
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
            'MbrMaritalStatusID'   => !empty($data['marital_status_id']) ? (int)$data['marital_status_id'] : null,
            'MbrEducationLevelID'  => !empty($data['education_level_id']) ? (int)$data['education_level_id'] : null,
            'MbrMembershipStatusID' => !empty($data['membership_status_id']) ? (int) $data['membership_status_id'] : null,
            'BranchID'             => !empty($data['branch_id']) ? (int)$data['branch_id'] : null,
            'FamilyID'             => !empty($data['family_id']) ? (int)$data['family_id'] : null,
        ];

        // Handle profile picture update or removal
        if (array_key_exists('profile_picture', $data)) {
            $currentMember = $repo->findById($mbrId);
            $oldPicture = $currentMember['MbrProfilePicture'] ?? null;

            if ($data['profile_picture'] === null) {
                // Remove profile picture
                $updateData['MbrProfilePicture'] = null;
                if ($oldPicture && file_exists(__DIR__ . '/../../' . $oldPicture)) {
                    unlink(__DIR__ . '/../../' . $oldPicture);
                }
            } elseif ($data['profile_picture'] !== $oldPicture) {
                // New picture uploaded
                $updateData['MbrProfilePicture'] = $data['profile_picture'];
                if ($oldPicture && file_exists(__DIR__ . '/../../' . $oldPicture)) {
                    unlink(__DIR__ . '/../../' . $oldPicture);
                }
            }
        }

        $repo->beginTransaction();
        try {
            $repo->update($mbrId, $updateData);

            if (isset($data['phone_numbers'])) {
                $phoneNumbers = $data['phone_numbers'];
                if (is_string($phoneNumbers)) {
                    $phoneJson = html_entity_decode($phoneNumbers, ENT_QUOTES, 'UTF-8');
                    $decoded = json_decode($phoneJson, true);
                    $phoneNumbers = is_array($decoded) ? $decoded : [];
                }
                if (!is_array($phoneNumbers)) {
                    $phoneNumbers = [];
                }

                $repo->deletePhones($mbrId);

                foreach ($phoneNumbers as $index => $phoneData) {
                    if (is_string($phoneData)) {
                        $phone = trim($phoneData);
                        $phoneTypeId = 1;
                    } else {
                        $phone = trim($phoneData['number'] ?? $phoneData['PhoneNumber'] ?? '');
                        $phoneTypeId = (int)($phoneData['type_id'] ?? $phoneData['PhoneTypeID'] ?? 1);
                    }

                    if ($phone === '')
                        continue;
                    $isPrimary = $index === 0 ? 1 : 0;
                    $repo->addPhone($mbrId, $phone, $phoneTypeId, $isPrimary);
                }
            }

            // Handle role update
            if (!empty($data['member_role'])) {
                // Deactivate old roles
                $orm->update('member_role', ['IsActive' => 0], ['MbrID' => $mbrId]);

                // Assign new role
                $orm->insert('member_role', [
                    'MbrID' => $mbrId,
                    'RoleID' => (int) $data['member_role'],
                    'IsActive' => 1,
                    'AssignedBy' => Auth::getCurrentUserId(),
                    'AssignedAt' => date('Y-m-d H:i:s')
                ]);
            }

            // Handle Authentication Update
            if (!empty($data['username']) || !empty($data['password'])) {
                $authData = [];
                if (!empty($data['username'])) {
                    // Check username uniqueness
                    $existingAuth = $orm->getWhere('user_authentication', ['Username' => $data['username']]);
                    if (!empty($existingAuth) && $existingAuth[0]['MbrID'] != $mbrId) {
                        throw new Exception('Username already exists');
                    }
                    $authData['Username'] = $data['username'];
                }

                if (!empty($data['password'])) {
                    $passwordCheck = Helpers::validatePasswordStrength($data['password']);
                    if (!$passwordCheck['valid']) {
                        throw new Exception('Password does not meet requirements: ' . implode(', ', $passwordCheck['errors']));
                    }
                    $authData['PasswordHash'] = password_hash($data['password'], PASSWORD_DEFAULT);
                }

                if (!empty($authData)) {
                    $existing = $orm->getWhere('user_authentication', ['MbrID' => $mbrId]);
                    if (!empty($existing)) {
                        $orm->update('user_authentication', $authData, ['MbrID' => $mbrId]);
                    } else {
                        // If no auth record exists, create one (e.g. enabling login for existing member)
                        $authData['MbrID'] = $mbrId;
                        $authData['Email'] = $data['email_address'];
                        $authData['EmailVerified'] = 0;
                        $authData['IsActive'] = 1;
                        $authData['CreatedAt'] = date('Y-m-d H:i:s');
                        $orm->insert('user_authentication', $authData);
                    }
                }
            }

            $repo->commit();
            return ['status' => 'success', 'mbr_id' => $mbrId];
        } catch (Exception $e) {
            $repo->rollBack();
            Helpers::logError("Member update failed for MbrID $mbrId: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Soft delete a member
     */
    public static function delete(int $mbrId): array
    {
        $repo = new MemberRepository();
        $success = $repo->delete($mbrId, Auth::getCurrentUserId());

        if (!$success) {
            ResponseHelper::error('Member not found or already deleted', 404);
        }

        Helpers::logError("Member soft-deleted: MbrID $mbrId by " . Auth::getCurrentUserId());
        return ['status' => 'success'];
    }

    /**
     * Retrieve a single member with phones and family
     */
    public static function get(int $mbrId): array
    {
        $repo = new MemberRepository();
        $member = $repo->findById($mbrId);

        if (empty($member)) {
            ResponseHelper::error('Member not found', 404);
        }

        $phones = $repo->getPhones($mbrId);
        $member['phones'] = $phones;
        $member['PhoneNumbers'] = array_column($phones, 'PhoneNumber');
        $member['PrimaryPhone'] = !empty($member['PhoneNumbers']) ? $member['PhoneNumbers'][0] : null;

        return $member;
    }

    /**
     * Get recent active members (last 10)
     */
    public static function getRecent(): array
    {
        $repo = new MemberRepository();
        // Since getRecent in repository uses optimized single query or ORM?
        // Repository::getRecent used selectWithJoin.
        // But Member::getRecent used QueryBuilder and caching.
        // I should update Repository to support QueryBuilder or keep QueryBuilder usage here?
        // The instruction says "Refactor Member.php to use repository".
        // The repository implementation of getRecent was:
        /*
        return $this->orm->selectWithJoin(
            baseTable: 'churchmember c',
            joins: [...],
            fields: [...],
            conditions: ['c.Deleted' => 0],
            orderBy: ['c.MbrRegistrationDate' => 'DESC'],
            limit: $limit
        );
        */
        // This is fine, but it misses the cache logic.
        // I should add cache logic to Member class (Service layer) or Repository.
        // Since caching is an optimization, Service layer is okay for now.
        // But the repository method returns array.
        // I'll use the repository method for now.
        return $repo->getRecent();
    }

    /**
     * Retrieve paginated list of active members
     */
    public static function getAll(int $page = 1, int $limit = 10, array $filters = []): array
    {
        $repo = new MemberRepository();

        // Sort logic
        $orderBy = 'c.MbrRegistrationDate DESC';
        if (!empty($filters['sort_by'])) {
            $sortColumn = $filters['sort_by'];
            $sortDir = strtoupper($filters['sort_dir'] ?? 'DESC');

            $columnMap = [
                'MbrFullName' => 'CONCAT_WS(c.MbrFirstName, c.MbrOtherNames, c.MbrFamilyName)',
                'MbrFirstName' => 'c.MbrFirstName',
                'MbrFamilyName' => 'c.MbrFamilyName',
                'MbrRegistrationDate' => 'c.MbrRegistrationDate',
                'MbrEmailAddress' => 'c.MbrEmailAddress',
                'MbrMembershipStatus' => 'mst.StatusName',
                'name' => 'c.MbrFirstName',
                'email' => 'c.MbrEmailAddress',
                'status' => 'mst.StatusName',
                'date' => 'c.MbrRegistrationDate'
            ];

            if (isset($columnMap[$sortColumn])) {
                $orderBy = $columnMap[$sortColumn] . ' ' . ($sortDir === 'ASC' ? 'ASC' : 'DESC');
            }
        }

        $offset = ($page - 1) * $limit;

        $result = $repo->findAll($limit, $offset, $filters, $orderBy);

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

    /**
     * Get member statistics
     */
    public static function getStats(): array
    {
        $statsRepo = new MemberStats();
        $stats = $statsRepo->getStats();

        $total = 0;
        $active = 0;
        $inactive = 0;
        foreach ($stats['status_counts'] as $row) {
            $count = (int)$row['count'];
            $total += $count;
            if ($row['MbrMembershipStatus'] === 'Active') {
                $active = $count;
            } elseif ($row['MbrMembershipStatus'] === 'Inactive') {
                $inactive += $count;
            }
        }

        $genderDistribution = [];
        foreach ($stats['gender_counts'] as $row) {
            $genderDistribution[] = [
                'gender' => $row['MbrGender'] ?? 'Unknown',
                'count' => (int) $row['count']
            ];
        }

        $ageDistribution = [];
        foreach ($stats['age_groups'] as $row) {
            $ageDistribution[] = [
                'group' => $row['age_group'],
                'count' => (int) $row['count']
            ];
        }

        return [
            'total_members' => $total,
            'active_members' => $active,
            'inactive_members' => $inactive,
            'new_this_month' => (int) $stats['new_this_month'],
            'gender_distribution' => $genderDistribution,
            'age_distribution' => $ageDistribution
        ];
    }
}
