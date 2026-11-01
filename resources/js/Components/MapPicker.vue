<script setup>
import { onMounted, ref, onBeforeUnmount, watch, nextTick } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { XMarkIcon, MagnifyingGlassIcon } from '@heroicons/vue/24/outline';

// Prop to control visibility
const props = defineProps({
    show: Boolean,
    initialAddress: {
        type: String,
        default: ''
    },
    initialCoordinates: {
        type: Object,
        default: null, // { lat: number, lng: number }
    }
});

watch(() => props.show, (val) => {
    if (val) {
        nextTick(() => {
            setTimeout(() => {
                initMap();
                if (props.initialAddress) {
                    searchQuery.value = props.initialAddress;
                    searchLocation(true);
                }
            }, 100);
        });
    } else {
        if (map.value) {
            map.value.remove();
            map.value = null;
        }
    }
});

const emit = defineEmits(['close', 'confirm']);

const mapContainer = ref(null);
const map = ref(null);
const marker = ref(null);
const searchQuery = ref('');
const searchResults = ref([]);
const isSearching = ref(false);

// Default User Location (will try to get real location)
const center = ref([-6.36356577, 107.14537203]); // Default to plant coordinates
const address = ref('');
const addressDetails = ref({});

const initMap = () => {
    if (!mapContainer.value) return;

    // Fix Leaflet Icon Issue in Webpack/Vite
    delete L.Icon.Default.prototype._getIconUrl;
    L.Icon.Default.mergeOptions({
        iconRetinaUrl: new URL('leaflet/dist/images/marker-icon-2x.png', import.meta.url).href,
        iconUrl: new URL('leaflet/dist/images/marker-icon.png', import.meta.url).href,
        shadowUrl: new URL('leaflet/dist/images/marker-shadow.png', import.meta.url).href,
    });

    if (props.initialCoordinates && props.initialCoordinates.lat && props.initialCoordinates.lng) {
        center.value = [parseFloat(props.initialCoordinates.lat), parseFloat(props.initialCoordinates.lng)];
    }

    map.value = L.map(mapContainer.value).setView(center.value, props.initialCoordinates ? 17 : 14);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map.value);

    // Add Draggable Marker
    marker.value = L.marker(center.value, { draggable: true }).addTo(map.value);

    // Initial addressDetails
    addressDetails.value = {
        address: `${center.value[0]}, ${center.value[1]}`,
        latitude: center.value[0],
        longitude: center.value[1],
    };

    // Event: Marker Drag End
    marker.value.on('dragend', async () => {
        const position = marker.value.getLatLng();
        await reverseGeocode(position.lat, position.lng);
    });

    // Event: Map Click
    map.value.on('click', async (e) => {
        marker.value.setLatLng(e.latlng);
        await reverseGeocode(e.latlng.lat, e.latlng.lng);
    });
    
    if (props.initialCoordinates && props.initialCoordinates.lat && props.initialCoordinates.lng) {
        reverseGeocode(props.initialCoordinates.lat, props.initialCoordinates.lng);
    } else if (!props.initialAddress && navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(position => {
            const { latitude, longitude } = position.coords;
            const latLng = [latitude, longitude];
            map.value.setView(latLng, 15);
            marker.value.setLatLng(latLng);
            reverseGeocode(latitude, longitude);
        });
    }
};

const reverseGeocode = async (lat, lng) => {
    addressDetails.value = {
        address: `Koordinat: ${lat.toFixed(8)}, ${lng.toFixed(8)}`,
        latitude: lat,
        longitude: lng,
    };
    try {
        const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`);
        const data = await response.json();
        
        address.value = data.display_name || `Koordinat: ${lat.toFixed(8)}, ${lng.toFixed(8)}`;
        addressDetails.value = {
            address: data.display_name || `Koordinat: ${lat.toFixed(8)}, ${lng.toFixed(8)}`,
            latitude: lat,
            longitude: lng,
            city: data.address?.city || data.address?.town || data.address?.village || '',
            state: data.address?.state || '',
            postal_code: data.address?.postcode || '',
            country: data.address?.country || '',
            full_details: data.address
        };
    } catch (error) {
        console.error('Geocoding error:', error);
        address.value = `Koordinat: ${lat.toFixed(8)}, ${lng.toFixed(8)}`;
    }
};

const searchLocation = async (autoSelect = false) => {
    if (!searchQuery.value) return;
    
    isSearching.value = true;
    
    const performSearch = async (query) => {
        try {
            const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}`);
            return await response.json();
        } catch (e) {
            console.error("Fetch error for query:", query, e);
            return [];
        }
    };

    try {
        let data = await performSearch(searchQuery.value);
        
        if (autoSelect && data.length === 0 && searchQuery.value.includes(',')) {
            const parts = searchQuery.value.split(',').filter(p => p.trim().length > 0);
            if (parts.length >= 2) {
                const simpleQuery = parts.slice(-2).join(', ');
                data = await performSearch(simpleQuery);
            } 
            if (data.length === 0 && parts.length > 0) {
                 const lastPart = parts[parts.length - 1];
                 data = await performSearch(lastPart);
            }
        }

        searchResults.value = data;

        if (autoSelect && data.length > 0) {
            selectResult(data[0]);
        }
    } catch (error) {
        console.error('Search error:', error);
    } finally {
        isSearching.value = false;
    }
};

const selectResult = (result) => {
    const lat = parseFloat(result.lat);
    const lon = parseFloat(result.lon);
    const latLng = [lat, lon];
    
    map.value.setView(latLng, 16);
    marker.value.setLatLng(latLng);
    reverseGeocode(lat, lon);
    
    searchResults.value = [];
};

const confirmSelection = () => {
    emit('confirm', addressDetails.value);
    emit('close');
};

onBeforeUnmount(() => {
    if (map.value) {
        map.value.remove();
    }
});
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-[110] overflow-y-auto px-4 py-6 sm:px-0 flex items-center justify-center">
        <!-- Backdrop -->
        <div class="fixed inset-0 transform transition-all" @click="$emit('close')">
            <div class="absolute inset-0 bg-slate-900/80 backdrop-blur-sm"></div>
        </div>

        <!-- Modal Content -->
        <div class="relative bg-slate-900 rounded-2xl border border-slate-700 shadow-2xl w-full max-w-4xl overflow-hidden transform transition-all h-[90vh] flex flex-col">
            <!-- Header -->
            <div class="px-6 py-4 border-b border-slate-700 flex items-center justify-between bg-slate-800/60">
                <div class="flex items-center gap-2">
                    <span class="text-xl">🗺️</span>
                    <h3 class="text-base font-bold text-white">Pilih Titik Lokasi dari Peta (Map Picker)</h3>
                </div>
                <button @click="$emit('close')" class="text-slate-400 hover:text-white transition-colors p-1 rounded-md hover:bg-slate-800">
                    <XMarkIcon class="h-6 w-6" />
                </button>
            </div>

            <!-- Toolbar -->
            <div class="p-3 bg-slate-800/40 flex gap-3 border-b border-slate-700 z-[1000] relative">
                <div class="relative flex-1">
                    <input 
                        v-model="searchQuery" 
                        @keyup.enter="searchLocation"
                        type="text" 
                        placeholder="Cari lokasi, jalan, atau kawasan industri (tekan Enter)..." 
                        class="w-full bg-slate-900 border-slate-700 rounded-lg py-2 pl-3 pr-10 text-xs text-white placeholder:text-slate-500 focus:ring-2 focus:ring-blue-500/50"
                    />
                    <button 
                        @click="searchLocation"
                        class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-blue-400 p-1"
                    >
                        <MagnifyingGlassIcon class="h-4 w-4" />
                    </button>

                    <!-- Search Results -->
                    <div v-if="searchResults.length > 0" class="absolute top-full left-0 right-0 mt-1 bg-slate-800 border border-slate-700 rounded-lg shadow-xl overflow-hidden max-h-52 overflow-y-auto z-[2000]">
                        <button 
                            v-for="result in searchResults" 
                            :key="result.place_id"
                            @click="selectResult(result)"
                            class="w-full text-left px-3 py-2 text-xs text-slate-300 hover:bg-slate-700 hover:text-white border-b border-slate-700 last:border-0 transition-colors"
                        >
                            {{ result.display_name }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Map Container -->
            <div class="flex-1 relative bg-slate-800">
                <div ref="mapContainer" class="absolute inset-0 z-0"></div>
                
                <!-- Selected Address Overlay -->
                <div class="absolute bottom-5 left-5 right-5 bg-slate-900/95 backdrop-blur border border-slate-700 p-3.5 rounded-xl z-[500] shadow-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="space-y-1 max-w-xl">
                        <div class="flex items-center gap-2">
                            <span class="text-[11px] text-slate-400 font-bold uppercase tracking-wider">Koordinat Terpilih:</span>
                            <span v-if="addressDetails.latitude" class="text-xs font-mono font-bold text-cyan-400 bg-cyan-950/60 px-2 py-0.5 rounded border border-cyan-800/60">
                                {{ Number(addressDetails.latitude).toFixed(8) }}, {{ Number(addressDetails.longitude).toFixed(8) }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-300 line-clamp-1 leading-relaxed">
                            {{ address || 'Klik di mana saja pada peta atau geser pin merah ke titik checkpoint...' }}
                        </p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <button 
                            @click="$emit('close')"
                            class="px-3.5 py-1.5 rounded-lg bg-slate-800 text-slate-300 hover:text-white hover:bg-slate-700 text-xs font-semibold transition border border-slate-700"
                        >
                            Batal
                        </button>
                        <button 
                            @click="confirmSelection"
                            :disabled="!addressDetails.latitude"
                            class="px-4 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold shadow-lg shadow-blue-500/20 disabled:opacity-50 disabled:cursor-not-allowed transition"
                        >
                            ✓ Gunakan Titik Ini
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
.leaflet-container {
    background: #1e293b;
}
</style>
