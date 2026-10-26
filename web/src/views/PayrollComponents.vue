<template>
  <CrudPage
    title="Komponen Gaji (Payroll Components)"
    singular="Komponen"
    endpoint="/payroll-components"
    :columns="columns"
    :form-fields="formFields"
    :initial-form="initialForm"
    :map-data-to-form="mapDataToForm"
    :transform-payload="transformPayload"
  >
    <template #form="{ form, formFields }">
      <div class="row g-3">
        <div class="col-md-6" v-for="f in formFields" :key="f.key" v-show="f.showIf ? f.showIf(form) : true" :class="{ 'col-md-12': f.type === 'textarea' }">
          <label class="form-label fw-semibold text-secondary small mb-1">{{ f.label }} <span v-if="f.required" class="text-danger">*</span></label>
          
          <template v-if="f.key === 'formula'">
            <div class="d-flex justify-content-between mb-2">
              <span class="text-muted small">Anda dapat mengetik rumus atau memilih variabel.</span>
              <button type="button" class="btn btn-sm btn-outline-info" @click="openVariableModal(form)">
                <i class="mdi mdi-format-list-bulleted me-1"></i> Pilih Variabel
              </button>
            </div>
            <textarea v-model="form[f.key]" class="form-control bg-light border-0 px-3 py-2 shadow-none rounded-3" rows="3" :placeholder="'Masukkan ' + f.label.toLowerCase()" ref="formulaInput"></textarea>
            <div v-if="f.hint" class="form-text text-muted small mt-1" v-html="f.hint"></div>
          </template>

          <template v-else-if="f.type === 'select'">
            <select v-model="form[f.key]" class="form-select bg-light border-0 px-3 py-2 shadow-none rounded-3" :required="f.required">
              <option value="">-- Pilih --</option>
              <option v-for="opt in f.options" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
            </select>
            <div v-if="f.hint" class="form-text text-muted small mt-1" v-html="f.hint"></div>
          </template>

          <template v-else-if="f.type === 'textarea'">
            <textarea v-model="form[f.key]" class="form-control bg-light border-0 px-3 py-2 shadow-none rounded-3" rows="3" :placeholder="'Masukkan ' + f.label.toLowerCase()"></textarea>
            <div v-if="f.hint" class="form-text text-muted small mt-1" v-html="f.hint"></div>
          </template>
          <template v-else-if="f.type === 'checkboxes'">
            <div class="d-flex flex-wrap gap-3 mt-1">
              <div class="form-check" v-for="opt in f.options" :key="opt.value">
                <input class="form-check-input" type="checkbox" :id="f.key + '_' + opt.value" :value="opt.value" v-model="form[f.key]" />
                <label class="form-check-label small" :for="f.key + '_' + opt.value">
                  {{ opt.label }}
                </label>
              </div>
            </div>
            <div v-if="f.hint" class="form-text text-muted small mt-1" v-html="f.hint"></div>
          </template>
          
          <template v-else>
            <input v-model="form[f.key]" :type="f.type || 'text'" :required="f.required" class="form-control bg-light border-0 px-3 py-2 shadow-none rounded-3" :placeholder="'Masukkan ' + f.label.toLowerCase()" />
            <div v-if="f.hint" class="form-text text-muted small mt-1" v-html="f.hint"></div>
          </template>
        </div>
      </div>
    </template>
    
    <!-- Custom Rendering untuk Kolom Formula di Tabel -->
    <template #cell-formula="{ value, row }">
      <div v-if="value && row.calculation_method !== 'FIXED_AMOUNT'">
        <div style="max-width: 320px; flex-wrap: wrap; display: flex; gap: 4px; align-items: center; margin-bottom: 8px; font-family: monospace; font-size: 0.82rem;">
          <template v-for="(token, idx) in formatFormulaTokens(value)" :key="idx">
            <span v-if="token.type === 'system'" class="badge bg-primary-subtle text-primary border border-primary-subtle">
              {{ token.text }}
            </span>
            <span v-else-if="token.type === 'component'" class="badge bg-success-subtle text-success border border-success-subtle">
              {{ token.text }}
            </span>
            <span v-else class="text-muted fw-medium">{{ token.text }}</span>
          </template>
        </div>
        
        <!-- Informasi Perhitungan -->
        <div v-if="formatFormulaTokens(value).some(t => t.type === 'system' || t.type === 'component')" class="mt-2 p-2 bg-light rounded border border-light" style="font-size: 0.75rem;">
          <div class="fw-bold text-dark mb-1">Informasi Perhitungan:</div>
          
          <div v-if="formatFormulaTokens(value).filter(t => t.type === 'system').length > 0" class="mb-1">
            <span class="text-muted d-block">Variabel Dipakai:</span>
            <div class="d-flex flex-wrap gap-1 mt-1">
              <span v-for="(t, i) in [...new Set(formatFormulaTokens(value).filter(t => t.type === 'system').map(t => t.text))]" :key="'sys'+i" class="badge bg-primary-subtle text-primary border border-primary-subtle">
                {{ t }}
              </span>
            </div>
          </div>
          
          <div v-if="formatFormulaTokens(value).filter(t => t.type === 'component').length > 0">
            <span class="text-muted d-block mt-1">Komponen Dipakai:</span>
            <div class="d-flex flex-wrap gap-1 mt-1">
              <span v-for="(t, i) in [...new Set(formatFormulaTokens(value).filter(t => t.type === 'component').map(t => t.text))]" :key="'comp'+i" class="badge bg-success-subtle text-success border border-success-subtle">
                {{ t }}
              </span>
            </div>
          </div>
        </div>
      </div>
      <span v-else class="text-muted">-</span>
    </template>
  </CrudPage>

  <!-- Modal Pilih Variabel -->
  <div v-if="showVarModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5); z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content border-0 rounded-4 shadow-lg">
        <div class="modal-header border-0 pt-4 px-4 pb-0">
          <h5 class="modal-title fw-bold">Variabel Dinamis Penggajian</h5>
          <button type="button" class="btn-close shadow-none" @click="showVarModal = false"></button>
        </div>
        <div class="modal-body px-4 py-4">
          <p class="text-muted mb-3">Klik pada variabel untuk memasukkannya ke dalam rumus.</p>
          <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th>Kode Variabel</th>
                  <th>Nama</th>
                  <th>Deskripsi</th>
                  <th class="text-end">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="loadingVars">
                  <td colspan="4" class="text-center py-3">Memuat variabel...</td>
                </tr>
                <tr v-for="v in variables" :key="v.code" style="cursor: pointer;" @click="insertVariable(v.code)">
                  <td><code class="fs-6 text-primary">{{ v.code }}</code></td>
                  <td class="fw-medium">{{ v.name }}</td>
                  <td class="text-muted small">{{ v.description }}</td>
                  <td class="text-end">
                    <button type="button" class="btn btn-sm btn-light text-primary">Pilih</button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import CrudPage from '../components/CrudPage.vue'
import api from '../api'

const showVarModal = ref(false)
const variables = ref([])
const loadingVars = ref(false)
const currentForm = ref(null)

const openVariableModal = async (formRef) => {
  currentForm.value = formRef
  showVarModal.value = true
  if (variables.value.length === 0) {
    loadingVars.value = true
    try {
      const { data } = await api.get('/payroll-rules/variables')
      variables.value = data.data
    } catch (e) {
      console.error(e)
    } finally {
      loadingVars.value = false
    }
  }
}

const insertVariable = (code) => {
  if (currentForm.value) {
    currentForm.value.formula = (currentForm.value.formula || '') + code
  }
  showVarModal.value = false
}

const formatFormulaTokens = (formula) => {
  if (!formula) return []
  // Regex memisahkan teks biasa dan variabel di dalam { }
  const regex = /(\{[^}]+\})/g
  const parts = formula.split(regex)
  return parts.filter(p => p).map(p => {
    if (p.startsWith('{') && p.endsWith('}')) {
      const code = p.slice(1, -1)
      // Cek apakah kode ada di variabel yang ada di modal (bisa dianggap component jika tak ada di list system, tp di sini kita hardcode list system dasar)
      const systemVars = ['basic_salary', 'total_working_days', 'total_attendance_days', 'total_working_hour_per_day', 'total_day_off', 'total_days_in_month', 'amount', 'total_overtime_minutes', 'total_overtime_hours', 'total_late_minutes', 'total_late_occurrences', 'total_unpaid_leave_days', 'total_allowance', 'taxable_earnings', 'bpjs_basis_total', 'tax_rate', 'tax_deduction', 'is_probation', 'status_percobaan']
      if (systemVars.includes(code)) {
        return { type: 'system', text: p }
      }
      return { type: 'component', text: p }
    }
    return { type: 'text', text: p }
  })
}



const columns = [
  { key: 'code', label: 'Kode' },
  { key: 'name', label: 'Nama' },
  { key: 'type', label: 'Tipe' },{ key: 'category', label: 'Siklus', format: (v) => v === 'monthly' ? 'Bulanan' : v === 'daily' ? 'Harian' : '-' },
  { key: 'payment_schema', label: 'Skema', format: (v) => {
      const schemas = {
          'MONTHLY_PAID_MONTHLY': 'Gaji Bulanan',
          'DAILY_PAID_DAILY': 'Gaji Harian'
      };
      if (typeof v === 'string') { try { v = JSON.parse(v); } catch(e) {} }
        if (Array.isArray(v)) { return v.map(s => schemas[s] || s).join(', ') || '-'; }
        return schemas[v] || '-';
  }},
  { key: 'formula', label: 'Formula' },
  { key: 'is_taxable', label: 'Kena Pajak', format: (v) => (v == 1 || v === true || v === 'true' || v === '1') ? '✅ Ya' : '➖' },
  { key: 'is_active', label: 'Aktif', format: (v) => (v == 1 || v === true || v === 'true' || v === '1') ? '✅ Ya' : '➖' },
]

const formFields = [
  { key: 'code', label: 'Kode', required: true },
  { key: 'name', label: 'Nama', required: true },
  { key: 'description', label: 'Deskripsi', type: 'textarea' },
  { key: 'type', label: 'Tipe', type: 'select', required: true, options: [
    { value: 'earning', label: 'Penghasilan (Earning)' },
    { value: 'deduction', label: 'Potongan (Deduction)' },
    { value: 'tax', label: 'Pajak (Tax)' },
    { value: 'bpjs', label: 'BPJS' },
  ]},{ key: 'category', label: 'Siklus', type: 'select', required: true, options: [
      { value: 'monthly', label: 'Bulanan' },
      { value: 'daily', label: 'Harian' }
    ]},
    { key: 'payment_schema', label: 'Skema Pembayaran', type: 'checkboxes', required: false, options: [
      { value: 'MONTHLY_PAID_MONTHLY', label: 'Gaji Bulanan' },
      { value: 'DAILY_PAID_DAILY', label: 'Gaji Harian' }
    ]},
  { key: 'has_rules', label: 'Gunakan Formula / Rules?', type: 'select', options: [
    { value: true, label: 'Ya, Gunakan Formula' },
    { value: false, label: 'Tidak (Statis)' },
  ]},
  { key: 'formula', label: 'Formula / Rumus', type: 'textarea', showIf: form => (form.has_rules == 1 || form.has_rules === true || form.calculation_method === 'FORMULA' || form.calculation_source === 'FORMULA') && form.calculation_method !== 'FIXED_AMOUNT' },
  { key: 'calculation_source', label: 'Calculation Source', type: 'select', required: true, options: [
    { value: 'FIXED', label: 'Fixed' },
    { value: 'FORMULA', label: 'Formula' },
    { value: 'ATTENDANCE', label: 'Attendance' },
    { value: 'OVERTIME', label: 'Overtime' },
    { value: 'SALES', label: 'Sales' },
    { value: 'LOAN', label: 'Loan' },
    { value: 'TAX', label: 'Tax' },
    { value: 'MANUAL', label: 'Manual Input' },
  ]},
  { key: 'calculation_method', label: 'Calculation Method', type: 'select', required: true, options: [
    { value: 'FIXED_AMOUNT', label: 'Fixed Amount' },
    { value: 'PERCENTAGE', label: 'Percentage' },
    { value: 'PER_DAY', label: 'Per Day' },
    { value: 'PER_HOUR', label: 'Per Hour' },
    { value: 'PER_OCCURRENCE', label: 'Per Occurrence' },
    { value: 'TIERED', label: 'Tiered' },
    { value: 'FORMULA', label: 'Formula Evaluator' },
    { value: 'SCHEDULED_INSTALLMENT', label: 'Scheduled Installment' },
  ]},
  { key: 'default_value', label: 'Default Value', type: 'number' },
  { key: 'is_taxable', label: 'Kena Pajak', type: 'select', options: [
    { value: true, label: 'Ya' }, { value: false, label: 'Tidak' },
  ]},
  { key: 'is_bpjs_basis', label: 'Basis BPJS', type: 'select', options: [
    { value: true, label: 'Ya' }, { value: false, label: 'Tidak' },
  ]},
  { key: 'is_active', label: 'Aktif', type: 'select', options: [
    { value: true, label: 'Ya' }, { value: false, label: 'Tidak' },
  ]},
]

const initialForm = () => ({ type: 'earning', category: 'monthly', payment_schema: [], calculation_source: 'FIXED', calculation_method: 'FIXED_AMOUNT', is_taxable: true, is_bpjs_basis: false, is_active: true })

const mapDataToForm = (row) => {
  const data = { ...row }
  if (!data.payment_schema || !Array.isArray(data.payment_schema)) {
    try {
      data.payment_schema = typeof data.payment_schema === 'string' ? JSON.parse(data.payment_schema) : []
    } catch {
      data.payment_schema = []
    }
  }
  return data
}

const transformPayload = (form) => {
  const payload = { ...form }
  if (!payload.payment_schema || !Array.isArray(payload.payment_schema)) {
    payload.payment_schema = []
  }
  return payload
}
</script>



















