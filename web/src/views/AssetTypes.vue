<template>
  <CrudPage
    title="Tipe Aset (Asset Types)"
    singular="Tipe Aset"
    endpoint="/asset-types"
    :columns="[
      { key: 'code', label: 'Kode Tipe Aset' },
      { key: 'name', label: 'Nama Tipe Aset' },
      { key: 'description', label: 'Deskripsi' }
    ]"
    :form-fields="[
      { key: 'code', label: 'Kode Tipe Aset', placeholder: 'Contoh: ELK / LAPTOP / KND / ATK', required: true },
      { key: 'name', label: 'Nama Tipe Aset', placeholder: 'Contoh: Elektronik / Laptop & Komputer / ATK', required: true },
      { key: 'description', label: 'Deskripsi', type: 'textarea', placeholder: 'Keterangan kategori aset...' }
    ]"
  >
    <template #header-actions>
      <router-link to="/hrms/assets" class="btn btn-outline-secondary px-3 waves-effect" style="border-radius:6px;">
        Data Aset
      </router-link>
    </template>
  </CrudPage>
</template>
<script setup>
import CrudPage from '../components/CrudPage.vue'
</script>
