<template>
  <CrudPage
    title="Departemen"
    singular="Departemen"
    endpoint="/departments"
    :columns="[
      { key: 'code', label: 'Kode' },
      { key: 'name', label: 'Nama Departemen' },
      { key: 'description', label: 'Deskripsi' },
      { key: 'is_active', label: 'Status' }
    ]"
    :form-fields="[
      { key: 'name', label: 'Nama Departemen', required: true },
      { key: 'code', label: 'Kode', required: true },
      { key: 'description', label: 'Deskripsi', type: 'textarea' },
      { key: 'is_active', label: 'Status', type: 'select', options: [{ value: true, label: 'Aktif' }, { value: false, label: 'Nonaktif' }], required: true }
    ]"
    :filters="[
      { key: 'is_active', label: 'Status', options: [{ value: 1, label: 'Aktif' }, { value: 0, label: 'Nonaktif' }] }
    ]"
  />
</template>

<script setup>
import CrudPage from '../components/CrudPage.vue'
</script>
