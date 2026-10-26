<template>
  <div class="row">
    <div class="col-12">
      <div class="page-title-box d-sm-flex align-items-center justify-content-between">
        <h4 class="mb-sm-0">Laporan Keterlambatan Karyawan</h4>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <!-- Filter Section -->
      <div class="row mb-4 align-items-end">
        <div class="col-md-3">
          <label class="form-label">Tanggal Mulai</label>
          <input type="date" v-model="filters.start_date" class="form-control" />
        </div>
        <div class="col-md-3">
          <label class="form-label">Tanggal Selesai</label>
          <input type="date" v-model="filters.end_date" class="form-control" />
        </div>
        <div class="col-md-3">
          <label class="form-label">Departemen</label>
          <select v-model="filters.department_id" class="form-select">
            <option value="">Semua Departemen</option>
            <option v-for="d in departments" :key="d.id" :value="d.id">{{ d.name }}</option>
          </select>
        </div>
        <div class="col-md-3 text-end">
          <button class="btn btn-primary me-2" @click="fetchData" :disabled="loading">
            Tampilkan
          </button>
          <button class="btn btn-danger" @click="downloadPdf" :disabled="loading || data.length === 0">
            Cetak PDF
          </button>
        </div>
      </div>

      <!-- Table Section -->
      <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle">
          <thead class="table-light">
            <tr>
              <th width="50" class="text-center">No</th>
              <th>Tanggal</th>
              <th>NIK</th>
              <th>Nama Karyawan</th>
              <th>Departemen</th>
              <th class="text-center">Jam Clock In</th>
              <th class="text-center">Keterlambatan</th>
              <th>Alasan / Keterangan</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="8" class="text-center py-4">
                <div class="spinner-border text-primary" role="status"></div>
              </td>
            </tr>
            <tr v-else-if="data.length === 0">
              <td colspan="8" class="text-center py-4 text-muted">Tidak ada data keterlambatan pada periode ini</td>
            </tr>
            <tr v-else v-for="(item, index) in data" :key="item.id">
              <td class="text-center">{{ index + 1 }}</td>
              <td class="text-center">{{ item.date }}</td>
              <td>{{ item.employee?.nip || item.employee?.employee_id || '-' }}</td>
              <td class="fw-bold">{{ item.employee?.name || '-' }}</td>
              <td>{{ item.employee?.primary_assignment?.department?.name || '-' }}</td>
              <td class="text-center">{{ formatTime(item.clock_in_at) }}</td>
              <td class="text-center fw-bold text-danger">{{ item.late_minutes }} Menit</td>
              <td>{{ item.remarks || item.reason || '-' }}</td>
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

const today = new Date()
const firstDay = new Date(today.getFullYear(), today.getMonth(), 1)
const formatDate = (d) => d.toISOString().split('T')[0]

const filters = ref({
  start_date: formatDate(firstDay),
  end_date: formatDate(today),
  department_id: ''
})
const data = ref([])
const departments = ref([])
const loading = ref(false)

const formatTime = (timeStr) => {
  if (!timeStr) return '-'
  if (timeStr.includes('T')) {
    return timeStr.split('T')[1].substring(0, 5)
  }
  return timeStr.substring(0, 5)
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
  if (!filters.value.start_date || !filters.value.end_date) {
    return alert('Pilih rentang tanggal terlebih dahulu')
  }
  loading.value = true
  try {
    const params = new URLSearchParams()
    params.append('start_date', filters.value.start_date)
    params.append('end_date', filters.value.end_date)
    if (filters.value.department_id) params.append('department_id', filters.value.department_id)
    
    const res = await api.get(`/reports/tardiness?${params.toString()}`)
    data.value = res.data.data
  } catch (e) {
    alert('Gagal mengambil data laporan keterlambatan')
  } finally {
    loading.value = false
  }
}

const downloadPdf = () => {
  const params = new URLSearchParams()
  params.append('type', 'tardiness')
  params.append('start_date', filters.value.start_date)
  params.append('end_date', filters.value.end_date)
  if (filters.value.department_id) params.append('department_id', filters.value.department_id)
  const token = localStorage.getItem('hrms_token')
  params.append('token', token)

  window.open(`${import.meta.env.VITE_API_URL}/reports/export-pdf?${params.toString()}`, '_blank')
}

onMounted(() => {
  loadDepartments()
  fetchData()
})
</script>
