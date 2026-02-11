<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';

defineProps({
    stats: Object,
    recent_inspections: Array,
});
</script>

<template>
    <AppLayout title="Quality Control Dashboard">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Quality Control Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Stats Grid -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6 border-l-4 border-indigo-500">
                        <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Inspections</div>
                        <div class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ stats.total_inspections }}</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6 border-l-4 border-green-500">
                        <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Pass Rate (30 Days)</div>
                        <div class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ stats.pass_rate }}%</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6 border-l-4 border-red-500">
                        <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Open NCRs</div>
                        <div class="mt-2 text-3xl font-bold text-red-600">{{ stats.open_ncrs }}</div>
                    </div>
                </div>

                <!-- Navigation Integration -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6 mb-8">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Quick Actions</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <Link :href="route('qc.incoming.index')" class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-indigo-50 dark:hover:bg-gray-600 transition">
                            <div class="font-semibold text-indigo-700 dark:text-indigo-400">Incoming QC (IQC)</div>
                            <div class="text-xs text-gray-500 mt-1">Check Raw Materials</div>
                        </Link>
                        <Link :href="route('qc.in-process.index')" class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-indigo-50 dark:hover:bg-gray-600 transition">
                            <div class="font-semibold text-indigo-700 dark:text-indigo-400">In-Process QC (IPQC)</div>
                            <div class="text-xs text-gray-500 mt-1">Check Production</div>
                        </Link>
                        <Link :href="route('qc.ncr.index')" class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-indigo-50 dark:hover:bg-gray-600 transition">
                            <div class="font-semibold text-indigo-700 dark:text-indigo-400">Defect Management (NCR)</div>
                            <div class="text-xs text-gray-500 mt-1">Manage Rejects</div>
                        </Link>
                        <Link :href="route('qc.coa.create')" class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-indigo-50 dark:hover:bg-gray-600 transition">
                            <div class="font-semibold text-indigo-700 dark:text-indigo-400">COA Generator</div>
                            <div class="text-xs text-gray-500 mt-1">Print Certificates</div>
                        </Link>
                        <Link :href="route('qc.master-points.index')" class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-indigo-50 dark:hover:bg-gray-600 transition">
                            <div class="font-semibold text-indigo-700 dark:text-indigo-400">Master Data</div>
                            <div class="text-xs text-gray-500 mt-1">QC Standards/Points</div>
                        </Link>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Recent Inspections</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Reference</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Inspector</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="inspection in recent_inspections" :key="inspection.id">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ inspection.date }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                        {{ inspection.reference_type?.split('\\').pop() }} #{{ inspection.reference_id }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" 
                                            :class="{
                                                'bg-green-100 text-green-800': inspection.status === 'pass',
                                                'bg-red-100 text-red-800': inspection.status === 'fail'
                                            }">
                                            {{ inspection.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ inspection.inspector }}</td>
                                </tr>
                                <tr v-if="recent_inspections.length === 0">
                                    <td colspan="4" class="px-6 py-4 text-center text-gray-500">No inspections found.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>
