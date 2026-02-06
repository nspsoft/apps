<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, nextTick, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    PaperAirplaneIcon, 
    MagnifyingGlassIcon,
    ChatBubbleLeftRightIcon,
    UserCircleIcon,
    CheckCircleIcon,
    ClockIcon,
    ArrowPathIcon
} from '@heroicons/vue/24/outline';
import axios from 'axios';

const props = defineProps({
    contacts: Array,
});

const search = ref('');
const activeContact = ref(null);
const messages = ref([]);
const isLoadingMessages = ref(false);
const chatContainer = ref(null);

const form = useForm({
    phone: '',
    message: '',
});

// Filter contacts
const filteredContacts = computed(() => {
    if (!search.value) return props.contacts;
    return props.contacts.filter(c => 
        c.phone.includes(search.value) || 
        (c.customer?.name || '').toLowerCase().includes(search.value.toLowerCase())
    );
});

// Select contact & load history
const selectContact = async (contact) => {
    activeContact.value = contact;
    form.phone = contact.phone;
    isLoadingMessages.value = true;
    
    try {
        const response = await axios.get(route('sales.whatsapp.history', contact.phone));
        messages.value = response.data;
        scrollToBottom();
    } catch (error) {
        console.error('Failed to load history', error);
    } finally {
        isLoadingMessages.value = false;
    }
};

// Send message
const sendMessage = () => {
    if (!form.message.trim()) return;

    form.post(route('sales.whatsapp.send'), {
        preserveScroll: true,
        onSuccess: () => {
            // Optimistic update
            messages.value.push({
                direction: 'outgoing',
                message: form.message,
                created_at: new Date().toISOString(),
                intent: 'manual_reply'
            });
            form.message = '';
            scrollToBottom();
        }
    });
};

const scrollToBottom = async () => {
    await nextTick();
    if (chatContainer.value) {
        chatContainer.value.scrollTop = chatContainer.value.scrollHeight;
    }
};

const formatDate = (dateString) => {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
        day: '2-digit',
        month: 'short'
    }).format(date);
};

// Auto refresh history every 10s if active
let pollingInterval;
onMounted(() => {
    pollingInterval = setInterval(() => {
        if (activeContact.value) {
            axios.get(route('sales.whatsapp.history', activeContact.value.phone))
                .then(res => {
                    // Simple check if new messages
                    if (res.data.length > messages.value.length) {
                        messages.value = res.data;
                        scrollToBottom();
                    }
                });
        }
    }, 10000);
});

</script>

<template>
    <Head title="WhatsApp Center" />

    <AppLayout title="WhatsApp Center">
        <div class="h-[calc(100vh-12rem)] w-full flex gap-6 overflow-hidden">
            
            <!-- Chat List (Left) -->
            <div class="w-1/3 flex flex-col gap-4">
                <!-- Search -->
                <div class="relative">
                    <input 
                        v-model="search"
                        type="text" 
                        placeholder="Search chats..." 
                        class="w-full bg-slate-900/50 border border-slate-700 rounded-xl py-3 pl-10 pr-4 text-white placeholder:text-slate-500 focus:ring-2 focus:ring-cyan-500/50 font-sans"
                    />
                    <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-500" />
                </div>

                <!-- Contact List -->
                <div class="flex-1 overflow-y-auto pr-2 space-y-2 custom-scrollbar">
                    <button 
                        v-for="contact in filteredContacts" 
                        :key="contact.phone"
                        @click="selectContact(contact)"
                        class="w-full text-left p-4 rounded-2xl border transition-all duration-200 group relative overflow-hidden"
                        :class="activeContact?.phone === contact.phone 
                            ? 'bg-cyan-500/10 border-cyan-500/50 shadow-[0_0_20px_rgba(6,182,212,0.15)]' 
                            : 'bg-slate-900/40 border-slate-800 hover:border-slate-600 hover:bg-slate-800/40'"
                    >
                        <!-- Glow Effect -->
                        <div v-if="activeContact?.phone === contact.phone" class="absolute inset-0 bg-gradient-to-r from-cyan-500/10 to-transparent opacity-50"></div>

                        <div class="relative flex justify-between items-start mb-1">
                            <h3 class="font-bold text-slate-200 group-hover:text-white transition-colors">
                                {{ contact.customer?.name || contact.phone }}
                            </h3>
                            <span class="text-[10px] text-slate-500 font-mono mt-1">
                                {{ formatDate(contact.last_activity) }}
                            </span>
                        </div>
                        
                        <p class="text-sm text-slate-400 line-clamp-1 group-hover:text-cyan-200/70 transition-colors">
                            {{ contact.last_message }}
                        </p>

                        <div class="mt-2 flex items-center gap-2">
                            <span class="px-2 py-0.5 rounded text-[10px] font-medium bg-slate-800 text-slate-400 border border-slate-700">
                                {{ contact.last_intent || 'Unknown' }}
                            </span>
                        </div>
                    </button>
                    
                    <div v-if="filteredContacts.length === 0" class="text-center py-10 text-slate-500">
                        No chats found
                    </div>
                </div>
            </div>

            <!-- Chat Area (Center & Right) -->
            <div class="flex-1 flex gap-6">
                <!-- Main Chat Window -->
                <div class="flex-1 bg-slate-900/40 border border-slate-800 rounded-3xl flex flex-col overflow-hidden relative backdrop-blur-sm">
                    
                    <div v-if="activeContact" class="flex flex-col h-full">
                        <!-- Header -->
                        <div class="p-4 border-b border-slate-800 bg-slate-900/80 flex justify-between items-center backdrop-blur-md z-10">
                            <div class="flex items-center gap-3">
                                <div class="h-10 w-10 rounded-full bg-gradient-to-br from-cyan-500 to-blue-600 flex items-center justify-center text-white font-bold shadow-lg shadow-cyan-500/20">
                                    {{ (activeContact.customer?.name || activeContact.phone).charAt(0) }}
                                </div>
                                <div>
                                    <h2 class="font-bold text-white text-lg">
                                        {{ activeContact.customer?.name || activeContact.phone }}
                                    </h2>
                                    <p class="text-xs text-cyan-400 font-mono flex items-center gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                        WhatsApp Connected
                                    </p>
                                </div>
                            </div>
                            <button @click="selectContact(activeContact)" class="p-2 hover:bg-slate-800 rounded-full text-slate-400 hover:text-white transition-colors">
                                <ArrowPathIcon class="h-5 w-5" :class="{ 'animate-spin': isLoadingMessages }" />
                            </button>
                        </div>

                        <!-- Messages -->
                        <div ref="chatContainer" class="flex-1 overflow-y-auto p-6 space-y-6 custom-scrollbar bg-dots-pattern">
                            <div v-for="msg in messages" :key="msg.id" class="flex flex-col" :class="msg.direction === 'outgoing' ? 'items-end' : 'items-start'">
                                <div 
                                    class="max-w-[70%] p-4 rounded-2xl relative group transition-all duration-300 hover:scale-[1.01]"
                                    :class="msg.direction === 'outgoing' 
                                        ? 'bg-gradient-to-br from-cyan-600 to-blue-600 text-white rounded-tr-sm shadow-[0_5px_15px_rgba(8,145,178,0.2)]' 
                                        : 'bg-slate-800 border border-slate-700 text-slate-200 rounded-tl-sm shadow-lg'"
                                >
                                    <p class="whitespace-pre-wrap leading-relaxed">{{ msg.message }}</p>
                                    
                                    <div class="mt-2 flex items-center justify-end gap-2 opacity-70">
                                        <span class="text-[10px] font-mono">{{ formatDate(msg.created_at) }}</span>
                                        <CheckCircleIcon v-if="msg.direction === 'outgoing'" class="h-3 w-3" />
                                    </div>

                                    <!-- Intent Badge for Incoming -->
                                    <div v-if="msg.direction === 'incoming' && msg.intent" class="absolute -top-2 -right-2">
                                        <span class="bg-slate-950 border border-slate-700 text-[10px] px-2 py-0.5 rounded-full text-cyan-400 shadow-xl">
                                            {{ msg.intent }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Input -->
                        <div class="p-4 bg-slate-900/80 border-t border-slate-800 backdrop-blur-md">
                            <form @submit.prevent="sendMessage" class="relative">
                                <input 
                                    v-model="form.message"
                                    type="text" 
                                    placeholder="Type a message to reply manually..." 
                                    class="w-full bg-slate-950 border-0 rounded-xl py-4 pl-4 pr-14 text-white placeholder:text-slate-600 focus:ring-2 focus:ring-cyan-500/50 shadow-inner"
                                    :disabled="form.processing"
                                />
                                <button 
                                    type="submit" 
                                    :disabled="form.processing || !form.message.trim()"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 p-2 bg-cyan-500 hover:bg-cyan-400 text-white rounded-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed shadow-lg shadow-cyan-500/20"
                                >
                                    <PaperAirplaneIcon class="h-5 w-5" />
                                </button>
                            </form>
                            <p class="text-[10px] text-slate-500 mt-2 text-center">
                                Tip: Sending a manual message will not stop the bot from replying to future messages.
                            </p>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-else class="h-full flex flex-col items-center justify-center text-slate-500 p-8 text-center">
                        <div class="h-40 w-40 rounded-full bg-slate-800/50 flex items-center justify-center mb-6 border border-slate-700 shadow-2xl animate-pulse-slow">
                            <ChatBubbleLeftRightIcon class="h-20 w-20 text-slate-600" />
                        </div>
                        <h3 class="text-xl font-bold text-white mb-2">Select a Conversation</h3>
                        <p class="max-w-xs mx-auto">Click on a contact from the left panel to view history and chat manually.</p>
                    </div>
                </div>

                <!-- Info Panel (Right) - Only visible when chat active -->
                <div v-if="activeContact" class="w-80 hidden xl:flex flex-col gap-4">
                    <!-- Customer Card -->
                    <div class="bg-slate-900/40 border border-slate-800 rounded-3xl p-6 backdrop-blur-sm">
                        <div class="flex items-center gap-4 mb-6">
                            <div class="h-16 w-16 rounded-2xl bg-slate-800 flex items-center justify-center text-cyan-400">
                                <UserCircleIcon class="h-10 w-10" />
                            </div>
                            <div>
                                <div class="text-[10px] uppercase tracking-wider text-slate-500 font-bold mb-1">Customer</div>
                                <h3 class="text-lg font-bold text-white leading-tight">
                                    {{ activeContact.customer?.name || 'Unregistered' }}
                                </h3>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <span class="text-xs text-slate-500 block mb-1">Phone Number</span>
                                <span class="text-sm font-mono text-cyan-300 bg-cyan-950/30 px-2 py-1 rounded border border-cyan-900/50">
                                    {{ activeContact.phone }}
                                </span>
                            </div>
                            
                            <div v-if="activeContact.last_intent">
                                <span class="text-xs text-slate-500 block mb-1">Last Detected Intent</span>
                                <span class="text-xs font-bold text-white bg-slate-800 px-3 py-1.5 rounded-lg border border-slate-700 inline-block">
                                    ✨ {{ activeContact.last_intent }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions (Placeholder) -->
                    <div class="bg-gradient-to-br from-indigo-900/20 to-purple-900/20 border border-indigo-500/20 rounded-3xl p-6 backdrop-blur-sm">
                        <h4 class="text-sm font-bold text-indigo-300 mb-4 flex items-center gap-2">
                             Quick Actions
                        </h4>
                        <div class="space-y-2">
                            <button class="w-full text-left px-4 py-3 rounded-xl bg-slate-900/50 hover:bg-indigo-500/20 border border-slate-700 hover:border-indigo-500/50 text-sm text-slate-300 transition-all flex items-center justify-between group">
                                Create Sales Order
                                <ArrowPathIcon class="h-4 w-4 opacity-0 group-hover:opacity-100 transition-opacity" />
                            </button>
                            <button class="w-full text-left px-4 py-3 rounded-xl bg-slate-900/50 hover:bg-indigo-500/20 border border-slate-700 hover:border-indigo-500/50 text-sm text-slate-300 transition-all flex items-center justify-between group">
                                View Profile
                                <ArrowPathIcon class="h-4 w-4 opacity-0 group-hover:opacity-100 transition-opacity" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #334155;
    border-radius: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #475569;
}

.bg-dots-pattern {
    background-image: radial-gradient(#334155 1px, transparent 1px);
    background-size: 20px 20px;
}
.animate-pulse-slow {
    animation: pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>
