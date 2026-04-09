-- ============================================================================
-- Migration: Remove Duplicate Database Indexes
-- ============================================================================
-- Date:    2026-02-04
-- Purpose: Remove 22 duplicate indexes to improve write performance and reduce storage
-- Tables:  churchmember, contribution, expense, communication, fiscal_year, event_attendance
-- ============================================================================

-- Start transaction for safe execution
START TRANSACTION;

-- ============================================================================
-- churchmember table (4 duplicate indexes)
-- ============================================================================
ALTER TABLE `churchmember`
  DROP INDEX `idx_mbr_email`,      -- duplicates idx_member_email on MbrEmailAddress
  DROP INDEX `idx_mbr_deleted`,    -- duplicates idx_member_deleted on Deleted
  DROP INDEX `idx_mbr_status`,     -- duplicates idx_member_status on MbrMembershipStatusID
  DROP INDEX `idx_mbr_branch`;     -- duplicates idx_member_branch on BranchID

-- ============================================================================
-- contribution table (5 duplicate indexes)
-- ============================================================================
ALTER TABLE `contribution`
  DROP INDEX `idx_contrib_date`,   -- duplicates idx_date on ContributionDate
  DROP INDEX `idx_contrib_type`,   -- duplicates idx_type on ContributionTypeID
  DROP INDEX `idx_contrib_fiscal`, -- duplicates idx_fiscal on FiscalYearID
  DROP INDEX `idx_contrib_deleted`,-- duplicates idx_deleted on Deleted
  DROP INDEX `idx_contrib_member`; -- duplicates idx_member on MbrID

-- ============================================================================
-- expense table (6 duplicate indexes)
-- ============================================================================
ALTER TABLE `expense`
  DROP INDEX `idx_exp_date`,       -- duplicates idx_date on ExpDate
  DROP INDEX `idx_exp_status`,     -- duplicates idx_approval on ApprovalStatus
  DROP INDEX `idx_exp_category`,   -- duplicates idx_category on ExpCategoryID
  DROP INDEX `idx_exp_fiscal`,     -- duplicates idx_fiscal on FiscalYearID
  DROP INDEX `idx_exp_deleted`,    -- duplicates idx_deleted on Deleted
  DROP INDEX `idx_exp_branch`;     -- duplicates idx_branch on BranchID

-- ============================================================================
-- communication table (1 duplicate index)
-- ============================================================================
ALTER TABLE `communication`
  DROP INDEX `idx_comm_status`;    -- duplicates idx_status on StatusID

-- ============================================================================
-- fiscal_year table (2 duplicate indexes)
-- ============================================================================
ALTER TABLE `fiscal_year`
  DROP INDEX `idx_fy_status`,      -- covered by idx_branch_active on Status
  DROP INDEX `idx_fy_branch`;      -- duplicates idx_branch on BranchID

-- ============================================================================
-- event_attendance table (2 duplicate indexes)
-- ============================================================================
ALTER TABLE `event_attendance`
  DROP INDEX `idx_evt_att_event`,  -- duplicates idx_event on EventID
  DROP INDEX `idx_evt_att_member`; -- duplicates idx_member on MbrID

-- Commit the changes
COMMIT;

-- ============================================================================
-- Verification queries (run separately to confirm removal)
-- ============================================================================
-- SHOW INDEX FROM churchmember WHERE Key_name LIKE 'idx_mbr_%';
-- SHOW INDEX FROM contribution WHERE Key_name LIKE 'idx_contrib_%';
-- SHOW INDEX FROM expense WHERE Key_name LIKE 'idx_exp_%';
-- SHOW INDEX FROM communication WHERE Key_name = 'idx_comm_status';
-- SHOW INDEX FROM fiscal_year WHERE Key_name LIKE 'idx_fy_%';
-- SHOW INDEX FROM event_attendance WHERE Key_name LIKE 'idx_evt_att_%';
