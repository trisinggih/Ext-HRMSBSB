<template>
  <div class="row">
    <div class="col-md-6 mx-auto">
      <div class="card">
        <div class="card-header text-center">
          <h4 class="card-title mb-0">Live Attendance & Tracking</h4>
        </div>
        <div class="card-body text-center">
          
          <div class="mb-4">
            <h5>Waktu Sekarang:</h5>
            <h3>{{ currentTime }}</h3>
          </div>

          <div v-if="locationError" class="alert alert-danger">
            {{ locationError }}
          </div>

          <div v-if="!isTracking" class="alert alert-info">
            Tracking GPS tidak aktif. Silakan Clock In untuk mulai merekam lokasi.
          </div>
          
          <div v-else class="alert alert-success">
            <i class="mdi mdi-map-marker-radius"></i> Tracking GPS aktif. Lokasi terakhir disinkronkan pada: {{ lastSyncedTime || 'Belum ada' }}.
          </div>

          <div class="d-grid gap-2">
            <button class="btn btn-lg btn-primary" @click="handleClockIn" :disabled="isLoading || isClockedIn">
              <span v-if="isLoading && action === 'clock_in'" class="spinner-border spinner-border-sm"></span>
              CLOCK IN
            </button>
            <button class="btn btn-lg btn-danger" @click="handleClockOut" :disabled="isLoading || !isClockedIn">
              <span v-if="isLoading && action === 'clock_out'" class="spinner-border spinner-border-sm"></span>
              CLOCK OUT
            </button>
          </div>
          
          <div class="mt-4">
            <div class="form-check form-switch d-inline-block">
              <input class="form-check-input" type="checkbox" id="canvassingSwitch" v-model="isCanvassing">
              <label class="form-check-label" for="canvassingSwitch">Mode Canvassing (Abaikan Radius Kantor)</label>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import axios from '../api'
import 'leaflet/dist/leaflet.css'
import { toast } from 'vue3-toastify'

const props = defineProps({
  employeeId: {
    type: [Number, String],
  }
})

const currentTime = ref(new Date().toLocaleTimeString('id-ID', { timeZone: 'Asia/Jakarta' }))
let clockInterval = null
let syncInterval = null

const isLoading = ref(false)
const action = ref('')
const isClockedIn = ref(false)
const isTracking = ref(false)
const lastSyncedTime = ref('')
const locationError = ref('')
const isCanvassing = ref(false) // toggle untuk field sales

onMounted(() => {
  clockInterval = setInterval(() => {
    currentTime.value = new Date().toLocaleTimeString('id-ID', { timeZone: 'Asia/Jakarta' })
  }, 1000)
})

onUnmounted(() => {
  if (clockInterval) clearInterval(clockInterval)
  stopTracking()
})

const getPosition = () => {
  return new Promise((resolve, reject) => {
    if (!navigator.geolocation) {
      reject(new Error('Geolocation tidak didukung oleh browser Anda.'))
    }
    navigator.geolocation.getCurrentPosition(resolve, reject, {
      enableHighAccuracy: true,
      timeout: 10000,
      maximumAge: 0
    })
  })
}

const handleClockIn = async () => {
  isLoading.value = true
  action.value = 'clock_in'
  locationError.value = ''
  
  try {
    const pos = await getPosition()
    const coords = {
      latitude: pos.coords.latitude,
      longitude: pos.coords.longitude,
      accuracy: pos.coords.accuracy,
      is_canvassing: isCanvassing.value ? 1 : 0
    }
    
    await axios.post('/attendance/clock-in', coords)
    
    toast.success('Clock In berhasil!')
    isClockedIn.value = true
    startTracking()
    
  } catch (error) {
    console.error(error)
    if (error.response?.data?.message) {
      toast.error(error.response.data.message)
    } else {
      locationError.value = 'Gagal mendapatkan lokasi. Pastikan GPS aktif dan izin diberikan.'
    }
  } finally {
    isLoading.value = false
    action.value = ''
  }
}

const handleClockOut = async () => {
  isLoading.value = true
  action.value = 'clock_out'
  locationError.value = ''
  
  try {
    const pos = await getPosition()
    const coords = {
      latitude: pos.coords.latitude,
      longitude: pos.coords.longitude,
      accuracy: pos.coords.accuracy,
    }
    
    await axios.post('/attendance/clock-out', coords)
    
    toast.success('Clock Out berhasil!')
    isClockedIn.value = false
    stopTracking()
    
  } catch (error) {
    console.error(error)
    if (error.response?.data?.message) {
      toast.error(error.response.data.message)
    } else {
      locationError.value = 'Gagal mendapatkan lokasi.'
    }
  } finally {
    isLoading.value = false
    action.value = ''
  }
}

const startTracking = () => {
  if (syncInterval) return
  isTracking.value = true
  
  // Sync setiap 2 menit (120000 ms)
  syncInterval = setInterval(async () => {
    try {
      const pos = await getPosition()
      await axios.post('/attendance/sync-location', {
        latitude: pos.coords.latitude,
        longitude: pos.coords.longitude,
        accuracy: pos.coords.accuracy,
      })
      lastSyncedTime.value = new Date().toLocaleTimeString('id-ID', { timeZone: 'Asia/Jakarta' })
    } catch (e) {
      console.error('Failed to sync location', e)
    }
  }, 120000)
}

const stopTracking = () => {
  if (syncInterval) {
    clearInterval(syncInterval)
    syncInterval = null
  }
  isTracking.value = false
}
</script>
