<template>
  <div>
    <div ref="mapContainer" style="height: 300px; width: 100%; border-radius: 8px; margin-bottom: 1rem; z-index: 1;"></div>
    <div class="row">
      <div class="col-md-6 mb-3">
        <label class="form-label fw-semibold text-secondary">Latitude</label>
        <input type="text" class="form-control bg-light border-0 px-3 py-2 shadow-none rounded-3" :value="lat" disabled />
      </div>
      <div class="col-md-6 mb-3">
        <label class="form-label fw-semibold text-secondary">Longitude</label>
        <input type="text" class="form-control bg-light border-0 px-3 py-2 shadow-none rounded-3" :value="lng" disabled />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

const props = defineProps({
  lat: { type: [String, Number], default: '' },
  lng: { type: [String, Number], default: '' }
})

const emit = defineEmits(['update:lat', 'update:lng'])

const mapContainer = ref(null)
let map = null
let marker = null

// Fix leaflet default icon issue
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
  iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
  iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
  shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
});

onMounted(() => {
  const initialLat = props.lat ? parseFloat(props.lat) : -6.200000;
  const initialLng = props.lng ? parseFloat(props.lng) : 106.816666;

  map = L.map(mapContainer.value).setView([initialLat, initialLng], 13)

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
  }).addTo(map)

  if (props.lat && props.lng) {
    marker = L.marker([initialLat, initialLng]).addTo(map)
  }

  map.on('click', (e) => {
    const { lat, lng } = e.latlng
    if (marker) {
      marker.setLatLng(e.latlng)
    } else {
      marker = L.marker(e.latlng).addTo(map)
    }
    emit('update:lat', lat.toString())
    emit('update:lng', lng.toString())
  })
  
  // ensure map is properly sized after rendering in modal
  setTimeout(() => {
    if (map) {
      map.invalidateSize()
    }
  }, 200)
})

onUnmounted(() => {
  if (map) {
    map.remove()
    map = null
  }
})

watch(() => [props.lat, props.lng], ([newLat, newLng]) => {
  if (map && newLat && newLng && !marker) {
    marker = L.marker([newLat, newLng]).addTo(map)
    map.setView([newLat, newLng], 13)
  }
})
</script>
