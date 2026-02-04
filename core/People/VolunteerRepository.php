<?php

declare(strict_types=1);

namespace AliveChMS\Core\People;

use AliveChMS\Core\System\ORM;

/**
 * Volunteer Repository
 * 
 * Handles database operations for church volunteers and departmental service.
 */
class VolunteerRepository
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

    public function getRoles(): array
    {
        return $this->orm->runQuery(
            "SELECT vr.*, (SELECT COUNT(*) FROM member_volunteer_role mvr WHERE mvr.VolunteerRoleID = vr.VolunteerRoleID AND mvr.IsActive = 1) AS MemberCount
             FROM volunteer_role vr
             WHERE vr.IsActive = 1
             ORDER BY vr.RoleName ASC"
        );
    }

    public function createRole(array $data): int
    {
        $result = $this->orm->insert('volunteer_role', $data);
        return (int)$result['id'];
    }

    public function assignToEvent(array $data): void
    {
        $this->orm->insert('event_volunteer', $data);
    }

    public function getEventVolunteers(int $eventId): array
    {
        return $this->orm->selectWithJoin(
            baseTable: 'event_volunteer ev',
            joins: [
                ['table' => 'churchmember m',   'on' => 'ev.MbrID = m.MbrID'],
                ['table' => 'volunteer_role vr', 'on' => 'ev.VolunteerRoleID = vr.VolunteerRoleID', 'type' => 'LEFT']
            ],
            fields: ['ev.*', 'm.MbrFirstName', 'm.MbrFamilyName', 'vr.RoleName'],
            conditions: ['ev.EventID' => ':id'],
            params: [':id' => $eventId]
        );
    }

    public function updateStatus(int $assignmentId, string $status): void
    {
        $this->orm->update('event_volunteer', ['Status' => $status], ['AssignmentID' => $assignmentId]);
    }

    public function removeVolunteer(int $assignmentId): void
    {
        $this->orm->delete('event_volunteer', ['AssignmentID' => $assignmentId]);
    }

    public function getAssignments(int $eventId, int $memberId): array
    {
        return $this->orm->selectWithJoin(
            baseTable: 'event_volunteer ev',
            joins: [
                ['table' => 'churchmember m', 'on' => 'ev.MbrID = m.MbrID'],
                ['table' => 'volunteer_role vr', 'on' => 'ev.VolunteerRoleID = vr.VolunteerRoleID', 'type' => 'LEFT']
            ],
            fields: ['ev.*', 'm.MbrFirstName', 'm.MbrFamilyName', 'vr.RoleName'],
            conditions: ['ev.EventID' => ':id', 'ev.MbrID' => ':memberId'],
            params: [':id' => $eventId, ':memberId' => $memberId]
        );
    }

    public function getVolunteers(int $eventId): array
    {
        return $this->orm->selectWithJoin(
            baseTable: 'event_volunteer ev',
            joins: [
                ['table' => 'churchmember m', 'on' => 'ev.MbrID = m.MbrID'],
                ['table' => 'volunteer_role vr', 'on' => 'ev.VolunteerRoleID = vr.VolunteerRoleID', 'type' => 'LEFT']
            ],
            fields: ['ev.*', 'm.MbrFirstName', 'm.MbrFamilyName', 'vr.RoleName'],
            conditions: ['ev.EventID' => ':id'],
            params: [':id' => $eventId]
        );
    }

    public function getStats(int $eventId): array
    {
        return $this->orm->selectWithJoin(
            baseTable: 'event_volunteer ev',
            joins: [
                ['table' => 'churchmember m', 'on' => 'ev.MbrID = m.MbrID'],
                ['table' => 'volunteer_role vr', 'on' => 'ev.VolunteerRoleID = vr.VolunteerRoleID', 'type' => 'LEFT']
            ],
            fields: ['ev.*', 'm.MbrFirstName', 'm.MbrFamilyName', 'vr.RoleName'],
            conditions: ['ev.EventID' => ':id'],
            params: [':id' => $eventId]
        );
    }

    public function getByEvent(int $eventId, int $page = 1, int $limit = 10): array
    {
        return $this->orm->selectWithJoin(
            baseTable: 'event_volunteer ev',
            joins: [
                ['table' => 'churchmember m', 'on' => 'ev.MbrID = m.MbrID'],
                ['table' => 'volunteer_role vr', 'on' => 'ev.VolunteerRoleID = vr.VolunteerRoleID', 'type' => 'LEFT']
            ],
            fields: ['ev.*', 'm.MbrFirstName', 'm.MbrFamilyName', 'vr.RoleName'],
            conditions: ['ev.EventID' => ':id'],
            params: [':id' => $eventId]
        );
    }

    public function getByRole(int $roleId): array
    {
        return $this->orm->selectWithJoin(
            baseTable: 'event_volunteer ev',
            joins: [
                ['table' => 'churchmember m', 'on' => 'ev.MbrID = m.MbrID'],
                ['table' => 'volunteer_role vr', 'on' => 'ev.VolunteerRoleID = vr.VolunteerRoleID', 'type' => 'LEFT']
            ],
            fields: ['ev.*', 'm.MbrFirstName', 'm.MbrFamilyName', 'vr.RoleName'],
            conditions: ['ev.VolunteerRoleID' => ':id'],
            params: [':id' => $roleId]
        );
    }

    public function getByMember(int $memberId): array
    {
        return $this->orm->selectWithJoin(
            baseTable: 'event_volunteer ev',
            joins: [
                ['table' => 'churchmember m', 'on' => 'ev.MbrID = m.MbrID'],
                ['table' => 'volunteer_role vr', 'on' => 'ev.VolunteerRoleID = vr.VolunteerRoleID', 'type' => 'LEFT']
            ],
            fields: ['ev.*', 'm.MbrFirstName', 'm.MbrFamilyName', 'vr.RoleName'],
            conditions: ['ev.MbrID' => ':id'],
            params: [':id' => $memberId]
        );
    }

    public function assignRoleToMember(array $data): void
    {
        $this->orm->insert('member_volunteer_role', $data);
    }

    public function getMemberVolunteerRoles(int $memberId): array
    {
        return $this->orm->selectWithJoin(
            baseTable: 'member_volunteer_role mvr',
            joins: [
                ['table' => 'churchmember m', 'on' => 'mvr.MbrID = m.MbrID'],
                ['table' => 'volunteer_role vr', 'on' => 'mvr.VolunteerRoleID = vr.VolunteerRoleID', 'type' => 'LEFT']
            ],
            fields: ['mvr.*', 'm.MbrFirstName', 'm.MbrFamilyName', 'vr.RoleName'],
            conditions: ['mvr.MbrID' => ':id'],
            params: [':id' => $memberId]
        );
    }

    public function removeMemberVolunteerRole(int $assignmentId): void
    {
        $this->orm->delete('member_volunteer_role', ['AssignmentID' => $assignmentId]);
    }

    public function getMembersByRole(int $roleId, int $page = 1, int $limit = 10): array
    {
        return $this->orm->selectWithJoin(
            baseTable: 'member_volunteer_role mvr',
            joins: [
                ['table' => 'churchmember m', 'on' => 'mvr.MbrID = m.MbrID'],
                ['table' => 'volunteer_role vr', 'on' => 'mvr.VolunteerRoleID = vr.VolunteerRoleID', 'type' => 'LEFT']
            ],
            fields: ['mvr.*', 'm.MbrFirstName', 'm.MbrFamilyName', 'vr.RoleName'],
            conditions: ['mvr.VolunteerRoleID' => ':id'],
            params: [':id' => $roleId]
        );
    }
}
