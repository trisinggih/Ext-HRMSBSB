<template>
  <div class="row">
    <div class="col-12">
      <div class="page-title-box d-sm-flex align-items-center justify-content-between">
        <h4 class="mb-sm-0">Laporan Rekapitulasi Evaluasi KPI Karyawan</h4>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <!-- Filter Section -->
      <div class="row mb-4 align-items-end">
        <div class="col-md-3">
          <label class="form-label">Tahun</label>
          <input type="number" v-model="filters.year" class="form-control" placeholder="2026" />
        </div>
        <div class="col-md-3">
          <label class="form-label">Tipe Evaluation</label>
          <select v-model="filters.kpi_type" class="form-select">
            <option value="">Semua Tipe</option>
            <option value="probation">Probation (Karyawan Percobaan)</option>
            <option value="existing">Existing (Karyawan Tetap/Kontrak)</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Periode / Kategori</label>
          <input type="text" v-model="filters.period" class="form-control" placeholder="Misal: Q1, Jan, Semester 1" />
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
              <th>NIK</th>
              <th>Nama Karyawan</th>
              <th>Departemen</th>
              <th>Jabatan</th>
              <th class="text-center">Tipe KPI</th>
              <th class="text-center">Periode</th>
              <th class="text-center">Skor Akhir</th>
              <th class="text-center">Grade / Kategori</th>
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
              <td colspan="10" class="text-center py-4 text-muted">Tidak ada data rekapitulasi KPI pada kriteria ini</td>
            </tr>
            <tr v-else v-for="(item, index) in data" :key="item.id">
              <td class="text-center">{{ index + 1 }}</td>
              <td>{{ item.employee?.nip || item.employee?.employee_id || '-' }}</td>
              <td class="fw-bold">{{ item.employee?.name || '-' }}</td>
              <td>{{ item.employee?.primary_assignment?.department?.name || '-' }}</td>
              <td>{{ item.employee?.primary_assignment?.position?.name || '-' }}</td>
              <td class="text-center"><span class="badge bg-secondary">{{ item.type || 'Existing' }}</span></td>
              <td class="text-center">{{ item.period || item.month || '-' }} {{ item.year || '' }}</td>
              <td class="text-center fw-bold text-primary">{{ Number(item.final_score || item.score || 0).toFixed(2) }}</td>
              <td class="text-center fw-bold">{{ item.grade || item.rating || '-' }}</td>
              <td class="text-center">
                <span class="badge" :class="item.status === 'approved' || item.status === 'completed' ? 'bg-success' : 'bg-warning'">
                  {{ (item.status || 'draft').toUpperCase() }}
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
  year: new Date().getFullYear(),
  kpi_type: '',
  period: ''
})
const data = ref([])
const loading = ref(false)

const fetchData = async () => {
  loading.value = true
  try {
    const params = new URLSearchParams()
    if (filters.value.year) params.append('year', filters.value.year)
    if (filters.value.kpi_type) params.append('kpi_type', filters.value.kpi_type)
    if (filters.value.period) params.append('period', filters.value.period)
    
    const res = await api.get(`/reports/kpi-summary?${params.toString()}`)
    data.value = res.data.data
  } catch (e) {
    alert('Gagal mengambil data laporan KPI')
  } finally {
    loading.value = false
  }
}

const downloadPdf = () => {
  const params = new URLSearchParams()
  params.append('type', 'kpi-summary')
  if (filters.value.year) params.append('year', filters.value.year)
  if (filters.value.kpi_type) params.append('kpi_type', filters.value.kpi_type)
  if (filters.value.period) params.append('period', filters.value.period)
  const token = localStorage.getItem('hrms_token')
  params.append('token', token)

  window.open(`${import.meta.env.VITE_API_URL}/reports/export-pdf?${params.toString()}`, '_blank')
}

onMounted(() => {
  fetchData()
})
</script>
