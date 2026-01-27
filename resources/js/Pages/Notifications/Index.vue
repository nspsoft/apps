<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    CheckCircleIcon,
    BellIcon,
    ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    notifications: Object,
});

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('id-ID', { 
        day: '2-digit', 
        month: 'short', 
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const markAsRead = (id) => {
    router.post(`/notifications/${id}/read`, {}, {
        preserveScroll: true,
    });
};

const markAllAsRead = () => {
    router.post('/notifications/mark-all-read', {}, {
        preserveScroll: true,
    });
};

const getIcon = (type) => {
    if (type === 'low_stock') return ExclamationTriangleIcon;
    return BellIcon;
};
</script>

<template>
    <Head title="Notifications" />
    
    <AppLayout title="Notifications">
        <div class="max-w-4xl mx-auto">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">All Notifications</h2>
                <button 
                    @click="markAllAsRead"
                    class="text-sm font-medium text-blue-400 hover:text-blue-300"
                >
                    Mark all as read
                </button>
            </div>

            <div class="space-y-4">
                <div 
                    v-for="notification in notifications.data" 
                    :key="notification.id"
                    class="rounded-2xl border p-4 transition-all"
                    :class="notification.read_at 
                        ? 'bg-white dark:bg-slate-950/50 border-slate-200 dark:border-slate-800' 
                        : 'bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 shadow-md shadow-blue-500/5'"
                >
                    <div class="flex items-start gap-4">
                        <div 
                            class="p-2 rounded-xl"
                            :class="notification.read_at ? 'bg-slate-50 dark:bg-slate-800 text-slate-500' : 'bg-blue-500/20 text-blue-400'"
                        >
                            <component :is="getIcon(notification.data.type)" class="h-6 w-6" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between">
                                <p 
                                    class="text-sm font-medium"
                                    :class="notification.read_at ? 'text-slate-600 dark:text-slate-300' : 'text-slate-900 dark:text-white'"
                                >
                                    {{ notification.data.title }}
                                </p>
                                <span class="text-xs text-slate-500">{{ formatDate(notification.created_at) }}</span>
                            </div>
                            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">{{ notification.data.message }}</p>
                            
                            <div class="mt-3 flex items-center gap-4">
                                <Link 
                                    v-if="notification.data.link"
                                    :href="notification.data.link"
                                    class="text-xs font-medium text-blue-400 hover:text-blue-300"
                                >
                                    View Details
                                </Link>
                                <button
                                    v-if="!notification.read_at"
                                    @click="markAsRead(notification.id)"
                                    class="text-xs font-medium text-slate-500 hover:text-slate-900 dark:text-white"
                                >
                                    Mark as read
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="notifications.data.length === 0" class="text-center py-12 text-slate-500">
                    <BellIcon class="mx-auto h-12 w-12 text-slate-700" />
                    <p class="mt-4">No notifications yet.</p>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="notifications.last_page > 1" class="mt-6 flex justify-center gap-2">
                <Link
                    v-for="link in notifications.links"
                    :key="link.label"
                    :href="link.url || '#'"
                    class="px-3 py-1.5 rounded-lg text-sm transition-colors"
                    :class="link.active 
                        ? 'bg-blue-600 text-slate-900 dark:text-white' 
                        : link.url 
                            ? 'text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 hover:text-slate-900 dark:text-white' 
                            : 'text-white cursor-not-allowed'"
                    v-html="link.label"
                />
            </div>
        </div>
    </AppLayout>
</template>


