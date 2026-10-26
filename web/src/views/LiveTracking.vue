<template>
  <div style="height: calc(100vh - 145px); margin-top: -24px; margin-bottom: -60px;">
    <!-- Map Container -->
    <div id="map" class="w-100 h-100" style="z-index: 1;"></div>
  </div>
</template>

<script setup>
import { shallowRef, onMounted, onUnmounted } from 'vue';
import { useAuthStore } from '../stores/auth';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import api from '../api';
import { toast } from 'vue3-toastify';

const auth = useAuthStore();
const map = shallowRef(null);
const markers = shallowRef({}); // Simpan marker berdasarkan employee_id
const echo = shallowRef(null);

// Fix default icon Leaflet di Vue/Vite
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
  iconRetinaUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon-2x.png',
  iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon.png',
  shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
});

// Custom Icon untuk Employee
const createEmployeeIcon = (name) => {
  const initial = name ? name.charAt(0).toUpperCase() : '?';
  return L.divIcon({
    className: 'custom-employee-marker',
    html: `<div style="
      background-color: #3b82f6; 
      color: white; 
      width: 30px; 
      height: 30px; 
      border-radius: 50%; 
      display: flex; 
      align-items: center; 
      justify-content: center;
      border: 2px solid white;
      box-shadow: 0 2px 5px rgba(0,0,0,0.3);
      font-weight: bold;
    ">${initial}</div>`,
    iconSize: [30, 30],
    iconAnchor: [15, 15],
    popupAnchor: [0, -15]
  });
};

const initMap = () => {
  // Default to Indonesia center
  map.value = L.map('map').setView([-2.5489, 118.0149], 5);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    maxZoom: 19
  }).addTo(map.value);
};

const loadInitialLocations = async () => {
  try {
    const res = await api.get('/employees', {
      params: { per_page: 100, is_active: 1 }
    });
    
    if (res.data?.success) {
      const employees = res.data.data.data || res.data.data;
      
      let bounds = L.latLngBounds();
      let hasValidLocation = false;
      
      employees.forEach(emp => {
        if (emp.last_latitude && emp.last_longitude) {
          updateMarker(emp.id, emp.name, emp.last_latitude, emp.last_longitude, emp.updated_at);
          bounds.extend([emp.last_latitude, emp.last_longitude]);
          hasValidLocation = true;
        }
      });
      
      if (hasValidLocation) {
        map.value.fitBounds(bounds, { padding: [50, 50], maxZoom: 15 });
      }
    }
  } catch (error) {
    console.error('Error loading initial locations:', error);
    toast.error('Gagal memuat data lokasi awal');
  }
};

const updateMarker = (id, name, lat, lng, updatedAt = null) => {
  const timeString = updatedAt 
    ? new Date(updatedAt).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta', day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) 
    : new Date().toLocaleString('id-ID', { timeZone: 'Asia/Jakarta', day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });

  if (markers.value[id]) {
    // Update existing marker
    const marker = markers.value[id];
    marker.setLatLng([lat, lng]);
    
    marker.setPopupContent(`
      <div class="text-center">
        <div class="font-bold text-slate-800">${name}</div>
        <div class="text-xs text-slate-500 mt-1">Update Terakhir:</div>
        <div class="text-[10px] font-medium text-slate-600">${timeString}</div>
      </div>
    `);
  } else {
    // Create new marker
    const marker = L.marker([lat, lng], {
      icon: createEmployeeIcon(name)
    }).addTo(map.value);
    
    marker.bindPopup(`
      <div class="text-center">
        <div class="font-bold text-slate-800">${name}</div>
        <div class="text-xs text-slate-500 mt-1">Update Terakhir:</div>
        <div class="text-[10px] font-medium text-slate-600">${timeString}</div>
      </div>
    `);
    
    markers.value[id] = marker;
  }
};

const initWebSocket = () => {
  window.Pusher = Pusher;

  // Use Vite env vars for Reverb
  echo.value = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
  });

  // Listen to 'live-location' channel
  echo.value.channel('live-location')
    .listen('.location.updated', (e) => {
      console.log('Received location update:', e);
      if (e.employeeId && e.latitude && e.longitude) {
        updateMarker(e.employeeId, e.name || 'Employee', e.latitude, e.longitude);
      }
    });
};

onMounted(() => {
  initMap();
  loadInitialLocations();
  initWebSocket();
});

onUnmounted(() => {
  if (map.value) {
    map.value.remove();
  }
  if (echo.value) {
    echo.value.disconnect();
  }
});
</script>

<style>
/* Adjust Leaflet popup styles to match Tailwind */
.leaflet-popup-content-wrapper {
  border-radius: 0.5rem;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}
.leaflet-popup-content {
  margin: 10px 14px;
}
</style>
