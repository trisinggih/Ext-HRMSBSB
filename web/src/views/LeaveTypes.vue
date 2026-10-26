<template>
  <CrudPage
    title="Tipe Cuti (Leave Types)"
    singular="Tipe Cuti"
    endpoint="/leave-types"
    :columns="columns"
    :form-fields="formFields"
    :initial-form="initialForm"
  />
</template>

<script setup>
import CrudPage from '../components/CrudPage.vue'

const columns = [
  { key: 'name', label: 'Nama' },
  { key: 'code', label: 'Kode' },
  { key: 'category', label: 'Kategori' },
  { key: 'default_days', label: 'Default Hari' },
  { key: 'is_paid', label: 'Dibayar', format: (v) => (v == 1 || v === true || v === 'true' || v === '1') ? '✓ Ya' : '−' },
  { key: 'is_carry_forward', label: 'Carry Forward', format: (v) => (v == 1 || v === true || v === 'true' || v === '1') ? '✓ Ya' : '−' },
  { key: 'is_active', label: 'Aktif', format: (v) => (v == 1 || v === true || v === 'true' || v === '1') ? '✓ Ya' : '−' },
]

const formFields = [
  { key: 'name', label: 'Nama', required: true },
  { key: 'code', label: 'Kode', required: true },
  { 
    key: 'category', 
    label: 'Kategori', 
    type: 'select', 
    options: [
      { value: 'annual', label: 'Annual' },
      { value: 'maternity', label: 'Maternity' },
      { value: 'marriage', label: 'Marriage' },
      { value: 'special', label: 'Special' },
      { value: 'public', label: 'Public' },
      { value: 'unpaid', label: 'Unpaid' },
      { value: 'custom', label: 'Custom' }
    ],
    required: true
  },
  { key: 'default_days', label: 'Default Hari', type: 'number', required: true },
  { key: 'is_paid', label: 'Dibayar', type: 'select', options: [{ value: true, label: 'Ya' }, { value: false, label: 'Tidak' }] },
  { key: 'is_carry_forward', label: 'Carry Forward', type: 'select', options: [{ value: true, label: 'Ya' }, { value: false, label: 'Tidak' }] },
  { key: 'max_carry_forward_days', label: 'Max Carry Forward (hari)', type: 'number' },
  { key: 'is_active', label: 'Aktif', type: 'select', options: [{ value: true, label: 'Aktif' }, { value: false, label: 'Non-aktif' }] },
]

const initialForm = () => ({
  category: 'annual',
  default_days: 12,
  is_paid: true,
  is_carry_forward: false,
  max_carry_forward_days: 0,
  is_active: true
})
</script>
