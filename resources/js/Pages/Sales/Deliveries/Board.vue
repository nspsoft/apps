<script setup>
import { computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { 
    ClockIcon, 
    TruckIcon, 
    CheckCircleIcon, 
    DocumentCheckIcon,
    MapPinIcon
} from '@heroicons/vue/24/outline';
import { formatDate } from '@/helpers';

const props = defineProps({
    orders: Object // All orders passed from parent
});

const columns = [
    { 
        id: 'draft', 
        title: 'Draft / Request', 
        bg: 'bg-slate-50 dark:bg-slate-800/50',
        icon: ClockIcon,
        color: 'text-slate-500'
    },
    { 
        id: 'processing', 
        title: 'Processing (Gudang)', 
        bg: 'bg-amber-50 dark:bg-amber-900/10',
        icon: TruckIcon,
        color: 'text-amber-500',
        statuses: ['picking', 'packed'] 
    },
    { 
        id: 'shipped', 
        title: 'On Delivery (Logistik)', 
        bg: 'bg-blue-50 dark:bg-blue-900/10',
        icon: MapPinIcon,
        color: 'text-blue-500'
    },
    { 
        id: 'delivered', 
        title: 'Arrived (Driver)', 
        bg: 'bg-teal-50 dark:bg-teal-900/10',
        icon: CheckCircleIcon,
        color: 'text-teal-500'
    },
    { 
        id: 'completed', 
        title: 'Verified (Admin)', 
        bg: 'bg-emerald-50 dark:bg-emerald-900/10',
        icon: DocumentCheckIcon,
        color: 'text-emerald-500'
    }
];

// Helper to filter orders by column
const getOrdersByColumn = (colId, colStatuses) => {
    if (colStatuses) {
        return props.orders.data.filter(o => colStatuses.includes(o.status));
    }
    return props.orders.data.filter(o => o.status === colId);
};

// Drag & Drop Logic
const onDragStart = (evt, order) => {
    evt.dataTransfer.dropEffect = 'move';
    evt.dataTransfer.effectAllowed = 'move';
    evt.dataTransfer.setData('orderId', order.id);
    evt.dataTransfer.setData('sourceStatus', order.status);
};

const onDrop = (evt, targetStatus) => {
    const orderId = evt.dataTransfer.getData('orderId');
    const sourceStatus = evt.dataTransfer.getData('sourceStatus');

    // Prevent dropping if invalid logic
    // Example: Can't move from Completed back to Draft directly (optional strictness)
    
    // Determine the actual status to set
    // For 'processsing' column, we might default to 'packed' if moved there?
    // Let's simplified: If dropped 'processing', set to 'picking' (start) or 'packed'?
    // Let's assume 'processing' target -> set to 'picking' for now, or keep 'packed' if moved from picking?
    // Simpler: If target is 'processing', set to 'picking' (start of process).
    
    let newStatus = targetStatus;
    if (targetStatus === 'processing') newStatus = 'picking'; 

    if (sourceStatus === newStatus) return;

    if (confirm(`Pindahkan status DO ke '${targetStatus.toUpperCase()}'?`)) {
        router.visit(route('sales.deliveries.update-status', orderId), {
            method: 'patch',
            data: { status: newStatus },
            preserveScroll: true,
            preserveState: true,
        });
    }
};

</script>

<template>
    <div class="flex h-full gap-4 overflow-x-auto pb-4 items-stretch">
        <div 
            v-for="col in columns" 
            :key="col.id"
            class="flex-shrink-0 w-80 flex flex-col rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm"
            @dragover.prevent
            @dragenter.prevent
            @drop="onDrop($event, col.id)"
        >
            <!-- Header -->
            <div :class="`p-4 border-b border-slate-100 dark:border-slate-800 ${col.bg} rounded-t-2xl flex items-center justify-between`">
                <div class="flex items-center gap-2">
                    <component :is="col.icon" :class="`w-5 h-5 ${col.color}`" />
                    <h3 class="font-bold text-sm text-slate-700 dark:text-slate-200">{{ col.title }}</h3>
                </div>
                <span class="text-xs font-black bg-white dark:bg-slate-800 px-2 py-0.5 rounded-full text-slate-500 shadow-sm">
                    {{ getOrdersByColumn(col.id, col.statuses).length }}
                </span>
            </div>

            <!-- Cards Container -->
            <div class="p-3 flex-1 overflow-y-auto min-h-[150px] space-y-3 bg-slate-50/50 dark:bg-black/20">
                <div 
                    v-for="order in getOrdersByColumn(col.id, col.statuses)" 
                    :key="order.id"
                    draggable="true"
                    @dragstart="onDragStart($event, order)"
                    class="bg-white dark:bg-slate-800 p-4 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700 cursor-move hover:shadow-md hover:border-blue-400 transition-all group relative"
                >
                    <!-- Drag Handle visual -->
                    <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 cursor-grab text-slate-300">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path></svg>
                    </div>

                    <div class="flex justify-between items-start mb-2">
                        <span class="text-[10px] font-black uppercase text-slate-400 tracking-wider">{{ order.do_number }}</span>
                        <span class="text-[9px] font-bold px-1.5 py-0.5 rounded border" 
                            :class="order.status === 'completed' ? 'bg-emerald-50 border-emerald-100 text-emerald-600' : 'bg-slate-50 border-slate-100 text-slate-500'"
                        >
                            {{ order.status }}
                        </span>
                    </div>

                    <h4 class="text-sm font-bold text-slate-900 dark:text-white mb-1 line-clamp-1">{{ order.customer?.name }}</h4>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-3 flex items-center gap-1 truncate">
                        <MapPinIcon class="w-3 h-3" /> {{ order.shipping_address || 'No Address' }}
                    </p>

                    <div class="flex items-center gap-2 pt-3 border-t border-slate-50 dark:border-slate-700/50 mt-2">
                        <TruckIcon class="w-3.5 h-3.5 text-slate-400" />
                        <span class="text-xs font-medium text-slate-600 dark:text-slate-300 truncate">
                            {{ order.vehicle ? order.vehicle.license_plate : (order.vehicle_number || 'No Vehicle') }}
                        </span>
                    </div>
                     <div class="mt-1 text-[10px] text-slate-400 pl-5.5 truncate">
                        {{ order.driver_name || 'No Driver' }}
                    </div>
                </div>
                
                <!-- Empty State per column -->
                <div v-if="getOrdersByColumn(col.id, col.statuses).length === 0" class="h-full flex items-center justify-center py-8 text-center">
                    <span class="text-xs text-slate-400 border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-lg px-4 py-2 w-full">Kosong</span>
                </div>
            </div>
        </div>
    </div>
</template>
