<template>
  <CrudPage
    title="Bonus & Insentif"
    singular="Bonus / Insentif"
    endpoint="/rewards"
    :columns="columns"
    :form-fields="formFields"
    :filters="filters"
    :initial-form="initialForm"
    :map-data-to-form="mapDataToForm"
  >
    <!-- Flag indicating whether it's manual or auto-generated -->
    <template #body-source="{ row }">
      <span v-if="row.description && row.description.includes('Auto-generated')" class="badge bg-success">
        <i class="mdi mdi-robot-outline me-1"></i>Otomatis (Rutin)
      </span>
      <span v-else class="badge bg-primary">
        <i class="mdi mdi-account-edit-outline me-1"></i>Insidental (Manual)
      </span>
    </template>
  </CrudPage>
</template>

<script setup>
import CrudPage from '../components/CrudPage.vue'

const formatPeriodLabel = (p) => {
  if (!p) return '-'
  const s = p.start_date ? p.start_date.slice(0, 10) : ''
  const e = p.end_date ? p.end_date.slice(0, 10) : ''
  const range = (s && e) ? ` (${s} s/d ${e})` : ''
  return `${p.name || ('Periode ' + p.period)}${range}`
}

const columns = [
  { key: 'employee_id', label: 'ID Karyawan' },
  { key: 'employee', label: 'Karyawan', sub: 'name' },
  { key: 'title', label: 'Judul Bonus / Insentif' },
  { 
    key: 'payroll_period', 
    label: 'Periode Payroll', 
    format: (val, row) => row.payroll_period?.name || (row.payroll_period_id ? `Periode #${row.payroll_period_id}` : '-')
  },
  { key: 'type', label: 'Kategori', format: val => val === 'bonus' ? 'Bonus Penjualan/Tambahan' : val === 'performance' ? 'Kinerja' : val === 'employee_of_month' ? 'Karyawan Terbaik' : val === 'other' ? 'Lainnya (Benefit)' : 'Apresiasi' },
  { key: 'amount', label: 'Nominal (Rp)', format: val => val > 0 ? Number(val).toLocaleString('id-ID') : '-' },
  { key: 'reward_date', label: 'Tgl Pemberian', format: val => val ? new Date(val).toLocaleDateString('id-ID', { timeZone: 'Asia/Jakarta' }) : '-' },
  { key: 'source', label: 'Tipe' }
]

const filters = [
  { 
    key: 'payroll_period_id', 
    label: 'Periode Payroll', 
    relationEndpoint: '/payroll/periods',
    optionLabel: formatPeriodLabel
  },
  { 
    key: 'employee_id', 
    label: 'Karyawan', 
    relationEndpoint: '/employees' 
  }
]

const formFields = [
  { key: 'employee_id', label: 'Pilih Karyawan', type: 'relation', relationEndpoint: '/employees', required: true },
  { 
    key: 'payroll_period_id', 
    label: 'Target Periode Payroll', 
    type: 'relation', 
    relationEndpoint: '/payroll/periods', 
    optionLabel: formatPeriodLabel,
    placeholder: 'Pilih Periode Payroll',
    hint: 'Pilih periode penggajian agar bonus ini otomatis masuk ke slip gaji periode tersebut.' 
  },
  { key: 'title', label: 'Judul / Nama Bonus', type: 'text', required: true, placeholder: 'Contoh: Bonus Penjualan Q3' },
  { key: 'type', label: 'Kategori', type: 'select', required: true, options: [{value: 'bonus', label: 'Bonus'}, {value: 'performance', label: 'Bonus Kinerja'}, {value: 'appreciation', label: 'Apresiasi / Reward'}, {value: 'employee_of_month', label: 'Karyawan Terbaik'}, {value: 'other', label: 'Lainnya'}] },
  { key: 'amount', label: 'Nominal (Rp)', type: 'currency', required: true },
  { key: 'reward_date', label: 'Tanggal Diberikan', type: 'date', required: true, hint: 'Tanggal pencatatan atau tanggal efektif pemberian bonus.' },
  { key: 'description', label: 'Keterangan Tambahan', type: 'textarea' }
]

const initialForm = () => ({ 
  employee_id: '',
  payroll_period_id: '',
  reward_date: new Date().toISOString().slice(0, 10), 
  type: 'bonus',
  title: '',
  amount: '',
  description: ''
})

const mapDataToForm = (row) => ({
  id: row.id,
  employee_id: row.employee_id,
  payroll_period_id: row.payroll_period_id || '',
  title: row.title,
  type: row.type,
  amount: row.amount,
  reward_date: row.reward_date ? row.reward_date.slice(0, 10) : '',
  description: row.description
})
</script>
