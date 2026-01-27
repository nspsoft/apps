<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ArrowLeftIcon } from '@heroicons/vue/24/outline';

defineProps({
    log: Object,
});

const formatJson = (data) => {
    return JSON.stringify(data, null, 2);
};
</script>

<template>
    <Head title="Log Details" />
    
    <AppLayout title="Log Details">
        <div class="mb-6">
            <Link
                href="/admin/activity-logs"
                class="inline-flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white transition-colors"
            >
                <ArrowLeftIcon class="h-4 w-4" />
                Back to Activity Logs
            </Link>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Log Info -->
            <div class="lg:col-span-1 space-y-6">
                <div class="bg-white dark:bg-slate-950 rounded-2xl border border-slate-200 dark:border-slate-800 p-6">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Log Information</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs text-slate-500 uppercase font-bold">Date</label>
                            <p class="mt-1 text-slate-600 dark:text-slate-300">{{ log.created_at }}</p>
                        </div>
                        <div>
                            <label class="block text-xs text-slate-500 uppercase font-bold">Causer</label>
                            <p class="mt-1 text-slate-600 dark:text-slate-300">{{ log.causer_name }}</p>
                        </div>
                        <div>
                            <label class="block text-xs text-slate-500 uppercase font-bold">Subject Type</label>
                            <p class="mt-1 text-slate-600 dark:text-slate-300">{{ log.subject_type }}</p>
                        </div>
                        <div>
                            <label class="block text-xs text-slate-500 uppercase font-bold">Subject ID</label>
                            <p class="mt-1 text-slate-600 dark:text-slate-300">{{ log.subject_id }}</p>
                        </div>
                        <div>
                            <label class="block text-xs text-slate-500 uppercase font-bold">Event</label>
                            <p class="mt-1 text-slate-600 dark:text-slate-300 capitalize">{{ log.event }}</p>
                        </div>
                        <div>
                            <label class="block text-xs text-slate-500 uppercase font-bold">Description</label>
                            <p class="mt-1 text-slate-600 dark:text-slate-300">{{ log.description }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Changes -->
            <div class="lg:col-span-2">
                <div class="bg-white dark:bg-slate-950 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 h-full">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Changes Recorded</h3>
                    
                    <div v-if="log.properties && (log.properties.attributes || log.properties.old)" class="space-y-6">
                        <div v-if="log.properties.old">
                            <h4 class="text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Old Data</h4>
                            <pre class="bg-white dark:bg-slate-950 p-4 rounded-xl text-xs text-red-300 overflow-x-auto">{{ formatJson(log.properties.old) }}</pre>
                        </div>
                        
                        <div v-if="log.properties.attributes">
                            <h4 class="text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">New Data</h4>
                            <pre class="bg-white dark:bg-slate-950 p-4 rounded-xl text-xs text-green-300 overflow-x-auto">{{ formatJson(log.properties.attributes) }}</pre>
                        </div>
                    </div>
                    <div v-else class="text-slate-500 italic">
                        No specific attribute changes recorded or available.
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>


