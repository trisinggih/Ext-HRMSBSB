<template>
  <div class="row">
    <div class="col-12">
      <div class="page-title-box d-sm-flex align-items-center justify-content-between">
        <h4 class="mb-sm-0">Laporan Surat Peringatan (SP) & Pelanggaran</h4>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <!-- Filter Section -->
      <div class="row mb-4 align-items-end">
        <div class="col-md-3">
          <label class="form-label">Tanggal Terbit Mulai</label>
          <input type="date" v-model="filters.start_date" class="form-control" />
        </div>
        <div class="col-md-3">
          <label class="form-label">Tanggal Terbit Selesai</label>
          <input type="date" v-model="filters.end_date" class="form-control" />
        </div>
        <div class="col-md-3">
          <label class="form-label">Tingkat SP</label>
          <select v-model="filters.warning_level" class="form-select">
            <option value="">Semua Tingkat</option>
            <option value="SP 1">Surat Peringatan 1 (SP 1)</option>
            <option value="SP 2">Surat Peringatan 2 (SP 2)</option>
            <option value="SP 3">Surat Peringatan 3 (SP 3)</option>
            <option value="Teguran Lisan">Teguran Lisan</option>
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
              <th>No Surat</th>
              <th class="text-center">Tgl Terbit</th>
              <th>NIK</th>
              <th>Nama Karyawan</th>
              <th>Departemen</th>
              <th class="text-center">Tingkat SP</th>
              <th>Keterangan Pelanggaran</th>
              <th class="text-center">Tgl Kadaluarsa</th>
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
              <td colspan="10" class="text-center py-4 text-muted">Tidak ada data pelanggaran / SP pada periode ini</td>
            </tr>
            <tr v-else v-for="(item, index) in data" :key="item.id">
              <td class="text-center">{{ index + 1 }}</td>
              <td class="fw-bold">{{ item.letter_number || '-' }}</td>
              <td class="text-center">{{ item.issue_date || item.created_at?.split('T')[0] || '-' }}</td>
              <td>{{ item.employee?.nip || item.employee?.employee_id || '-' }}</td>
              <td class="fw-bold">{{ item.employee?.name || '-' }}</td>
              <td>{{ item.employee?.primary_assignment?.department?.name || '-' }}</td>
              <td class="text-center fw-bold text-danger">{{ (item.warning_level || item.type || 'SP').toUpperCase() }}</td>
              <td>{{ item.description || item.reason || '-' }}</td>
              <td class="text-center">{{ item.expiry_date || '-' }}</td>
              <td class="text-center">
                <span class="badge" :class="isStillActive(item.expiry_date) ? 'bg-danger' : 'bg-secondary'">
                  {{ isStillActive(item.expiry_date) ? 'AKTIF' : 'EXPIRED' }}
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

const filters = ref({
  start_date: '',
  end_date: '',
  warning_level: ''
})
const data = ref([])
const loading = ref(false)

const isStillActive = (expiryDate) => {
  if (!expiryDate) return true
  const todayStr = new Date().toISOString().split('T')[0]
  return expiryDate >= todayStr
}

const fetchData = async () => {
  loading.value = true
  try {
    const params = new URLSearchParams()
    if (filters.value.start_date) params.append('start_date', filters.value.start_date)
    if (filters.value.end_date) params.append('end_date', filters.value.end_date)
    if (filters.value.warning_level) params.append('warning_level', filters.value.warning_level)
    
    const res = await api.get(`/reports/violations?${params.toString()}`)
    data.value = res.data.data
  } catch (e) {
    alert('Gagal mengambil data laporan pelanggaran')
  } finally {
    loading.value = false
  }
}

const downloadPdf = () => {
  const params = new URLSearchParams()
  params.append('type', 'violations')
  if (filters.value.start_date) params.append('start_date', filters.value.start_date)
  if (filters.value.end_date) params.append('end_date', filters.value.end_date)
  if (filters.value.warning_level) params.append('warning_level', filters.value.warning_level)
  const token = localStorage.getItem('hrms_token')
  params.append('token', token)

  window.open(`${import.meta.env.VITE_API_URL}/reports/export-pdf?${params.toString()}`, '_blank')
}

onMounted(() => {
  fetchData()
})
</script>
