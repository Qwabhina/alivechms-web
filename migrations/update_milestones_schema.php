<?php
// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load environment
if (class_exists('Dotenv\Dotenv')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
}

require_once __DIR__ . '/../core/Helpers.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/ORM.php';

try {
    $db = Database::getInstance()->getConnection();

    echo "Updating milestone_type table...\n";

    // Check if Description column exists
    $stmt = $db->query("SHOW COLUMNS FROM milestone_type LIKE 'Description'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE milestone_type ADD COLUMN Description TEXT NULL AFTER TypeName");
        echo "Added Description column.\n";
    }

    // Check if Icon column exists
    $stmt = $db->query("SHOW COLUMNS FROM milestone_type LIKE 'Icon'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE milestone_type ADD COLUMN Icon VARCHAR(50) NULL AFTER Description");
        echo "Added Icon column.\n";
    }

    // Check if Color column exists
    $stmt = $db->query("SHOW COLUMNS FROM milestone_type LIKE 'Color'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE milestone_type ADD COLUMN Color VARCHAR(20) NULL AFTER Icon");
        echo "Added Color column.\n";
    }

    // Check if CreatedAt column exists
    $stmt = $db->query("SHOW COLUMNS FROM milestone_type LIKE 'CreatedAt'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE milestone_type ADD COLUMN CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
        echo "Added CreatedAt column.\n";
    }

    // Check if IsActive column exists
    $stmt = $db->query("SHOW COLUMNS FROM milestone_type LIKE 'IsActive'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE milestone_type ADD COLUMN IsActive TINYINT(1) DEFAULT 1");
        echo "Added IsActive column.\n";
    }

    echo "Updating member_milestone table...\n";

    // Check if Location column exists
    $stmt = $db->query("SHOW COLUMNS FROM member_milestone LIKE 'Location'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE member_milestone ADD COLUMN Location VARCHAR(200) NULL AFTER MilestoneDate");
        echo "Added Location column.\n";
    }

    // Check if OfficiatingPastor column exists
    $stmt = $db->query("SHOW COLUMNS FROM member_milestone LIKE 'OfficiatingPastor'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE member_milestone ADD COLUMN OfficiatingPastor VARCHAR(150) NULL AFTER Location");
        echo "Added OfficiatingPastor column.\n";
    }

    // Check if CertificateNumber column exists
    $stmt = $db->query("SHOW COLUMNS FROM member_milestone LIKE 'CertificateNumber'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE member_milestone ADD COLUMN CertificateNumber VARCHAR(100) NULL AFTER OfficiatingPastor");
        echo "Added CertificateNumber column.\n";
    }

    // Check if Notes column exists
    $stmt = $db->query("SHOW COLUMNS FROM member_milestone LIKE 'Notes'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE member_milestone ADD COLUMN Notes TEXT NULL AFTER CertificateNumber");
        echo "Added Notes column.\n";
    }

    // Check if RecordedAt column exists
    $stmt = $db->query("SHOW COLUMNS FROM member_milestone LIKE 'RecordedAt'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE member_milestone ADD COLUMN RecordedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
        echo "Added RecordedAt column.\n";
    }

    // Check if Deleted column exists
    $stmt = $db->query("SHOW COLUMNS FROM member_milestone LIKE 'Deleted'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE member_milestone ADD COLUMN Deleted TINYINT(1) DEFAULT 0");
        echo "Added Deleted column.\n";
    }

    // Check if RecordedBy column exists
    $stmt = $db->query("SHOW COLUMNS FROM member_milestone LIKE 'RecordedBy'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE member_milestone ADD COLUMN RecordedBy INT(11) NULL");
        echo "Added RecordedBy column.\n";
    }

    echo "Schema update completed successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
