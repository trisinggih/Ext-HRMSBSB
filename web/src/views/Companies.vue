<template>
  <CrudPage
    title="Perusahaan (Company)"
    singular="Perusahaan"
    endpoint="/companies"
    :columns="[
      { key: 'name', label: 'Nama', width: '220px' },
      { key: 'parent_company', label: 'Induk Perusahaan', width: '200px' },
      { key: 'city', label: 'Kota' },
      { key: 'phone', label: 'Telepon' },
      { key: 'is_active', label: 'Status', width: '80px' }
    ]"
    :form-fields="[
      { key: 'name', label: 'Nama Perusahaan', required: true },
      { key: 'legal_name', label: 'Nama Legal (PT / CV)' },
      { key: 'parent_company_id', label: 'Perusahaan Induk (Holding)' },
      { key: 'npwp', label: 'NPWP' },
      { key: 'phone', label: 'Telepon' },
      { key: 'email', label: 'Email', type: 'email' },
      { key: 'is_active', label: 'Status', type: 'select', options: [{label: 'Aktif', value: true}, {label: 'Tidak Aktif', value: false}], default: true },
      { key: 'province', label: 'Provinsi' },
      { key: 'city', label: 'Kota' },
      { key: 'postal_code', label: 'Kode Pos' },
      { key: 'address', label: 'Alamat', type: 'textarea', colClass: 'col-12' },
    ]"
    :initial-form="() => ({ is_active: true, group_id: 1, parent_company_id: null })"
    :filters="[]"
    table-class="table-sm font-size-13"
  >
    <template #cell-name="{ row }">
      <div class="fw-medium text-dark">{{ row.name }}</div>
      <div v-if="row.legal_name" class="text-muted font-size-11">{{ row.legal_name }}</div>
    </template>

    <template #cell-parent_company="{ row }">
      <span v-if="row.parent_company" class="text-dark fw-medium">
        {{ row.parent_company.name }}
      </span>
      <span v-else class="text-muted">-</span>
    </template>

    <template #cell-is_active="{ row }">
      <span class="badge" :class="row.is_active ? 'bg-success' : 'bg-danger'">
        {{ row.is_active ? 'Aktif' : 'Nonaktif' }}
      </span>
    </template>

    <template #field-parent_company_id="{ form }">
      <select class="form-select bg-light border-0 px-3 py-2 shadow-none rounded-3" v-model="form.parent_company_id">
        <option :value="null">-- Tidak Ada (Perusahaan Utama) --</option>
        <option v-for="c in companyList" :key="c.id" :value="c.id" :disabled="form.id && form.id === c.id">
          {{ c.name }} {{ c.legal_name ? `(${c.legal_name})` : '' }}
        </option>
      </select>
    </template>

    <template #field-npwp="{ form }">
      <input type="text" class="form-control bg-light border-0 px-3 py-2 shadow-none rounded-3" v-model="form.npwp" @input="form.npwp = form.npwp ? form.npwp.replace(/[^0-9]/g, '') : ''" placeholder="Hanya Angka" />
    </template>

    <template #field-postal_code="{ form }">
      <input type="text" class="form-control bg-light border-0 px-3 py-2 shadow-none rounded-3" v-model="form.postal_code" @input="form.postal_code = form.postal_code ? form.postal_code.replace(/[^0-9]/g, '') : ''" placeholder="Hanya Angka" />
    </template>

    <template #field-phone="{ form }">
      <input type="text" class="form-control bg-light border-0 px-3 py-2 shadow-none rounded-3" v-model="form.phone" @input="onPhoneInput(form)" placeholder="628xxxxxxxxxx" />
    </template>

    <template #field-province="{ form }">
      <select class="form-select bg-light border-0 px-3 py-2 shadow-none rounded-3" v-model="form.province">
        <option value="">Pilih Provinsi</option>
        <option v-for="prov in provinces" :key="prov.code" :value="prov.name">{{ prov.name }}</option>
      </select>
    </template>

    <template #field-city="{ form }">
      <CitySelect :form="form" />
    </template>
    <template #actions="{ row, openEdit, remove }">
      <router-link :to="`/hrms/companies/${row.id}`" class="btn-action-detail" title="Detail Perusahaan">
        <i class="mdi mdi-eye-outline"></i> Detail
      </router-link>
      <button @click="openEdit(row)" class="btn-action-edit" title="Edit Perusahaan">
        <i class="mdi mdi-pencil-outline"></i> Edit
      </button>
      <button @click="remove(row)" class="btn-action-delete" title="Hapus Perusahaan">
        <i class="mdi mdi-trash-can-outline"></i> Hapus
      </button>
    </template>
  </CrudPage>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import CrudPage from '../components/CrudPage.vue'
import CitySelect from '../components/CitySelect.vue'
import api from '../api'

const provinces = ref([])
const companyList = ref([])

onMounted(async () => {
  await Promise.all([
    loadProvinces(),
    loadCompaniesList()
  ])
})

const onPhoneInput = (form) => {
  if (!form.phone) return
  let val = form.phone.replace(/[^0-9]/g, '')
  if (val.startsWith('0')) val = '62' + val.substring(1)
  else if (val.startsWith('8')) val = '628' + val.substring(1)
  form.phone = val
}

const loadProvinces = async () => {
  try {
    const { data } = await api.get('/regions/provinces')
    provinces.value = data.data
  } catch (e) {
    console.error(e)
  }
}

const loadCompaniesList = async () => {
  try {
    const { data } = await api.get('/companies', { params: { per_page: 100 } })
    companyList.value = data.data?.data || data.data || []
  } catch (e) {
    console.error(e)
  }
}
</script>
