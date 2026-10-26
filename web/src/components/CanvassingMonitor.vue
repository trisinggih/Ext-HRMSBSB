<template>
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h4 class="card-title mb-0">Canvassing Monitor (Live Sales Location)</h4>
          <button class="btn btn-sm btn-primary" @click="fetchData">Refresh Data</button>
        </div>
        <div class="card-body">
          <div class="row mb-3">
            <div class="col-md-4">
              <label>Pilih Sales untuk Riwayat Rute (Opsional):</label>
              <select v-model="selectedEmployeeId" class="form-select" @change="fetchHistory">
                <option :value="null">-- Tampilkan Semua Live Location --</option>
                <option v-for="emp in employees" :key="emp.id" :value="emp.id">
                  {{ emp.name }}
                </option>
              </select>
            </div>
          </div>
          <div id="map" style="height: 500px; width: 100%; border-radius: 8px;"></div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import axios from 'axios'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

// Perbaiki bug ikon bawaan leaflet di Vue
import iconRetinaUrl from 'leaflet/dist/images/marker-icon-2x.png'
import iconUrl from 'leaflet/dist/images/marker-icon.png'
import shadowUrl from 'leaflet/dist/images/marker-shadow.png'

L.Icon.Default.mergeOptions({
  iconRetinaUrl,
  iconUrl,
  shadowUrl,
})

const map = ref(null)
const markers = ref([])
const polyline = ref(null)
const selectedEmployeeId = ref(null)
const employees = ref([])
let refreshInterval = null

onMounted(async () => {
  initMap()
  await fetchEmployees()
  await fetchData()
  
  // Auto refresh live data every 30 seconds if no specific employee selected
  refreshInterval = setInterval(() => {
    if (!selectedEmployeeId.value) {
      fetchData()
    }
  }, 30000)
})

onUnmounted(() => {
  if (refreshInterval) clearInterval(refreshInterval)
  if (map.value) map.value.remove()
})

const initMap = () => {
  // Default to Jakarta
  map.value = L.map('map').setView([-6.2088, 106.8456], 12)
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
  }).addTo(map.value)
}

const clearMap = () => {
  markers.value.forEach(m => map.value.removeLayer(m))
  markers.value = []
  if (polyline.value) {
    map.value.removeLayer(polyline.value)
    polyline.value = null
  }
}

const fetchEmployees = async () => {
  try {
    const res = await axios.get('/employees')
    employees.value = res.data.data.data || []
  } catch (error) {
    console.error('Failed to fetch employees', error)
  }
}

const fetchData = async () => {
  if (selectedEmployeeId.value) {
    await fetchHistory()
    return
  }

  try {
    const res = await axios.get('/canvassing/live')
    const liveData = res.data.data
    
    clearMap()
    
    const bounds = L.latLngBounds()
    
    liveData.forEach(item => {
      const latLng = [item.location.latitude, item.location.longitude]
      const timeString = new Date(item.location.timestamp).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta', day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
      const marker = L.marker(latLng)
        .bindPopup(`<b>${item.employee.name}</b><br>Update Terakhir: ${timeString}`)
        .addTo(map.value)
      
      markers.value.push(marker)
      bounds.extend(latLng)
    })
    
    if (liveData.length > 0) {
      map.value.fitBounds(bounds, { padding: [50, 50] })
    }
  } catch (error) {
    console.error('Failed to fetch live locations', error)
  }
}

const fetchHistory = async () => {
  if (!selectedEmployeeId.value) {
    await fetchData()
    return
  }

  try {
    const res = await axios.get(`/canvassing/history?employee_id=${selectedEmployeeId.value}`)
    const historyData = res.data.data
    
    clearMap()
    
    if (historyData.length === 0) return
    
    const latLngs = historyData.map(loc => [loc.latitude, loc.longitude])
    
    // Draw polyline
    polyline.value = L.polyline(latLngs, { color: 'blue', weight: 4 }).addTo(map.value)
    
    // Add marker at start and end
    const startLoc = latLngs[0]
    const endLoc = latLngs[latLngs.length - 1]
    
    const startMarker = L.marker(startLoc)
      .bindPopup(`<b>Mulai</b><br>${new Date(historyData[0].timestamp).toLocaleTimeString('id-ID', { timeZone: 'Asia/Jakarta' })}`)
      .addTo(map.value)
    
    const endMarker = L.marker(endLoc)
      .bindPopup(`<b>Lokasi Terakhir</b><br>${new Date(historyData[historyData.length-1].timestamp).toLocaleTimeString('id-ID', { timeZone: 'Asia/Jakarta' })}`)
      .addTo(map.value)
      
    markers.value.push(startMarker, endMarker)
    
    map.value.fitBounds(polyline.value.getBounds(), { padding: [50, 50] })
    
  } catch (error) {
    console.error('Failed to fetch location history', error)
  }
}
</script>
