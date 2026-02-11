<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';

const props = defineProps({
    receipt: Object,
});

const form = useForm({
    inspector_id: props.receipt.received_by, // Default to receiver or current user? Let's assume current user logic later, using received_by as placeholder
    inspection_date: new Date().toISOString().slice(0, 10),
    items: [],
});

const initForm = () => {
    form.items = props.receipt.items.map(item => {
        // Prepare inspection data structure
        const inspectionData = {};
        if (item.product.qc_master_points) {
            item.product.qc_master_points.forEach(point => {
                inspectionData[point.id] = {
                    actual_value: '',
                    remark: '',
                };
            });
        }

        return {
            id: item.id,
            qty_received: item.qty_received,
            qty_rejected: 0,
            qty_accepted: item.qty_received,
            inspection_data: inspectionData,
            // Helper for display
            product_name: item.product.name,
            product_code: item.product.code,
            master_points: item.product.qc_master_points || [],
            unit: item.unit?.name,
        };
    });
};

onMounted(() => {
    initForm();
});

const updateAccepted = (itemIndex) => {
    const item = form.items[itemIndex];
    item.qty_accepted = Math.max(0, item.qty_received - item.qty_rejected);
};

const submit = () => {
    // Current user Logic: if inspector_id is required from selection or current auth user?
    // For now assuming the backend validates inspector_id. 
    // If we want to assign "myself", we might need to pass auth user prop or handle in backend if not provided.
    // The Controller expects 'inspector_id'. Let's add a selector or hidden field.
    // For specific requirement, I'll add a simple user select if needed, but for now hardcode or use receipt.received_by as default.
    // Ideally, we should pick the inspector.
    
    // Quick fix: assume user is logged in, pass their ID or let backend handle?
    // Backend validation says `inspector_id` required.
    // I'll add a simple input for Inspector ID or just use a select if I had users list.
    // Since I don't have users list in props, I will prompt or rely on a Prop I missed?
    // Ah, I missed passing 'users' or 'inspector'.
    // Let's rely on the user manually entering ID for now or just using the received_by.
    // Wait, `received_by` is in `receipt`.
    
    form.post(route('qc.incoming.store', props.receipt.id));
};
</script>

<template>
    <AppLayout title="Perform Inspection">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Inspect GRN: {{ receipt.grn_number }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <form @submit.prevent="submit">
                    <!-- Header Info -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6 mb-6">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div>
                                <h3 class="text-sm font-medium text-gray-500">Supplier</h3>
                                <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ receipt.supplier?.name }}</p>
                            </div>
                            <div>
                                <h3 class="text-sm font-medium text-gray-500">Receipt Date</h3>
                                <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ receipt.receipt_date }}</p>
                            </div>
                            <div>
                                <InputLabel value="Inspection Date" />
                                <TextInput type="date" v-model="form.inspection_date" class="w-full mt-1" required />
                            </div>
                            <div>
                                <InputLabel value="Inspector ID" />
                                <TextInput type="number" v-model="form.inspector_id" class="w-full mt-1" required placeholder="User ID" />
                            </div>
                        </div>
                    </div>

                    <!-- Items Inspection -->
                    <div v-for="(item, index) in form.items" :key="item.id" class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6 mb-6">
                        <div class="border-b border-gray-200 dark:border-gray-700 pb-4 mb-4">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                                {{ item.product_code }} - {{ item.product_name }}
                            </h3>
                            <div class="flex gap-4 mt-2">
                                <span class="text-sm text-gray-500">Received: {{ item.qty_received }} {{ item.unit }}</span>
                            </div>
                        </div>

                        <!-- Quantities -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                            <div>
                                <InputLabel value="Qty Rejected" />
                                <TextInput 
                                    type="number" 
                                    step="any"
                                    v-model="item.qty_rejected" 
                                    @input="updateAccepted(index)"
                                    class="w-full mt-1 bg-red-50 text-red-900 border-red-300 focus:border-red-500 focus:ring-red-500" 
                                />
                                <div v-if="form.errors[`items.${index}.qty_rejected`]" class="text-red-500 text-xs mt-1">
                                    {{ form.errors[`items.${index}.qty_rejected`] }}
                                </div>
                            </div>
                            <div>
                                <InputLabel value="Qty Accepted" />
                                <TextInput 
                                    type="number" 
                                    step="any"
                                    v-model="item.qty_accepted" 
                                    class="w-full mt-1 bg-green-50 text-green-900 border-green-300" 
                                    readonly 
                                />
                            </div>
                        </div>

                        <!-- QC Master Points -->
                        <div v-if="item.master_points.length > 0">
                            <h4 class="font-medium text-gray-900 dark:text-gray-200 mb-3">QC Parameters</h4>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead class="bg-gray-50 dark:bg-gray-700">
                                        <tr>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Parameter</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Standard</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Actual Value</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Remark</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                        <tr v-for="point in item.master_points" :key="point.id">
                                            <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">
                                                {{ point.parameter_name }}
                                            </td>
                                            <td class="px-4 py-2 text-sm text-gray-500">
                                                {{ point.standard_min }} - {{ point.standard_max }} {{ point.unit }}
                                            </td>
                                            <td class="px-4 py-2">
                                                <TextInput 
                                                    type="number" 
                                                    step="0.01" 
                                                    v-model="item.inspection_data[point.id].actual_value"
                                                    class="w-32" 
                                                    required 
                                                />
                                            </td>
                                            <td class="px-4 py-2">
                                                <TextInput 
                                                    type="text" 
                                                    v-model="item.inspection_data[point.id].remark"
                                                    class="w-full" 
                                                    placeholder="Optional"
                                                />
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div v-else class="text-sm text-gray-500 italic">
                            No QC Standards defined for this product.
                        </div>
                    </div>

                    <div class="flex justify-end p-6">
                        <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                            Submit Inspection
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
