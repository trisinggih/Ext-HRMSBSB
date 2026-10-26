<template>
  <CrudPage
    title="Input Gaji Manual (Payroll Inputs)"
    singular="Input Gaji"
    endpoint="/payroll-inputs"
    :columns="columns"
    :form-fields="formFields"
    :initial-form="initialForm"
  />
</template>

<script setup>
import { ref, onMounted } from 'vue'
import CrudPage from '../components/CrudPage.vue'
import api from '../api'

const columns = [
  { key: 'employee', sub: 'name', label: 'Karyawan', format: (_, row) => row.employee ? `${row.employee.name || ''} (${row.employee.employee_id || ''})` : '-' },
  { key: 'component', sub: 'name', label: 'Komponen' },
  { key: 'period', sub: 'name', label: 'Periode', format: (_, row) => row.period ? row.period.name : '-' },
  { key: 'amount', label: 'Jumlah (Rp)', format: val => Number(val).toLocaleString('id-ID') },
  { key: 'source', label: 'Sumber' },
  { key: 'note', label: 'Catatan' },
]

const formFields = ref([
  { key: 'payroll_period_id', label: 'Periode Payroll', type: 'select', required: true, options: [] },
  { key: 'employee_id', label: 'Karyawan', type: 'select', required: true, options: [] },
  { key: 'payroll_component_id', label: 'Komponen Gaji', type: 'select', required: true, options: [] },
  { key: 'amount', label: 'Nominal (Rp)', type: 'number' },
  { key: 'quantity', label: 'Kuantitas / Hari', type: 'number' },
  { key: 'note', label: 'Catatan Khusus', type: 'textarea' },
  { key: 'source', label: 'Sumber Input', type: 'select', options: [
    { value: 'MANUAL', label: 'Input Manual Admin' },
    { value: 'ADJUSTMENT', label: 'Penyesuaian' },
    { value: 'CORRECTION', label: 'Koreksi' },
  ]},
])

const initialForm = () => ({ source: 'MANUAL' })

onMounted(async () => {
  try {
    const [empRes, compRes, periodRes] = await Promise.all([
      api.get('/employees?per_page=1000&for_payroll=1'),
      api.get('/payroll-components'),
      api.get('/payroll-periods')
    ]);
    
    const employees = empRes.data.data || empRes.data;
    const components = compRes.data.data || compRes.data;
    const periods = periodRes.data.data || periodRes.data;

    formFields.value[0].options = periods.map(p => ({ value: p.id, label: p.name || p.period }));
    formFields.value[1].options = employees.map(e => ({ value: e.id, label: `${e.name || ''} (${e.employee_id})` }));
    formFields.value[2].options = components.map(c => ({ value: c.id, label: c.name }));
  } catch (error) {
    console.error("Gagal memuat data master", error);
  }
})
</script>
