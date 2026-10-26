<template>
  <CrudPage
    title="Potongan Lain-Lain"
    singular="Potongan Lain-Lain"
    endpoint="/payroll-deductions"
    :columns="columns"
    :form-fields="formFields"
    :filters="filters"
    :initial-form="initialForm"
    :map-data-to-form="mapDataToForm"
  >
    <template #body-type="{ row }">
      <span v-if="row.type === 'kerusakan'" class="badge bg-danger">
        <i class="mdi mdi-tools me-1"></i>Kerusakan / Ganti Rugi
      </span>
      <span v-else-if="row.type === 'denda'" class="badge bg-warning text-dark">
        <i class="mdi mdi-alert-circle-outline me-1"></i>Denda / Pelanggaran
      </span>
      <span v-else-if="row.type === 'pinjaman'" class="badge bg-info text-dark">
        <i class="mdi mdi-cash-minus me-1"></i>Kasbon / Pinjaman
      </span>
      <span v-else-if="row.type === 'klaim'" class="badge bg-secondary">
        <i class="mdi mdi-file-document-edit-outline me-1"></i>Penyesuaian
      </span>
      <span v-else class="badge bg-dark">
        <i class="mdi mdi-minus-circle-outline me-1"></i>Lainnya
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

const deductionTypeLabels = {
  kerusakan: 'Ganti Rugi / Kerusakan Barang',
  denda: 'Denda / Pelanggaran',
  pinjaman: 'Kasbon / Pinjaman Lain',
  klaim: 'Klaim / Penyesuaian',
  other: 'Lainnya'
}

const columns = [
  { key: 'employee_id', label: 'ID Karyawan' },
  { key: 'employee', label: 'Karyawan', sub: 'name' },
  { key: 'title', label: 'Judul Potongan' },
  { 
    key: 'payroll_period', 
    label: 'Periode Payroll', 
    format: (val, row) => row.payroll_period?.name || (row.payroll_period_id ? `Periode #${row.payroll_period_id}` : '-')
  },
  { 
    key: 'type', 
    label: 'Kategori'
  },
  { 
    key: 'amount', 
    label: 'Nominal Potongan (Rp)', 
    format: val => val > 0 ? Number(val).toLocaleString('id-ID') : '-' 
  },
  { 
    key: 'deduction_date', 
    label: 'Tgl Pemotongan', 
    format: val => val ? new Date(val).toLocaleDateString('id-ID', { timeZone: 'Asia/Jakarta' }) : '-' 
  }
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
  },
  {
    key: 'type',
    label: 'Kategori',
    options: [
      { value: 'kerusakan', label: 'Ganti Rugi / Kerusakan Barang' },
      { value: 'denda', label: 'Denda / Pelanggaran' },
      { value: 'pinjaman', label: 'Kasbon / Pinjaman Lain' },
      { value: 'klaim', label: 'Klaim / Penyesuaian' },
      { value: 'other', label: 'Lainnya' }
    ]
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
    hint: 'Pilih periode penggajian agar potongan ini otomatis memotong di slip gaji periode tersebut.' 
  },
  { key: 'title', label: 'Judul / Nama Potongan', type: 'text', required: true, placeholder: 'Contoh: Ganti Rugi Barang Hilang' },
  { 
    key: 'type', 
    label: 'Kategori Potongan', 
    type: 'select', 
    required: true, 
    options: [
      { value: 'kerusakan', label: 'Ganti Rugi / Kerusakan Barang' },
      { value: 'denda', label: 'Denda / Pelanggaran' },
      { value: 'pinjaman', label: 'Kasbon / Pinjaman Lain' },
      { value: 'klaim', label: 'Klaim / Penyesuaian' },
      { value: 'other', label: 'Lainnya' }
    ] 
  },
  { key: 'amount', label: 'Nominal Potongan (Rp)', type: 'currency', required: true },
  { key: 'deduction_date', label: 'Tanggal Pemotongan', type: 'date', required: true, hint: 'Tanggal pencatatan atau tanggal efektif pemotongan.' },
  { key: 'description', label: 'Keterangan Tambahan / Alasan', type: 'textarea' }
]

const initialForm = () => ({ 
  employee_id: '',
  payroll_period_id: '',
  deduction_date: new Date().toISOString().slice(0, 10), 
  type: 'kerusakan',
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
  deduction_date: row.deduction_date ? row.deduction_date.slice(0, 10) : '',
  description: row.description
})
</script>
