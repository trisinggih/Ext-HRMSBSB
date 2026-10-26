<template>
  <div>
    <CrudPage
      title="Manajemen Saldo Cuti Karyawan"
      singular="Saldo Cuti"
      endpoint="/leave-balances"
      :columns="columns"
      :form-fields="formFields"
      :filters="filters"
      :initial-form="initialForm"
      :map-data-to-form="mapDataToForm"
    />
  </div>
</template>

<script setup>
import { ref } from 'vue'
import CrudPage from '../components/CrudPage.vue'

const currentYear = new Date().getFullYear()

const columns = [
  { key: 'employee', label: 'Karyawan', sub: 'name' },
  { key: 'leave_type', label: 'Tipe Cuti', sub: 'name' },
  { key: 'year', label: 'Tahun' },
  { key: 'quota', label: 'Kuota', format: v => `${parseInt(v || 0)} hari` },
  { key: 'carried_forward', label: 'Carry Fwd', format: v => `${parseInt(v || 0)} hari` },
  { key: 'used', label: 'Terpakai', format: v => `${parseInt(v || 0)} hari` },
  { key: 'expired', label: 'Hangus', format: v => `${parseInt(v || 0)} hari` },
  { key: 'remaining', label: 'Sisa', format: v => `${parseInt(v || 0)} hari` },
]

const formFields = [
  { key: 'employee_id', label: 'Karyawan', type: 'relation', relationEndpoint: '/employees', required: true },
  { key: 'leave_type_id', label: 'Tipe Cuti', type: 'relation', relationEndpoint: '/leave-types', required: true },
  { key: 'year', label: 'Tahun', type: 'number', required: true },
  { key: 'quota', label: 'Kuota (Hari)', type: 'number', required: true },
  { key: 'carried_forward', label: 'Carry Forward (Hari)', type: 'number' },
  { key: 'used', label: 'Terpakai (Hari)', type: 'number' },
  { key: 'expired', label: 'Hangus (Hari)', type: 'number' },
]

const filters = ref([
  { key: 'employee_id', label: 'Karyawan', type: 'relation', relationEndpoint: '/employees' },
  { key: 'leave_type_id', label: 'Tipe Cuti', type: 'relation', relationEndpoint: '/leave-types' },
  { key: 'year', label: 'Tahun', options: [
    { value: currentYear - 1, label: (currentYear - 1).toString() },
    { value: currentYear, label: currentYear.toString() },
    { value: currentYear + 1, label: (currentYear + 1).toString() },
  ]}
])

const initialForm = () => ({
  employee_id: '',
  leave_type_id: '',
  year: currentYear,
  quota: 12,
  carried_forward: 0,
  used: 0,
  expired: 0,
})

const mapDataToForm = (row) => ({
  employee_id: row.employee_id,
  leave_type_id: row.leave_type_id,
  year: row.year,
  quota: row.quota,
  carried_forward: row.carried_forward,
  used: row.used,
  expired: row.expired,
})
</script>
