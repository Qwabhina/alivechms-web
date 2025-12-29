<?php

/**
 * Event API Routes – v1
 *
 * Complete church event management:
 * - Create, update, delete events
 * - Bulk & single attendance recording
 * - View single event with attendance summary
 * - Paginated listing with date/branch filtering
 *
 * All operations strictly permission-controlled.
 *
 * @package  AliveChMS\Routes
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2025-November
 */

declare(strict_types=1);

require_once __DIR__ . '/../core/Event.php';

class EventRoutes extends BaseRoute
{
   public static function handle(): void
   {
      // Get route variables from global scope
      global $method, $path, $pathParts;

      self::rateLimit(maxAttempts: 60, windowSeconds: 60);

      match (true) {
         // CREATE EVENT
         $method === 'POST' && $path === 'event/create' => (function () {
            self::authenticate();
            self::authorize('manage_events');

            $payload = self::getPayload();

            $result = Event::create($payload);
            self::success($result, 'Event created', 201);
         })(),

         // UPDATE EVENT
         $method === 'PUT' && $pathParts[0] === 'event' && ($pathParts[1] ?? '') === 'update' && isset($pathParts[2]) => (function () use ($pathParts) {
            self::authenticate();
            self::authorize('manage_events');

            $eventId = self::getIdFromPath($pathParts, 2, 'Event ID');

            $payload = self::getPayload();

            $result = Event::update($eventId, $payload);
            self::success($result, 'Event updated');
         })(),

         // DELETE EVENT
         $method === 'DELETE' && $pathParts[0] === 'event' && ($pathParts[1] ?? '') === 'delete' && isset($pathParts[2]) => (function () use ($pathParts) {
            self::authenticate();
            self::authorize('manage_events');

            $eventId = self::getIdFromPath($pathParts, 2, 'Event ID');

            $result = Event::delete($eventId);
            self::success($result, 'Event deleted');
         })(),

         // VIEW SINGLE EVENT
         $method === 'GET' && $pathParts[0] === 'event' && ($pathParts[1] ?? '') === 'view' && isset($pathParts[2]) => (function () use ($pathParts) {
            self::authenticate();
            self::authorize('view_events');

            $eventId = self::getIdFromPath($pathParts, 2, 'Event ID');

            $event = Event::get($eventId);
            self::success($event);
         })(),

         // LIST ALL EVENTS (Paginated + Filtered)
         $method === 'GET' && $path === 'event/all' => (function () {
            self::authenticate();
            self::authorize('view_events');

            [$page, $limit] = self::getPagination(10, 100);

            $filters = self::getFilters(['branch_id', 'start_date', 'end_date']);

            $result = Event::getAll($page, $limit, $filters);
            self::paginated($result['data'], $result['pagination']['total'], $page, $limit);
         })(),

         // RECORD BULK ATTENDANCE
         $method === 'POST' && $pathParts[0] === 'event' && ($pathParts[1] ?? '') === 'attendance' && ($pathParts[2] ?? '') === 'bulk' && isset($pathParts[3]) => (function () use ($pathParts) {
            self::authenticate();
            self::authorize('record_attendance');

            $eventId = self::getIdFromPath($pathParts, 3, 'Event ID');

            $payload = self::getPayload([
               'attendances' => 'required|array'
            ]);

            $result = Event::recordBulkAttendance($eventId, $payload);
            self::success($result, 'Attendance recorded');
         })(),

         // RECORD SINGLE ATTENDANCE (Mobile/Self-Check-in)
         $method === 'POST' && $pathParts[0] === 'event' && ($pathParts[1] ?? '') === 'attendance' && ($pathParts[2] ?? '') === 'single' && isset($pathParts[3]) => (function () use ($pathParts) {
            self::authenticate();
            self::authorize('record_attendance');

            $eventId = self::getIdFromPath($pathParts, 3, 'Event ID');

            $payload = self::getPayload([
               'member_id' => 'required|numeric',
               'status' => 'nullable|in:Present,Absent,Late'
            ]);

            $status = $payload['status'] ?? 'Present';
            $result = Event::recordSingleAttendance($eventId, (int)$payload['member_id'], $status);
            self::success($result, 'Attendance recorded');
         })(),

         // FALLBACK
         default => self::error('Event endpoint not found', 404),
      };
   }
}

// Dispatch
EventRoutes::handle();
