<template>
  <div class="kpi-period-detail-page">
    <!-- Top Header Bar -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
      <div class="d-flex align-items-center gap-3">
        <router-link 
          to="/hrms/kpi/periods"
          class="btn btn-outline-secondary btn-sm rounded-2 px-3 py-1.5 fw-medium d-flex align-items-center gap-1 shadow-xs"
          title="Kembali ke Daftar Periode Penilaian KPI"
        >
          <i class="mdi mdi-arrow-left"></i> Kembali ke Periode
        </router-link>
        <div>
          <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
            <h5 class="mb-0 fw-bold text-dark font-size-18">
              {{ period?.name || 'Detail Penilaian KPI Periode' }}
            </h5>
            <span class="badge bg-primary text-white font-size-11 px-2.5 py-1 rounded-pill">
              <i class="mdi mdi-account-group-outline me-1"></i> Penilaian KPI Anak Buah
            </span>
            <span v-if="period" class="badge font-size-11" :class="phaseBadge(period.type)">
              Fase: {{ phaseLabel(period.type) }}
            </span>
            <span v-if="period" class="badge font-size-11" :class="deptBadge(period.department_group)">
              Divisi: {{ deptLabel(period.department_group) }}
            </span>
            <span v-if="period" class="badge font-size-11" :class="{ 'bg-secondary': period.status === 'draft', 'bg-success': period.status === 'active', 'bg-dark': period.status === 'closed' }">
              {{ { draft: 'Draft', active: 'Aktif', closed: 'Ditutup' }[period.status] || period.status }}
            </span>
          </div>
          <small class="text-muted d-block font-size-12" v-if="period">
            Rentang Evaluasi: <strong>{{ formatDate(period.start_date) }}</strong> s/d <strong>{{ formatDate(period.end_date) }}</strong>
          </small>
        </div>
      </div>

      <div class="d-flex align-items-center gap-2">
        <router-link 
          v-if="period"
          :to="`/hrms/kpi/reports?period_id=${period.id}`" 
          class="btn btn-success btn-sm rounded-2 px-3 py-1.5 fw-medium shadow-sm d-flex align-items-center gap-1.5"
          title="Buka Laporan Rekapitulasi Hasil Penilaian Periode Ini"
        >
          <i class="mdi mdi-chart-box-outline font-size-14"></i>
          <span>Rekap Hasil Nilai</span>
        </router-link>
      </div>
    </div>

    <!-- Stat Cards / Overview Grid -->
    <div class="row g-3 mb-3" v-if="!loading">
      <div class="col-lg-3 col-md-6 col-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 border-start border-4 border-primary">
          <div class="card-body p-3">
            <div class="text-secondary small fw-semibold text-uppercase font-size-11">Total Anak Buah</div>
            <div class="d-flex align-items-center justify-content-between mt-1">
              <span class="fs-4 fw-bold text-dark">{{ periodAssessments.length }}</span>
              <span class="avatar-sm bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center">
                <i class="mdi mdi-account-multiple font-size-20"></i>
              </span>
            </div>
            <div class="text-muted font-size-11 mt-1">Transaksi lembar KPI di periode ini</div>
          </div>
        </div>
      </div>

      <div class="col-lg-3 col-md-6 col-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 border-start border-4 border-secondary">
          <div class="card-body p-3">
            <div class="text-secondary small fw-semibold text-uppercase font-size-11">Belum Dinilai (Pending)</div>
            <div class="d-flex align-items-center justify-content-between mt-1">
              <span class="fs-4 fw-bold text-secondary">{{ countByStatus('pending') }}</span>
              <span class="avatar-sm bg-secondary bg-opacity-10 text-secondary rounded-circle d-flex align-items-center justify-content-center">
                <i class="mdi mdi-clock-outline font-size-20"></i>
              </span>
            </div>
            <div class="text-muted font-size-11 mt-1">Menunggu input nilai atasan</div>
          </div>
        </div>
      </div>

      <div class="col-lg-3 col-md-6 col-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 border-start border-4 border-warning">
          <div class="card-body p-3">
            <div class="text-secondary small fw-semibold text-uppercase font-size-11">Sedang Dinilai</div>
            <div class="d-flex align-items-center justify-content-between mt-1">
              <span class="fs-4 fw-bold text-warning">{{ countByStatus('in_review') }}</span>
              <span class="avatar-sm bg-warning bg-opacity-10 text-warning rounded-circle d-flex align-items-center justify-content-center">
                <i class="mdi mdi-clipboard-text-clock font-size-20"></i>
              </span>
            </div>
            <div class="text-muted font-size-11 mt-1">Sudah dinilai / dalam review</div>
          </div>
        </div>
      </div>

      <div class="col-lg-3 col-md-6 col-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 border-start border-4 border-success">
          <div class="card-body p-3">
            <div class="text-secondary small fw-semibold text-uppercase font-size-11">Disetujui (Approved)</div>
            <div class="d-flex align-items-center justify-content-between mt-1">
              <span class="fs-4 fw-bold text-success">{{ countByStatus('approved') }}</span>
              <span class="avatar-sm bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center">
                <i class="mdi mdi-check-decagram font-size-20"></i>
              </span>
            </div>
            <div class="text-muted font-size-11 mt-1">Nilai final disetujui</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Filter & Search Card -->
    <div class="card border-0 shadow-sm rounded-3 mb-3">
      <div class="card-body p-3">
        <div class="row g-2 align-items-center justify-content-between">
          <!-- Search input -->
          <div class="col-12 col-md-6">
            <div class="input-group input-group-sm">
              <span class="input-group-text bg-light border-end-0 text-muted">
                <i class="mdi mdi-magnify font-size-14"></i>
              </span>
              <input
                v-model="txSearchQuery"
                type="text"
                class="form-control bg-light border-start-0 font-size-13"
                placeholder="Cari nomor transaksi (KPI-...), nama karyawan, NIP, atau jabatan..."
              />
              <button 
                v-if="txSearchQuery" 
                @click="txSearchQuery = ''" 
                class="btn btn-outline-secondary btn-sm" 
                type="button"
              >
                <i class="mdi mdi-close"></i>
              </button>
            </div>
          </div>

          <!-- Status Filter Tabs -->
          <div class="col-12 col-md-6 d-flex align-items-center justify-content-md-end gap-1 flex-wrap">
            <span class="font-size-11 text-secondary fw-semibold me-1">Status:</span>
            <div class="btn-group btn-group-sm" role="group">
              <button
                type="button"
                class="btn btn-sm px-2.5 py-1 font-size-11 transition-all"
                :class="txStatusFilter === 'all' ? 'btn-primary text-white fw-bold shadow-xs' : 'btn-outline-secondary bg-white'"
                @click="txStatusFilter = 'all'"
              >
                Semua ({{ periodAssessments.length }})
              </button>
              <button
                type="button"
                class="btn btn-sm px-2.5 py-1 font-size-11 transition-all"
                :class="txStatusFilter === 'pending' ? 'btn-primary text-white fw-bold shadow-xs' : 'btn-outline-secondary bg-white'"
                @click="txStatusFilter = 'pending'"
              >
                Pending ({{ countByStatus('pending') }})
              </button>
              <button
                type="button"
                class="btn btn-sm px-2.5 py-1 font-size-11 transition-all"
                :class="txStatusFilter === 'in_review' ? 'btn-primary text-white fw-bold shadow-xs' : 'btn-outline-secondary bg-white'"
                @click="txStatusFilter = 'in_review'"
              >
                Dinilai ({{ countByStatus('in_review') }})
              </button>
              <button
                type="button"
                class="btn btn-sm px-2.5 py-1 font-size-11 transition-all"
                :class="txStatusFilter === 'approved' ? 'btn-primary text-white fw-bold shadow-xs' : 'btn-outline-secondary bg-white'"
                @click="txStatusFilter = 'approved'"
              >
                Disetujui ({{ countByStatus('approved') }})
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content Table Card -->
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
      <!-- Loading State -->
      <div v-if="loading" class="text-center py-5">
        <div class="spinner-border text-primary mb-2" style="width: 2.2rem; height: 2.2rem;" role="status"></div>
        <p class="text-muted small mb-0">Memuat transaksi lembar penilaian KPI anak buah...</p>
      </div>

      <!-- Empty State (No assessments at all for this period) -->
      <div v-else-if="periodAssessments.length === 0" class="text-center py-5 p-4">
        <i class="mdi mdi-clipboard-text-clock-outline font-size-48 text-muted mb-2 d-block"></i>
        <h6 class="fw-bold text-dark mb-1 font-size-16">Belum Ada Transaksi KPI di Periode Ini</h6>
        <p class="text-muted small mb-3">
          Tidak ditemukan lembar penilaian untuk anak buah Anda pada periode ini.
        </p>
        <router-link to="/hrms/kpi/periods" class="btn btn-primary btn-sm rounded-2 px-3 py-1.5 fw-medium">
          <i class="mdi mdi-arrow-left me-1"></i> Kembali ke Periode KPI
        </router-link>
      </div>

      <!-- Empty Filter State -->
      <div v-else-if="filteredTransactions.length === 0" class="text-center py-5 text-muted p-4">
        <i class="mdi mdi-filter-remove-outline font-size-36 text-muted mb-2 d-block"></i>
        <h6 class="fw-bold text-dark mb-1">Tidak Ditemukan Data yang Cocok</h6>
        <p class="small mb-2">Tidak ada transaksi yang cocok dengan kata kunci atau filter status yang dipilih.</p>
        <button @click="txSearchQuery = ''; txStatusFilter = 'all'" class="btn btn-outline-secondary btn-sm rounded-2 px-3 py-1">
          Reset Filter
        </button>
      </div>

      <!-- Transactions Table -->
      <div v-else class="table-responsive">
        <table class="table table-hover align-middle mb-0 font-size-13">
          <thead class="table-light font-size-11 text-secondary text-uppercase border-bottom">
            <tr>
              <th style="width: 45px;" class="text-center">No</th>
              <th style="min-width: 170px;">No. Transaksi</th>
              <th style="min-width: 240px;">Karyawan (Anak Buah)</th>
              <th style="min-width: 150px;">Jabatan</th>
              <th style="min-width: 140px;">Divisi</th>
              <th style="width: 110px;" class="text-center">Status</th>
              <th style="width: 120px;" class="text-center">Nilai Akhir</th>
              <th style="min-width: 150px;" class="text-center">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(tx, idx) in filteredTransactions" :key="tx.id">
              <td class="text-center text-muted font-size-12 fw-medium">{{ idx + 1 }}</td>
              <td>
                <div class="d-flex align-items-center gap-1.5">
                  <span class="badge bg-light text-primary border border-primary-subtle font-monospace font-size-12 px-2 py-1 fw-bold">
                    {{ tx.transaction_number || ('KPI-' + tx.id) }}
                  </span>
                </div>
                <small class="text-muted font-size-10 d-block mt-0.5" v-if="tx.created_at">
                  Dibuat: {{ formatDate(tx.created_at) }}
                </small>
              </td>
              <td>
                <div class="d-flex align-items-center gap-2">
                  <div class="avatar-circle-sm bg-primary bg-opacity-10 text-primary fw-bold rounded-circle d-flex align-items-center justify-content-center font-size-12 shadow-xs flex-shrink-0" style="width: 32px; height: 32px; min-width: 32px; min-height: 32px; flex: 0 0 32px;">
                    {{ (tx.employee?.name || 'K')[0].toUpperCase() }}
                  </div>
                  <div>
                    <div class="fw-semibold text-dark font-size-13">{{ tx.employee?.name || '—' }}</div>
                    <div class="text-muted font-size-11" v-if="tx.employee?.employee_id">
                      NIP: {{ tx.employee?.employee_id }}
                    </div>
                  </div>
                </div>
              </td>
              <td class="text-secondary font-size-12">
                {{ tx.employee?.position?.name || tx.employee?.primary_assignment?.position?.name || '—' }}
              </td>
              <td class="text-secondary font-size-12">
                {{ tx.employee?.division?.name || tx.employee?.primary_assignment?.division?.name || '—' }}
              </td>
              <td class="text-center">
                <span class="badge font-size-11 px-2.5 py-1 rounded-pill" :class="assessmentStatusBadge(tx.status)">
                  {{ assessmentStatusLabel(tx.status) }}
                </span>
              </td>
              <td class="text-center">
                <span v-if="tx.final_score !== null && tx.final_score !== undefined" class="badge bg-light text-dark border font-size-12 px-2.5 py-1 fw-bold">
                  {{ Number(tx.final_score).toFixed(2) }}
                </span>
                <span v-else class="text-muted font-size-11 fst-italic">Belum Dinilai</span>
              </td>
              <td class="text-center">
                <div class="d-flex align-items-center justify-content-center gap-1">
                  <router-link
                    :to="`/hrms/kpi/assessments/${tx.id}`"
                    class="btn-action-detail font-size-12 px-2.5 py-1 text-decoration-none shadow-xs"
                    title="Buka form lembar penilaian karyawan ini"
                  >
                    <i class="mdi mdi-clipboard-edit-outline"></i>
                    <span>{{ tx.status === 'pending' ? 'Beri Nilai' : 'Detail Penilaian' }}</span>
                  </router-link>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Card Footer -->
      <div class="card-footer bg-light border-top p-3 d-flex justify-content-between align-items-center flex-wrap gap-2" v-if="!loading && periodAssessments.length > 0">
        <div class="small text-muted font-size-12">
          Menampilkan <strong>{{ filteredTransactions.length }}</strong> dari total <strong>{{ periodAssessments.length }}</strong> transaksi penilaian anak buah.
        </div>
        <div class="d-flex align-items-center gap-2">
          <router-link 
            v-if="period"
            :to="`/hrms/kpi/reports?period_id=${period.id}`" 
            class="btn btn-outline-success btn-sm rounded-2 px-3 py-1 font-size-12 fw-medium shadow-xs"
          >
            <i class="mdi mdi-file-chart-outline me-1"></i> Rekapitulasi Hasil KPI
          </router-link>
          <router-link 
            to="/hrms/kpi/periods" 
            class="btn btn-secondary btn-sm rounded-2 px-3 py-1 font-size-12"
          >
            Kembali
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import api from '../../api'
import { toast } from 'vue3-toastify'
import 'vue3-toastify/dist/index.css'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const periodId = route.params.id

const period = ref(null)
const periodAssessments = ref([])
const loading = ref(true)
const txSearchQuery = ref('')
const txStatusFilter = ref('all')

const fetchPeriodAndAssessments = async () => {
  loading.value = true
  try {
    // 1. Ambil detail Periode
    const periodRes = await api.get(`/kpi/periods/${periodId}`)
    period.value = periodRes.data?.data || periodRes.data

    // 2. Ambil seluruh transaksi penilaian anak buah di periode ini
    const assessRes = await api.get('/kpi/assessments', {
      params: {
        period_id: periodId,
        only_subordinates: auth.isSpv ? 1 : undefined,
        per_page: 200
      }
    })
    periodAssessments.value = assessRes.data?.data?.data || assessRes.data?.data || []
  } catch (err) {
    console.error('Failed to load period assessments:', err)
    toast.error('Gagal memuat detail penilaian KPI periode')
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchPeriodAndAssessments()
})

const countByStatus = (status) => {
  return periodAssessments.value.filter(a => a.status === status).length
}

const filteredTransactions = computed(() => {
  let list = periodAssessments.value

  if (txStatusFilter.value !== 'all') {
    list = list.filter(a => a.status === txStatusFilter.value)
  }

  const q = txSearchQuery.value.trim().toLowerCase()
  if (q) {
    list = list.filter(a => {
      const txNo = String(a.transaction_number || ('KPI-' + a.id)).toLowerCase()
      const empName = String(a.employee?.name || '').toLowerCase()
      const nip = String(a.employee?.employee_id || '').toLowerCase()
      const pos = String(a.employee?.position?.name || a.employee?.primary_assignment?.position?.name || '').toLowerCase()
      const div = String(a.employee?.division?.name || a.employee?.primary_assignment?.division?.name || '').toLowerCase()
      return txNo.includes(q) || empName.includes(q) || nip.includes(q) || pos.includes(q) || div.includes(q)
    })
  }

  return list
})

const assessmentStatusLabel = (s) => ({
  pending: 'Pending',
  in_review: 'Sedang Dinilai',
  completed: 'Selesai',
  approved: 'Disetujui'
}[s] || s || 'Pending')

const assessmentStatusBadge = (s) => ({
  pending: 'bg-secondary text-white',
  in_review: 'bg-warning text-dark',
  completed: 'bg-info text-white',
  approved: 'bg-success text-white'
}[s] || 'bg-light text-muted border')

const formatDate = (dateStr) => {
  if (!dateStr) return '-'
  try {
    const d = new Date(dateStr)
    return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
  } catch {
    return dateStr
  }
}

const phaseLabel = (val) => ({
  daily: 'Harian (Daily)',
  weekly: 'Mingguan (Weekly)',
  monthly: 'Bulanan (Monthly)',
  quarterly: 'Kuartalan (Quarterly)',
  semesterly: 'Semesteran',
  annual: 'Tahunan'
}[val] || val || 'Bulanan')

const phaseBadge = (val) => ({
  daily: 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
  weekly: 'bg-info-subtle text-info border border-info-subtle',
  monthly: 'bg-primary-subtle text-primary border border-primary-subtle',
  quarterly: 'bg-dark-subtle text-dark border border-dark-subtle',
  semesterly: 'bg-secondary-subtle text-secondary border',
  annual: 'bg-danger-subtle text-danger border'
}[val] || 'bg-light text-muted border')

const deptLabel = (val) => ({
  all: 'Semua Departemen',
  FAT: 'FAT (Finance, Acc & Tax)',
  HRGA: 'HRGA (HR & GA)',
  Sales: 'Sales & Marketing',
  Operasional: 'Operasional & Gudang'
}[val] || val || 'Semua Departemen')

const deptBadge = (val) => {
  const v = String(val || '').toUpperCase()
  if (v === 'FAT') return 'bg-info-subtle text-info border border-info-subtle'
  if (v === 'HRGA') return 'bg-purple-subtle text-purple border'
  if (v === 'SALES') return 'bg-success-subtle text-success border border-success-subtle'
  if (v === 'OPERASIONAL') return 'bg-warning-subtle text-warning-emphasis border border-warning-subtle'
  return 'bg-light text-secondary border'
}
</script>

<style scoped>
.kpi-period-detail-page {
  animation: fadeIn 0.2s ease-in-out;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(4px); }
  to { opacity: 1; transform: translateY(0); }
}

.shadow-xs {
  box-shadow: 0 1px 2px rgba(0,0,0,0.05);
}
</style>
