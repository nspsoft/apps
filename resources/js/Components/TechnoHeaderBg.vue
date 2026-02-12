<script setup>
import { onMounted, ref } from 'vue';

const svgRef = ref(null);

// Generate some random circuit paths
const paths = [
    "M -10,32 L 100,32 L 120,10 L 300,10",
    "M 50,64 L 80,40 L 200,40 L 220,60 L 400,60",
    "M 150,0 L 170,20 L 350,20 L 370,50 L 500,50",
    "M 250,64 L 280,30 L 450,30 L 480,10 L 600,10",
    "M 400,0 L 420,15 L 550,15 L 580,45 L 750,45",
    "M 600,64 L 630,35 L 800,35",
    "M 700,0 L 730,25 L 900,25 L 920,55 L 1100,55",
    "M 850,64 L 880,40 L 1050,40 L 1080,15 L 1200,15",
    "M 1000,0 L 1030,30 L 1200,30 L 1230,60 L 1400,60",
    "M 1150,64 L 1180,20 L 1350,20 L 1380,45 L 1500,45",
    "M 1300,0 L 1330,25 L 1500,25",
    "M 1450,64 L 1480,35 L 1650,35 L 1680,10 L 1800,10",
    "M 1600,0 L 1630,30 L 1800,30 L 1830,55 L 2000,55",
];

const pulses = ref([]);

onMounted(() => {
    // We can add some dynamic behavior here if needed, 
    // but pure CSS is usually enough and more performant.
});

</script>

<template>
    <div class="absolute inset-0 overflow-hidden pointer-events-none select-none z-0 bg-slate-900/40 dark:bg-slate-950/40 backdrop-blur-[2px]">
        <!-- Hexagon Pattern Overlay -->
        <div class="absolute inset-0 opacity-[0.03] dark:opacity-[0.05]" 
             style="background-image: url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iMTA0IiB2aWV3Qm94PSIwIDAgNjAgMTA0IiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPjxwYXRoIGQ9Ik02MCA0OUwzMCA2NkwwIDQ5VjE2TDMwIDBMNjAgMTZWNDl6TTMwIDY4diMzNE0wIDY2bDMwIDM0IDMwLTM0TTI5IDE2TDAtM00zMSAxNkw2MC0zIiBzdHJva2U9IiNmZmYiIGZpbGw9Im5vbmUiLz48L3N2Zz4='); background-size: 30px 52px;">
        </div>

        <svg viewBox="0 0 1920 64" preserveAspectRatio="xMidYMid slice" class="w-full h-full opacity-30 dark:opacity-50">
            <defs>
                <linearGradient id="flow-blue" x1="0%" y1="0%" x2="100%" y2="0%">
                    <stop offset="0%" stop-color="transparent" />
                    <stop offset="50%" stop-color="#3b82f6" />
                    <stop offset="100%" stop-color="transparent" />
                </linearGradient>
                <linearGradient id="flow-red" x1="0%" y1="0%" x2="100%" y2="0%">
                    <stop offset="0%" stop-color="transparent" />
                    <stop offset="50%" stop-color="#dc2626" />
                    <stop offset="100%" stop-color="transparent" />
                </linearGradient>
                
                <filter id="glow" x="-20%" y="-20%" width="140%" height="140%">
                    <feGaussianBlur stdDeviation="2" result="blur" />
                    <feComposite in="SourceGraphic" in2="blur" operator="over" />
                </filter>
            </defs>

            <!-- Static Circuit Lines -->
            <g stroke="currentColor" fill="none" stroke-width="0.5" class="text-slate-500/20 dark:text-slate-400/10">
                <path v-for="(p, i) in paths" :key="'static-'+i" :d="p" />
            </g>

            <!-- Animated Fiber Optic Flows -->
            <g filter="url(#glow)">
                <path v-for="(p, i) in paths" :key="'flow-'+i" 
                      :d="p" 
                      fill="none" 
                      :stroke="i % 3 === 0 ? 'url(#flow-red)' : 'url(#flow-blue)'"
                      stroke-width="1.5"
                      stroke-dasharray="100 1000"
                      class="flow-path"
                      :style="{ animationDuration: (3 + (i % 5)) + 's', animationDelay: (i * 0.5) + 's' }"
                />
            </g>
        </svg>

        <!-- Final Glow Overlay -->
        <div class="absolute inset-x-0 bottom-0 h-[1px] bg-gradient-to-r from-transparent via-cyan-500/50 to-transparent opacity-30"></div>
    </div>
</template>

<style scoped>
@keyframes flow {
    0% {
        stroke-dashoffset: 1100;
    }
    100% {
        stroke-dashoffset: -100;
    }
}

.flow-path {
    animation: flow linear infinite;
}

/* Add a slow pulse to the entire SVG */
svg {
    animation: pulse 10s ease-in-out infinite alternate;
}

@keyframes pulse {
    0% { opacity: 0.3; }
    100% { opacity: 0.5; }
}

:deep(.dark) svg {
    animation: pulse-dark 10s ease-in-out infinite alternate;
}

@keyframes pulse-dark {
    0% { opacity: 0.5; }
    100% { opacity: 0.8; }
}
</style>
