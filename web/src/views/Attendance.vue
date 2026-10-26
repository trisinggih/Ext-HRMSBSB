<template>
  <div>
    <CrudPage
      ref="crudRef"
      title="Laporan Kehadiran Harian"
      singular="Kehadiran"
      endpoint="/attendance/admin/daily"
      :columns="dailyColumns"
      :filters="dailyFilters"
      :creatable="false"
      :exportable="true"
      :export-filename="exportFilename"
      :default-per-page="50"
      :base-filters="baseFilters"
    >
      <template #header-actions>
        <div class="d-flex align-items-center me-3 gap-2">
          <label class="mb-0 fw-bold">Dari:</label>
          <input type="date" v-model="startDate" class="form-control form-control-sm" style="width: 140px" />
          <label class="mb-0 fw-bold">Sampai:</label>
          <input type="date" v-model="endDate" class="form-control form-control-sm" style="width: 140px" />
          <button @click="applyDateFilter" class="btn btn-sm btn-primary">
            <i class="mdi mdi-magnify"></i> Cari
          </button>
        </div>
      </template>

      <template #cell-name="{ value, row }">
        <div class="d-flex align-items-center">
          <img
            v-if="row.employee_photo"
            :src="row.employee_photo"
            class="rounded-circle me-2 border cursor-pointer"
            style="width: 32px; height: 32px; object-fit: cover"
            alt="Foto Profile"
            @click="openPhotoModal(row)"
            title="Klik untuk memperbesar foto profil"
          />
          <div
            v-else
            class="rounded-circle me-2 bg-primary text-white d-flex align-items-center justify-content-center fw-bold"
            style="width: 32px; height: 32px; font-size: 13px"
          >
            {{ (row.name || 'E').charAt(0).toUpperCase() }}
          </div>
          <div>
            <div class="fw-bold text-dark">{{ row.name }}</div>
            <div class="text-muted small" v-if="row.position">{{ row.position }}</div>
            <div v-if="row.employee_id || row.nip" class="mt-1">
              <span class="badge bg-light text-secondary border font-monospace" style="font-size: 10px; font-weight: 500;">
                {{ row.employee_id || row.nip }}
              </span>
            </div>
          </div>
        </div>
      </template>

      <template #cell-shift_name="{ value, row }">
        <div>
          <div class="fw-semibold text-dark">{{ value || '-' }}</div>
          <div class="text-muted small d-inline-flex align-items-center gap-1" v-if="row.shift_time" style="font-size: 11px;">
            <i class="mdi mdi-clock-outline"></i> {{ row.shift_time }}
          </div>
        </div>
      </template>

      <template #cell-status="{ value, row }">
        <span class="badge" :class="{
          'bg-success': value === 'present' && !(row?.late_minutes > 0),
          'bg-warning text-dark': value === 'permit',
          'bg-danger': value === 'absent' || value === 'late' || (value === 'present' && row?.late_minutes > 0),
          'bg-info': value === 'leave' || value === 'permission' || value === 'sick',
          'bg-secondary': value === 'holiday' || value === 'off' || value === '-'
        }">
          <template v-if="value === 'late' || (value === 'present' && row?.late_minutes > 0)">Terlambat</template>
          <template v-else-if="value === 'present'">Hadir</template>
          <template v-else-if="value === 'absent'">Mangkir</template>
          <template v-else-if="value === 'leave'">Cuti</template>
          <template v-else-if="value === 'permission'">Izin</template>
          <template v-else-if="value === 'sick'">Sakit</template>
          <template v-else-if="value === 'permit'">
            <template v-if="row?.clock_out_at">Permit Pulang Awal</template>
            <template v-else-if="row?.break_start_at || row?.break_end_at">Permit Keperluan</template>
            <template v-else>Permit</template>
          </template>
          <template v-else-if="value === 'off'">Jatah Libur</template>
          <template v-else-if="value === 'holiday'">Libur Perusahaan</template>
          <template v-else-if="value === '-'">Belum Absen</template>
          <template v-else>{{ (value || '-').toUpperCase() }}</template>
        </span>
        <button
          v-if="row?.attachment_url"
          type="button"
          class="btn btn-sm p-0 ms-1 text-primary border-0 align-baseline"
          @click.stop="openAttachmentModal(row)"
          title="Ada lampiran surat/bukti (Klik untuk melihat)"
        >
          <i class="mdi mdi-paperclip fs-6"></i>
        </button>
      </template>

      <template #actions="{ row }">
        <div class="d-flex align-items-center gap-1">
          <button
            v-if="row?.attachment_url"
            @click="openAttachmentModal(row)"
            class="btn-action-attachment"
            title="Lihat Surat Dokter / Lampiran Izin"
          >
            <i class="mdi mdi-file-document-outline"></i> Surat/Bukti
          </button>
          <button
            @click="openPhotoModal(row)"
            class="btn-action-detail"
            title="Lihat Foto Clock In & Clock Out"
          >
            <i class="mdi mdi-camera-outline"></i> Foto
          </button>
          <button @click="openManualAttendance(row)" class="btn-action-edit" title="Input Kehadiran Manual">
            <i class="mdi mdi-clock-edit-outline"></i> Absen Manual
          </button>
        </div>
      </template>
    </CrudPage>

    <!-- Modal Preview Foto Absen -->
    <div class="modal fade" id="photoPreviewModal" tabindex="-1" aria-hidden="true" ref="photoModalRef">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow-lg border-0">
          <div class="modal-header bg-dark text-white py-2">
            <h5 class="modal-title fs-6 d-flex align-items-center gap-2">
              <i class="mdi mdi-camera text-info fs-5"></i>
              <span>Foto Kehadiran - <strong>{{ selectedRow?.name || '-' }}</strong></span>
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
                    <span class="badge bg-white text-primary" v-if="selectedRow?.clock_in_at">{{ selectedRow.clock_in_at }}</span>
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
                    <span class="badge bg-white text-danger" v-if="selectedRow?.clock_out_at">{{ selectedRow.clock_out_at }}</span>
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

              <!-- Foto / Dokumen Lampiran Surat Dokter / Izin jika ada -->
              <div class="col-12" v-if="selectedRow?.attachment_url">
                <div class="card shadow-sm border-0">
                  <div class="card-header bg-warning text-dark py-2 fw-bold small d-flex justify-content-between align-items-center">
                    <span><i class="mdi mdi-file-document-outline me-1"></i> Lampiran Surat Keterangan / Bukti</span>
                    <a :href="selectedRow.attachment_url" target="_blank" class="btn btn-sm btn-outline-dark py-0 px-2 small">
                      <i class="mdi mdi-open-in-new"></i> Buka File
                    </a>
                  </div>
                  <div class="card-body text-center p-2 bg-white rounded-bottom">
                    <template v-if="isPdf(selectedRow.attachment_url)">
                      <div class="py-3">
                        <i class="mdi mdi-file-pdf-box text-danger display-4 d-block mb-2"></i>
                        <span class="d-block text-muted small mb-2">Dokumen Surat Keterangan format PDF</span>
                        <a :href="selectedRow.attachment_url" target="_blank" class="btn btn-primary btn-sm">
                          <i class="mdi mdi-download me-1"></i> Lihat / Unduh PDF
                        </a>
                      </div>
                    </template>
                    <template v-else>
                      <a :href="selectedRow.attachment_url" target="_blank" title="Klik untuk memperbesar">
                        <img :src="selectedRow.attachment_url" class="img-fluid rounded border" style="max-height: 350px; object-fit: contain;" alt="Lampiran Bukti" />
                      </a>
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

    <!-- Modal Preview Dokumen Lampiran (Surat Izin / Sakit) -->
    <div class="modal fade" id="attachmentPreviewModal" tabindex="-1" aria-hidden="true" ref="attachmentModalRef">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow-lg border-0">
          <div class="modal-header bg-primary text-white py-2">
            <h5 class="modal-title fs-6 d-flex align-items-center gap-2">
              <i class="mdi mdi-file-document-outline fs-5"></i>
              <span>Lampiran Dokumen - <strong>{{ selectedRow?.name || '-' }}</strong></span>
            </h5>
            <button type="button" class="btn-close btn-close-white" @click="closeAttachmentPreview"></button>
          </div>
          <div class="modal-body p-3 bg-light">
            <div class="card shadow-sm border-0 mb-2">
              <div class="card-header bg-white py-2 small d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                  <span class="badge bg-info me-2">{{ (selectedRow?.status || '').toUpperCase() }}</span>
                  <span class="text-muted"><i class="mdi mdi-calendar me-1"></i>{{ selectedRow?.date || '-' }}</span>
                </div>
                <div v-if="selectedRow?.attachment_url">
                  <a :href="selectedRow.attachment_url" target="_blank" download class="btn btn-sm btn-outline-primary py-1 px-2">
                    <i class="mdi mdi-open-in-new me-1"></i> Buka / Unduh File Asli
                  </a>
                </div>
              </div>
              <div class="card-body text-center p-3 bg-white" style="min-height: 380px;">
                <template v-if="isPdf(selectedRow?.attachment_url)">
                  <div class="d-flex flex-column align-items-center justify-content-center py-5">
                    <i class="mdi mdi-file-pdf-box text-danger display-3 mb-2"></i>
                    <h6 class="fw-bold">Dokumen Surat Keterangan (PDF)</h6>
                    <p class="text-muted small mb-3">Dokumen ini berformat PDF. Klik tombol di bawah untuk membuka.</p>
                    <a :href="selectedRow?.attachment_url" target="_blank" class="btn btn-primary px-3 py-2">
                      <i class="mdi mdi-open-in-new me-1"></i> Buka PDF di Tab Baru
                    </a>
                  </div>
                </template>
                <template v-else-if="selectedRow?.attachment_url">
                  <a :href="selectedRow.attachment_url" target="_blank" title="Klik untuk membuka ukuran penuh">
                    <img :src="selectedRow.attachment_url" class="img-fluid rounded border shadow-xs" style="max-height: 500px; object-fit: contain;" alt="Lampiran Dokumen" />
                  </a>
                </template>
                <template v-else>
                  <div class="text-muted py-5">
                    <i class="mdi mdi-file-alert-outline fs-1 d-block mb-1 opacity-50"></i>
                    <span>Tidak ada file lampiran</span>
                  </div>
                </template>
              </div>
            </div>
            <div v-if="selectedRow?.notes && selectedRow?.notes !== '-'" class="p-2 bg-white rounded border small text-muted">
              <strong>Keterangan:</strong> {{ selectedRow.notes }}
            </div>
          </div>
          <div class="modal-footer py-2">
            <button type="button" class="btn btn-sm btn-secondary" @click="closeAttachmentPreview">Tutup</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Absen Manual -->
    <div class="modal fade" id="manualAttendanceModal" tabindex="-1" aria-hidden="true" ref="modalRef">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Input Absen Manual</h5>
            <button type="button" class="btn-close" @click="closeManualAttendance"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Karyawan</label>
              <input type="text" class="form-control" :value="form.employee_name" readonly disabled />
            </div>
            <div class="mb-3">
              <label class="form-label">Tanggal</label>
              <input type="date" class="form-control" v-model="form.date" readonly disabled />
            </div>
            <div class="mb-3">
              <label class="form-label">Status Kehadiran <span class="text-danger">*</span></label>
              <select class="form-select" v-model="form.status" required>
                <option value="present">Hadir (Present)</option>
                <option value="absent">Mangkir (Absent)</option>
                <option value="late">Terlambat (Late)</option>
                <option value="permission">Izin (Permission)</option>
                <option value="sick">Sakit (Sick)</option>
                <option value="leave">Cuti (Leave)</option>
                <option value="off">Jatah Libur (Off Shift)</option>
                <option value="holiday">Libur Perusahaan (Holiday)</option>
                <option value="permit_early_leave">Permit Pulang Awal</option>
                <option value="permit_break">Permit Keluar Sebentar (Nanti Balik Lagi)</option>
              </select>
            </div>
            <div class="mb-3" v-if="['present', 'late', 'permit_early_leave', 'permit_break', 'permit'].includes(form.status)">
              <div class="row">
                <div class="col-6">
                  <label class="form-label">Jam Masuk (Clock In)</label>
                  <input type="time" class="form-control" v-model="form.clock_in_at" />
                </div>
                <div class="col-6" v-if="['present', 'late', 'permit_early_leave', 'permit'].includes(form.status)">
                  <label class="form-label">Jam Keluar (Clock Out)</label>
                  <input type="time" class="form-control" v-model="form.clock_out_at" />
                </div>
              </div>
            </div>
            <div class="mb-3" v-if="['permit_break'].includes(form.status)">
              <div class="row">
                <div class="col-6">
                  <label class="form-label">Jam Keluar Sebentar (Break Start)</label>
                  <input type="time" class="form-control" v-model="form.break_start_at" />
                </div>
                <div class="col-6">
                  <label class="form-label">Jam Kembali (Break End)</label>
                  <input type="time" class="form-control" v-model="form.break_end_at" />
                </div>
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label">Keterangan / Remark</label>
              <textarea class="form-control" rows="2" v-model="form.notes" placeholder="Catatan/keterangan kehadiran..."></textarea>
            </div>
            <!-- Upload Lampiran untuk Izin / Sakit / Cuti / Permit -->
            <div class="mb-3" v-if="['sick', 'permission', 'permit', 'permit_early_leave', 'permit_break', 'leave'].includes(form.status)">
              <label class="form-label d-flex justify-content-between align-items-center mb-1">
                <span>
                  Lampiran Dokumen / Surat
                  <span class="text-danger" v-if="form.status === 'sick'">*</span>
                </span>
                <span class="text-muted" style="font-size: 11px;">(Surat Dokter / Bukti Izin, maks. 10MB)</span>
              </label>
              <input
                type="file"
                class="form-control"
                accept="image/*,application/pdf"
                @change="handleFileUpload"
                ref="fileInputRef"
              />
              <div v-if="attachmentFile" class="form-text text-success d-flex align-items-center gap-1 mt-1 small">
                <i class="mdi mdi-check-circle"></i>
                <span>File terpilih: <strong>{{ attachmentFile.name }}</strong> ({{ (attachmentFile.size / 1024).toFixed(1) }} KB)</span>
                <button type="button" class="btn btn-sm btn-link text-danger p-0 ms-2 text-decoration-none" @click="clearAttachment">Batal</button>
              </div>
              <div v-else-if="existingAttachmentUrl" class="form-text mt-1 d-flex align-items-center gap-1 small">
                <i class="mdi mdi-file-check-outline text-info"></i>
                <span>Lampiran tersimpan:</span>
                <a :href="existingAttachmentUrl" target="_blank" class="fw-semibold text-primary">Lihat File Saat Ini</a>
              </div>
            </div>
          </div>
          <div class="modal-footer flex-between">
            <button type="button" class="btn btn-light" @click="closeManualAttendance">Batal</button>
            <button type="button" class="btn btn-primary" @click="saveManualAttendance" :disabled="saving">
              <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
              Simpan Kehadiran
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, reactive } from 'vue'
import CrudPage from '../components/CrudPage.vue'
import api from '../api'

const fmtTime = (v) => {
  if (!v) return '-'
  if (typeof v === 'string' && v.includes(':') && !v.includes('T') && !v.includes(' ')) {
    const parts = v.trim().split(':')
    if (parts.length >= 2) return `${parts[0].padStart(2, '0')}:${parts[1].padStart(2, '0')}`
  }
  const d = new Date(v)
  if (isNaN(d.getTime())) return v 
  const hours = String(d.getHours()).padStart(2, '0')
  const minutes = String(d.getMinutes()).padStart(2, '0')
  return `${hours}:${minutes}`
}

const dailyColumns = [
  { key: 'date', label: 'Tanggal', width: '110px' },
  { key: 'name', label: 'Karyawan', width: '220px' },
  { key: 'branch', label: 'Area Penempatan' },
  { key: 'department', label: 'Departemen' },
  { key: 'shift_name', label: 'Shift' },
  { key: 'clock_in_at', label: 'CI', format: fmtTime },
  { key: 'clock_out_at', label: 'CO', format: fmtTime },
  { key: 'late_minutes', label: 'Telat', format: v => v ? `${v} mnt` : '-' },
  { key: 'working_hours', label: 'Jam Kerja', format: v => v ? `${v} jam` : '-' },
  { key: 'status', label: 'Status' },
  { key: 'notes', label: 'Keterangan', width: '220px', format: v => v || '-' },
]

const crudRef = ref(null)
const startDate = ref(new Date().toISOString().split('T')[0])
const endDate = ref(new Date().toISOString().split('T')[0])
const baseFilters = ref({ start_date: startDate.value, end_date: endDate.value })

const exportFilename = computed(() => {
  const s = startDate.value.replace(/-/g, '')
  const e = endDate.value.replace(/-/g, '')
  return `Laporan_Kehadiran_${s}_sd_${e}`
})

const applyDateFilter = () => {
  baseFilters.value = { start_date: startDate.value, end_date: endDate.value }
  if (crudRef.value) {
    if (typeof crudRef.value.doSearch === 'function') {
      crudRef.value.doSearch()
    } else if (typeof crudRef.value.load === 'function') {
      crudRef.value.load()
    }
  }
}

const dailyFilters = ref([
  { key: 'company_id', label: 'Perusahaan (Legal)', type: 'relation', relationEndpoint: '/companies' },
  { key: 'branch_id', label: 'Area Penempatan', type: 'relation', relationEndpoint: '/branches' },
  { key: 'department_id', label: 'Departemen', type: 'relation', relationEndpoint: '/departments' },
  { key: 'division_id', label: 'Divisi', type: 'relation', relationEndpoint: '/divisions' },
  { key: 'position_id', label: 'Jabatan', type: 'relation', relationEndpoint: '/positions' },
  { key: 'status', label: 'Status', options: [
    { value: 'present', label: 'Hadir' },
    { value: 'late', label: 'Terlambat' },
    { value: 'absent', label: 'Mangkir' },
    { value: 'permission', label: 'Izin' },
    { value: 'sick', label: 'Sakit' },
    { value: 'leave', label: 'Cuti' },
    { value: 'permit', label: 'Permit' },
    { value: 'off', label: 'Jatah Libur' },
    { value: 'holiday', label: 'Libur Perusahaan' },
  ]},
])

// PHOTO PREVIEW MODAL LOGIC
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

// ATTACHMENT PREVIEW MODAL LOGIC (SURAT IJIN / SAKIT)
let bsAttachmentModal = null
const attachmentModalRef = ref(null)

const isPdf = (url) => {
  if (!url || typeof url !== 'string') return false
  const clean = url.split('?')[0].toLowerCase()
  return clean.endsWith('.pdf')
}

const openAttachmentModal = (row) => {
  selectedRow.value = row
  bsAttachmentModal?.show()
}

const closeAttachmentPreview = () => {
  bsAttachmentModal?.hide()
}

// MANUAL ATTENDANCE LOGIC
let bsModal = null
const modalRef = ref(null)
const saving = ref(false)
const fileInputRef = ref(null)
const attachmentFile = ref(null)
const existingAttachmentUrl = ref(null)

const form = reactive({
  employee_id: null,
  employee_name: '',
  date: '',
  status: 'present',
  clock_in_at: '',
  clock_out_at: '',
  break_start_at: '',
  break_end_at: '',
  notes: ''
})

onMounted(() => {
  if (typeof window !== 'undefined' && window.bootstrap) {
    if (modalRef.value) bsModal = new window.bootstrap.Modal(modalRef.value)
    if (photoModalRef.value) bsPhotoModal = new window.bootstrap.Modal(photoModalRef.value)
    if (attachmentModalRef.value) bsAttachmentModal = new window.bootstrap.Modal(attachmentModalRef.value)
  }
})

const handleFileUpload = (e) => {
  const file = e.target.files?.[0]
  if (file) {
    if (file.size > 10 * 1024 * 1024) {
      alert('Ukuran file maksimal 10MB')
      if (fileInputRef.value) fileInputRef.value.value = ''
      attachmentFile.value = null
      return
    }
    attachmentFile.value = file
  } else {
    attachmentFile.value = null
  }
}

const clearAttachment = () => {
  attachmentFile.value = null
  if (fileInputRef.value) {
    fileInputRef.value.value = ''
  }
}

const openManualAttendance = (row) => {
  form.employee_id = row.id // id in response is employee_id
  form.employee_name = row.name
  form.date = row.date || startDate.value
  
  // Reset attachment upload
  clearAttachment()
  existingAttachmentUrl.value = row.attachment_url || null

  if (row.status === 'permit') {
    if (row.break_start_at || row.break_end_at) {
      form.status = 'permit_break'
    } else {
      form.status = 'permit_early_leave'
    }
  } else {
    form.status = row.status && row.status !== '-' ? row.status : 'present'
  }
  
  form.notes = row.notes && row.notes !== '-' ? row.notes : ''
  
  if (row.clock_in_at && typeof row.clock_in_at === 'string' && row.clock_in_at.includes(':')) {
    form.clock_in_at = row.clock_in_at.slice(0, 5)
  } else {
    form.clock_in_at = '08:00'
  }
  
  if (row.clock_out_at && typeof row.clock_out_at === 'string' && row.clock_out_at.includes(':')) {
    form.clock_out_at = row.clock_out_at.slice(0, 5)
  } else {
    form.clock_out_at = ''
  }

  if (row.break_start_at && typeof row.break_start_at === 'string' && row.break_start_at.includes(':')) {
    form.break_start_at = row.break_start_at.slice(0, 5)
  } else {
    form.break_start_at = ''
  }

  if (row.break_end_at && typeof row.break_end_at === 'string' && row.break_end_at.includes(':')) {
    form.break_end_at = row.break_end_at.slice(0, 5)
  } else {
    form.break_end_at = ''
  }
  
  bsModal?.show()
}

const closeManualAttendance = () => {
  bsModal?.hide()
}

const saveManualAttendance = async () => {
  if (!form.status) return alert('Pilih status kehadiran')
  
  saving.value = true
  try {
    const formData = new FormData()
    formData.append('employee_id', form.employee_id)
    formData.append('date', form.date)

    let statusVal = form.status
    if (['permit_early_leave', 'permit_break'].includes(statusVal)) {
      statusVal = 'permit'
    }
    formData.append('status', statusVal)

    if (['present', 'late', 'permit', 'permit_early_leave', 'permit_break'].includes(form.status)) {
      if (form.clock_in_at) formData.append('clock_in_at', form.clock_in_at.slice(0, 5))
      if (form.clock_out_at) formData.append('clock_out_at', form.clock_out_at.slice(0, 5))
      if (form.break_start_at) formData.append('break_start_at', form.break_start_at.slice(0, 5))
      if (form.break_end_at) formData.append('break_end_at', form.break_end_at.slice(0, 5))
    }

    if (form.notes && form.notes.trim()) {
      formData.append('notes', form.notes.trim())
    }

    if (attachmentFile.value) {
      formData.append('attachment', attachmentFile.value)
    }
    
    await api.post('/attendance/admin/manual', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
    
    clearAttachment()
    closeManualAttendance()
    if (crudRef.value) {
      if (typeof crudRef.value.doSearch === 'function') {
        crudRef.value.doSearch()
      } else if (typeof crudRef.value.load === 'function') {
        crudRef.value.load()
      }
    }
  } catch (error) {
    alert(error.response?.data?.message || 'Gagal menyimpan kehadiran')
  } finally {
    saving.value = false
  }
}
</script>
