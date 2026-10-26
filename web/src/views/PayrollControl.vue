<template>
  <div>
    <!-- Header (outside card, standard diagonal cut tab) -->
    <div class="d-flex align-items-stretch mb-0" style="min-height: 42px;">
      <div style="
        background: var(--bs-primary, #008779);
        color: #fff;
        padding: 10px 40px 10px 20px;
        clip-path: polygon(0 0, calc(100% - 24px) 0, 100% 100%, 0 100%);
        display: flex;
        align-items: center;
        min-width: 200px;
        max-width: 50%;
      ">
        <h4 class="mb-0 font-size-16 fw-semibold text-white text-nowrap" style="letter-spacing: 0.01em;">
          Monitoring & Control Penggajian
        </h4>
      </div>
      <div class="flex-grow-1 d-flex align-items-center justify-content-end gap-2 ps-3" style="padding-bottom: 10px;">
        <router-link
          :to="{ path: '/hrms/payroll-periods', query: { cycle: filters.cycle_type === 'weekly' ? 'weekly_payment' : 'monthly' } }"
          class="btn btn-action-secondary px-3 py-2 shadow-none"
          style="border-radius:7px; font-size: 0.85rem;"
        >
          <i class="mdi mdi-calendar-clock-outline me-1"></i> Kelola Periode ({{ filters.cycle_type === 'weekly' ? 'Mingguan' : 'Bulanan' }})
        </router-link>
        <router-link to="/hrms/payslips" class="btn btn-action-secondary px-3 py-2 shadow-none" style="border-radius:7px; font-size: 0.85rem;">
          <i class="mdi mdi-history me-1"></i> Riwayat Slip Gaji
        </router-link>
        <button
          class="btn btn-action-success px-3 py-2 waves-effect waves-light shadow-sm"
          style="border-radius:7px; font-size: 0.85rem;"
          @click="sendBulkEmail"
          :disabled="loading || summary.total_email_unsent === 0"
        >
          <i class="mdi mdi-email-multiple-outline me-1"></i>
          Kirim Email Bulk ({{ summary.total_email_unsent || 0 }} Belum Dikirim)
        </button>
      </div>
    </div>

    <!-- Main Card -->
    <div class="card">
      <div class="card-body">
        <!-- Cycle Switcher Tabs (Bulanan vs Mingguan) -->
        <div class="d-flex gap-2 border-bottom pb-3 mb-4 align-items-center flex-wrap">
          <button
            type="button"
            class="btn btn-sm px-3 py-2 fw-semibold"
            :class="filters.cycle_type === 'monthly' ? 'btn-primary shadow-sm' : 'btn-light text-muted'"
            style="border-radius: 8px;"
            @click="switchCycle('monthly')"
          >
            <i class="mdi mdi-calendar-month me-1"></i> 1. Monitoring Gaji Bulanan
          </button>
          <button
            type="button"
            class="btn btn-sm px-3 py-2 fw-semibold"
            :class="filters.cycle_type === 'weekly' ? 'btn-primary shadow-sm' : 'btn-light text-muted'"
            style="border-radius: 8px;"
            @click="switchCycle('weekly')"
          >
            <i class="mdi mdi-calendar-week me-1"></i> 2. Monitoring Pembayaran Mingguan
          </button>
        </div>

        <!-- Banner Info Siklus -->
        <div
          class="p-3 rounded-3 mb-4 border d-flex justify-content-between align-items-center flex-wrap gap-2"
          :class="filters.cycle_type === 'weekly' ? 'bg-warning-subtle border-warning-subtle' : 'bg-info-subtle border-info-subtle'"
        >
          <div>
            <div class="fw-bold text-dark fs-6">
              {{ filters.cycle_type === 'weekly' ? 'Monitoring Pembayaran Mingguan (Jumat s/d Kamis)' : 'Monitoring Penggajian Bulanan (Staf & Karyawan Tetap/Kontrak)' }}
            </div>
            <div class="small text-muted">
              {{ filters.cycle_type === 'weekly'
                ? 'Siklus mingguan berjalan dari hari Jumat sampai Kamis (7 hari). Memantau status proses dan pembayaran gaji karyawan berskema mingguan (DAILY_PAID_WEEKLY).'
                : 'Memantau kelengkapan proses gaji dan pengiriman slip gaji bulanan untuk semua karyawan berskema bulanan.'
              }}
            </div>
          </div>
          <span class="badge px-3 py-2 fs-7 bg-white text-dark border">
            {{ summary.period_name || (filters.cycle_type === 'weekly' ? 'Siklus Jumat s/d Kamis' : 'Periode Terpilih') }}
          </span>
        </div>

        <!-- Filter & Search Controls (Standard Toolbar Row) -->
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
          <!-- Left: Tampilkan X data -->
          <div class="d-flex align-items-center text-muted">
            <span class="me-2 text-nowrap">Tampilkan</span>
            <select v-model="perPage" class="form-select form-select-sm w-auto" style="border-radius: 8px; font-weight: 500;">
              <option :value="10">10</option>
              <option :value="25">25</option>
              <option :value="50">50</option>
              <option :value="100">100</option>
            </select>
            <span class="ms-2 text-nowrap">data</span>
          </div>

          <!-- Right: Filters, Search & Reload -->
          <div class="d-flex align-items-center flex-wrap gap-2">
            <!-- Filter Bulan -->
            <div>
              <select v-model="filters.month" @change="onMonthOrYearChange" class="form-select form-select-sm border-0 bg-light text-secondary w-auto" style="border-radius: 8px; font-weight: 500; min-width: 130px; box-shadow: 0 2px 4px rgba(0,0,0,0.03);">
                <option v-for="(name, num) in monthNames" :key="num" :value="num">{{ name }}</option>
              </select>
            </div>

            <!-- Filter Tahun -->
            <div>
              <input
                type="number"
                v-model="filters.year"
                @change="onMonthOrYearChange"
                class="form-control form-control-sm border-0 bg-light text-secondary text-center"
                style="border-radius: 8px; font-weight: 500; width: 85px; box-shadow: 0 2px 4px rgba(0,0,0,0.03);"
                placeholder="Tahun"
              />
            </div>

            <!-- Filter Periode Spesifik (Bila ada lebih dari 1 periode dalam bulan tsb) -->
            <div v-if="availablePeriods.length > 0">
              <select v-model="filters.payroll_period_id" @change="fetchData" class="form-select form-select-sm border-0 bg-light text-secondary w-auto" style="border-radius: 8px; font-weight: 500; min-width: 200px; box-shadow: 0 2px 4px rgba(0,0,0,0.03);">
                <option value="">{{ filters.cycle_type === 'weekly' ? 'Semua Minggu Bulan Ini' : 'Semua Periode Bulan Ini' }}</option>
                <option v-for="p in availablePeriods" :key="p.id" :value="p.id">
                  {{ p.name }} {{ (p.start_date && p.end_date) ? `(${p.start_date} s/d ${p.end_date})` : '' }}
                </option>
              </select>
            </div>

            <!-- Filter Perusahaan -->
            <div>
              <select v-model="filters.company_id" @change="fetchData" class="form-select form-select-sm border-0 bg-light text-secondary w-auto" style="border-radius: 8px; font-weight: 500; min-width: 170px; box-shadow: 0 2px 4px rgba(0,0,0,0.03);">
                <option value="">Semua Perusahaan</option>
                <option v-for="c in companies" :key="c.id" :value="c.id">{{ c.name }}</option>
              </select>
            </div>

            <!-- Filter Status Payroll -->
            <div>
              <select v-model="filters.status" @change="fetchData" class="form-select form-select-sm border-0 bg-light text-secondary w-auto" style="border-radius: 8px; font-weight: 500; min-width: 140px; box-shadow: 0 2px 4px rgba(0,0,0,0.03);">
                <option value="">Semua Status</option>
                <option value="processed">Sudah Diproses</option>
                <option value="unprocessed">Belum Diproses</option>
              </select>
            </div>

            <!-- Search box (Standard Input Group with Magnify Icon) -->
            <div class="position-relative" style="width: 220px;">
              <div class="input-group input-group-sm bg-light" style="border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.03); overflow: hidden;">
                <span class="input-group-text bg-transparent border-0 text-muted ps-3 pe-2">
                  <i class="mdi mdi-magnify font-size-16"></i>
                </span>
                <input
                  type="search"
                  v-model="searchQuery"
                  class="form-control border-0 bg-transparent fw-medium ps-0"
                  style="box-shadow: none;"
                  placeholder="Pencarian cepat..."
                />
              </div>
            </div>

            <!-- Reload Button -->
            <button
              class="btn btn-sm btn-outline-secondary px-2"
              @click="fetchData"
              :disabled="loading"
              style="border-radius: 8px;"
              title="Muat Ulang Data"
            >
              <i class="mdi mdi-refresh" :class="{ 'mdi-spin': loading }"></i>
            </button>
          </div>
        </div>

        <!-- Table Loading -->
        <div v-if="loading" class="text-center py-5">
          <div class="spinner-border text-primary" role="status"></div>
          <p class="text-muted mt-2 mb-0">Memuat status penggajian karyawan...</p>
        </div>

        <!-- Table List (Standard HRMS Table with Gradient Header) -->
        <div v-else class="table-responsive">
          <table class="table table-striped table-bordered table-hover mb-0">
            <thead class="custom-thead-gradient">
              <tr>
                <th style="width: 50px;" class="text-center align-middle text-white border-0">No.</th>
                <th class="text-white border-0 align-middle">NIP</th>
                <th class="text-white border-0 align-middle">Nama Karyawan</th>
                <th class="text-white border-0 align-middle">Perusahaan & Departemen</th>
                <th class="text-white border-0 align-middle">Jabatan</th>
                <th class="text-center text-white border-0 align-middle">Skema Gaji</th>
                <th class="text-center text-white border-0 align-middle">Status Penggajian</th>
                <th class="text-end text-white border-0 align-middle">Gaji Bersih (THP)</th>
                <th class="text-center text-white border-0 align-middle">Status Email Slip</th>
                <th class="text-center text-white border-0 align-middle" style="width: 140px;">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="filteredData.length === 0">
                <td colspan="10" class="text-center py-5 text-muted">
                  <div class="mb-2 fs-6">Tidak ada data karyawan yang cocok dengan kriteria filter ini.</div>
                  <div class="small text-secondary">
                    Silakan ubah pilihan bulan, tahun, siklus, atau perusahaan di atas.
                  </div>
                </td>
              </tr>
              <tr v-for="(item, index) in paginatedData" :key="item.employee_id">
                <td class="text-center align-middle text-muted small">
                  {{ (currentPage - 1) * perPage + index + 1 }}
                </td>
                <td class="align-middle">
                  <span class="badge bg-light text-secondary border font-monospace">
                    {{ item.nip || '-' }}
                  </span>
                </td>
                <td class="align-middle">
                  <div class="fw-semibold text-dark">{{ item.name }}</div>
                  <small class="text-muted">{{ item.email || 'Tanpa Email' }}</small>
                </td>
                <td class="align-middle">
                  <div class="text-dark small fw-medium">{{ item.company_name }}</div>
                  <small class="text-muted">{{ item.department_name }}</small>
                </td>
                <td class="align-middle">
                  <span class="text-dark small">{{ item.position_name }}</span>
                </td>

                <!-- Skema Gaji -->
                <td class="text-center align-middle">
                  <span
                    class="badge px-2 py-1 font-size-11"
                    :class="item.salary_cycle_type === 'DAILY_PAID_WEEKLY' || item.salary_cycle_type === 'weekly' ? 'bg-warning-subtle text-warning border border-warning-subtle' : 'bg-primary-subtle text-primary border border-primary-subtle'"
                  >
                    {{ item.salary_cycle_label || 'Bulanan' }}
                  </span>
                </td>

                <!-- Status Payroll -->
                <td class="text-center align-middle">
                  <span v-if="item.is_payroll_processed" class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                    SUDAH DIPROSES <span v-if="item.payroll_status">({{ item.payroll_status.toUpperCase() }})</span>
                  </span>
                  <span v-else class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">
                    BELUM DIPROSES
                  </span>
                </td>

                <!-- Net Salary -->
                <td class="text-end align-middle">
                  <span v-if="item.is_payroll_processed" class="fw-bold text-dark">
                    Rp {{ formatRupiah(item.net_salary) }}
                  </span>
                  <span v-else class="text-muted">-</span>
                </td>

                <!-- Status Email -->
                <td class="text-center align-middle">
                  <div v-if="item.is_email_sent">
                    <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1">
                      SUDAH DIKIRIM
                    </span>
                    <small class="d-block text-muted font-size-11 mt-1">{{ item.email_sent_at }}</small>
                  </div>
                  <div v-else-if="item.is_payroll_processed">
                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1">
                      BELUM DIKIRIM
                    </span>
                  </div>
                  <div v-else>
                    <span class="badge bg-light text-muted border px-2 py-1">-</span>
                  </div>
                </td>

                <!-- Actions -->
                <td class="text-center align-middle">
                  <div v-if="item.is_payroll_processed" class="d-flex justify-content-center gap-1">
                    <button
                      type="button"
                      class="btn-action-detail"
                      @click="sendSingleEmail(item)"
                      title="Kirim Slip via Email"
                      :disabled="sendingEmailId === item.payroll_run_id"
                    >
                      <i class="mdi mdi-email-outline"></i> Email
                    </button>
                    <button
                      type="button"
                      class="btn-action-secondary"
                      @click="openPdf(item.payroll_run_id)"
                      title="Download PDF Slip"
                    >
                      <i class="mdi mdi-file-pdf-box text-danger"></i> PDF
                    </button>
                  </div>
                  <span v-else class="text-muted small">
                    Perlu Generate
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination (Standard Pagination Rounded) -->
        <div v-if="filteredData.length > 0" class="row align-items-center mt-3 pt-3 border-top">
          <div class="col-sm-6">
            <div class="text-muted small">
              Menampilkan {{ ((currentPage - 1) * perPage) + 1 }} sampai {{ Math.min(currentPage * perPage, filteredData.length) }} dari {{ filteredData.length }} data (halaman {{ currentPage }} dari {{ totalPages }})
            </div>
          </div>
          <div class="col-sm-6">
            <div class="float-sm-end">
              <ul class="pagination pagination-rounded mb-0">
                <li class="page-item" :class="{ disabled: currentPage <= 1 }">
                  <a class="page-link" href="#" @click.prevent="currentPage > 1 && (currentPage--)">‹</a>
                </li>
                <li v-for="p in visiblePages" :key="p" class="page-item" :class="{ active: currentPage === p }">
                  <a class="page-link" href="#" @click.prevent="currentPage = p">{{ p }}</a>
                </li>
                <li class="page-item" :class="{ disabled: currentPage >= totalPages }">
                  <a class="page-link" href="#" @click.prevent="currentPage < totalPages && (currentPage++)">›</a>
                </li>
              </ul>
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

const today = new Date()
const monthNames = {
  '01': 'Januari', '02': 'Februari', '03': 'Maret', '04': 'April',
  '05': 'Mei', '06': 'Juni', '07': 'Juli', '08': 'Agustus',
  '09': 'September', '10': 'Oktober', '11': 'November', '12': 'Desember'
}

const currentMonth = String(today.getMonth() + 1).padStart(2, '0')
const currentYear = today.getFullYear()

const filters = ref({
  cycle_type: 'monthly',
  month: currentMonth,
  year: currentYear,
  payroll_period_id: '',
  company_id: '',
  status: '',
  email_status: ''
})

const summary = ref({
  total_employees: 0,
  total_processed: 0,
  total_unprocessed: 0,
  total_email_sent: 0,
  total_email_unsent: 0
})

const availablePeriods = ref([])
const data = ref([])
const companies = ref([])
const loading = ref(false)
const sendingEmailId = ref(null)

const switchCycle = (cycle) => {
  filters.value.cycle_type = cycle
  filters.value.payroll_period_id = ''
  fetchData()
}

const onMonthOrYearChange = () => {
  filters.value.payroll_period_id = ''
  fetchData()
}

const searchQuery = ref('')
const perPage = ref(25)
const currentPage = ref(1)

const filteredData = computed(() => {
  let list = data.value || []
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase()
    list = list.filter(item =>
      (item.name || '').toLowerCase().includes(q) ||
      (item.nip || '').toLowerCase().includes(q) ||
      (item.company_name || '').toLowerCase().includes(q) ||
      (item.department_name || '').toLowerCase().includes(q) ||
      (item.position_name || '').toLowerCase().includes(q) ||
      (item.salary_cycle_label || '').toLowerCase().includes(q)
    )
  }
  return list
})

const paginatedData = computed(() => {
  const start = (currentPage.value - 1) * perPage.value
  return filteredData.value.slice(start, start + perPage.value)
})

const totalPages = computed(() => {
  return Math.ceil(filteredData.value.length / perPage.value) || 1
})

const visiblePages = computed(() => {
  const total = totalPages.value
  const cur = currentPage.value
  const delta = 2
  const range = []
  for (let i = Math.max(1, cur - delta); i <= Math.min(total, cur + delta); i++) {
    range.push(i)
  }
  return range
})

const formatRupiah = (val) => {
  if (!val) return '0'
  return Number(val).toLocaleString('id-ID')
}

const loadCompanies = async () => {
  try {
    const res = await api.get('/companies', { params: { per_page: 200 } })
    const d = res.data !== undefined ? res.data : res
    if (Array.isArray(d)) companies.value = d
    else if (d && Array.isArray(d.data)) companies.value = d.data
    else companies.value = []
  } catch (e) {
    console.error(e)
  }
}

const fetchData = async () => {
  loading.value = true
  try {
    const params = new URLSearchParams()
    params.append('cycle_type', filters.value.cycle_type)
    params.append('month', filters.value.month)
    params.append('year', filters.value.year)
    if (filters.value.payroll_period_id) params.append('payroll_period_id', filters.value.payroll_period_id)
    if (filters.value.company_id) params.append('company_id', filters.value.company_id)
    if (filters.value.status) params.append('status', filters.value.status)
    if (filters.value.email_status) params.append('email_status', filters.value.email_status)

    const res = await api.get(`/payroll/control-monitoring?${params.toString()}`)
    data.value = res.data.data || []
    summary.value = res.data.summary || summary.value
    availablePeriods.value = res.data.available_periods || []
    currentPage.value = 1
  } catch (e) {
    console.error('Gagal memuat data monitoring penggajian:', e)
  } finally {
    loading.value = false
  }
}

const sendSingleEmail = async (item) => {
  if (!item.payroll_run_id) return
  sendingEmailId.value = item.payroll_run_id
  try {
    const res = await api.post(`/payroll/runs/${item.payroll_run_id}/send-email`)
    alert(res.data.message || 'Email berhasil dikirim!')
    fetchData()
  } catch (e) {
    alert(e.response?.data?.message || 'Gagal mengirim email')
  } finally {
    sendingEmailId.value = null
  }
}

const sendBulkEmail = async () => {
  const periodId = filters.value.payroll_period_id || summary.value.period_id || data.value.find(i => i.period_id)?.period_id
  if (!periodId) {
    return alert('Periode payroll belum ditemukan atau belum ada slip yang diproses.')
  }
  if (!confirm(`Kirim email slip gaji secara otomatis ke semua karyawan yang belum menerima email?`)) {
    return
  }

  loading.value = true
  try {
    const res = await api.post('/payroll/send-email-bulk', { payroll_period_id: periodId })
    alert(res.data.message || 'Kirim email bulk berhasil!')
    fetchData()
  } catch (e) {
    alert(e.response?.data?.message || 'Gagal mengirim email bulk')
  } finally {
    loading.value = false
  }
}

const openPdf = (runId) => {
  const token = localStorage.getItem('hrms_token')
  window.open(`${import.meta.env.VITE_API_URL}/payroll/runs/${runId}/pdf?token=${token}`, '_blank')
}

onMounted(async () => {
  await loadCompanies()
  await fetchData()
})
</script>

<style scoped>
.custom-thead-gradient th {
  background: var(--bs-primary, #124d32) !important;
  color: white !important;
  border-bottom: 0 !important;
}
</style>
