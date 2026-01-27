<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    UserCircleIcon,
    KeyIcon,
    CheckCircleIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    user: Object,
});

const profileForm = useForm({
    name: props.user?.name || '',
    email: props.user?.email || '',
});

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updateProfile = () => {
    profileForm.put('/profile', {
        preserveScroll: true,
        onSuccess: () => {
            // Success handled by flash message
        },
    });
};

const updatePassword = () => {
    passwordForm.put('/profile/password', {
        preserveScroll: true,
        onSuccess: () => {
            passwordForm.reset();
        },
    });
};
</script>

<template>
    <Head title="Your Profile" />
    
    <AppLayout title="Your Profile">
        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Profile Information -->
            <div class="glass-card rounded-2xl p-6">
                <div class="flex items-center gap-4 mb-6">
                    <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 text-slate-900 dark:text-white text-2xl font-bold">
                        {{ user?.name?.charAt(0)?.toUpperCase() || 'U' }}
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                            <UserCircleIcon class="h-5 w-5 text-slate-500 dark:text-slate-400" />
                            Profile Information
                        </h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Update your account's profile information and email address.</p>
                    </div>
                </div>

                <form @submit.prevent="updateProfile" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-1">Name</label>
                            <input 
                                v-model="profileForm.name" 
                                type="text" 
                                class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                                required
                            />
                            <p v-if="profileForm.errors.name" class="text-red-400 text-xs mt-1">{{ profileForm.errors.name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-1">Email</label>
                            <input 
                                v-model="profileForm.email" 
                                type="email" 
                                class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                                required
                            />
                            <p v-if="profileForm.errors.email" class="text-red-400 text-xs mt-1">{{ profileForm.errors.email }}</p>
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button 
                            type="submit" 
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-blue-600 text-sm font-semibold text-white dark:text-white hover:bg-blue-500 transition-colors"
                            :disabled="profileForm.processing"
                        >
                            <CheckCircleIcon class="h-4 w-4" />
                            {{ profileForm.processing ? 'Saving...' : 'Save Changes' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Update Password -->
            <div class="glass-card rounded-2xl p-6">
                <div class="flex items-center gap-4 mb-6">
                    <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-50 dark:bg-slate-800 text-amber-400">
                        <KeyIcon class="h-8 w-8" />
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                            <KeyIcon class="h-5 w-5 text-slate-500 dark:text-slate-400" />
                            Update Password
                        </h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Ensure your account is using a long, random password to stay secure.</p>
                    </div>
                </div>

                <form @submit.prevent="updatePassword" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-1">Current Password</label>
                        <input 
                            v-model="passwordForm.current_password" 
                            type="password" 
                            class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                            required
                        />
                        <p v-if="passwordForm.errors.current_password" class="text-red-400 text-xs mt-1">{{ passwordForm.errors.current_password }}</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-1">New Password</label>
                            <input 
                                v-model="passwordForm.password" 
                                type="password" 
                                class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                                required
                            />
                            <p v-if="passwordForm.errors.password" class="text-red-400 text-xs mt-1">{{ passwordForm.errors.password }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-1">Confirm Password</label>
                            <input 
                                v-model="passwordForm.password_confirmation" 
                                type="password" 
                                class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                                required
                            />
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button 
                            type="submit" 
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-amber-600 text-sm font-semibold text-slate-900 dark:text-white hover:bg-amber-500 transition-colors"
                            :disabled="passwordForm.processing"
                        >
                            <KeyIcon class="h-4 w-4" />
                            {{ passwordForm.processing ? 'Updating...' : 'Update Password' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>



