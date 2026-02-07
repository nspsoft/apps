<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { 
    UserPlusIcon, 
    MagnifyingGlassIcon,
    FunnelIcon,
    TrashIcon,
    PencilSquareIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    leads: Array,
    title: String
});

const form = useForm({
    name: '',
    company: '',
    email: '',
    phone: '',
    status: 'new',
    source: ''
});

const showCreateModal = ref(false);
const editingLead = ref(null);

const submit = () => {
    if (editingLead.value) {
        form.put(route('crm.leads.update', editingLead.value.id), {
            onSuccess: () => {
                showCreateModal.value = false;
                editingLead.value = null;
                form.reset();
            }
        });
    } else {
        form.post(route('crm.leads.store'), {
            onSuccess: () => {
                showCreateModal.value = false;
                form.reset();
            }
        });
    }
};

const editLead = (lead) => {
    editingLead.value = lead;
    form.name = lead.name;
    form.company = lead.company;
    form.email = lead.email;
    form.phone = lead.phone;
    form.status = lead.status;
    form.source = lead.source;
    showCreateModal.value = true;
};

const deleteLead = (lead) => {
    if (confirm('Are you sure you want to delete this lead?')) {
        useForm({}).delete(route('crm.leads.destroy', lead.id));
    }
};

const getStatusColor = (status) => {
    const colors = {
        'new': 'bg-blue-500/10 text-blue-400 border-blue-500/20',
        'contacted': 'bg-yellow-500/10 text-yellow-400 border-yellow-500/20',
        'qualified': 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
        'lost': 'bg-rose-500/10 text-rose-400 border-rose-500/20'
    };
    return colors[status] || 'bg-slate-500/10 text-slate-400 border-slate-500/20';
};
</script>

<template>
    <Head :title="title" />

    <AppLayout :title="title">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <UserPlusIcon class="h-6 w-6 text-cyan-600 dark:text-cyan-400" />
                        LEADS DATABASE
                    </h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Manage and track potential customer acquisitions.</p>
                </div>
                <button @click="showCreateModal = true; editingLead = null; form.reset()" 
                    class="px-4 py-2 bg-cyan-600 dark:bg-cyan-500 hover:bg-cyan-500 dark:hover:bg-cyan-400 text-white dark:text-slate-900 font-bold rounded-lg transition-all flex items-center gap-2 shadow-lg shadow-cyan-500/30">
                    <UserPlusIcon class="h-5 w-5" />
                    NEW LEAD
                </button>
            </div>

            <!-- Main Content -->
            <div class="grid grid-cols-1 gap-6">
                <div class="glass-card p-6">
                    <!-- Filters -->
                    <div class="flex items-center gap-4 mb-6">
                        <div class="relative flex-1 max-w-md">
                            <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400 dark:text-slate-500" />
                            <input type="text" placeholder="Search leads..." 
                                class="w-full bg-slate-50 dark:bg-black/20 border border-slate-200 dark:border-white/10 rounded-lg pl-10 pr-4 py-2 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:border-cyan-500/50 focus:ring-1 focus:ring-cyan-500/50 transition-all outline-none">
                        </div>
                        <button class="p-2 text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white border border-slate-200 dark:border-white/10 rounded-lg hover:bg-slate-100 dark:hover:bg-white/5 transition-all">
                            <FunnelIcon class="h-5 w-5" />
                        </button>
                    </div>

                    <!-- Table -->
                    <div class="overflow-x-auto overflow-y-auto max-h-[600px]">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="text-xs text-slate-500 font-bold uppercase tracking-wider border-b border-slate-200 dark:border-white/10">
                                    <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3">Name / Company</th>
                                    <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3">Contact</th>
                                    <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3">Source</th>
                                    <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3">Status</th>
                                    <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                                <tr v-for="lead in leads" :key="lead.id" class="group hover:bg-slate-50 dark:hover:bg-white/5 transition-all">
                                    <td class="px-4 py-3">
                                        <div class="font-bold text-slate-900 dark:text-white">{{ lead.name }}</div>
                                        <div class="text-xs text-slate-500 dark:text-slate-400">{{ lead.company || 'No Company' }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">
                                        <div>{{ lead.email }}</div>
                                        <div class="text-xs text-slate-400 dark:text-slate-500">{{ lead.phone }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-400">{{ lead.source || '-' }}</td>
                                    <td class="px-4 py-3">
                                        <span :class="['px-2 py-1 rounded text-[10px] font-bold uppercase tracking-wider border', getStatusColor(lead.status)]">
                                            {{ lead.status }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-all">
                                            <button @click="editLead(lead)" class="p-1.5 text-slate-400 hover:text-cyan-600 dark:hover:text-cyan-400 hover:bg-cyan-50 dark:hover:bg-cyan-500/10 rounded transition-all">
                                                <PencilSquareIcon class="h-4 w-4" />
                                            </button>
                                            <button @click="deleteLead(lead)" class="p-1.5 text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-500/10 rounded transition-all">
                                                <TrashIcon class="h-4 w-4" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="leads.length === 0">
                                    <td colspan="5" class="px-4 py-12 text-center text-slate-500 uppercase tracking-widest text-sm">
                                        No leads found in database
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create/Edit Modal (Simplified) -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" @click="showCreateModal = false"></div>
            <div class="relative w-full max-w-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-white/10 rounded-2xl p-6 shadow-2xl">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-6">{{ editingLead ? 'Edit Lead' : 'Create New Lead' }}</h3>
                
                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-1">Full Name</label>
                        <input v-model="form.name" type="text" class="w-full bg-slate-50 dark:bg-black/20 border border-slate-200 dark:border-white/10 rounded-lg px-4 py-2 text-slate-900 dark:text-white focus:border-cyan-500 outline-none transition-colors" required>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-1">Company</label>
                            <input v-model="form.company" type="text" class="w-full bg-slate-50 dark:bg-black/20 border border-slate-200 dark:border-white/10 rounded-lg px-4 py-2 text-slate-900 dark:text-white focus:border-cyan-500 outline-none transition-colors">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-1">Status</label>
                            <select v-model="form.status" class="w-full bg-slate-50 dark:bg-black/20 border border-slate-200 dark:border-white/10 rounded-lg px-4 py-2 text-slate-900 dark:text-white focus:border-cyan-500 outline-none transition-colors">
                                <option value="new">New</option>
                                <option value="contacted">Contacted</option>
                                <option value="qualified">Qualified</option>
                                <option value="lost">Lost</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-1">Email</label>
                            <input v-model="form.email" type="email" class="w-full bg-slate-50 dark:bg-black/20 border border-slate-200 dark:border-white/10 rounded-lg px-4 py-2 text-slate-900 dark:text-white focus:border-cyan-500 outline-none transition-colors">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-1">Phone</label>
                            <input v-model="form.phone" type="text" class="w-full bg-slate-50 dark:bg-black/20 border border-slate-200 dark:border-white/10 rounded-lg px-4 py-2 text-slate-900 dark:text-white focus:border-cyan-500 outline-none transition-colors">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-1">Source</label>
                        <input v-model="form.source" type="text" placeholder="e.g. LinkedIn, Website, Referral" class="w-full bg-slate-50 dark:bg-black/20 border border-slate-200 dark:border-white/10 rounded-lg px-4 py-2 text-slate-900 dark:text-white focus:border-cyan-500 outline-none transition-colors">
                    </div>

                    <div class="flex justify-end gap-3 mt-6">
                        <button type="button" @click="showCreateModal = false" class="px-4 py-2 text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white transition-colors">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-cyan-600 dark:bg-cyan-500 hover:bg-cyan-500 dark:hover:bg-cyan-400 text-white dark:text-slate-900 font-bold rounded-lg transition-colors">
                            {{ editingLead ? 'Update Lead' : 'Create Lead' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
