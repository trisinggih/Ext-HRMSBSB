<template>
  <div class="row">
    <div class="col-12">
      <div class="page-title-box d-sm-flex align-items-center justify-content-between">
        <h4 class="mb-sm-0">Laporan Data Karyawan</h4>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <!-- Filter Section -->
      <div class="row mb-4 align-items-end">
        <div class="col-md-3">
          <label class="form-label">Status Kepegawaian</label>
          <select v-model="filters.status" class="form-select">
            <option value="">Semua Status</option>
            <option value="permanent">Karyawan Tetap</option>
            <option value="contract">Kontrak</option>
            <option value="probation">Probation</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Departemen</label>
          <select v-model="filters.department_id" class="form-select">
            <option value="">Semua Departemen</option>
            <option v-for="d in departments" :key="d.id" :value="d.id">{{ d.name }}</option>
          </select>
        </div>
        <div class="col-md-6 text-end">
          <button class="btn btn-primary me-2" @click="fetchData" :disabled="loading">
            <i class="mdi mdi-filter"></i> Tampilkan
          </button>
          <button class="btn btn-danger" @click="downloadPdf" :disabled="loading || data.length === 0">
            <i class="mdi mdi-file-pdf-box"></i> Cetak PDF
          </button>
        </div>
      </div>

      <!-- Table Section -->
      <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle">
          <thead class="table-light">
            <tr>
              <th width="50">No</th>
              <th>NIK</th>
              <th>Nama Karyawan</th>
              <th>Departemen</th>
              <th>Jabatan</th>
              <th>Status</th>
              <th>Tgl Bergabung</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="7" class="text-center py-4">
                <div class="spinner-border text-primary" role="status"></div>
              </td>
            </tr>
            <tr v-else-if="data.length === 0">
              <td colspan="7" class="text-center py-4 text-muted">Tidak ada data karyawan</td>
            </tr>
            <tr v-else v-for="(item, index) in data" :key="item.id">
              <td class="text-center">{{ index + 1 }}</td>
              <td>{{ item.employee_code }}</td>
              <td class="fw-bold">{{ item.name }}</td>
              <td>{{ item.department?.name || '-' }}</td>
              <td>{{ item.position?.name || '-' }}</td>
              <td>
                <span class="badge" :class="{
                  'bg-success': item.employment_status === 'permanent',
                  'bg-info': item.employment_status === 'contract',
                  'bg-warning': item.employment_status === 'probation'
                }">
                  {{ formatStatus(item.employment_status) }}
                </span>
              </td>
              <td>{{ item.join_date }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../../api'

const filters = ref({
  status: '',
  department_id: ''
})
const data = ref([])
const departments = ref([])
const loading = ref(false)

const formatStatus = (status) => {
  const map = { permanent: 'Tetap', contract: 'Kontrak', probation: 'Probation' }
  return map[status] || status
}

const loadDepartments = async () => {
  try {
    const res = await api.get('/departments')
    departments.value = res.data.data
  } catch (e) {
    console.error(e)
  }
}

const fetchData = async () => {
  loading.value = true
  try {
    const params = new URLSearchParams()
    if (filters.value.status) params.append('status', filters.value.status)
    if (filters.value.department_id) params.append('department_id', filters.value.department_id)
    
    const res = await api.get(`/reports/employees?${params.toString()}`)
    data.value = res.data.data
  } catch (e) {
    alert('Gagal mengambil data laporan')
  } finally {
    loading.value = false
  }
}

const downloadPdf = () => {
  const params = new URLSearchParams()
  params.append('type', 'employee')
  if (filters.value.status) params.append('status', filters.value.status)
  if (filters.value.department_id) params.append('department_id', filters.value.department_id)
  // Ambil token dari localStorage
  const token = localStorage.getItem('hrms_token')
  params.append('token', token)

  window.open(`${import.meta.env.VITE_API_URL}/reports/export-pdf?${params.toString()}`, '_blank')
}

onMounted(() => {
  loadDepartments()
  fetchData()
})
</script>
