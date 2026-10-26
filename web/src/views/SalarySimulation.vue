<template>
  <div class="container-fluid">
    <div class="row">
      <!-- Panel Kiri: Input -->
      <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 mb-4">
          <div class="card-body">
            <h5 class="fw-bold mb-3">Pilih Karyawan & Penempatan</h5>

            <!-- 1. Pilih Karyawan -->
            <div class="mb-3">
              <label class="form-label text-secondary small">Karyawan</label>
              <Multiselect
                v-model="selectedEmployeeId"
                :options="employeeOptions"
                searchable
                placeholder="Pilih Karyawan..."
                class="custom-multiselect"
                @change="onEmployeeSelect"
              />
            </div>

            <!-- 2. Pilih Penempatan -->
            <div v-if="assignments.length > 0" class="mb-3">
              <label class="form-label text-secondary small">Penempatan / Perusahaan</label>
              <div
                v-for="a in assignments"
                :key="a.id"
                class="assignment-card p-3 rounded-3 border mb-2 cursor-pointer"
                :class="selectedAssignmentId === a.id ? 'border-primary bg-primary bg-opacity-10' : 'border-light bg-light'"
                @click="selectAssignment(a)"
                style="cursor: pointer;"
              >
                <div class="d-flex justify-content-between align-items-start">
                  <div>
                    <div class="fw-semibold">{{ a.company_name }}</div>
                    <div class="small text-muted">
                      {{ a.position_name }} · {{ a.department_name }}
                      <span v-if="a.placement_company_name && a.placement_company_id !== a.company_id" class="badge bg-warning-subtle text-dark border ms-1 py-0" style="font-size: 0.65rem;">
                        Penempatan: {{ a.placement_company_name }}
                      </span>
                    </div>
                  </div>
                  <span v-if="a.is_primary" class="badge bg-primary ms-2" style="font-size: 0.65rem;">Utama</span>
                </div>
                <div class="mt-2">
                  <span
                    class="badge small"
                    :class="a.salary_cycle_type ? 'bg-success bg-opacity-75' : 'bg-secondary bg-opacity-50'"
                  >
                    <i class="mdi mdi-calendar-sync me-1"></i>{{ a.salary_cycle_label }}
                  </span>
                  <span class="badge bg-secondary bg-opacity-25 text-dark ms-1 small">
                    {{ a.components_count }} komponen
                  </span>
                </div>
              </div>
            </div>

            <div v-else-if="selectedEmployeeId && !loadingAssignments" class="alert alert-warning small py-2">
              Karyawan ini belum memiliki penempatan.
            </div>

            <!-- 3. Konfigurasi Kehadiran -->
            <div v-if="selectedAssignmentId">
              <hr />
              <!-- Info Skema -->
              <div class="alert alert-info py-2 small mb-3">
                <i class="mdi mdi-information-outline me-1"></i>
                Simulasi berdasarkan skema: <strong>{{ selectedAssignment?.salary_cycle_label }}</strong>
              </div>

              <h6 class="fw-bold mb-3">Konfigurasi Kehadiran</h6>
              <div class="row">
                <div class="col-6 mb-2">
                  <label class="form-label text-secondary small mb-1">Total Hari ({{ simParams.cycle_type === 'weekly_payment' ? '1 Minggu' : '1 Bulan' }})</label>
                  <input type="number" class="form-control form-control-sm" v-model.number="simParams.days_in_month" :min="simParams.cycle_type === 'weekly_payment' ? 1 : 28" :max="simParams.cycle_type === 'weekly_payment' ? 7 : 31">
                </div>
                <div class="col-6 mb-2">
                  
                  <label class="form-label text-secondary small mb-1">Jatah Libur (Dinamis)</label>
                  <input type="number" class="form-control form-control-sm" v-model.number="simParams.day_off" min="0" max="31">
                </div>
                <div class="col-6 mb-2">
                  <label class="form-label text-secondary small mb-1">Efektif Hari Kerja</label>
                  <input type="number" class="form-control form-control-sm bg-light" :value="effectiveWorkDays" readonly>
                </div>
                <div class="col-6 mb-2">
                  <label class="form-label text-secondary small mb-1">Total Absen (Unpaid)</label>
                  <input type="number" class="form-control form-control-sm" v-model.number="simParams.absent_days" min="0" :max="effectiveWorkDays">
                </div>
                <div class="col-6 mb-2">
                  <label class="form-label text-secondary small mb-1">Total Kehadiran</label>
                  <input type="number" class="form-control form-control-sm bg-light" :value="presentDays" readonly>
                </div>
                <div class="col-6 mb-2">
                  <label class="form-label text-secondary small mb-1">Total Telat (Menit)</label>
                  <input type="number" class="form-control form-control-sm" v-model.number="simParams.late_minutes" min="0">
                </div>
                <div class="col-12 mb-3">
                  <label class="form-label text-secondary small mb-1">Total Lembur (Jam)</label>
                  <input type="number" class="form-control form-control-sm" v-model.number="simParams.overtime_hours" min="0" step="0.5">
                </div>
              </div>
            </div>
            

            <button
              class="btn btn-primary w-100 mt-2"
              :disabled="!selectedAssignmentId || simulating"
              @click="simulate"
            >
              <span v-if="simulating" class="spinner-border spinner-border-sm me-1"></span>
              Simulasikan Gaji
            </button>
          </div>
        </div>
      </div>

      <!-- Panel Kanan: Hasil -->
      <div class="col-md-8">
        <div class="card border-0 shadow-sm rounded-4 h-100">
          <div class="card-body p-4">
            <h5 class="fw-bold mb-4">Hasil Simulasi</h5>

            <div v-if="simulationResult" class="simulation-result">

              <!-- INFO DASAR -->
              <div class="mb-4">
                <h6 class="fw-bold text-primary mb-3 border-bottom pb-2">Informasi Dasar</h6>
                <ul class="list-group list-group-flush">
                  <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                    <span>Perusahaan</span>
                    <span class="fw-semibold">{{ selectedAssignment?.company_name }}</span>
                  </li>
                  <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                    <span>Skema Pembayaran</span>
                    <span class="badge bg-primary">{{ selectedAssignment?.salary_cycle_label }}</span>
                  </li>

                  <!-- Untuk skema BULANAN: tampilkan Gaji Pokok -->
                  <li v-if="selectedAssignment?.salary_cycle_type === 'MONTHLY_PAID_MONTHLY'" class="list-group-item d-flex justify-content-between align-items-center px-0">
                    <span>Gaji Pokok</span>
                    <span class="fw-semibold">Rp {{ (Number(simulationResult.breakdown?.basic_salary) || 0).toLocaleString('id-ID') }}</span>
                  </li>

                  <!-- Untuk skema HARIAN: tampilkan ringkasan kehadiran -->
                  <template v-if="selectedAssignment?.salary_cycle_type !== 'MONTHLY_PAID_MONTHLY'">
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                      <span>Gaji Harian (dari Komponen)</span>
                      <span class="fw-semibold">Rp {{ (Number(simulationResult.variables?.gaji_harian) || 0).toLocaleString('id-ID') }}</span>
                    </li>
                    <li class="list-group-item px-0">
                      <div class="d-flex justify-content-between align-items-center">
                        <span>Ringkasan Kehadiran</span>
                        <span class="fw-semibold"></span>
                      </div>
                      <div class="row g-1 mt-1">
                        <div class="col-4">
                          <div class="bg-success bg-opacity-10 border border-success border-opacity-25 rounded p-2 text-center">
                            <div class="fw-bold text-success">{{ simulationResult.variables?.total_attendance_days ?? ((simParams.days_in_month - simParams.day_off) - simParams.absent_days) }}</div>
                            <div class="text-muted" style="font-size: 0.72em;">Hari Hadir</div>
                          </div>
                        </div>
                        <div class="col-4">
                          <div class="bg-warning bg-opacity-10 border border-warning border-opacity-25 rounded p-2 text-center">
                            <div class="fw-bold text-warning">{{ simulationResult.variables?.total_unpaid_leave_days ?? simParams.absent_days }}</div>
                            <div class="text-muted" style="font-size: 0.72em;">Hari Absen</div>
                          </div>
                        </div>
                        <div class="col-4">
                          <div class="bg-info bg-opacity-10 border border-info border-opacity-25 rounded p-2 text-center">
                            <div class="fw-bold text-info">{{ simulationResult.variables?.total_working_days ?? (simParams.days_in_month - simParams.day_off) }}</div>
                            <div class="text-muted" style="font-size: 0.72em;">Hari Kerja</div>
                          </div>
                        </div>
                      </div>
                    </li>
                  </template>

                  <li v-if="simulationResult.variables?.ptkp_status" class="list-group-item d-flex justify-content-between align-items-center px-0">
                    <span>Status PTKP & Kategori TER</span>
                    <span class="fw-semibold">
                      <span class="badge bg-secondary me-2">{{ simulationResult.variables.ptkp_status }}</span>
                      <span class="badge bg-primary">Kategori {{ simulationResult.variables.ter_category }}</span>
                    </span>
                  </li>
                </ul>
              </div>

              <div class="row">
                <!-- PENDAPATAN -->
                <div class="col-md-6 mb-4">
                  <h6 class="fw-bold text-success mb-3 border-bottom pb-2">Pendapatan (Earnings)</h6>
                  <ul class="list-group list-group-flush">
                    <li v-for="earn in simulationResult.earnings" :key="earn.id" class="list-group-item d-flex justify-content-between align-items-center px-0">
                      <span>{{ earn.name }}</span>
                      <span class="fw-semibold">Rp {{ (Number(earn.amount) || 0).toLocaleString('id-ID') }}</span>
                    </li>
                    <li v-if="!simulationResult.earnings?.length" class="list-group-item text-muted text-center px-0 small">Tidak ada pendapatan</li>
                  </ul>
                  <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                    <span class="fw-bold">Total Pendapatan</span>
                    <span class="fw-bold text-success">Rp {{ (Number(simulationResult.total_earnings) || 0).toLocaleString('id-ID') }}</span>
                  </div>
                </div>

                <!-- POTONGAN -->
                <div class="col-md-6 mb-4">
                  <h6 class="fw-bold text-danger mb-3 border-bottom pb-2">Potongan (Deductions)</h6>
                  <ul class="list-group list-group-flush">
                    <li v-for="deduct in simulationResult.deductions" :key="deduct.id" class="list-group-item px-0">
                      <div class="d-flex justify-content-between align-items-center">
                        <span>{{ deduct.name }}</span>
                        <span class="fw-semibold">Rp {{ (Number(deduct.amount) || 0).toLocaleString('id-ID') }}</span>
                      </div>
                      <div v-if="deduct.formula" class="text-muted small mt-1" style="font-size: 0.8em; font-family: monospace;">
                        {{ deduct.formula }}
                      </div>
                    </li>
                    <li v-if="!simulationResult.deductions?.length" class="list-group-item text-muted text-center px-0 small">
                      Tidak ada potongan
                    </li>
                  </ul>
                  <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                    <span class="fw-bold">Total Potongan</span>
                    <span class="fw-bold text-danger">Rp {{ (Number(simulationResult.total_deductions) || 0).toLocaleString('id-ID') }}</span>
                  </div>
                </div>
              </div>

              <!-- VARIABEL KALKULASI -->
              <div v-if="simulationResult.variables && Object.keys(simulationResult.variables).length > 0" class="mb-4 bg-white p-3 rounded border">
                <h6 class="fw-bold text-info mb-3 border-bottom pb-2"><i class="mdi mdi-information-outline me-1"></i>Variabel Kalkulasi</h6>
                <div class="row g-2">
                  <div class="col-6 col-md-4" v-for="(val, key) in simulationResult.variables" :key="key">
                    <div class="p-2 border rounded bg-light">
                      <div class="text-muted small fw-bold text-uppercase" style="font-size: 0.7em;">{{ key.replace(/_/g, ' ') }}</div>
                      <div class="fw-semibold" style="font-size: 0.9em;">
                        {{ typeof val === 'number' ? val.toLocaleString('id-ID', { maximumFractionDigits: 4 }) : !isNaN(Number(val)) && val !== '' && val !== null ? Number(val).toLocaleString('id-ID', { maximumFractionDigits: 4 }) : val }}
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- TAKE HOME PAY -->
              <div class="bg-light p-3 rounded mt-2 d-flex justify-content-between align-items-center border border-primary border-opacity-25">
                <h5 class="mb-0 fw-bold">Take Home Pay (THP)</h5>
                <h4 class="mb-0 fw-bold text-primary">Rp {{ (Number(simulationResult.take_home_pay) || 0).toLocaleString('id-ID') }}</h4>
              </div>
            </div>

            <div v-else-if="!simulating" class="text-center text-muted py-5">
              <i class="mdi mdi-calculator fs-1 mb-3 text-secondary opacity-50 d-block"></i>
              <p>Pilih karyawan dan penempatan, lalu klik "Simulasikan Gaji".</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, reactive, computed, watch } from 'vue'
import { toast } from 'vue3-toastify'
import api from '../api'
import Multiselect from '@vueform/multiselect'

const employeeOptions = ref([])
const selectedEmployeeId = ref(null)
const assignments = ref([])
const selectedAssignmentId = ref(null)
const loadingAssignments = ref(false)

const selectedAssignment = computed(() =>
  assignments.value.find(a => a.id === selectedAssignmentId.value)
)

const simParams = reactive({
  days_in_month: 7,
  day_off: 0,
  present_days: 7,
  absent_days: 0,
  late_minutes: 0,
  late_occurrences: 0,
  overtime_hours: 0,
  cycle_type: 'weekly_payment'
})

// Computed: Efektif hari kerja = Total Hari - Jatah Libur
const effectiveWorkDays = computed(() => Math.max(0, simParams.days_in_month - simParams.day_off))

// Computed: Total Kehadiran = Efektif Hari Kerja - Total Absen (Unpaid)
const presentDays = computed(() => Math.max(0, effectiveWorkDays.value - simParams.absent_days))

watch(() => simParams.cycle_type, (newVal) => {
  if (newVal === 'weekly_payment') {
    simParams.days_in_month = 7
    simParams.day_off = 0
    simParams.present_days = 7
    simParams.absent_days = 0
    simParams.late_minutes = 0
    simParams.overtime_hours = 0
  } else if (newVal === 'weekly_payslip') {
    simParams.days_in_month = 30
    simParams.day_off = 0
    simParams.present_days = 30
    simParams.absent_days = 0
    simParams.late_minutes = 0
    simParams.overtime_hours = 0
  } else if (newVal === 'daily_payment') {
    simParams.days_in_month = 1
    simParams.day_off = 0
    simParams.present_days = 1
    simParams.absent_days = 0
    simParams.late_minutes = 0
    simParams.overtime_hours = 0
  }
})

const simulating = ref(false)
const simulationResult = ref(null)

const loadEmployees = async () => {
  try {
    const { data } = await api.get('/employees?per_page=1000&for_payroll=1')
    if (data.success) {
      const employees = data.data.data || data.data
      employeeOptions.value = employees.map(emp => ({
        value: emp.id,
        label: `${emp.name} (${emp.employee_id ?? emp.nip ?? '-'})`
      }))
    }
  } catch (error) {
    console.error('Failed to load employees:', error)
  }
}

const onEmployeeSelect = async (empId) => {
  simulationResult.value = null
  assignments.value = []
  selectedAssignmentId.value = null

  if (!empId) return

  loadingAssignments.value = true
  try {
    const { data } = await api.get(`/employees/${empId}/assignments`)
    if (data.success) {
      assignments.value = data.data
      // Auto-select primary
      const primary = data.data.find(a => a.is_primary) ?? data.data[0]
      if (primary) selectAssignment(primary)
    }
  } catch (e) {
    toast.error('Gagal memuat penempatan karyawan')
  } finally {
    loadingAssignments.value = false
  }
}

const selectAssignment = (assignment) => {
  selectedAssignmentId.value = assignment.id
  simulationResult.value = null
  
  if (assignment.salary_cycle_type === 'DAILY_PAID_DAILY' || assignment.salary_cycle_type === 'daily') {
    simParams.cycle_type = 'daily_payment'
    simParams.days_in_month = 1
    simParams.day_off = 0
    simParams.present_days = 1
  } else {
    simParams.cycle_type = 'monthly_payment'
    simParams.days_in_month = 30
    simParams.day_off = 4
    simParams.present_days = 26
  }
  simParams.absent_days = 0
  simParams.late_minutes = 0
  simParams.overtime_hours = 0
}

const simulate = async () => {
  if (!selectedEmployeeId.value || !selectedAssignmentId.value) return

  simulating.value = true
  simulationResult.value = null
  try {
    const workingDays = effectiveWorkDays.value

    const { data } = await api.post(`/payroll/simulate/${selectedEmployeeId.value}`, {
      ...simParams,
      working_days: workingDays,
      present_days: presentDays.value,
      assignment_id: selectedAssignmentId.value
    })
    if (data.success) {
      const rawResult = data.data

      const earningsList = (rawResult._details || []).filter(d => d.type === 'earning')
      const deductionsList = (rawResult._details || []).filter(d => d.type === 'deduction' || d.type === 'tax' || d.type === 'bpjs')

      const totalEarnings = earningsList.reduce((sum, item) => sum + (Number(item.amount) || 0), 0)
      const totalDeductions = deductionsList.reduce((sum, item) => sum + (Number(item.amount) || 0), 0)

      simulationResult.value = {
        ...rawResult,
        earnings: earningsList.map((e, idx) => ({ id: e.payroll_component_id || `e${idx}`, name: e.label, amount: e.amount, formula: e.formula })),
        deductions: deductionsList.map((d, idx) => ({ id: d.payroll_component_id || `d${idx}`, name: d.label, amount: d.amount, formula: d.formula })),
        total_earnings: totalEarnings,
        total_deductions: totalDeductions,
        variables: rawResult.breakdown?.variables || {},
        take_home_pay: rawResult.net_salary
      }
      toast.success('Simulasi berhasil dijalankan')
    } else {
      toast.error('Gagal menjalankan simulasi')
    }
  } catch (error) {
    toast.error('Gagal simulasi: ' + (error.response?.data?.message || error.message))
  } finally {
    simulating.value = false
  }
}

onMounted(() => {
  loadEmployees()
})
</script>

<style src="@vueform/multiselect/themes/default.css"></style>
<style scoped>
.custom-multiselect {
  --ms-bg: #f8f9fa;
  --ms-border-color: transparent;
  --ms-radius: 0.5rem;
  --ms-font-size: 1rem;
  --ms-py: 0.5rem;
  --ms-px: 1rem;
  --ms-ring-width: 0px;
  --ms-placeholder-color: #6c757d;
}
.assignment-card {
  transition: border-color 0.2s, background-color 0.2s;
}
.assignment-card:hover {
  border-color: #0d6efd !important;
}
</style>
