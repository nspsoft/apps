<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    CubeIcon,
    ClipboardDocumentListIcon,
    PresentationChartLineIcon,
    ArrowLongRightIcon,
    DocumentMagnifyingGlassIcon,
    ChartBarIcon,
    ShoppingBagIcon,
    Cog8ToothIcon,
} from '@heroicons/vue/24/outline';
</script>

<template>
    <Head title="Reports" />
    
    <AppLayout title="Reports">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <!-- Inventory Balance -->
            <Link 
                href="/inventory/reports/inventory-balance"
                class="group relative overflow-hidden rounded-3xl glass-card p-8 hover:border-blue-500/50 transition-all duration-300 hover:shadow-[0_20px_40px_-15px_rgba(59,130,246,0.15)]"
            >
                <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity">
                    <CubeIcon class="h-24 w-24 text-blue-400" />
                </div>
                <div class="relative flex flex-col h-full">
                    <div class="p-4 rounded-2xl bg-blue-500/10 text-blue-400 w-fit mb-6 group-hover:scale-110 transition-transform">
                        <CubeIcon class="h-8 w-8" />
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">Inventory Balance</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed mb-6">Analyze current <strong>Stock Levels</strong> and financial valuation across all warehouses. Includes SKU-level aging data.</p>
                    <div class="mt-auto flex items-center text-sm font-bold text-blue-400 group-hover:translate-x-1 transition-transform">
                        Generate Report <ArrowLongRightIcon class="h-4 w-4 ml-2" />
                    </div>
                </div>
            </Link>

            <!-- Stock Card -->
            <Link 
                href="/inventory/reports/stock-card"
                class="group relative overflow-hidden rounded-3xl glass-card p-8 hover:border-emerald-500/50 transition-all duration-300 hover:shadow-[0_20px_40px_-15px_rgba(16,185,129,0.15)]"
            >
                <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity">
                    <DocumentMagnifyingGlassIcon class="h-24 w-24 text-emerald-400" />
                </div>
                <div class="relative flex flex-col h-full">
                    <div class="p-4 rounded-2xl bg-emerald-500/10 text-emerald-400 w-fit mb-6 group-hover:scale-110 transition-transform">
                        <DocumentMagnifyingGlassIcon class="h-8 w-8" />
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">Stock Ledger</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed mb-6">Audit <strong>Every Transaction</strong> for specific items. Trace movements from procurement to final sales delivery.</p>
                    <div class="mt-auto flex items-center text-sm font-bold text-emerald-400 group-hover:translate-x-1 transition-transform">
                        Trace Movements <ArrowLongRightIcon class="h-4 w-4 ml-2" />
                    </div>
                </div>
            </Link>

            <!-- Sales Summary -->
            <Link 
                href="/inventory/reports/sales-summary"
                class="group relative overflow-hidden rounded-3xl glass-card p-8 hover:border-cyan-500/50 transition-all duration-300 hover:shadow-[0_20px_40px_-15px_rgba(6,182,212,0.15)]"
            >
                <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity">
                    <ChartBarIcon class="h-24 w-24 text-cyan-400" />
                </div>
                <div class="relative flex flex-col h-full">
                    <div class="p-4 rounded-2xl bg-cyan-500/10 text-cyan-400 w-fit mb-6 group-hover:scale-110 transition-transform">
                        <ChartBarIcon class="h-8 w-8" />
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">Sales Analytics</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed mb-6">Monitor <strong>Top Performers</strong> and revenue trends. Drill down by customer, region, or product category.</p>
                    <div class="mt-auto flex items-center text-sm font-bold text-cyan-400 group-hover:translate-x-1 transition-transform">
                        Explore Trends <ArrowLongRightIcon class="h-4 w-4 ml-2" />
                    </div>
                </div>
            </Link>

            <!-- Purchase Summary -->
            <Link 
                href="/inventory/reports/purchase-summary"
                class="group relative overflow-hidden rounded-3xl glass-card p-8 hover:border-amber-500/50 transition-all duration-300 hover:shadow-[0_20px_40px_-15px_rgba(245,158,11,0.15)]"
            >
                <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity">
                    <ShoppingBagIcon class="h-24 w-24 text-amber-400" />
                </div>
                <div class="relative flex flex-col h-full">
                    <div class="p-4 rounded-2xl bg-amber-500/10 text-amber-400 w-fit mb-6 group-hover:scale-110 transition-transform">
                        <ShoppingBagIcon class="h-8 w-8" />
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">Procurement Costs</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed mb-6">Evaluate <strong>Spending Efficiency</strong> across suppliers. Track lead times and price variance for key materials.</p>
                    <div class="mt-auto flex items-center text-sm font-bold text-amber-400 group-hover:translate-x-1 transition-transform">
                        Analyze Costs <ArrowLongRightIcon class="h-4 w-4 ml-2" />
                    </div>
                </div>
            </Link>

            <!-- Production Summary -->
            <Link 
                href="/inventory/reports/production-summary"
                class="group relative overflow-hidden rounded-3xl glass-card p-8 hover:border-purple-500/50 transition-all duration-300 hover:shadow-[0_20px_40px_-15px_rgba(168,85,247,0.15)]"
            >
                <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity">
                    <Cog8ToothIcon class="h-24 w-24 text-purple-400" />
                </div>
                <div class="relative flex flex-col h-full">
                    <div class="p-4 rounded-2xl bg-purple-500/10 text-purple-400 w-fit mb-6 group-hover:scale-110 transition-transform">
                        <Cog8ToothIcon class="h-8 w-8" />
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">Production Yield</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed mb-6">Track <strong>Manufacturing Efficiency</strong>. Compare planned vs actual output and identify waste bottlenecks.</p>
                    <div class="mt-auto flex items-center text-sm font-bold text-purple-400 group-hover:translate-x-1 transition-transform">
                        View Yields <ArrowLongRightIcon class="h-4 w-4 ml-2" />
                    </div>
                </div>
            </Link>
        </div>
    </AppLayout>
</template>



