<template>
  <div class="warnings-page">
    <!-- Top Stat Cards -->
    <div class="row g-3 mb-4">
      <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm rounded-3 h-100 overflow-hidden" style="border-left: 4px solid #dc3545 !important;">
          <div class="card-body p-3 d-flex align-items-center justify-content-between">
            <div>
              <div class="text-muted small fw-medium text-uppercase mb-1">Perlu Tindakan (Ter-flagging)</div>
              <h3 class="fw-bold mb-0 text-danger">{{ flaggedList.length }} <span class="fs-6 fw-normal text-muted">Karyawan</span></h3>
              <div class="small text-muted mt-1">Berdasarkan KPI & Absensi</div>
            </div>
            <div class="rounded-circle p-3 bg-danger bg-opacity-10 text-danger">
              <i class="mdi mdi-account-alert font-size-24"></i>
            </div>
          </div>
        </div>
      </div>

      <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm rounded-3 h-100 overflow-hidden" style="border-left: 4px solid #ffc107 !important;">
          <div class="card-body p-3 d-flex align-items-center justify-content-between">
            <div>
              <div class="text-muted small fw-medium text-uppercase mb-1">Surat Peringatan Aktif</div>
              <h3 class="fw-bold mb-0 text-warning">{{ activeWarningsCount }} <span class="fs-6 fw-normal text-muted">SP</span></h3>
              <div class="small text-muted mt-1">Dalam masa berlaku (6 Bulan)</div>
            </div>
            <div class="rounded-circle p-3 bg-warning bg-opacity-10 text-warning">
              <i class="mdi mdi-alert-circle-outline font-size-24"></i>
            </div>
          </div>
        </div>
      </div>

      <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm rounded-3 h-100 overflow-hidden" style="border-left: 4px solid #008779 !important;">
          <div class="card-body p-3 d-flex align-items-center justify-content-between">
            <div>
              <div class="text-muted small fw-medium text-uppercase mb-1">Total Riwayat SP</div>
              <h3 class="fw-bold mb-0 text-primary">{{ totalWarningsCount }} <span class="fs-6 fw-normal text-muted">Diterbitkan</span></h3>
              <div class="small text-muted mt-1">Semua catatan disiplin</div>
            </div>
            <div class="rounded-circle p-3 bg-primary bg-opacity-10 text-primary">
              <i class="mdi mdi-file-document-multiple-outline font-size-24"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
      <ul class="nav nav-pills bg-light p-1 rounded-3 border">
        <li class="nav-item">
          <button 
            type="button"
            class="nav-link px-3 py-2 fw-medium rounded-2 d-flex align-items-center"
            :class="{ 'active bg-danger text-white shadow-sm': activeTab === 'flagged', 'text-secondary': activeTab !== 'flagged' }"
            @click="activeTab = 'flagged'"
          >
            <i class="mdi mdi-account-alert-outline me-2 font-size-18"></i>
            Karyawan Ter-flagging (Review SP)
            <span v-if="flaggedList.length" class="badge rounded-pill ms-2" :class="activeTab === 'flagged' ? 'bg-white text-danger' : 'bg-danger text-white'">
              {{ flaggedList.length }}
            </span>
          </button>
        </li>
        <li class="nav-item">
          <button 
            type="button"
            class="nav-link px-3 py-2 fw-medium rounded-2 d-flex align-items-center"
            :class="{ 'active bg-primary text-white shadow-sm': activeTab === 'all', 'text-secondary': activeTab !== 'all' }"
            @click="activeTab = 'all'"
          >
            <i class="mdi mdi-format-list-bulleted me-2 font-size-18"></i>
            Daftar Surat Peringatan (SP)
          </button>
        </li>
      </ul>

      <div class="d-flex gap-2">
        <button 
          v-if="activeTab === 'flagged'" 
          class="btn btn-outline-secondary btn-sm px-3 rounded-2" 
          @click="fetchFlaggedCandidates" 
          :disabled="loadingFlagged"
        >
          <i class="mdi mdi-refresh me-1" :class="{ 'mdi-spin': loadingFlagged }"></i> Refresh Analisis
        </button>
      </div>
    </div>

    <!-- TAB 1: KARYAWAN TER-FLAGGING -->
    <div v-if="activeTab === 'flagged'">
      <!-- Legal Notice & Info Banner -->
      <div class="alert alert-info border-0 shadow-sm rounded-3 d-flex align-items-start gap-3 p-3 mb-3" style="background-color: #e8f4fd;">
        <i class="mdi mdi-shield-alert-outline text-info font-size-24 mt-1"></i>
        <div class="font-size-13 text-secondary">
          <strong class="text-dark d-block mb-1">Kepatuhan Hubungan Industrial & UU Ketenagakerjaan (PP No. 35/2021)</strong>
          Daftar ini adalah rekomendasi/peringatan dini (*early warning*) bagi HRD berdasarkan deteksi performa KPI yang rendah (&lt; 70), ketidakhadiran tanpa keterangan (mangkir), dan catatan evaluasi probasi.
          Sistem sengaja <strong>tidak menerbitkan SP secara otomatis</strong> demi keabsahan hukum formil. HRD disarankan melakukan klarifikasi/pembinaan terlebih dahulu, lalu menekan tombol <strong>Terbitkan SP</strong> jika terbukti memenuhi unsur pelanggaran tata tertib kerja.
        </div>
      </div>

      <!-- Filters for Flagged -->
      <div class="card border-0 shadow-sm rounded-3 mb-3">
        <div class="card-body p-3">
          <div class="row g-2 align-items-center">
            <div class="col-md-5">
              <div class="input-group input-group-sm">
                <span class="input-group-text bg-light border-0"><i class="mdi mdi-magnify"></i></span>
                <input 
                  type="text" 
                  v-model="flaggedSearch" 
                  class="form-control bg-light border-0" 
                  placeholder="Cari nama karyawan, NIK, atau departemen..."
                />
              </div>
            </div>
            <div class="col-md-4">
              <select v-model="flaggedCategoryFilter" class="form-select form-select-sm bg-light border-0">
                <option value="">Semua Kategori Flagging</option>
                <option value="kpi">KPI Rendah (&lt; 70)</option>
                <option value="attendance">Mangkir / Ketidakhadiran</option>
                <option value="tardiness">Keterlambatan Berat</option>
                <option value="probation">Catatan Probasi</option>
              </select>
            </div>
            <div class="col-md-3 text-end">
              <span class="text-muted small">Menampilkan <strong>{{ filteredFlaggedList.length }}</strong> dari {{ flaggedList.length }} karyawan</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Flagged Candidates Table -->
      <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light font-size-13 text-uppercase text-secondary">
                <tr>
                  <th style="width: 50px;" class="text-center">No</th>
                  <th>Karyawan</th>
                  <th>Departemen / Jabatan</th>
                  <th>Indikator Pelanggaran / Flagging</th>
                  <th>Status SP Saat Ini</th>
                  <th>Rekomendasi</th>
                  <th class="text-end" style="width: 160px;">Aksi</th>
                </tr>
              </thead>
              <tbody class="font-size-13">
                <tr v-if="loadingFlagged">
                  <td colspan="7" class="text-center py-5 text-muted">
                    <i class="mdi mdi-spin mdi-loading font-size-24 d-block mb-2 text-primary"></i>
                    Menganalisis data KPI, absensi, dan riwayat disiplin karyawan...
                  </td>
                </tr>

                <tr v-else-if="filteredFlaggedList.length === 0">
                  <td colspan="7" class="text-center py-5">
                    <div class="py-4">
                      <div class="rounded-circle bg-success bg-opacity-10 text-success d-inline-flex p-3 mb-2">
                        <i class="mdi mdi-check-decagram font-size-36"></i>
                      </div>
                      <h6 class="fw-bold text-dark mt-2">Tidak Ada Karyawan Ter-flagging</h6>
                      <p class="text-muted small mb-0">Semua performa KPI dan absensi karyawan saat ini berada dalam batas normal.</p>
                    </div>
                  </td>
                </tr>

                <tr v-for="(item, idx) in filteredFlaggedList" :key="item.employee_id">
                  <td class="text-center text-muted">{{ idx + 1 }}</td>
                  <td>
                    <div class="d-flex align-items-center">
                      <img 
                        :src="item.avatar || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(item.name) + '&background=random'" 
                        class="rounded-circle me-2 border" 
                        style="width: 36px; height: 36px; object-fit: cover;"
                        alt="avatar"
                      />
                      <div>
                        <span class="fw-bold text-dark d-block">{{ item.name }}</span>
                        <span class="text-muted font-size-11">{{ item.employee_code }}</span>
                      </div>
                    </div>
                  </td>
                  <td>
                    <span class="d-block fw-medium text-dark">{{ item.department }}</span>
                    <span class="text-muted font-size-12">{{ item.position }}</span>
                  </td>
                  <td>
                    <div class="d-flex flex-wrap gap-1 mb-1">
                      <span 
                        v-for="(flag, fIdx) in item.flags" 
                        :key="fIdx"
                        class="badge rounded-pill"
                        :class="{
                          'bg-danger': flag.severity === 'danger',
                          'bg-warning text-dark': flag.severity === 'warning',
                          'bg-secondary': flag.severity === 'info'
                        }"
                      >
                        {{ flag.badge }}
                      </span>
                    </div>
                    <ul class="mb-0 ps-3 text-muted font-size-11" style="max-width: 380px;">
                      <li v-for="(flag, fIdx) in item.flags" :key="'det-' + fIdx">
                        {{ flag.detail }}
                      </li>
                    </ul>
                  </td>
                  <td>
                    <div v-if="item.active_warning">
                      <span class="badge bg-warning text-dark mb-1">
                        <i class="mdi mdi-alert me-1"></i>{{ item.active_warning.type_label }} Aktif
                      </span>
                      <div class="font-size-11 text-muted">
                        Berlaku s/d {{ item.active_warning.expired_date || '-' }}
                      </div>
                    </div>
                    <div v-else>
                      <span class="badge bg-light text-muted border">Belum Ada SP Aktif</span>
                      <div v-if="item.history_warnings_count > 0" class="font-size-11 text-muted">
                        {{ item.history_warnings_count }} SP lampau
                      </div>
                    </div>
                  </td>
                  <td>
                    <span 
                      class="badge px-2 py-1"
                      :class="{
                        'bg-danger': item.suggested_type === 'sp3',
                        'bg-warning text-dark': item.suggested_type === 'sp2',
                        'bg-primary': item.suggested_type === 'sp1'
                      }"
                    >
                      <i class="mdi mdi-arrow-right-bold me-1"></i>
                      {{ item.active_warning ? 'Eskalasi ' + item.suggested_type.toUpperCase() : 'Rekomendasi ' + item.suggested_type.toUpperCase() }}
                    </span>
                  </td>
                  <td class="text-end">
                    <button 
                      class="btn btn-sm btn-primary px-3 rounded-2 shadow-sm waves-effect waves-light"
                      @click="openIssueWarningModal(item)"
                      title="Terbitkan Surat Peringatan"
                    >
                      <i class="mdi mdi-file-document-edit me-1"></i> Terbitkan SP
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- TAB 2: SEMUA SURAT PERINGATAN (CRUD) -->
    <div v-if="activeTab === 'all'">
      <CrudPage
        ref="crudPageRef"
        title="Daftar Surat Peringatan (SP)"
        endpoint="/warnings"
        :columns="columns"
        :form-fields="formFields"
        :hide-header="true"
        @loaded="handleCrudLoaded"
      >
        <template #header-actions>
          <button @click="openCreateManual" class="btn btn-primary btn-sm px-3 rounded-2">
            <i class="mdi mdi-plus me-1"></i> Buat SP Manual
          </button>
        </template>

        <template #cell-status="{ row }">
          <span class="badge" :class="{
            'bg-success': row.status === 'active',
            'bg-secondary': row.status === 'expired',
            'bg-danger': row.status === 'revoked',
          }">{{ row.status === 'active' ? 'Aktif' : row.status === 'expired' ? 'Kedaluwarsa' : 'Dicabut' }}</span>
        </template>
        
        <template #cell-warning_type="{ row }">
          <span class="badge" :class="{
            'bg-danger': row.warning_type === 'sp3',
            'bg-warning text-dark': row.warning_type === 'sp2',
            'bg-primary': row.warning_type === 'sp1',
            'bg-secondary': row.warning_type === 'warning'
          }">{{ row.warning_type ? row.warning_type.toUpperCase() : '-' }}</span>
        </template>
        
        <template #cell-employee="{ row }">
          <span class="fw-bold text-dark">{{ row.employee?.name || '-' }}</span>
          <span v-if="row.employee?.employee_id" class="text-muted font-size-11 d-block">{{ row.employee?.employee_id }}</span>
        </template>

        <template #actions="{ row }">
          <button @click="downloadPdf(row)" class="btn-action-secondary" title="Download PDF SP">
            <i class="mdi mdi-file-pdf-box text-danger"></i> PDF
          </button>
        </template>
      </CrudPage>
    </div>

    <!-- MODAL PENERBITAN SP DARI FLAGGED CANDIDATE -->
    <div 
      v-if="issueModalVisible" 
      class="modal fade show d-block" 
      tabindex="-1" 
      style="background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 1055;"
    >
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
          <div class="modal-header bg-light border-bottom py-3 px-4">
            <div>
              <h5 class="modal-title fw-bold text-dark mb-0">
                <i class="mdi mdi-file-document-edit text-danger me-2"></i>Penerbitan Surat Peringatan (SP)
              </h5>
              <span class="font-size-12 text-muted">Penindakan resmi atas temuan flagging KPI / pelanggaran disiplin</span>
            </div>
            <button type="button" class="btn-close" @click="issueModalVisible = false" :disabled="submittingIssue"></button>
          </div>

          <form @submit.prevent="submitIssueWarning">
            <div class="modal-body p-4 font-size-13">
              <!-- Employee Info Header Card -->
              <div class="card bg-light border-0 rounded-3 mb-3 p-3">
                <div class="d-flex align-items-center justify-content-between">
                  <div class="d-flex align-items-center">
                    <img 
                      :src="selectedCandidate?.avatar || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(selectedCandidate?.name || 'User')" 
                      class="rounded-circle me-3 border" 
                      style="width: 44px; height: 44px; object-fit: cover;"
                    />
                    <div>
                      <h6 class="mb-0 fw-bold text-dark">{{ selectedCandidate?.name }}</h6>
                      <span class="text-muted small">{{ selectedCandidate?.employee_code }} &bull; {{ selectedCandidate?.department }} ({{ selectedCandidate?.position }})</span>
                    </div>
                  </div>
                  <div class="text-end">
                    <span v-if="selectedCandidate?.active_warning" class="badge bg-warning text-dark">
                      Status Saat Ini: {{ selectedCandidate?.active_warning.type_label }}
                    </span>
                    <span v-else class="badge bg-secondary">
                      Belum Ada SP Aktif
                    </span>
                  </div>
                </div>
              </div>

              <div class="row g-3">
                <!-- Warning Type -->
                <div class="col-md-6">
                  <label class="form-label fw-semibold text-secondary small mb-1">
                    Tingkat Peringatan <span class="text-danger">*</span>
                  </label>
                  <select v-model="issueForm.warning_type" class="form-select" required>
                    <option value="warning">Teguran Lisan / Peringatan</option>
                    <option value="sp1">Surat Peringatan Pertama (SP 1)</option>
                    <option value="sp2">Surat Peringatan Kedua (SP 2)</option>
                    <option value="sp3">Surat Peringatan Ketiga / Terakhir (SP 3)</option>
                  </select>
                </div>

                <!-- Status -->
                <div class="col-md-6">
                  <label class="form-label fw-semibold text-secondary small mb-1">
                    Status SP <span class="text-danger">*</span>
                  </label>
                  <select v-model="issueForm.status" class="form-select" required>
                    <option value="active">Aktif (Berlaku)</option>
                    <option value="expired">Kedaluwarsa</option>
                    <option value="revoked">Dicabut</option>
                  </select>
                </div>

                <!-- Issue Date -->
                <div class="col-md-6">
                  <label class="form-label fw-semibold text-secondary small mb-1">
                    Tanggal Dikeluarkan <span class="text-danger">*</span>
                  </label>
                  <input 
                    type="date" 
                    v-model="issueForm.issue_date" 
                    class="form-control" 
                    @change="handleIssueDateChange"
                    required 
                  />
                </div>

                <!-- Expired Date -->
                <div class="col-md-6">
                  <label class="form-label fw-semibold text-secondary small mb-1">
                    Tanggal Kedaluwarsa (Masa Berlaku)
                  </label>
                  <input type="date" v-model="issueForm.expired_date" class="form-control" />
                  <div class="form-text font-size-11 text-muted">
                    Standar PP 35/2021: Berlaku selama 6 bulan sejak tanggal diterbitkan.
                  </div>
                </div>

                <!-- Reason / Basis for Warning -->
                <div class="col-12">
                  <label class="form-label fw-semibold text-secondary small mb-1">
                    Alasan / Uraian Pelanggaran <span class="text-danger">*</span>
                  </label>
                  <textarea 
                    v-model="issueForm.reason" 
                    class="form-control font-monospace" 
                    rows="5" 
                    placeholder="Tuliskan uraian pelanggaran tata tertib / kinerja..."
                    required
                  ></textarea>
                  <div class="form-text font-size-11 text-muted">
                    Draf alasan telah diisi otomatis berdasarkan hasil evaluasi sistem. Anda dapat mengedit atau menambahkan hasil klarifikasi tatap muka.
                  </div>
                </div>

                <!-- Evidence File Upload -->
                <div class="col-12">
                  <label class="form-label fw-semibold text-secondary small mb-1">
                    Lampiran Bukti / Berita Acara (Opsional)
                  </label>
                  <input 
                    type="file" 
                    @change="handleEvidenceFileChange" 
                    class="form-control" 
                    accept=".jpg,.jpeg,.png,.pdf"
                  />
                  <div class="form-text font-size-11 text-muted">
                    Format: PDF, JPG, PNG (Maks 5 MB). Lampirkan rekapan absensi atau lembar evaluasi jika ada.
                  </div>
                </div>
              </div>
            </div>

            <div class="modal-footer bg-light border-top py-3 px-4">
              <button 
                type="button" 
                class="btn btn-light px-4 rounded-3" 
                @click="issueModalVisible = false" 
                :disabled="submittingIssue"
              >
                Batal
              </button>
              <button 
                type="submit" 
                class="btn btn-danger px-4 rounded-3 shadow-sm waves-effect waves-light" 
                :disabled="submittingIssue"
              >
                <i v-if="submittingIssue" class="mdi mdi-spin mdi-loading me-1"></i>
                <i v-else class="mdi mdi-check me-1"></i>
                {{ submittingIssue ? 'Menerbitkan...' : 'Terbitkan Surat Peringatan' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import CrudPage from './CrudPage.vue'
import api from '../api'
import Swal from 'sweetalert2'

// State
const activeTab = ref('flagged')
const crudPageRef = ref(null)

const flaggedList = ref([])
const loadingFlagged = ref(false)
const flaggedSearch = ref('')
const flaggedCategoryFilter = ref('')

const totalWarningsCount = ref(0)
const activeWarningsCount = ref(0)

// Modal State
const issueModalVisible = ref(false)
const selectedCandidate = ref(null)
const submittingIssue = ref(false)
const evidenceFile = ref(null)

const issueForm = ref({
  employee_id: null,
  warning_type: 'sp1',
  issue_date: '',
  expired_date: '',
  reason: '',
  status: 'active',
})

// Columns for CrudPage
const columns = [
  { key: 'employee', label: 'Karyawan', sub: 'name' },
  { key: 'warning_type', label: 'Tipe SP' },
  { key: 'issue_date', label: 'Tgl Dikeluarkan' },
  { key: 'expired_date', label: 'Tgl Kedaluwarsa' },
  { key: 'status', label: 'Status' },
]

// Form fields for manual creation in CrudPage
const formFields = [
  { key: 'employee_id', label: 'Karyawan', type: 'relation', relationEndpoint: '/employees', required: true },
  { key: 'warning_type', label: 'Tipe Peringatan', type: 'select', options: [
    { value: 'sp1', label: 'SP 1' },
    { value: 'sp2', label: 'SP 2' },
    { value: 'sp3', label: 'SP 3' },
    { value: 'warning', label: 'Teguran Lisan' },
  ], required: true },
  { key: 'issue_date', label: 'Tgl Dikeluarkan', type: 'date', required: true },
  { key: 'expired_date', label: 'Tgl Kedaluwarsa', type: 'date' },
  { key: 'reason', label: 'Alasan', type: 'textarea', required: true },
  { key: 'evidence', label: 'Bukti (URL / File)', type: 'text' },
  { key: 'status', label: 'Status', type: 'select', options: [
    { value: 'active', label: 'Aktif' },
    { value: 'expired', label: 'Kedaluwarsa' },
    { value: 'revoked', label: 'Dicabut' },
  ] },
]

// Filtered Flagged Candidates
const filteredFlaggedList = computed(() => {
  let list = flaggedList.value
  if (flaggedSearch.value) {
    const q = flaggedSearch.value.toLowerCase()
    list = list.filter(item => 
      item.name.toLowerCase().includes(q) ||
      item.employee_code.toLowerCase().includes(q) ||
      item.department.toLowerCase().includes(q) ||
      item.position.toLowerCase().includes(q)
    )
  }

  if (flaggedCategoryFilter.value) {
    const cat = flaggedCategoryFilter.value
    list = list.filter(item => item.flags.some(f => f.category === cat))
  }

  return list
})

// Helper: Hitung tanggal + 6 bulan
const addSixMonths = (dateStr) => {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  if (isNaN(d.getTime())) return ''
  d.setMonth(d.getMonth() + 6)
  return d.toISOString().split('T')[0]
}

const handleIssueDateChange = () => {
  if (issueForm.value.issue_date) {
    issueForm.value.expired_date = addSixMonths(issueForm.value.issue_date)
  }
}

const handleEvidenceFileChange = (e) => {
  const file = e.target.files?.[0]
  evidenceFile.value = file || null
}

// Fetch Flagged Candidates from API
const fetchFlaggedCandidates = async () => {
  loadingFlagged.value = true
  try {
    const res = await api.get('/warnings/flagged')
    flaggedList.value = res.data?.data || []
  } catch (err) {
    console.error('Gagal mengambil daftar flagged candidates:', err)
  } finally {
    loadingFlagged.value = false
  }
}

// Fetch General Warnings Statistics
const fetchWarningsStats = async () => {
  try {
    const res = await api.get('/warnings?per_page=100')
    const items = res.data?.data?.items || []
    totalWarningsCount.value = res.data?.data?.pagination?.total ?? items.length
    activeWarningsCount.value = items.filter(w => w.status === 'active').length
  } catch (err) {
    console.error('Gagal mengambil statistik warnings:', err)
  }
}

// Open Modal to Issue Warning for Flagged Candidate
const openIssueWarningModal = (candidate) => {
  selectedCandidate.value = candidate
  const today = new Date().toISOString().split('T')[0]
  
  issueForm.value = {
    employee_id: candidate.employee_id,
    warning_type: candidate.suggested_type || 'sp1',
    issue_date: today,
    expired_date: addSixMonths(today),
    reason: candidate.suggested_reason || '',
    status: 'active',
  }
  evidenceFile.value = null
  issueModalVisible.value = true
}

// Submit Warning from Modal
const submitIssueWarning = async () => {
  submittingIssue.value = true
  try {
    const formData = new FormData()
    formData.append('employee_id', issueForm.value.employee_id)
    formData.append('warning_type', issueForm.value.warning_type)
    formData.append('issue_date', issueForm.value.issue_date)
    if (issueForm.value.expired_date) {
      formData.append('expired_date', issueForm.value.expired_date)
    }
    formData.append('reason', issueForm.value.reason)
    formData.append('status', issueForm.value.status)
    if (evidenceFile.value) {
      formData.append('evidence', evidenceFile.value)
    }

    await api.post('/warnings', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })

    await Swal.fire({
      icon: 'success',
      title: 'SP Berhasil Diterbitkan',
      text: `Surat Peringatan untuk ${selectedCandidate.value?.name} telah resmi diterbitkan.`,
      timer: 2000,
      showConfirmButton: false,
    })

    issueModalVisible.value = false
    // Refresh lists
    await fetchFlaggedCandidates()
    await fetchWarningsStats()
    activeTab.value = 'all'
  } catch (err) {
    console.error(err)
    const msg = err.response?.data?.message || 'Gagal menerbitkan Surat Peringatan.'
    Swal.fire({
      icon: 'error',
      title: 'Gagal',
      text: msg,
    })
  } finally {
    submittingIssue.value = false
  }
}

// Download PDF
const downloadPdf = async (row) => {
  try {
    const res = await api.get(`/warnings/${row.id}/pdf`, { responseType: 'blob' })
    const url = window.URL.createObjectURL(new Blob([res.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `SP_${row.employee?.name || 'Karyawan'}_${row.issue_date}.pdf`)
    document.body.appendChild(link)
    link.click()
  } catch (error) {
    console.error(error)
    Swal.fire({
      icon: 'error',
      title: 'Download Gagal',
      text: 'Gagal mendownload PDF Surat Peringatan.'
    })
  }
}

const handleCrudLoaded = (data) => {
  if (data?.pagination?.total !== undefined) {
    totalWarningsCount.value = data.pagination.total
  }
}

const openCreateManual = () => {
  crudPageRef.value?.openCreate?.()
}

onMounted(() => {
  fetchFlaggedCandidates()
  fetchWarningsStats()
})
</script>

<style scoped>
.font-size-11 {
  font-size: 11px;
}
.font-size-12 {
  font-size: 12px;
}
.font-size-13 {
  font-size: 13px;
}
.font-size-18 {
  font-size: 18px;
}
.font-size-24 {
  font-size: 24px;
}
.font-size-36 {
  font-size: 36px;
}
</style>
