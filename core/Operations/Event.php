<?php

/**
 * Event Management Service
 *
 * @package  AliveChMS\Core
 * @version  2.0.0
 */

declare(strict_types=1);

namespace AliveChMS\Core\Operations;

use AliveChMS\Core\Operations\EventRepository;
use AliveChMS\Core\System\Helpers;
use AliveChMS\Core\System\ResponseHelper;
use AliveChMS\Core\Identity\Auth;
use Exception;

class Event
{
   /**
    * Create a new church event
    */
   public static function create(array $data): array
   {
      $repo = new EventRepository();

      Helpers::validateInput($data, [
         'title'       => 'required|max:150',
         'event_date'  => 'required|date',
         'branch_id'   => 'required|numeric',
         'description' => 'max:1000|nullable',
         'start_time'  => 'nullable',
         'end_time'    => 'nullable',
         'location'    => 'max:200|nullable'
      ]);

      $branchId = (int)$data['branch_id'];

      // Combine Date and Time for Schema
      $startDateTime = $data['event_date'] . ' ' . ($data['start_time'] ?? '00:00:00');
      $endDateTime = !empty($data['end_time']) ? $data['event_date'] . ' ' . $data['end_time'] : null;

      $eventId = $repo->create([
         'EventName' => $data['title'],
         'EventDescription' => $data['description'] ?? null,
         'EventDateTime' => $startDateTime,
         'EndDateTime' => $endDateTime,
         'Location'         => $data['location'] ?? null,
         'BranchID'         => $branchId,
         'CreatedBy'        => Auth::getCurrentUserId(),
         'CreatedAt'        => date('Y-m-d H:i:s')
      ]);

      return ['status' => 'success', 'event_id' => $eventId];
   }

   public static function update(int $eventId, array $data): array
   {
      $repo = new EventRepository();
      $event = $repo->findById($eventId);

      if (!$event)
         ResponseHelper::error('Event not found', 404);

      $update = [];
      if (!empty($data['title']))
         $update['EventName'] = $data['title'];
      if (isset($data['description']))  $update['EventDescription'] = $data['description'];

      // Update date/time if provided
      if (!empty($data['event_date'])) {
         $date = $data['event_date'];
         $time = $data['start_time'] ?? explode(' ', $event['EventDateTime'])[1];
         $update['EventDateTime'] = $date . ' ' . $time;
      }

      if (!empty($update)) {
         $update['UpdatedAt'] = date('Y-m-d H:i:s');
         $repo->update($eventId, $update);
      }

      return ['status' => 'success'];
   }

   public static function delete(int $eventId): array
   {
      $repo = new EventRepository();
      $event = $repo->findById($eventId);

      if (!$event)
         ResponseHelper::error('Event not found', 404);
      if ($repo->hasAttendance($eventId)) {
         ResponseHelper::error('Cannot delete event with recorded attendance', 400);
      }

      $repo->update($eventId, [
         'Deleted' => 1,
         'DeletedAt' => date('Y-m-d H:i:s')
      ]);

      return ['status' => 'success'];
   }

   /**
    * Attendance
    */
   public static function recordAttendance(int $eventId, int $memberId): array
   {
      $repo = new EventRepository();
      $repo->recordAttendance([
         'EventID' => $eventId,
         'MbrID' => $memberId,
         'CheckInTime' => date('Y-m-d H:i:s')
      ]);

      return ['status' => 'success'];
   }

   public static function get(int $eventId): array
   {
      $repo = new EventRepository();
      $event = $repo->findById($eventId);
      if (!$event)
         ResponseHelper::error('Event not found', 404);
      return $event;
   }

   public static function getAll(int $page = 1, int $limit = 10, array $filters = []): array
   {
      $repo = new EventRepository();
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