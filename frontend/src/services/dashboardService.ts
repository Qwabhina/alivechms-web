import apiClient from '@/api/apiClient';

// --- Interfaces matching Backend Data Structures ---

export interface FiscalYearStats {
   id: number | null;
   name: string;
   start_date: string;
   end_date: string;
   status: string;
}

export interface FinancialStats {
   fiscal_year: FiscalYearStats;
   total_amount: number;
   total_count: number;
   average_amount: number;
   average_per_contributor: number;
   unique_contributors: number;
   month_total: number;
   month_count: number;
   month_growth: number;
   last_month_total: number;
   week_total: number;
   week_count: number;
   today_total: number;
   today_count: number;
   monthly_trend: Array<{
      month: string;
      month_label: string;
      total: number;
      count: number;
   }>;
   by_type: Array<{
      ContributionTypeID: number;
      ContributionTypeName: string;
      total: number;
      count: number;
   }>;
   by_payment_method: Array<{
      PaymentMethodID: number;
      PaymentMethodName: string;
      total: number;
      count: number;
   }>;
}

export interface MemberStats {
   status_counts: Array<{
      MbrMembershipStatus: string;
      count: number;
   }>;
   new_this_month: number;
   gender_counts: Array<{
      MbrGender: string;
      count: number;
   }>;
   age_groups: Array<{
      age_group: string;
      count: number;
   }>;
}

export interface Activity {
   id: number;
   text: string;
   time: string;
   icon: string;
   color: string;
   type?: 'financial' | 'member' | 'event' | 'system';
}

export interface DashboardData {
   financial: FinancialStats;
   membership: MemberStats;
   recent_activity: Activity[];
}

export const dashboardService = {
   /**
    * Fetches the comprehensive dashboard data.
    */
   async getDashboardData(): Promise<DashboardData> {
      // In a real scenario, this might be a single aggregate endpoint
      // For now, we simulate fetching from the specialized endpoints we know exist or will exist

      // Mocking the aggregate response structure based on the "Overview" endpoint pattern
      const response = await apiClient.get('/dashboard/overview');
      return response.data.data;
   },

   /**
    * Fallback method to generate mock data if backend isn't ready
    */
   getMockData(): DashboardData {
      return {
         financial: {
            fiscal_year: { id: 1, name: 'FY2026', start_date: '2026-01-01', end_date: '2026-12-31', status: 'Active' },
            total_amount: 154250.00,
            total_count: 450,
            average_amount: 342.77,
            average_per_contributor: 1250.00,
            unique_contributors: 123,
            month_total: 12500.00,
            month_count: 45,
            month_growth: 12.5,
            last_month_total: 11100.00,
            week_total: 3200.00,
            week_count: 12,
            today_total: 450.00,
            today_count: 2,
            monthly_trend: [
               { month: '2025-03', month_label: 'Mar 2025', total: 10000, count: 40 },
               { month: '2025-04', month_label: 'Apr 2025', total: 11000, count: 42 },
               { month: '2025-05', month_label: 'May 2025', total: 10500, count: 38 },
               { month: '2025-06', month_label: 'Jun 2025', total: 12000, count: 45 },
               { month: '2025-07', month_label: 'Jul 2025', total: 11500, count: 41 },
               { month: '2025-08', month_label: 'Aug 2025', total: 13000, count: 50 },
               { month: '2025-09', month_label: 'Sep 2025', total: 12500, count: 44 },
               { month: '2025-10', month_label: 'Oct 2025', total: 14000, count: 55 },
               { month: '2025-11', month_label: 'Nov 2025', total: 13500, count: 48 },
               { month: '2025-12', month_label: 'Dec 2025', total: 18000, count: 70 },
               { month: '2026-01', month_label: 'Jan 2026', total: 11100, count: 39 },
               { month: '2026-02', month_label: 'Feb 2026', total: 12500, count: 45 },
            ],
            by_type: [
               { ContributionTypeID: 1, ContributionTypeName: 'Tithes', total: 85000, count: 300 },
               { ContributionTypeID: 2, ContributionTypeName: 'Offering', total: 45000, count: 100 },
               { ContributionTypeID: 3, ContributionTypeName: 'Projects', total: 24250, count: 50 },
            ],
            by_payment_method: [
               { PaymentMethodID: 1, PaymentMethodName: 'Cash', total: 50000, count: 200 },
               { PaymentMethodID: 2, PaymentMethodName: 'Bank Transfer', total: 80000, count: 150 },
               { PaymentMethodID: 3, PaymentMethodName: 'Mobile Money', total: 24250, count: 100 },
            ]
         },
         membership: {
            status_counts: [
               { MbrMembershipStatus: 'Active', count: 850 },
               { MbrMembershipStatus: 'Inactive', count: 120 },
               { MbrMembershipStatus: 'Visitor', count: 45 },
            ],
            new_this_month: 12,
            gender_counts: [
               { MbrGender: 'Male', count: 400 },
               { MbrGender: 'Female', count: 615 },
            ],
            age_groups: [
               { age_group: 'Under 18', count: 150 },
               { age_group: '18-30', count: 250 },
               { age_group: '31-45', count: 300 },
               { age_group: '46-60', count: 200 },
               { age_group: 'Over 60', count: 115 },
            ]
         },
         recent_activity: [
            { id: 1, text: 'Member John Doe added', time: '2 hours ago', icon: 'pi pi-user-plus', color: '#22c55e', type: 'member' },
            { id: 2, text: 'Tithe of $500 received', time: '3 hours ago', icon: 'pi pi-dollar', color: '#3b82f6', type: 'financial' },
            { id: 3, text: 'System backup completed', time: '1 day ago', icon: 'pi pi-database', color: '#64748b', type: 'system' },
         ]
      };
   }
};
