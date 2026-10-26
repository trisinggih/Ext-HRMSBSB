<template>
  <div class="kpi-reports-page">
    <!-- Non-Print Header & Action Bar -->
    <div class="d-flex justify-content-between align-items-center mb-4 no-print flex-wrap gap-2">
      <div>
        <h4 class="font-size-20 fw-bold mb-1 text-dark">
          {{ auth.isSpv ? 'Rekap Hasil Penilaian KPI Anak Buah' : 'Laporan Rekapitulasi KPI' }}
        </h4>
        <p class="text-muted mb-0 small">
          {{ auth.isSpv ? 'Ringkasan hasil evaluasi berkala dan performa seluruh anak buah langsung' : 'Ringkasan & Evaluasi Hasil Penilaian Kinerja Karyawan Per Periode' }}
        </p>
      </div>
      <div class="d-flex gap-2">
        <button @click="exportReportPdf" class="btn btn-primary btn-sm rounded-2 px-3 py-2 fw-medium shadow-sm" :disabled="loading || downloadingPdf || assessments.length === 0">
          <span v-if="downloadingPdf" class="spinner-border spinner-border-sm me-1"></span>
          Export PDF Rekap
        </button>
      </div>
    </div>

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm rounded-3 mb-4 no-print">
      <div class="card-body p-3">
        <div class="row g-3 align-items-end">
          <div class="col-lg-3 col-md-6">
            <label class="form-label fw-bold small text-secondary mb-1">Periode Penilaian KPI</label>
            <Multiselect v-model="filterPeriod" :options="periodOptions" @change="fetchReport" searchable class="custom-multiselect-sm" placeholder="Pilih Periode..." />
          </div>
          <div class="col-lg-2 col-md-6">
            <label class="form-label fw-bold small text-secondary mb-1">Divisi / Departemen</label>
            <select v-model="filterDept" @change="fetchReport" class="form-select form-select-sm border-secondary-subtle rounded-2">
              <option v-if="auth.isSuperAdmin || auth.isStaffHr" value="all">Semua Divisi</option>
              <option v-if="auth.isSuperAdmin || auth.isStaffHr || resolveUserDeptGroup() === 'FAT'" value="FAT">FAT (Finance, Acc, Tax)</option>
              <option v-if="auth.isSuperAdmin || auth.isStaffHr || resolveUserDeptGroup() === 'HRGA'" value="HRGA">HRGA (HR & GA)</option>
              <option v-if="auth.isSuperAdmin || auth.isStaffHr || resolveUserDeptGroup() === 'Sales'" value="Sales">Sales & Marketing</option>
              <option v-if="auth.isSuperAdmin || auth.isStaffHr || resolveUserDeptGroup() === 'Operasional'" value="Operasional">Operasional & Gudang</option>
            </select>
          </div>
          <div class="col-lg-2 col-md-6">
            <label class="form-label fw-bold small text-secondary mb-1">Fase Periode</label>
            <select v-model="filterPhase" @change="fetchReport" class="form-select form-select-sm border-secondary-subtle rounded-2">
              <option value="all">Semua Fase</option>
              <option value="daily">Harian (Daily)</option>
              <option value="weekly">Mingguan (Weekly)</option>
              <option value="monthly">Bulanan (Monthly)</option>
              <option value="quarterly">Kuartalan (Quarterly)</option>
            </select>
          </div>
          <div class="col-lg-2 col-md-6">
            <label class="form-label fw-bold small text-secondary mb-1">Status Penilaian</label>
            <select v-model="filterStatus" @change="fetchReport" class="form-select form-select-sm border-secondary-subtle rounded-2">
              <option value="">Semua Status</option>
              <option value="approved">Disetujui (Approved)</option>
              <option value="in_review">Sedang Dinilai (In Review)</option>
              <option value="pending">Menunggu (Pending)</option>
            </select>
          </div>
          <div class="col-lg-2 col-md-6">
            <label class="form-label fw-bold small text-secondary mb-1">Cari Karyawan / NIP</label>
            <div class="input-group input-group-sm">
              <input type="text" v-model="searchQuery" class="form-control" placeholder="Ketik nama / NIP..." />
            </div>
          </div>
          <div class="col-lg-1 col-md-6">
            <button class="btn btn-outline-secondary btn-sm w-100 rounded-2 py-2 fw-medium" @click="fetchReport" :disabled="loading">
              <span v-if="loading" class="spinner-border spinner-border-sm me-1"></span>
              Filter
            </button>
          </div>

          <!-- Quick Scope Focus (Khusus Anak Buah Saya vs Semua Divisi) -->
          <div class="col-12 d-flex align-items-center justify-content-between flex-wrap gap-2 pt-2 border-top">
            <div class="d-flex align-items-center gap-2 flex-wrap">
              <span class="small fw-semibold text-secondary font-size-12">Fokus Rekapitulasi:</span>
              <button
                type="button"
                class="btn btn-sm rounded-pill font-size-12 px-3 py-1 transition-all"
                :class="filterSubordinates ? 'btn-primary text-white shadow-sm fw-bold' : 'btn-outline-secondary bg-white'"
                @click="filterSubordinates = true; fetchReport()"
              >
                Anak Buah Saya (Bawahan Langsung)
              </button>
              <button
                type="button"
                class="btn btn-sm rounded-pill font-size-12 px-3 py-1 transition-all"
                :class="!filterSubordinates ? 'btn-primary text-white shadow-sm fw-bold' : 'btn-outline-secondary bg-white'"
                @click="filterSubordinates = false; fetchReport()"
              >
                Seluruh Karyawan Divisi
              </button>
            </div>
            <div class="small text-muted font-size-11" v-if="filterSubordinates">
              Menampilkan rekapitulasi penilaian dari seluruh anak buah langsung di bawah Anda.
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Print Header Only -->
    <div class="print-only mb-4 border-bottom pb-3">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h3 class="fw-bold mb-1 text-primary">HRMS - REKAPITULASI PENILAIAN KPI KARYAWAN</h3>
          <p class="mb-0 text-muted small">Periode: <strong>{{ selectedPeriodName }}</strong></p>
        </div>
        <div class="text-end">
          <div class="small text-muted">Tanggal Cetak: {{ currentDateStr }}</div>
          <div class="small text-muted">Total Karyawan: {{ filteredAssessments.length }} Orang</div>
        </div>
      </div>
    </div>

    <!-- Summary Stat Cards -->
    <div v-if="summary" class="row g-3 mb-4">
      <div class="col-lg-2 col-md-4 col-6">
        <div class="card border-0 shadow-sm rounded-3 text-center p-3 h-100 border-start border-4 border-primary">
          <div class="text-muted small text-uppercase tracking-wider">Total Evaluasi</div>
          <div class="fw-bold fs-3 text-dark mt-1">{{ summary.total }}</div>
        </div>
      </div>
      <div class="col-lg-2 col-md-4 col-6">
        <div class="card border-0 shadow-sm rounded-3 text-center p-3 h-100 border-start border-4 border-success">
          <div class="text-muted small text-uppercase tracking-wider">Disetujui</div>
          <div class="fw-bold fs-3 text-success mt-1">{{ summary.completed }}</div>
        </div>
      </div>
      <div class="col-lg-2 col-md-4 col-6">
        <div class="card border-0 shadow-sm rounded-3 text-center p-3 h-100 border-start border-4 border-warning">
          <div class="text-muted small text-uppercase tracking-wider">Sedang Dinilai</div>
          <div class="fw-bold fs-3 text-warning mt-1">{{ summary.in_review }}</div>
        </div>
      </div>
      <div class="col-lg-2 col-md-4 col-6">
        <div class="card border-0 shadow-sm rounded-3 text-center p-3 h-100 border-start border-4 border-secondary">
          <div class="text-muted small text-uppercase tracking-wider">Menunggu</div>
          <div class="fw-bold fs-3 text-secondary mt-1">{{ summary.pending }}</div>
        </div>
      </div>
      <div class="col-lg-2 col-md-4 col-6">
        <div class="card border-0 shadow-sm rounded-3 text-center p-3 h-100 border-start border-4 border-info">
          <div class="text-muted small text-uppercase tracking-wider">Rata-rata Skor</div>
          <div class="fw-bold fs-3 mt-1" :class="scoreColor(summary.average_score)">
            {{ summary.average_score !== null ? parseFloat(summary.average_score).toFixed(2) : '—' }}
          </div>
        </div>
      </div>
      <div class="col-lg-2 col-md-4 col-6">
        <div class="card border-0 shadow-sm rounded-3 text-center p-3 h-100 border-start border-4 border-dark">
          <div class="text-muted small text-uppercase tracking-wider">Skor Tertinggi</div>
          <div class="fw-bold fs-3 text-success mt-1">
            {{ summary.highest_score !== null ? parseFloat(summary.highest_score).toFixed(2) : '—' }}
          </div>
        </div>
      </div>
    </div>

    <!-- Main Table Card -->
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
      <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center no-print">
        <h6 class="mb-0 fw-bold text-dark font-size-14">Daftar Hasil Penilaian KPI</h6>
        <span class="badge bg-light text-secondary border px-3 py-1 font-size-12">{{ filteredAssessments.length }} Karyawan</span>
      </div>

      <div class="card-body p-0">
        <div v-if="loading" class="text-center py-5 text-muted no-print">
          <div class="spinner-border text-primary mb-2" style="width: 2rem; height: 2rem;"></div>
          <div class="small">Memuat laporan rekapitulasi KPI...</div>
        </div>

        <div v-else-if="filteredAssessments.length === 0" class="text-center py-5 text-muted no-print">
          <h6 class="mt-2 mb-0 text-dark fw-bold">Tidak Ada Data Penilaian</h6>
          <p class="small text-muted mb-0">Silakan pilih periode atau ubah kata kunci pencarian.</p>
        </div>

        <div v-else class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="bg-light text-secondary font-size-12 text-uppercase">
              <tr>
                <th style="width: 4%;" class="text-center">No</th>
                <th style="width: 22%;">Karyawan</th>
                <th style="width: 14%;">Jabatan</th>
                <th style="width: 12%;">Divisi</th>
                <th style="width: 16%;">Periode & Fase</th>
                <th style="width: 10%;" class="text-center">Status</th>
                <th style="width: 10%;" class="text-center">Nilai Akhir</th>
                <th style="width: 10%;" class="text-center">Predikat</th>
                <th style="width: 12%;" class="text-center no-print">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(a, i) in filteredAssessments" :key="a.id">
                <td class="text-center text-muted small">{{ i + 1 }}</td>
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <div class="avatar-circle-sm bg-primary bg-opacity-10 text-primary fw-bold rounded-circle d-flex align-items-center justify-content-center font-size-12 shadow-sm flex-shrink-0" style="width: 32px; height: 32px; min-width: 32px; min-height: 32px; flex: 0 0 32px;">
                      {{ (a.employee?.name || 'K')[0].toUpperCase() }}
                    </div>
                    <div>
                      <div class="d-flex align-items-center gap-1.5 flex-wrap">
                        <div class="fw-bold text-dark font-size-13">{{ a.employee?.name }}</div>
                        <span class="badge bg-light text-primary border border-primary-subtle font-monospace font-size-10 px-1.5 py-0.5">
                          {{ a.transaction_number || ('KPI-' + a.id) }}
                        </span>
                      </div>
                      <div class="d-flex align-items-center gap-1 flex-wrap mt-0-5">
                        <span v-if="a.employee?.department_group" class="badge font-size-10 px-2 py-0-5" :class="departmentBadgeClass(a.employee?.department_group)">
                          {{ a.employee?.department_group }}
                        </span>
                        <small class="text-muted font-size-11">NIP: {{ a.employee?.employee_id || '-' }}</small>
                      </div>
                    </div>
                  </div>
                </td>
                <td class="font-size-13 text-secondary">{{ a.employee?.position?.name || '—' }}</td>
                <td class="font-size-13 text-secondary">{{ a.employee?.division?.name || '—' }}</td>
                <td>
                  <div class="font-size-12 fw-medium text-dark">{{ a.period?.name || '—' }}</div>
                  <div class="mt-0-5" v-if="a.period?.type">
                    <span class="badge font-size-10 px-2 py-0-5" :class="phaseBadgeClass(a.period.type)">
                      {{ phaseLabel(a.period.type) }}
                    </span>
                  </div>
                </td>
                <td class="text-center">
                  <span class="badge font-size-11 px-2 py-1" :class="statusBadgeClass(a.status)">
                    {{ statusLabel(a.status) }}
                  </span>
                </td>
                <td class="text-center">
                  <span v-if="a.final_score !== null" class="fw-bold font-size-14" :class="scoreColor(a.final_score)">
                    {{ parseFloat(a.final_score).toFixed(2) }}
                  </span>
                  <span v-else class="text-muted small">—</span>
                </td>
                <td class="text-center">
                  <span v-if="a.final_score !== null" class="badge font-size-11 px-2 py-1" :class="predikatBadgeClass(a.final_score)">
                    {{ predikat(a.final_score) }}
                  </span>
                  <span v-else class="text-muted small">—</span>
                </td>
                <td class="text-center no-print">
                  <div class="d-flex justify-content-center gap-1">
                    <router-link :to="`/hrms/kpi/assessments/${a.id}`"
                                 class="btn btn-sm btn-outline-primary px-2 py-1 rounded-2 font-size-12"
                                 title="Buka Form Evaluasi">
                      Nilai
                    </router-link>
                    <button @click="downloadSinglePdf(a)"
                            class="btn btn-sm btn-outline-secondary px-2 py-1 rounded-2 font-size-12"
                            title="Export PDF Karyawan Ini">
                      PDF
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- PDF / Print Signatures Block -->
    <div class="print-only mt-5 pt-4 border-top">
      <div class="row text-center font-size-13">
        <div class="col-4">
          <p class="mb-5 text-muted">Dibuat Oleh (HR Staff),</p>
          <div class="fw-bold text-dark border-bottom border-dark d-inline-block pb-1" style="min-width: 180px;">
            ____________________
          </div>
          <div class="small text-muted mt-1">HR Admin</div>
        </div>
        <div class="col-4">
          <p class="mb-5 text-muted">Diverifikasi Oleh (HR Manager),</p>
          <div class="fw-bold text-dark border-bottom border-dark d-inline-block pb-1" style="min-width: 180px;">
            ____________________
          </div>
          <div class="small text-muted mt-1">HR Manager</div>
        </div>
        <div class="col-4">
          <p class="mb-5 text-muted">Disetujui Oleh (Direksi),</p>
          <div class="fw-bold text-dark border-bottom border-dark d-inline-block pb-1" style="min-width: 180px;">
            ____________________
          </div>
          <div class="small text-muted mt-1">Director</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import Multiselect from '@vueform/multiselect'
import '@vueform/multiselect/themes/default.css'
import api from '../../api'
import { toast } from 'vue3-toastify'
import 'vue3-toastify/dist/index.css'

const auth = useAuthStore()

const resolveUserDeptGroup = () => {
  if (auth.isSuperAdmin || auth.isStaffHr) return 'all'
  const grp = (auth.employee?.department_group || '').toLowerCase().trim()
  if (grp === 'fat') return 'FAT'
  if (grp === 'hrga') return 'HRGA'
  if (grp === 'sales') return 'Sales'
  if (grp === 'operasional' || grp === 'operational') return 'Operasional'

  const combined = [
    auth.employee?.position,
    auth.employee?.division,
    auth.employee?.department
  ].filter(Boolean).join(' ').toLowerCase()

  if (combined.match(/(gudang|operasional|operation|logistik|purchasing|bengkel|toko|inventory)/i)) {
    return 'Operasional'
  }
  if (combined.match(/(fat|finance|accounting|tax|akuntansi|pajak|keuangan)/i)) {
    return 'FAT'
  }
  if (combined.match(/(hr|ga|human resource|general affair|personalia|sdm)/i)) {
    return 'HRGA'
  }
  if (combined.match(/(sales|marketing|penjualan|pemasaran|content|corporate communication)/i)) {
    return 'Sales'
  }

  return 'Operasional'
}

const loading = ref(false)
const assessments = ref([])
const summary = ref(null)
const filterPeriod = ref(null)
const filterDept = ref(resolveUserDeptGroup())
const filterPhase = ref('all')
const filterStatus = ref('')
const filterSubordinates = ref(auth.isSpv)
const searchQuery = ref('')
const periodOptions = ref([])

const currentDateStr = new Date().toLocaleDateString('id-ID', {
  year: 'numeric', month: 'long', day: 'numeric'
})

const selectedPeriodName = computed(() => {
  const found = periodOptions.value.find(p => p.value === filterPeriod.value)
  return found ? found.label : 'Semua Periode'
})

const filteredAssessments = computed(() => {
  if (!searchQuery.value) return assessments.value
  const q = searchQuery.value.toLowerCase()
  return assessments.value.filter(a => {
    const tx = (a.transaction_number || ('KPI-' + a.id)).toLowerCase()
    const name = (a.employee?.name || '').toLowerCase()
    const nip = (a.employee?.employee_id || '').toLowerCase()
    return tx.includes(q) || name.includes(q) || nip.includes(q)
  })
})

const phaseLabel = (type) => ({
  daily: 'Harian (Daily)',
  weekly: 'Mingguan (Weekly)',
  monthly: 'Bulanan (Monthly)',
  quarterly: 'Kuartalan (Quarterly)',
  semesterly: 'Semesteran',
  annual: 'Tahunan'
}[type] || type || 'Bulanan')

const phaseBadgeClass = (type) => ({
  daily: 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
  weekly: 'bg-info-subtle text-info border border-info-subtle',
  monthly: 'bg-primary-subtle text-primary border border-primary-subtle',
  quarterly: 'bg-dark-subtle text-dark border border-dark-subtle',
  semesterly: 'bg-secondary-subtle text-secondary border',
  annual: 'bg-danger-subtle text-danger border'
}[type] || 'bg-light text-muted border')

const statusBadgeClass = (s) => ({
  'bg-secondary': s === 'pending',
  'bg-warning text-dark': s === 'in_review',
  'bg-success text-white': s === 'approved'
})

const statusLabel = (s) => ({
  pending: 'Menunggu',
  in_review: 'Sedang Dinilai',
  approved: 'Disetujui'
})[s] || s

const departmentBadgeClass = (grp) => {
  const g = String(grp || '').toUpperCase()
  if (g === 'FAT') return 'bg-info-subtle text-info border border-info-subtle'
  if (g === 'HRGA') return 'bg-purple-subtle text-purple border'
  if (g === 'SALES') return 'bg-success-subtle text-success border border-success-subtle'
  if (g === 'OPERASIONAL') return 'bg-warning-subtle text-warning-emphasis border border-warning-subtle'
  return 'bg-light text-secondary border'
}

const scoreColor = (score) => {
  if (score === null) return 'text-muted'
  const s = parseFloat(score)
  return s >= 4.0 ? 'text-success' : s >= 3.0 ? 'text-primary' : s >= 2.0 ? 'text-warning' : 'text-danger'
}

const predikat = (score) => {
  if (score === null) return '—'
  const s = parseFloat(score)
  if (s >= 4.5) return 'Sangat Baik'
  if (s >= 3.5) return 'Baik'
  if (s >= 2.5) return 'Cukup'
  if (s >= 1.5) return 'Kurang'
  return 'Sangat Kurang'
}

const predikatBadgeClass = (score) => {
  const s = parseFloat(score)
  if (s >= 4.5) return 'bg-success text-white'
  if (s >= 3.5) return 'bg-primary text-white'
  if (s >= 2.5) return 'bg-warning text-dark'
  if (s >= 1.5) return 'bg-danger text-white'
  return 'bg-dark text-white'
}

const fetchReport = async () => {
  loading.value = true
  try {
    const params = {
      period_id: filterPeriod.value,
      status: filterStatus.value,
      department_group: (!filterSubordinates.value && filterDept.value !== 'all') ? filterDept.value : null,
      period_type: filterPhase.value !== 'all' ? filterPhase.value : null,
      only_subordinates: filterSubordinates.value ? 1 : 0,
      scope: filterSubordinates.value ? 'subordinates' : 'all',
    }
    const { data } = await api.get('/kpi/assessments/report', { params })
    summary.value = data.data.summary
    assessments.value = data.data.assessments || []
  } catch (e) {
    toast.error('Gagal memuat laporan KPI')
  } finally {
    loading.value = false
  }
}

const downloadingPdf = ref(false)

const exportReportPdf = async () => {
  downloadingPdf.value = true
  try {
    const params = {
      period_id: filterPeriod.value,
      status: filterStatus.value,
      department_group: (!filterSubordinates.value && filterDept.value !== 'all') ? filterDept.value : null,
      period_type: filterPhase.value !== 'all' ? filterPhase.value : null,
      only_subordinates: filterSubordinates.value ? 1 : 0,
      scope: filterSubordinates.value ? 'subordinates' : 'all',
    }
    const response = await api.get('/kpi/assessments/report/pdf', {
      params,
      responseType: 'blob'
    })
    const url = window.URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }))
    const link = document.createElement('a')
    link.href = url
    const periodStr = (selectedPeriodName.value || 'All').replace(/ /g, '_')
    link.setAttribute('download', `Laporan_Rekapitulasi_KPI_${periodStr}.pdf`)
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
    toast.success('Laporan PDF KPI berhasil di-download')
  } catch (e) {
    toast.error('Gagal mengunduh laporan PDF')
  } finally {
    downloadingPdf.value = false
  }
}

const downloadSinglePdf = async (item) => {
  try {
    const response = await api.get(`/kpi/assessments/${item.id}/pdf`, {
      responseType: 'blob'
    })
    const url = window.URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }))
    const link = document.createElement('a')
    link.href = url
    const empName = (item.employee?.name || 'Assessment').replace(/ /g, '_')
    link.setAttribute('download', `KPI_Assessment_${empName}.pdf`)
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
    toast.success('File PDF berhasil di-download')
  } catch (e) {
    toast.error('Gagal mengunduh PDF karyawan')
  }
}

const route = useRoute()

const syncPeriodFromRoute = () => {
  const queryPeriodId = route.query.period_id ? Number(route.query.period_id) : null
  if (queryPeriodId && periodOptions.value.some(p => p.value === queryPeriodId)) {
    filterPeriod.value = queryPeriodId
    fetchReport()
    return true
  }
  return false
}

watch(() => route.query.period_id, () => {
  if (periodOptions.value.length > 0) {
    syncPeriodFromRoute()
  }
})

onMounted(async () => {
  if (auth.userType === 'employee' && !auth.employee?.department_group) {
    await auth.refreshMe()
    if (!auth.isSuperAdmin && !auth.isStaffHr) {
      filterDept.value = resolveUserDeptGroup()
    }
  }
  try {
    const { data } = await api.get('/kpi/periods', { params: { per_page: 100 } })
    const list = data.data?.data || data.data || []
    periodOptions.value = list.map(p => ({ value: p.id, label: p.name }))
    if (!syncPeriodFromRoute() && periodOptions.value.length > 0) {
      filterPeriod.value = periodOptions.value[0].value
      fetchReport()
    }
  } catch (e) {
    console.error(e)
  }
})
</script>

<style scoped>
.custom-multiselect-sm {
  --ms-bg: #f8f9fa;
  --ms-border-color: #dee2e6;
  --ms-radius: 0.375rem;
  --ms-py: 0.25rem;
  --ms-px: 0.75rem;
  --ms-ring-width: 0px;
  --ms-font-size: 0.875rem;
  --ms-placeholder-color: #6c757d;
}

.print-only {
  display: none !important;
}

@media print {
  .no-print, nav, header, sidebar, .navbar, .footer, .btn, .card-header {
    display: none !important;
  }

  .print-only {
    display: block !important;
  }

  body, #app, .kpi-reports-page {
    background: #fff !important;
    color: #000 !important;
    padding: 0 !important;
    margin: 0 !important;
  }

  .card {
    border: 1px solid #ddd !important;
    box-shadow: none !important;
  }

  .table {
    border: 1px solid #ddd !important;
  }

  .table th, .table td {
    padding: 6px 10px !important;
    border-color: #ccc !important;
  }
}
</style>
