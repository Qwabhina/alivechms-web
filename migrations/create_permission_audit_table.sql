-- Permission Audit Trail Table
-- Tracks all permission changes for compliance and forensics

CREATE TABLE IF NOT EXISTS permission_audit (
    AuditID INT AUTO_INCREMENT PRIMARY KEY,
    ActionType ENUM('role_created', 'role_updated', 'role_deleted', 'permissions_assigned', 'permission_created', 'permission_updated', 'permission_deleted', 'role_assigned_to_member', 'role_removed_from_member') NOT NULL,
    PerformedBy INT NOT NULL COMMENT 'MbrID of user who performed the action',
    TargetRoleID INT NULL COMMENT 'Role affected by the action',
    TargetPermissionID INT NULL COMMENT 'Permission affected by the action',
    TargetMemberID INT NULL COMMENT 'Member affected by the action',
    OldValue TEXT NULL COMMENT 'Previous state (JSON)',
    NewValue TEXT NULL COMMENT 'New state (JSON)',
    IPAddress VARCHAR(45) NULL COMMENT 'IP address of the user',
    UserAgent TEXT NULL COMMENT 'Browser user agent',
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_performed_by (PerformedBy),
    INDEX idx_target_role (TargetRoleID),
    INDEX idx_target_permission (TargetPermissionID),
    INDEX idx_target_member (TargetMemberID),
    INDEX idx_created_at (CreatedAt),
    INDEX idx_action_type (ActionType),
    
    FOREIGN KEY (PerformedBy) REFERENCES churchmember(MbrID) ON DELETE RESTRICT,
    FOREIGN KEY (TargetRoleID) REFERENCES churchrole(RoleID) ON DELETE SET NULL,
    FOREIGN KEY (TargetPermissionID) REFERENCES permission(PermissionID) ON DELETE SET NULL,
    FOREIGN KEY (TargetMemberID) REFERENCES churchmember(MbrID) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
