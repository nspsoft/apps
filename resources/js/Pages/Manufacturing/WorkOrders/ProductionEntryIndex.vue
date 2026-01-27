<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { 
    CubeIcon, 
    ArrowRightIcon, 
    ClockIcon,
    MagnifyingGlassIcon
} from '@heroicons/vue/24/outline';
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { formatNumber } from '@/helpers';

const props = defineProps({
    workOrders: Array,
});

const search = ref('');

const filteredWOs = computed(() => {
    if (!search.value) return props.workOrders;
    return props.workOrders.filter(wo => 
        wo.wo_number.toLowerCase().includes(search.value.toLowerCase()) ||
        wo.product_name.toLowerCase().includes(search.value.toLowerCase())
    );
});

</script>

<template>
    <Head title="Input Produksi" />
    
    <AppLayout title="Input Produksi">
        <div class="max-w-7xl mx-auto">
            <!-- PWA Style Header for Mobile -->
            <div class="mb-6 lg:mb-8">
                <div class="relative">
                    <input 
                        v-model="search"
                        type="text" 
                        placeholder="Search Work Order / Product..." 
                        class="w-full rounded-2xl border-0 bg-white dark:bg-slate-950 py-4 pl-12 pr-4 text-slate-900 dark:text-white placeholder:text-slate-500 shadow-lg focus:ring-2 focus:ring-cyan-500/50"
                    />
                    <MagnifyingGlassIcon class="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-500" />
                </div>
            </div>

            <div v-if="filteredWOs.length === 0" class="text-center py-12">
                <div class="h-24 w-24 mx-auto rounded-full glass-card flex items-center justify-center mb-4">
                    <CubeIcon class="h-10 w-10 text-slate-500" />
                </div>
                <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2">No Active Jobs</h3>
                <p class="text-slate-500">All work orders are completed or none are started.</p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <Link 
                    v-for="wo in filteredWOs" 
                    :key="wo.id"
                    :href="route('manufacturing.work-orders.record-production-form', wo.id)"
                    class="block glass-card rounded-3xl p-5 hover:border-cyan-500/50 hover:shadow-lg hover:shadow-cyan-500/10 transition-all active:scale-[0.98]"
                >
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <div class="text-xs font-bold text-cyan-400 mb-1 border border-cyan-500/30 bg-cyan-500/10 px-2 py-0.5 rounded-full w-fit">
                                {{ wo.wo_number }}
                            </div>
                            <h3 class="font-bold text-slate-900 dark:text-white text-lg leading-tight line-clamp-2">
                                {{ wo.product_name }}
                            </h3>
                            <p class="text-xs text-slate-500 mt-1 font-mono">{{ wo.product_sku }}</p>
                        </div>
                        <div class="h-10 w-10 rounded-full bg-slate-50 dark:bg-slate-800 flex items-center justify-center text-slate-500 dark:text-slate-400 group-hover:text-cyan-400 group-hover:bg-cyan-500/20 transition-colors">
                            <ArrowRightIcon class="h-5 w-5" />
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="mb-4">
                        <div class="flex justify-between text-xs mb-2">
                            <span class="text-slate-500 dark:text-slate-400">Progress</span>
                            <span class="text-slate-900 dark:text-white font-mono font-bold">{{ wo.percent.toFixed(0) }}%</span>
                        </div>
                        <div class="h-2 bg-slate-50 dark:bg-slate-800 rounded-full overflow-hidden">
                            <div 
                                class="h-full bg-gradient-to-r from-cyan-500 to-blue-500 rounded-full"
                                :style="{ width: `${wo.percent}%` }"
                            ></div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 pt-4 border-t border-slate-200 dark:border-slate-800">
                        <div>
                            <div class="text-[10px] text-slate-500 uppercase font-bold mb-1">Produced</div>
                            <div class="text-emerald-400 font-mono font-bold">{{ formatNumber(wo.qty_produced) }}</div>
                        </div>
                        <div class="text-right">
                            <div class="text-[10px] text-slate-500 uppercase font-bold mb-1">Target</div>
                            <div class="text-slate-600 dark:text-slate-300 font-mono font-bold">{{ formatNumber(wo.qty_planned) }}</div>
                        </div>
                    </div>
                </Link>
            </div>
        </div>
    </AppLayout>
</template>



