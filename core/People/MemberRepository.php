<?php

declare(strict_types=1);

namespace AliveChMS\Core\People;

use AliveChMS\Core\System\ORM;
use Exception;

class MemberRepository
{
   private ORM $orm;

   public function __construct()
   {
      $this->orm = new ORM();
   }

   public function beginTransaction(): void
   {
      $this->orm->beginTransaction();
   }

   public function commit(): void
   {
      $this->orm->commit();
   }

   public function rollBack(): void
   {
      $this->orm->rollBack();
   }

   /**
    * Create a new member record
    */
   public function create(array $data): int
   {
      return $this->orm->insert('churchmember', $data)['id'];
   }

   /**
    * Update member record
    */
   public function update(int $id, array $data): bool
   {
      $affected = $this->orm->update('churchmember', $data, ['MbrID' => $id]);
      return $affected >= 0; // Return true even if no rows changed (e.g. same data)
   }

   /**
    * Soft delete member
    */
   public function delete(int $id, int $deletedBy): bool
   {
      $affected = $this->orm->update('churchmember', [
         'Deleted' => 1,
         'DeletedBy' => $deletedBy,
         'DeletedAt' => date('Y-m-d H:i:s')
      ], ['MbrID' => $id]);

      return $affected > 0;
   }

   /**
    * Find member by ID with all relations
    */
   public function findById(int $id): ?array
   {
      $member = $this->orm->selectWithJoin(
         baseTable: 'churchmember c',
         joins: [
            ['table' => 'family f', 'on' => 'c.FamilyID = f.FamilyID', 'type' => 'LEFT'],
            ['table' => 'marital_status ms', 'on' => 'c.MbrMaritalStatusID = ms.StatusID', 'type' => 'LEFT'],
            ['table' => 'education_level el', 'on' => 'c.MbrEducationLevelID = el.LevelID', 'type' => 'LEFT'],
            ['table' => 'membership_status mst', 'on' => 'c.MbrMembershipStatusID = mst.StatusID', 'type' => 'LEFT'],
            ['table' => 'member_role mr', 'on' => 'c.MbrID = mr.MbrID AND mr.IsActive = 1', 'type' => 'LEFT'],
            ['table' => 'church_role r', 'on' => 'mr.RoleID = r.RoleID', 'type' => 'LEFT'],
            ['table' => 'user_authentication ua', 'on' => 'c.MbrID = ua.MbrID', 'type' => 'LEFT']
         ],
         fields: [
            'c.*',
            'f.FamilyName',
            'ms.StatusName as MaritalStatusName',
            'el.LevelName as EducationLevelName',
            'mst.StatusName as MembershipStatusName',
            'r.RoleName',
            'r.RoleID',
            'ua.Username',
            'ua.IsActive as AuthActive'
         ],
         conditions: ['c.MbrID' => ':id', 'c.Deleted' => 0],
         params: [':id' => $id]
      );

      return $member[0] ?? null;
   }

   /**
    * Find member by Unique ID
    */
   public function findByUniqueId(string $uniqueId): ?array
   {
      $member = $this->orm->getWhere('churchmember', [
         'MbrUniqueID' => $uniqueId,
         'Deleted' => 0
      ]);
      return $member[0] ?? null;
   }

   /**
    * Get member phones
    */
   public function getPhones(int $mbrId): array
   {
      return $this->orm->selectWithJoin(
         baseTable: 'member_phone mp',
         joins: [
            ['table' => 'phone_type pt', 'on' => 'mp.PhoneTypeID = pt.TypeID', 'type' => 'LEFT']
         ],
         fields: ['mp.*', 'pt.TypeName as PhoneTypeName'],
         conditions: ['mp.MbrID' => ':id'],
         params: [':id' => $mbrId]
      );
   }

   /**
    * Add phone number
    */
   public function addPhone(int $mbrId, string $number, int $typeId, int $isPrimary): void
   {
      $this->orm->insert('member_phone', [
         'MbrID' => $mbrId,
         'PhoneNumber' => $number,
         'PhoneTypeID' => $typeId,
         'IsPrimary' => $isPrimary
      ]);
   }

   /**
    * Delete all phones for member
    */
   public function deletePhones(int $mbrId): void
   {
      $this->orm->delete('member_phone', ['MbrID' => $mbrId]);
   }

   /**
    * Check if email exists
    */
   public function emailExists(string $email, ?int $ignoreId = null): bool
   {
      $sql = "SELECT MbrID FROM churchmember WHERE MbrEmailAddress = :email AND Deleted = 0";
      $params = [':email' => $email];

      if ($ignoreId) {
         $sql .= " AND MbrID != :id";
         $params[':id'] = $ignoreId;
      }

      $result = $this->orm->runQuery($sql, $params);
      return !empty($result);
   }

   /**
    * Get paginated list of members with complex filtering
    */
   public function findAll(int $limit, int $offset, array $filters, string $orderBy): array
   {
      // Build WHERE conditions
      $whereConditions = ['c.Deleted = 0'];
      $params = [];

      if (!empty($filters['status'])) {
         $whereConditions[] = 'mst.StatusName = :status';
         $params[':status'] = $filters['status'];
      } else {
         $whereConditions[] = 'mst.StatusName = :status';
         $params[':status'] = 'Active';
      }

      if (!empty($filters['family_id'])) {
         $whereConditions[] = 'c.FamilyID = :family_id';
         $params[':family_id'] = (int) $filters['family_id'];
      }

      if (!empty($filters['date_from'])) {
         $whereConditions[] = 'c.MbrRegistrationDate >= :date_from';
         $params[':date_from'] = $filters['date_from'];
      }

      if (!empty($filters['date_to'])) {
         $whereConditions[] = 'c.MbrRegistrationDate <= :date_to';
         $params[':date_to'] = $filters['date_to'];
      }

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

      $params[':limit'] = $limit;
      $params[':offset'] = $offset;

      $members = $this->orm->runQuery(
         "SELECT c.*, f.FamilyName,
                    ms.StatusName as MaritalStatusName,
                    el.LevelName as EducationLevelName,
                    mst.StatusName as MembershipStatusName,
                    GROUP_CONCAT(DISTINCT CONCAT(p.PhoneNumber, ':', COALESCE(pt.TypeName, 'Mobile')) ORDER BY p.IsPrimary DESC, p.PhoneNumber SEPARATOR '||') AS PhoneData,
                    MAX(CASE WHEN p.IsPrimary = 1 THEN p.PhoneNumber END) AS PrimaryPhone
             FROM `churchmember` c
             LEFT JOIN `family` f ON c.FamilyID = f.FamilyID
             LEFT JOIN `marital_status` ms ON c.MbrMaritalStatusID = ms.StatusID
             LEFT JOIN `education_level` el ON c.MbrEducationLevelID = el.LevelID
             LEFT JOIN `membership_status` mst ON c.MbrMembershipStatusID = mst.StatusID
             LEFT JOIN `member_phone` p ON c.MbrID = p.MbrID
             LEFT JOIN `phone_type` pt ON p.PhoneTypeID = pt.TypeID
             WHERE $whereClause
             GROUP BY c.MbrID
             ORDER BY $orderBy
             LIMIT :limit OFFSET :offset",
         $params
      );

      // Get total count
      $countParams = array_diff_key($params, [':limit' => 0, ':offset' => 0]);
      $totalResult = $this->orm->runQuery(
         "SELECT COUNT(DISTINCT c.MbrID) AS total 
             FROM `churchmember` c
             LEFT JOIN `membership_status` mst ON c.MbrMembershipStatusID = mst.StatusID
             LEFT JOIN `member_phone` p ON c.MbrID = p.MbrID
             WHERE $whereClause",
         $countParams
      );

      $total = (int) ($totalResult[0]['total'] ?? 0);

      // Process phone numbers for display
      foreach ($members as &$member) {
         // Convert pipe-separated phone data to array
         $phoneData = !empty($member['PhoneData']) ? explode('||', $member['PhoneData']) : [];
         $member['PhoneNumbers'] = [];
         $member['phones'] = [];

         foreach ($phoneData as $index => $data) {
            if (empty($data))
               continue;

            $parts = explode(':', $data);
            $phoneNumber = $parts[0] ?? '';
            $phoneType = $parts[1] ?? 'Mobile';

            $member['PhoneNumbers'][] = $phoneNumber;
            $member['phones'][] = [
               'PhoneNumber' => $phoneNumber,
               'PhoneTypeName' => $phoneType,
               'IsPrimary' => ($index === 0) ? 1 : 0
            ];
         }

         // Ensure PrimaryPhone is set
         if (empty($member['PrimaryPhone']) && !empty($member['PhoneNumbers'])) {
            $member['PrimaryPhone'] = $member['PhoneNumbers'][0];
         }

         // Clean up temporary field
         unset($member['PhoneData']);
      }

      return ['data' => $members, 'total' => $total];
   }

   /**
    * Get recent members
    */
   public function getRecent(int $limit = 10): array
   {
      return $this->orm->selectWithJoin(
         baseTable: 'churchmember c',
         joins: [
            ['table' => 'membership_status mst', 'on' => 'c.MbrMembershipStatusID = mst.StatusID', 'type' => 'LEFT']
         ],
         fields: ['c.MbrID', 'c.MbrFirstName', 'c.MbrFamilyName', 'c.MbrProfilePicture', 'mst.StatusName'],
         conditions: ['c.Deleted' => 0],
         orderBy: ['c.MbrRegistrationDate' => 'DESC'],
         limit: $limit
      );
   }
}
