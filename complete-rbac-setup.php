<?php

/**
 * Complete RBAC Setup
 * Finishes the RBAC migration by setting up hierarchy and permissions
 */

declare(strict_types=1);

// Load environment
if (file_exists(__DIR__ . '/.env')) {
   $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
   foreach ($lines as $line) {
      if (strpos(trim($line), '#') === 0) continue;
      if (strpos($line, '=') === false) continue;
      list($key, $value) = explode('=', $line, 2);
      $_ENV[trim($key)] = trim($value);
   }
}

echo "=================================================================\n";
echo "COMPLETING RBAC SETUP\n";
echo "=================================================================\n\n";

try {
   $db = new PDO('mysql:host=localhost;dbname=alivechms', $_ENV['DB_USER'] ?? 'root', $_ENV['DB_PASS'] ?? '');
   $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

   echo "1. Setting up role hierarchy...\n";

   // Clear existing hierarchy
   $db->exec("DELETE FROM role_hierarchy");

   // Set up hierarchy using the new system roles
   // Super Admin (12) > Admin (13) > Pastor (14) > Elder (15) > Member (19)
   $hierarchies = [
      ['child' => 13, 'parent' => 12, 'level' => 1], // Admin inherits from Super Admin
      ['child' => 14, 'parent' => 13, 'level' => 1], // Pastor inherits from Admin
      ['child' => 15, 'parent' => 14, 'level' => 1], // Elder inherits from Pastor
      ['child' => 16, 'parent' => 13, 'level' => 1], // Treasurer inherits from Admin
      ['child' => 17, 'parent' => 13, 'level' => 1], // Secretary inherits from Admin
      ['child' => 18, 'parent' => 19, 'level' => 1], // Group Leader inherits from Member
      ['child' => 19, 'parent' => 20, 'level' => 1], // Member inherits from Guest
   ];

   $stmt = $db->prepare("INSERT INTO role_hierarchy (ChildRoleID, ParentRoleID, InheritanceLevel) VALUES (?, ?, ?)");
   foreach ($hierarchies as $h) {
      $stmt->execute([$h['child'], $h['parent'], $h['level']]);
   }

   echo "  ✓ Role hierarchy configured (" . count($hierarchies) . " relationships)\n\n";

   echo "2. Assigning permissions to Super Admin...\n";

   // Super Admin gets ALL permissions
   $permissions = $db->query("SELECT PermissionID FROM permission WHERE IsActive = 1")->fetchAll(PDO::FETCH_COLUMN);

   // Clear existing Super Admin permissions
   $db->exec("DELETE FROM rolepermission WHERE ChurchRoleID = 12");

   $stmt = $db->prepare("INSERT IGNORE INTO rolepermission (ChurchRoleID, PermissionID) VALUES (12, ?)");
   foreach ($permissions as $permId) {
      $stmt->execute([$permId]);
   }

   echo "  ✓ Super Admin assigned " . count($permissions) . " permissions\n\n";

   echo "3. Assigning permissions to Admin...\n";

   // Admin gets most permissions (exclude manage_users)
   $adminPerms = $db->query("
        SELECT PermissionID FROM permission 
        WHERE IsActive = 1 
        AND PermissionName NOT IN ('manage_users')
    ")->fetchAll(PDO::FETCH_COLUMN);

   $db->exec("DELETE FROM rolepermission WHERE ChurchRoleID = 13");

   $stmt = $db->prepare("INSERT IGNORE INTO rolepermission (ChurchRoleID, PermissionID) VALUES (13, ?)");
   foreach ($adminPerms as $permId) {
      $stmt->execute([$permId]);
   }

   echo "  ✓ Admin assigned " . count($adminPerms) . " permissions\n\n";

   echo "4. Assigning permissions to Pastor...\n";

   // Pastor permissions
   $pastorPermNames = [
      'view_members',
      'create_members',
      'edit_members',
      'manage_families',
      'view_member_reports',
      'view_contribution',
      'view_expenses',
      'view_financial_reports',
      'view_events',
      'create_events',
      'manage_events',
      'record_attendance',
      'view_attendance_reports',
      'manage_volunteers',
      'view_groups',
      'create_groups',
      'manage_groups',
      'assign_group_members',
      'send_messages',
      'send_bulk_messages',
      'view_message_history',
      'view_dashboard',
      'view_analytics'
   ];

   $placeholders = str_repeat('?,', count($pastorPermNames) - 1) . '?';
   $pastorPerms = $db->prepare("SELECT PermissionID FROM permission WHERE PermissionName IN ($placeholders)");
   $pastorPerms->execute($pastorPermNames);
   $pastorPermIds = $pastorPerms->fetchAll(PDO::FETCH_COLUMN);

   $db->exec("DELETE FROM rolepermission WHERE ChurchRoleID = 14");

   $stmt = $db->prepare("INSERT IGNORE INTO rolepermission (ChurchRoleID, PermissionID) VALUES (14, ?)");
   foreach ($pastorPermIds as $permId) {
      $stmt->execute([$permId]);
   }

   echo "  ✓ Pastor assigned " . count($pastorPermIds) . " permissions\n\n";

   echo "5. Assigning permissions to Treasurer...\n";

   // Treasurer permissions
   $treasurerPermNames = [
      'view_members',
      'view_contribution',
      'create_contribution',
      'edit_contribution',
      'view_expenses',
      'create_expense',
      'approve_expenses',
      'manage_budgets',
      'approve_budgets',
      'view_financial_reports',
      'manage_fiscal_years',
      'view_dashboard',
      'export_reports'
   ];

   $placeholders = str_repeat('?,', count($treasurerPermNames) - 1) . '?';
   $treasurerPerms = $db->prepare("SELECT PermissionID FROM permission WHERE PermissionName IN ($placeholders)");
   $treasurerPerms->execute($treasurerPermNames);
   $treasurerPermIds = $treasurerPerms->fetchAll(PDO::FETCH_COLUMN);

   $db->exec("DELETE FROM rolepermission WHERE ChurchRoleID = 16");

   $stmt = $db->prepare("INSERT IGNORE INTO rolepermission (ChurchRoleID, PermissionID) VALUES (16, ?)");
   foreach ($treasurerPermIds as $permId) {
      $stmt->execute([$permId]);
   }

   echo "  ✓ Treasurer assigned " . count($treasurerPermIds) . " permissions\n\n";

   echo "6. Assigning permissions to Secretary...\n";

   // Secretary permissions
   $secretaryPermNames = [
      'view_members',
      'create_members',
      'edit_members',
      'manage_families',
      'view_events',
      'create_events',
      'manage_events',
      'record_attendance',
      'view_groups',
      'send_messages',
      'view_message_history',
      'view_dashboard'
   ];

   $placeholders = str_repeat('?,', count($secretaryPermNames) - 1) . '?';
   $secretaryPerms = $db->prepare("SELECT PermissionID FROM permission WHERE PermissionName IN ($placeholders)");
   $secretaryPerms->execute($secretaryPermNames);
   $secretaryPermIds = $secretaryPerms->fetchAll(PDO::FETCH_COLUMN);

   $db->exec("DELETE FROM rolepermission WHERE ChurchRoleID = 17");

   $stmt = $db->prepare("INSERT IGNORE INTO rolepermission (ChurchRoleID, PermissionID) VALUES (17, ?)");
   foreach ($secretaryPermIds as $permId) {
      $stmt->execute([$permId]);
   }

   echo "  ✓ Secretary assigned " . count($secretaryPermIds) . " permissions\n\n";

   echo "7. Assigning permissions to Member...\n";

   // Member permissions (basic access)
   $memberPermNames = ['view_events', 'view_groups', 'view_dashboard'];

   $memberPerms = $db->prepare("SELECT PermissionID FROM permission WHERE PermissionName IN (?, ?, ?)");
   $memberPerms->execute($memberPermNames);
   $memberPermIds = $memberPerms->fetchAll(PDO::FETCH_COLUMN);

   $db->exec("DELETE FROM rolepermission WHERE ChurchRoleID = 19");

   $stmt = $db->prepare("INSERT IGNORE INTO rolepermission (ChurchRoleID, PermissionID) VALUES (19, ?)");
   foreach ($memberPermIds as $permId) {
      $stmt->execute([$permId]);
   }

   echo "  ✓ Member assigned " . count($memberPermIds) . " permissions\n\n";

   echo "8. Creating views...\n";

   // Create views
   $db->exec("
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
        WHERE cm.Deleted = 0
    ");

   $db->exec("
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
        AND p.IsActive = 1
    ");

   echo "  ✓ Views created\n\n";

   echo "9. Creating stored procedures...\n";

   $db->exec("DROP PROCEDURE IF EXISTS sp_get_user_permissions");
   $db->exec("
        CREATE PROCEDURE sp_get_user_permissions(IN p_member_id INT)
        BEGIN
            IF EXISTS (
                SELECT 1 FROM permission_cache 
                WHERE MbrID = p_member_id 
                AND (ExpiresAt IS NULL OR ExpiresAt > NOW())
                LIMIT 1
            ) THEN
                SELECT DISTINCT PermissionName 
                FROM permission_cache 
                WHERE MbrID = p_member_id 
                AND (ExpiresAt IS NULL OR ExpiresAt > NOW());
            ELSE
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
        END
    ");

   $db->exec("DROP PROCEDURE IF EXISTS sp_invalidate_permission_cache");
   $db->exec("
        CREATE PROCEDURE sp_invalidate_permission_cache(IN p_member_id INT)
        BEGIN
            DELETE FROM permission_cache WHERE MbrID = p_member_id;
        END
    ");

   $db->exec("DROP PROCEDURE IF EXISTS sp_invalidate_role_cache");
   $db->exec("
        CREATE PROCEDURE sp_invalidate_role_cache(IN p_role_id INT)
        BEGIN
            DELETE pc FROM permission_cache pc
            JOIN memberrole mr ON pc.MbrID = mr.MbrID
            WHERE mr.ChurchRoleID = p_role_id;
        END
    ");

   echo "  ✓ Stored procedures created\n\n";

   echo "10. Creating triggers...\n";

   $db->exec("DROP TRIGGER IF EXISTS trg_rolepermission_after_insert");
   $db->exec("
        CREATE TRIGGER trg_rolepermission_after_insert
        AFTER INSERT ON rolepermission
        FOR EACH ROW
        BEGIN
            DELETE pc FROM permission_cache pc
            JOIN memberrole mr ON pc.MbrID = mr.MbrID
            WHERE mr.ChurchRoleID = NEW.ChurchRoleID;
        END
    ");

   $db->exec("DROP TRIGGER IF EXISTS trg_rolepermission_after_delete");
   $db->exec("
        CREATE TRIGGER trg_rolepermission_after_delete
        AFTER DELETE ON rolepermission
        FOR EACH ROW
        BEGIN
            DELETE pc FROM permission_cache pc
            JOIN memberrole mr ON pc.MbrID = mr.MbrID
            WHERE mr.ChurchRoleID = OLD.ChurchRoleID;
        END
    ");

   $db->exec("DROP TRIGGER IF EXISTS trg_memberrole_after_insert");
   $db->exec("
        CREATE TRIGGER trg_memberrole_after_insert
        AFTER INSERT ON memberrole
        FOR EACH ROW
        BEGIN
            DELETE FROM permission_cache WHERE MbrID = NEW.MbrID;
        END
    ");

   $db->exec("DROP TRIGGER IF EXISTS trg_memberrole_after_update");
   $db->exec("
        CREATE TRIGGER trg_memberrole_after_update
        AFTER UPDATE ON memberrole
        FOR EACH ROW
        BEGIN
            DELETE FROM permission_cache WHERE MbrID = NEW.MbrID;
        END
    ");

   $db->exec("DROP TRIGGER IF EXISTS trg_memberrole_after_delete");
   $db->exec("
        CREATE TRIGGER trg_memberrole_after_delete
        AFTER DELETE ON memberrole
        FOR EACH ROW
        BEGIN
            DELETE FROM permission_cache WHERE MbrID = OLD.MbrID;
        END
    ");

   echo "  ✓ Triggers created\n\n";

   echo "=================================================================\n";
   echo "✅ RBAC SETUP COMPLETED SUCCESSFULLY!\n";
   echo "=================================================================\n\n";

   echo "Summary:\n";
   echo "  - Role hierarchy: 7 relationships\n";
   echo "  - Super Admin: " . count($permissions) . " permissions\n";
   echo "  - Admin: " . count($adminPerms) . " permissions\n";
   echo "  - Pastor: " . count($pastorPermIds) . " permissions\n";
   echo "  - Treasurer: " . count($treasurerPermIds) . " permissions\n";
   echo "  - Secretary: " . count($secretaryPermIds) . " permissions\n";
   echo "  - Member: " . count($memberPermIds) . " permissions\n";
   echo "  - Views: 2 created\n";
   echo "  - Stored procedures: 3 created\n";
   echo "  - Triggers: 5 created\n\n";

   echo "The comprehensive RBAC system is now active!\n";
} catch (PDOException $e) {
   echo "\n❌ DATABASE ERROR:\n";
   echo $e->getMessage() . "\n\n";
   exit(1);
} catch (Exception $e) {
   echo "\n❌ ERROR:\n";
   echo $e->getMessage() . "\n\n";
   exit(1);
}
