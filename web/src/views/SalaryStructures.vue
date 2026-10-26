<template>
  <CrudPage
    title="Struktur Gaji Karyawan"
    singular="Struktur Gaji"
    endpoint="/salary-structures"
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
  { key: 'employee', label: 'Karyawan', sub: 'name' },
  { key: 'component', label: 'Komponen', sub: 'name' },
  { key: 'amount', label: 'Nominal (Rp)', format: (v) => new Intl.NumberFormat('id-ID').format(v) },
  { key: 'effective_from', label: 'Berlaku Sejak' },
  { key: 'is_active', label: 'Aktif', format: (v) => (v == 1 || v === true || v === 'true' || v === '1') ? '✓ Ya' : '−' },
]

const formFields = ref([
  { key: 'employee_id', label: 'Karyawan', type: 'select', required: true, options: [] },
  { key: 'payroll_component_id', label: 'Komponen Gaji', type: 'select', required: true, options: [] },
  { key: 'amount', label: 'Nominal (Rp)', type: 'number', required: true },
  { key: 'effective_from', label: 'Berlaku Sejak', type: 'date', required: true },
  { key: 'is_active', label: 'Aktif', type: 'select', options: [
    { value: 1, label: 'Ya' }, { value: 0, label: 'Tidak' },
  ]},
])

const initialForm = () => ({
  amount: 0,
  effective_from: new Date().toISOString().split('T')[0],
  is_active: 1
})

onMounted(async () => {
  try {
    const [empRes, compRes] = await Promise.all([
      api.get('/employees?per_page=1000&for_payroll=1'),
      api.get('/payroll-components?per_page=1000')
    ])
    
    formFields.value[0].options = empRes.data.data.map(e => ({ value: e.id, label: `${e.employee_code} - ${e.name}` }))
    formFields.value[1].options = compRes.data.data.map(c => ({ value: c.id, label: `${c.code} - ${c.name} (${c.type})` }))
  } catch (error) {
    console.error('Failed to load options', error)
  }
})
</script>
