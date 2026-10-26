<template>
  <div class="row">
    <div class="col-12">
      <div class="page-title-box d-sm-flex align-items-center justify-content-between">
        <h4 class="mb-sm-0">Laporan Cuti Karyawan</h4>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <!-- Filter Section -->
      <div class="row mb-4 align-items-end">
        <div class="col-md-3">
          <label class="form-label">Tanggal Mulai (Pengajuan)</label>
          <input type="date" v-model="filters.start_date" class="form-control" />
        </div>
        <div class="col-md-3">
          <label class="form-label">Tanggal Selesai</label>
          <input type="date" v-model="filters.end_date" class="form-control" />
        </div>
        <div class="col-md-3">
          <label class="form-label">Status</label>
          <select v-model="filters.status" class="form-select">
            <option value="">Semua Status</option>
            <option value="approved">Disetujui</option>
            <option value="pending">Menunggu</option>
            <option value="rejected">Ditolak</option>
          </select>
        </div>
        <div class="col-md-3 text-end">
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
              <th>Nama Karyawan</th>
              <th>Tipe Cuti</th>
              <th>Tanggal Mulai</th>
              <th>Tanggal Selesai</th>
              <th>Lama (Hari)</th>
              <th>Keterangan</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="8" class="text-center py-4">
                <div class="spinner-border text-primary" role="status"></div>
              </td>
            </tr>
            <tr v-else-if="data.length === 0">
              <td colspan="8" class="text-center py-4 text-muted">Tidak ada data cuti</td>
            </tr>
            <tr v-else v-for="(item, index) in data" :key="item.id">
              <td class="text-center">{{ index + 1 }}</td>
              <td class="fw-bold">{{ item.employee?.name }}</td>
              <td>{{ item.leave_type?.name }}</td>
              <td>{{ item.start_date }}</td>
              <td>{{ item.end_date }}</td>
              <td class="text-center">{{ item.total_days }} Hari</td>
              <td>{{ item.reason }}</td>
              <td>
                <span class="badge" :class="{
                  'bg-success': item.status === 'approved',
                  'bg-warning': item.status === 'pending',
                  'bg-danger': item.status === 'rejected'
                }">{{ formatStatus(item.status) }}</span>
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
  status: 'approved'
})
const data = ref([])
const loading = ref(false)

const formatStatus = (status) => {
  const map = { approved: 'Disetujui', pending: 'Menunggu', rejected: 'Ditolak' }
  return map[status] || status
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
    
    const res = await api.get(`/reports/leaves?${params.toString()}`)
    data.value = res.data.data
  } catch (e) {
    alert('Gagal mengambil data laporan')
  } finally {
    loading.value = false
  }
}

const downloadPdf = () => {
  const params = new URLSearchParams()
  params.append('type', 'leave')
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
