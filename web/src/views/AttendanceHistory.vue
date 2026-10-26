<template>
  <div>
    <CrudPage
      title="Riwayat Kehadiran (Semua Karyawan)"
      singular="Riwayat"
      endpoint="/attendance/admin/history"
      :columns="columns"
      :filters="filters"
      :creatable="false"
      :default-per-page="50"
    >
      <template #cell-employee="{ value, row }">
        <div>
          <div class="fw-semibold text-dark">{{ row.employee?.name || row.employee_name || value || '-' }}</div>
          <div class="text-muted small" v-if="row.employee?.position">{{ row.employee.position }}</div>
          <div v-if="row.employee?.employee_id || row.employee_id || row.nip" class="mt-1">
            <span class="badge bg-light text-secondary border font-monospace" style="font-size: 10px; font-weight: 500;">
              {{ row.employee?.employee_id || row.employee_id || row.nip }}
            </span>
          </div>
        </div>
      </template>

      <template #cell-shift="{ value, row }">
        <div>
          <div class="fw-semibold text-dark">{{ row.shift?.name || value || '-' }}</div>
          <div class="text-muted small d-inline-flex align-items-center gap-1" v-if="row.shift?.clock_in && row.shift?.clock_out" style="font-size: 11px;">
            <i class="mdi mdi-clock-outline"></i> {{ row.shift.clock_in.substring(0,5) }} - {{ row.shift.clock_out.substring(0,5) }}
          </div>
        </div>
      </template>

      <template #actions="{ row }">
        <button
          @click="openPhotoModal(row)"
          class="btn btn-sm btn-info text-white"
          title="Lihat Foto Clock In & Clock Out"
        >
          <i class="mdi mdi-camera-outline me-1"></i> Foto
        </button>
      </template>
    </CrudPage>

    <!-- Modal Preview Foto Absen -->
    <div class="modal fade" id="photoHistoryPreviewModal" tabindex="-1" aria-hidden="true" ref="photoModalRef">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow-lg border-0">
          <div class="modal-header bg-dark text-white py-2">
            <h5 class="modal-title fs-6 d-flex align-items-center gap-2">
              <i class="mdi mdi-camera text-info fs-5"></i>
              <span>Foto Kehadiran - <strong>{{ selectedRow?.employee_name || selectedRow?.employee?.name || '-' }}</strong></span>
            </h5>
            <button type="button" class="btn-close btn-close-white" @click="closePhotoPreview"></button>
          </div>
          <div class="modal-body p-3 bg-light">
            <div class="row g-3">
              <!-- Foto Clock In -->
              <div class="col-md-6">
                <div class="card h-100 shadow-sm border-0">
                  <div class="card-header bg-primary text-white py-2 fw-bold small d-flex justify-content-between align-items-center">
                    <span><i class="mdi mdi-login me-1"></i> Foto Clock In (Masuk)</span>
                    <span class="badge bg-white text-primary" v-if="selectedRow?.clock_in_at">{{ fmt(selectedRow.clock_in_at) }}</span>
                  </div>
                  <div class="card-body text-center p-2 d-flex flex-column align-items-center justify-content-center bg-white rounded-bottom" style="min-height: 260px;">
                    <template v-if="selectedRow?.clock_in_photo">
                      <a :href="selectedRow.clock_in_photo" target="_blank" title="Klik untuk memperbesar">
                        <img :src="selectedRow.clock_in_photo" class="img-fluid rounded border" style="max-height: 280px; object-fit: contain;" alt="Foto Clock In" />
                      </a>
                    </template>
                    <template v-else>
                      <div class="text-muted py-4">
                        <i class="mdi mdi-camera-off fs-1 d-block mb-1 text-secondary opacity-50"></i>
                        <span class="small">Tidak ada foto clock in</span>
                      </div>
                    </template>
                  </div>
                </div>
              </div>

              <!-- Foto Clock Out -->
              <div class="col-md-6">
                <div class="card h-100 shadow-sm border-0">
                  <div class="card-header bg-danger text-white py-2 fw-bold small d-flex justify-content-between align-items-center">
                    <span><i class="mdi mdi-logout me-1"></i> Foto Clock Out (Pulang)</span>
                    <span class="badge bg-white text-danger" v-if="selectedRow?.clock_out_at">{{ fmt(selectedRow.clock_out_at) }}</span>
                  </div>
                  <div class="card-body text-center p-2 d-flex flex-column align-items-center justify-content-center bg-white rounded-bottom" style="min-height: 260px;">
                    <template v-if="selectedRow?.clock_out_photo">
                      <a :href="selectedRow.clock_out_photo" target="_blank" title="Klik untuk memperbesar">
                        <img :src="selectedRow.clock_out_photo" class="img-fluid rounded border" style="max-height: 280px; object-fit: contain;" alt="Foto Clock Out" />
                      </a>
                    </template>
                    <template v-else>
                      <div class="text-muted py-4">
                        <i class="mdi mdi-camera-off fs-1 d-block mb-1 text-secondary opacity-50"></i>
                        <span class="small">Tidak ada foto clock out</span>
                      </div>
                    </template>
                  </div>
                </div>
              </div>
            </div>

            <!-- Info Detail -->
            <div class="mt-3 p-2 bg-white rounded border text-start small text-muted d-flex justify-content-between flex-wrap gap-2">
              <div><i class="mdi mdi-calendar me-1"></i> Tanggal: <strong>{{ selectedRow?.date || '-' }}</strong></div>
              <div v-if="selectedRow?.notes && selectedRow?.notes !== '-'"><i class="mdi mdi-information-outline me-1"></i> Keterangan: {{ selectedRow.notes }}</div>
            </div>
          </div>
          <div class="modal-footer py-2">
            <button type="button" class="btn btn-sm btn-secondary" @click="closePhotoPreview">Tutup</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import CrudPage from '../components/CrudPage.vue'
import api from '../api'

const fmt = (iso) => {
  if (!iso) return '-'
  if (typeof iso === 'string' && iso.includes(':') && !iso.includes('T') && !iso.includes(' ')) {
    const parts = iso.trim().split(':')
    if (parts.length >= 2) return `${parts[0].padStart(2, '0')}:${parts[1].padStart(2, '0')}`
  }
  const d = new Date(iso)
  if (isNaN(d.getTime())) return iso
  const hours = String(d.getHours()).padStart(2, '0')
  const minutes = String(d.getMinutes()).padStart(2, '0')
  return `${hours}:${minutes}`
}

const columns = [
  { key: 'date', label: 'Tanggal' },
  { key: 'employee', label: 'Karyawan', sub: 'name' },
  { key: 'shift', label: 'Shift', sub: 'name' },
  { key: 'clock_in_at', label: 'CI', format: fmt },
  { key: 'clock_out_at', label: 'CO', format: fmt },
  { key: 'late_minutes', label: 'Telat', format: v => v ? `${v} mnt` : '-' },
  { key: 'working_hours', label: 'Jam Kerja', format: v => v ? `${v} jam` : '-' },
  { key: 'status', label: 'Status' },
]

const filters = ref([
  { key: 'month', label: 'Bulan (YYYY-MM)', type: 'month' },
  { key: 'employee_id', label: 'Karyawan', options: [] },
  { key: 'status', label: 'Status', options: [
    { value: 'present', label: 'Present' },
    { value: 'late', label: 'Late' },
    { value: 'absent', label: 'Absent' },
    { value: 'leave', label: 'Leave' }
  ]}
])

let bsPhotoModal = null
const photoModalRef = ref(null)
const selectedRow = ref(null)

const openPhotoModal = (row) => {
  selectedRow.value = row
  bsPhotoModal?.show()
}

const openPhoto = (url, title, row) => {
  selectedRow.value = row
  bsPhotoModal?.show()
}

const closePhotoPreview = () => {
  bsPhotoModal?.hide()
}

onMounted(async () => {
  if (typeof window !== 'undefined' && window.bootstrap) {
    bsPhotoModal = new window.bootstrap.Modal(photoModalRef.value)
  }
  try {
    const { data } = await api.get('/employees?per_page=1000')
    const employees = data.data?.data || []
    filters.value[1].options = employees.map(e => ({ value: e.id, label: e.name }))
  } catch(e) {}
})
</script>
