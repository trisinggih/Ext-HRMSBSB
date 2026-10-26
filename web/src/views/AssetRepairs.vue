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
          Permohonan Perbaikan Alat Kerja
        </h4>
      </div>
      <div class="flex-grow-1 d-flex align-items-center justify-content-end gap-2 ps-3" style="padding-bottom: 10px;">
        <!-- Opsi Dropdown -->
        <div class="dropdown">
          <button
            class="btn btn-action-secondary px-3 py-2 waves-effect shadow-none"
            type="button"
            data-bs-toggle="dropdown"
            aria-expanded="false"
            style="border-radius:7px; font-size: 0.85rem;"
          >
            <i class="mdi mdi-tune-vertical-variant me-1"></i> Opsi
          </button>
          <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 py-2" style="border-radius:10px; min-width:190px;">
            <li>
              <router-link to="/hrms/assets" class="dropdown-item py-2 px-3">
                <i class="mdi mdi-archive-outline me-2 text-info"></i> Data Aset
              </router-link>
            </li>
            <li>
              <router-link to="/hrms/asset-requests" class="dropdown-item py-2 px-3">
                <i class="mdi mdi-file-document-edit-outline me-2 text-primary"></i> Pengadaan & Inventaris
              </router-link>
            </li>
            <li>
              <router-link to="/hrms/asset-approval-settings" class="dropdown-item py-2 px-3">
                <i class="mdi mdi-cog-outline me-2 text-primary"></i> Konfigurasi Approval
              </router-link>
            </li>
          </ul>
        </div>

        <router-link
          to="/hrms/asset-repairs/create"
          class="btn btn-action-add px-3 py-2 waves-effect waves-light shadow-sm text-decoration-none"
          style="border-radius:7px; font-size: 0.85rem;"
        >
          <i class="mdi mdi-plus-circle-outline font-size-15 me-1"></i> Ajukan Perbaikan
        </router-link>
      </div>
    </div>

    <!-- Main Card -->
    <div class="card">
      <div class="card-body">
        <!-- Summary Stats Cards -->
        <div class="row g-3 mb-4">
          <div class="col-6 col-md-3">
            <div class="p-3 rounded-3 bg-light border border-light-subtle d-flex align-items-center justify-content-between">
              <div>
                <div class="text-muted small fw-medium">Total Permohonan</div>
                <div class="fs-4 fw-bold text-dark mt-1">{{ totalItems }}</div>
              </div>
              <div class="badge bg-secondary-subtle text-secondary fs-6 px-2 py-1">Semua</div>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="p-3 rounded-3 bg-warning-subtle border border-warning-subtle d-flex align-items-center justify-content-between">
              <div>
                <div class="text-warning-emphasis small fw-medium">Menunggu Review</div>
                <div class="fs-4 fw-bold text-warning-emphasis mt-1">{{ stats.pending }}</div>
              </div>
              <span class="badge bg-warning text-dark px-2 py-1">Pending</span>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="p-3 rounded-3 bg-info-subtle border border-info-subtle d-flex align-items-center justify-content-between">
              <div>
                <div class="text-info-emphasis small fw-medium">Disetujui / Proses</div>
                <div class="fs-4 fw-bold text-info-emphasis mt-1">{{ stats.in_progress }}</div>
              </div>
              <span class="badge bg-info text-white px-2 py-1">Perbaikan</span>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="p-3 rounded-3 bg-success-subtle border border-success-subtle d-flex align-items-center justify-content-between">
              <div>
                <div class="text-success-emphasis small fw-medium">Selesai Diperbaiki</div>
                <div class="fs-4 fw-bold text-success-emphasis mt-1">{{ stats.completed }}</div>
              </div>
              <span class="badge bg-success text-white px-2 py-1">Selesai</span>
            </div>
          </div>
        </div>

        <!-- Filter & Search Controls -->
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
          <!-- Left: Tampilkan X data -->
          <div class="d-flex align-items-center text-muted">
            <span class="me-2 text-nowrap">Tampilkan</span>
            <select v-model="perPage" @change="fetchData(1)" class="form-select form-select-sm w-auto" style="border-radius: 8px; font-weight: 500;">
              <option :value="10">10</option>
              <option :value="25">25</option>
              <option :value="50">50</option>
              <option :value="100">100</option>
            </select>
            <span class="ms-2 text-nowrap">data</span>
          </div>

          <!-- Right: Filter dropdowns & search -->
          <div class="d-flex align-items-center flex-wrap gap-2">
            <!-- Filter Status -->
            <div>
              <select v-model="filterStatus" @change="fetchData(1)" class="form-select form-select-sm border-0 bg-light text-secondary w-auto" style="border-radius: 8px; font-weight: 500; min-width: 150px; box-shadow: 0 2px 4px rgba(0,0,0,0.03);">
                <option value="">Semua Status</option>
                <option value="pending">Menunggu Review</option>
                <option value="approved">Disetujui</option>
                <option value="in_repair">Dalam Perbaikan</option>
                <option value="completed">Selesai</option>
                <option value="rejected">Ditolak</option>
              </select>
            </div>

            <!-- Filter Prioritas -->
            <div>
              <select v-model="filterPriority" @change="fetchData(1)" class="form-select form-select-sm border-0 bg-light text-secondary w-auto" style="border-radius: 8px; font-weight: 500; min-width: 140px; box-shadow: 0 2px 4px rgba(0,0,0,0.03);">
                <option value="">Semua Prioritas</option>
                <option value="tinggi">Prioritas Tinggi</option>
                <option value="sedang">Prioritas Sedang</option>
                <option value="rendah">Prioritas Rendah</option>
              </select>
            </div>

            <!-- Filter Perusahaan -->
            <div>
              <select v-model="filterCompany" @change="fetchData(1)" class="form-select form-select-sm border-0 bg-light text-secondary w-auto" style="border-radius: 8px; font-weight: 500; min-width: 160px; box-shadow: 0 2px 4px rgba(0,0,0,0.03);">
                <option value="">Semua Perusahaan</option>
                <option v-for="c in companies" :key="c.id" :value="c.id">{{ c.name }}</option>
              </select>
            </div>

            <!-- Search box -->
            <div class="search-box position-relative" style="min-width: 240px;">
              <input
                type="text"
                v-model="searchQuery"
                @keyup.enter="fetchData(1)"
                class="form-control form-control-sm border-0 bg-light text-dark ps-3 pe-4"
                placeholder="Cari alat, kode, PIC..."
                style="border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.03);"
              />
              <button
                v-if="searchQuery"
                @click="searchQuery = ''; fetchData(1)"
                class="btn btn-link btn-sm position-absolute end-0 top-50 translate-middle-y text-muted p-0 pe-2"
                type="button"
                style="line-height: 1;"
              >
                &times;
              </button>
            </div>
          </div>
        </div>

        <!-- Table Loading -->
        <div v-if="loading" class="text-center py-5">
          <div class="spinner-border text-primary" role="status"></div>
          <p class="text-muted mt-2 mb-0">Memuat data permohonan perbaikan alat kerja...</p>
        </div>

        <!-- Table Data -->
        <div v-else class="table-responsive">
          <table class="table align-middle table-nowrap table-hover mb-0">
            <thead class="table-light text-uppercase fs-7 text-muted">
              <tr>
                <th style="width: 50px;">No</th>
                <th>Tgl Pengajuan</th>
                <th>Karyawan Pemohon</th>
                <th>Alat Kerja</th>
                <th>Uraian Kerusakan</th>
                <th>Estimasi & Prioritas</th>
                <th class="text-center">Status</th>
                <th class="text-end" style="width: 150px;">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="repairs.length === 0">
                <td colspan="8" class="text-center py-5 text-muted">
                  <div class="mb-2 fs-5">Belum ada permohonan perbaikan alat kerja</div>
                  <router-link to="/hrms/asset-repairs/create" class="btn btn-sm btn-outline-primary mt-1">
                    + Ajukan Perbaikan Alat
                  </router-link>
                </td>
              </tr>
              <tr v-for="(row, idx) in repairs" :key="row.id">
                <td class="text-muted">{{ (currentPage - 1) * perPage + idx + 1 }}</td>
                <td>
                  <div class="fw-semibold text-dark">{{ formatDate(row.submission_date) }}</div>
                  <small class="text-muted">{{ row.submission_month || '-' }}</small>
                </td>
                <td>
                  <div class="fw-semibold text-dark">{{ row.employee ? row.employee.name : (row.pic_name || '-') }}</div>
                  <div class="small text-muted">
                    {{ row.employee?.employee_id ? 'NIP: ' + row.employee.employee_id : '' }}
                    {{ row.employee?.position ? ' • ' + row.employee.position.name : '' }}
                  </div>
                  <div class="small text-muted" v-if="row.branch">
                    {{ row.branch.name }}
                  </div>
                </td>
                <td>
                  <div class="fw-semibold text-dark">{{ row.item_name }}</div>
                  <div class="small text-muted">
                    <span v-if="row.brand_model">{{ row.brand_model }}</span>
                    <span v-if="row.asset_code || row.asset?.inventory_number">
                      • Kode: <code>{{ row.asset_code || row.asset?.inventory_number }}</code>
                    </span>
                  </div>
                  <div class="small text-secondary" v-if="row.quantity && row.quantity > 1">
                    Jumlah: {{ row.quantity }} unit
                  </div>
                </td>
                <td>
                  <div class="text-wrap" style="max-width: 250px;">
                    <span class="badge me-1" :class="conditionBadgeClass(row.condition)">
                      {{ formatCondition(row.condition) }}
                    </span>
                    <span class="badge" :class="row.operational_disrupted ? 'bg-danger-subtle text-danger' : 'bg-light text-muted'">
                      {{ row.operational_disrupted ? 'Operasional Terganggu' : 'Operasional Normal' }}
                    </span>
                    <p class="mb-0 text-muted small mt-1 text-truncate" :title="row.damage_description">
                      {{ row.damage_description }}
                    </p>
                  </div>
                </td>
                <td>
                  <div class="fw-bold text-dark">
                    {{ row.estimated_cost ? formatRupiah(row.estimated_cost) : '-' }}
                  </div>
                  <div>
                    <span class="badge" :class="priorityBadgeClass(row.priority)">
                      Prioritas {{ capitalize(row.priority || 'sedang') }}
                    </span>
                  </div>
                </td>
                <td class="text-center">
                  <span class="badge py-2 px-3 fw-semibold" :class="statusBadgeClass(row.status)">
                    {{ statusLabel(row.status) }}
                  </span>
                </td>
                <td class="text-end">
                  <div class="d-flex justify-content-end gap-1">
                    <!-- Cetak PDF -->
                    <button
                      type="button"
                      class="btn-action-secondary"
                      title="Cetak Formulir PDF"
                      @click="printPdf(row)"
                    >
                      <i class="mdi mdi-file-pdf-box text-danger"></i> PDF
                    </button>

                    <!-- Update Status -->
                    <button
                      type="button"
                      class="btn-action-detail"
                      title="Update Status Permohonan"
                      @click="openStatusModal(row)"
                    >
                      <i class="mdi mdi-information-outline"></i> Status
                    </button>

                    <!-- Edit -->
                    <router-link
                      :to="`/hrms/asset-repairs/${row.id}/edit`"
                      class="btn-action-edit"
                      title="Ubah Formulir"
                    >
                      <i class="mdi mdi-pencil-outline"></i> Edit
                    </router-link>

                    <!-- Hapus -->
                    <button
                      type="button"
                      class="btn-action-delete"
                      title="Hapus"
                      @click="deleteRepair(row)"
                    >
                      <i class="mdi mdi-trash-can-outline"></i> Hapus
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="totalItems > 0" class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-4 pt-3 border-top">
          <div class="text-muted small">
            Menampilkan {{ ((currentPage - 1) * perPage) + 1 }} sampai {{ Math.min(currentPage * perPage, totalItems) }} dari {{ totalItems }} entri
          </div>
          <nav>
            <ul class="pagination pagination-sm mb-0">
              <li class="page-item" :class="{ disabled: currentPage === 1 }">
                <button class="page-link" @click="fetchData(currentPage - 1)">Sebelumnya</button>
              </li>
              <li
                v-for="p in totalPages"
                :key="p"
                class="page-item"
                :class="{ active: p === currentPage }"
              >
                <button class="page-link" @click="fetchData(p)">{{ p }}</button>
              </li>
              <li class="page-item" :class="{ disabled: currentPage === totalPages }">
                <button class="page-link" @click="fetchData(currentPage + 1)">Selanjutnya</button>
              </li>
            </ul>
          </nav>
        </div>
      </div>
    </div>

    <!-- Modal Update Status -->
    <div v-if="showStatusModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
          <div class="modal-header border-0 pb-0">
            <h5 class="modal-title fw-bold text-dark">Update Status Permohonan</h5>
            <button type="button" class="btn-close" @click="showStatusModal = false"></button>
          </div>
          <div class="modal-body p-4">
            <div class="p-3 rounded-3 bg-light mb-3">
              <div class="fw-semibold text-dark">{{ activeRow?.item_name }}</div>
              <div class="text-muted small">
                Pemohon: {{ activeRow?.employee ? activeRow.employee.name : activeRow?.pic_name }}
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold">Pilih Status Baru <span class="text-danger">*</span></label>
              <select v-model="statusForm.status" class="form-select rounded-3">
                <option value="pending">Menunggu Review (Pending)</option>
                <option value="approved">Disetujui (Approved)</option>
                <option value="in_repair">Dalam Proses Perbaikan (In Repair)</option>
                <option value="completed">Selesai Diperbaiki (Completed)</option>
                <option value="rejected">Ditolak (Rejected)</option>
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold">Catatan / Tindak Lanjut</label>
              <textarea
                v-model="statusForm.admin_notes"
                rows="3"
                class="form-control rounded-3"
                placeholder="Tuliskan catatan perbaikan, estimasi waktu, atau alasan penolakan..."
              ></textarea>
            </div>
          </div>
          <div class="modal-footer border-0 pt-0">
            <button type="button" class="btn btn-light rounded-3 px-3" @click="showStatusModal = false">Batal</button>
            <button
              type="button"
              class="btn btn-primary rounded-3 px-4"
              :disabled="updatingStatus"
              @click="submitStatusChange"
            >
              <span v-if="updatingStatus" class="spinner-border spinner-border-sm me-1"></span>
              Simpan Status
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import api from '../api'
import Swal from 'sweetalert2'

const repairs = ref([])
const companies = ref([])
const loading = ref(false)
const totalItems = ref(0)
const currentPage = ref(1)
const perPage = ref(10)

const searchQuery = ref('')
const filterStatus = ref('')
const filterPriority = ref('')
const filterCompany = ref('')

const stats = ref({
  pending: 0,
  in_progress: 0,
  completed: 0,
})

const showStatusModal = ref(false)
const activeRow = ref(null)
const updatingStatus = ref(false)
const statusForm = ref({
  status: 'pending',
  admin_notes: '',
})

const totalPages = computed(() => Math.ceil(totalItems.value / perPage.value) || 1)

const extractArray = (res) => {
  if (!res) return []
  const d = res.data !== undefined ? res.data : res
  if (Array.isArray(d)) return d
  if (d && Array.isArray(d.data)) return d.data
  if (d && d.data && Array.isArray(d.data.data)) return d.data.data
  return []
}

const fetchData = async (page = 1) => {
  currentPage.value = page
  loading.value = true
  try {
    const { data } = await api.get('/asset-repairs', {
      params: {
        page: currentPage.value,
        per_page: perPage.value,
        search: searchQuery.value || undefined,
        status: filterStatus.value || undefined,
        priority: filterPriority.value || undefined,
        company_id: filterCompany.value || undefined,
      }
    })

    if (data && data.data && Array.isArray(data.data)) {
      repairs.value = data.data
      totalItems.value = data.total || data.data.length
    } else {
      const list = extractArray(data)
      repairs.value = list
      totalItems.value = list.length
    }

    // Refresh quick stats
    calculateStats()
  } catch (err) {
    console.error('Gagal mengambil data perbaikan aset:', err)
  } finally {
    loading.value = false
  }
}

const calculateStats = async () => {
  try {
    const { data } = await api.get('/asset-repairs')
    const all = extractArray(data)
    stats.value = {
      pending: all.filter(r => r.status === 'pending').length,
      in_progress: all.filter(r => r.status === 'approved' || r.status === 'in_repair').length,
      completed: all.filter(r => r.status === 'completed').length,
    }
  } catch (e) {
    // ignore
  }
}

const loadCompanies = async () => {
  try {
    const res = await api.get('/companies', { params: { per_page: 200 } })
    companies.value = extractArray(res)
  } catch (err) {
    console.error('Gagal memuat companies:', err)
  }
}

const printPdf = (row) => {
  const baseUrl = api.defaults.baseURL || '/api/v1'
  const token = localStorage.getItem('token') || localStorage.getItem('auth_token')
  const url = `${baseUrl}/asset-repairs/${row.id}/pdf${token ? '?token=' + encodeURIComponent(token) : ''}`
  window.open(url, '_blank')
}

const openStatusModal = (row) => {
  activeRow.value = row
  statusForm.value = {
    status: row.status || 'pending',
    admin_notes: row.admin_notes || '',
  }
  showStatusModal.value = true
}

const submitStatusChange = async () => {
  if (!activeRow.value) return
  updatingStatus.value = true
  try {
    await api.post(`/asset-repairs/${activeRow.value.id}/status`, statusForm.value)
    Swal.fire({
      icon: 'success',
      title: 'Status Diperbarui',
      text: 'Status permohonan berhasil disimpan.',
      timer: 1500,
      showConfirmButton: false,
    })
    showStatusModal.value = false
    fetchData(currentPage.value)
  } catch (err) {
    Swal.fire({
      icon: 'error',
      title: 'Gagal',
      text: err.response?.data?.message || 'Gagal mengubah status permohonan.',
    })
  } finally {
    updatingStatus.value = false
  }
}

const deleteRepair = async (row) => {
  const res = await Swal.fire({
    title: 'Hapus Permohonan?',
    text: `Permohonan perbaikan untuk "${row.item_name}" akan dihapus.`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#6c757d',
    confirmButtonText: 'Ya, Hapus',
    cancelButtonText: 'Batal',
  })

  if (res.isConfirmed) {
    try {
      await api.delete(`/asset-repairs/${row.id}`)
      Swal.fire({
        icon: 'success',
        title: 'Berhasil Dihapus',
        timer: 1200,
        showConfirmButton: false,
      })
      fetchData(currentPage.value)
    } catch (err) {
      Swal.fire({
        icon: 'error',
        title: 'Gagal',
        text: err.response?.data?.message || 'Gagal menghapus data.',
      })
    }
  }
}

const formatDate = (val) => {
  if (!val) return '-'
  try {
    return new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }).format(new Date(val))
  } catch (e) {
    return val
  }
}

const formatRupiah = (val) => {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val)
}

const capitalize = (val) => {
  if (!val) return ''
  return val.charAt(0).toUpperCase() + val.slice(1)
}

const formatCondition = (c) => {
  if (c === 'tidak_berfungsi') return 'Tidak Berfungsi'
  if (c === 'lainnya') return 'Lainnya'
  return 'Rusak'
}

const conditionBadgeClass = (c) => {
  if (c === 'rusak') return 'bg-danger text-white'
  if (c === 'tidak_berfungsi') return 'bg-warning text-dark'
  return 'bg-secondary text-white'
}

const priorityBadgeClass = (p) => {
  if (p === 'tinggi') return 'bg-danger-subtle text-danger border border-danger-subtle'
  if (p === 'sedang') return 'bg-warning-subtle text-warning-emphasis border border-warning-subtle'
  return 'bg-secondary-subtle text-secondary border border-secondary-subtle'
}

const statusBadgeClass = (s) => {
  if (s === 'approved') return 'bg-info text-white'
  if (s === 'in_repair') return 'bg-primary text-white'
  if (s === 'completed') return 'bg-success text-white'
  if (s === 'rejected') return 'bg-danger text-white'
  return 'bg-warning text-dark'
}

const statusLabel = (s) => {
  if (s === 'approved') return 'Disetujui'
  if (s === 'in_repair') return 'Dalam Perbaikan'
  if (s === 'completed') return 'Selesai'
  if (s === 'rejected') return 'Ditolak'
  return 'Pending Review'
}

onMounted(() => {
  fetchData()
  loadCompanies()
})
</script>

<style scoped>
.search-box input:focus {
  outline: none;
  background-color: #fff !important;
  box-shadow: 0 0 0 2px rgba(0, 135, 121, 0.25) !important;
}
</style>
