<template>
  <div class="row">
    <div class="col-12">
      <div class="page-title-box d-sm-flex align-items-center justify-content-between">
        <h4 class="mb-sm-0">Laporan Lembur Karyawan</h4>
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
          <label class="form-label">Status Approval</label>
          <select v-model="filters.status" class="form-select">
            <option value="">Semua Status</option>
            <option value="approved">Approved</option>
            <option value="pending">Pending</option>
            <option value="rejected">Rejected</option>
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
              <th class="text-center">Jam Mulai</th>
              <th class="text-center">Jam Selesai</th>
              <th class="text-center">Durasi (Jam)</th>
              <th>Alasan Lembur</th>
              <th class="text-center">Status</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="10" class="text-center py-4">
                <div class="spinner-border text-primary" role="status"></div>
              </td>
            </tr>
            <tr v-else-if="data.length === 0">
              <td colspan="10" class="text-center py-4 text-muted">Tidak ada data pengajuan lembur pada periode ini</td>
            </tr>
            <tr v-else v-for="(item, index) in data" :key="item.id">
              <td class="text-center">{{ index + 1 }}</td>
              <td class="text-center">{{ item.date }}</td>
              <td>{{ item.employee?.nip || item.employee?.employee_id || '-' }}</td>
              <td class="fw-bold">{{ item.employee?.name || '-' }}</td>
              <td>{{ item.employee?.primary_assignment?.department?.name || '-' }}</td>
              <td class="text-center">{{ formatTime(item.start_time) }}</td>
              <td class="text-center">{{ formatTime(item.end_time) }}</td>
              <td class="text-center fw-bold">{{ item.duration_hours || '-' }}</td>
              <td>{{ item.reason || '-' }}</td>
              <td class="text-center">
                <span class="badge" :class="item.status === 'approved' ? 'bg-success' : (item.status === 'rejected' ? 'bg-danger' : 'bg-warning')">
                  {{ (item.status || 'pending').toUpperCase() }}
                </span>
              </td>
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
  status: ''
})
const data = ref([])
const loading = ref(false)

const formatTime = (timeStr) => {
  if (!timeStr) return '-'
  return timeStr.substring(0, 5)
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
    if (filters.value.status) params.append('status', filters.value.status)
    
    const res = await api.get(`/reports/overtime?${params.toString()}`)
    data.value = res.data.data
  } catch (e) {
    alert('Gagal mengambil data laporan lembur')
  } finally {
    loading.value = false
  }
}

const downloadPdf = () => {
  const params = new URLSearchParams()
  params.append('type', 'overtime')
  params.append('start_date', filters.value.start_date)
  params.append('end_date', filters.value.end_date)
  if (filters.value.status) params.append('status', filters.value.status)
  const token = localStorage.getItem('hrms_token')
  params.append('token', token)

  window.open(`${import.meta.env.VITE_API_URL}/reports/export-pdf?${params.toString()}`, '_blank')
}

onMounted(() => {
  fetchData()
})
</script>
