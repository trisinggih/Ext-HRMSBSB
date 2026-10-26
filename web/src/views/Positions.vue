<template>
  <CrudPage
    ref="crudPageRef"
    title="Jabatan (Position)"
    singular="Jabatan"
    endpoint="/positions"
          :columns="[
        { key: 'code', label: 'Kode' },
        { key: 'name', label: 'Nama Jabatan' },
        { key: 'level', label: 'Level' },
        { key: 'department', label: 'Departemen', sub: 'name' },
        { key: 'division', label: 'Divisi', sub: 'name' },
      ]"
    :form-fields="formFields"
          :filters="[
        { key: 'department_id', label: 'Filter Departemen', relationEndpoint: '/departments' },
        { key: 'division_id', label: 'Filter Divisi', relationEndpoint: '/divisions' },
        { key: 'is_active', label: 'Status', options: [{ value: 1, label: 'Aktif' }, { value: 0, label: 'Nonaktif' }] }
      ]"
    :transform-payload="transformPayload"
  >
    <template #actions="{ row, openEdit, remove, refresh }">
      <template v-if="!row.is_department_manager">
        <button @click="openEdit(row)" class="btn-action-edit" title="Edit Jabatan">
          <i class="mdi mdi-pencil-outline"></i> Edit
        </button>
        <button v-if="auth.isSuperAdmin" @click="openSalaryModal(row)" class="btn-action-success" title="Komponen Salary">
          <i class="mdi mdi-cash-multiple"></i> Salary
        </button>
        <button @click="remove(row)" class="btn-action-delete" title="Hapus Jabatan">
          <i class="mdi mdi-trash-can-outline"></i> Hapus
        </button>
      </template>
      <span v-else class="text-muted fst-italic small">Otomatis (Manager)</span>
    </template>
    
    <template #form="{ form, formFields, saving }">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label fw-semibold small mb-1">Departemen</label>
          <Multiselect v-model="form.department_id" :options="async (q) => fetchOptions('/departments', q)" @change="handleDepartmentChange(form)" searchable class="custom-multiselect" placeholder="Pilih Departemen" />
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold small mb-1">Divisi</label>
          <Multiselect :key="'div-' + (form.department_id || 'all')" v-model="form.division_id" :options="async (q) => fetchOptions('/divisions', q, form.department_id ? { department_id: form.department_id } : {})" searchable class="custom-multiselect" placeholder="Pilih Divisi" />
        </div>
        
        <div class="col-md-6">
          <label class="form-label fw-semibold small mb-1">Kode <span class="text-danger">*</span></label>
          <div class="input-group">
            <span class="input-group-text bg-light border-0 border-end text-muted fw-medium px-3" v-if="getPrefix(form)">{{ getPrefix(form) }}-</span>
            <input :value="getDisplayCode(form)" @input="e => form.code = e.target.value" type="text" class="form-control bg-light border-0 px-3 py-2 shadow-none" :class="getPrefix(form) ? 'rounded-end-3' : 'rounded-3'" placeholder="Masukkan kode" required />
          </div>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold small mb-1">Nama <span class="text-danger">*</span></label>
          <input v-model="form.name" type="text" class="form-control bg-light border-0 px-3 py-2 rounded-3" required placeholder="Masukkan nama" />
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold small mb-1">Level</label>
          <select v-model="form.level" class="form-select bg-light border-0 px-3 py-2 rounded-3 shadow-none">
            <option value="" disabled selected>Pilih Level Jabatan</option>
            <option value="Staff">Staff</option>
            <option value="Coordinator">Coordinator</option>
            <option value="Supervisor">Supervisor</option>
            <option value="Manager">Manager</option>
            <option value="General Manager">General Manager</option>
            <option value="Director">Director</option>
          </select>
        </div>

      </div>
    </template>
  </CrudPage>

  <!-- Komponen Salary Modal -->
  <div class="modal fade" id="salaryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Komponen Gaji Bawaan (Default)</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <h6 class="fw-bold">{{ selectedPositionForSalary?.name }} <span class="text-muted">({{ selectedPositionForSalary?.code }})</span></h6>
          </div>
          <div v-if="loadingPositionSalary" class="text-center py-4">
            <span class="spinner-border spinner-border-sm text-primary me-2"></span>
            <span class="text-muted small">Memuat data komponen gaji jabatan...</span>
          </div>
          <div v-else-if="selectedPositionForSalary && payrollComponentsList.length > 0">
            <div class="table-responsive">
              <table class="table table-sm table-bordered align-middle">
                <thead class="table-light">
                  <tr>
                    <th style="width: 40px;" class="text-center">#</th>
                    <th>Nama Komponen</th>
                    <th style="width: 250px;">Nominal (Jika Fixed Amount)</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="comp in payrollComponentsList" :key="comp.id">
                    <td class="text-center">
                      <input class="form-check-input" type="checkbox" :id="'comp-'+comp.id" 
                             :checked="isComponentSelected(selectedPositionForSalary, comp.id)"
                             @change="toggleComponent(selectedPositionForSalary, comp, $event.target.checked)">
                    </td>
                    <td>
                      <label class="form-check-label w-100 mb-0" :for="'comp-'+comp.id" style="cursor: pointer;">
                        {{ comp.name }}
                      </label>
                    </td>
                    <td>
                      <div v-if="isComponentSelected(selectedPositionForSalary, comp.id) && comp.calculation_method === 'FIXED_AMOUNT'">
                        <input type="text" class="form-control form-control-sm" 
                               placeholder="Nominal"
                               :value="formatInputNominal(getComponentAmount(selectedPositionForSalary, comp.id))"
                               @input="updateComponentAmount(selectedPositionForSalary, comp.id, $event.target.value)">
                      </div>
                      <span v-else-if="isComponentSelected(selectedPositionForSalary, comp.id)" class="text-muted small fst-italic">Bukan tipe Fixed Amount</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          <div v-else>
            <p class="text-muted small mb-0">Memuat komponen gaji...</p>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="button" class="btn btn-primary" @click="saveSalaryComponents" :disabled="savingSalary">
            <span v-if="savingSalary" class="spinner-border spinner-border-sm me-1"></span>
            Simpan Komponen
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import CrudPage from '../components/CrudPage.vue'
import { ref, onMounted } from 'vue'
import Multiselect from '@vueform/multiselect'
import '@vueform/multiselect/themes/default.css'
import api from '../api'
import Swal from 'sweetalert2'
import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()
const crudPageRef = ref(null)
const payrollComponentsList = ref([])
const selectedPositionForSalary = ref(null)
const savingSalary = ref(false)
const loadingPositionSalary = ref(false)
let salaryModalInstance = null

const openSalaryModal = async (row) => {
  // Set initial selected position with loading
  selectedPositionForSalary.value = JSON.parse(JSON.stringify(row))
  if (!selectedPositionForSalary.value.payroll_components) {
    selectedPositionForSalary.value.payroll_components = []
  }
  
  const { Modal } = window.bootstrap
  salaryModalInstance = new Modal(document.getElementById('salaryModal'))
  salaryModalInstance.show()

  // Fetch the latest detailed position with payrollComponents directly from API
  loadingPositionSalary.value = true
  try {
    const { data } = await api.get(`/positions/${row.id}`)
    const fullPosition = data.data
    if (fullPosition && fullPosition.payroll_components) {
      selectedPositionForSalary.value = {
        ...fullPosition,
        payroll_components: fullPosition.payroll_components.map(pc => ({
          id: pc.id,
          payroll_component_id: pc.payroll_component_id,
          fixed_amount: parseFloat(pc.fixed_amount) || 0
        }))
      }
    }
  } catch (err) {
    console.error('Gagal mengambil data komponen gaji posisi:', err)
  } finally {
    loadingPositionSalary.value = false
  }
}

const saveSalaryComponents = async () => {
  savingSalary.value = true
  try {
    const payload = selectedPositionForSalary.value
    await api.put(`/positions/${payload.id}`, payload)
    Swal.fire({
      title: 'Berhasil!',
      text: 'Komponen salary berhasil disimpan.',
      icon: 'success',
      timer: 1500,
      showConfirmButton: false
    })
    salaryModalInstance.hide()
    // Reload table data so rows have the latest payroll_components
    crudPageRef.value?.load()
  } catch (err) {
    console.error(err)
    Swal.fire({
      title: 'Gagal!',
      text: err.response?.data?.message || 'Terjadi kesalahan saat menyimpan',
      icon: 'error'
    })
  } finally {
    savingSalary.value = false
  }
}

const branches = ref({})
const departments = ref({})
const divisions = ref({})

onMounted(async () => {
  try {
    const { data } = await api.get('/payroll-components', { params: { is_active: 1, per_page: 100 } })
    payrollComponentsList.value = data.data?.data || data.data || []
    
    const [br, dp, dv] = await Promise.all([
      api.get('/branches?per_page=1000'),
      api.get('/departments?per_page=1000'),
      api.get('/divisions?per_page=1000')
    ])
    const brList = br.data?.data?.data || br.data?.data || []
    brList.forEach(x => branches.value[x.id] = x.code)
    const dpList = dp.data?.data?.data || dp.data?.data || []
    dpList.forEach(x => departments.value[x.id] = x.code)
    const dvList = dv.data?.data?.data || dv.data?.data || []
    dvList.forEach(x => divisions.value[x.id] = x.code)
  } catch (e) {
    console.error(e)
  }
})

const getPrefix = (form) => {
   if (form.division_id && divisions.value[form.division_id]) {
     return divisions.value[form.division_id]
   }
   if (form.department_id && departments.value[form.department_id]) {
     return departments.value[form.department_id]
   }
   if (form.branch_id && branches.value[form.branch_id]) {
     return branches.value[form.branch_id]
   }
   return ''
}
const getDisplayCode = (form) => {
  const prefix = getPrefix(form)
  if (prefix && form.code && form.code.startsWith(prefix + '-')) {
    return form.code.substring(prefix.length + 1)
  }
  return form.code || ''
}

const transformPayload = (payload) => {
  const prefix = getPrefix(payload)
  if (prefix && payload.code && !payload.code.startsWith(prefix + '-')) {
    payload.code = `${prefix}-${payload.code}`
  }
  return payload
}

const isComponentSelected = (form, compId) => {
  if (!form || !form.payroll_components) return false
  return form.payroll_components.some(c => c.payroll_component_id === compId)
}

const toggleComponent = (form, comp, isChecked) => {
  if (!form) return
  if (!form.payroll_components) form.payroll_components = []
  
  if (isChecked) {
    form.payroll_components.push({
      payroll_component_id: comp.id,
      fixed_amount: 0
    })
  } else {
    form.payroll_components = form.payroll_components.filter(c => c.payroll_component_id !== comp.id)
  }
}

const getComponentAmount = (form, compId) => {
  if (!form || !form.payroll_components) return ''
  const c = form.payroll_components.find(x => x.payroll_component_id === compId)
  if (!c) return ''
  return c.fixed_amount
}

const formatInputNominal = (val) => {
  if (val == null) return ''
  const str = String(val).replace(/\D/g, '')
  if (!str) return ''
  return parseInt(str, 10).toLocaleString('id-ID')
}

const updateComponentAmount = (form, compId, value) => {
  if (!form || !form.payroll_components) return
  const item = form.payroll_components.find(x => x.payroll_component_id === compId)
  if (item) {
    const rawVal = String(value).replace(/\D/g, '')
    item.fixed_amount = rawVal ? parseFloat(rawVal) : 0
  }
}

const formFields = [
  { key: 'department_id', label: 'Departemen' },
  { key: 'division_id', label: 'Divisi' },
  { key: 'name', label: 'Nama', required: true },
  { key: 'code', label: 'Kode', required: true },
  { key: 'level', label: 'Level' },
]

const fetchOptions = async (endpoint, query, filters = {}) => {
  try {
    const { data } = await api.get(endpoint, { params: { search: query, per_page: 50, ...filters } })
    return (data.data?.data || data.data || []).map(item => ({
      value: item.id,
      label: item.code ? `[${item.code}] ${item.name || item.title || item.id}` : (item.name || item.title || item.id)
    }))
  } catch (e) {
    return []
  }
}

const handleCompanyChange = (form) => {
  form.branch_id = null
  form.department_id = null
  form.division_id = null
}

const handleBranchChange = (form) => {
  form.department_id = null
  form.division_id = null
}

const handleDepartmentChange = (form) => {
  form.division_id = null
}

const handleDivisionChange = (form) => {
  // Empty, can be removed but keeping for consistency if needed
}
</script>

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
</style>

