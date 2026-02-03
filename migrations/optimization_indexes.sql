-- Database Optimization Migration
-- Adds missing indexes for performance on frequently queried columns

-- Church Member Table
ALTER TABLE `churchmember`
ADD INDEX `idx_mbr_email` (`MbrEmailAddress`),
ADD INDEX `idx_mbr_deleted` (`Deleted`),
ADD INDEX `idx_mbr_status` (`MbrMembershipStatusID`),
ADD INDEX `idx_mbr_branch` (`BranchID`);

-- Contribution Table
ALTER TABLE `contribution`
ADD INDEX `idx_contrib_date` (`ContributionDate`),
ADD INDEX `idx_contrib_type` (`ContributionTypeID`),
ADD INDEX `idx_contrib_fiscal` (`FiscalYearID`),
ADD INDEX `idx_contrib_deleted` (`Deleted`),
ADD INDEX `idx_contrib_member` (`MbrID`);

-- Expense Table
ALTER TABLE `expense`
ADD INDEX `idx_exp_date` (`ExpDate`),
ADD INDEX `idx_exp_status` (`ApprovalStatus`),
ADD INDEX `idx_exp_category` (`ExpCategoryID`),
ADD INDEX `idx_exp_fiscal` (`FiscalYearID`),
ADD INDEX `idx_exp_deleted` (`Deleted`),
ADD INDEX `idx_exp_branch` (`BranchID`);

-- Fiscal Year
ALTER TABLE `fiscal_year`
ADD INDEX `idx_fy_status` (`Status`),
ADD INDEX `idx_fy_branch` (`BranchID`);

-- Attendance / Events
ALTER TABLE `event_attendance`
ADD INDEX `idx_evt_att_event` (`EventID`),
ADD INDEX `idx_evt_att_member` (`MbrID`);

-- Communication
ALTER TABLE `communication`
ADD INDEX `idx_comm_status` (`StatusID`),
ADD INDEX `idx_comm_sent_at` (`SentAt`);
