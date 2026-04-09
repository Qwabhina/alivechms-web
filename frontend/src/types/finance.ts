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
