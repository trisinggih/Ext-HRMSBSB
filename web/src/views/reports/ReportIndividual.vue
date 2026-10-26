<template>
  <div class="row">
    <div class="col-12">
      <div class="page-title-box d-sm-flex align-items-center justify-content-between">
        <h4 class="mb-sm-0">Laporan Per Individu (360° Summary)</h4>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <!-- Filter Section -->
      <div class="row mb-4 align-items-end">
        <div class="col-md-5">
          <label class="form-label">Pilih Karyawan</label>
          <select v-model="filters.employee_id" class="form-select" @change="fetchData">
            <option value="">-- Pilih Karyawan --</option>
            <option v-for="emp in employees" :key="emp.id" :value="emp.id">
              {{ emp.nip || emp.employee_id || '' }} - {{ emp.name }}
            </option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Tahun</label>
          <input type="number" v-model="filters.year" class="form-control" placeholder="2026" />
        </div>
        <div class="col-md-4 text-end">
          <button class="btn btn-primary me-2" @click="fetchData" :disabled="loading || !filters.employee_id">
            Tampilkan
          </button>
          <button class="btn btn-danger" @click="downloadPdf" :disabled="loading || !data">
            Cetak PDF
          </button>
        </div>
      </div>

      <!-- Loading state -->
      <div v-if="loading" class="text-center py-5">
        <div class="spinner-border text-primary" role="status"></div>
        <p class="mt-2 text-muted">Memuat data rekapitulasi individu...</p>
      </div>

      <!-- Empty state -->
      <div v-else-if="!filters.employee_id" class="text-center py-5 text-muted">
        Silakan pilih karyawan terlebih dahulu untuk melihat Laporan Individu 360°.
      </div>

      <!-- Data Display -->
      <div v-else-if="data" class="row">
        <!-- Biodata Overview Card -->
        <div class="col-md-12 mb-4">
          <div class="card bg-light border-0">
            <div class="card-body d-flex align-items-center justify-content-between">
              <div>
                <h5 class="mb-1 text-primary">{{ data.employee.name }}</h5>
                <p class="mb-0 text-muted">
                  NIK: {{ data.employee.nip || data.employee.employee_id }} | 
                  Dept: {{ data.employee.primary_assignment?.department?.name || '-' }} | 
                  Jabatan: {{ data.employee.primary_assignment?.position?.name || '-' }}
                </p>
                <small class="text-muted">
                  Status: {{ data.employee.employee_status || '-' }} | Tgl Masuk: {{ data.employee.join_date || '-' }}
                </small>
              </div>
              <div class="text-end">
                <span class="badge bg-info p-2 fs-6">Periode Evaluasi {{ filters.year }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Summary Cards -->
        <div class="col-md-3 mb-3">
          <div class="card card-body text-center border-start border-4 border-success">
            <h6 class="text-muted">Total Kehadiran</h6>
            <h3 class="mb-0 text-success">{{ data.attendance_summary?.total_present || 0 }} Hari</h3>
          </div>
        </div>
        <div class="col-md-3 mb-3">
          <div class="card card-body text-center border-start border-4 border-warning">
            <h6 class="text-muted">Total Terlambat</h6>
            <h3 class="mb-0 text-warning">{{ data.attendance_summary?.total_late || 0 }} Kali</h3>
          </div>
        </div>
        <div class="col-md-3 mb-3">
          <div class="card card-body text-center border-start border-4 border-info">
            <h6 class="text-muted">Total Cuti / Izin</h6>
            <h3 class="mb-0 text-info">{{ data.attendance_summary?.total_leave || 0 }} Hari</h3>
          </div>
        </div>
        <div class="col-md-3 mb-3">
          <div class="card card-body text-center border-start border-4 border-primary">
            <h6 class="text-muted">Total Jam Lembur</h6>
            <h3 class="mb-0 text-primary">{{ data.overtime_summary?.total_hours || 0 }} Jam</h3>
          </div>
        </div>

        <!-- Detail Sections -->
        <div class="col-md-6 mb-4">
          <div class="card h-100">
            <div class="card-header bg-transparent fw-bold">Riwayat Evaluasi KPI</div>
            <div class="card-body p-0">
              <table class="table table-sm mb-0">
                <thead>
                  <tr>
                    <th>Periode / Bulan</th>
                    <th>Tipe</th>
                    <th>Skor</th>
                    <th>Grade</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="!data.kpi_evaluations?.length">
                    <td colspan="4" class="text-center text-muted py-3">Belum ada evaluasi KPI</td>
                  </tr>
                  <tr v-for="kpi in data.kpi_evaluations" :key="kpi.id">
                    <td>{{ kpi.period || kpi.month || '-' }} {{ kpi.year || '' }}</td>
                    <td>{{ kpi.type || 'Existing' }}</td>
                    <td class="fw-bold">{{ kpi.final_score || kpi.score || 0 }}</td>
                    <td><span class="badge bg-secondary">{{ kpi.grade || kpi.rating || '-' }}</span></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="col-md-6 mb-4">
          <div class="card h-100">
            <div class="card-header bg-transparent fw-bold text-danger">Catatan Pelanggaran & SP</div>
            <div class="card-body p-0">
              <table class="table table-sm mb-0">
                <thead>
                  <tr>
                    <th>No Surat</th>
                    <th>Tingkat SP</th>
                    <th>Tanggal</th>
                    <th>Berlaku</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="!data.violations?.length">
                    <td colspan="4" class="text-center text-muted py-3">Tidak ada catatan pelanggaran</td>
                  </tr>
                  <tr v-for="sp in data.violations" :key="sp.id">
                    <td>{{ sp.letter_number || '-' }}</td>
                    <td class="text-danger fw-bold">{{ sp.warning_level || 'SP' }}</td>
                    <td>{{ sp.issue_date || sp.date || '-' }}</td>
                    <td>{{ sp.expiry_date || '-' }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../../api'

const filters = ref({
  employee_id: '',
  year: new Date().getFullYear()
})
const employees = ref([])
const data = ref(null)
const loading = ref(false)

const loadEmployees = async () => {
  try {
    const res = await api.get('/employees?limit=500')
    employees.value = res.data.data.data || res.data.data || []
  } catch (e) {
    console.error(e)
  }
}

const fetchData = async () => {
  if (!filters.value.employee_id) return
  loading.value = true
  try {
    const params = new URLSearchParams()
    params.append('employee_id', filters.value.employee_id)
    params.append('year', filters.value.year)

    const res = await api.get(`/reports/individual?${params.toString()}`)
    data.value = res.data.data
  } catch (e) {
    alert('Gagal mengambil data laporan individu')
  } finally {
    loading.value = false
  }
}

const downloadPdf = () => {
  if (!filters.value.employee_id) return
  const params = new URLSearchParams()
  params.append('type', 'individual')
  params.append('employee_id', filters.value.employee_id)
  params.append('year', filters.value.year)
  const token = localStorage.getItem('hrms_token')
  params.append('token', token)

  window.open(`${import.meta.env.VITE_API_URL}/reports/export-pdf?${params.toString()}`, '_blank')
}

onMounted(() => {
  loadEmployees()
})
</script>
