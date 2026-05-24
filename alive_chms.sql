-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 26, 2026 at 12:40 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `alive_chms`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_get_user_permissions` (IN `p_user_id` INT)   BEGIN
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

--
-- Functions
--
CREATE DEFINER=`root`@`localhost` FUNCTION `fn_generate_member_unique_id` (`p_registration_date` DATE) RETURNS VARCHAR(20) CHARSET utf8mb4 COLLATE utf8mb4_general_ci DETERMINISTIC BEGIN
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

-- --------------------------------------------------------

--
-- Table structure for table `asset`
--

CREATE TABLE `asset` (
  `AssetID` int(11) NOT NULL,
  `AssetName` varchar(200) NOT NULL,
  `AssetDescription` text DEFAULT NULL,
  `AssetConditionID` int(11) NOT NULL,
  `AssetStatusID` int(11) NOT NULL,
  `AcquisitionDate` date NOT NULL,
  `AcquisitionType` varchar(50) DEFAULT NULL COMMENT 'Purchased, Donated, Leased',
  `AcquisitionValue` decimal(12,2) DEFAULT NULL,
  `CurrentValue` decimal(12,2) DEFAULT NULL,
  `DonorID` int(11) DEFAULT NULL,
  `SerialNumber` varchar(100) DEFAULT NULL,
  `Location` varchar(300) DEFAULT NULL COMMENT 'Specific location beyond branch',
  `AssignedTo` int(11) DEFAULT NULL COMMENT 'Person responsible',
  `BranchID` int(11) NOT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `asset_condition`
--

CREATE TABLE `asset_condition` (
  `ConditionID` int(11) NOT NULL,
  `ConditionName` varchar(50) NOT NULL,
  `DisplayOrder` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `asset_maintenance`
--

CREATE TABLE `asset_maintenance` (
  `MaintenanceID` int(11) NOT NULL,
  `AssetID` int(11) NOT NULL,
  `MaintenanceDate` date NOT NULL,
  `MaintenanceType` varchar(100) NOT NULL COMMENT 'Repair, Service, Inspection',
  `Description` text NOT NULL,
  `Cost` decimal(10,2) DEFAULT NULL,
  `PerformedBy` varchar(200) DEFAULT NULL,
  `NextMaintenanceDate` date DEFAULT NULL,
  `RecordedBy` int(11) NOT NULL,
  `RecordedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `asset_status`
--

CREATE TABLE `asset_status` (
  `StatusID` int(11) NOT NULL,
  `StatusName` varchar(50) NOT NULL,
  `DisplayOrder` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

CREATE TABLE `audit_log` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(11) DEFAULT NULL COMMENT 'Null for failed logins or system actions',
  `action` varchar(255) NOT NULL,
  `entity_type` varchar(100) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `changes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`changes`)),
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `branch`
--

CREATE TABLE `branch` (
  `BranchID` int(11) NOT NULL,
  `BranchName` varchar(200) NOT NULL,
  `BranchAddress` text DEFAULT NULL,
  `BranchPhoneNumber` varchar(20) DEFAULT NULL,
  `BranchEmailAddress` varchar(150) DEFAULT NULL,
  `BranchCode` varchar(20) DEFAULT NULL COMMENT 'Short code for branch identification',
  `TimeZone` varchar(50) DEFAULT 'UTC',
  `IsActive` tinyint(1) DEFAULT 1,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `budget_approval`
--

CREATE TABLE `budget_approval` (
  `ApprovalID` int(11) NOT NULL,
  `BudgetID` int(11) NOT NULL,
  `ApprovedBy` int(11) NOT NULL,
  `ApprovalStatus` varchar(50) NOT NULL DEFAULT 'Pending',
  `ApprovalComments` text DEFAULT NULL,
  `ApprovedAt` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `budget_item`
--

CREATE TABLE `budget_item` (
  `ItemID` int(11) NOT NULL,
  `BudgetID` int(11) NOT NULL,
  `ItemName` varchar(200) NOT NULL,
  `Amount` decimal(12,2) NOT NULL,
  `CategoryType` varchar(20) NOT NULL,
  `SubcategoryID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `budget_item_category`
--

CREATE TABLE `budget_item_category` (
  `SubcategoryID` int(11) NOT NULL,
  `CategoryType` varchar(20) NOT NULL COMMENT 'Income or Expense',
  `SubcategoryName` varchar(100) NOT NULL,
  `IsActive` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `churchmember`
--

CREATE TABLE `churchmember` (
  `MbrID` int(11) NOT NULL,
  `MbrUniqueID` varchar(20) DEFAULT NULL COMMENT 'Format: MBRXXYY-DDDD (XX=month, YY=year, DDDD=serial)',
  `MbrFirstName` varchar(100) NOT NULL,
  `MbrFamilyName` varchar(100) NOT NULL,
  `MbrOtherNames` varchar(150) DEFAULT NULL,
  `MbrGender` varchar(20) DEFAULT NULL COMMENT 'Flexible to allow cultural variations',
  `MbrEmailAddress` varchar(150) DEFAULT NULL,
  `MbrResidentialAddress` text DEFAULT NULL,
  `MbrDateOfBirth` date DEFAULT NULL,
  `MbrOccupation` varchar(150) DEFAULT NULL,
  `MbrMaritalStatusID` int(11) DEFAULT NULL,
  `MbrEducationLevelID` int(11) DEFAULT NULL,
  `MbrProfilePicture` varchar(500) DEFAULT NULL,
  `MbrRegistrationDate` date NOT NULL,
  `MbrMembershipStatusID` int(11) NOT NULL,
  `BranchID` int(11) NOT NULL,
  `FamilyID` int(11) DEFAULT NULL,
  `Deleted` tinyint(1) DEFAULT 0,
  `DeletedAt` timestamp NULL DEFAULT NULL,
  `DeletedBy` int(11) DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Triggers `churchmember`
--
DELIMITER $$
CREATE TRIGGER `trg_member_before_insert` BEFORE INSERT ON `churchmember` FOR EACH ROW BEGIN
  IF NEW.MbrUniqueID IS NULL THEN
    SET NEW.MbrUniqueID = fn_generate_member_unique_id(NEW.MbrRegistrationDate);
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `church_budget`
--

CREATE TABLE `church_budget` (
  `BudgetID` int(11) NOT NULL,
  `BudgetTitle` varchar(300) NOT NULL,
  `TotalAmount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `BudgetSummary` text DEFAULT NULL,
  `BudgetStatus` varchar(50) DEFAULT 'Draft',
  `FiscalYearID` int(11) NOT NULL,
  `BranchID` int(11) DEFAULT NULL,
  `CreatedBy` int(11) NOT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `church_event`
--

CREATE TABLE `church_event` (
  `EventID` int(11) NOT NULL,
  `EventName` varchar(200) NOT NULL,
  `EventDescription` text DEFAULT NULL,
  `EventDateTime` datetime NOT NULL,
  `EndDateTime` datetime DEFAULT NULL,
  `Location` varchar(300) DEFAULT NULL,
  `RecurrencePattern` varchar(100) DEFAULT NULL COMMENT 'Daily, Weekly, Monthly, etc',
  `RecurrenceEndDate` date DEFAULT NULL,
  `MaxAttendees` int(11) DEFAULT NULL,
  `RequiresRegistration` tinyint(1) DEFAULT 0,
  `BranchID` int(11) DEFAULT NULL,
  `CreatedBy` int(11) NOT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `church_group`
--

CREATE TABLE `church_group` (
  `GroupID` int(11) NOT NULL,
  `GroupName` varchar(200) NOT NULL,
  `GroupLeaderID` int(11) DEFAULT NULL,
  `GroupDescription` text DEFAULT NULL,
  `GroupTypeID` int(11) NOT NULL,
  `BranchID` int(11) DEFAULT NULL,
  `MeetingSchedule` varchar(200) DEFAULT NULL,
  `IsActive` tinyint(1) DEFAULT 1,
  `Deleted` tinyint(1) DEFAULT 0,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `church_role`
--

CREATE TABLE `church_role` (
  `RoleID` int(11) NOT NULL,
  `RoleName` varchar(100) NOT NULL,
  `RoleDescription` text DEFAULT NULL,
  `IsSystemRole` tinyint(1) DEFAULT 0 COMMENT 'System roles cannot be deleted',
  `IsActive` tinyint(1) DEFAULT 1,
  `DisplayOrder` int(11) DEFAULT 0,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `communication`
--

CREATE TABLE `communication` (
  `CommID` int(11) NOT NULL,
  `Title` varchar(300) NOT NULL,
  `Message` text NOT NULL,
  `SentBy` int(11) NOT NULL,
  `TargetType` varchar(50) NOT NULL COMMENT 'All, Branch, Group, Member, Custom',
  `TargetMemberID` int(11) DEFAULT NULL,
  `TargetGroupID` int(11) DEFAULT NULL,
  `TargetBranchID` int(11) DEFAULT NULL,
  `ChannelID` int(11) DEFAULT NULL,
  `StatusID` int(11) DEFAULT NULL,
  `ScheduledFor` datetime DEFAULT NULL,
  `SentAt` datetime DEFAULT NULL,
  `TotalRecipients` int(11) DEFAULT 0,
  `SuccessfulDeliveries` int(11) DEFAULT 0,
  `FailedDeliveries` int(11) DEFAULT 0,
  `ErrorMessage` text DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `communication_channel`
--

CREATE TABLE `communication_channel` (
  `ChannelID` int(11) NOT NULL,
  `ChannelName` varchar(50) NOT NULL,
  `IsActive` tinyint(1) DEFAULT 1,
  `DisplayOrder` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `communication_delivery`
--

CREATE TABLE `communication_delivery` (
  `DeliveryID` int(11) NOT NULL,
  `CommID` int(11) NOT NULL,
  `MbrID` int(11) NOT NULL,
  `ChannelID` int(11) DEFAULT NULL,
  `Status` varchar(50) DEFAULT 'Pending',
  `DeliveredAt` datetime DEFAULT NULL,
  `ReadAt` datetime DEFAULT NULL,
  `ErrorMessage` text DEFAULT NULL,
  `RetryCount` int(11) DEFAULT 0,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `communication_status`
--

CREATE TABLE `communication_status` (
  `StatusID` int(11) NOT NULL,
  `StatusName` varchar(50) NOT NULL,
  `DisplayOrder` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `communication_template`
--

CREATE TABLE `communication_template` (
  `TemplateID` int(11) NOT NULL,
  `TemplateName` varchar(200) NOT NULL,
  `Subject` varchar(300) DEFAULT NULL,
  `MessageBody` text NOT NULL,
  `TemplateType` varchar(50) NOT NULL COMMENT 'Email, SMS, InApp',
  `Variables` text DEFAULT NULL COMMENT 'JSON array of available variables',
  `CreatedBy` int(11) NOT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contribution`
--

CREATE TABLE `contribution` (
  `ContributionID` int(11) NOT NULL,
  `MbrID` int(11) NOT NULL,
  `ContributionAmount` decimal(12,2) NOT NULL,
  `ContributionDate` date NOT NULL,
  `ContributionTypeID` int(11) NOT NULL,
  `PaymentMethodID` int(11) NOT NULL,
  `PaymentReference` varchar(200) DEFAULT NULL,
  `ReceiptNumber` varchar(100) DEFAULT NULL,
  `Notes` text DEFAULT NULL,
  `FiscalYearID` int(11) DEFAULT NULL COMMENT 'Optional - for reporting',
  `BranchID` int(11) NOT NULL,
  `RecordedBy` int(11) NOT NULL,
  `RecordedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `Deleted` tinyint(1) DEFAULT 0,
  `DeletedAt` timestamp NULL DEFAULT NULL,
  `DeletedBy` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contribution_audit`
--

CREATE TABLE `contribution_audit` (
  `AuditID` int(11) NOT NULL,
  `ContributionID` int(11) NOT NULL,
  `ActionType` varchar(50) NOT NULL COMMENT 'Created, Updated, Deleted',
  `OldAmount` decimal(12,2) DEFAULT NULL,
  `NewAmount` decimal(12,2) DEFAULT NULL,
  `OldDate` date DEFAULT NULL,
  `NewDate` date DEFAULT NULL,
  `ChangedBy` int(11) NOT NULL,
  `ChangeReason` text DEFAULT NULL,
  `IPAddress` varchar(45) DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contribution_type`
--

CREATE TABLE `contribution_type` (
  `ContributionTypeID` int(11) NOT NULL,
  `ContributionTypeName` varchar(100) NOT NULL,
  `ContributionTypeDescription` text DEFAULT NULL,
  `IsActive` tinyint(1) DEFAULT 1,
  `IsTaxDeductible` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `document`
--

CREATE TABLE `document` (
  `DocumentID` int(11) NOT NULL,
  `DocumentName` varchar(300) NOT NULL,
  `DocumentDescription` text DEFAULT NULL,
  `CategoryID` int(11) DEFAULT NULL,
  `FileURL` varchar(500) NOT NULL,
  `FileType` varchar(50) DEFAULT NULL,
  `FileSize` bigint(20) DEFAULT NULL COMMENT 'Size in bytes',
  `RelatedToType` varchar(50) DEFAULT NULL COMMENT 'Member, Contribution, Expense, Asset',
  `RelatedToID` int(11) DEFAULT NULL,
  `BranchID` int(11) DEFAULT NULL,
  `UploadedBy` int(11) NOT NULL,
  `UploadedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `document_category`
--

CREATE TABLE `document_category` (
  `CategoryID` int(11) NOT NULL,
  `CategoryName` varchar(100) NOT NULL,
  `Description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `education_level`
--

CREATE TABLE `education_level` (
  `LevelID` int(11) NOT NULL,
  `LevelName` varchar(100) NOT NULL,
  `DisplayOrder` int(11) DEFAULT 0,
  `IsActive` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `event_attendance`
--

CREATE TABLE `event_attendance` (
  `AttendanceID` int(11) NOT NULL,
  `EventID` int(11) NOT NULL,
  `MbrID` int(11) NOT NULL,
  `CheckInTime` datetime DEFAULT NULL,
  `CheckOutTime` datetime DEFAULT NULL,
  `AttendedBy` varchar(200) DEFAULT NULL COMMENT 'For guest/non-member tracking',
  `Notes` text DEFAULT NULL,
  `RecordedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `event_volunteer`
--

CREATE TABLE `event_volunteer` (
  `AssignmentID` int(11) NOT NULL,
  `EventID` int(11) NOT NULL,
  `MbrID` int(11) NOT NULL,
  `VolunteerRoleID` int(11) NOT NULL,
  `AssignedBy` int(11) DEFAULT NULL,
  `AssignedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `Status` varchar(50) DEFAULT 'Assigned' COMMENT 'Assigned, Confirmed, Completed, Cancelled'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `expense`
--

CREATE TABLE `expense` (
  `ExpID` int(11) NOT NULL,
  `ExpTitle` varchar(100) NOT NULL,
  `ExpDescription` text NOT NULL,
  `ExpAmount` decimal(12,2) NOT NULL,
  `ExpDate` date NOT NULL,
  `ExpCategoryID` int(11) NOT NULL,
  `PaymentMethodID` int(11) DEFAULT NULL,
  `PaymentReference` varchar(200) DEFAULT NULL,
  `ReceiptNumber` varchar(100) DEFAULT NULL,
  `ReceiptImageURL` varchar(500) DEFAULT NULL,
  `VendorName` varchar(200) DEFAULT NULL,
  `ApprovalStatus` varchar(50) DEFAULT 'Pending',
  `FiscalYearID` int(11) DEFAULT NULL,
  `BranchID` int(11) NOT NULL,
  `RequestedBy` int(11) NOT NULL,
  `RequestedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `Deleted` tinyint(1) DEFAULT 0,
  `DeletedAt` timestamp NULL DEFAULT NULL,
  `DeletedBy` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `expense_approval`
--

CREATE TABLE `expense_approval` (
  `ApprovalID` int(11) NOT NULL,
  `ExpID` int(11) NOT NULL,
  `ApproverID` int(11) NOT NULL,
  `ApprovalStatus` varchar(50) NOT NULL,
  `ApprovalComments` text DEFAULT NULL,
  `ApprovedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `expense_audit`
--

CREATE TABLE `expense_audit` (
  `AuditID` int(11) NOT NULL,
  `ExpID` int(11) NOT NULL,
  `ActionType` varchar(50) NOT NULL,
  `OldAmount` decimal(12,2) DEFAULT NULL,
  `NewAmount` decimal(12,2) DEFAULT NULL,
  `OldDate` date DEFAULT NULL,
  `NewDate` date DEFAULT NULL,
  `ChangedBy` int(11) NOT NULL,
  `ChangeReason` text DEFAULT NULL,
  `IPAddress` varchar(45) DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `expense_category`
--

CREATE TABLE `expense_category` (
  `ExpCategoryID` int(11) NOT NULL,
  `CategoryName` varchar(100) NOT NULL,
  `CategoryDescription` text DEFAULT NULL,
  `ParentCategoryID` int(11) DEFAULT NULL COMMENT 'For sub-categories',
  `IsActive` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `family`
--

CREATE TABLE `family` (
  `FamilyID` int(11) NOT NULL,
  `FamilyName` varchar(150) NOT NULL,
  `HeadOfHouseholdID` int(11) DEFAULT NULL,
  `BranchID` int(11) NOT NULL,
  `FamilyAddress` text DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `family_member`
--

CREATE TABLE `family_member` (
  `FamilyMemberID` int(11) NOT NULL,
  `FamilyID` int(11) NOT NULL,
  `MbrID` int(11) NOT NULL,
  `RelationshipID` int(11) NOT NULL,
  `MarriageDate` date DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `family_relationship`
--

CREATE TABLE `family_relationship` (
  `RelationshipID` int(11) NOT NULL,
  `RelationshipName` varchar(50) NOT NULL,
  `DisplayOrder` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fiscal_year`
--

CREATE TABLE `fiscal_year` (
  `FiscalYearID` int(11) NOT NULL,
  `FiscalYearName` varchar(100) NOT NULL,
  `StartDate` date NOT NULL,
  `EndDate` date NOT NULL,
  `BranchID` int(11) NOT NULL,
  `Status` varchar(20) DEFAULT 'Active',
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `group_member`
--

CREATE TABLE `group_member` (
  `GroupMemberID` int(11) NOT NULL,
  `GroupID` int(11) NOT NULL,
  `MbrID` int(11) NOT NULL,
  `JoinedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `LeftAt` timestamp NULL DEFAULT NULL,
  `IsActive` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `group_type`
--

CREATE TABLE `group_type` (
  `GroupTypeID` int(11) NOT NULL,
  `GroupTypeName` varchar(100) NOT NULL,
  `GroupTypeDescription` text DEFAULT NULL,
  `IsActive` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `marital_status`
--

CREATE TABLE `marital_status` (
  `StatusID` int(11) NOT NULL,
  `StatusName` varchar(50) NOT NULL,
  `DisplayOrder` int(11) DEFAULT 0,
  `IsActive` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `membership_status`
--

CREATE TABLE `membership_status` (
  `StatusID` int(11) NOT NULL,
  `StatusName` varchar(50) NOT NULL,
  `StatusDescription` text DEFAULT NULL,
  `IsActive` tinyint(1) DEFAULT 1,
  `DisplayOrder` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `membership_type`
--

CREATE TABLE `membership_type` (
  `MshipTypeID` int(11) NOT NULL,
  `MshipTypeName` varchar(100) NOT NULL,
  `MshipTypeDescription` text DEFAULT NULL,
  `IsActive` tinyint(1) DEFAULT 1,
  `RequiresApproval` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `member_membership_type`
--

CREATE TABLE `member_membership_type` (
  `MemberMshipTypeID` int(11) NOT NULL,
  `MbrID` int(11) NOT NULL,
  `MshipTypeID` int(11) NOT NULL,
  `StartDate` date NOT NULL,
  `EndDate` date DEFAULT NULL,
  `IsActive` tinyint(1) DEFAULT 1,
  `ApprovedBy` int(11) DEFAULT NULL,
  `ApprovedAt` timestamp NULL DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `member_milestone`
--

CREATE TABLE `member_milestone` (
  `MilestoneID` int(11) NOT NULL,
  `MbrID` int(11) NOT NULL,
  `MilestoneTypeID` int(11) NOT NULL,
  `MilestoneDate` date NOT NULL,
  `Location` varchar(300) DEFAULT NULL,
  `OfficiatingPastor` varchar(200) DEFAULT NULL,
  `CertificateNumber` varchar(100) DEFAULT NULL,
  `Notes` text DEFAULT NULL,
  `RecordedBy` int(11) NOT NULL,
  `RecordedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `Deleted` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `member_phone`
--

CREATE TABLE `member_phone` (
  `PhoneID` int(11) NOT NULL,
  `MbrID` int(11) NOT NULL,
  `PhoneNumber` varchar(20) NOT NULL,
  `PhoneTypeID` int(11) DEFAULT NULL COMMENT 'Mobile, Home, Work, etc',
  `IsPrimary` tinyint(1) DEFAULT 0,
  `IsVerified` tinyint(1) DEFAULT 0,
  `VerifiedAt` timestamp NULL DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `member_role`
--

CREATE TABLE `member_role` (
  `MemberRoleID` int(11) NOT NULL,
  `MbrID` int(11) NOT NULL,
  `RoleID` int(11) NOT NULL,
  `StartDate` date DEFAULT NULL COMMENT 'When role assignment becomes active',
  `EndDate` date DEFAULT NULL COMMENT 'When role assignment expires',
  `IsActive` tinyint(1) DEFAULT 1 COMMENT 'Manually deactivate without deleting',
  `AssignedBy` int(11) DEFAULT NULL COMMENT 'Who assigned this role',
  `AssignedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `Notes` text DEFAULT NULL COMMENT 'Reason for assignment'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `member_volunteer_role`
--

CREATE TABLE `member_volunteer_role` (
  `MemberVolunteerRoleID` int(11) NOT NULL,
  `MbrID` int(11) NOT NULL COMMENT 'Member ID',
  `VolunteerRoleID` int(11) NOT NULL COMMENT 'Volunteer Role ID',
  `StartDate` date DEFAULT NULL COMMENT 'When assignment becomes active',
  `EndDate` date DEFAULT NULL COMMENT 'When assignment expires',
  `IsActive` tinyint(1) DEFAULT 1 COMMENT 'Active status',
  `AssignedBy` int(11) DEFAULT NULL COMMENT 'Who assigned this role',
  `AssignedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `Notes` text DEFAULT NULL COMMENT 'Assignment notes'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migration`
--

CREATE TABLE `migration` (
  `MigrationID` int(11) NOT NULL,
  `MigrationName` varchar(255) NOT NULL,
  `Batch` int(11) NOT NULL,
  `ExecutedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `milestone_type`
--

CREATE TABLE `milestone_type` (
  `MilestoneTypeID` int(11) NOT NULL,
  `TypeName` varchar(100) NOT NULL,
  `Description` text DEFAULT NULL,
  `Icon` varchar(50) DEFAULT NULL,
  `Color` varchar(20) DEFAULT NULL,
  `IsActive` tinyint(1) DEFAULT 1,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `TokenID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `TokenHash` varchar(255) NOT NULL,
  `ExpiresAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `IsUsed` tinyint(1) DEFAULT 0,
  `UsedAt` timestamp NULL DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_method`
--

CREATE TABLE `payment_method` (
  `PaymentMethodID` int(11) NOT NULL,
  `PaymentMethodName` varchar(100) NOT NULL,
  `PaymentMethodDescription` text DEFAULT NULL,
  `DisplayOrder` tinyint(2) NOT NULL DEFAULT 0,
  `IsActive` tinyint(1) DEFAULT 1,
  `RequiresReference` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permission`
--

CREATE TABLE `permission` (
  `PermissionID` int(11) NOT NULL,
  `PermissionName` varchar(100) NOT NULL COMMENT 'e.g., members.view, finances.edit',
  `CategoryID` int(11) DEFAULT NULL,
  `PermissionDescription` text DEFAULT NULL,
  `IsActive` tinyint(1) DEFAULT 1,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permission_audit`
--

CREATE TABLE `permission_audit` (
  `AuditID` int(11) NOT NULL,
  `ActionType` varchar(100) NOT NULL,
  `PerformedBy` int(11) NOT NULL,
  `TargetType` varchar(50) NOT NULL COMMENT 'Role, Permission, Member',
  `TargetID` int(11) NOT NULL,
  `OldValue` text DEFAULT NULL COMMENT 'Previous state (JSON)',
  `NewValue` text DEFAULT NULL COMMENT 'New state (JSON)',
  `IPAddress` varchar(45) DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permission_category`
--

CREATE TABLE `permission_category` (
  `CategoryID` int(11) NOT NULL,
  `CategoryName` varchar(100) NOT NULL,
  `CategoryDescription` text DEFAULT NULL,
  `DisplayOrder` int(11) DEFAULT 0,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `phone_type`
--

CREATE TABLE `phone_type` (
  `TypeID` int(11) NOT NULL,
  `TypeName` varchar(50) NOT NULL,
  `DisplayOrder` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pledge`
--

CREATE TABLE `pledge` (
  `PledgeID` int(11) NOT NULL,
  `MbrID` int(11) NOT NULL,
  `CampaignID` int(11) DEFAULT NULL,
  `PledgeTypeID` int(11) NOT NULL,
  `PledgeAmount` decimal(12,2) NOT NULL,
  `PledgeDate` date NOT NULL,
  `DueDate` date DEFAULT NULL,
  `PledgeFrequency` varchar(50) DEFAULT NULL COMMENT 'One-time, Weekly, Monthly, Quarterly, Yearly',
  `Status` varchar(50) DEFAULT 'Active',
  `Description` text DEFAULT NULL,
  `FiscalYearID` int(11) DEFAULT NULL,
  `CreatedBy` int(11) NOT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pledge_campaign`
--

CREATE TABLE `pledge_campaign` (
  `CampaignID` int(11) NOT NULL,
  `CampaignName` varchar(200) NOT NULL,
  `CampaignDescription` text DEFAULT NULL,
  `StartDate` date NOT NULL,
  `EndDate` date DEFAULT NULL,
  `TargetAmount` decimal(12,2) DEFAULT NULL,
  `BranchID` int(11) DEFAULT NULL,
  `IsActive` tinyint(1) DEFAULT 1,
  `CreatedBy` int(11) NOT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pledge_payment`
--

CREATE TABLE `pledge_payment` (
  `PaymentID` int(11) NOT NULL,
  `PledgeID` int(11) NOT NULL,
  `ContributionID` int(11) DEFAULT NULL COMMENT 'Link to actual contribution',
  `PaymentAmount` decimal(12,2) NOT NULL,
  `PaymentDate` date NOT NULL,
  `Notes` text DEFAULT NULL,
  `RecordedBy` int(11) NOT NULL,
  `RecordedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pledge_type`
--

CREATE TABLE `pledge_type` (
  `PledgeTypeID` int(11) NOT NULL,
  `PledgeTypeName` varchar(100) NOT NULL,
  `Description` text DEFAULT NULL,
  `IsActive` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `role_permission`
--

CREATE TABLE `role_permission` (
  `RolePermissionID` int(11) NOT NULL,
  `RoleID` int(11) NOT NULL,
  `PermissionID` int(11) NOT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sermon`
--

CREATE TABLE `sermon` (
  `SermonID` int(11) NOT NULL,
  `Title` varchar(300) NOT NULL,
  `Description` text DEFAULT NULL,
  `SermonDate` date NOT NULL,
  `Speaker` varchar(200) DEFAULT NULL,
  `BibleText` varchar(300) DEFAULT NULL,
  `EventID` int(11) DEFAULT NULL,
  `AudioURL` varchar(500) DEFAULT NULL,
  `VideoURL` varchar(500) DEFAULT NULL,
  `TranscriptURL` varchar(500) DEFAULT NULL,
  `ThumbnailURL` varchar(500) DEFAULT NULL,
  `Duration` int(11) DEFAULT NULL COMMENT 'Duration in seconds',
  `ViewCount` int(11) DEFAULT 0,
  `DownloadCount` int(11) DEFAULT 0,
  `UploadedBy` int(11) NOT NULL,
  `UploadedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_setting`
--

CREATE TABLE `system_setting` (
  `SettingID` int(11) NOT NULL,
  `SettingKey` varchar(150) NOT NULL,
  `SettingValue` text DEFAULT NULL,
  `SettingType` varchar(50) DEFAULT 'string' COMMENT 'string, number, boolean, json',
  `Category` varchar(100) DEFAULT NULL,
  `Description` text DEFAULT NULL,
  `IsEditable` tinyint(1) DEFAULT 1,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_authentication`
--

CREATE TABLE `user_authentication` (
  `UserID` int(11) NOT NULL,
  `MbrID` int(11) NOT NULL,
  `Username` varchar(100) NOT NULL,
  `Email` varchar(150) NOT NULL COMMENT 'Separate from member email for login',
  `PasswordHash` varchar(255) NOT NULL,
  `EmailVerified` tinyint(1) DEFAULT 0,
  `EmailVerifiedAt` timestamp NULL DEFAULT NULL,
  `IsActive` tinyint(1) DEFAULT 1,
  `IsLocked` tinyint(1) DEFAULT 0,
  `FailedLoginAttempts` int(11) DEFAULT 0,
  `LastLoginAt` timestamp NULL DEFAULT NULL,
  `LastLoginIP` varchar(45) DEFAULT NULL,
  `PasswordChangedAt` timestamp NULL DEFAULT NULL,
  `MustChangePassword` tinyint(1) DEFAULT 0,
  `TwoFactorEnabled` tinyint(1) DEFAULT 0,
  `TwoFactorSecret` varchar(255) DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `SessionID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `TokenHash` varchar(255) NOT NULL COMMENT 'Hashed refresh token',
  `DeviceInfo` text DEFAULT NULL,
  `IPAddress` varchar(45) DEFAULT NULL,
  `UserAgent` text DEFAULT NULL,
  `ExpiresAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `IsRevoked` tinyint(1) DEFAULT 0,
  `RevokedAt` timestamp NULL DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `visitor`
--

CREATE TABLE `visitor` (
  `VisitorID` int(11) NOT NULL,
  `FirstName` varchar(100) NOT NULL,
  `LastName` varchar(100) NOT NULL,
  `EmailAddress` varchar(150) DEFAULT NULL,
  `PhoneNumber` varchar(20) DEFAULT NULL,
  `Address` text DEFAULT NULL,
  `FirstVisitDate` date NOT NULL,
  `LastVisitDate` date DEFAULT NULL,
  `VisitCount` int(11) DEFAULT 1,
  `Source` varchar(100) DEFAULT NULL COMMENT 'How they heard about church',
  `InterestedInMembership` tinyint(1) DEFAULT 0,
  `AssignedFollowUpPerson` int(11) DEFAULT NULL,
  `BranchID` int(11) NOT NULL,
  `ConvertedToMemberID` int(11) DEFAULT NULL,
  `ConvertedAt` timestamp NULL DEFAULT NULL,
  `Notes` text DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `visitor_followup`
--

CREATE TABLE `visitor_followup` (
  `FollowUpID` int(11) NOT NULL,
  `VisitorID` int(11) NOT NULL,
  `FollowUpDate` date NOT NULL,
  `FollowUpType` varchar(100) NOT NULL COMMENT 'Call, Visit, Email, SMS',
  `Notes` text DEFAULT NULL,
  `NextFollowUpDate` date DEFAULT NULL,
  `PerformedBy` int(11) NOT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `visitor_visit`
--

CREATE TABLE `visitor_visit` (
  `VisitID` int(11) NOT NULL,
  `VisitorID` int(11) NOT NULL,
  `VisitDate` date NOT NULL,
  `EventID` int(11) DEFAULT NULL,
  `Notes` text DEFAULT NULL,
  `RecordedBy` int(11) NOT NULL,
  `RecordedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `volunteer_role`
--

CREATE TABLE `volunteer_role` (
  `VolunteerRoleID` int(11) NOT NULL,
  `RoleName` varchar(150) NOT NULL,
  `Description` text DEFAULT NULL,
  `IsActive` tinyint(1) DEFAULT 1,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_active_member_roles`
-- (See below for the actual view)
--
CREATE TABLE `v_active_member_roles` (
`MemberRoleID` int(11)
,`MbrID` int(11)
,`MbrFirstName` varchar(100)
,`MbrFamilyName` varchar(100)
,`RoleID` int(11)
,`RoleName` varchar(100)
,`RoleDescription` text
,`StartDate` date
,`EndDate` date
,`IsActive` tinyint(1)
,`AssignedBy` int(11)
,`AssignedAt` timestamp
,`Status` varchar(8)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_financial_summary`
-- (See below for the actual view)
--
CREATE TABLE `v_financial_summary` (
`BranchID` int(11)
,`BranchName` varchar(200)
,`Year` int(4)
,`Month` int(2)
,`UniqueContributors` bigint(21)
,`TotalContributions` bigint(21)
,`TotalIncome` decimal(34,2)
,`TotalExpenses` decimal(34,2)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_member_permissions`
-- (See below for the actual view)
--
CREATE TABLE `v_member_permissions` (
`MbrID` int(11)
,`PermissionID` int(11)
,`PermissionName` varchar(100)
,`PermissionDescription` text
,`PermissionCategory` varchar(100)
,`RoleID` int(11)
,`RoleName` varchar(100)
);

-- --------------------------------------------------------

--
-- Structure for view `v_active_member_roles`
--
DROP TABLE IF EXISTS `v_active_member_roles`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_active_member_roles`  AS SELECT `mr`.`MemberRoleID` AS `MemberRoleID`, `mr`.`MbrID` AS `MbrID`, `cm`.`MbrFirstName` AS `MbrFirstName`, `cm`.`MbrFamilyName` AS `MbrFamilyName`, `mr`.`RoleID` AS `RoleID`, `cr`.`RoleName` AS `RoleName`, `cr`.`RoleDescription` AS `RoleDescription`, `mr`.`StartDate` AS `StartDate`, `mr`.`EndDate` AS `EndDate`, `mr`.`IsActive` AS `IsActive`, `mr`.`AssignedBy` AS `AssignedBy`, `mr`.`AssignedAt` AS `AssignedAt`, CASE WHEN `mr`.`IsActive` = 0 THEN 'Inactive' WHEN `mr`.`EndDate` is not null AND `mr`.`EndDate` < curdate() THEN 'Expired' WHEN `mr`.`StartDate` is not null AND `mr`.`StartDate` > curdate() THEN 'Pending' ELSE 'Active' END AS `Status` FROM ((`member_role` `mr` join `churchmember` `cm` on(`mr`.`MbrID` = `cm`.`MbrID`)) join `church_role` `cr` on(`mr`.`RoleID` = `cr`.`RoleID`)) WHERE `cm`.`Deleted` = 0 ;

-- --------------------------------------------------------

--
-- Structure for view `v_financial_summary`
--
DROP TABLE IF EXISTS `v_financial_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_financial_summary`  AS SELECT `b`.`BranchID` AS `BranchID`, `b`.`BranchName` AS `BranchName`, year(`c`.`ContributionDate`) AS `Year`, month(`c`.`ContributionDate`) AS `Month`, count(distinct `c`.`MbrID`) AS `UniqueContributors`, count(`c`.`ContributionID`) AS `TotalContributions`, sum(`c`.`ContributionAmount`) AS `TotalIncome`, (select coalesce(sum(`e`.`ExpAmount`),0) from `expense` `e` where `e`.`BranchID` = `b`.`BranchID` and year(`e`.`ExpDate`) = year(`c`.`ContributionDate`) and month(`e`.`ExpDate`) = month(`c`.`ContributionDate`) and `e`.`Deleted` = 0) AS `TotalExpenses` FROM (`branch` `b` left join `contribution` `c` on(`b`.`BranchID` = `c`.`BranchID` and `c`.`Deleted` = 0)) WHERE `c`.`ContributionDate` is not null GROUP BY `b`.`BranchID`, `b`.`BranchName`, year(`c`.`ContributionDate`), month(`c`.`ContributionDate`) ;

-- --------------------------------------------------------

--
-- Structure for view `v_member_permissions`
--
DROP TABLE IF EXISTS `v_member_permissions`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_member_permissions`  AS SELECT DISTINCT `mr`.`MbrID` AS `MbrID`, `p`.`PermissionID` AS `PermissionID`, `p`.`PermissionName` AS `PermissionName`, `p`.`PermissionDescription` AS `PermissionDescription`, `pc`.`CategoryName` AS `PermissionCategory`, `cr`.`RoleID` AS `RoleID`, `cr`.`RoleName` AS `RoleName` FROM ((((`member_role` `mr` join `church_role` `cr` on(`mr`.`RoleID` = `cr`.`RoleID`)) join `role_permission` `rp` on(`cr`.`RoleID` = `rp`.`RoleID`)) join `permission` `p` on(`rp`.`PermissionID` = `p`.`PermissionID`)) left join `permission_category` `pc` on(`p`.`CategoryID` = `pc`.`CategoryID`)) WHERE `mr`.`IsActive` = 1 AND `cr`.`IsActive` = 1 AND `p`.`IsActive` = 1 AND (`mr`.`StartDate` is null OR `mr`.`StartDate` <= curdate()) AND (`mr`.`EndDate` is null OR `mr`.`EndDate` >= curdate()) ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `asset`
--
ALTER TABLE `asset`
  ADD PRIMARY KEY (`AssetID`),
  ADD KEY `idx_condition` (`AssetConditionID`),
  ADD KEY `idx_status` (`AssetStatusID`),
  ADD KEY `idx_branch` (`BranchID`),
  ADD KEY `idx_donor` (`DonorID`),
  ADD KEY `idx_assigned` (`AssignedTo`),
  ADD KEY `idx_serial` (`SerialNumber`);

--
-- Indexes for table `asset_condition`
--
ALTER TABLE `asset_condition`
  ADD PRIMARY KEY (`ConditionID`);

--
-- Indexes for table `asset_maintenance`
--
ALTER TABLE `asset_maintenance`
  ADD PRIMARY KEY (`MaintenanceID`),
  ADD KEY `idx_asset` (`AssetID`),
  ADD KEY `idx_date` (`MaintenanceDate`),
  ADD KEY `idx_next_date` (`NextMaintenanceDate`),
  ADD KEY `fk_maintenance_recorded_by` (`RecordedBy`);

--
-- Indexes for table `asset_status`
--
ALTER TABLE `asset_status`
  ADD PRIMARY KEY (`StatusID`);

--
-- Indexes for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_audit_user` (`user_id`),
  ADD KEY `idx_audit_entity` (`entity_type`,`entity_id`),
  ADD KEY `idx_audit_action` (`action`),
  ADD KEY `idx_audit_date` (`created_at`);

--
-- Indexes for table `branch`
--
ALTER TABLE `branch`
  ADD PRIMARY KEY (`BranchID`),
  ADD UNIQUE KEY `idx_branch_code` (`BranchCode`),
  ADD KEY `idx_branch_active` (`IsActive`);

--
-- Indexes for table `budget_approval`
--
ALTER TABLE `budget_approval`
  ADD PRIMARY KEY (`ApprovalID`),
  ADD KEY `idx_budget` (`BudgetID`),
  ADD KEY `idx_approver` (`ApprovedBy`);

--
-- Indexes for table `budget_item`
--
ALTER TABLE `budget_item`
  ADD PRIMARY KEY (`ItemID`),
  ADD KEY `idx_budget` (`BudgetID`),
  ADD KEY `idx_subcategory` (`SubcategoryID`);

--
-- Indexes for table `budget_item_category`
--
ALTER TABLE `budget_item_category`
  ADD PRIMARY KEY (`SubcategoryID`),
  ADD KEY `idx_type` (`CategoryType`);

--
-- Indexes for table `churchmember`
--
ALTER TABLE `churchmember`
  ADD PRIMARY KEY (`MbrID`),
  ADD UNIQUE KEY `idx_member_unique_id` (`MbrUniqueID`),
  ADD KEY `idx_member_branch` (`BranchID`),
  ADD KEY `idx_member_family` (`FamilyID`),
  ADD KEY `idx_member_status` (`MbrMembershipStatusID`),
  ADD KEY `idx_member_email` (`MbrEmailAddress`),
  ADD KEY `idx_member_deleted` (`Deleted`),
  ADD KEY `idx_member_names` (`MbrFamilyName`,`MbrFirstName`),
  ADD KEY `idx_member_registration` (`MbrRegistrationDate`),
  ADD KEY `fk_member_marital_status` (`MbrMaritalStatusID`),
  ADD KEY `fk_member_education` (`MbrEducationLevelID`),
  ADD KEY `fk_member_deleted_by` (`DeletedBy`);

--
-- Indexes for table `church_budget`
--
ALTER TABLE `church_budget`
  ADD PRIMARY KEY (`BudgetID`),
  ADD KEY `idx_fiscal` (`FiscalYearID`),
  ADD KEY `idx_branch` (`BranchID`),
  ADD KEY `idx_status` (`BudgetStatus`),
  ADD KEY `fk_budget_created_by` (`CreatedBy`);

--
-- Indexes for table `church_event`
--
ALTER TABLE `church_event`
  ADD PRIMARY KEY (`EventID`),
  ADD KEY `idx_datetime` (`EventDateTime`),
  ADD KEY `idx_branch` (`BranchID`),
  ADD KEY `idx_created_by` (`CreatedBy`);

--
-- Indexes for table `church_group`
--
ALTER TABLE `church_group`
  ADD PRIMARY KEY (`GroupID`),
  ADD KEY `idx_leader` (`GroupLeaderID`),
  ADD KEY `idx_type` (`GroupTypeID`),
  ADD KEY `idx_branch` (`BranchID`),
  ADD KEY `idx_active` (`IsActive`),
  ADD KEY `idx_deleted` (`Deleted`);

--
-- Indexes for table `church_role`
--
ALTER TABLE `church_role`
  ADD PRIMARY KEY (`RoleID`),
  ADD UNIQUE KEY `idx_role_name` (`RoleName`),
  ADD KEY `idx_active` (`IsActive`),
  ADD KEY `idx_system_role` (`IsSystemRole`);

--
-- Indexes for table `communication`
--
ALTER TABLE `communication`
  ADD PRIMARY KEY (`CommID`),
  ADD KEY `idx_sent_by` (`SentBy`),
  ADD KEY `idx_target_member` (`TargetMemberID`),
  ADD KEY `idx_target_group` (`TargetGroupID`),
  ADD KEY `idx_channel` (`ChannelID`),
  ADD KEY `idx_status` (`StatusID`),
  ADD KEY `idx_scheduled` (`ScheduledFor`),
  ADD KEY `fk_comm_target_branch` (`TargetBranchID`),
  ADD KEY `idx_comm_sent_at` (`SentAt`);

--
-- Indexes for table `communication_channel`
--
ALTER TABLE `communication_channel`
  ADD PRIMARY KEY (`ChannelID`);

--
-- Indexes for table `communication_delivery`
--
ALTER TABLE `communication_delivery`
  ADD PRIMARY KEY (`DeliveryID`),
  ADD KEY `idx_comm` (`CommID`),
  ADD KEY `idx_member` (`MbrID`),
  ADD KEY `idx_status` (`Status`),
  ADD KEY `idx_comm_member` (`CommID`,`MbrID`),
  ADD KEY `fk_delivery_channel` (`ChannelID`);

--
-- Indexes for table `communication_status`
--
ALTER TABLE `communication_status`
  ADD PRIMARY KEY (`StatusID`);

--
-- Indexes for table `communication_template`
--
ALTER TABLE `communication_template`
  ADD PRIMARY KEY (`TemplateID`),
  ADD KEY `idx_type` (`TemplateType`),
  ADD KEY `fk_commtemplate_created_by` (`CreatedBy`);

--
-- Indexes for table `contribution`
--
ALTER TABLE `contribution`
  ADD PRIMARY KEY (`ContributionID`),
  ADD KEY `idx_member` (`MbrID`),
  ADD KEY `idx_date` (`ContributionDate`),
  ADD KEY `idx_type` (`ContributionTypeID`),
  ADD KEY `idx_payment` (`PaymentMethodID`),
  ADD KEY `idx_fiscal` (`FiscalYearID`),
  ADD KEY `idx_branch` (`BranchID`),
  ADD KEY `idx_deleted` (`Deleted`),
  ADD KEY `idx_receipt` (`ReceiptNumber`),
  ADD KEY `idx_branch_date` (`BranchID`,`ContributionDate`),
  ADD KEY `idx_member_date` (`MbrID`,`ContributionDate`),
  ADD KEY `fk_contribution_recorded_by` (`RecordedBy`),
  ADD KEY `fk_contribution_deleted_by` (`DeletedBy`);

--
-- Indexes for table `contribution_audit`
--
ALTER TABLE `contribution_audit`
  ADD PRIMARY KEY (`AuditID`),
  ADD KEY `idx_contribution` (`ContributionID`),
  ADD KEY `idx_changed_by` (`ChangedBy`),
  ADD KEY `idx_created_at` (`CreatedAt`);

--
-- Indexes for table `contribution_type`
--
ALTER TABLE `contribution_type`
  ADD PRIMARY KEY (`ContributionTypeID`),
  ADD UNIQUE KEY `idx_name` (`ContributionTypeName`);

--
-- Indexes for table `document`
--
ALTER TABLE `document`
  ADD PRIMARY KEY (`DocumentID`),
  ADD KEY `idx_category` (`CategoryID`),
  ADD KEY `idx_related` (`RelatedToType`,`RelatedToID`),
  ADD KEY `idx_branch` (`BranchID`),
  ADD KEY `idx_uploaded_by` (`UploadedBy`);

--
-- Indexes for table `document_category`
--
ALTER TABLE `document_category`
  ADD PRIMARY KEY (`CategoryID`),
  ADD UNIQUE KEY `idx_name` (`CategoryName`);

--
-- Indexes for table `education_level`
--
ALTER TABLE `education_level`
  ADD PRIMARY KEY (`LevelID`),
  ADD UNIQUE KEY `idx_name` (`LevelName`);

--
-- Indexes for table `event_attendance`
--
ALTER TABLE `event_attendance`
  ADD PRIMARY KEY (`AttendanceID`),
  ADD UNIQUE KEY `idx_unique_attendance` (`EventID`,`MbrID`),
  ADD KEY `idx_event` (`EventID`),
  ADD KEY `idx_member` (`MbrID`),
  ADD KEY `idx_checkin` (`CheckInTime`);

--
-- Indexes for table `event_volunteer`
--
ALTER TABLE `event_volunteer`
  ADD PRIMARY KEY (`AssignmentID`),
  ADD KEY `idx_event` (`EventID`),
  ADD KEY `idx_member` (`MbrID`),
  ADD KEY `idx_role` (`VolunteerRoleID`),
  ADD KEY `fk_volunteer_assigned_by` (`AssignedBy`);

--
-- Indexes for table `expense`
--
ALTER TABLE `expense`
  ADD PRIMARY KEY (`ExpID`),
  ADD KEY `idx_category` (`ExpCategoryID`),
  ADD KEY `idx_date` (`ExpDate`),
  ADD KEY `idx_fiscal` (`FiscalYearID`),
  ADD KEY `idx_branch` (`BranchID`),
  ADD KEY `idx_requested_by` (`RequestedBy`),
  ADD KEY `idx_approval` (`ApprovalStatus`),
  ADD KEY `idx_deleted` (`Deleted`),
  ADD KEY `idx_branch_date` (`BranchID`,`ExpDate`),
  ADD KEY `fk_expense_payment` (`PaymentMethodID`),
  ADD KEY `fk_expense_deleted_by` (`DeletedBy`);

--
-- Indexes for table `expense_approval`
--
ALTER TABLE `expense_approval`
  ADD PRIMARY KEY (`ApprovalID`),
  ADD KEY `idx_expense` (`ExpID`),
  ADD KEY `idx_approver` (`ApproverID`);

--
-- Indexes for table `expense_audit`
--
ALTER TABLE `expense_audit`
  ADD PRIMARY KEY (`AuditID`),
  ADD KEY `idx_expense` (`ExpID`),
  ADD KEY `idx_changed_by` (`ChangedBy`),
  ADD KEY `idx_created_at` (`CreatedAt`);

--
-- Indexes for table `expense_category`
--
ALTER TABLE `expense_category`
  ADD PRIMARY KEY (`ExpCategoryID`),
  ADD KEY `idx_parent` (`ParentCategoryID`),
  ADD KEY `idx_name` (`CategoryName`);

--
-- Indexes for table `family`
--
ALTER TABLE `family`
  ADD PRIMARY KEY (`FamilyID`),
  ADD KEY `idx_head` (`HeadOfHouseholdID`),
  ADD KEY `idx_branch` (`BranchID`),
  ADD KEY `idx_family_name` (`FamilyName`);

--
-- Indexes for table `family_member`
--
ALTER TABLE `family_member`
  ADD PRIMARY KEY (`FamilyMemberID`),
  ADD UNIQUE KEY `idx_unique_family_member` (`FamilyID`,`MbrID`),
  ADD KEY `idx_family` (`FamilyID`),
  ADD KEY `idx_member` (`MbrID`),
  ADD KEY `idx_relationship` (`RelationshipID`);

--
-- Indexes for table `family_relationship`
--
ALTER TABLE `family_relationship`
  ADD PRIMARY KEY (`RelationshipID`);

--
-- Indexes for table `fiscal_year`
--
ALTER TABLE `fiscal_year`
  ADD PRIMARY KEY (`FiscalYearID`),
  ADD KEY `idx_branch` (`BranchID`),
  ADD KEY `idx_dates` (`StartDate`,`EndDate`),
  ADD KEY `idx_branch_active` (`BranchID`,`Status`);

--
-- Indexes for table `group_member`
--
ALTER TABLE `group_member`
  ADD PRIMARY KEY (`GroupMemberID`),
  ADD UNIQUE KEY `idx_unique_group_member` (`GroupID`,`MbrID`,`IsActive`),
  ADD KEY `idx_group` (`GroupID`),
  ADD KEY `idx_member` (`MbrID`),
  ADD KEY `idx_active` (`IsActive`);

--
-- Indexes for table `group_type`
--
ALTER TABLE `group_type`
  ADD PRIMARY KEY (`GroupTypeID`),
  ADD UNIQUE KEY `idx_name` (`GroupTypeName`);

--
-- Indexes for table `marital_status`
--
ALTER TABLE `marital_status`
  ADD PRIMARY KEY (`StatusID`),
  ADD UNIQUE KEY `idx_name` (`StatusName`);

--
-- Indexes for table `membership_status`
--
ALTER TABLE `membership_status`
  ADD PRIMARY KEY (`StatusID`),
  ADD UNIQUE KEY `idx_name` (`StatusName`);

--
-- Indexes for table `membership_type`
--
ALTER TABLE `membership_type`
  ADD PRIMARY KEY (`MshipTypeID`),
  ADD UNIQUE KEY `idx_name` (`MshipTypeName`);

--
-- Indexes for table `member_membership_type`
--
ALTER TABLE `member_membership_type`
  ADD PRIMARY KEY (`MemberMshipTypeID`),
  ADD KEY `idx_member` (`MbrID`),
  ADD KEY `idx_type` (`MshipTypeID`),
  ADD KEY `idx_active` (`MbrID`,`IsActive`),
  ADD KEY `fk_memmship_approved_by` (`ApprovedBy`);

--
-- Indexes for table `member_milestone`
--
ALTER TABLE `member_milestone`
  ADD PRIMARY KEY (`MilestoneID`),
  ADD KEY `idx_member` (`MbrID`),
  ADD KEY `idx_type` (`MilestoneTypeID`),
  ADD KEY `idx_date` (`MilestoneDate`),
  ADD KEY `idx_member_date` (`MbrID`,`MilestoneDate`),
  ADD KEY `idx_type_date` (`MilestoneTypeID`,`MilestoneDate`),
  ADD KEY `fk_milestone_recorded_by` (`RecordedBy`);

--
-- Indexes for table `member_phone`
--
ALTER TABLE `member_phone`
  ADD PRIMARY KEY (`PhoneID`),
  ADD KEY `idx_member` (`MbrID`),
  ADD KEY `idx_phone_number` (`PhoneNumber`),
  ADD KEY `idx_primary` (`MbrID`,`IsPrimary`),
  ADD KEY `fk_phone_type` (`PhoneTypeID`);

--
-- Indexes for table `member_role`
--
ALTER TABLE `member_role`
  ADD PRIMARY KEY (`MemberRoleID`),
  ADD KEY `idx_member` (`MbrID`),
  ADD KEY `idx_role` (`RoleID`),
  ADD KEY `idx_assigned_by` (`AssignedBy`),
  ADD KEY `idx_active` (`IsActive`),
  ADD KEY `idx_dates` (`StartDate`,`EndDate`),
  ADD KEY `idx_member_active` (`MbrID`,`IsActive`);

--
-- Indexes for table `member_volunteer_role`
--
ALTER TABLE `member_volunteer_role`
  ADD PRIMARY KEY (`MemberVolunteerRoleID`),
  ADD KEY `idx_member` (`MbrID`),
  ADD KEY `idx_volunteer_role` (`VolunteerRoleID`),
  ADD KEY `idx_active` (`IsActive`),
  ADD KEY `idx_member_active` (`MbrID`,`IsActive`),
  ADD KEY `idx_role_active` (`VolunteerRoleID`,`IsActive`);

--
-- Indexes for table `migration`
--
ALTER TABLE `migration`
  ADD PRIMARY KEY (`MigrationID`),
  ADD UNIQUE KEY `idx_migration_name` (`MigrationName`);

--
-- Indexes for table `milestone_type`
--
ALTER TABLE `milestone_type`
  ADD PRIMARY KEY (`MilestoneTypeID`),
  ADD UNIQUE KEY `idx_name` (`TypeName`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`TokenID`),
  ADD KEY `idx_user` (`UserID`),
  ADD KEY `idx_token` (`TokenHash`),
  ADD KEY `idx_expires` (`ExpiresAt`);

--
-- Indexes for table `payment_method`
--
ALTER TABLE `payment_method`
  ADD PRIMARY KEY (`PaymentMethodID`),
  ADD UNIQUE KEY `idx_name` (`PaymentMethodName`);

--
-- Indexes for table `permission`
--
ALTER TABLE `permission`
  ADD PRIMARY KEY (`PermissionID`),
  ADD UNIQUE KEY `idx_permission_name` (`PermissionName`),
  ADD KEY `idx_category` (`CategoryID`),
  ADD KEY `idx_active` (`IsActive`);

--
-- Indexes for table `permission_audit`
--
ALTER TABLE `permission_audit`
  ADD PRIMARY KEY (`AuditID`),
  ADD KEY `idx_performed_by` (`PerformedBy`),
  ADD KEY `idx_target` (`TargetType`,`TargetID`),
  ADD KEY `idx_created_at` (`CreatedAt`);

--
-- Indexes for table `permission_category`
--
ALTER TABLE `permission_category`
  ADD PRIMARY KEY (`CategoryID`),
  ADD UNIQUE KEY `idx_category_name` (`CategoryName`),
  ADD KEY `idx_display_order` (`DisplayOrder`);

--
-- Indexes for table `phone_type`
--
ALTER TABLE `phone_type`
  ADD PRIMARY KEY (`TypeID`);

--
-- Indexes for table `pledge`
--
ALTER TABLE `pledge`
  ADD PRIMARY KEY (`PledgeID`),
  ADD KEY `idx_member` (`MbrID`),
  ADD KEY `idx_campaign` (`CampaignID`),
  ADD KEY `idx_type` (`PledgeTypeID`),
  ADD KEY `idx_fiscal` (`FiscalYearID`),
  ADD KEY `idx_date` (`PledgeDate`),
  ADD KEY `fk_pledge_created_by` (`CreatedBy`);

--
-- Indexes for table `pledge_campaign`
--
ALTER TABLE `pledge_campaign`
  ADD PRIMARY KEY (`CampaignID`),
  ADD KEY `idx_branch` (`BranchID`),
  ADD KEY `idx_dates` (`StartDate`,`EndDate`),
  ADD KEY `idx_active` (`IsActive`),
  ADD KEY `fk_campaign_created_by` (`CreatedBy`);

--
-- Indexes for table `pledge_payment`
--
ALTER TABLE `pledge_payment`
  ADD PRIMARY KEY (`PaymentID`),
  ADD KEY `idx_pledge` (`PledgeID`),
  ADD KEY `idx_contribution` (`ContributionID`),
  ADD KEY `idx_date` (`PaymentDate`),
  ADD KEY `fk_pledgepay_recorded_by` (`RecordedBy`);

--
-- Indexes for table `pledge_type`
--
ALTER TABLE `pledge_type`
  ADD PRIMARY KEY (`PledgeTypeID`),
  ADD UNIQUE KEY `idx_name` (`PledgeTypeName`);

--
-- Indexes for table `role_permission`
--
ALTER TABLE `role_permission`
  ADD PRIMARY KEY (`RolePermissionID`),
  ADD UNIQUE KEY `idx_unique_role_permission` (`RoleID`,`PermissionID`),
  ADD KEY `idx_role` (`RoleID`),
  ADD KEY `idx_permission` (`PermissionID`);

--
-- Indexes for table `sermon`
--
ALTER TABLE `sermon`
  ADD PRIMARY KEY (`SermonID`),
  ADD KEY `idx_event` (`EventID`),
  ADD KEY `idx_date` (`SermonDate`),
  ADD KEY `idx_speaker` (`Speaker`),
  ADD KEY `idx_uploaded_by` (`UploadedBy`);

--
-- Indexes for table `system_setting`
--
ALTER TABLE `system_setting`
  ADD PRIMARY KEY (`SettingID`),
  ADD UNIQUE KEY `idx_key` (`SettingKey`),
  ADD KEY `idx_category` (`Category`);

--
-- Indexes for table `user_authentication`
--
ALTER TABLE `user_authentication`
  ADD PRIMARY KEY (`UserID`),
  ADD UNIQUE KEY `idx_username` (`Username`),
  ADD UNIQUE KEY `idx_email` (`Email`),
  ADD UNIQUE KEY `idx_user_member` (`MbrID`),
  ADD KEY `idx_active` (`IsActive`),
  ADD KEY `idx_last_login` (`LastLoginAt`);

--
-- Indexes for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`SessionID`),
  ADD KEY `idx_user` (`UserID`),
  ADD KEY `idx_token` (`TokenHash`),
  ADD KEY `idx_expires` (`ExpiresAt`),
  ADD KEY `idx_active_sessions` (`UserID`,`IsRevoked`,`ExpiresAt`);

--
-- Indexes for table `visitor`
--
ALTER TABLE `visitor`
  ADD PRIMARY KEY (`VisitorID`),
  ADD KEY `idx_branch` (`BranchID`),
  ADD KEY `idx_first_visit` (`FirstVisitDate`),
  ADD KEY `idx_email` (`EmailAddress`),
  ADD KEY `idx_phone` (`PhoneNumber`),
  ADD KEY `idx_follow_up` (`AssignedFollowUpPerson`),
  ADD KEY `idx_converted` (`ConvertedToMemberID`);

--
-- Indexes for table `visitor_followup`
--
ALTER TABLE `visitor_followup`
  ADD PRIMARY KEY (`FollowUpID`),
  ADD KEY `idx_visitor` (`VisitorID`),
  ADD KEY `idx_date` (`FollowUpDate`),
  ADD KEY `idx_next_date` (`NextFollowUpDate`),
  ADD KEY `idx_performed_by` (`PerformedBy`);

--
-- Indexes for table `visitor_visit`
--
ALTER TABLE `visitor_visit`
  ADD PRIMARY KEY (`VisitID`),
  ADD KEY `idx_visitor` (`VisitorID`),
  ADD KEY `idx_date` (`VisitDate`),
  ADD KEY `idx_event` (`EventID`),
  ADD KEY `fk_visit_recorded_by` (`RecordedBy`);

--
-- Indexes for table `volunteer_role`
--
ALTER TABLE `volunteer_role`
  ADD PRIMARY KEY (`VolunteerRoleID`),
  ADD UNIQUE KEY `idx_name` (`RoleName`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `asset`
--
ALTER TABLE `asset`
  MODIFY `AssetID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `asset_condition`
--
ALTER TABLE `asset_condition`
  MODIFY `ConditionID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `asset_maintenance`
--
ALTER TABLE `asset_maintenance`
  MODIFY `MaintenanceID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `asset_status`
--
ALTER TABLE `asset_status`
  MODIFY `StatusID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `branch`
--
ALTER TABLE `branch`
  MODIFY `BranchID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `budget_approval`
--
ALTER TABLE `budget_approval`
  MODIFY `ApprovalID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `budget_item`
--
ALTER TABLE `budget_item`
  MODIFY `ItemID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `budget_item_category`
--
ALTER TABLE `budget_item_category`
  MODIFY `SubcategoryID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `churchmember`
--
ALTER TABLE `churchmember`
  MODIFY `MbrID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `church_budget`
--
ALTER TABLE `church_budget`
  MODIFY `BudgetID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `church_event`
--
ALTER TABLE `church_event`
  MODIFY `EventID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `church_group`
--
ALTER TABLE `church_group`
  MODIFY `GroupID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `church_role`
--
ALTER TABLE `church_role`
  MODIFY `RoleID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `communication`
--
ALTER TABLE `communication`
  MODIFY `CommID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `communication_channel`
--
ALTER TABLE `communication_channel`
  MODIFY `ChannelID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `communication_delivery`
--
ALTER TABLE `communication_delivery`
  MODIFY `DeliveryID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `communication_status`
--
ALTER TABLE `communication_status`
  MODIFY `StatusID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `communication_template`
--
ALTER TABLE `communication_template`
  MODIFY `TemplateID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contribution`
--
ALTER TABLE `contribution`
  MODIFY `ContributionID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contribution_audit`
--
ALTER TABLE `contribution_audit`
  MODIFY `AuditID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contribution_type`
--
ALTER TABLE `contribution_type`
  MODIFY `ContributionTypeID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `document`
--
ALTER TABLE `document`
  MODIFY `DocumentID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `document_category`
--
ALTER TABLE `document_category`
  MODIFY `CategoryID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `education_level`
--
ALTER TABLE `education_level`
  MODIFY `LevelID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `event_attendance`
--
ALTER TABLE `event_attendance`
  MODIFY `AttendanceID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `event_volunteer`
--
ALTER TABLE `event_volunteer`
  MODIFY `AssignmentID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `expense`
--
ALTER TABLE `expense`
  MODIFY `ExpID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `expense_approval`
--
ALTER TABLE `expense_approval`
  MODIFY `ApprovalID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `expense_audit`
--
ALTER TABLE `expense_audit`
  MODIFY `AuditID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `expense_category`
--
ALTER TABLE `expense_category`
  MODIFY `ExpCategoryID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `family`
--
ALTER TABLE `family`
  MODIFY `FamilyID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `family_member`
--
ALTER TABLE `family_member`
  MODIFY `FamilyMemberID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `family_relationship`
--
ALTER TABLE `family_relationship`
  MODIFY `RelationshipID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fiscal_year`
--
ALTER TABLE `fiscal_year`
  MODIFY `FiscalYearID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `group_member`
--
ALTER TABLE `group_member`
  MODIFY `GroupMemberID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `group_type`
--
ALTER TABLE `group_type`
  MODIFY `GroupTypeID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `marital_status`
--
ALTER TABLE `marital_status`
  MODIFY `StatusID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `membership_status`
--
ALTER TABLE `membership_status`
  MODIFY `StatusID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `membership_type`
--
ALTER TABLE `membership_type`
  MODIFY `MshipTypeID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `member_membership_type`
--
ALTER TABLE `member_membership_type`
  MODIFY `MemberMshipTypeID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `member_milestone`
--
ALTER TABLE `member_milestone`
  MODIFY `MilestoneID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `member_phone`
--
ALTER TABLE `member_phone`
  MODIFY `PhoneID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `member_role`
--
ALTER TABLE `member_role`
  MODIFY `MemberRoleID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `member_volunteer_role`
--
ALTER TABLE `member_volunteer_role`
  MODIFY `MemberVolunteerRoleID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migration`
--
ALTER TABLE `migration`
  MODIFY `MigrationID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `milestone_type`
--
ALTER TABLE `milestone_type`
  MODIFY `MilestoneTypeID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  MODIFY `TokenID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_method`
--
ALTER TABLE `payment_method`
  MODIFY `PaymentMethodID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `permission`
--
ALTER TABLE `permission`
  MODIFY `PermissionID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `permission_audit`
--
ALTER TABLE `permission_audit`
  MODIFY `AuditID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `permission_category`
--
ALTER TABLE `permission_category`
  MODIFY `CategoryID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `phone_type`
--
ALTER TABLE `phone_type`
  MODIFY `TypeID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pledge`
--
ALTER TABLE `pledge`
  MODIFY `PledgeID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pledge_campaign`
--
ALTER TABLE `pledge_campaign`
  MODIFY `CampaignID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pledge_payment`
--
ALTER TABLE `pledge_payment`
  MODIFY `PaymentID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pledge_type`
--
ALTER TABLE `pledge_type`
  MODIFY `PledgeTypeID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `role_permission`
--
ALTER TABLE `role_permission`
  MODIFY `RolePermissionID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sermon`
--
ALTER TABLE `sermon`
  MODIFY `SermonID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_setting`
--
ALTER TABLE `system_setting`
  MODIFY `SettingID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_authentication`
--
ALTER TABLE `user_authentication`
  MODIFY `UserID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `SessionID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `visitor`
--
ALTER TABLE `visitor`
  MODIFY `VisitorID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `visitor_followup`
--
ALTER TABLE `visitor_followup`
  MODIFY `FollowUpID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `visitor_visit`
--
ALTER TABLE `visitor_visit`
  MODIFY `VisitID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `volunteer_role`
--
ALTER TABLE `volunteer_role`
  MODIFY `VolunteerRoleID` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `asset`
--
ALTER TABLE `asset`
  ADD CONSTRAINT `fk_asset_assigned_to` FOREIGN KEY (`AssignedTo`) REFERENCES `churchmember` (`MbrID`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_asset_branch` FOREIGN KEY (`BranchID`) REFERENCES `branch` (`BranchID`),
  ADD CONSTRAINT `fk_asset_condition` FOREIGN KEY (`AssetConditionID`) REFERENCES `asset_condition` (`ConditionID`),
  ADD CONSTRAINT `fk_asset_donor` FOREIGN KEY (`DonorID`) REFERENCES `churchmember` (`MbrID`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_asset_status` FOREIGN KEY (`AssetStatusID`) REFERENCES `asset_status` (`StatusID`);

--
-- Constraints for table `asset_maintenance`
--
ALTER TABLE `asset_maintenance`
  ADD CONSTRAINT `fk_maintenance_asset` FOREIGN KEY (`AssetID`) REFERENCES `asset` (`AssetID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_maintenance_recorded_by` FOREIGN KEY (`RecordedBy`) REFERENCES `churchmember` (`MbrID`);

--
-- Constraints for table `budget_approval`
--
ALTER TABLE `budget_approval`
  ADD CONSTRAINT `fk_budgetapproval_approver` FOREIGN KEY (`ApprovedBy`) REFERENCES `churchmember` (`MbrID`),
  ADD CONSTRAINT `fk_budgetapproval_budget` FOREIGN KEY (`BudgetID`) REFERENCES `church_budget` (`BudgetID`) ON DELETE CASCADE;

--
-- Constraints for table `budget_item`
--
ALTER TABLE `budget_item`
  ADD CONSTRAINT `fk_budgetitem_budget` FOREIGN KEY (`BudgetID`) REFERENCES `church_budget` (`BudgetID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_budgetitem_subcategory` FOREIGN KEY (`SubcategoryID`) REFERENCES `budget_item_category` (`SubcategoryID`);

--
-- Constraints for table `churchmember`
--
ALTER TABLE `churchmember`
  ADD CONSTRAINT `fk_member_branch` FOREIGN KEY (`BranchID`) REFERENCES `branch` (`BranchID`),
  ADD CONSTRAINT `fk_member_deleted_by` FOREIGN KEY (`DeletedBy`) REFERENCES `churchmember` (`MbrID`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_member_education` FOREIGN KEY (`MbrEducationLevelID`) REFERENCES `education_level` (`LevelID`),
  ADD CONSTRAINT `fk_member_family` FOREIGN KEY (`FamilyID`) REFERENCES `family` (`FamilyID`),
  ADD CONSTRAINT `fk_member_marital_status` FOREIGN KEY (`MbrMaritalStatusID`) REFERENCES `marital_status` (`StatusID`),
  ADD CONSTRAINT `fk_member_membership_status` FOREIGN KEY (`MbrMembershipStatusID`) REFERENCES `membership_status` (`StatusID`);

--
-- Constraints for table `church_budget`
--
ALTER TABLE `church_budget`
  ADD CONSTRAINT `fk_budget_branch` FOREIGN KEY (`BranchID`) REFERENCES `branch` (`BranchID`),
  ADD CONSTRAINT `fk_budget_created_by` FOREIGN KEY (`CreatedBy`) REFERENCES `churchmember` (`MbrID`),
  ADD CONSTRAINT `fk_budget_fiscal` FOREIGN KEY (`FiscalYearID`) REFERENCES `fiscal_year` (`FiscalYearID`);

--
-- Constraints for table `church_event`
--
ALTER TABLE `church_event`
  ADD CONSTRAINT `fk_event_branch` FOREIGN KEY (`BranchID`) REFERENCES `branch` (`BranchID`),
  ADD CONSTRAINT `fk_event_created_by` FOREIGN KEY (`CreatedBy`) REFERENCES `churchmember` (`MbrID`);

--
-- Constraints for table `church_group`
--
ALTER TABLE `church_group`
  ADD CONSTRAINT `fk_group_branch` FOREIGN KEY (`BranchID`) REFERENCES `branch` (`BranchID`),
  ADD CONSTRAINT `fk_group_leader` FOREIGN KEY (`GroupLeaderID`) REFERENCES `churchmember` (`MbrID`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_group_type` FOREIGN KEY (`GroupTypeID`) REFERENCES `group_type` (`GroupTypeID`);

--
-- Constraints for table `communication`
--
ALTER TABLE `communication`
  ADD CONSTRAINT `fk_comm_channel` FOREIGN KEY (`ChannelID`) REFERENCES `communication_channel` (`ChannelID`),
  ADD CONSTRAINT `fk_comm_sent_by` FOREIGN KEY (`SentBy`) REFERENCES `churchmember` (`MbrID`),
  ADD CONSTRAINT `fk_comm_status` FOREIGN KEY (`StatusID`) REFERENCES `communication_status` (`StatusID`),
  ADD CONSTRAINT `fk_comm_target_branch` FOREIGN KEY (`TargetBranchID`) REFERENCES `branch` (`BranchID`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_comm_target_group` FOREIGN KEY (`TargetGroupID`) REFERENCES `church_group` (`GroupID`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_comm_target_member` FOREIGN KEY (`TargetMemberID`) REFERENCES `churchmember` (`MbrID`) ON DELETE SET NULL;

--
-- Constraints for table `communication_delivery`
--
ALTER TABLE `communication_delivery`
  ADD CONSTRAINT `fk_delivery_channel` FOREIGN KEY (`ChannelID`) REFERENCES `communication_channel` (`ChannelID`),
  ADD CONSTRAINT `fk_delivery_comm` FOREIGN KEY (`CommID`) REFERENCES `communication` (`CommID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_delivery_member` FOREIGN KEY (`MbrID`) REFERENCES `churchmember` (`MbrID`) ON DELETE CASCADE;

--
-- Constraints for table `communication_template`
--
ALTER TABLE `communication_template`
  ADD CONSTRAINT `fk_commtemplate_created_by` FOREIGN KEY (`CreatedBy`) REFERENCES `churchmember` (`MbrID`);

--
-- Constraints for table `contribution`
--
ALTER TABLE `contribution`
  ADD CONSTRAINT `fk_contribution_branch` FOREIGN KEY (`BranchID`) REFERENCES `branch` (`BranchID`),
  ADD CONSTRAINT `fk_contribution_deleted_by` FOREIGN KEY (`DeletedBy`) REFERENCES `churchmember` (`MbrID`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_contribution_fiscal` FOREIGN KEY (`FiscalYearID`) REFERENCES `fiscal_year` (`FiscalYearID`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_contribution_member` FOREIGN KEY (`MbrID`) REFERENCES `churchmember` (`MbrID`),
  ADD CONSTRAINT `fk_contribution_payment` FOREIGN KEY (`PaymentMethodID`) REFERENCES `payment_method` (`PaymentMethodID`),
  ADD CONSTRAINT `fk_contribution_recorded_by` FOREIGN KEY (`RecordedBy`) REFERENCES `churchmember` (`MbrID`),
  ADD CONSTRAINT `fk_contribution_type` FOREIGN KEY (`ContributionTypeID`) REFERENCES `contribution_type` (`ContributionTypeID`);

--
-- Constraints for table `contribution_audit`
--
ALTER TABLE `contribution_audit`
  ADD CONSTRAINT `fk_contraudit_changed_by` FOREIGN KEY (`ChangedBy`) REFERENCES `churchmember` (`MbrID`),
  ADD CONSTRAINT `fk_contraudit_contribution` FOREIGN KEY (`ContributionID`) REFERENCES `contribution` (`ContributionID`) ON DELETE CASCADE;

--
-- Constraints for table `document`
--
ALTER TABLE `document`
  ADD CONSTRAINT `fk_document_branch` FOREIGN KEY (`BranchID`) REFERENCES `branch` (`BranchID`),
  ADD CONSTRAINT `fk_document_category` FOREIGN KEY (`CategoryID`) REFERENCES `document_category` (`CategoryID`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_document_uploaded_by` FOREIGN KEY (`UploadedBy`) REFERENCES `churchmember` (`MbrID`);

--
-- Constraints for table `event_attendance`
--
ALTER TABLE `event_attendance`
  ADD CONSTRAINT `fk_attendance_event` FOREIGN KEY (`EventID`) REFERENCES `church_event` (`EventID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_attendance_member` FOREIGN KEY (`MbrID`) REFERENCES `churchmember` (`MbrID`) ON DELETE CASCADE;

--
-- Constraints for table `event_volunteer`
--
ALTER TABLE `event_volunteer`
  ADD CONSTRAINT `fk_volunteer_assigned_by` FOREIGN KEY (`AssignedBy`) REFERENCES `churchmember` (`MbrID`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_volunteer_event` FOREIGN KEY (`EventID`) REFERENCES `church_event` (`EventID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_volunteer_member` FOREIGN KEY (`MbrID`) REFERENCES `churchmember` (`MbrID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_volunteer_role` FOREIGN KEY (`VolunteerRoleID`) REFERENCES `volunteer_role` (`VolunteerRoleID`);

--
-- Constraints for table `expense`
--
ALTER TABLE `expense`
  ADD CONSTRAINT `fk_expense_branch` FOREIGN KEY (`BranchID`) REFERENCES `branch` (`BranchID`),
  ADD CONSTRAINT `fk_expense_category` FOREIGN KEY (`ExpCategoryID`) REFERENCES `expense_category` (`ExpCategoryID`),
  ADD CONSTRAINT `fk_expense_deleted_by` FOREIGN KEY (`DeletedBy`) REFERENCES `churchmember` (`MbrID`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_expense_fiscal` FOREIGN KEY (`FiscalYearID`) REFERENCES `fiscal_year` (`FiscalYearID`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_expense_payment` FOREIGN KEY (`PaymentMethodID`) REFERENCES `payment_method` (`PaymentMethodID`),
  ADD CONSTRAINT `fk_expense_requested_by` FOREIGN KEY (`RequestedBy`) REFERENCES `churchmember` (`MbrID`);

--
-- Constraints for table `expense_approval`
--
ALTER TABLE `expense_approval`
  ADD CONSTRAINT `fk_expapproval_approver` FOREIGN KEY (`ApproverID`) REFERENCES `churchmember` (`MbrID`),
  ADD CONSTRAINT `fk_expapproval_expense` FOREIGN KEY (`ExpID`) REFERENCES `expense` (`ExpID`) ON DELETE CASCADE;

--
-- Constraints for table `expense_audit`
--
ALTER TABLE `expense_audit`
  ADD CONSTRAINT `fk_expaudit_changed_by` FOREIGN KEY (`ChangedBy`) REFERENCES `churchmember` (`MbrID`),
  ADD CONSTRAINT `fk_expaudit_expense` FOREIGN KEY (`ExpID`) REFERENCES `expense` (`ExpID`) ON DELETE CASCADE;

--
-- Constraints for table `family`
--
ALTER TABLE `family`
  ADD CONSTRAINT `fk_family_branch` FOREIGN KEY (`BranchID`) REFERENCES `branch` (`BranchID`),
  ADD CONSTRAINT `fk_family_head` FOREIGN KEY (`HeadOfHouseholdID`) REFERENCES `churchmember` (`MbrID`);

--
-- Constraints for table `family_member`
--
ALTER TABLE `family_member`
  ADD CONSTRAINT `fk_fammember_family` FOREIGN KEY (`FamilyID`) REFERENCES `family` (`FamilyID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_fammember_member` FOREIGN KEY (`MbrID`) REFERENCES `churchmember` (`MbrID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_fammember_relationship` FOREIGN KEY (`RelationshipID`) REFERENCES `family_relationship` (`RelationshipID`);

--
-- Constraints for table `fiscal_year`
--
ALTER TABLE `fiscal_year`
  ADD CONSTRAINT `fk_fiscal_branch` FOREIGN KEY (`BranchID`) REFERENCES `branch` (`BranchID`);

--
-- Constraints for table `group_member`
--
ALTER TABLE `group_member`
  ADD CONSTRAINT `fk_groupmember_group` FOREIGN KEY (`GroupID`) REFERENCES `church_group` (`GroupID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_groupmember_member` FOREIGN KEY (`MbrID`) REFERENCES `churchmember` (`MbrID`) ON DELETE CASCADE;

--
-- Constraints for table `member_membership_type`
--
ALTER TABLE `member_membership_type`
  ADD CONSTRAINT `fk_memmship_approved_by` FOREIGN KEY (`ApprovedBy`) REFERENCES `churchmember` (`MbrID`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_memmship_member` FOREIGN KEY (`MbrID`) REFERENCES `churchmember` (`MbrID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_memmship_type` FOREIGN KEY (`MshipTypeID`) REFERENCES `membership_type` (`MshipTypeID`);

--
-- Constraints for table `member_milestone`
--
ALTER TABLE `member_milestone`
  ADD CONSTRAINT `fk_milestone_member` FOREIGN KEY (`MbrID`) REFERENCES `churchmember` (`MbrID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_milestone_recorded_by` FOREIGN KEY (`RecordedBy`) REFERENCES `churchmember` (`MbrID`),
  ADD CONSTRAINT `fk_milestone_type` FOREIGN KEY (`MilestoneTypeID`) REFERENCES `milestone_type` (`MilestoneTypeID`);

--
-- Constraints for table `member_phone`
--
ALTER TABLE `member_phone`
  ADD CONSTRAINT `fk_phone_member` FOREIGN KEY (`MbrID`) REFERENCES `churchmember` (`MbrID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_phone_type` FOREIGN KEY (`PhoneTypeID`) REFERENCES `phone_type` (`TypeID`);

--
-- Constraints for table `member_role`
--
ALTER TABLE `member_role`
  ADD CONSTRAINT `fk_memberrole_assigned_by` FOREIGN KEY (`AssignedBy`) REFERENCES `churchmember` (`MbrID`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_memberrole_member` FOREIGN KEY (`MbrID`) REFERENCES `churchmember` (`MbrID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_memberrole_role` FOREIGN KEY (`RoleID`) REFERENCES `church_role` (`RoleID`) ON DELETE CASCADE;

--
-- Constraints for table `member_volunteer_role`
--
ALTER TABLE `member_volunteer_role`
  ADD CONSTRAINT `fk_mvr_member` FOREIGN KEY (`MbrID`) REFERENCES `churchmember` (`MbrID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_mvr_volunteer_role` FOREIGN KEY (`VolunteerRoleID`) REFERENCES `volunteer_role` (`VolunteerRoleID`) ON DELETE CASCADE;

--
-- Constraints for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD CONSTRAINT `fk_reset_user` FOREIGN KEY (`UserID`) REFERENCES `user_authentication` (`UserID`) ON DELETE CASCADE;

--
-- Constraints for table `permission`
--
ALTER TABLE `permission`
  ADD CONSTRAINT `fk_permission_category` FOREIGN KEY (`CategoryID`) REFERENCES `permission_category` (`CategoryID`) ON DELETE SET NULL;

--
-- Constraints for table `permission_audit`
--
ALTER TABLE `permission_audit`
  ADD CONSTRAINT `fk_permaudit_performed_by` FOREIGN KEY (`PerformedBy`) REFERENCES `churchmember` (`MbrID`);

--
-- Constraints for table `pledge`
--
ALTER TABLE `pledge`
  ADD CONSTRAINT `fk_pledge_campaign` FOREIGN KEY (`CampaignID`) REFERENCES `pledge_campaign` (`CampaignID`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_pledge_created_by` FOREIGN KEY (`CreatedBy`) REFERENCES `churchmember` (`MbrID`),
  ADD CONSTRAINT `fk_pledge_fiscal` FOREIGN KEY (`FiscalYearID`) REFERENCES `fiscal_year` (`FiscalYearID`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_pledge_member` FOREIGN KEY (`MbrID`) REFERENCES `churchmember` (`MbrID`),
  ADD CONSTRAINT `fk_pledge_type` FOREIGN KEY (`PledgeTypeID`) REFERENCES `pledge_type` (`PledgeTypeID`);

--
-- Constraints for table `pledge_campaign`
--
ALTER TABLE `pledge_campaign`
  ADD CONSTRAINT `fk_campaign_branch` FOREIGN KEY (`BranchID`) REFERENCES `branch` (`BranchID`),
  ADD CONSTRAINT `fk_campaign_created_by` FOREIGN KEY (`CreatedBy`) REFERENCES `churchmember` (`MbrID`);

--
-- Constraints for table `pledge_payment`
--
ALTER TABLE `pledge_payment`
  ADD CONSTRAINT `fk_pledgepay_contribution` FOREIGN KEY (`ContributionID`) REFERENCES `contribution` (`ContributionID`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_pledgepay_pledge` FOREIGN KEY (`PledgeID`) REFERENCES `pledge` (`PledgeID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pledgepay_recorded_by` FOREIGN KEY (`RecordedBy`) REFERENCES `churchmember` (`MbrID`);

--
-- Constraints for table `role_permission`
--
ALTER TABLE `role_permission`
  ADD CONSTRAINT `fk_roleperm_permission` FOREIGN KEY (`PermissionID`) REFERENCES `permission` (`PermissionID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_roleperm_role` FOREIGN KEY (`RoleID`) REFERENCES `church_role` (`RoleID`) ON DELETE CASCADE;

--
-- Constraints for table `sermon`
--
ALTER TABLE `sermon`
  ADD CONSTRAINT `fk_sermon_event` FOREIGN KEY (`EventID`) REFERENCES `church_event` (`EventID`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sermon_uploaded_by` FOREIGN KEY (`UploadedBy`) REFERENCES `churchmember` (`MbrID`);

--
-- Constraints for table `user_authentication`
--
ALTER TABLE `user_authentication`
  ADD CONSTRAINT `fk_auth_member` FOREIGN KEY (`MbrID`) REFERENCES `churchmember` (`MbrID`) ON DELETE CASCADE;

--
-- Constraints for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `fk_session_user` FOREIGN KEY (`UserID`) REFERENCES `user_authentication` (`UserID`) ON DELETE CASCADE;

--
-- Constraints for table `visitor`
--
ALTER TABLE `visitor`
  ADD CONSTRAINT `fk_visitor_branch` FOREIGN KEY (`BranchID`) REFERENCES `branch` (`BranchID`),
  ADD CONSTRAINT `fk_visitor_converted` FOREIGN KEY (`ConvertedToMemberID`) REFERENCES `churchmember` (`MbrID`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_visitor_followup` FOREIGN KEY (`AssignedFollowUpPerson`) REFERENCES `churchmember` (`MbrID`) ON DELETE SET NULL;

--
-- Constraints for table `visitor_followup`
--
ALTER TABLE `visitor_followup`
  ADD CONSTRAINT `fk_followup_performed_by` FOREIGN KEY (`PerformedBy`) REFERENCES `churchmember` (`MbrID`),
  ADD CONSTRAINT `fk_followup_visitor` FOREIGN KEY (`VisitorID`) REFERENCES `visitor` (`VisitorID`) ON DELETE CASCADE;

--
-- Constraints for table `visitor_visit`
--
ALTER TABLE `visitor_visit`
  ADD CONSTRAINT `fk_visit_event` FOREIGN KEY (`EventID`) REFERENCES `church_event` (`EventID`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_visit_recorded_by` FOREIGN KEY (`RecordedBy`) REFERENCES `churchmember` (`MbrID`),
  ADD CONSTRAINT `fk_visit_visitor` FOREIGN KEY (`VisitorID`) REFERENCES `visitor` (`VisitorID`) ON DELETE CASCADE;
SET FOREIGN_KEY_CHECKS=1;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
