-- ============================================================================
-- Migration: Recreate Missing Stored Procedures and Functions
-- ============================================================================
-- Date:    2026-04-14
-- Purpose: Rebuild stored routines that were missing from SQL dump
--          Based on analysis of codebase references
-- ============================================================================

-- Start transaction for safe execution
START TRANSACTION;

-- ============================================================================
-- 1. Function: fn_generate_member_unique_id
-- ============================================================================
-- Purpose: Generate a unique member ID based on registration date
-- Format: MBRXXYY-DDDD (XX=month, YY=year, DDDD=4-digit sequential number)
-- Used by: trg_member_before_insert trigger on churchmember table
-- ============================================================================

DELIMITER $$

DROP FUNCTION IF EXISTS `fn_generate_member_unique_id`$$

CREATE FUNCTION `fn_generate_member_unique_id`(
    p_registration_date DATE
) RETURNS VARCHAR(20)
DETERMINISTIC
BEGIN
    DECLARE v_year VARCHAR(2);
    DECLARE v_month VARCHAR(2);
    DECLARE v_prefix VARCHAR(6);
    DECLARE v_serial INT DEFAULT 0;
    DECLARE v_unique_id VARCHAR(20) DEFAULT NULL;
    DECLARE v_existing_id VARCHAR(20) DEFAULT NULL;
    
    -- Extract month and year from registration date
    SET v_month = LPAD(MONTH(p_registration_date), 2, '0');
    SET v_year = RIGHT(YEAR(p_registration_date), 2);
    SET v_prefix = CONCAT('MBR', v_month, v_year);
    
    -- Find the highest existing serial number system-wide
    SELECT MAX(CAST(SUBSTRING(MbrUniqueID, 8, 4) AS UNSIGNED))
    INTO v_serial
    FROM churchmember
    WHERE MbrUniqueID LIKE 'MBR%';
    
    -- If no existing IDs found, start from 0 (will be incremented to 1)
    SET v_serial = IFNULL(v_serial, 0);
    
    -- Generate the new ID with incremented serial
    SET v_unique_id = CONCAT(v_prefix, '-', LPAD(v_serial + 1, 4, '0'));
    
    RETURN v_unique_id;
END$$

DELIMITER ;

-- ============================================================================
-- 2. Procedure: sp_get_user_permissions
-- ============================================================================
-- Purpose: Retrieve all permission names for a specific user
-- Used by: RBACRepository.php getUserPermissions() method
-- Returns: List of permission names (e.g., 'members.view', 'finances.edit')
-- ============================================================================

DELIMITER $$

DROP PROCEDURE IF EXISTS `sp_get_user_permissions`$$

CREATE PROCEDURE `sp_get_user_permissions`(
    IN p_user_id INT
)
BEGIN
    SELECT DISTINCT p.PermissionName
    FROM permission p
    INNER JOIN role_permission rp ON p.PermissionID = rp.PermissionID
    INNER JOIN church_role cr ON rp.RoleID = cr.RoleID AND cr.IsActive = 1
    INNER JOIN member_role mr ON cr.RoleID = mr.RoleID 
        AND mr.MbrID = p_user_id 
        AND mr.IsActive = 1
        AND (mr.StartDate IS NULL OR mr.StartDate <= CURDATE())
        AND (mr.EndDate IS NULL OR mr.EndDate >= CURDATE())
    WHERE p.IsActive = 1
    ORDER BY p.PermissionName;
END$$

DELIMITER ;

-- ============================================================================
-- Verify the routines were created
-- ============================================================================

-- Check that the function exists
SELECT ROUTINE_NAME, ROUTINE_TYPE 
FROM INFORMATION_SCHEMA.ROUTINES 
WHERE ROUTINE_SCHEMA = DATABASE() 
AND ROUTINE_NAME IN ('fn_generate_member_unique_id', 'sp_get_user_permissions');

-- Commit the changes
COMMIT;

-- ============================================================================
-- Verification queries (can be run separately)
-- ============================================================================
-- Test the function:
-- SELECT fn_generate_member_unique_id('2026-04-14');
-- 
-- Test the procedure:
-- CALL sp_get_user_permissions(1);
-- 
-- View all routines in database:
-- SHOW FUNCTION STATUS WHERE Db = DATABASE();
-- SHOW PROCEDURE STATUS WHERE Db = DATABASE();
-- ============================================================================
