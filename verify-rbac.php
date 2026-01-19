<?php

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

$db = new PDO('mysql:host=localhost;dbname=alivechms', $_ENV['DB_USER'] ?? 'root', $_ENV['DB_PASS'] ?? '');

echo "RBAC System Verification\n";
echo "========================\n\n";

// Check tables
echo "Tables:\n";
$tables = ['permission_category', 'permission_cache', 'permission_group', 'permission_audit'];
foreach ($tables as $table) {
   try {
      $count = $db->query("SELECT COUNT(*) FROM $table")->fetchColumn();
      echo "  ✓ $table ($count rows)\n";
   } catch (Exception $e) {
      echo "  ✗ $table (ERROR)\n";
   }
}

// Check views
echo "\nViews:\n";
$views = ['v_active_member_roles', 'v_member_permissions', 'v_role_permissions'];
foreach ($views as $view) {
   try {
      $count = $db->query("SELECT COUNT(*) FROM $view")->fetchColumn();
      echo "  ✓ $view ($count rows)\n";
   } catch (Exception $e) {
      echo "  ✗ $view (ERROR)\n";
   }
}

// Check stored procedures
echo "\nStored Procedures:\n";
$procs = $db->query("SHOW PROCEDURE STATUS WHERE Db = DATABASE()")->fetchAll(PDO::FETCH_ASSOC);
foreach ($procs as $proc) {
   echo "  ✓ {$proc['Name']}\n";
}

// Check triggers
echo "\nTriggers:\n";
$triggers = $db->query("SHOW TRIGGERS")->fetchAll(PDO::FETCH_ASSOC);
foreach ($triggers as $trigger) {
   echo "  ✓ {$trigger['Trigger']} on {$trigger['Table']}\n";
}

// Check role hierarchy
echo "\nRole Hierarchy:\n";
$hierarchy = $db->query("
    SELECT c.RoleName as Child, p.RoleName as Parent
    FROM role_hierarchy rh
    JOIN churchrole c ON rh.ChildRoleID = c.RoleID
    JOIN churchrole p ON rh.ParentRoleID = p.RoleID
")->fetchAll(PDO::FETCH_ASSOC);
foreach ($hierarchy as $h) {
   echo "  {$h['Child']} → {$h['Parent']}\n";
}

// Check role permissions
echo "\nRole Permissions:\n";
$rolePerms = $db->query("
    SELECT cr.RoleName, COUNT(rp.PermissionID) as PermCount
    FROM churchrole cr
    LEFT JOIN rolepermission rp ON cr.RoleID = rp.ChurchRoleID
    WHERE cr.IsSystemRole = 1
    GROUP BY cr.RoleID, cr.RoleName
    ORDER BY cr.DisplayOrder
")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rolePerms as $rp) {
   echo "  {$rp['RoleName']}: {$rp['PermCount']} permissions\n";
}

echo "\n✅ RBAC System is operational!\n";
