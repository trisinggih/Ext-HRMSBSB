<template>
  <div class="employee-history-page">
    <!-- Page Header -->
    <div class="row mb-4 align-items-center">
      <div class="col-md-7">
        <div class="d-flex align-items-center gap-3">
          <router-link to="/hrms/employees" class="btn btn-outline-secondary btn-sm rounded-3 shadow-xs">
            <i class="mdi mdi-arrow-left me-1"></i> Kembali ke Karyawan
          </router-link>
          <div>
            <h4 class="mb-1 text-dark fw-bold">
              <i class="mdi mdi-account-clock-outline text-primary me-2"></i>History Karyawan (Riwayat Terminasi)
            </h4>
            <p class="text-muted font-size-13 mb-0">
              Daftar seluruh karyawan yang telah keluar, resign, PHK, habis kontrak, atau berstatus non-aktif.
            </p>
          </div>
        </div>
      </div>
      <div class="col-md-5 text-md-end mt-3 mt-md-0 d-flex justify-content-md-end gap-2">
        <router-link to="/hrms/mutations" class="btn btn-outline-info btn-sm rounded-3 shadow-xs">
          <i class="mdi mdi-swap-horizontal me-1"></i> Riwayat Mutasi & Karir
        </router-link>
        <router-link to="/hrms/terminations" class="btn btn-outline-primary btn-sm rounded-3 shadow-xs">
          <i class="mdi mdi-file-document-edit-outline me-1"></i> Kelola Form Terminasi
        </router-link>
        <button @click="fetchData" class="btn btn-light btn-sm rounded-3 shadow-xs text-secondary border">
          <i class="mdi mdi-refresh me-1" :class="{ 'mdi-spin': loading }"></i> Refresh
        </button>
      </div>
    </div>

    <!-- Summary Statistics Cards -->
    <div class="row g-3 mb-4">
      <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
          <div class="card-body p-3 d-flex align-items-center">
            <div class="avatar-md rounded-3 bg-secondary-subtle text-secondary d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px;">
              <i class="mdi mdi-account-off-outline font-size-24"></i>
            </div>
            <div class="flex-grow-1 overflow-hidden">
              <p class="text-muted text-truncate font-size-12 mb-1 fw-medium">Total Karyawan Keluar</p>
              <h4 class="mb-0 fw-bold text-dark">{{ pagination.total }}</h4>
            </div>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
          <div class="card-body p-3 d-flex align-items-center">
            <div class="avatar-md rounded-3 bg-info-subtle text-info d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px;">
              <i class="mdi mdi-account-arrow-right-outline font-size-24"></i>
            </div>
            <div class="flex-grow-1 overflow-hidden">
              <p class="text-muted text-truncate font-size-12 mb-1 fw-medium">Resign (Pengunduran Diri)</p>
              <h4 class="mb-0 fw-bold text-info">{{ stats.resignCount }}</h4>
            </div>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
          <div class="card-body p-3 d-flex align-items-center">
            <div class="avatar-md rounded-3 bg-danger-subtle text-danger d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px;">
              <i class="mdi mdi-account-cancel-outline font-size-24"></i>
            </div>
            <div class="flex-grow-1 overflow-hidden">
              <p class="text-muted text-truncate font-size-12 mb-1 fw-medium">PHK / Pemberhentian</p>
              <h4 class="mb-0 fw-bold text-danger">{{ stats.phkCount }}</h4>
            </div>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
          <div class="card-body p-3 d-flex align-items-center">
            <div class="avatar-md rounded-3 bg-warning-subtle text-warning d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px;">
              <i class="mdi mdi-calendar-clock-outline font-size-24"></i>
            </div>
            <div class="flex-grow-1 overflow-hidden">
              <p class="text-muted text-truncate font-size-12 mb-1 fw-medium">Habis Kontrak / Lainnya</p>
              <h4 class="mb-0 fw-bold text-warning">{{ stats.otherCount }}</h4>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Card Filter & Table -->
    <div class="card border-0 shadow-sm rounded-3">
      <div class="card-body p-4">
        
        <!-- Filters Row -->
        <div class="row g-2 mb-3">
          <div class="col-md-3">
            <div class="input-group input-group-sm shadow-xs">
              <span class="input-group-text bg-white border-end-0 text-muted"><i class="mdi mdi-magnify"></i></span>
              <input
                v-model="filters.search"
                @keyup.enter="applyFilters"
                type="text"
                class="form-control border-start-0"
                placeholder="Cari Nama / NIP / Email..."
              />
            </div>
          </div>
          <div class="col-md-2 col-6">
            <select v-model="filters.company_id" @change="applyFilters" class="form-select form-select-sm shadow-xs">
              <option value="">Semua Perusahaan (Legal)</option>
              <option v-for="comp in companies" :key="comp.id" :value="comp.id">{{ comp.name }}</option>
            </select>
          </div>
          <div class="col-md-2 col-6">
            <select v-model="filters.branch_id" @change="applyFilters" class="form-select form-select-sm shadow-xs">
              <option value="">Semua Area Penempatan</option>
              <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.company?.name ? `${b.name} (${b.company.name})` : b.name }}</option>
            </select>
          </div>
          <div class="col-md-2 col-6">
            <select v-model="filters.department_id" @change="applyFilters" class="form-select form-select-sm shadow-xs">
              <option value="">Semua Departemen</option>
              <option v-for="dept in departments" :key="dept.id" :value="dept.id">{{ dept.branch?.name ? `${dept.name} (${dept.branch.name})` : dept.name }}</option>
            </select>
          </div>
          <div class="col-md-2 col-6">
            <select v-model="filters.termination_type" @change="applyFilters" class="form-select form-select-sm shadow-xs">
              <option value="">Semua Tipe Keluar</option>
              <option value="resignation">Resign</option>
              <option value="phk">PHK</option>
              <option value="contract_end">Habis Kontrak</option>
              <option value="retirement">Pensiun</option>
              <option value="termination">Pemberhentian</option>
              <option value="other">Lainnya / Manual</option>
            </select>
          </div>
          <div class="col-md-1 d-flex gap-1">
            <button @click="applyFilters" class="btn btn-primary btn-sm w-100 shadow-xs" title="Terapkan Filter">
              <i class="mdi mdi-filter"></i>
            </button>
            <button @click="resetFilters" class="btn btn-light btn-sm text-muted shadow-xs border" title="Reset Filter">
              <i class="mdi mdi-close"></i>
            </button>
          </div>
        </div>

        <!-- Table View -->
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 border">
            <thead class="table-light">
              <tr class="text-muted font-size-12 text-uppercase">
                <th style="width: 50px;">Foto</th>
                <th>Karyawan</th>
                <th>Perusahaan & Cabang</th>
                <th>Departemen & Jabatan</th>
                <th>Masa Kerja</th>
                <th>Tanggal Keluar</th>
                <th>Tipe & Alasan</th>
                <th>Status</th>
                <th class="text-end" style="width: 140px;">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="loading">
                <td colspan="9" class="text-center py-5">
                  <div class="spinner-border text-primary spinner-border-sm me-2" role="status"></div>
                  <span class="text-muted font-size-13">Memuat riwayat karyawan...</span>
                </td>
              </tr>
              <tr v-else-if="items.length === 0">
                <td colspan="9" class="text-center py-5">
                  <div class="text-muted mb-2">
                    <i class="mdi mdi-account-search-outline font-size-48 text-secondary opacity-50"></i>
                  </div>
                  <h6 class="text-dark fw-bold mb-1">Tidak Ada Data History Karyawan</h6>
                  <p class="text-muted font-size-12 mb-0">Belum ada karyawan yang berstatus keluar/terminasi atau sesuai dengan filter pencarian.</p>
                </td>
              </tr>
              <tr v-for="row in items" :key="row.id" class="cursor-pointer">
                <!-- Foto -->
                <td>
                  <img
                    :src="row.photo ? (row.photo.startsWith('http') ? row.photo : 'http://localhost:8011/storage/' + row.photo) : 'https://ui-avatars.com/api/?name=' + encodeURIComponent(row.name || 'User') + '&background=random'"
                    @error="e => e.target.src = 'https://ui-avatars.com/api/?name=' + encodeURIComponent(row.name || 'User') + '&background=random'"
                    class="rounded-circle img-thumbnail shadow-xs"
                    style="width: 40px; height: 40px; object-fit: cover;"
                    alt="avatar"
                  />
                </td>

                <!-- Karyawan -->
                <td>
                  <div class="fw-bold text-dark">{{ row.name }}</div>
                  <div class="d-flex align-items-center gap-1 mt-1">
                    <span class="badge bg-light text-secondary border font-monospace" style="font-size: 10px;">
                      {{ row.employee_id || row.nip || '-' }}
                    </span>
                    <small v-if="row.email" class="text-muted font-size-11">&bull; {{ row.email }}</small>
                  </div>
                </td>

                <!-- Perusahaan & Cabang -->
                <td>
                  <div class="fw-semibold text-dark font-size-13">{{ row.assignments?.[0]?.company?.name || '-' }}</div>
                  <div class="text-muted font-size-12">{{ row.assignments?.[0]?.branch?.name || '-' }}</div>
                  <div v-if="row.assignments?.[0]?.placement_company && row.assignments?.[0]?.placement_company_id !== row.assignments?.[0]?.company_id" class="mt-1">
                    <span class="badge bg-warning-subtle text-dark border font-size-10">
                      <i class="mdi mdi-map-marker-radius me-1"></i>{{ row.assignments?.[0]?.placement_company?.name }}
                    </span>
                  </div>
                </td>

                <!-- Departemen & Jabatan -->
                <td>
                  <div class="text-dark font-size-13">{{ row.assignments?.[0]?.department?.name || '-' }}</div>
                  <div v-if="row.assignments?.[0]?.department?.branch?.name || row.assignments?.[0]?.branch?.name" class="text-muted font-size-11">
                    (Cabang: {{ row.assignments?.[0]?.department?.branch?.name || row.assignments?.[0]?.branch?.name }})
                  </div>
                  <div class="text-muted font-size-12">{{ row.assignments?.[0]?.position?.name || '-' }}</div>
                </td>

                <!-- Masa Kerja -->
                <td>
                  <div class="text-dark font-size-12">
                    <i class="mdi mdi-calendar-check text-success me-1"></i>{{ formatDate(row.assignments?.[0]?.join_date) }}
                  </div>
                  <small class="text-muted font-size-11">
                    Tenure: {{ calculateTenure(row.assignments?.[0]?.join_date, getTerminationDate(row)) }}
                  </small>
                </td>

                <!-- Tanggal Keluar -->
                <td>
                  <div class="d-inline-flex align-items-center gap-1 text-danger fw-semibold font-size-13">
                    <i class="mdi mdi-calendar-remove"></i>
                    {{ formatDate(getTerminationDate(row)) || 'Tidak Tercatat' }}
                  </div>
                </td>

                <!-- Tipe & Alasan -->
                <td>
                  <div>
                    <span class="badge" :class="getTerminationTypeBadgeClass(getTerminationType(row))">
                      {{ formatTerminationType(getTerminationType(row)) }}
                    </span>
                  </div>
                  <div v-if="getTerminationReason(row)" class="text-muted font-size-11 text-truncate mt-1" style="max-width: 180px;" :title="getTerminationReason(row)">
                    {{ getTerminationReason(row) }}
                  </div>
                </td>

                <!-- Status -->
                <td>
                  <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">
                    Nonaktif / Keluar
                  </span>
                </td>

                <!-- Aksi -->
                <td class="text-end">
                  <div class="d-inline-flex align-items-center gap-1">
                    <router-link
                      :to="`/hrms/employees/${row.id}`"
                      class="btn btn-sm btn-outline-info shadow-xs rounded-2"
                      title="Lihat Detail Profil Karyawan"
                    >
                      <i class="mdi mdi-account-eye-outline me-1"></i> Detail
                    </router-link>
                    
                    <button
                      v-if="row.latest_termination?.id"
                      @click="downloadPdf(row.latest_termination)"
                      class="btn btn-sm btn-outline-danger shadow-xs rounded-2"
                      title="Cetak Surat Terminasi (PDF)"
                      :disabled="downloadingId === row.latest_termination.id"
                    >
                      <span v-if="downloadingId === row.latest_termination.id" class="spinner-border spinner-border-sm"></span>
                      <i v-else class="mdi mdi-file-pdf-box"></i>
                    </button>

                    <button
                      @click="confirmReactivate(row)"
                      class="btn btn-sm btn-outline-success shadow-xs rounded-2"
                      title="Aktifkan Kembali Karyawan"
                    >
                      <i class="mdi mdi-account-check-outline"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="pagination.total > 0" class="d-flex align-items-center justify-content-between mt-3 pt-3 border-top flex-wrap gap-2">
          <div class="text-muted font-size-12">
            Menampilkan <strong>{{ (pagination.current_page - 1) * pagination.per_page + 1 }}</strong> -
            <strong>{{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }}</strong> dari
            <strong>{{ pagination.total }}</strong> karyawan keluar
          </div>
          <div class="d-flex align-items-center gap-1">
            <button
              class="btn btn-sm btn-light border"
              :disabled="pagination.current_page <= 1"
              @click="changePage(pagination.current_page - 1)"
            >
              &laquo; Prev
            </button>
            <span class="px-2 font-size-12 fw-bold text-dark">
              Hal {{ pagination.current_page }} dari {{ pagination.last_page }}
            </span>
            <button
              class="btn btn-sm btn-light border"
              :disabled="pagination.current_page >= pagination.last_page"
              @click="changePage(pagination.current_page + 1)"
            >
              Next &raquo;
            </button>
          </div>
        </div>

      </div>
    </div>

    <!-- Modal Konfirmasi Reactivate -->
    <div v-if="showReactivateModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 1060;">
      <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <div class="modal-content border-0 shadow-lg rounded-3">
          <div class="modal-body p-4 text-center">
            <div class="avatar-lg bg-success-subtle text-success rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
              <i class="mdi mdi-account-convert font-size-28"></i>
            </div>
            <h5 class="fw-bold text-dark mb-2">Aktifkan Kembali Karyawan?</h5>
            <p class="text-muted font-size-13 mb-3">
              Apakah Anda yakin ingin mengaktifkan kembali karyawan <strong class="text-dark">{{ selectedEmployee?.name }}</strong> (NIP: {{ selectedEmployee?.employee_id || '-' }})?
            </p>
            <div class="alert alert-warning text-start font-size-12 py-2 mb-4">
              <i class="mdi mdi-alert-circle-outline me-1"></i>
              Status karyawan akan kembali menjadi <strong>Aktif</strong> dan akan muncul pada daftar karyawan aktif serta absensi harian.
            </div>
            <div class="d-flex justify-content-center gap-2">
              <button type="button" class="btn btn-light px-4 rounded-2" @click="showReactivateModal = false" :disabled="reactivating">
                Batal
              </button>
              <button type="button" class="btn btn-success px-4 rounded-2" @click="executeReactivate" :disabled="reactivating">
                <span v-if="reactivating" class="spinner-border spinner-border-sm me-1"></span>
                Ya, Aktifkan Kembali
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import api from '../api'
import { toast } from 'vue3-toastify'

const loading = ref(false)
const items = ref([])
const companies = ref([])
const branches = ref([])
const departments = ref([])

const pagination = reactive({
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0
})

const stats = reactive({
  resignCount: 0,
  phkCount: 0,
  otherCount: 0
})

const filters = reactive({
  search: '',
  company_id: '',
  branch_id: '',
  department_id: '',
  termination_type: '',
  start_date: '',
  end_date: ''
})

const filteredBranches = computed(() => {
  if (!filters.company_id) return branches.value
  return branches.value.filter(b => b.company_id == filters.company_id)
})

const onCompanyChange = () => {
  filters.branch_id = ''
  applyFilters()
}

const fetchData = async (page = 1) => {
  loading.value = true
  try {
    const params = {
      page,
      per_page: pagination.per_page,
      search: filters.search || undefined,
      company_id: filters.company_id || undefined,
      branch_id: filters.branch_id || undefined,
      department_id: filters.department_id || undefined,
      termination_type: filters.termination_type || undefined,
      start_date: filters.start_date || undefined,
      end_date: filters.end_date || undefined,
    }

    const res = await api.get('/employees/history', { params })
    const resData = res.data?.data || {}
    items.value = resData.data || []
    pagination.current_page = resData.current_page || 1
    pagination.last_page = resData.last_page || 1
    pagination.total = resData.total || 0

    // Compute basic stats
    calculateStats(items.value)
  } catch (err) {
    console.error('Failed to fetch employee history:', err)
    toast.error('Gagal memuat data history karyawan')
  } finally {
    loading.value = false
  }
}

const calculateStats = (list) => {
  let resign = 0
  let phk = 0
  let other = 0

  list.forEach(emp => {
    const t = getTerminationType(emp)
    if (t === 'resignation') resign++
    else if (t === 'phk' || t === 'termination') phk++
    else other++
  })

  stats.resignCount = resign
  stats.phkCount = phk
  stats.otherCount = other
}

const fetchRelations = async () => {
  try {
    const [compRes, branchRes, deptRes] = await Promise.all([
      api.get('/companies?per_page=100').catch(() => ({ data: { data: [] } })),
      api.get('/branches?per_page=200').catch(() => ({ data: { data: [] } })),
      api.get('/departments?per_page=200').catch(() => ({ data: { data: [] } }))
    ])
    companies.value = compRes.data?.data?.data || compRes.data?.data || []
    branches.value = branchRes.data?.data?.data || branchRes.data?.data || []
    departments.value = deptRes.data?.data?.data || deptRes.data?.data || []
  } catch (e) {
    console.warn('Error loading filter relations:', e)
  }
}

const applyFilters = () => {
  fetchData(1)
}

const resetFilters = () => {
  filters.search = ''
  filters.company_id = ''
  filters.branch_id = ''
  filters.department_id = ''
  filters.termination_type = ''
  filters.start_date = ''
  filters.end_date = ''
  fetchData(1)
}

const changePage = (p) => {
  fetchData(p)
}

// Helpers
const getTerminationDate = (row) => {
  if (row.latest_termination?.termination_date) return row.latest_termination.termination_date
  if (row.terminations && row.terminations.length > 0) {
    return row.terminations[0].termination_date
  }
  return row.updated_at ? row.updated_at.substring(0, 10) : null
}

const getTerminationType = (row) => {
  if (row.latest_termination?.type) return row.latest_termination.type
  if (row.terminations && row.terminations.length > 0) {
    return row.terminations[0].type
  }
  return 'other'
}

const getTerminationReason = (row) => {
  if (row.latest_termination?.reason) return row.latest_termination.reason
  if (row.terminations && row.terminations.length > 0) {
    return row.terminations[0].reason
  }
  return null
}

const formatTerminationType = (type) => {
  const map = {
    resignation: 'Resign',
    phk: 'PHK',
    contract_end: 'Habis Kontrak',
    retirement: 'Pensiun',
    termination: 'Pemberhentian',
    other: 'Nonaktif / Lainnya'
  }
  return map[type] || (type ? type.toUpperCase() : 'Nonaktif')
}

const getTerminationTypeBadgeClass = (type) => {
  switch (type) {
    case 'resignation':
      return 'bg-info-subtle text-info border border-info-subtle'
    case 'phk':
    case 'termination':
      return 'bg-danger-subtle text-danger border border-danger-subtle'
    case 'contract_end':
      return 'bg-warning-subtle text-warning border border-warning-subtle'
    case 'retirement':
      return 'bg-secondary-subtle text-secondary border border-secondary-subtle'
    default:
      return 'bg-light text-dark border'
  }
}

const formatDate = (dateStr) => {
  if (!dateStr) return '-'
  try {
    const d = new Date(dateStr)
    if (isNaN(d.getTime())) return dateStr
    return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
  } catch {
    return dateStr
  }
}

const calculateTenure = (joinDateStr, exitDateStr) => {
  if (!joinDateStr) return '-'
  const start = new Date(joinDateStr)
  const end = exitDateStr ? new Date(exitDateStr) : new Date()
  if (isNaN(start.getTime()) || isNaN(end.getTime())) return '-'

  let months = (end.getFullYear() - start.getFullYear()) * 12 + (end.getMonth() - start.getMonth())
  if (months < 0) return '-'
  const years = Math.floor(months / 12)
  const remMonths = months % 12

  if (years > 0 && remMonths > 0) return `${years} thn ${remMonths} bln`
  if (years > 0) return `${years} tahun`
  return `${remMonths} bulan`
}

// Download PDF
const downloadingId = ref(null)
const downloadPdf = async (termination) => {
  if (!termination || !termination.id) return
  downloadingId.value = termination.id
  try {
    const res = await api.get(`/terminations/${termination.id}/pdf`, { responseType: 'blob' })
    const blob = new Blob([res.data], { type: 'application/pdf' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `Surat_Terminasi_${termination.id}.pdf`)
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
  } catch (err) {
    toast.error('Gagal mengunduh surat terminasi PDF.')
  } finally {
    downloadingId.value = null
  }
}

// Reactivate Modal
const showReactivateModal = ref(false)
const selectedEmployee = ref(null)
const reactivating = ref(false)

const confirmReactivate = (emp) => {
  selectedEmployee.value = emp
  showReactivateModal.value = true
}

const executeReactivate = async () => {
  if (!selectedEmployee.value) return
  reactivating.value = true
  try {
    await api.post(`/employees/${selectedEmployee.value.id}/reactivate`)
    toast.success(`Karyawan ${selectedEmployee.value.name} berhasil diaktifkan kembali!`)
    showReactivateModal.value = false
    selectedEmployee.value = null
    fetchData(pagination.current_page)
  } catch (err) {
    toast.error('Gagal mengaktifkan kembali karyawan.')
  } finally {
    reactivating.value = false
  }
}

onMounted(() => {
  fetchData()
  fetchRelations()
})
</script>

<style scoped>
.shadow-xs {
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}
.cursor-pointer {
  cursor: default;
}
</style>
