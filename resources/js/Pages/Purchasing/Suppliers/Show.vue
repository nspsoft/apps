<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    ArrowLeftIcon,
    PencilSquareIcon,
    BuildingOffice2Icon,
    PhoneIcon,
    EnvelopeIcon,
    MapPinIcon,
    UserCircleIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    supplier: Object,
});

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};
</script>

<template>
    <Head title="Supplier Details" />
    
    <AppLayout title="Supplier Details">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <Link
                        href="/purchasing/suppliers"
                        class="p-2 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white hover:bg-slate-700 transition-colors"
                    >
                        <ArrowLeftIcon class="h-5 w-5" />
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">{{ supplier.name }}</h1>
                        <div class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
                            <span class="font-mono">{{ supplier.code }}</span>
                            <span>•</span>
                            <span :class="supplier.is_active ? 'text-emerald-400' : 'text-slate-500'">
                                {{ supplier.is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                    </div>
                </div>
                <Link
                    :href="`/purchasing/suppliers/${supplier.id}/edit`"
                    class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white dark:text-white hover:bg-blue-500 transition-colors"
                >
                    <PencilSquareIcon class="h-5 w-5" />
                    Edit Supplier
                </Link>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- General Information -->
                    <div class="rounded-2xl glass-card overflow-hidden">
                        <div class="border-b border-slate-200 dark:border-slate-800 px-6 py-4">
                            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">General Information</h2>
                        </div>
                        <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div class="col-span-1 sm:col-span-2 flex items-start gap-3">
                                <div class="p-2 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-500 dark:text-slate-400">
                                    <MapPinIcon class="h-5 w-5" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-500 mb-1">Address</label>
                                    <p class="text-slate-900 dark:text-white">{{ supplier.address || '-' }}</p>
                                    <p class="text-slate-900 dark:text-white mt-1">
                                        {{ [supplier.city, supplier.postal_code].filter(Boolean).join(', ') }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <div class="p-2 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-500 dark:text-slate-400">
                                    <UserCircleIcon class="h-5 w-5" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-500 mb-1">Primary Contact</label>
                                    <p class="text-slate-900 dark:text-white font-medium">{{ supplier.contact_person || '-' }}</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <div class="p-2 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-500 dark:text-slate-400">
                                    <PhoneIcon class="h-5 w-5" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-500 mb-1">Phone / Fax</label>
                                    <p class="text-slate-900 dark:text-white">{{ supplier.phone || '-' }}</p>
                                    <p v-if="supplier.fax" class="text-slate-500 dark:text-slate-400 text-sm mt-0.5">Fax: {{ supplier.fax }}</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <div class="p-2 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-500 dark:text-slate-400">
                                    <EnvelopeIcon class="h-5 w-5" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-500 mb-1">Email</label>
                                    <p class="text-slate-900 dark:text-white">{{ supplier.email || '-' }}</p>
                                </div>
                            </div>

                             <div class="flex items-start gap-3">
                                <div class="p-2 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-500 dark:text-slate-400">
                                    <BuildingOffice2Icon class="h-5 w-5" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-500 mb-1">Tax Info</label>
                                    <p class="text-slate-900 dark:text-white">NPWP: {{ supplier.npwp || '-' }}</p>
                                    <p class="text-slate-500 dark:text-slate-400 text-sm mt-0.5">Tax ID: {{ supplier.tax_id || '-' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Persons -->
                    <div class="rounded-2xl glass-card overflow-hidden">
                         <div class="border-b border-slate-200 dark:border-slate-800 px-6 py-4 flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Contact Persons</h2>
                            <span class="text-xs font-medium text-slate-500 dark:text-slate-400 bg-slate-50 dark:bg-slate-800 px-2 py-1 rounded-lg">
                                {{ supplier.contacts ? supplier.contacts.length : 0 }} Contacts
                            </span>
                        </div>
                        <div class="p-6">
                            <div v-if="!supplier.contacts?.length" class="text-center py-4 text-slate-500">
                                No additional contacts listed.
                            </div>
                            <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div v-for="contact in supplier.contacts" :key="contact.id" class="p-4 rounded-xl bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 hover:border-blue-500/30 transition-colors">
                                    <div class="flex items-start justify-between mb-2">
                                        <div>
                                            <h3 class="text-slate-900 dark:text-white font-medium">{{ contact.name }}</h3>
                                            <p class="text-xs text-blue-400">{{ contact.position || 'No Position' }}</p>
                                        </div>
                                    </div>
                                    <div class="space-y-1 text-sm">
                                        <div v-if="contact.phone" class="flex items-center gap-2 text-slate-500 dark:text-slate-400">
                                            <PhoneIcon class="h-3 w-3" />
                                            {{ contact.phone }}
                                        </div>
                                        <div v-if="contact.email" class="flex items-center gap-2 text-slate-500 dark:text-slate-400">
                                            <EnvelopeIcon class="h-3 w-3" />
                                            {{ contact.email }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Payment Info -->
                    <div class="rounded-2xl glass-card overflow-hidden">
                        <div class="border-b border-slate-200 dark:border-slate-800 px-6 py-4">
                            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Payment Terms</h2>
                        </div>
                        <div class="p-6 space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-500 mb-1">Terms</label>
                                <p class="text-slate-900 dark:text-white font-mono text-lg">{{ supplier.payment_terms }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-500 mb-1">Due Days</label>
                                <p class="text-slate-900 dark:text-white">{{ supplier.payment_days }} Days</p>
                            </div>
                        </div>
                    </div>

                    <!-- Stats / Metadata -->
                    <div class="rounded-2xl glass-card overflow-hidden">
                         <div class="border-b border-slate-200 dark:border-slate-800 px-6 py-4">
                            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Metadata</h2>
                        </div>
                        <div class="p-6 space-y-3 text-sm">
                            <div class="flex justify-between">
                                <span class="text-slate-500">Created At</span>
                                <span class="text-slate-600 dark:text-slate-300">{{ formatDate(supplier.created_at) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Last Updated</span>
                                <span class="text-slate-600 dark:text-slate-300">{{ formatDate(supplier.updated_at) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>



