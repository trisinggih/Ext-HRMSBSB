<template>
  <div>
    <!-- CrudPage for Check Points -->
    <CrudPage
      ref="crudRef"
      title="Daftar Check Point Sales / Kunjungan"
      singular="Check Point"
      endpoint="/check-points"
      :columns="columns"
      :filters="filters"
      :creatable="false"
      :exportable="true"
      export-filename="Laporan_Check_Point"
      :default-per-page="15"
    >
      <template #header-actions>
        <button class="btn btn-outline-info btn-sm d-flex align-items-center gap-1 shadow-sm rounded-2" @click="openAllOnMapModal">
          <i class="mdi mdi-map-marker-multiple font-size-16"></i>
          <span>Lihat Semua di Peta</span>
        </button>
      </template>

      <!-- Cell Employee -->
      <template #cell-employee="{ row }">
        <div class="d-flex align-items-center py-1">
          <div
            class="rounded-circle me-2 bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center fw-bold border border-primary border-opacity-25"
            style="width: 36px; height: 36px; font-size: 13px; min-width: 36px;"
          >
            {{ (row.employee?.name || 'K').charAt(0).toUpperCase() }}
          </div>
          <div>
            <div class="fw-bold text-dark">{{ row.employee?.name || '-' }}</div>
            <div class="text-muted small font-size-11" v-if="row.employee?.employee_id">
              <span class="badge bg-light text-secondary border font-monospace">{{ row.employee.employee_id }}</span>
            </div>
            <div class="text-muted font-size-11 mt-0" v-if="row.employee?.company?.name">
              {{ row.employee.company.name }}
            </div>
          </div>
        </div>
      </template>

      <!-- Cell Client / Klien -->
      <template #cell-client_name="{ row }">
        <div class="fw-semibold text-dark">{{ row.client_name || '-' }}</div>
        <div class="text-muted small" v-if="row.purpose">
          <i class="mdi mdi-bullseye-arrow me-1 text-primary"></i>{{ row.purpose }}
        </div>
      </template>

      <!-- Cell Location -->
      <template #cell-location="{ row }">
        <div>
          <div class="text-truncate small" style="max-width: 220px;" :title="row.address || 'Koordinat GPS'">
            <i class="mdi mdi-map-marker text-danger me-1"></i>
            {{ row.address || `${row.latitude}, ${row.longitude}` }}
          </div>
          <button 
            v-if="row.latitude && row.longitude" 
            @click="openSingleMap(row)" 
            class="btn btn-link btn-sm p-0 font-size-11 text-decoration-none text-primary mt-1"
          >
            <i class="mdi mdi-eye-outline me-1"></i>Buka Peta Lokasi
          </button>
        </div>
      </template>

      <!-- Cell Photo / Foto Kunjungan -->
      <template #cell-photos="{ row }">
        <div class="d-flex align-items-center gap-1">
          <template v-if="getThumbnails(row).length > 0">
            <div 
              class="position-relative cursor-pointer"
              @click="openPhotoGallery(row)"
              title="Klik untuk melihat foto"
            >
              <img
                :src="getThumbnails(row)[0]"
                class="rounded border shadow-xs"
                style="width: 44px; height: 44px; object-fit: cover;"
                alt="Foto"
              />
              <span
                v-if="getThumbnails(row).length > 1"
                class="position-absolute bottom-0 end-0 badge bg-dark bg-opacity-75 text-white"
                style="font-size: 9px; padding: 2px 4px;"
              >
                +{{ getThumbnails(row).length - 1 }}
              </span>
            </div>
          </template>
          <span v-else class="text-muted font-size-12">Tidak ada foto</span>
        </div>
      </template>

      <!-- Cell Tanggal / Waktu -->
      <template #cell-created_at="{ row }">
        <div class="small fw-medium text-dark">{{ formatDateTime(row.created_at) }}</div>
        <div class="text-muted font-size-11">{{ timeAgo(row.created_at) }}</div>
      </template>

      <!-- Actions -->
      <template #actions="{ row }">
        <button class="btn-action-detail" @click="openDetailModal(row)" title="Detail Lengkap">
          <i class="mdi mdi-eye-outline"></i> Detail
        </button>
      </template>
    </CrudPage>

    <!-- Modal Detail Check Point -->
    <div v-if="showDetailModal && selectedItem" class="modal fade show d-block" tabindex="-1" style="background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 1055;">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
          <div class="modal-header bg-primary text-white border-bottom-0 py-3 px-4">
            <h5 class="modal-title fw-bold text-white mb-0 d-flex align-items-center gap-2">
              <i class="mdi mdi-map-marker-check font-size-20"></i>
              Detail Check Point Kunjungan
            </h5>
            <button type="button" class="btn-close btn-close-white" @click="closeDetailModal"></button>
          </div>

          <div class="modal-body p-4 bg-light">
            <div class="row g-3">
              <!-- Info Karyawan & Client -->
              <div class="col-md-6">
                <div class="card border-0 shadow-xs rounded-3 p-3 h-100 bg-white">
                  <h6 class="fw-bold text-primary mb-3 border-bottom pb-2">Informasi Kunjungan</h6>
                  <table class="table table-sm table-borderless mb-0 font-size-13">
                    <tbody>
                      <tr>
                        <td class="text-muted" style="width: 120px;">Karyawan</td>
                        <td class="fw-bold text-dark">: {{ selectedItem.employee?.name || '-' }}</td>
                      </tr>
                      <tr>
                        <td class="text-muted">NIK</td>
                        <td>: <span class="badge bg-light text-secondary border font-monospace">{{ selectedItem.employee?.employee_id || '-' }}</span></td>
                      </tr>
                      <tr>
                        <td class="text-muted">Perusahaan</td>
                        <td>: {{ selectedItem.employee?.company?.name || '-' }}</td>
                      </tr>
                      <tr>
                        <td class="text-muted">Jabatan / Dept</td>
                        <td>: {{ selectedItem.employee?.position?.name || '-' }} / {{ selectedItem.employee?.department?.name || '-' }}</td>
                      </tr>
                      <tr>
                        <td class="text-muted">Nama Klien / Toko</td>
                        <td class="fw-bold text-dark">: {{ selectedItem.client_name || '-' }}</td>
                      </tr>
                      <tr>
                        <td class="text-muted">Tujuan</td>
                        <td class="text-primary fw-semibold">: {{ selectedItem.purpose || '-' }}</td>
                      </tr>
                      <tr>
                        <td class="text-muted">Waktu Kunjungan</td>
                        <td>: {{ formatDateTime(selectedItem.created_at) }}</td>
                      </tr>
                      <tr>
                        <td class="text-muted">Catatan</td>
                        <td>: <em>{{ selectedItem.notes || '-' }}</em></td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

              <!-- Info Lokasi & Peta Preview -->
              <div class="col-md-6">
                <div class="card border-0 shadow-xs rounded-3 p-3 h-100 bg-white d-flex flex-column">
                  <h6 class="fw-bold text-primary mb-2 border-bottom pb-2">Lokasi Kunjungan GPS</h6>
                  <div class="mb-2 font-size-12 text-muted">
                    <i class="mdi mdi-map-marker text-danger me-1"></i>
                    <strong>Alamat:</strong> {{ selectedItem.address || 'Alamat tidak terdeteksi otomatis' }}
                  </div>
                  <div class="mb-2 font-size-11 text-secondary font-monospace">
                    Koordinat: {{ selectedItem.latitude }}, {{ selectedItem.longitude }}
                  </div>
                  
                  <!-- Map Container -->
                  <div class="flex-grow-1 position-relative rounded-3 overflow-hidden border" style="min-height: 180px;">
                    <div id="detailMap" style="width: 100%; height: 100%; min-height: 180px;"></div>
                  </div>
                  
                  <div class="mt-2 text-end">
                    <a
                      :href="`https://www.google.com/maps/search/?api=1&query=${selectedItem.latitude},${selectedItem.longitude}`"
                      target="_blank"
                      class="btn btn-sm btn-outline-primary font-size-12 px-3 rounded-2"
                    >
                      <i class="mdi mdi-google-maps me-1"></i>Buka di Google Maps
                    </a>
                  </div>
                </div>
              </div>

              <!-- Foto-foto Dokumentasi -->
              <div class="col-12">
                <div class="card border-0 shadow-xs rounded-3 p-3 bg-white">
                  <h6 class="fw-bold text-primary mb-3 border-bottom pb-2">
                    Foto Dokumentasi Kunjungan ({{ getThumbnails(selectedItem).length }} Foto)
                  </h6>
                  <div v-if="getThumbnails(selectedItem).length > 0" class="d-flex flex-wrap gap-3">
                    <div
                      v-for="(photo, idx) in getThumbnails(selectedItem)"
                      :key="idx"
                      class="position-relative border rounded-3 overflow-hidden shadow-xs cursor-pointer group-hover"
                      style="width: 140px; height: 140px;"
                      @click="previewImage(photo)"
                    >
                      <img
                        :src="photo"
                        class="w-100 h-100"
                        style="object-fit: cover;"
                        alt="Dokumentasi"
                      />
                      <div class="position-absolute bottom-0 start-0 end-0 bg-dark bg-opacity-50 text-white text-center py-1 font-size-11">
                        Foto {{ idx + 1 }} <i class="mdi mdi-magnify-plus ms-1"></i>
                      </div>
                    </div>
                  </div>
                  <div v-else class="text-muted text-center py-3 font-size-13">
                    Tidak ada lampiran foto untuk kunjungan ini.
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="modal-footer border-top-0 px-4 pb-3 pt-2 bg-light">
            <button type="button" class="btn btn-secondary px-4 rounded-2" @click="closeDetailModal">Tutup</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Peta Semua Check Point -->
    <div v-if="showAllMapModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 1050;">
      <div class="modal-dialog modal-dialog-centered modal-xl" style="max-width: 92%;">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
          <div class="modal-header bg-dark text-white border-bottom-0 py-3 px-4">
            <h5 class="modal-title fw-bold text-white mb-0 d-flex align-items-center gap-2">
              <i class="mdi mdi-map-marker-radius font-size-22 text-info"></i>
              Sebaran Lokasi Check Point (Kunjungan Sales)
            </h5>
            <button type="button" class="btn-close btn-close-white" @click="closeAllMapModal"></button>
          </div>
          <div class="modal-body p-0 position-relative" style="height: 68vh;">
            <div id="allCheckPointsMap" style="width: 100%; height: 100%;"></div>
          </div>
          <div class="modal-footer border-top-0 px-4 py-2 bg-light d-flex justify-content-between align-items-center">
            <span class="text-muted small">
              Menampilkan titik koordinat kunjungan yang tersimpan di sistem.
            </span>
            <button type="button" class="btn btn-secondary px-4 rounded-2" @click="closeAllMapModal">Tutup</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Image Preview Lightbox -->
    <div v-if="previewPhotoUrl" class="modal fade show d-block" tabindex="-1" style="background: rgba(0, 0, 0, 0.85); z-index: 1065;" @click="previewPhotoUrl = null">
      <div class="modal-dialog modal-dialog-centered modal-lg" @click.stop>
        <div class="modal-content bg-transparent border-0 text-center">
          <div class="d-flex justify-content-end mb-2">
            <button type="button" class="btn btn-light btn-sm rounded-circle shadow" @click="previewPhotoUrl = null">
              <i class="mdi mdi-close font-size-16"></i>
            </button>
          </div>
          <img :src="previewPhotoUrl" class="img-fluid rounded-3 shadow-lg mx-auto" style="max-height: 80vh; object-fit: contain;" alt="Preview Foto" />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, nextTick } from 'vue'
import CrudPage from '../components/CrudPage.vue'
import api from '../api'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

// Fix default Leaflet icon paths
delete L.Icon.Default.prototype._getIconUrl
L.Icon.Default.mergeOptions({
  iconRetinaUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon-2x.png',
  iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon.png',
  shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
})

const crudRef = ref(null)
const showDetailModal = ref(false)
const selectedItem = ref(null)
const previewPhotoUrl = ref(null)

const showAllMapModal = ref(false)
let detailMapInstance = null
let allMapInstance = null

// Table Columns configuration
const columns = [
  { key: 'employee', label: 'Karyawan' },
  { key: 'client_name', label: 'Klien / Tempat' },
  { key: 'location', label: 'Lokasi GPS' },
  { key: 'photos', label: 'Foto Bukti' },
  { key: 'notes', label: 'Catatan', format: (v) => v || '-' },
  { key: 'created_at', label: 'Waktu Input' },
]

// Filters configuration
const filters = [
  { key: 'employee_id', label: 'Filter Karyawan', relationEndpoint: '/employees' },
  { key: 'date', label: 'Filter Tanggal', type: 'date' },
]

const getThumbnails = (row) => {
  if (!row) return []
  if (row.photos_urls && Array.isArray(row.photos_urls) && row.photos_urls.length > 0) {
    return row.photos_urls
  }
  if (row.photos && Array.isArray(row.photos) && row.photos.length > 0) {
    return row.photos.map(p => p.startsWith('http') ? p : `${api.defaults.baseURL.replace('api/v1', 'storage')}/${p.replace(/^\//, '')}`)
  }
  if (row.photo_url) {
    return [row.photo_url]
  }
  if (row.photo_path) {
    const p = row.photo_path
    return [p.startsWith('http') ? p : `${api.defaults.baseURL.replace('api/v1', 'storage')}/${p.replace(/^\//, '')}`]
  }
  return []
}

const formatDateTime = (dateStr) => {
  if (!dateStr) return '-'
  const d = new Date(dateStr)
  return d.toLocaleString('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const timeAgo = (dateStr) => {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  const now = new Date()
  const diffSec = Math.floor((now - d) / 1000)
  if (diffSec < 60) return 'Baru saja'
  const diffMin = Math.floor(diffSec / 60)
  if (diffMin < 60) return `${diffMin} menit yang lalu`
  const diffHours = Math.floor(diffMin / 60)
  if (diffHours < 24) return `${diffHours} jam yang lalu`
  const diffDays = Math.floor(diffHours / 24)
  return `${diffDays} hari yang lalu`
}

const openDetailModal = (row) => {
  selectedItem.value = row
  showDetailModal.value = true
  nextTick(() => {
    initDetailMap(row)
  })
}

const openSingleMap = (row) => {
  openDetailModal(row)
}

const openPhotoGallery = (row) => {
  const photos = getThumbnails(row)
  if (photos.length > 0) {
    previewPhotoUrl.value = photos[0]
  }
}

const previewImage = (url) => {
  previewPhotoUrl.value = url
}

const closeDetailModal = () => {
  showDetailModal.value = false
  selectedItem.value = null
  if (detailMapInstance) {
    detailMapInstance.remove()
    detailMapInstance = null
  }
}

const initDetailMap = (item) => {
  if (!item.latitude || !item.longitude) return
  const lat = parseFloat(item.latitude)
  const lng = parseFloat(item.longitude)
  if (isNaN(lat) || isNaN(lng)) return

  const mapEl = document.getElementById('detailMap')
  if (!mapEl) return

  if (detailMapInstance) {
    detailMapInstance.remove()
    detailMapInstance = null
  }

  detailMapInstance = L.map(mapEl).setView([lat, lng], 16)
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; OpenStreetMap'
  }).addTo(detailMapInstance)

  const marker = L.marker([lat, lng]).addTo(detailMapInstance)
  marker.bindPopup(`<b>${item.client_name || 'Check Point'}</b><br>${item.purpose || ''}`).openPopup()

  setTimeout(() => {
    detailMapInstance?.invalidateSize()
  }, 300)
}

const openAllOnMapModal = async () => {
  showAllMapModal.value = true
  await nextTick()

  const mapEl = document.getElementById('allCheckPointsMap')
  if (!mapEl) return

  if (allMapInstance) {
    allMapInstance.remove()
    allMapInstance = null
  }

  // Fetch all recent checkpoints
  try {
    const res = await api.get('/check-points?per_page=100')
    const items = res.data?.data?.data || res.data?.data || []

    allMapInstance = L.map(mapEl).setView([-2.5489, 118.0149], 5)
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; OpenStreetMap'
    }).addTo(allMapInstance)

    const validMarkers = []
    items.forEach(cp => {
      const lat = parseFloat(cp.latitude)
      const lng = parseFloat(cp.longitude)
      if (!isNaN(lat) && !isNaN(lng)) {
        const marker = L.marker([lat, lng]).addTo(allMapInstance)
        const photoThumb = getThumbnails(cp)[0] || ''
        const photoHtml = photoThumb ? `<div class="mt-1 text-center"><img src="${photoThumb}" style="width: 100px; height: 70px; object-fit: cover; border-radius: 4px;" /></div>` : ''
        
        marker.bindPopup(`
          <div style="min-width: 160px;">
            <div class="fw-bold text-primary">${cp.client_name || 'Check Point'}</div>
            <div class="small text-muted">${cp.purpose || '-'}</div>
            <hr class="my-1" />
            <div class="small"><b>Karyawan:</b> ${cp.employee?.name || '-'}</div>
            <div class="small"><b>Waktu:</b> ${formatDateTime(cp.created_at)}</div>
            ${photoHtml}
          </div>
        `)
        validMarkers.push([lat, lng])
      }
    })

    if (validMarkers.length > 0) {
      const bounds = L.latLngBounds(validMarkers)
      allMapInstance.fitBounds(bounds, { padding: [50, 50], maxZoom: 16 })
    }

    setTimeout(() => {
      allMapInstance?.invalidateSize()
    }, 300)
  } catch (err) {
    console.error('Failed to load check points for map', err)
  }
}

const closeAllMapModal = () => {
  showAllMapModal.value = false
  if (allMapInstance) {
    allMapInstance.remove()
    allMapInstance = null
  }
}
</script>

<style scoped>
.shadow-xs {
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}
.cursor-pointer {
  cursor: pointer;
}
.group-hover:hover {
  opacity: 0.9;
  transform: scale(1.02);
  transition: all 0.2s ease;
}
</style>
