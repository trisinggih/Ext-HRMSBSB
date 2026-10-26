<template>
  <div>
    <!-- Card Filter Departemen (Divisi) & Fase Periode Penilaian -->
    <div class="card border-0 shadow-sm rounded-3 mb-3">
      <div class="card-body p-3">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
          <!-- Department Filter Tabs -->
          <div class="flex-grow-1">
            <div class="text-secondary small fw-bold text-uppercase mb-2 d-flex align-items-center gap-1 font-size-11">
              <i class="mdi mdi-domain text-primary"></i> Pisahkan Divisi / Departemen:
            </div>
            <div class="d-flex flex-wrap gap-2">
              <button
                v-for="dept in deptTabs"
                :key="dept.key"
                type="button"
                class="btn btn-sm rounded-pill font-size-12 px-3 py-1-5 d-flex align-items-center gap-2 transition-all"
                :class="selectedDept === dept.key ? 'btn-primary text-white shadow-sm fw-bold' : 'btn-outline-secondary bg-white'"
                @click="selectedDept = dept.key"
              >
                <i :class="dept.icon"></i>
                <span>{{ dept.label }}</span>
                <span 
                  class="badge rounded-pill font-size-11 ms-1"
                  :class="selectedDept === dept.key ? 'bg-white text-primary' : 'bg-light text-secondary border'"
                >
                  {{ deptCounts[dept.key] ?? 0 }}
                </span>
              </button>
            </div>
          </div>

          <!-- Phase Evaluation Filter (Harian, Mingguan, Bulanan, Kuartalan) -->
          <div class="d-flex flex-column align-items-sm-end">
            <div class="text-secondary small fw-bold text-uppercase mb-2 d-flex align-items-center gap-1 font-size-11">
              <i class="mdi mdi-calendar-clock text-info"></i> Fase Periode Penilaian:
            </div>
            <div class="btn-group p-1 bg-light rounded-3 border" role="group">
              <button
                v-for="phase in phaseOptions"
                :key="phase.key"
                type="button"
                class="btn btn-sm px-3 py-1 font-size-12 rounded-2 transition-all fw-medium"
                :class="selectedPhase === phase.key ? 'btn-white text-primary shadow-sm fw-bold bg-white' : 'text-muted border-0 bg-transparent'"
                @click="selectedPhase = phase.key"
              >
                {{ phase.label }}
              </button>
            </div>
          </div>
        </div>

        <!-- Focus Scope: Anak Buah Saya vs Semua Divisi -->
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 pt-2 mt-2 border-top">
          <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="small fw-semibold text-secondary font-size-12">Fokus Penilaian:</span>
            <button
              type="button"
              class="btn btn-sm rounded-pill font-size-12 px-3 py-1 transition-all"
              :class="onlySubordinates ? 'btn-primary text-white shadow-sm fw-bold' : 'btn-outline-secondary bg-white'"
              @click="onlySubordinates = true"
            >
              Anak Buah Saya (Bawahan Langsung)
            </button>
            <button
              type="button"
              class="btn btn-sm rounded-pill font-size-12 px-3 py-1 transition-all"
              :class="!onlySubordinates ? 'btn-primary text-white shadow-sm fw-bold' : 'btn-outline-secondary bg-white'"
              @click="onlySubordinates = false"
            >
              Semua Karyawan Divisi
            </button>
          </div>
          <div class="small text-muted font-size-11" v-if="onlySubordinates">
            Menampilkan lembar penilaian untuk seluruh anak buah langsung di bawah kewenangan Anda.
          </div>
        </div>
      </div>
    </div>

    <CrudPage
      ref="crudRef"
      :title="auth.isSpv ? 'Penilaian KPI Anak Buah' : 'Daftar Penilaian KPI'"
      singular="Penilaian KPI"
      endpoint="/kpi/assessments"
      :columns="columns"
      :filters="filters"
      :base-filters="baseFilters"
      :creatable="true"
      :initial-form="initialForm"
      :map-data-to-form="mapDataToForm"
      @loaded="handleLoaded"
    >
      <template #header-actions>
        <button type="button" class="btn btn-outline-info btn-sm rounded-2" @click="showRubricModal = true">
          <i class="mdi mdi-format-list-checks me-1"></i> Standar Poin Penilaian
        </button>
      </template>

      <template #cell-transaction_number="{ value, row }">
        <router-link :to="'/hrms/kpi/assessments/' + row.id" class="badge bg-light text-primary border border-primary-subtle font-monospace font-size-11 text-decoration-none px-2 py-1 fw-bold">
          {{ value || ('KPI-' + row.id) }}
        </router-link>
      </template>

      <template #cell-employee="{ row }">
        <div class="fw-semibold text-dark">{{ row.employee?.name || '—' }}</div>
        <div class="d-flex align-items-center gap-1 flex-wrap mt-1">
          <span 
            v-if="row.employee?.department_group" 
            class="badge font-size-10 px-2 py-0-5" 
            :class="departmentBadgeClass(row.employee?.department_group)"
          >
            {{ row.employee?.department_group }}
          </span>
          <span class="text-muted font-size-11">
            {{ row.employee?.position?.name || row.employee?.division?.name || (row.employee?.employee_id ? `NIP: ${row.employee.employee_id}` : '—') }}
          </span>
        </div>
      </template>

      <template #cell-period="{ row }">
        <span class="badge bg-light text-secondary border font-size-11">{{ row.period?.name || '—' }}</span>
        <div class="mt-1" v-if="row.period?.type">
          <span class="badge font-size-10 px-2 py-0-5" :class="phaseBadgeClass(row.period.type)">
            {{ phaseLabel(row.period.type) }}
          </span>
        </div>
      </template>
      
      <template #cell-template="{ row }">
        <span class="small text-muted">{{ row.template?.name || '—' }}</span>
      </template>

      <template #cell-reviewer="{ row }">
        <div class="small fw-medium text-dark">{{ row.reviewer?.name || '—' }}</div>
      </template>

      <template #cell-status="{ row }">
        <span class="badge font-size-11 fw-medium" :class="statusBadge(row.status)">{{ statusLabel(row.status) }}</span>
      </template>

      <template #cell-final_score="{ row }">
        <span v-if="row.final_score !== null" class="fw-bold" :class="scoreColor(row.final_score)">
          {{ parseFloat(row.final_score).toFixed(2) }}
        </span>
        <span v-else class="text-muted">—</span>
      </template>

      <template #actions="{ row, openEdit, remove }">
        <router-link :to="`/hrms/kpi/assessments/${row.id}`" class="btn-action-detail" title="Buka Form Penilaian">
          <i class="mdi mdi-clipboard-text-outline"></i> Nilai
        </router-link>
        <button v-if="row.status === 'in_review'" @click="approve(row)" class="btn-action-success" title="Setujui Penilaian">
          <i class="mdi mdi-check-circle-outline"></i> Setujui
        </button>
        <button @click="openEdit(row)" class="btn-action-edit" title="Edit Data Assessment">
          <i class="mdi mdi-pencil-outline"></i> Edit
        </button>
        <button @click="remove(row)" class="btn-action-delete" title="Hapus Assessment">
          <i class="mdi mdi-trash-can-outline"></i> Hapus
        </button>
      </template>

      <!-- Modal Form Tambah / Edit Penilaian KPI -->
      <template #form="{ form, saving, editingId }">
        <div class="row g-3">
          <!-- Periode KPI -->
          <div class="col-md-6">
            <label class="form-label fw-semibold text-secondary small mb-1">
              Periode Penilaian KPI <span class="text-danger">*</span>
            </label>
            <Multiselect
              v-model="form.period_id"
              :options="periodOptions"
              :searchable="true"
              placeholder="Pilih periode penilaian..."
              class="rounded-3 border-0 bg-light shadow-none custom-multiselect"
              required
            />
            <small class="text-muted font-size-11">Periode aktif atau draft tempat evaluasi dicatat.</small>
          </div>

          <!-- Status -->
          <div class="col-md-6">
            <label class="form-label fw-semibold text-secondary small mb-1">
              Status Penilaian <span class="text-danger">*</span>
            </label>
            <select v-model="form.status" class="form-select bg-light border-0 px-3 py-2 shadow-none rounded-3" required>
              <option value="pending">Menunggu (Pending)</option>
              <option value="in_review">Sedang Dinilai (In Review)</option>
              <option value="approved">Disetujui (Approved)</option>
            </select>
            <small class="text-muted font-size-11">Status progres penilaian kinerja.</small>
          </div>

          <!-- Karyawan yang Dinilai -->
          <div class="col-md-12">
            <label class="form-label fw-semibold text-secondary small mb-1">
              Karyawan yang Dinilai <span class="text-danger">*</span>
            </label>
            <Multiselect
              v-model="form.employee_id"
              :options="filteredEmployeeOptions"
              :searchable="true"
              placeholder="Pilih atau cari nama / NIP karyawan..."
              class="rounded-3 border-0 bg-light shadow-none custom-multiselect"
              @change="onEmployeeChanged(form, $event)"
              required
            />
            <small class="text-muted font-size-11">
              Memilih karyawan akan otomatis mendeteksi atasan langsung dan template KPI yang cocok.
            </small>
          </div>

          <!-- Template KPI -->
          <div class="col-md-6">
            <label class="form-label fw-semibold text-secondary small mb-1">
              Template Indikator KPI
            </label>
            <Multiselect
              v-model="form.template_id"
              :options="templateOptions"
              :searchable="true"
              placeholder="Pilih template indikator..."
              class="rounded-3 border-0 bg-light shadow-none custom-multiselect"
            />
            <small class="text-muted font-size-11">
              Opsional: jika kosong, sistem otomatis mencocokkan sesuai jabatan/divisi karyawan.
            </small>
          </div>

          <!-- Penilai / Reviewer -->
          <div class="col-md-6">
            <label class="form-label fw-semibold text-secondary small mb-1">
              Penilai / Atasan (Reviewer)
            </label>
            <Multiselect
              v-model="form.reviewer_id"
              :options="employeeOptions"
              :searchable="true"
              placeholder="Pilih atasan atau penilai..."
              class="rounded-3 border-0 bg-light shadow-none custom-multiselect"
            />
            <small class="text-muted font-size-11">
              Opsional: jika kosong, otomatis menggunakan atasan/supervisor karyawan.
            </small>
          </div>

          <!-- Catatan / Feedback Awal -->
          <div class="col-md-12">
            <label class="form-label fw-semibold text-secondary small mb-1">
              Catatan Tambahan (Opsional)
            </label>
            <textarea
              v-model="form.reviewer_notes"
              class="form-control bg-light border-0 px-3 py-2 shadow-none rounded-3"
              rows="2"
              placeholder="Tambahkan catatan awal atau arahan evaluasi..."
            ></textarea>
          </div>
        </div>
      </template>
    </CrudPage>

    <!-- Modal Panduan Standar Poin Penilaian -->
    <div v-if="showRubricModal" class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5); z-index: 1055;">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-3">
          <div class="modal-header border-bottom p-3">
            <h6 class="modal-title fw-bold text-dark d-flex align-items-center gap-2">
              <i class="mdi mdi-format-list-checks text-primary font-size-18"></i> Standar Poin Penilaian KPI (Skala 0 - 100 & 1 - 5)
            </h6>
            <button type="button" class="btn-close" @click="showRubricModal = false"></button>
          </div>
          <div class="modal-body p-3 font-size-13">
            <p class="text-muted small mb-3">
              Gunakan panduan rubrik poin berikut sebagai acuan objektivitas dalam memberikan penilaian capaian indikator kinerja bawahan:
            </p>
            <div class="table-responsive">
              <table class="table table-bordered align-middle mb-2">
                <thead class="table-light font-size-12">
                  <tr>
                    <th style="width: 150px;">Tingkat Capaian</th>
                    <th style="width: 100px;" class="text-center">Skor (0-100)</th>
                    <th style="width: 90px;" class="text-center">Skala (1-5)</th>
                    <th>Deskripsi Standar Evaluasi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr class="bg-success-subtle">
                    <td>
                      <span class="badge bg-success text-white">Poin 5 · Sangat Baik</span>
                    </td>
                    <td class="text-center fw-bold text-success">90 - 100</td>
                    <td class="text-center fw-bold text-success">4.5 - 5.0</td>
                    <td>Melampaui target yang ditetapkan (≥100%), kualitas kerja sempurna, zero error / zero incident, konsisten berinisiatif tinggi.</td>
                  </tr>
                  <tr class="bg-primary-subtle">
                    <td>
                      <span class="badge bg-primary text-white">Poin 4 · Baik</span>
                    </td>
                    <td class="text-center fw-bold text-primary">80 - 89</td>
                    <td class="text-center fw-bold text-primary">3.5 - 4.4</td>
                    <td>Mencapai target standar (85% - 99%), pekerjaan selesai tuntas tepat waktu sesuai SOP, hanya terdapat catatan perbaikan minor.</td>
                  </tr>
                  <tr class="bg-warning-subtle">
                    <td>
                      <span class="badge bg-warning text-dark">Poin 3 · Cukup</span>
                    </td>
                    <td class="text-center fw-bold text-warning-emphasis">70 - 79</td>
                    <td class="text-center fw-bold text-warning-emphasis">2.5 - 3.4</td>
                    <td>Mendekati target (70% - 84%), pekerjaan tuntas namun ada keterlambatan atau catatan evaluasi yang perlu ditingkatkan.</td>
                  </tr>
                  <tr class="bg-danger-subtle">
                    <td>
                      <span class="badge bg-danger text-white">Poin 2 · Kurang</span>
                    </td>
                    <td class="text-center fw-bold text-danger">60 - 69</td>
                    <td class="text-center fw-bold text-danger">1.5 - 2.4</td>
                    <td>Di bawah target (60% - 69%), sering terjadi keterlambatan, deviasi terhadap SOP, memerlukan pembinaan berkala.</td>
                  </tr>
                  <tr class="bg-light">
                    <td>
                      <span class="badge bg-dark text-white">Poin 1 · Sangat Kurang</span>
                    </td>
                    <td class="text-center fw-bold text-dark">&lt; 60</td>
                    <td class="text-center fw-bold text-dark">&lt; 1.5</td>
                    <td>Jauh di bawah target (&lt;60%), kelalaian berulang, komplain tinggi atau terjadi insiden berat. Perlu konseling/SP.</td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div class="alert alert-light border small text-muted mb-0 mt-3">
              <i class="mdi mdi-lightbulb-on-outline text-warning me-1"></i>
              Untuk rincian indikator spesifik dan rumus ukur dari masing-masing 26 jabatan, silakan buka menu 
              <router-link to="/hrms/kpi/indicators" class="fw-semibold text-primary">Informasi Indikator & Poin Penilaian</router-link>.
            </div>
          </div>
          <div class="modal-footer p-2 bg-light border-top">
            <button type="button" class="btn btn-secondary btn-sm rounded-2" @click="showRubricModal = false">Tutup</button>
            <router-link to="/hrms/kpi/indicators" class="btn btn-primary btn-sm rounded-2">
              <i class="mdi mdi-book-open-page-variant-outline me-1"></i> Buka Katalog Lengkap Indikator
            </router-link>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useAuthStore } from '../../stores/auth'
import CrudPage from '../../components/CrudPage.vue'
import Multiselect from '@vueform/multiselect'
import '@vueform/multiselect/themes/default.css'
import api from '../../api'
import { toast } from 'vue3-toastify'

const auth = useAuthStore()
const crudRef = ref(null)
const showRubricModal = ref(false)

const resolveUserDeptGroup = () => {
  if (auth.isSuperAdmin || auth.isStaffHr) return 'all'

  // 1. Cek dari auth.employee.department_group
  const grp = (auth.employee?.department_group || '').toLowerCase().trim()
  if (['fat', 'hrga', 'sales', 'operasional'].includes(grp)) {
    return grp
  }

  // 2. Fallback cek dari kombinasi teks posisi, divisi, departemen
  const combined = [
    auth.employee?.position,
    auth.employee?.division,
    auth.employee?.department
  ].filter(Boolean).join(' ').toLowerCase()

  if (combined.match(/(gudang|operasional|operation|logistik|purchasing|bengkel|toko|inventory)/i)) {
    return 'operasional'
  }
  if (combined.match(/(fat|finance|accounting|tax|akuntansi|pajak|keuangan)/i)) {
    return 'fat'
  }
  if (combined.match(/(hr|ga|human resource|general affair|personalia|sdm)/i)) {
    return 'hrga'
  }
  if (combined.match(/(sales|marketing|penjualan|pemasaran|content|corporate communication)/i)) {
    return 'sales'
  }

  return 'operasional'
}

const selectedDept = ref(resolveUserDeptGroup())
const selectedPhase = ref('all')
const deptCounts = ref({ all: 0, fat: 0, hrga: 0, sales: 0, operasional: 0 })

const allDeptTabs = [
  { key: 'all', label: 'Semua Divisi', icon: 'mdi mdi-view-grid-outline' },
  { key: 'fat', label: 'FAT (Finance, Acc & Tax)', icon: 'mdi mdi-cash-register' },
  { key: 'hrga', label: 'HRGA (HR & GA)', icon: 'mdi mdi-account-group-outline' },
  { key: 'sales', label: 'Sales & Marketing', icon: 'mdi mdi-chart-line' },
  { key: 'operasional', label: 'Operasional & Gudang', icon: 'mdi mdi-truck-delivery-outline' },
]

const deptTabs = computed(() => {
  if (auth.isSuperAdmin || auth.isStaffHr) {
    return allDeptTabs
  }
  const userGroup = resolveUserDeptGroup()
  const matched = allDeptTabs.filter(t => t.key === userGroup)
  return matched.length ? matched : [{ key: 'operasional', label: 'Operasional & Gudang', icon: 'mdi mdi-truck-delivery-outline' }]
})

watch(
  () => [auth.employee?.department_group, auth.employee?.position, auth.employee?.division],
  () => {
    if (!auth.isSuperAdmin && !auth.isStaffHr) {
      selectedDept.value = resolveUserDeptGroup()
    }
  }
)

const phaseOptions = [
  { key: 'all', label: 'Semua Fase' },
  { key: 'daily', label: 'Harian' },
  { key: 'weekly', label: 'Mingguan' },
  { key: 'monthly', label: 'Bulanan' },
  { key: 'quarterly', label: 'Kuartalan' },
]

const onlySubordinates = ref(auth.isSpv)

const baseFilters = computed(() => {
  const bf = {}
  if (onlySubordinates.value) {
    bf.only_subordinates = 1
  }
  if (!onlySubordinates.value && selectedDept.value && selectedDept.value !== 'all') {
    bf.department_group = selectedDept.value
  }
  if (selectedPhase.value && selectedPhase.value !== 'all') {
    bf.period_type = selectedPhase.value
  }
  return bf
})

const handleLoaded = (rows, response) => {
  if (response?.department_counts) {
    deptCounts.value = { ...deptCounts.value, ...response.department_counts }
  }
}

const departmentBadgeClass = (grp) => {
  const g = String(grp || '').toUpperCase()
  if (g === 'FAT') return 'bg-info-subtle text-info border border-info-subtle'
  if (g === 'HRGA') return 'bg-purple-subtle text-purple border border-purple-subtle'
  if (g === 'SALES') return 'bg-success-subtle text-success border border-success-subtle'
  if (g === 'OPERASIONAL') return 'bg-warning-subtle text-warning-emphasis border border-warning-subtle'
  return 'bg-light text-secondary border'
}

const phaseLabel = (type) => ({
  daily: 'Harian',
  weekly: 'Mingguan',
  monthly: 'Bulanan',
  quarterly: 'Kuartalan',
  semesterly: 'Semesteran',
  annual: 'Tahunan'
}[type] || type)

const phaseBadgeClass = (type) => ({
  daily: 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
  weekly: 'bg-info-subtle text-info border border-info-subtle',
  monthly: 'bg-primary-subtle text-primary border border-primary-subtle',
  quarterly: 'bg-dark-subtle text-dark border border-dark-subtle',
  semesterly: 'bg-secondary-subtle text-secondary border',
  annual: 'bg-danger-subtle text-danger border'
}[type] || 'bg-light text-muted border')

const columns = [
  { key: 'transaction_number', label: 'No. Transaksi' },
  { key: 'employee', label: 'Karyawan', sub: 'name' },
  { key: 'period', label: 'Periode', sub: 'name' },
  { key: 'template', label: 'Template', sub: 'name' },
  { key: 'reviewer', label: 'Penilai', sub: 'name' },
  { key: 'status', label: 'Status' },
  { key: 'final_score', label: 'Nilai Akhir' },
]

const filters = ref([
  { key: 'period_id', label: 'Periode', type: 'relation', relationEndpoint: '/kpi/periods' },
  { key: 'status', label: 'Status', options: [
    { value: 'pending', label: 'Menunggu' },
    { value: 'in_review', label: 'Sedang Dinilai' },
    { value: 'approved', label: 'Disetujui' }
  ]}
])

const periodOptions = ref([])
const templateOptions = ref([])
const employeeOptions = ref([])
const employeeRawMap = new Map()
const templateRawList = ref([])

const filteredEmployeeOptions = computed(() => {
  if (auth.isSuperAdmin || auth.isStaffHr) return employeeOptions.value
  const userGroup = resolveUserDeptGroup()
  return employeeOptions.value.filter(opt => {
    const raw = employeeRawMap.get(opt.value)
    if (!raw) return true

    // Subordinates
    const primaryAssignment = raw.assignments?.find(a => a.is_primary) || raw.assignments?.[0]
    if (primaryAssignment?.supervisor_id === auth.employee?.id || raw.supervisor_id === auth.employee?.id) {
      return true
    }
    // Department group
    const empGrp = (raw.department_group || '').toLowerCase()
    if (empGrp && empGrp === userGroup) return true

    const div = (primaryAssignment?.division?.name || raw.division?.name || '').toLowerCase()
    const dep = (primaryAssignment?.department?.name || raw.department?.name || '').toLowerCase()
    if (userGroup === 'operasional' && (div.includes('gudang') || div.includes('operation') || dep.includes('operation') || div.includes('logistik') || div.includes('bengkel') || div.includes('toko'))) {
      return true
    }
    return false
  })
})

const statusBadge = (s) => ({
  'bg-secondary': s === 'pending',
  'bg-warning text-dark': s === 'in_review',
  'bg-success': s === 'approved',
}[s] || 'bg-secondary')

const statusLabel = (s) => ({ pending: 'Menunggu', in_review: 'Dinilai', approved: 'Disetujui' }[s] || s)

const scoreColor = (score) => {
  if (score === null || score === undefined) return 'text-muted'
  const s = parseFloat(score)
  if (s > 10) {
    return s >= 85 ? 'text-success' : s >= 75 ? 'text-primary' : s >= 60 ? 'text-warning' : 'text-danger'
  }
  return s >= 4 ? 'text-success' : s >= 2.5 ? 'text-warning' : 'text-danger'
}

const initialForm = () => ({
  period_id: periodOptions.value.length ? periodOptions.value[0].value : null,
  employee_id: null,
  template_id: null,
  reviewer_id: null,
  status: 'pending',
  reviewer_notes: '',
})

const mapDataToForm = (row) => {
  // Pastikan employee dan reviewer ada dalam opsi agar label multiselect tampil
  if (row.employee && !employeeOptions.value.some(e => e.value === row.employee.id)) {
    employeeOptions.value.push({
      value: row.employee.id,
      label: `${row.employee.name}${row.employee.employee_id ? ` (${row.employee.employee_id})` : ''}`
    })
  }
  if (row.reviewer && !employeeOptions.value.some(e => e.value === row.reviewer.id)) {
    employeeOptions.value.push({
      value: row.reviewer.id,
      label: `${row.reviewer.name}${row.reviewer.employee_id ? ` (${row.reviewer.employee_id})` : ''}`
    })
  }

  return {
    period_id: row.period_id,
    employee_id: row.employee_id,
    template_id: row.template_id,
    reviewer_id: row.reviewer_id,
    status: row.status || 'pending',
    reviewer_notes: row.reviewer_notes || '',
  }
}

// Auto-suggest reviewer dan template ketika karyawan dipilih
const onEmployeeChanged = (form, employeeId) => {
  if (!employeeId) return
  const emp = employeeRawMap.get(employeeId)
  if (!emp) return

  const primaryAssignment = emp.assignments?.find(a => a.is_primary) || emp.assignments?.[0]
  const supervisorId = primaryAssignment?.supervisor_id || emp.supervisor_id
  const positionId = primaryAssignment?.position_id || emp.position_id
  const divisionId = primaryAssignment?.division_id || emp.division_id

  // 1. Auto-fill reviewer jika belum diisi
  if (!form.reviewer_id && supervisorId) {
    form.reviewer_id = supervisorId
  }

  // 2. Auto-match template jika belum diisi
  if (!form.template_id && templateRawList.value.length) {
    let matched = null
    if (positionId) {
      matched = templateRawList.value.find(t => t.scope === 'position' && t.position_id === positionId)
    }
    if (!matched && divisionId) {
      matched = templateRawList.value.find(t => t.scope === 'division' && t.division_id === divisionId)
    }
    if (!matched) {
      matched = templateRawList.value[0]
    }
    if (matched) {
      form.template_id = matched.id
    }
  }
}

const loadDropdownOptions = async () => {
  try {
    const [pRes, tRes, eRes] = await Promise.all([
      api.get('/kpi/periods', { params: { per_page: 100 } }),
      api.get('/kpi/templates', { params: { per_page: 100, is_active: 1 } }),
      api.get('/employees', { params: { per_page: 1000, is_active: 1 } })
    ])

    // Periode
    const periods = pRes.data?.data?.data || pRes.data?.data || []
    periodOptions.value = periods.map(p => ({
      value: p.id,
      label: `${p.name} (${p.status === 'active' ? 'Aktif' : p.status === 'draft' ? 'Draft' : 'Ditutup'})`
    }))

    // Template
    const templates = tRes.data?.data?.data || tRes.data?.data || []
    templateRawList.value = templates
    templateOptions.value = templates.map(t => ({
      value: t.id,
      label: `${t.name} (${t.scope === 'position' ? (t.position?.name || 'Jabatan') : (t.division?.name || 'Divisi')})`
    }))

    // Karyawan
    const employees = eRes.data?.data?.data || eRes.data?.data || []
    employeeRawMap.clear()
    employeeOptions.value = employees.map(e => {
      employeeRawMap.set(e.id, e)
      const primaryAssignment = e.assignments?.find(a => a.is_primary) || e.assignments?.[0]
      const pos = primaryAssignment?.position?.name || e.position?.name || ''
      const div = primaryAssignment?.division?.name || e.division?.name || ''
      const sub = [pos, div].filter(Boolean).join(' · ')
      return {
        value: e.id,
        label: `${e.name}${e.employee_id ? ` (${e.employee_id})` : ''}${sub ? ` — ${sub}` : ''}`
      }
    })
  } catch (err) {
    console.error('Error loading dropdown options for KPI Assessments:', err)
  }
}

const approve = async (a) => {
  if (!confirm('Setujui penilaian ini?')) return
  try {
    await api.post(`/kpi/assessments/${a.id}/approve`)
    toast.success('Penilaian disetujui')
    crudRef.value?.load()
  } catch {
    toast.error('Gagal approve penilaian')
  }
}

onMounted(async () => {
  if (auth.userType === 'employee' && !auth.employee?.department_group) {
    await auth.refreshMe()
    if (!auth.isSuperAdmin && !auth.isStaffHr) {
      selectedDept.value = resolveUserDeptGroup()
    }
  }
  loadDropdownOptions()
})
</script>

<style scoped>
.custom-multiselect {
  --ms-bg: #f8f9fa;
  --ms-border-color: transparent;
  --ms-radius: 0.5rem;
  --ms-font-size: 0.875rem;
  --ms-line-height: 1.5;
  --ms-py: 0.45rem;
  --ms-px: 0.75rem;
  --ms-ring-width: 0px;
  --ms-placeholder-color: #6c757d;
  --ms-dropdown-bg: #ffffff;
  --ms-dropdown-border-color: #e9ecef;
  --ms-dropdown-radius: 0.5rem;
  --ms-option-bg-pointed: #f1f5f9;
  --ms-option-color-pointed: #1e293b;
  --ms-option-bg-selected: #008779;
  --ms-option-color-selected: #ffffff;
}
</style>
