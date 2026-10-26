<template>
  <div class="row">
    <div class="col-12">
      <div class="page-title-box d-sm-flex align-items-center justify-content-between">
        <h4 class="mb-sm-0">Laporan Penggajian</h4>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <!-- Filter Section -->
      <div class="row mb-4 align-items-end">
        <div class="col-md-4">
          <label class="form-label">Periode Penggajian</label>
          <select v-model="filters.payroll_period_id" class="form-select">
            <option value="">Pilih Periode...</option>
            <option v-for="p in periods" :key="p.id" :value="p.id">{{ p.name }}</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Departemen</label>
          <select v-model="filters.department_id" class="form-select">
            <option value="">Semua Departemen</option>
            <option v-for="d in departments" :key="d.id" :value="d.id">{{ d.name }}</option>
          </select>
        </div>
        <div class="col-md-5 text-end">
          <button class="btn btn-primary me-2" @click="fetchData" :disabled="loading || !filters.payroll_period_id">
            <i class="mdi mdi-filter"></i> Tampilkan
          </button>
          <button class="btn btn-success me-2" @click="exportExcel" :disabled="loading || !filters.payroll_period_id || exporting">
            <i class="mdi mdi-file-excel"></i> {{ exporting ? 'Mengekspor...' : 'Export Excel' }}
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
              <th class="text-end">Penerimaan Kotor</th>
              <th class="text-end">Potongan (Total)</th>
              <th class="text-end">BPJS (Karyawan)</th>
              <th class="text-end">Gaji Bersih (THP)</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="8" class="text-center py-4">
                <div class="spinner-border text-primary" role="status"></div>
              </td>
            </tr>
            <tr v-else-if="!filters.payroll_period_id && data.length === 0">
              <td colspan="8" class="text-center py-4 text-muted">Silakan pilih periode penggajian</td>
            </tr>
            <tr v-else-if="data.length === 0">
              <td colspan="8" class="text-center py-4 text-muted">Tidak ada data penggajian</td>
            </tr>
            <tr v-else v-for="(item, index) in data" :key="item.id">
              <td class="text-center">{{ index + 1 }}</td>
              <td>{{ item.employee?.employee_code }}</td>
              <td class="fw-bold">{{ item.employee?.name }}</td>
              <td>{{ item.employee?.department?.name || '-' }}</td>
              <td class="text-end">{{ money(item.gross_salary) }}</td>
              <td class="text-end text-danger">{{ money(item.total_deduction) }}</td>
              <td class="text-end text-danger">{{ money(item.total_bpjs) }}</td>
              <td class="text-end text-success fw-bold">{{ money(item.net_salary) }}</td>
            </tr>
          </tbody>
          <tfoot v-if="data.length > 0" class="table-light">
            <tr>
              <th colspan="4" class="text-end">TOTAL KESELURUHAN</th>
              <th class="text-end">{{ money(totalGross) }}</th>
              <th class="text-end text-danger">{{ money(totalDeduction) }}</th>
              <th class="text-end text-danger">{{ money(totalBpjs) }}</th>
              <th class="text-end text-success fw-bold">{{ money(totalNet) }}</th>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../../api'

const filters = ref({
  payroll_period_id: '',
  department_id: ''
})
const data = ref([])
const periods = ref([])
const departments = ref([])
const loading = ref(false)

const money = (val) => {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(val || 0)
}

const totalGross = computed(() => data.value.reduce((sum, item) => sum + Number(item.gross_salary || 0), 0))
const totalDeduction = computed(() => data.value.reduce((sum, item) => sum + Number(item.total_deduction || 0), 0))
const totalBpjs = computed(() => data.value.reduce((sum, item) => sum + Number(item.total_bpjs || 0), 0))
const totalNet = computed(() => data.value.reduce((sum, item) => sum + Number(item.net_salary || 0), 0))

const loadOptions = async () => {
  try {
    const [resDep, resPer] = await Promise.all([
      api.get('/departments'),
      api.get('/payroll/periods')
    ])
    departments.value = resDep.data.data
    periods.value = resPer.data.data
  } catch (e) {
    console.error(e)
  }
}

const fetchData = async () => {
  if (!filters.value.payroll_period_id) return
  loading.value = true
  try {
    const params = new URLSearchParams()
    params.append('payroll_period_id', filters.value.payroll_period_id)
    if (filters.value.department_id) params.append('department_id', filters.value.department_id)
    
    const res = await api.get(`/reports/payroll?${params.toString()}`)
    data.value = res.data.data
  } catch (e) {
    alert('Gagal mengambil data laporan')
  } finally {
    loading.value = false
  }
}

const downloadPdf = () => {
  const params = new URLSearchParams()
  params.append('type', 'payroll')
  params.append('payroll_period_id', filters.value.payroll_period_id)
  if (filters.value.department_id) params.append('department_id', filters.value.department_id)
  const token = localStorage.getItem('hrms_token')
  params.append('token', token)

  window.open(`${import.meta.env.VITE_API_URL}/reports/export-pdf?${params.toString()}`, '_blank')
}

const exporting = ref(false)
const exportExcel = async () => {
  if (!filters.value.payroll_period_id) return
  if (exporting.value) return
  exporting.value = true
  try {
    const res = await api.get(`/payroll/periods/${filters.value.payroll_period_id}/export-excel`, { responseType: 'blob' })
    const selectedP = periods.value.find(p => p.id == filters.value.payroll_period_id)
    const pName = selectedP?.name ? selectedP.name.replace(/\s+/g, '_') : filters.value.payroll_period_id
    const filename = `Laporan_Gaji_Mekanik_${pName}.xlsx`
    const url = window.URL.createObjectURL(new Blob([res.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', filename)
    document.body.appendChild(link)
    link.click()
    link.parentNode.removeChild(link)
    window.URL.revokeObjectURL(url)
  } catch (e) {
    console.error('Export error', e)
    alert('Gagal mengekspor laporan Excel.')
  } finally {
    exporting.value = false
  }
}

onMounted(() => {
  loadOptions()
})
</script>
