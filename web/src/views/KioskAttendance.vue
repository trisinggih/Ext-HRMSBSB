<template>
  <div class="kiosk-container d-flex flex-column justify-content-center align-items-center vh-100 bg-light" style="background-image: linear-gradient(135deg, #fdfbfb 0%, #ebedee 100%);">
    <!-- PIN Verification -->
    <div v-if="!isPinVerified" class="card shadow-lg border-0 rounded-4" style="width: 450px; max-width: 90vw;">
      <div class="card-body p-5 text-center">
        <div class="mb-4 text-primary">
          <i class="mdi mdi-lock-outline" style="font-size: 4rem;"></i>
        </div>
        <h3 class="mb-3 fw-bold text-dark">Buka Kiosk Absensi</h3>
        <p class="text-muted small mb-4">Masukkan PIN Kiosk Perusahaan untuk mengaktifkan mode absensi di perangkat ini.</p>
        <input type="password" class="form-control form-control-lg text-center mb-4 letter-spacing-2 bg-light border-0" v-model="pin" placeholder="****" maxlength="6" @keyup.enter="verifyPin" />
        <button class="btn btn-primary w-100 py-3 fw-bold rounded-3 shadow-sm" @click="verifyPin" :disabled="loading">
          <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
          Buka Kiosk Sekarang
        </button>
      </div>
    </div>
    
    <!-- Kiosk Interface -->
    <div v-else class="text-center w-100 h-100 d-flex flex-column">
      <!-- Premium Header -->
      <div class="kiosk-header py-2 px-4 shadow-sm d-flex justify-content-between align-items-center text-white bg-primary" style="border-bottom: 3px solid rgba(255,255,255,0.2);">
        <div class="d-flex align-items-center">
          <div class="bg-white text-primary rounded-circle d-flex justify-content-center align-items-center me-3 shadow-sm" style="width: 40px; height: 40px;">
            <i class="mdi mdi-clock-check-outline fs-4"></i>
          </div>
          <div class="text-start">
            <h4 class="mb-0 fw-bold text-white">Kiosk Absensi</h4>
            <span class="text-white-50 small text-uppercase fw-semibold letter-spacing-1">{{ companyName }}</span>
          </div>
        </div>
        <div class="text-end">
          <h2 class="mb-0 fw-bold text-white" style="font-family: monospace;">{{ currentTime }}</h2>
          <span class="text-white-50 small text-uppercase">{{ currentDate }}</span>
        </div>
      </div>
      
      <!-- Main Content -->
      <div class="flex-grow-1 d-flex justify-content-center align-items-center p-4">
        <div class="card shadow-lg border-0 rounded-4 overflow-hidden" style="width: 900px; max-width: 100%;">
          <div class="row g-0">
            <!-- Camera Section -->
            <div class="col-md-6 bg-dark d-flex justify-content-center align-items-center flex-column" style="min-height: 500px; position: relative;">
               <video ref="videoElement" autoplay playsinline class="w-100 h-100 object-fit-cover position-absolute top-0 start-0" v-show="cameraActive"></video>
               <canvas ref="canvasElement" class="d-none"></canvas>
               
               <!-- Camera Overlay Grid -->
               <div v-if="cameraActive" class="position-absolute w-100 h-100 pointer-events-none d-flex justify-content-center align-items-center" style="z-index: 2;">
                  <div class="border border-white border-2 rounded-circle" style="width: 250px; height: 320px; opacity: 0.3; border-style: dashed !important;"></div>
               </div>
               
               <div v-if="!cameraActive" class="text-white text-center position-relative z-1">
                 <div class="spinner-grow text-light mb-3" role="status" style="width: 3rem; height: 3rem;"></div>
                 <h5 class="fw-light">Menyiapkan Kamera...</h5>
               </div>
               
               <!-- Camera Status Badge -->
               <div v-if="cameraActive" class="position-absolute bottom-0 start-0 m-3 px-3 py-1 bg-success text-white rounded-pill small fw-semibold shadow" style="z-index: 3;">
                 <i class="mdi mdi-circle-medium text-white blink"></i> LIVE
               </div>
            </div>
            
            <!-- Input Section -->
            <div class="col-md-6 p-5 d-flex flex-column justify-content-center bg-white">
              <div class="mb-5 text-center">
                <h3 class="fw-bold text-dark mb-2">Pindai & Absen</h3>
                <p class="text-muted small">Posisikan wajah Anda di dalam garis panduan, lalu masukkan NIP Anda di bawah ini.</p>
              </div>
              
              <div class="mb-5">
                <label class="form-label fw-bold text-secondary small text-uppercase">Nomor Induk Pegawai (NIP)</label>
                <input type="text" class="form-control form-control-lg fw-bold text-center border-2 py-3" style="font-size: 1.5rem; letter-spacing: 0.1rem;" placeholder="Ketik NIP Anda..." v-model="nip" @keyup.enter="submitAttendance('in')" />
              </div>
              
              <div class="row g-3 mt-auto">
                <div class="col-6">
                  <button class="btn btn-success w-100 py-3 fw-bold fs-5 shadow rounded-3 border-0 action-btn" @click="submitAttendance('in')" :disabled="loadingAttendance" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
                    <span v-if="loadingAttendance && actionType === 'in'" class="spinner-border spinner-border-sm me-1"></span>
                    <i v-else class="mdi mdi-login me-1"></i> MASUK
                  </button>
                </div>
                <div class="col-6">
                  <button class="btn btn-danger w-100 py-3 fw-bold fs-5 shadow rounded-3 border-0 action-btn" @click="submitAttendance('out')" :disabled="loadingAttendance" style="background: linear-gradient(135deg, #ff0844 0%, #ffb199 100%);">
                    <span v-if="loadingAttendance && actionType === 'out'" class="spinner-border spinner-border-sm me-1"></span>
                    <i v-else class="mdi mdi-logout me-1"></i> PULANG
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, nextTick } from "vue"
import { toast } from "vue3-toastify"
import api from "../api"
import Swal from "sweetalert2"

const pin = ref("")
const nip = ref("")
const isPinVerified = ref(false)
const loading = ref(false)
const loadingAttendance = ref(false)
const currentTime = ref("")
const currentDate = ref("")
const companyId = ref(null)
const companyName = ref("")
const actionType = ref("")

const videoElement = ref(null)
const canvasElement = ref(null)
const cameraActive = ref(false)
let stream = null
let timer = null

const verifyPin = async () => {
  if (!pin.value) {
    toast.error("Silakan masukkan PIN!")
    return
  }
  
  loading.value = true
  try {
    const { data } = await api.post("/attendance/kiosk/verify-pin", { pin: pin.value })
    companyId.value = data.company_id
    companyName.value = data.company_name
    isPinVerified.value = true
    
    // Start camera after UI is updated
    nextTick(() => {
      startCamera()
    })
  } catch (error) {
    toast.error(error.response?.data?.message || "PIN tidak valid atau gagal terhubung ke server.")
  } finally {
    loading.value = false
  }
}

const startCamera = async () => {
  try {
    stream = await navigator.mediaDevices.getUserMedia({ 
      video: { facingMode: "user" },
      audio: false
    })
    
    if (videoElement.value) {
      videoElement.value.srcObject = stream
      cameraActive.value = true
    }
  } catch (err) {
    console.error("Camera error:", err)
    toast.error("Gagal mengakses kamera. Pastikan memberikan izin akses kamera.")
  }
}

const stopCamera = () => {
  if (stream) {
    stream.getTracks().forEach(track => track.stop())
    cameraActive.value = false
  }
}

const capturePhoto = () => {
  if (!videoElement.value || !canvasElement.value) return null
  
  const video = videoElement.value
  const canvas = canvasElement.value
  
  // Set canvas dimensions to match video
  canvas.width = video.videoWidth
  canvas.height = video.videoHeight
  
  // Draw current frame to canvas
  const ctx = canvas.getContext('2d')
  ctx.drawImage(video, 0, 0, canvas.width, canvas.height)
  
  // Return base64 string
  return canvas.toDataURL('image/jpeg', 0.8)
}

const submitAttendance = async (type) => {
  if (!nip.value) {
    toast.warning("Silakan masukkan NIP Anda terlebih dahulu.")
    return
  }
  
  if (!cameraActive.value) {
    toast.warning("Kamera belum aktif. Tunggu sebentar atau pastikan izin kamera diberikan.")
    return
  }
  
  actionType.value = type
  loadingAttendance.value = true
  
  try {
    const photoBase64 = capturePhoto()
    if (!photoBase64) {
      throw new Error("Gagal mengambil foto dari kamera.")
    }
    
    const endpoint = type === 'in' ? '/attendance/kiosk/clock-in' : '/attendance/kiosk/clock-out'
    
    const { data } = await api.post(endpoint, {
      company_id: companyId.value,
      nip: nip.value,
      photo: photoBase64
    })
    
    Swal.fire({
      icon: 'success',
      title: 'Berhasil',
      html: `<b>${data.data.employee_name}</b><br>Berhasil Absen ${type === 'in' ? 'Masuk' : 'Pulang'} pada jam ${data.data.time}`,
      timer: 3000,
      showConfirmButton: false
    })
    
    // Reset NIP
    nip.value = ""
    
  } catch (error) {
    const errorMsg = error.response?.data?.message || error.message || "Gagal melakukan absensi."
    Swal.fire({
      icon: 'error',
      title: 'Gagal',
      text: errorMsg
    })
  } finally {
    loadingAttendance.value = false
    actionType.value = ""
  }
}

const updateTime = () => {
  const now = new Date()
  currentTime.value = now.toLocaleTimeString("id-ID", { hour: "2-digit", minute: "2-digit", second: "2-digit" })
  currentDate.value = now.toLocaleDateString("id-ID", { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })
}

onMounted(() => {
  updateTime()
  timer = setInterval(updateTime, 1000)
})

onUnmounted(() => {
  if (timer) clearInterval(timer)
  stopCamera()
})
</script>

<style scoped>
.letter-spacing-1 { letter-spacing: 0.1rem; }
.letter-spacing-2 { letter-spacing: 0.5rem; font-size: 1.5rem; }
.object-fit-cover { object-fit: cover; }
.pointer-events-none { pointer-events: none; }
.action-btn { transition: transform 0.2s; }
.action-btn:hover { transform: translateY(-3px); }
.action-btn:active { transform: translateY(1px); }

@keyframes blink-animation {
  0% { opacity: 1; }
  50% { opacity: 0; }
  100% { opacity: 1; }
}
.blink { animation: blink-animation 1.5s infinite; }
</style>
