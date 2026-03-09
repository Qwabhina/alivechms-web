<script setup>
import { ref } from 'vue';
import AppMenuItem from './AppMenuItem.vue';

const model = ref([
    {
        label: 'Home',
        items: [
            {
                label: 'Dashboard',
                icon: 'pi pi-fw pi-home',
                to: '/'
            }
        ]
    },
    {
        label: 'Church Management',
        items: [
            {
                label: 'Members',
                icon: 'pi pi-fw pi-users',
                to: '/members',
                items: [
                    {
                        label: 'Member List',
                        icon: 'pi pi-fw pi-list',
                        to: '/members'
                    },
                    {
                        label: 'Add Member',
                        icon: 'pi pi-fw pi-user-plus',
                        to: '/members/create'
                    }
                ]
            },
            {
                label: 'Finance',
                icon: 'pi pi-fw pi-dollar',
                to: '/finance'
            },
            {
                label: 'Events',
                icon: 'pi pi-fw pi-calendar',
                to: '/events'
            }
        ]
    },
    {
        label: 'Reports & Settings',
        items: [
            {
                label: 'Reports',
                icon: 'pi pi-fw pi-chart-bar',
                to: '/reports'
            },
            {
                label: 'Settings',
                icon: 'pi pi-fw pi-cog',
                to: '/settings'
            }
        ]
    }
]);
</script>

<template>
    <ul class="layout-menu">
        <template v-for="(item, i) in model" :key="item">
            <app-menu-item v-if="!item.separator" :item="item" :index="i"></app-menu-item>
            <li v-if="item.separator" class="menu-separator"></li>
        </template>
    </ul>
</template>

<style lang="scss" scoped></style>
