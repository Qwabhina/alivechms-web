<?php

declare(strict_types=1);

namespace AliveChMS\Core\Operations;

use AliveChMS\Core\System\ORM;

/**
 * Event Repository
 * 
 * Handles database operations for church events and attendance.
 */
class EventRepository
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

   public function create(array $data): int
   {
      $result = $this->orm->insert('church_event', $data);
      return (int) $result['id'];
   }

   public function update(int $id, array $data): int
   {
      return $this->orm->update('church_event', $data, ['EventID' => $id]);
   }

   public function findById(int $id): ?array
   {
      $result = $this->orm->selectWithJoin(
         baseTable: 'church_event e',
         joins: [
            ['table' => 'branch b', 'on' => 'e.BranchID = b.BranchID'],
            ['table' => 'churchmember c', 'on' => 'e.CreatedBy = c.MbrID', 'type' => 'LEFT']
         ],
         fields: [
            'e.*',
            'b.BranchName',
            'c.MbrFirstName AS CreatorFirstName',
            'c.MbrFamilyName AS CreatorFamilyName'
         ],
         conditions: ['e.EventID' => ':id', 'e.Deleted' => 0],
         params: [':id' => $id]
      );

      return $result[0] ?? null;
   }

   public function findAll(int $limit, int $offset, array $filters = [], array $orderBy = ['e.EventDateTime' => 'DESC']): array
   {
      $conditions = ['e.Deleted' => 0];
      $params = [];

      if (!empty($filters['branch_id'])) {
         $conditions['e.BranchID'] = ':branch';
         $params[':branch'] = (int) $filters['branch_id'];
      }
      if (!empty($filters['start_date'])) {
         $conditions['e.EventDateTime >='] = ':start';
         $params[':start'] = $filters['start_date'];
      }
      if (!empty($filters['end_date'])) {
         $conditions['e.EventDateTime <='] = ':end';
         $params[':end'] = $filters['end_date'];
      }

      $events = $this->orm->selectWithJoin(
         baseTable: 'church_event e',
         joins: [['table' => 'branch b', 'on' => 'e.BranchID = b.BranchID']],
         fields: [
            'e.EventID',
            'e.EventName',
            'e.EventDateTime',
            'e.EndDateTime',
            'e.Location',
            'b.BranchName'
         ],
         conditions: $conditions,
         params: $params,
         orderBy: $orderBy,
         limit: $limit,
         offset: $offset
      );

      $total = $this->orm->runQuery(
         "SELECT COUNT(*) AS total FROM church_event WHERE Deleted = 0",
         $params
      )[0]['total'];

      return [
         'data' => $events,
         'total' => (int) $total
      ];
   }

   /**
    * Attendance Tracking
    */

   public function recordAttendance(array $data): void
   {
      // Check if attendance already recorded
      $existing = $this->orm->getWhere('event_attendance', [
         'EventID' => $data['EventID'],
         'MbrID' => $data['MbrID']
      ]);

      if (!empty($existing)) {
         $this->orm->update('event_attendance', [
            'CheckInTime' => $data['CheckInTime'] ?? date('Y-m-d H:i:s')
         ], ['AttendanceID' => $existing[0]['AttendanceID']]);
      } else {
         $this->orm->insert('event_attendance', $data);
      }
   }

   public function getAttendanceStats(int $eventId): array
   {
      return $this->orm->runQuery(
         "SELECT COUNT(*) AS total FROM event_attendance WHERE EventID = :id",
         [':id' => $eventId]
      );
   }

   public function hasAttendance(int $eventId): bool
   {
      $res = $this->orm->getWhere('event_attendance', ['EventID' => $eventId]);
      return !empty($res);
   }
}
