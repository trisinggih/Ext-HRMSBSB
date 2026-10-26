<template>
  <CrudPage
    ref="crudPageRef"
    :key="route.fullPath"
    :title="pageTitle"
    :singular="pageTitle"
    endpoint="/payroll/periods"
    :columns="columns"
    :form-fields="formFields"
    :initial-form="initialForm"
    :map-data-to-form="mapDataToForm"
    :transform-payload="transformPayload"
    :base-filters="route.query.cycle ? { cycle_type: route.query.cycle } : {}"
    :filters="filters"
    @formChange="onFormChange"
  >
    <template #actions="{ row }">
      <button v-if="row.status === 'draft'" @click="openGenerateModal(row)" class="btn-action-edit" title="Generate">
        <i class="mdi mdi-cog-outline"></i> Generate
      </button>
      <button v-if="row.status === 'draft'" @click="act(row.id, 'process')" class="btn-action-detail" title="Process">
        <i class="mdi mdi-play-circle-outline"></i> Process
      </button>
      <button v-if="row.status === 'processing' || row.status === 'calculated'" @click="act(row.id, 'complete')" class="btn-action-success" title="Complete">
        <i class="mdi mdi-check-circle-outline"></i> Complete
      </button>
      <button v-if="row.status === 'completed' || row.status === 'approved'" @click="act(row.id, 'close')" class="btn-action-secondary" title="Close">
        <i class="mdi mdi-lock-outline"></i> Close
      </button>
      <button v-if="row.status !== 'closed'" @click="cancelPeriod(row)" class="btn-action-delete" title="Batalkan & Hapus Periode">
        <i class="mdi mdi-trash-can-outline"></i> Hapus
      </button>
      <button @click="viewRuns(row)" class="btn-action-detail" title="Lihat Slip Gaji">
        <i class="mdi mdi-receipt-text-outline"></i> Slip
      </button>
      <button @click="exportReport(row)" class="btn-action-success" title="Export Excel Laporan">
        <i class="mdi mdi-file-excel-box"></i> Export
      </button>
    </template>
  </CrudPage>

  <!-- Modal Generate Payroll -->
  <div v-if="showGenerateModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px);">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content border-0 shadow-lg rounded-4">
        <div class="modal-header border-bottom-0 pt-4 px-4 pb-2">
          <h5 class="modal-title fw-bold fs-4 text-dark">Generate Payroll</h5>
          <button type="button" class="btn-close" @click="showGenerateModal = false"></button>
        </div>
        <div class="modal-body px-4">
          <div class="alert alert-info border-0 shadow-sm d-flex align-items-center mb-3">
            <i class="mdi mdi-information-outline fs-4 me-3 text-info"></i>
            <div>
              <strong>Informasi Penting:</strong><br/>
              Pastikan semua data absensi, pengajuan lembur, dan kasbon/pinjaman telah <b>disetujui (Approved)</b> sebelum men-generate payroll agar perhitungan akurat.
            </div>
          </div>
          
          <div class="d-flex align-items-center justify-content-between mb-2">
            <p class="text-muted mb-0">Pilih karyawan yang akan di-generate payroll-nya untuk periode ini.</p>
            <span class="badge" :class="selectedPeriodCycle === 'daily' ? 'bg-info text-dark' : 'bg-primary'">
              Skema: {{ selectedPeriodCycle === 'daily' ? 'Gaji Harian' : 'Gaji Bulanan' }}
            </span>
          </div>

          <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
            <div class="form-check mb-0">
              <input class="form-check-input" type="checkbox" id="checkAllEmp" 
                :checked="selectedEmployeeIds.length === filteredEmployees.length && filteredEmployees.length > 0" 
                @change="toggleAll">
              <label class="form-check-label fw-bold" for="checkAllEmp">
                Pilih Semua ({{ filteredEmployees.length }} Karyawan Sesuai Tipe & Cabang)
              </label>
            </div>
            <span class="badge bg-primary rounded-pill">{{ selectedEmployeeIds.length }} Terpilih</span>
          </div>

          <div class="table-responsive border rounded" style="max-height: 400px; overflow-y: auto;">
            <table class="table table-hover table-sm mb-0 align-middle">
              <thead class="table-light sticky-top" style="z-index: 1;">
                <tr>
                  <th scope="col" class="text-center" style="width: 40px;">#</th>
                  <th scope="col">ID / NIK</th>
                  <th scope="col">Nama Karyawan</th>
                  <th scope="col">Tipe / Skema Gaji</th>
                  <th scope="col">Jabatan</th>
                  <th scope="col">Perusahaan</th>
                  <th scope="col">Cabang / Area</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="emp in filteredEmployees" :key="emp.id" @click="toggleEmployee(emp.id)" style="cursor: pointer;">
                  <td class="text-center" @click.stop>
                    <input class="form-check-input m-0" type="checkbox" :value="emp.id" v-model="selectedEmployeeIds" :id="'emp-'+emp.id">
                  </td>
                  <td><span class="badge bg-secondary">{{ emp.employee_id || emp.nik || '-' }}</span></td>
                  <td class="fw-medium">{{ emp.name || emp.first_name }}</td>
                  <td>
                    <span class="badge" :class="getMatchingAssignment(emp)?.salary_cycle_type === 'DAILY_PAID_DAILY' || getMatchingAssignment(emp)?.salary_cycle_type === 'daily' ? 'bg-info text-dark' : 'bg-success'">
                      {{ getMatchingAssignment(emp)?.salary_cycle_type === 'DAILY_PAID_DAILY' || getMatchingAssignment(emp)?.salary_cycle_type === 'daily' ? 'Gaji Harian' : 'Gaji Bulanan' }}
                    </span>
                  </td>
                  <td class="text-muted small">{{ getMatchingAssignment(emp)?.position?.name || '-' }}</td>
                  <td class="text-muted small">{{ getMatchingAssignment(emp)?.company?.name || '-' }}</td>
                  <td class="text-muted small">{{ getMatchingAssignment(emp)?.branch?.name || '-' }}</td>
                </tr>
                <tr v-if="filteredEmployees.length === 0">
                  <td colspan="7" class="text-center text-muted py-4">
                    <i class="mdi mdi-account-off fs-1 text-light"></i><br>
                    Tidak ada karyawan yang terdaftar dengan tipe skema dan cabang ini.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
        <div class="modal-footer border-top-0 px-4 pb-4 pt-2">
          <button type="button" class="btn btn-light" @click="showGenerateModal = false">Batal</button>
          <button type="button" class="btn btn-primary px-4" @click="submitGenerate">
            Generate ({{ selectedEmployeeIds.length }})
          </button>
        </div>
      </div>
    </div>
  </div>

</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import api from '../api'
import CrudPage from '../components/CrudPage.vue'

const router = useRouter()
const route = useRoute()
const crudPageRef = ref(null)

const pageTitle = computed(() => {
  if (route.query.cycle === 'daily') return 'Gaji Harian'
  if (route.query.cycle === 'weekly') return 'Gaji Mingguan'
  if (route.query.cycle === 'weekly_payment') return 'Pembayaran Mingguan'
  if (route.query.cycle === 'weekly_payslip') return 'Slip Gaji Mingguan (Bulanan)'
  if (route.query.cycle === 'monthly') return 'Gaji Bulanan'
  return 'Periode Payroll'
})

const showGenerateModal = ref(false)
const selectedPeriodId = ref(null)
const selectedPeriodCycle = ref('')
const selectedPeriodCompanyId = ref(null)
const selectedPeriodBranchId = ref(null)
const employeesList = ref([])
const selectedEmployeeIds = ref([])

const getMatchingAssignment = (emp) => {
  if (!emp?.assignments || emp.assignments.length === 0) return null
  return emp.assignments.find(a => {
    let matchCycle = false
    if (selectedPeriodCycle.value === 'daily') {
      matchCycle = (a.salary_cycle_type === 'DAILY_PAID_DAILY' || a.salary_cycle_type === 'daily')
    } else {
      matchCycle = (a.salary_cycle_type === 'MONTHLY_PAID_MONTHLY' || a.salary_cycle_type === 'monthly')
    }

    const matchCompany = selectedPeriodCompanyId.value
      ? (String(a.company_id) === String(selectedPeriodCompanyId.value) || String(a.placement_company_id) === String(selectedPeriodCompanyId.value))
      : true
    const matchBranch = selectedPeriodBranchId.value ? String(a.branch_id) === String(selectedPeriodBranchId.value) : true
    return matchCycle && matchCompany && matchBranch
  }) || emp.assignments[0]
}

const filteredEmployees = computed(() => {
  if (!selectedPeriodCycle.value) return []
  return employeesList.value.filter(emp => {
    if (emp.assignments && emp.assignments.length > 0) {
      return emp.assignments.some(a => {
        let matchCycle = false
        if (selectedPeriodCycle.value === 'daily') {
          matchCycle = (a.salary_cycle_type === 'DAILY_PAID_DAILY' || a.salary_cycle_type === 'daily')
        } else {
          matchCycle = (a.salary_cycle_type === 'MONTHLY_PAID_MONTHLY' || a.salary_cycle_type === 'monthly')
        }

        const matchCompany = selectedPeriodCompanyId.value
          ? (String(a.company_id) === String(selectedPeriodCompanyId.value) || String(a.placement_company_id) === String(selectedPeriodCompanyId.value))
          : true
        const matchBranch = selectedPeriodBranchId.value ? String(a.branch_id) === String(selectedPeriodBranchId.value) : true
        return matchCycle && matchCompany && matchBranch
      })
    }
    return false
  })
})

onMounted(async () => {
  try {
    const res = await api.get('/employees?per_page=1000&is_active=1&for_payroll=1')
    employeesList.value = res.data?.data?.data || res.data?.data || []
  } catch (e) {
    console.error('Failed to load employees', e)
  }
})

const openGenerateModal = (row) => {
  selectedPeriodId.value = row.id
  selectedPeriodCycle.value = row.cycle_type
  selectedPeriodCompanyId.value = row.company_id
  selectedPeriodBranchId.value = row.branch_id
  
  // Otomatis centang semua karyawan yang sesuai dengan skema tipe periode dan cabang
  selectedEmployeeIds.value = filteredEmployees.value.map(e => e.id)
  showGenerateModal.value = true
}

const toggleAll = (e) => {
  if (e.target.checked) {
    selectedEmployeeIds.value = filteredEmployees.value.map(emp => emp.id)
  } else {
    selectedEmployeeIds.value = []
  }
}

const toggleEmployee = (id) => {
  const index = selectedEmployeeIds.value.indexOf(id)
  if (index === -1) {
    selectedEmployeeIds.value.push(id)
  } else {
    selectedEmployeeIds.value.splice(index, 1)
  }
}

const submitGenerate = async () => {
  if (!selectedPeriodId.value) return
  if (selectedEmployeeIds.value.length === 0) {
    alert('Pilih minimal satu karyawan!')
    return
  }
  
  if (!confirm(`Yakin ingin generate untuk ${selectedEmployeeIds.value.length} karyawan?`)) return
  try {
    const res = await api.post(`/payroll/generate`, { 
      payroll_period_id: selectedPeriodId.value,
      employee_ids: selectedEmployeeIds.value
    })
    alert(res.data.message || 'Berhasil')
    showGenerateModal.value = false
    if (crudPageRef.value) {
      crudPageRef.value.load()
    }
  } catch (e) { 
    alert(e.response?.data?.message || 'Gagal melakukan action')
  }
}

const cancelPeriod = async (row) => {
  if (!confirm(`Yakin ingin membatalkan & menghapus (soft delete) periode "${row.name}"? Semua slip gaji pada periode ini juga akan dibatalkan.`)) return
  try {
    const res = await api.post(`/payroll/periods/${row.id}/cancel`)
    alert(res.data.message || 'Periode berhasil dibatalkan (soft delete)')
    if (crudPageRef.value) {
      crudPageRef.value.load()
    }
  } catch (e) {
    alert(e.response?.data?.message || 'Gagal membatalkan periode')
  }
}

const isDailyCycle = computed(() => route.query.cycle === 'daily')

const filters = computed(() => {
  const f = [
    { key: 'company_id', label: 'Filter Perusahaan', relationEndpoint: '/companies' },
    { key: 'branch_id', label: 'Filter Cabang / Area', relationEndpoint: '/branches' }
  ]
  if (route.query.cycle === 'monthly') {
    f.push({ key: 'period', label: 'Filter Periode (YYYY-MM)' })
  } else if (isDailyCycle.value) {
    f.push({ key: 'date', label: 'Filter Tanggal', type: 'date' })
  }
  return f
})

const columns = computed(() => [
  { key: 'company', sub: 'name', label: 'Perusahaan' },
  { key: 'branch', sub: 'name', label: 'Cabang / Area' },
  { key: 'period', label: 'Periode' },
  { key: 'name', label: 'Nama' },
  { 
    key: 'cycle_type', 
    label: 'Kategori / Tipe', 
    format: (v) => {
      if (v === 'daily') return 'Gaji Harian'
      return 'Gaji Bulanan'
    } 
  },
  { key: 'start_date', label: isDailyCycle.value ? 'Tanggal' : 'Mulai' },
  { key: 'end_date', label: 'Selesai', hidden: isDailyCycle.value },
  { key: 'status', label: 'Status' },
])

const formFields = computed(() => {
  const cycleOptions = [
    { value: 'monthly', label: 'Gaji Bulanan' },
    { value: 'daily', label: 'Gaji Harian' }
  ]

  return [
    { key: 'company_id', label: 'Perusahaan', type: 'relation', relationEndpoint: '/companies', required: true },
    { key: 'branch_id', label: 'Cabang / Area', type: 'relation', relationEndpoint: '/branches', required: true },
    { 
      key: 'cycle_type', 
      label: 'Skema Gaji', 
      type: 'select', 
      required: true, 
      disabled: !!route.query.cycle, 
      options: cycleOptions 
    },
    { 
      key: 'period', 
      label: 'Periode Bulan (YYYY-MM)', 
      showIf: form => form.cycle_type === 'monthly'
    },
    { 
      key: 'start_date', 
      label: 'Tanggal', 
      type: 'date', 
      showIf: form => form.cycle_type === 'daily'
    },
  ]
})

const getFridayToThursday = (d = new Date()) => {
  const day = d.getDay()
  const diffToFriday = (day >= 5) ? (5 - day) : (5 - day - 7)
  const friday = new Date(d)
  friday.setDate(d.getDate() + diffToFriday)
  
  const thursday = new Date(friday)
  thursday.setDate(friday.getDate() + 6)

  const toYmd = (date) => `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`
  return {
    start: toYmd(friday),
    end: toYmd(thursday),
    period: `${friday.getFullYear()}-${String(friday.getMonth() + 1).padStart(2, '0')}`
  }
}

const initialForm = () => {
  const d = new Date()
  const todayStr = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
  const defaultPeriod = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`
  const weeklyDates = getFridayToThursday(d)
  const isWeekly = route.query.cycle === 'weekly_payment'

  return {
    company_id: '',
    branch_id: '',
    cycle_type: route.query.cycle === 'monthly' ? 'monthly' : (route.query.cycle || 'monthly'),
    period: isWeekly ? weeklyDates.period : defaultPeriod,
    start_date: isWeekly ? weeklyDates.start : todayStr,
    end_date: isWeekly ? weeklyDates.end : todayStr
  }
}

const mapDataToForm = (row) => ({
  company_id: row.company_id || '',
  branch_id: row.branch_id || '',
  cycle_type: row.cycle_type,
  period: row.period,
  start_date: row.start_date,
  end_date: row.end_date,
  name: row.name,
})

let lastBranchId = null
const onFormChange = async (form) => {
  if (form.branch_id && form.branch_id !== lastBranchId && !form.company_id) {
    try {
      const res = await api.get(`/branches/${form.branch_id}`)
      if (res.data?.data?.company_id && !form.company_id) {
        form.company_id = res.data.data.company_id
      }
    } catch (e) {
      console.error('Failed to auto-select company from branch', e)
    }
  }
  lastBranchId = form.branch_id
}

const transformPayload = (form) => {
  const payload = { ...form }
  if (payload.cycle_type === 'daily') {
    payload.end_date = payload.start_date
    payload.period = payload.start_date ? payload.start_date.substring(0, 7) : ''
  } else {
    payload.cycle_type = 'monthly'
    if (payload.period) {
      const [y, m] = payload.period.split('-').map(Number)
      const lastDay = new Date(y, m, 0).getDate()
      payload.start_date = `${payload.period}-01`
      payload.end_date = `${payload.period}-${String(lastDay).padStart(2, '0')}`
    }
  }
  return payload
}

const act = async (id, action) => {
  if (!confirm(`Yakin ingin melakukan action ${action} pada periode ini?`)) return
  try {
    let res = await api.post(`/payroll/periods/${id}/${action}`)
    alert(res.data.message || 'Berhasil')
    if (crudPageRef.value) {
      crudPageRef.value.load()
    }
  } catch (e) { 
    alert(e.response?.data?.message || 'Gagal melakukan action')
  }
}

const viewRuns = (p) => {
  router.push({ path: '/hrms/payslips', query: { period: p.id } })
}

const exportingId = ref(null)
const exportReport = async (p) => {
  if (exportingId.value) return
  exportingId.value = p.id
  try {
    const res = await api.get(`/payroll/periods/${p.id}/export-excel`, { responseType: 'blob' })
    const filename = `Laporan_Gaji_Mekanik_${p.name ? p.name.replace(/\s+/g, '_') : p.id}.xlsx`
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
    alert('Gagal mengunduh file Excel.')
  } finally {
    exportingId.value = null
  }
}
</script>
