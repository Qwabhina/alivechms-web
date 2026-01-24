-- Migration: Add member volunteer role assignments
-- Date: 2026-01-23
-- Description: Create table to assign volunteer roles to members

CREATE TABLE IF NOT EXISTS `member_volunteer_role` (
  `MemberVolunteerRoleID` int(11) NOT NULL AUTO_INCREMENT,
  `MbrID` int(11) NOT NULL COMMENT 'Member ID',
  `VolunteerRoleID` int(11) NOT NULL COMMENT 'Volunteer Role ID',
  `StartDate` date DEFAULT NULL COMMENT 'When assignment becomes active',
  `EndDate` date DEFAULT NULL COMMENT 'When assignment expires',
  `IsActive` tinyint(1) DEFAULT 1 COMMENT 'Active status',
  `AssignedBy` int(11) DEFAULT NULL COMMENT 'Who assigned this role',
  `AssignedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `Notes` text DEFAULT NULL COMMENT 'Assignment notes',
  PRIMARY KEY (`MemberVolunteerRoleID`),
  KEY `idx_member` (`MbrID`),
  KEY `idx_volunteer_role` (`VolunteerRoleID`),
  KEY `idx_active` (`IsActive`),
  CONSTRAINT `fk_mvr_member` FOREIGN KEY (`MbrID`) REFERENCES `churchmember` (`MbrID`) ON DELETE CASCADE,
  CONSTRAINT `fk_mvr_volunteer_role` FOREIGN KEY (`VolunteerRoleID`) REFERENCES `volunteer_role` (`VolunteerRoleID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add index for performance
CREATE INDEX idx_member_active ON member_volunteer_role(MbrID, IsActive);
CREATE INDEX idx_role_active ON member_volunteer_role(VolunteerRoleID, IsActive);
