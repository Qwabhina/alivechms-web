export class DashboardAPI {
    /**
     * Get dashboard overview data
     * @returns {Promise<Object>}
     */
    async getOverview() {
        return await api.get('dashboard/overview');
    }
}
