/**
 * @file finance.ts
 * @description Types for the Financial module.
 */

/* ---------- Contribution ---------- */

export interface Contribution {
  ContributionID: number
  MbrID: number
  MemberName?: string
  ContributionTypeID: number
  ContributionTypeName?: string
  ContributionAmount: number
  ContributionDate: string
  PaymentMethodID: number | null
  PaymentMethodName?: string
  FiscalYearID: number | null
  FiscalYearName?: string
  ReceiptNumber: string | null
  Notes: string | null
  CreatedBy: number | null
  CreatedAt: string
  Deleted: number
}

export interface ContributionCreate {
  member_id: number
  contribution_type_id: number
  amount: number
  contribution_date: string
  payment_method_id?: number
  fiscal_year_id?: number
  receipt_number?: string
  notes?: string
}

export type ContributionUpdate = Partial<ContributionCreate>

export interface ContributionFilters {
  contribution_type_id?: number
  member_id?: number
  fiscal_year_id?: number
  start_date?: string
  end_date?: string
  search?: string
  sort_by?: string
  sort_dir?: 'ASC' | 'DESC'
}

export interface ContributionStats {
  total_amount: number
  total_count: number
  average_amount: number
  by_type: Array<{ type: string; total: number; count: number }>
  by_month: Array<{ month: string; total: number }>
}

export interface ContributionType {
  ContributionTypeID: number
  TypeName: string
  Description: string | null
  IsActive: number
}

export interface PaymentMethod {
  PaymentMethodID: number
  MethodName: string
  Description: string | null
  IsActive: number
}

/* ---------- Expense ---------- */

export interface Expense {
  ExpenseID: number
  ExpenseCategoryID: number
  CategoryName?: string
  Amount: number
  Description: string | null
  ExpenseDate: string
  ApprovedBy: number | null
  FiscalYearID: number | null
  CreatedBy: number | null
  CreatedAt: string
  Deleted: number
}

export interface ExpenseCategory {
  CategoryID: number
  CategoryName: string
  Description: string | null
  IsActive: number
}

/* ---------- Budget ---------- */

export interface Budget {
  BudgetID: number
  FiscalYearID: number
  FiscalYearName?: string
  CategoryID: number
  CategoryName?: string
  AllocatedAmount: number
  SpentAmount?: number
  Notes: string | null
}

/* ---------- Pledge ---------- */

export interface Pledge {
  PledgeID: number
  MbrID: number
  MemberName?: string
  PledgeTypeID: number
  PledgeTypeName?: string
  PledgeAmount: number
  AmountFulfilled: number
  PledgeDate: string
  DueDate: string | null
  Status: 'Active' | 'Fulfilled' | 'Cancelled'
  Notes: string | null
}

/* ---------- Fiscal Year ---------- */

export interface FiscalYear {
  FiscalYearID: number
  YearName: string
  StartDate: string
  EndDate: string
  Status: 'Active' | 'Closed' | 'Planned'
  IsCurrent: number
}

/* ---------- Finance Summary ---------- */

export interface FinanceSummary {
  total_income: number
  total_expenses: number
  net_balance: number
  income_by_type: Array<{ type: string; total: number }>
  expense_by_category: Array<{ category: string; total: number }>
}

/* ---------- Extended Detail Views ---------- */

export interface ContributionDetail extends Contribution {
  CreatedByName?: string
  UpdatedAt?: string
}

export interface ExpenseDetail extends Expense {
  CreatedByName?: string
  ApprovedByName?: string
  ApprovedAt?: string
  RejectionReason?: string
  Status: 'pending' | 'approved' | 'rejected' | 'cancelled'
  proofFiles?: Array<{
    FileID: number
    FileName: string
    FileUrl: string
    UploadedAt: string
  }>
}

export interface PledgeDetail extends Pledge {
  totalPaid: number
  remainingAmount: number
  percentagePaid: number
  payments: Array<{
    PaymentID: number
    ContributionID: number
    PaymentDate: string
    Amount: number
  }>
}

export interface BudgetDetail extends Budget {
  percentageUsed: number
  remainingAmount: number
  allocations: Array<{
    AllocationID: number
    CategoryName: string
    AllocatedAmount: number
    SpentAmount: number
  }>
}

/* ---------- Additional Input Types ---------- */

export interface ExpenseCreateInput {
  expense_category_id: number
  fiscal_year_id?: number
  amount: number
  expense_date: string
  description: string
  branch_id?: number
}

export interface ExpenseUpdateInput extends Partial<ExpenseCreateInput> {
  expense_id: number
}

export interface ExpenseReviewInput {
  expense_id: number
  status: 'approved' | 'rejected'
  rejection_reason?: string
}

export interface ExpenseListFilters {
  category_id?: number
  branch_id?: number
  status?: string
  fiscal_year_id?: number
  start_date?: string
  end_date?: string
  sort_by?: string
  sort_dir?: 'ASC' | 'DESC'
}

export interface PledgeCreateInput {
  member_id: number
  pledge_type_id: number
  fiscal_year_id?: number
  pledge_amount: number
  pledge_date?: string
  start_date?: string
  end_date?: string
  frequency?: 'weekly' | 'monthly' | 'quarterly' | 'yearly' | 'one-time'
  notes?: string
}

export interface PledgeUpdateInput extends Partial<PledgeCreateInput> {
  pledge_id: number
}

export interface PledgeListFilters {
  member_id?: number
  pledge_type_id?: number
  fiscal_year_id?: number
  status?: string
  sort_by?: string
  sort_dir?: 'ASC' | 'DESC'
}

export interface BudgetCreateInput {
  fiscal_year_id: number
  category_id?: number
  department_id?: number
  budget_amount: number
  notes?: string
}

export interface BudgetUpdateInput extends Partial<BudgetCreateInput> {
  budget_id: number
}

export interface BudgetListFilters {
  fiscal_year_id?: number
  category_id?: number
  department_id?: number
  sort_by?: string
  sort_dir?: 'ASC' | 'DESC'
}

export interface FiscalYearCreateInput {
  year_name: string
  start_date: string
  end_date: string
  is_active?: boolean
}

export interface FiscalYearUpdateInput extends Partial<FiscalYearCreateInput> {
  fiscal_year_id: number
}

export interface FiscalYearListFilters {
  is_active?: boolean
  is_closed?: boolean
  sort_by?: string
  sort_dir?: 'ASC' | 'DESC'
}

/* ---------- Pledge Type ---------- */

export interface PledgeType {
  PledgeTypeID: number
  TypeName: string
  Description: string | null
  IsActive: number
}
