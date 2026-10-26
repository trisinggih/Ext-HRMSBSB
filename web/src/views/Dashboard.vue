<template>
  <div class="container-fluid py-3">
    <!-- Header Page & Multi-Company Filter -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-body p-3">
        <div class="row align-items-center g-3">
          <div class="col-lg-5">
            <h4 class="mb-1 text-dark font-weight-bold tracking-tight">Executive Dashboard HRMS</h4>
            <p class="text-muted small mb-0">{{ auth.isSuperAdmin ? 'Ringkasan performa SDM, kehadiran, payroll, & hasil transaksi perusahaan' : 'Ringkasan operasional SDM, kehadiran, & pengajuan transaksi' }}</p>
          </div>
          <div class="col-lg-7">
            <div class="d-flex flex-wrap align-items-center justify-content-lg-end gap-2">
              <div class="d-flex align-items-center gap-1 bg-light border rounded px-2 py-1">
                <span class="small fw-semibold text-secondary">Perusahaan:</span>
                <select v-model="filterCompanyId" class="form-select form-select-sm border-0 bg-transparent fw-bold text-dark" style="min-width: 180px;" @change="onCompanyChange">
                  <option value="">Semua Perusahaan</option>
                  <option v-for="comp in companies" :key="comp.id" :value="comp.id">{{ comp.name }}</option>
                </select>
              </div>

              <div class="d-flex align-items-center gap-1 bg-light border rounded px-2 py-1">
                <span class="small fw-semibold text-secondary">Cabang:</span>
                <select v-model="filterBranchId" class="form-select form-select-sm border-0 bg-transparent fw-bold text-dark" style="min-width: 150px;" :disabled="!filterCompanyId && branches.length === 0" @change="loadDashboardData">
                  <option value="">Semua Cabang</option>
                  <option v-for="br in branches" :key="br.id" :value="br.id">{{ br.name }}</option>
                </select>
              </div>

              <button class="btn btn-primary btn-sm px-3 fw-semibold" @click="loadDashboardData" :disabled="loading">
                <span v-if="loading" class="spinner-border spinner-border-sm me-1"></span>
                <span v-else>Terapkan</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Indicator Stat Cards (Minimalist Modern Style) -->
    <div class="row g-3 mb-4">
      <div v-for="card in indicators" :key="card.label" class="col-lg-2-4 col-md-4 col-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 p-3 card-stat">
          <div class="d-flex justify-content-between align-items-start mb-2">
            <span class="text-secondary small fw-semibold text-uppercase tracking-wider">{{ card.label }}</span>
            <span class="badge bg-light text-dark border font-size-11">{{ card.badge || 'Stats' }}</span>
          </div>
          <div class="fs-2 fw-bold text-dark mb-1">{{ card.value }}</div>
          <div class="small" :class="card.trendUp ? 'text-success' : 'text-muted'">
            {{ card.trendText }}
          </div>
        </div>
      </div>
    </div>

    <!-- Section 2: Ringkasan Transaksi & Performance Summary (Hanya untuk Super Admin / Manager / SPV) -->
    <div v-if="auth.isSuperAdmin" class="row g-3 mb-4">
      <!-- Latest Payroll Transaction Summary Card -->
      <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <span class="fw-bold text-dark">Ringkasan Payroll Terakhir</span>
            <span class="badge bg-soft-success text-success fw-bold">{{ payrollSummary.status }}</span>
          </div>
          <div class="card-body d-flex flex-column justify-content-between">
            <div>
              <small class="text-muted d-block mb-1">{{ payrollSummary.title }}</small>
              <div class="fs-3 fw-bold text-primary mb-3">
                Rp {{ formatCurrency(payrollSummary.total_take_home) }}
              </div>
              <p class="small text-muted mb-0">Total estimasi pencairan gaji karyawan pada siklus payroll berjalan.</p>
            </div>
            <div class="mt-3 pt-3 border-top d-flex justify-content-between align-items-center">
              <span class="small text-secondary fw-semibold">Rata-rata Skor KPI Perusahaan</span>
              <span class="fw-bold fs-5 text-dark">{{ avgKpiScore }} / 100</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Top KPI Performers Table -->
      <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <span class="fw-bold text-dark">Karyawan Top Performer KPI</span>
            <router-link to="/hrms/kpi/reports" class="small text-primary text-decoration-none fw-semibold">Lihat Semua Laporan</router-link>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0" style="font-size: 12px;">
                <thead class="table-light">
                  <tr>
                    <th style="width: 30px;">#</th>
                    <th>Nama Karyawan</th>
                    <th>Jabatan</th>
                    <th>Departemen</th>
                    <th class="text-end">Skor KPI</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="topPerformers.length === 0">
                    <td colspan="5" class="text-center py-4 text-muted">Belum ada data evaluasi KPI ter-input.</td>
                  </tr>
                  <tr v-for="(p, idx) in topPerformers" :key="p.id">
                    <td class="fw-bold text-secondary">{{ idx + 1 }}</td>
                    <td class="fw-bold text-dark">{{ p.name }}</td>
                    <td>{{ p.position }}</td>
                    <td class="text-muted">{{ p.department }}</td>
                    <td class="text-end fw-bold text-success fs-6">{{ p.score }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Section 3: Grafik Kehadiran & Departemen -->
    <div class="row g-3 mb-4">
      <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-header bg-white border-bottom py-3">
            <span class="fw-bold text-dark">Statistik Kehadiran 7 Hari Terakhir</span>
          </div>
          <div class="card-body p-3">
            <apexchart type="bar" height="300" :options="attendanceChartOptions" :series="attendanceChartSeries"></apexchart>
          </div>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-header bg-white border-bottom py-3">
            <span class="fw-bold text-dark">Karyawan Per Departemen</span>
          </div>
          <div class="card-body p-3">
            <apexchart type="donut" height="300" :options="departmentChartOptions" :series="departmentChartSeries"></apexchart>
          </div>
        </div>
      </div>
    </div>

    <!-- Section 4: Tren Transaksi & Riwayat Pengajuan Terbaru -->
    <div class="row g-3">
      <!-- Monthly Transaction Requests Trend Chart -->
      <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <span class="fw-bold text-dark">Tren Transaksi Pengajuan Bulanan (Cuti, Lembur, Kasbon)</span>
          </div>
          <div class="card-body p-3">
            <apexchart type="area" height="280" :options="transactionTrendChartOptions" :series="transactionTrendChartSeries"></apexchart>
          </div>
        </div>
      </div>

      <!-- Recent Request Transactions Table -->
      <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <span class="fw-bold text-dark">Pengajuan Transaksi Terbaru</span>
            <router-link to="/hrms/approval" class="small text-primary text-decoration-none fw-semibold">Approval</router-link>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0" style="font-size: 11px;">
                <thead class="table-light">
                  <tr>
                    <th>Karyawan</th>
                    <th>Jenis Transaksi</th>
                    <th>Tanggal</th>
                    <th class="text-center">Status</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="recentTransactions.length === 0">
                    <td colspan="4" class="text-center py-4 text-muted">Belum ada pengajuan transaksi terbaru.</td>
                  </tr>
                  <tr v-for="t in recentTransactions" :key="t.id">
                    <td class="fw-bold text-dark">{{ t.employee_name }}</td>
                    <td class="text-secondary">{{ t.type }}</td>
                    <td>{{ t.date }}</td>
                    <td class="text-center">
                      <span class="badge" :class="statusBadgeClass(t.status)">
                        {{ t.status ? t.status.toUpperCase() : 'PENDING' }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../api'
import VueApexCharts from 'vue3-apexcharts'
import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()
const apexchart = VueApexCharts

// Filter State
const loading = ref(false)
const filterCompanyId = ref('')
const filterBranchId = ref('')

const companies = ref([])
const branches = ref([])

// Dashboard Data States
const indicators = ref([
  { label: 'Total Karyawan', value: '0', trendUp: true, trendText: 'Aktif Terdaftar', badge: 'Employees' },
  { label: 'Hadir Hari Ini', value: '0', trendUp: true, trendText: '0% Rate', badge: 'Present' },
  { label: 'Terlambat Hari Ini', value: '0', trendUp: true, trendText: '0% Total', badge: 'Late' },
  { label: 'Sedang Cuti', value: '0', trendUp: true, trendText: 'Cuti Disetujui', badge: 'On Leave' },
  { label: 'Pending Approval', value: '0', trendUp: false, trendText: 'Perlu Dicatat', badge: 'Approval' },
])

const payrollSummary = ref({
  title: 'Payroll Belum Dimulai',
  total_take_home: 0,
  status: 'DRAFT',
})

const avgKpiScore = ref(0)
const topPerformers = ref([])
const recentTransactions = ref([])

const attendanceStats = ref({ categories: [], series: [] })
const departmentStats = ref({ labels: [], series: [] })
const transactionTrendStats = ref({ categories: [], series: [] })

// ApexCharts Configurations
const attendanceChartOptions = computed(() => ({
  chart: { type: 'bar', stacked: false, toolbar: { show: false } },
  plotOptions: { bar: { columnWidth: '45%', borderRadius: 4 } },
  colors: ['#10b981', '#f59e0b', '#ef4444'],
  dataLabels: { enabled: false },
  xaxis: { categories: attendanceStats.value.categories || [] },
  legend: { position: 'top' }
}))

const attendanceChartSeries = computed(() => attendanceStats.value.series || [])

const departmentChartOptions = computed(() => ({
  chart: { type: 'donut' },
  labels: departmentStats.value.labels || [],
  colors: ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899', '#64748b'],
  legend: { position: 'bottom' }
}))

const departmentChartSeries = computed(() => departmentStats.value.series || [])

const transactionTrendChartOptions = computed(() => ({
  chart: { type: 'area', toolbar: { show: false } },
  colors: ['#3b82f6', '#f59e0b', '#8b5cf6'],
  stroke: { curve: 'smooth', width: 2 },
  fill: { type: 'gradient', gradient: { opacityFrom: 0.4, opacityTo: 0.05 } },
  xaxis: { categories: transactionTrendStats.value.categories || [] },
  legend: { position: 'top' }
}))

const transactionTrendChartSeries = computed(() => transactionTrendStats.value.series || [])

// Methods
const fetchCompanies = async () => {
  try {
    const res = await api.get('/companies', { params: { per_page: 100 } })
    companies.value = res.data.data?.data || res.data.data || []
  } catch (err) {
    console.error(err)
  }
}

const onCompanyChange = async () => {
  filterBranchId.value = ''
  branches.value = []
  if (filterCompanyId.value) {
    try {
      const res = await api.get('/branches', { params: { company_id: filterCompanyId.value, per_page: 100 } })
      branches.value = res.data.data?.data || res.data.data || []
    } catch (err) {
      console.error(err)
    }
  }
  loadDashboardData()
}

const loadDashboardData = async () => {
  loading.value = true
  try {
    const res = await api.get('/dashboard/indicators', {
      params: {
        company_id: filterCompanyId.value,
        branch_id: filterBranchId.value,
      }
    })

    const data = res.data.data
    if (data.indicators) {
      indicators.value = data.indicators
    }
    if (data.payroll_summary) {
      payrollSummary.value = data.payroll_summary
    } else {
      payrollSummary.value = { title: '-', total_take_home: 0, status: '-' }
    }
    if (data.recent_transactions) {
      recentTransactions.value = data.recent_transactions
    }
    if (data.top_performers) {
      topPerformers.value = data.top_performers
    } else {
      topPerformers.value = []
    }
    if (data.statistics) {
      if (data.statistics.attendance) attendanceStats.value = data.statistics.attendance
      if (data.statistics.departments) departmentStats.value = data.statistics.departments
      if (data.statistics.transaction_trends) transactionTrendStats.value = data.statistics.transaction_trends
      if (data.statistics.kpi !== undefined) avgKpiScore.value = data.statistics.kpi
    }
  } catch (err) {
    console.error(err)
  } finally {
    loading.value = false
  }
}

const formatCurrency = (val) => {
  return new Intl.NumberFormat('id-ID').format(val || 0)
}

const statusBadgeClass = (status) => {
  const s = (status || '').toLowerCase()
  return {
    approved: 'bg-success',
    pending: 'bg-warning text-dark',
    rejected: 'bg-danger',
  }[s] || 'bg-secondary'
}

onMounted(() => {
  fetchCompanies()
  loadDashboardData()
})
</script>

<style scoped>
.col-lg-2-4 {
  width: 20%;
}
@media (max-width: 992px) {
  .col-lg-2-4 {
    width: 50%;
  }
}
@media (max-width: 576px) {
  .col-lg-2-4 {
    width: 100%;
  }
}

.card-stat {
  transition: transform 0.15s ease-in-out;
}
.card-stat:hover {
  transform: translateY(-2px);
}

.bg-soft-success {
  background-color: #d1e7dd;
}
.bg-soft-warning {
  background-color: #fff3cd;
}
.bg-soft-danger {
  background-color: #f8d7da;
}
</style>
