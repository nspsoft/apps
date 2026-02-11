<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';

const props = defineProps({
    workOrder: Object,
});

const form = useForm({
    inspector_id: '', // User to input ID
    inspection_date: new Date().toISOString().slice(0, 10),
    sample_size: 1,
    qty_rejected_in_sample: 0,
    notes: '',
    inspection_data: {},
});

const initForm = () => {
    // Initialize standards
    const inspectionData = {};
    if (props.workOrder.product?.qc_master_points) {
        props.workOrder.product.qc_master_points.forEach(point => {
            inspectionData[point.id] = {
                actual_value: '',
                remark: '',
            };
        });
    }
    form.inspection_data = inspectionData;
};

onMounted(() => {
    initForm();
});

const submit = () => {
    form.post(route('qc.in-process.store', props.workOrder.id));
};
</script>

<template>
    <AppLayout title="In-Process Inspection">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Inspect Work Order: {{ workOrder.wo_number }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <form @submit.prevent="submit">
                    <!-- Header Info -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6 mb-6">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div>
                                <h3 class="text-sm font-medium text-gray-500">Product</h3>
                                <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ workOrder.product?.name }}</p>
                                <p class="text-sm text-gray-500">{{ workOrder.product?.code }}</p>
                            </div>
                            <div>
                                <h3 class="text-sm font-medium text-gray-500">Production Status</h3>
                                <p class="text-lg font-semibold text-gray-900 dark:text-white capitalize">{{ workOrder.status.replace('_', ' ') }}</p>
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

                    <!-- Inspection Points -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6 mb-6">
                        <div class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <InputLabel value="Sample Size (Qty Checked)" />
                                <TextInput type="number" v-model="form.sample_size" class="w-full mt-1" required />
                            </div>
                            <div>
                                <InputLabel value="Rejects Found in Sample" />
                                <TextInput type="number" v-model="form.qty_rejected_in_sample" class="w-full mt-1" />
                            </div>
                             <div>
                                <InputLabel value="Notes" />
                                <TextInput type="text" v-model="form.notes" class="w-full mt-1" />
                            </div>
                        </div>

                        <div v-if="workOrder.product?.qc_master_points?.length > 0">
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
                                        <tr v-for="point in workOrder.product.qc_master_points" :key="point.id">
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
                                                    v-model="form.inspection_data[point.id].actual_value"
                                                    class="w-32" 
                                                />
                                            </td>
                                            <td class="px-4 py-2">
                                                <TextInput 
                                                    type="text" 
                                                    v-model="form.inspection_data[point.id].remark"
                                                    class="w-full" 
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
