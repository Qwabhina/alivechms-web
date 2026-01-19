-- ============================================================================
-- COMPREHENSIVE RBAC SYSTEM FOR ALIVECHMS
-- ============================================================================
-- This migration creates a complete, secure, and performant RBAC system
-- 
-- Features:
-- - Role hierarchy with inheritance
-- - Permission caching support
-- - Comprehensive audit trail
-- - Temporal role assignments (start/end dates)
-- - Permission categories for better organization
-- ============================================================================

-- Start transaction
START TRANSACTION;

-- ============================================================================
-- 1. PERMISSION CATEGORIES
-- ============================================================================
-- Organize permissions into logical categories for better management

CREATE TABLE IF NOT EXISTS permission_category (
    CategoryID INT AUTO_INCREMENT PRIMARY KEY,
    CategoryName VARCHAR(50) NOT NULL UNIQUE,
    CategoryDescription TEXT,
    DisplayOrder INT DEFAULT 0,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_display_order (DisplayOrder)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add category to permission table
ALTER TABLE permission 
ADD COLUMN IF NOT EXISTS CategoryID INT NULL AFTER PermissionName,
ADD COLUMN IF NOT EXISTS PermissionDescription TEXT NULL AFTER CategoryID,
ADD COLUMN IF NOT EXISTS IsActive TINYINT(1) DEFAULT 1 AFTER PermissionDescription,
ADD COLUMN IF NOT EXISTS CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER IsActive;

-- Add foreign key for CategoryID (check if exists first)
SET @fk_perm_cat = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
    WHERE CONSTRAINT_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'permission' 
    AND CONSTRAINT_NAME = 'fk_permission_category');

SET @sql = IF(@fk_perm_cat = 0, 
    'ALTER TABLE permission ADD CONSTRAINT fk_permission_category FOREIGN KEY (CategoryID) REFERENCES permission_category(CategoryID) ON DELETE SET NULL',
    'SELECT "Foreign key already exists" AS message');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add index for active permissions
ALTER TABLE permission ADD INDEX IF NOT EXISTS idx_active (IsActive);

-- ============================================================================
-- 2. ENHANCED ROLE TABLE
-- ============================================================================
-- Add missing columns to churchrole table

ALTER TABLE churchrole 
ADD COLUMN IF NOT EXISTS Description TEXT NULL AFTER RoleDescription,
ADD COLUMN IF NOT EXISTS IsSystemRole TINYINT(1) DEFAULT 0 COMMENT 'System roles cannot be deleted',
ADD COLUMN IF NOT EXISTS IsActive TINYINT(1) DEFAULT 1,
ADD COLUMN IF NOT EXISTS DisplayOrder INT DEFAULT 0,
ADD COLUMN IF NOT EXISTS CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN IF NOT EXISTS UpdatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Consolidate description columns
UPDATE churchrole SET Description = RoleDescription WHERE Description IS NULL AND RoleDescription IS NOT NULL;

-- Add indexes
ALTER TABLE churchrole ADD INDEX IF NOT EXISTS idx_active (IsActive);
ALTER TABLE churchrole ADD INDEX IF NOT EXISTS idx_system_role (IsSystemRole);

-- ============================================================================
-- 3. TEMPORAL ROLE ASSIGNMENTS
-- ============================================================================
-- Add start/end dates to memberrole for temporary role assignments

ALTER TABLE memberrole
ADD COLUMN IF NOT EXISTS StartDate DATE NULL COMMENT 'When role assignment becomes active',
ADD COLUMN IF NOT EXISTS EndDate DATE NULL COMMENT 'When role assignment expires',
ADD COLUMN IF NOT EXISTS IsActive TINYINT(1) DEFAULT 1 COMMENT 'Manually deactivate without deleting',
ADD COLUMN IF NOT EXISTS AssignedBy INT NULL COMMENT 'Who assigned this role',
ADD COLUMN IF NOT EXISTS AssignedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN IF NOT EXISTS Notes TEXT NULL COMMENT 'Reason for assignment';

-- Add foreign key for AssignedBy (check if exists first)
SET @fk_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
    WHERE CONSTRAINT_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'memberrole' 
    AND CONSTRAINT_NAME = 'fk_memberrole_assigned_by');

SET @sql = IF(@fk_exists = 0, 
    'ALTER TABLE memberrole ADD CONSTRAINT fk_memberrole_assigned_by FOREIGN KEY (AssignedBy) REFERENCES churchmember(MbrID) ON DELETE SET NULL',
    'SELECT "Foreign key already exists" AS message');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add indexes for temporal queries
ALTER TABLE memberrole ADD INDEX IF NOT EXISTS idx_active (IsActive);
ALTER TABLE memberrole ADD INDEX IF NOT EXISTS idx_dates (StartDate, EndDate);
ALTER TABLE memberrole ADD INDEX IF NOT EXISTS idx_member_active (MbrID, IsActive);

-- ============================================================================
-- 4. ROLE HIERARCHY ENHANCEMENTS
-- ============================================================================
-- Enhance role_hierarchy table for better inheritance support

ALTER TABLE role_hierarchy
ADD COLUMN IF NOT EXISTS InheritanceLevel INT DEFAULT 1 COMMENT 'Distance from root (1=direct parent)',
ADD COLUMN IF NOT EXISTS CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- Add index for hierarchy queries
ALTER TABLE role_hierarchy ADD INDEX IF NOT EXISTS idx_hierarchy (ParentRoleID, InheritanceLevel);

-- ============================================================================
-- 5. PERMISSION CACHE TABLE
-- ============================================================================
-- Store computed permissions for performance

CREATE TABLE IF NOT EXISTS permission_cache (
    CacheID INT AUTO_INCREMENT PRIMARY KEY,
    MbrID INT NOT NULL,
    PermissionName VARCHAR(50) NOT NULL,
    SourceRoleID INT NOT NULL COMMENT 'Which role granted this permission',
    IsInherited TINYINT(1) DEFAULT 0 COMMENT 'Inherited from parent role',
    CachedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ExpiresAt TIMESTAMP NULL,
    
    UNIQUE KEY unique_member_permission (MbrID, PermissionName),
    INDEX idx_member (MbrID),
    INDEX idx_permission (PermissionName),
    INDEX idx_expires (ExpiresAt),
    INDEX idx_source_role (SourceRoleID),
    
    FOREIGN KEY (MbrID) REFERENCES churchmember(MbrID) ON DELETE CASCADE,
    FOREIGN KEY (SourceRoleID) REFERENCES churchrole(RoleID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 6. ENHANCED AUDIT TRAIL
-- ============================================================================
-- Already exists, but let's ensure it has all needed columns

-- Add request ID for tracking related changes
ALTER TABLE permission_audit
ADD COLUMN IF NOT EXISTS RequestID VARCHAR(36) NULL COMMENT 'UUID to group related changes',
ADD COLUMN IF NOT EXISTS SessionID VARCHAR(100) NULL COMMENT 'User session identifier';

-- Add indexes for better audit queries
ALTER TABLE permission_audit ADD INDEX IF NOT EXISTS idx_request_id (RequestID);
ALTER TABLE permission_audit ADD INDEX IF NOT EXISTS idx_session_id (SessionID);

-- ============================================================================
-- 7. PERMISSION GROUPS (Optional but useful)
-- ============================================================================
-- Group related permissions for bulk assignment

CREATE TABLE IF NOT EXISTS permission_group (
    GroupID INT AUTO_INCREMENT PRIMARY KEY,
    GroupName VARCHAR(100) NOT NULL UNIQUE,
    GroupDescription TEXT,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_name (GroupName)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS permission_group_member (
    GroupMemberID INT AUTO_INCREMENT PRIMARY KEY,
    GroupID INT NOT NULL,
    PermissionID INT NOT NULL,
    
    UNIQUE KEY unique_group_permission (GroupID, PermissionID),
    FOREIGN KEY (GroupID) REFERENCES permission_group(GroupID) ON DELETE CASCADE,
    FOREIGN KEY (PermissionID) REFERENCES permission(PermissionID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 8. SEED DATA - PERMISSION CATEGORIES
-- ============================================================================

INSERT IGNORE INTO permission_category (CategoryName, CategoryDescription, DisplayOrder) VALUES
('member_management', 'Member and family management permissions', 1),
('financial', 'Financial operations and reporting', 2),
('events', 'Event and attendance management', 3),
('groups', 'Group and ministry management', 4),
('communication', 'Communication and messaging', 5),
('administration', 'System administration and settings', 6),
('reports', 'Reporting and analytics', 7);

-- ============================================================================
-- 9. SEED DATA - CORE PERMISSIONS
-- ============================================================================

-- Get category IDs
SET @cat_member = (SELECT CategoryID FROM permission_category WHERE CategoryName = 'member_management');
SET @cat_financial = (SELECT CategoryID FROM permission_category WHERE CategoryName = 'financial');
SET @cat_events = (SELECT CategoryID FROM permission_category WHERE CategoryName = 'events');
SET @cat_groups = (SELECT CategoryID FROM permission_category WHERE CategoryName = 'groups');
SET @cat_communication = (SELECT CategoryID FROM permission_category WHERE CategoryName = 'communication');
SET @cat_admin = (SELECT CategoryID FROM permission_category WHERE CategoryName = 'administration');
SET @cat_reports = (SELECT CategoryID FROM permission_category WHERE CategoryName = 'reports');

-- Insert core permissions (only if they don't exist)
INSERT IGNORE INTO permission (PermissionName, CategoryID, PermissionDescription, IsActive) VALUES
-- Member Management
('view_members', @cat_member, 'View member profiles and information', 1),
('create_members', @cat_member, 'Add new members to the system', 1),
('edit_members', @cat_member, 'Edit member information', 1),
('delete_members', @cat_member, 'Delete or deactivate members', 1),
('manage_families', @cat_member, 'Manage family relationships', 1),
('view_member_reports', @cat_member, 'View member statistics and reports', 1),

-- Financial
('view_contribution', @cat_financial, 'View contribution records', 1),
('create_contribution', @cat_financial, 'Record new contributions', 1),
('edit_contribution', @cat_financial, 'Edit contribution records', 1),
('delete_contribution', @cat_financial, 'Delete contribution records', 1),
('view_expenses', @cat_financial, 'View expense records', 1),
('create_expense', @cat_financial, 'Submit expense requests', 1),
('approve_expenses', @cat_financial, 'Approve or reject expenses', 1),
('manage_budgets', @cat_financial, 'Create and manage budgets', 1),
('approve_budgets', @cat_financial, 'Approve budget proposals', 1),
('view_financial_reports', @cat_financial, 'View financial reports and analytics', 1),
('manage_fiscal_years', @cat_financial, 'Manage fiscal year settings', 1),

-- Events
('view_events', @cat_events, 'View church events', 1),
('create_events', @cat_events, 'Create new events', 1),
('manage_events', @cat_events, 'Edit and delete events', 1),
('record_attendance', @cat_events, 'Record event attendance', 1),
('view_attendance_reports', @cat_events, 'View attendance statistics', 1),
('manage_volunteers', @cat_events, 'Assign and manage event volunteers', 1),

-- Groups
('view_groups', @cat_groups, 'View church groups and ministries', 1),
('create_groups', @cat_groups, 'Create new groups', 1),
('manage_groups', @cat_groups, 'Edit and manage groups', 1),
('assign_group_members', @cat_groups, 'Add/remove group members', 1),

-- Communication
('send_messages', @cat_communication, 'Send messages to members', 1),
('send_bulk_messages', @cat_communication, 'Send bulk messages to groups', 1),
('view_message_history', @cat_communication, 'View sent message history', 1),

-- Administration
('manage_roles', @cat_admin, 'Create and manage roles', 1),
('manage_permissions', @cat_admin, 'Assign permissions to roles', 1),
('manage_branches', @cat_admin, 'Manage church branches', 1),
('manage_settings', @cat_admin, 'Configure system settings', 1),
('view_audit_logs', @cat_admin, 'View system audit logs', 1),
('manage_users', @cat_admin, 'Manage user accounts', 1),

-- Reports
('view_dashboard', @cat_reports, 'Access main dashboard', 1),
('export_reports', @cat_reports, 'Export reports to PDF/Excel', 1),
('view_analytics', @cat_reports, 'View advanced analytics', 1);

-- ============================================================================
-- 10. SEED DATA - CORE ROLES
-- ============================================================================

-- Insert core roles (only if they don't exist)
INSERT IGNORE INTO churchrole (RoleName, Description, IsSystemRole, IsActive, DisplayOrder) VALUES
('Super Admin', 'Full system access - cannot be deleted', 1, 1, 1),
('Admin', 'Administrative access to most features', 1, 1, 2),
('Pastor', 'Church leadership with broad access', 1, 1, 3),
('Elder', 'Church elder with oversight responsibilities', 0, 1, 4),
('Treasurer', 'Financial management and reporting', 0, 1, 5),
('Secretary', 'Administrative and record keeping', 0, 1, 6),
('Group Leader', 'Manage specific groups or ministries', 0, 1, 7),
('Member', 'Basic member access', 1, 1, 8),
('Guest', 'Limited guest access', 0, 1, 9);

-- ============================================================================
-- 11. ROLE HIERARCHY
-- ============================================================================

-- Clear existing hierarchy
DELETE FROM role_hierarchy;

-- Set up role hierarchy (Super Admin > Admin > Pastor > Elder > Member)
INSERT INTO role_hierarchy (ChildRoleID, ParentRoleID, InheritanceLevel)
SELECT 
    c.RoleID as ChildRoleID,
    p.RoleID as ParentRoleID,
    1 as InheritanceLevel
FROM churchrole c
CROSS JOIN churchrole p
WHERE 
    (c.RoleName = 'Admin' AND p.RoleName = 'Super Admin') OR
    (c.RoleName = 'Pastor' AND p.RoleName = 'Admin') OR
    (c.RoleName = 'Elder' AND p.RoleName = 'Pastor') OR
    (c.RoleName = 'Treasurer' AND p.RoleName = 'Admin') OR
    (c.RoleName = 'Secretary' AND p.RoleName = 'Admin') OR
    (c.RoleName = 'Group Leader' AND p.RoleName = 'Member') OR
    (c.RoleName = 'Member' AND p.RoleName = 'Guest');

-- ============================================================================
-- 12. ASSIGN PERMISSIONS TO ROLES
-- ============================================================================

-- Super Admin gets ALL permissions
INSERT IGNORE INTO rolepermission (ChurchRoleID, PermissionID)
SELECT 
    (SELECT RoleID FROM churchrole WHERE RoleName = 'Super Admin'),
    PermissionID
FROM permission
WHERE IsActive = 1;

-- Admin gets most permissions (exclude some super admin only)
INSERT IGNORE INTO rolepermission (ChurchRoleID, PermissionID)
SELECT 
    (SELECT RoleID FROM churchrole WHERE RoleName = 'Admin'),
    PermissionID
FROM permission
WHERE IsActive = 1
AND PermissionName NOT IN ('manage_users'); -- Reserve for Super Admin

-- Pastor permissions
INSERT IGNORE INTO rolepermission (ChurchRoleID, PermissionID)
SELECT 
    (SELECT RoleID FROM churchrole WHERE RoleName = 'Pastor'),
    PermissionID
FROM permission
WHERE PermissionName IN (
    'view_members', 'create_members', 'edit_members', 'manage_families',
    'view_member_reports', 'view_contribution', 'view_expenses',
    'view_financial_reports', 'view_events', 'create_events', 'manage_events',
    'record_attendance', 'view_attendance_reports', 'manage_volunteers',
    'view_groups', 'create_groups', 'manage_groups', 'assign_group_members',
    'send_messages', 'send_bulk_messages', 'view_message_history',
    'view_dashboard', 'view_analytics'
);

-- Treasurer permissions
INSERT IGNORE INTO rolepermission (ChurchRoleID, PermissionID)
SELECT 
    (SELECT RoleID FROM churchrole WHERE RoleName = 'Treasurer'),
    PermissionID
FROM permission
WHERE PermissionName IN (
    'view_members', 'view_contribution', 'create_contribution', 'edit_contribution',
    'view_expenses', 'create_expense', 'approve_expenses', 'manage_budgets',
    'approve_budgets', 'view_financial_reports', 'manage_fiscal_years',
    'view_dashboard', 'export_reports'
);

-- Secretary permissions
INSERT IGNORE INTO rolepermission (ChurchRoleID, PermissionID)
SELECT 
    (SELECT RoleID FROM churchrole WHERE RoleName = 'Secretary'),
    PermissionID
FROM permission
WHERE PermissionName IN (
    'view_members', 'create_members', 'edit_members', 'manage_families',
    'view_events', 'create_events', 'manage_events', 'record_attendance',
    'view_groups', 'send_messages', 'view_message_history', 'view_dashboard'
);

-- Member permissions (basic access)
INSERT IGNORE INTO rolepermission (ChurchRoleID, PermissionID)
SELECT 
    (SELECT RoleID FROM churchrole WHERE RoleName = 'Member'),
    PermissionID
FROM permission
WHERE PermissionName IN (
    'view_events', 'view_groups', 'view_dashboard'
);

-- ============================================================================
-- 13. CREATE VIEWS FOR EASY QUERYING
-- ============================================================================

-- View: Active member roles with details
CREATE OR REPLACE VIEW v_active_member_roles AS
SELECT 
    mr.MemberRoleID,
    mr.MbrID,
    cm.MbrFirstName,
    cm.MbrFamilyName,
    mr.ChurchRoleID,
    cr.RoleName,
    cr.Description as RoleDescription,
    mr.StartDate,
    mr.EndDate,
    mr.IsActive,
    mr.AssignedBy,
    mr.AssignedAt,
    CASE 
        WHEN mr.IsActive = 0 THEN 'Inactive'
        WHEN mr.EndDate IS NOT NULL AND mr.EndDate < CURDATE() THEN 'Expired'
        WHEN mr.StartDate IS NOT NULL AND mr.StartDate > CURDATE() THEN 'Pending'
        ELSE 'Active'
    END as Status
FROM memberrole mr
JOIN churchmember cm ON mr.MbrID = cm.MbrID
JOIN churchrole cr ON mr.ChurchRoleID = cr.RoleID
WHERE cm.Deleted = 0;

-- View: Member permissions (with inheritance)
CREATE OR REPLACE VIEW v_member_permissions AS
SELECT DISTINCT
    mr.MbrID,
    p.PermissionID,
    p.PermissionName,
    p.PermissionDescription,
    pc.CategoryName as PermissionCategory,
    cr.RoleID,
    cr.RoleName,
    CASE WHEN rh.ParentRoleID IS NOT NULL THEN 1 ELSE 0 END as IsInherited
FROM memberrole mr
JOIN churchrole cr ON mr.ChurchRoleID = cr.RoleID
LEFT JOIN role_hierarchy rh ON cr.RoleID = rh.ChildRoleID
LEFT JOIN churchrole parent_role ON rh.ParentRoleID = parent_role.RoleID
JOIN rolepermission rp ON (rp.ChurchRoleID = cr.RoleID OR rp.ChurchRoleID = parent_role.RoleID)
JOIN permission p ON rp.PermissionID = p.PermissionID
LEFT JOIN permission_category pc ON p.CategoryID = pc.CategoryID
WHERE mr.IsActive = 1
AND (mr.StartDate IS NULL OR mr.StartDate <= CURDATE())
AND (mr.EndDate IS NULL OR mr.EndDate >= CURDATE())
AND cr.IsActive = 1
AND p.IsActive = 1;

-- View: Role permission summary
CREATE OR REPLACE VIEW v_role_permissions AS
SELECT 
    cr.RoleID,
    cr.RoleName,
    cr.Description,
    COUNT(DISTINCT rp.PermissionID) as DirectPermissionCount,
    COUNT(DISTINCT inherited.PermissionID) as InheritedPermissionCount,
    COUNT(DISTINCT COALESCE(rp.PermissionID, inherited.PermissionID)) as TotalPermissionCount
FROM churchrole cr
LEFT JOIN rolepermission rp ON cr.RoleID = rp.ChurchRoleID
LEFT JOIN role_hierarchy rh ON cr.RoleID = rh.ChildRoleID
LEFT JOIN rolepermission inherited ON rh.ParentRoleID = inherited.ChurchRoleID
WHERE cr.IsActive = 1
GROUP BY cr.RoleID, cr.RoleName, cr.Description;

-- ============================================================================
-- 14. STORED PROCEDURES
-- ============================================================================

DELIMITER //

-- Procedure: Get user permissions (with caching)
DROP PROCEDURE IF EXISTS sp_get_user_permissions//
CREATE PROCEDURE sp_get_user_permissions(IN p_member_id INT)
BEGIN
    -- Check cache first
    IF EXISTS (
        SELECT 1 FROM permission_cache 
        WHERE MbrID = p_member_id 
        AND (ExpiresAt IS NULL OR ExpiresAt > NOW())
        LIMIT 1
    ) THEN
        -- Return cached permissions
        SELECT DISTINCT PermissionName 
        FROM permission_cache 
        WHERE MbrID = p_member_id 
        AND (ExpiresAt IS NULL OR ExpiresAt > NOW());
    ELSE
        -- Rebuild cache and return
        DELETE FROM permission_cache WHERE MbrID = p_member_id;
        
        INSERT INTO permission_cache (MbrID, PermissionName, SourceRoleID, IsInherited, ExpiresAt)
        SELECT DISTINCT
            p_member_id,
            p.PermissionName,
            cr.RoleID,
            CASE WHEN rh.ParentRoleID IS NOT NULL THEN 1 ELSE 0 END,
            DATE_ADD(NOW(), INTERVAL 1 HOUR)
        FROM memberrole mr
        JOIN churchrole cr ON mr.ChurchRoleID = cr.RoleID
        LEFT JOIN role_hierarchy rh ON cr.RoleID = rh.ChildRoleID
        LEFT JOIN churchrole parent_role ON rh.ParentRoleID = parent_role.RoleID
        JOIN rolepermission rp ON (rp.ChurchRoleID = cr.RoleID OR rp.ChurchRoleID = parent_role.RoleID)
        JOIN permission p ON rp.PermissionID = p.PermissionID
        WHERE mr.MbrID = p_member_id
        AND mr.IsActive = 1
        AND (mr.StartDate IS NULL OR mr.StartDate <= CURDATE())
        AND (mr.EndDate IS NULL OR mr.EndDate >= CURDATE())
        AND cr.IsActive = 1
        AND p.IsActive = 1;
        
        SELECT DISTINCT PermissionName FROM permission_cache WHERE MbrID = p_member_id;
    END IF;
END//

-- Procedure: Invalidate permission cache
DROP PROCEDURE IF EXISTS sp_invalidate_permission_cache//
CREATE PROCEDURE sp_invalidate_permission_cache(IN p_member_id INT)
BEGIN
    DELETE FROM permission_cache WHERE MbrID = p_member_id;
END//

-- Procedure: Invalidate cache for role
DROP PROCEDURE IF EXISTS sp_invalidate_role_cache//
CREATE PROCEDURE sp_invalidate_role_cache(IN p_role_id INT)
BEGIN
    DELETE pc FROM permission_cache pc
    JOIN memberrole mr ON pc.MbrID = mr.MbrID
    WHERE mr.ChurchRoleID = p_role_id;
END//

DELIMITER ;

-- ============================================================================
-- 15. TRIGGERS FOR AUTOMATIC CACHE INVALIDATION
-- ============================================================================

DELIMITER //

-- Trigger: Invalidate cache when role permissions change
DROP TRIGGER IF EXISTS trg_rolepermission_after_insert//
CREATE TRIGGER trg_rolepermission_after_insert
AFTER INSERT ON rolepermission
FOR EACH ROW
BEGIN
    DELETE pc FROM permission_cache pc
    JOIN memberrole mr ON pc.MbrID = mr.MbrID
    WHERE mr.ChurchRoleID = NEW.ChurchRoleID;
END//

DROP TRIGGER IF EXISTS trg_rolepermission_after_delete//
CREATE TRIGGER trg_rolepermission_after_delete
AFTER DELETE ON rolepermission
FOR EACH ROW
BEGIN
    DELETE pc FROM permission_cache pc
    JOIN memberrole mr ON pc.MbrID = mr.MbrID
    WHERE mr.ChurchRoleID = OLD.ChurchRoleID;
END//

-- Trigger: Invalidate cache when member role changes
DROP TRIGGER IF EXISTS trg_memberrole_after_insert//
CREATE TRIGGER trg_memberrole_after_insert
AFTER INSERT ON memberrole
FOR EACH ROW
BEGIN
    DELETE FROM permission_cache WHERE MbrID = NEW.MbrID;
END//

DROP TRIGGER IF EXISTS trg_memberrole_after_update//
CREATE TRIGGER trg_memberrole_after_update
AFTER UPDATE ON memberrole
FOR EACH ROW
BEGIN
    DELETE FROM permission_cache WHERE MbrID = NEW.MbrID;
END//

DROP TRIGGER IF EXISTS trg_memberrole_after_delete//
CREATE TRIGGER trg_memberrole_after_delete
AFTER DELETE ON memberrole
FOR EACH ROW
BEGIN
    DELETE FROM permission_cache WHERE MbrID = OLD.MbrID;
END//

DELIMITER ;

-- ============================================================================
-- COMMIT TRANSACTION
-- ============================================================================

COMMIT;

-- ============================================================================
-- VERIFICATION QUERIES
-- ============================================================================

-- Verify tables exist
SELECT 'Tables created successfully' as Status;
SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME IN ('permission_category', 'permission_cache', 'permission_group', 'permission_audit')
ORDER BY TABLE_NAME;

-- Verify permissions
SELECT 'Permissions seeded' as Status;
SELECT COUNT(*) as PermissionCount FROM permission;

-- Verify roles
SELECT 'Roles seeded' as Status;
SELECT RoleName, DisplayOrder FROM churchrole ORDER BY DisplayOrder;

-- Verify role hierarchy
SELECT 'Role hierarchy configured' as Status;
SELECT 
    c.RoleName as ChildRole,
    p.RoleName as ParentRole,
    rh.InheritanceLevel
FROM role_hierarchy rh
JOIN churchrole c ON rh.ChildRoleID = c.RoleID
JOIN churchrole p ON rh.ParentRoleID = p.RoleID
ORDER BY rh.InheritanceLevel, c.DisplayOrder;

-- Verify views
SELECT 'Views created' as Status;
SHOW FULL TABLES WHERE Table_type = 'VIEW';

-- Verify stored procedures
SELECT 'Stored procedures created' as Status;
SHOW PROCEDURE STATUS WHERE Db = DATABASE();

-- Verify triggers
SELECT 'Triggers created' as Status;
SHOW TRIGGERS;

SELECT '✅ RBAC System Installation Complete!' as Status;
