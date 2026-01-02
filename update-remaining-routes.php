<?php

/**
 * Route Update Helper Script
 * 
 * This script helps identify and update the remaining route files
 * to use the new ResponseHelper standardization.
 */

declare(strict_types=1);

$routeFiles = [
    'BudgetRoutes.php',
    'ContributionRoutes.php',
    'EventRoutes.php', 
    'ExpenseCategoryRoutes.php',
    'ExpenseRoutes.php',
    'FinanceRoutes.php',
    'FiscalYearRoutes.php',
    'GroupRoutes.php',
    'MembershipTypeRoutes.php',
    'PledgeRoutes.php',
    'PublicRoutes.php',
    'RoleRoutes.php',
    'SettingsRoutes.php',
    'VolunteerRoutes.php'
];

echo "🔍 Analyzing remaining route files for response patterns...\n\n";

foreach ($routeFiles as $file) {
    $filePath = __DIR__ . '/routes/' . $file;
    
    if (!file_exists($filePath)) {
        echo "❌ File not found: $file\n";
        continue;
    }
    
    $content = file_get_contents($filePath);
    
    // Check for old response patterns
    $patterns = [
        'self::success(' => 'ResponseHelper::success(',
        'self::error(' => 'ResponseHelper::error(',
        'self::paginated(' => 'ResponseHelper::paginated(',
        'Helpers::sendError(' => 'ResponseHelper::error(',
        'Helpers::sendSuccess(' => 'ResponseHelper::success(',
        'Helpers::sendFeedback(' => 'ResponseHelper::sendFeedback('
    ];
    
    $needsUpdate = false;
    $foundPatterns = [];
    
    foreach ($patterns as $old => $new) {
        if (strpos($content, $old) !== false) {
            $needsUpdate = true;
            $count = substr_count($content, $old);
            $foundPatterns[] = "$old ($count occurrences)";
        }
    }
    
    if ($needsUpdate) {
        echo "🔄 $file needs updating:\n";
        foreach ($foundPatterns as $pattern) {
            echo "   - $pattern\n";
        }
        
        // Check if ResponseHelper is already imported
        if (strpos($content, "require_once __DIR__ . '/../core/ResponseHelper.php';") === false) {
            echo "   - ⚠️  Needs ResponseHelper import\n";
        }
        
        echo "\n";
    } else {
        echo "✅ $file is already updated\n";
    }
}

echo "\n📋 Summary:\n";
echo "- Files analyzed: " . count($routeFiles) . "\n";
echo "- Use this information to systematically update each file\n";
echo "- Remember to add ResponseHelper import to each file\n";
echo "- Test each file after updating\n\n";

echo "🔧 Quick update commands for common patterns:\n";
echo "1. Add import: require_once __DIR__ . '/../core/ResponseHelper.php';\n";
echo "2. Replace self::success( with ResponseHelper::success(\n";
echo "3. Replace self::error( with ResponseHelper::error(\n";
echo "4. Replace self::paginated( with ResponseHelper::paginated(\n";
echo "5. Replace fallback errors with ResponseHelper::notFound()\n";