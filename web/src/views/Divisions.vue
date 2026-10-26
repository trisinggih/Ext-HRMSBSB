<template>
  <CrudPage
    title="Divisi"
    singular="Divisi"
    endpoint="/divisions"
    :columns="[
      { key: 'code', label: 'Kode' },
      { key: 'name', label: 'Nama Divisi' },
      { key: 'department', label: 'Departemen', sub: 'name' },
      { key: 'is_active', label: 'Status' },
    ]"
    :form-fields="[
      { key: 'name', label: 'Nama Divisi', required: true },
      { key: 'code', label: 'Kode', required: true },
      { key: 'department_id', label: 'Departemen', type: 'relation', relationEndpoint: '/departments' },
      { key: 'description', label: 'Deskripsi', type: 'textarea' },
      { key: 'is_active', label: 'Status', type: 'select', options: [{ value: true, label: 'Aktif' }, { value: false, label: 'Nonaktif' }], required: true }
    ]"
    :filters="[
      { key: 'department_id', label: 'Filter Departemen', relationEndpoint: '/departments' },
      { key: 'is_active', label: 'Status', options: [{ value: 1, label: 'Aktif' }, { value: 0, label: 'Nonaktif' }] }
    ]"
  />
</template>

<script setup>
import CrudPage from '../components/CrudPage.vue'
</script>
