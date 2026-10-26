<template>
  <div class="container-fluid py-3">
    <!-- Top Nav Tabs -->
    <div class="card border-0 shadow-sm mb-3">
      <div class="card-body p-2 d-flex flex-wrap align-items-center justify-content-between gap-2">
        <ul class="nav nav-pills custom-pills">
          <li class="nav-item">
            <button
              class="nav-link fw-semibold px-3 py-2 font-size-13"
              :class="{ active: activeTab === 'dashboard' }"
              @click="activeTab = 'dashboard'"
            >
              Executive Dashboard
            </button>
          </li>
          <li class="nav-item">
            <button
              class="nav-link fw-semibold px-3 py-2 font-size-13"
              :class="{ active: activeTab === 'database' }"
              @click="activeTab = 'database'"
            >
              Database Penilaian
            </button>
          </li>
          <li class="nav-item">
            <button
              class="nav-link fw-semibold px-3 py-2 font-size-13"
              :class="{ active: activeTab === 'history' }"
              @click="activeTab = 'history'"
            >
              Tren Skor Antar Periode
            </button>
          </li>
        </ul>

        <!-- Period Selector -->
        <div class="d-flex align-items-center gap-2">
          <label class="small text-muted mb-0 fw-semibold">Filter Periode:</label>
          <select v-model="selectedPeriodId" class="form-select form-select-sm" style="min-width: 180px;" @change="fetchData">
            <option value="">Semua Periode</option>
            <option v-for="p in periods" :key="p.id" :value="p.id">{{ p.name }}</option>
          </select>
          <button class="btn btn-sm btn-outline-secondary" title="Refresh Data" @click="fetchData">
            Refresh
          </button>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status"></div>
      <div class="mt-2 text-muted small">Memuat data analisis KPI...</div>
    </div>

    <div v-else>
      <!-- TAB 1: EXECUTIVE DASHBOARD -->
      <div v-if="activeTab === 'dashboard'">
        <!-- Standard Header Card -->
        <div class="card border-0 shadow-sm rounded-3 mb-3" style="border-left: 4px solid var(--bs-primary, #008779) !important;">
          <div class="card-body p-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
              <h5 class="mb-1 fw-bold text-dark">Executive Dashboard KPI</h5>
              <p class="small text-muted mb-0">Ringkasan analisis capaian dan distribusi penilaian kinerja karyawan.</p>
            </div>
            <span class="badge bg-light text-secondary border fw-medium px-3 py-2">
              {{ analytics.summary?.total_assessed || 0 }} Karyawan Dinilai
            </span>
          </div>
        </div>

        <div class="row g-3 mb-3">
          <!-- Left Column: Ringkasan & Rating & Tabel Departemen -->
          <div class="col-lg-7">
            <!-- Ringkasan Perusahaan Card -->
            <div class="card border-0 shadow-sm mb-3">
              <div class="card-header bg-dark-teal text-white py-2 fw-bold small">
                RINGKASAN PERUSAHAAN
              </div>
              <div class="card-body p-2">
                <div class="row g-2 text-center">
                  <div class="col-3">
                    <div class="p-2 border rounded bg-light">
                      <small class="text-muted d-block font-size-11">Total Karyawan</small>
                      <span class="fs-4 fw-bold text-dark">{{ analytics.summary?.total_assessed || 0 }}</span>
                    </div>
                  </div>
                  <div class="col-3">
                    <div class="p-2 border rounded bg-light">
                      <small class="text-muted d-block font-size-11">Rata-rata Skor</small>
                      <span class="fs-4 fw-bold text-primary">{{ formatNumber(analytics.summary?.avg_score) }}</span>
                    </div>
                  </div>
                  <div class="col-3">
                    <div class="p-2 border rounded bg-soft-success">
                      <small class="text-success d-block font-size-11 fw-bold">Skor Tertinggi</small>
                      <span class="fs-4 fw-bold text-success">{{ formatNumber(analytics.summary?.max_score) }}</span>
                    </div>
                  </div>
                  <div class="col-3">
                    <div class="p-2 border rounded bg-soft-danger">
                      <small class="text-danger d-block font-size-11 fw-bold">Skor Terendah</small>
                      <span class="fs-4 fw-bold text-danger">{{ formatNumber(analytics.summary?.min_score) }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Distribusi Rating Card -->
            <div class="card border-0 shadow-sm mb-3">
              <div class="card-header bg-dark-teal text-white py-2 fw-bold small">
                DISTRIBUSI RATING
              </div>
              <div class="card-body p-2">
                <div class="row g-2 text-center">
                  <div class="col-3">
                    <div class="p-2 border rounded bg-soft-success">
                      <small class="d-block text-success fw-bold font-size-11">Rating A (>=100)</small>
                      <span class="fs-4 fw-bold text-success">{{ analytics.ratings?.rating_a || 0 }}</span>
                    </div>
                  </div>
                  <div class="col-3">
                    <div class="p-2 border rounded bg-soft-info">
                      <small class="d-block text-info fw-bold font-size-11">Rating B (80-99.9)</small>
                      <span class="fs-4 fw-bold text-info">{{ analytics.ratings?.rating_b || 0 }}</span>
                    </div>
                  </div>
                  <div class="col-3">
                    <div class="p-2 border rounded bg-soft-warning">
                      <small class="d-block text-warning fw-bold font-size-11">Rating C (70-79.9)</small>
                      <span class="fs-4 fw-bold text-warning">{{ analytics.ratings?.rating_c || 0 }}</span>
                    </div>
                  </div>
                  <div class="col-3">
                    <div class="p-2 border rounded bg-soft-danger">
                      <small class="d-block text-danger fw-bold font-size-11">Rating D (&lt;70)</small>
                      <span class="fs-4 fw-bold text-danger">{{ analytics.ratings?.rating_d || 0 }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Rekap Per Departemen -->
            <div class="card border-0 shadow-sm mb-3">
              <div class="card-header bg-dark-teal text-white py-2 fw-bold small">
                REKAP PER DEPARTEMEN
              </div>
              <div class="card-body p-0">
                <div class="table-responsive">
                  <table class="table table-bordered align-middle text-center mb-0" style="font-size: 11px;">
                    <thead class="table-light">
                      <tr>
                        <th style="width: 30px;">No</th>
                        <th class="text-start">Departemen</th>
                        <th>Jml Karyawan</th>
                        <th>Rata-rata Skor</th>
                        <th>Skor Max</th>
                        <th>Skor Min</th>
                        <th class="bg-soft-success text-success">Rating A</th>
                        <th class="bg-soft-info text-info">Rating B</th>
                        <th class="bg-soft-danger text-danger">Rating C/D</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-if="!analytics.department_recap || analytics.department_recap.length === 0">
                        <td colspan="9" class="text-muted py-3">Belum ada data rekap departemen.</td>
                      </tr>
                      <tr v-for="(dept, idx) in analytics.department_recap" :key="dept.department">
                        <td>{{ idx + 1 }}</td>
                        <td class="text-start fw-bold">{{ dept.department }}</td>
                        <td>{{ dept.employee_count }}</td>
                        <td class="fw-bold text-primary">{{ formatNumber(dept.avg_score) }}</td>
                        <td class="text-success fw-bold">{{ formatNumber(dept.max_score) }}</td>
                        <td class="text-danger fw-bold">{{ formatNumber(dept.min_score) }}</td>
                        <td class="fw-bold text-success">{{ dept.rating_a }}</td>
                        <td class="fw-bold text-info">{{ dept.rating_b }}</td>
                        <td class="fw-bold text-danger">{{ dept.rating_cd }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>

            <!-- Top 5 & Bottom 5 Tables -->
            <div class="row g-2">
              <div class="col-md-6">
                <div class="card border-0 shadow-sm">
                  <div class="card-header bg-success text-white py-2 fw-bold small">
                    5 KARYAWAN SKOR TERTINGGI
                  </div>
                  <div class="card-body p-0">
                    <table class="table table-sm table-striped align-middle mb-0" style="font-size: 11px;">
                      <thead>
                        <tr>
                          <th style="width: 25px;">#</th>
                          <th>Nama</th>
                          <th>Dept</th>
                          <th class="text-end">Skor</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="(emp, idx) in analytics.top_5" :key="emp.id">
                          <td>{{ idx + 1 }}</td>
                          <td class="fw-bold">{{ emp.name }}</td>
                          <td class="text-muted">{{ emp.department }}</td>
                          <td class="text-end fw-bold text-success">{{ formatNumber(emp.score) }}</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="card border-0 shadow-sm">
                  <div class="card-header bg-danger text-white py-2 fw-bold small">
                    5 KARYAWAN PERLU PERHATIAN
                  </div>
                  <div class="card-body p-0">
                    <table class="table table-sm table-striped align-middle mb-0" style="font-size: 11px;">
                      <thead>
                        <tr>
                          <th style="width: 25px;">#</th>
                          <th>Nama</th>
                          <th>Dept</th>
                          <th class="text-end">Skor</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="(emp, idx) in analytics.bottom_5" :key="emp.id">
                          <td>{{ idx + 1 }}</td>
                          <td class="fw-bold">{{ emp.name }}</td>
                          <td class="text-muted">{{ emp.department }}</td>
                          <td class="text-end fw-bold text-danger">{{ formatNumber(emp.score) }}</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Right Column: Visual Charts -->
          <div class="col-lg-5">
            <!-- Top 10 Performers Chart -->
            <div class="card border-0 shadow-sm mb-3">
              <div class="card-header bg-white border-bottom py-2 fw-semibold small text-dark">
                Karyawan Top 10 Performer
              </div>
              <div class="card-body p-2">
                <apexchart type="bar" height="260" :options="top10ChartOptions" :series="top10ChartSeries"></apexchart>
              </div>
            </div>

            <!-- Sebaran Skor Karyawan Chart -->
            <div class="card border-0 shadow-sm mb-3">
              <div class="card-header bg-white border-bottom py-2 fw-semibold small text-dark">
                Sebaran Skor Karyawan
              </div>
              <div class="card-body p-2">
                <apexchart type="bar" height="220" :options="distributionChartOptions" :series="distributionChartSeries"></apexchart>
              </div>
            </div>

            <!-- Komposisi Rating Karyawan Donut Chart -->
            <div class="card border-0 shadow-sm">
              <div class="card-header bg-white border-bottom py-2 fw-semibold small text-dark">
                Komposisi Rating Karyawan
              </div>
              <div class="card-body p-2">
                <apexchart type="donut" height="220" :options="ratingPieChartOptions" :series="ratingPieChartSeries"></apexchart>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- TAB 2: DATABASE KARYAWAN -->
      <div v-else-if="activeTab === 'database'">
        <!-- Standard Header Card -->
        <div class="card border-0 shadow-sm rounded-3 mb-3" style="border-left: 4px solid var(--bs-primary, #008779) !important;">
          <div class="card-body p-3">
            <h5 class="mb-1 fw-bold text-dark">Database Penilaian Karyawan</h5>
            <p class="small text-muted mb-0">Pusat data hasil evaluasi KPI karyawan per periode penilaian.</p>
          </div>
        </div>

        <!-- Filter Card -->
        <div class="card border-0 shadow-sm mb-3">
          <div class="card-body p-2">
            <div class="row g-2 align-items-center">
              <div class="col-md-5">
                <div class="input-group input-group-sm">
                  <input v-model="dbSearch" type="text" class="form-control" placeholder="Cari Nama atau NIP..." />
                </div>
              </div>
              <div class="col-md-4">
                <select v-model="dbRatingFilter" class="form-select form-select-sm">
                  <option value="">Semua Rating</option>
                  <option value="A">Rating A (>= 100)</option>
                  <option value="B">Rating B (80 - 99.9)</option>
                  <option value="C">Rating C (70 - 79.9)</option>
                  <option value="D">Rating D (&lt; 70)</option>
                </select>
              </div>
              <div class="col-md-3 text-end">
                <router-link to="/hrms/kpi/assessments" class="btn btn-sm btn-primary px-3 rounded-2">
                  Kelola Penilaian
                </router-link>
              </div>
            </div>
          </div>
        </div>

        <!-- Database Karyawan Table -->
        <div class="card border-0 shadow-sm">
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0 font-size-12">
                <thead class="table-light text-secondary text-uppercase font-size-11">
                  <tr>
                    <th style="width: 40px;" class="text-center">No</th>
                    <th>Nama Karyawan</th>
                    <th>Departemen</th>
                    <th>Jabatan</th>
                    <th>Periode</th>
                    <th class="text-center">Total Skor</th>
                    <th class="text-center">Rating</th>
                    <th>Catatan Reviewer</th>
                    <th class="text-center" style="width: 90px;">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="filteredDatabaseList.length === 0">
                    <td colspan="9" class="text-center py-4 text-muted">Tidak ada data penilaian karyawan.</td>
                  </tr>
                  <tr v-for="(row, idx) in filteredDatabaseList" :key="row.id">
                    <td class="text-center text-muted">{{ idx + 1 }}</td>
                    <td class="fw-semibold text-dark">{{ row.employee?.name }}</td>
                    <td>{{ row.employee?.primary_assignment?.department?.name || row.employee?.department?.name || '-' }}</td>
                    <td>{{ row.employee?.primary_assignment?.position?.name || row.employee?.position?.name || '-' }}</td>
                    <td><span class="badge bg-light text-secondary border font-size-11">{{ row.period?.name || '-' }}</span></td>
                    <td class="text-center fw-bold" :class="scoreColor(row.final_score)">
                      {{ formatNumber(row.final_score) }}
                    </td>
                    <td class="text-center">
                      <span class="badge font-size-11" :class="ratingBadgeClass(row.final_score)">
                        {{ calculateRating(row.final_score) }}
                      </span>
                    </td>
                    <td class="text-muted small">{{ row.reviewer_notes || '—' }}</td>
                    <td class="text-center">
                      <router-link :to="`/hrms/kpi/assessments/${row.id}`" class="btn btn-sm btn-outline-primary py-1 px-2 rounded-2 font-size-11" title="Detail">
                        Detail
                      </router-link>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- TAB 3: RIWAYAT SKOR PERUSAHAAN ANTAR SIKLUS -->
      <div v-else-if="activeTab === 'history'">
        <!-- Standard Header Card -->
        <div class="card border-0 shadow-sm rounded-3 mb-3" style="border-left: 4px solid var(--bs-primary, #008779) !important;">
          <div class="card-body p-3">
            <h5 class="mb-1 fw-bold text-dark">Tren Riwayat Skor Antar Periode</h5>
            <p class="small text-muted mb-0">Memantau perkembangan tren performa perusahaan dari siklus ke siklus review.</p>
          </div>
        </div>

        <div class="row g-3">
          <!-- Tabel Riwayat Periode -->
          <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
              <div class="card-header bg-light text-dark py-2 fw-semibold small border-bottom">
                Riwayat Antar Periode
              </div>
              <div class="card-body p-0">
                <div class="table-responsive">
                  <table class="table table-bordered table-hover align-middle text-center mb-0" style="font-size: 11px;">
                    <thead class="table-light">
                      <tr>
                        <th class="text-start">Periode</th>
                        <th>Tgl Dicatat</th>
                        <th>Jml Karyawan</th>
                        <th>Rata-rata Skor</th>
                        <th class="bg-soft-success text-success">Rating A</th>
                        <th class="bg-soft-info text-info">Rating B</th>
                        <th class="bg-soft-danger text-danger">Rating C/D</th>
                        <th class="text-start">Catatan</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-if="!analytics.period_history || analytics.period_history.length === 0">
                        <td colspan="8" class="text-muted py-4">Belum ada data riwayat siklus.</td>
                      </tr>
                      <tr v-for="item in analytics.period_history" :key="item.period_id">
                        <td class="text-start fw-bold">{{ item.period_name }}</td>
                        <td>{{ item.recorded_date }}</td>
                        <td>{{ item.employee_count }}</td>
                        <td class="fw-bold text-primary">{{ formatNumber(item.avg_score) }}</td>
                        <td class="fw-bold text-success">{{ item.rating_a }}</td>
                        <td class="fw-bold text-info">{{ item.rating_b }}</td>
                        <td class="fw-bold text-danger">{{ item.rating_cd }}</td>
                        <td class="text-start text-muted small">{{ item.notes }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>

          <!-- Trend Line Chart -->
          <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
              <div class="card-header bg-white border-bottom py-2 fw-semibold small text-dark">
                Tren Rata-rata Skor Perusahaan
              </div>
              <div class="card-body p-2">
                <apexchart type="line" height="320" :options="trendChartOptions" :series="trendChartSeries"></apexchart>
              </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../../api'
import VueApexCharts from 'vue3-apexcharts'

const apexchart = VueApexCharts

// Tabs
const activeTab = ref('dashboard')

// State
const loading = ref(true)
const selectedPeriodId = ref('')
const periods = ref([])
const analytics = ref({})
const rawAssessments = ref([])

// Database tab filters
const dbSearch = ref('')
const dbRatingFilter = ref('')

// Compute Database Karyawan List
const filteredDatabaseList = computed(() => {
  if (!rawAssessments.value) return []
  return rawAssessments.value.filter(a => {
    const nameMatch = !dbSearch.value || 
      (a.employee?.name || '').toLowerCase().includes(dbSearch.value.toLowerCase()) ||
      (a.employee?.nip || '').toLowerCase().includes(dbSearch.value.toLowerCase())

    const rating = calculateRating(a.final_score)
    const ratingMatch = !dbRatingFilter.value || rating === dbRatingFilter.value

    return nameMatch && ratingMatch
  })
})

// ApexCharts Options & Series

// Top 10 Performers Horizontal Bar Chart
const top10ChartOptions = computed(() => ({
  chart: { type: 'bar', toolbar: { show: false } },
  plotOptions: { bar: { horizontal: true, barHeight: '60%', borderRadius: 4 } },
  colors: ['#198754'],
  dataLabels: { enabled: true, formatter: (val) => val.toFixed(1) },
  xaxis: { categories: (analytics.value.top_10 || []).map(i => i.name) },
  tooltip: { y: { formatter: (val) => val.toFixed(1) + ' Skor' } }
}))

const top10ChartSeries = computed(() => [{
  name: 'Skor KPI',
  data: (analytics.value.top_10 || []).map(i => i.score)
}])

// Sebaran Skor Histogram Column Chart
const distributionChartOptions = computed(() => {
  const dist = analytics.value.score_distribution || {}
  return {
    chart: { type: 'bar', toolbar: { show: false } },
    plotOptions: { bar: { columnWidth: '45%', borderRadius: 4 } },
    colors: ['#0d6efd'],
    dataLabels: { enabled: true },
    xaxis: { categories: Object.keys(dist) },
    yaxis: { title: { text: 'Jumlah Karyawan' } }
  }
})

const distributionChartSeries = computed(() => {
  const dist = analytics.value.score_distribution || {}
  return [{
    name: 'Karyawan',
    data: Object.values(dist)
  }]
})

// Rating Pie/Donut Chart
const ratingPieChartOptions = computed(() => ({
  chart: { type: 'donut' },
  labels: ['Rating A (>=100)', 'Rating B (80-99.9)', 'Rating C (70-79.9)', 'Rating D (<70)'],
  colors: ['#198754', '#0d6efd', '#ffc107', '#dc3545'],
  legend: { position: 'bottom' }
}))

const ratingPieChartSeries = computed(() => [
  analytics.value.ratings?.rating_a || 0,
  analytics.value.ratings?.rating_b || 0,
  analytics.value.ratings?.rating_c || 0,
  analytics.value.ratings?.rating_d || 0,
])

// Trend Line Chart
const trendChartOptions = computed(() => {
  const history = analytics.value.period_history || []
  return {
    chart: { type: 'line', toolbar: { show: false } },
    colors: ['#dc3545'],
    stroke: { width: 3, curve: 'smooth' },
    markers: { size: 5 },
    xaxis: { categories: history.map(h => h.period_name) },
    yaxis: { min: 0, max: 120, title: { text: 'Rata-rata Skor' } }
  }
})

const trendChartSeries = computed(() => {
  const history = analytics.value.period_history || []
  return [{
    name: 'Rata-rata Skor',
    data: history.map(h => h.avg_score)
  }]
})

// Fetch Data
const fetchData = async () => {
  loading.value = true
  try {
    const [analyticsRes, periodsRes, assessmentsRes] = await Promise.all([
      api.get('/kpi/dashboard-analytics', { params: { period_id: selectedPeriodId.value } }),
      api.get('/kpi/periods', { params: { per_page: 100 } }),
      api.get('/kpi/assessments', { params: { period_id: selectedPeriodId.value, per_page: 500 } })
    ])

    analytics.value = analyticsRes.data.data || {}
    periods.value = periodsRes.data.data?.data || periodsRes.data.data || []
    rawAssessments.value = assessmentsRes.data.data?.data || assessmentsRes.data.data || []
  } catch (err) {
    console.error(err)
  } finally {
    loading.value = false
  }
}

// Helpers
const formatNumber = (val) => {
  if (val === null || val === undefined || isNaN(val)) return '0.0'
  return parseFloat(val).toFixed(1)
}

const calculateRating = (score) => {
  const s = parseFloat(score || 0)
  if (s >= 100) return 'A'
  if (s >= 80) return 'B'
  if (s >= 70) return 'C'
  return 'D'
}

const ratingBadgeClass = (score) => {
  const r = calculateRating(score)
  return {
    'A': 'bg-success',
    'B': 'bg-info text-dark',
    'C': 'bg-warning text-dark',
    'D': 'bg-danger',
  }[r] || 'bg-secondary'
}

const scoreColor = (score) => {
  const s = parseFloat(score || 0)
  if (s >= 100) return 'text-success'
  if (s >= 80) return 'text-primary'
  if (s >= 70) return 'text-warning'
  return 'text-danger'
}

onMounted(() => {
  fetchData()
})
</script>

<style scoped>
.custom-pills .nav-link {
  color: #64748b;
  border-radius: 6px;
  transition: all 0.2s ease;
}
.custom-pills .nav-link.active {
  background-color: var(--bs-primary, #008779);
  color: #ffffff;
}

.bg-soft-success {
  background-color: #d1e7dd;
}
.bg-soft-info {
  background-color: #cff4fc;
}
.bg-soft-warning {
  background-color: #fff3cd;
}
.bg-soft-danger {
  background-color: #f8d7da;
}
</style>
