<script setup>
import { computed } from 'vue';

const props = defineProps({
    tasks: Array,
    projectStart: String,
    projectEnd: String
});

// Simple Gantt calculation
const formatDate = (date) => new Date(date);

const getProgressWidth = (task) => {
    if (!props.projectStart || !props.projectEnd) return 0;
    const start = formatDate(props.projectStart);
    const end = formatDate(props.projectEnd);
    const taskStart = formatDate(task.start_date_plan);
    const taskEnd = formatDate(task.end_date_plan);

    const totalDuration = end - start;
    const taskDuration = taskEnd - taskStart;
    
    return (taskDuration / totalDuration) * 100;
};

const getProgressLeft = (task) => {
    if (!props.projectStart || !props.projectEnd) return 0;
    const start = formatDate(props.projectStart);
    const end = formatDate(props.projectEnd);
    const taskStart = formatDate(task.start_date_plan);

    const totalDuration = end - start;
    const offset = taskStart - start;
    
    return (offset / totalDuration) * 100;
};
</script>

<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between mb-2">
            <h4 class="text-xs font-black text-cyan-400 uppercase tracking-widest">TIMELINE VISUALIZATION</h4>
            <div class="flex items-center gap-4 text-[10px] text-slate-500">
                <div class="flex items-center gap-1.5"><div class="w-2 h-2 bg-cyan-500/50 rounded-sm"></div> PLAN</div>
                <div class="flex items-center gap-1.5"><div class="w-2 h-2 bg-emerald-500 rounded-sm"></div> ACTUAL</div>
            </div>
        </div>

        <div class="hud-panel p-6 bg-[#0a0a16]/40 border border-white/5 rounded-xl overflow-x-auto">
            <div class="min-w-[800px] relative">
                <!-- Timeline Header (Months placeholders) -->
                <div class="grid grid-cols-12 border-b border-white/10 pb-2 mb-4">
                    <div v-for="i in 12" :key="i" class="text-[8px] text-slate-600 font-bold border-l border-white/5 pl-2 tracking-tighter">
                        PHASE_{{ i.toString().padStart(2, '0') }}
                    </div>
                </div>

                <!-- Grid Guide -->
                <div class="absolute top-8 bottom-0 left-0 right-0 grid grid-cols-12 pointer-events-none">
                    <div v-for="i in 12" :key="i" class="border-l border-white/5"></div>
                </div>

                <!-- Task Bars -->
                <div class="space-y-4 relative z-10">
                    <div v-for="task in tasks" :key="task.id" class="group">
                        <div class="flex items-center gap-4">
                            <div class="w-32 text-[10px] text-slate-400 truncate uppercase font-bold">{{ task.name }}</div>
                            <div class="flex-1 h-6 relative bg-white/5 rounded overflow-hidden">
                                <!-- Plan Bar -->
                                <div 
                                    class="absolute h-4 top-1 bg-cyan-500/20 border border-cyan-500/30 rounded-sm transition-all"
                                    :style="{ 
                                        left: `${getProgressLeft(task)}%`, 
                                        width: `${getProgressWidth(task)}%` 
                                    }"
                                ></div>
                                <!-- Actual Bar (based on progress) -->
                                <div 
                                    class="absolute h-2 top-2 bg-emerald-500 rounded-full shadow-[0_0_8px_rgba(16,185,129,0.5)] transition-all"
                                    :style="{ 
                                        left: `${getProgressLeft(task)}%`, 
                                        width: `${(getProgressWidth(task) * task.progress) / 100}%` 
                                    }"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
