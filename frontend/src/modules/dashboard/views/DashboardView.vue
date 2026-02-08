<template>
   <div class="dashboard-view">
      <div class="dashboard-header">
         <h1>Dashboard</h1>
         <p class="welcome-message">Welcome back, {{ authStore.userFullName || 'User' }}!</p>
      </div>

      <div class="stats-grid">
         <div class="stat-card">
            <div class="stat-icon" style="background: rgba(59, 130, 246, 0.1)">
               <i class="pi pi-users" style="color: #3b82f6"></i>
            </div>
            <div class="stat-content">
               <h3>Total Members</h3>
               <p class="stat-value">{{ stats.totalMembers }}</p>
               <span class="stat-change positive">+12 this month</span>
            </div>
         </div>

         <div class="stat-card">
            <div class="stat-icon" style="background: rgba(34, 197, 94, 0.1)">
               <i class="pi pi-dollar" style="color: #22c55e"></i>
            </div>
            <div class="stat-content">
               <h3>Total Income</h3>
               <p class="stat-value">${{ stats.totalIncome.toLocaleString() }}</p>
               <span class="stat-change positive">+8.2% from last month</span>
            </div>
         </div>

         <div class="stat-card">
            <div class="stat-icon" style="background: rgba(249, 115, 22, 0.1)">
               <i class="pi pi-calendar" style="color: #f97316"></i>
            </div>
            <div class="stat-content">
               <h3>Upcoming Events</h3>
               <p class="stat-value">{{ stats.upcomingEvents }}</p>
               <span class="stat-label">Next 30 days</span>
            </div>
         </div>

         <div class="stat-card">
            <div class="stat-icon" style="background: rgba(168, 85, 247, 0.1)">
               <i class="pi pi-chart-line" style="color: #a855f7"></i>
            </div>
            <div class="stat-content">
               <h3>This Month Attendance</h3>
               <p class="stat-value">{{ stats.attendance }}%</p>
               <span class="stat-change" :class="{ positive: stats.attendance >= 75, negative: stats.attendance < 75 }">
                  Average weekly
               </span>
            </div>
         </div>
      </div>

      <div class="dashboard-content">
         <div class="content-section">
            <div class="section-header">
               <h2>Recent Activity</h2>
               <router-link to="/members" class="link">View All</router-link>
            </div>
            <div class="activity-list">
               <div v-for="activity in recentActivity" :key="activity.id" class="activity-item">
                  <div class="activity-icon" :style="{ background: activity.color }">
                     <i :class="activity.icon"></i>
                  </div>
                  <div class="activity-details">
                     <p class="activity-text">{{ activity.text }}</p>
                     <span class="activity-time">{{ activity.time }}</span>
                  </div>
               </div>
            </div>
         </div>

         <div class="content-section">
            <div class="section-header">
               <h2>Upcoming Events</h2>
               <router-link to="/events" class="link">View Calendar</router-link>
            </div>
            <div class="events-list">
               <div v-for="event in upcomingEventsList" :key="event.id" class="event-item">
                  <div class="event-date">
                     <span class="event-day">{{ event.day }}</span>
                     <span class="event-month">{{ event.month }}</span>
                  </div>
                  <div class="event-details">
                     <h4>{{ event.name }}</h4>
                     <p>{{ event.time }}</p>
                  </div>
               </div>
            </div>
         </div>
      </div>
   </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useAuthStore } from '@/stores/authStore';

const authStore = useAuthStore();

const stats = ref({
   totalMembers: 1248,
   totalIncome: 45680,
   upcomingEvents: 8,
   attendance: 82,
});

const recentActivity = ref([
   {
      id: 1,
      text: 'New member registered: John Smith',
      time: '2 hours ago',
      icon: 'pi pi-user-plus',
      color: 'rgba(34, 197, 94, 0.1)',
   },
   {
      id: 2,
      text: 'Contribution received: $500',
      time: '5 hours ago',
      icon: 'pi pi-dollar',
      color: 'rgba(59, 130, 246, 0.1)',
   },
   {
      id: 3,
      text: 'Event scheduled: Youth Fellowship',
      time: '1 day ago',
      icon: 'pi pi-calendar',
      color: 'rgba(249, 115, 22, 0.1)',
   },
]);

const upcomingEventsList = ref([
   {
      id: 1,
      name: 'Sunday Service',
      day: '14',
      month: 'FEB',
      time: '9:00 AM - 12:00 PM',
   },
   {
      id: 2,
      name: 'Bible Study',
      day: '16',
      month: 'FEB',
      time: '6:00 PM - 8:00 PM',
   },
   {
      id: 3,
      name: 'Youth Fellowship',
      day: '18',
      month: 'FEB',
      time: '4:00 PM - 6:00 PM',
   },
]);

onMounted(() => {
   // TODO: Fetch real dashboard data from API
});
</script>

<style scoped>
.dashboard-view {
   padding: var(--space-xl);
}

.dashboard-header {
   margin-bottom: var(--space-2xl);
}

.dashboard-header h1 {
   color: var(--color-text);
   margin-bottom: var(--space-sm);
}

.welcome-message {
   color: var(--color-text-muted);
   font-size: var(--font-size-lg);
}

.stats-grid {
   display: grid;
   grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
   gap: var(--space-lg);
   margin-bottom: var(--space-2xl);
}

.stat-card {
   background: var(--color-surface);
   border-radius: var(--radius-lg);
   padding: var(--space-lg);
   box-shadow: var(--shadow-sm);
   display: flex;
   gap: var(--space-md);
   transition: all var(--transition-fast);
}

.stat-card:hover {
   box-shadow: var(--shadow-md);
   transform: translateY(-2px);
}

.stat-icon {
   width: 56px;
   height: 56px;
   border-radius: var(--radius-md);
   display: flex;
   align-items: center;
   justify-content: center;
   font-size: 1.5rem;
}

.stat-content h3 {
   font-size: var(--font-size-sm);
   color: var(--color-text-muted);
   font-weight: 500;
   margin-bottom: var(--space-xs);
}

.stat-value {
   font-size: var(--font-size-3xl);
   font-weight: 700;
   color: var(--color-text);
   margin-bottom: var(--space-xs);
}

.stat-change,
.stat-label {
   font-size: var(--font-size-xs);
   color: var(--color-text-muted);
}

.stat-change.positive {
   color: var(--color-success);
}

.stat-change.negative {
   color: var(--color-error);
}

.dashboard-content {
   display: grid;
   grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
   gap: var(--space-xl);
}

.content-section {
   background: var(--color-surface);
   border-radius: var(--radius-lg);
   padding: var(--space-lg);
   box-shadow: var(--shadow-sm);
}

.section-header {
   display: flex;
   justify-content: space-between;
   align-items: center;
   margin-bottom: var(--space-lg);
}

.section-header h2 {
   font-size: var(--font-size-xl);
   color: var(--color-text);
}

.link {
   color: var(--color-primary);
   font-size: var(--font-size-sm);
   text-decoration: none;
   transition: color var(--transition-fast);
}

.link:hover {
   color: var(--color-primary-light);
   text-decoration: underline;
}

.activity-list {
   display: flex;
   flex-direction: column;
   gap: var(--space-md);
}

.activity-item {
   display: flex;
   gap: var(--space-md);
   padding: var(--space-sm);
   border-radius: var(--radius-md);
   transition: background var(--transition-fast);
}

.activity-item:hover {
   background: var(--color-bg);
}

.activity-icon {
   width: 40px;
   height: 40px;
   border-radius: var(--radius-md);
   display: flex;
   align-items: center;
   justify-content: center;
   flex-shrink: 0;
}

.activity-details {
   flex: 1;
}

.activity-text {
   color: var(--color-text);
   font-size: var(--font-size-sm);
   margin-bottom: var(--space-xs);
}

.activity-time {
   color: var(--color-text-muted);
   font-size: var(--font-size-xs);
}

.events-list {
   display: flex;
   flex-direction: column;
   gap: var(--space-md);
}

.event-item {
   display: flex;
   gap: var(--space-md);
   padding: var(--space-md);
   border: 1px solid var(--color-border);
   border-radius: var(--radius-md);
   transition: all var(--transition-fast);
}

.event-item:hover {
   border-color: var(--color-primary);
   box-shadow: var(--shadow-sm);
}

.event-date {
   text-align: center;
   padding: var(--space-sm);
   background: var(--color-primary);
   color: white;
   border-radius: var(--radius-md);
   min-width: 60px;
}

.event-day {
   display: block;
   font-size: var(--font-size-2xl);
   font-weight: 700;
   line-height: 1;
}

.event-month {
   display: block;
   font-size: var(--font-size-xs);
   font-weight: 600;
   text-transform: uppercase;
}

.event-details h4 {
   color: var(--color-text);
   margin-bottom: var(--space-xs);
   font-size: var(--font-size-base);
}

.event-details p {
   color: var(--color-text-muted);
   font-size: var(--font-size-sm);
}

@media (max-width: 768px) {
   .dashboard-view {
      padding: var(--space-md);
   }

   .stats-grid {
      grid-template-columns: 1fr;
   }

   .dashboard-content {
      grid-template-columns: 1fr;
   }
}
</style>
