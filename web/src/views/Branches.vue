<template>
  <CrudPage
    title="Cabang (Branch)"
    singular="Cabang"
    endpoint="/branches"
    :columns="[
      { key: 'logo', label: 'Logo', width: '70px' },
      { key: 'name', label: 'Nama' },
      { key: 'code', label: 'Kode' },
      { key: 'company', label: 'Perusahaan', sub: 'name' },
      { key: 'city', label: 'Kota' },
      { key: 'phone', label: 'Telepon' },
      { key: 'attendance_radius', label: 'Radius (m)' },
    ]"
    :form-fields="[
      { key: 'name', label: 'Nama', required: true },
      { key: 'code', label: 'Kode Cabang', required: true },
      { key: 'company_id', label: 'Perusahaan', type: 'relation', relationEndpoint: '/companies', required: true },
      { key: 'logo', label: 'Logo Cabang', colClass: 'col-12' },
      { key: 'address', label: 'Alamat' },
      { key: 'province', label: 'Provinsi' },
      { key: 'city', label: 'Kota' },
      { key: 'postal_code', label: 'Kode Pos' },
      { key: 'latitude', label: 'Lokasi Peta', colClass: 'col-12' },
      { key: 'longitude', label: 'Longitude', showIf: () => false },
      { key: 'attendance_radius', label: 'Radius Absensi (m)' },
      { key: 'phone', label: 'Telepon' },
      { key: 'email', label: 'Email', type: 'email' },
    ]"
    :initial-form="onInitialForm"
    :map-data-to-form="onMapDataToForm"
    :filters="[
      { key: 'company_id', label: 'Filter Perusahaan', relationEndpoint: '/companies' }
    ]"
  >
    <template #cell-logo="{ row }">
      <div v-if="row.logo_url" class="d-inline-flex p-1 bg-white rounded border shadow-sm align-items-center justify-content-center" style="width: 42px; height: 42px;">
        <img :src="row.logo_url" alt="Logo" class="object-fit-contain w-100 h-100" />
      </div>
      <div v-else class="d-inline-flex bg-light rounded border align-items-center justify-content-center text-muted small" style="width: 42px; height: 42px;">
        <i class="mdi mdi-store-outline fs-5"></i>
      </div>
    </template>

    <template #field-logo="{ form }">
      <div class="d-flex align-items-center gap-3 p-3 bg-light rounded-3 border">
        <div class="d-flex align-items-center justify-content-center bg-white rounded border shadow-sm p-1" style="width: 64px; height: 64px; flex-shrink: 0;">
          <img v-if="logoPreview || form.logo_url" :src="logoPreview || form.logo_url" alt="Logo Preview" class="w-100 h-100 object-fit-contain" />
          <i v-else class="mdi mdi-image-outline text-muted fs-2"></i>
        </div>
        <div class="flex-grow-1">
          <input
            type="file"
            ref="fileInputRef"
            class="form-control form-control-sm bg-white border shadow-none"
            accept="image/png, image/jpeg, image/jpg, image/webp, image/svg+xml"
            @change="e => onLogoChange(e, form)"
          />
          <div class="d-flex align-items-center justify-content-between mt-1">
            <span class="text-muted" style="font-size: 11px;">Format: PNG, JPG, WEBP, SVG. Maks 2MB. Digunakan untuk cetak dokumen cabang.</span>
            <button
              v-if="form.logo || form.logo_url || logoPreview"
              type="button"
              class="btn btn-link btn-sm text-danger p-0 text-decoration-none"
              style="font-size: 11px;"
              @click="removeLogo(form)"
            >
              <i class="mdi mdi-trash-can-outline me-1"></i>Hapus Logo
            </button>
          </div>
        </div>
      </div>
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

    <template #field-latitude="{ form }">
      <MapPicker v-model:lat="form.latitude" v-model:lng="form.longitude" />
    </template>

    <template #field-longitude>
      <!-- Hidden since MapPicker handles it -->
      <div style="display: none;"></div>
    </template>

    <template #field-postal_code="{ form }">
      <input type="text" class="form-control bg-light border-0 px-3 py-2 shadow-none rounded-3" v-model="form.postal_code" @input="form.postal_code = form.postal_code ? String(form.postal_code).replace(/[^0-9]/g, '') : ''" placeholder="Hanya Angka" />
    </template>

    <template #field-attendance_radius="{ form }">
      <input type="text" class="form-control bg-light border-0 px-3 py-2 shadow-none rounded-3" v-model="form.attendance_radius" @input="form.attendance_radius = form.attendance_radius ? String(form.attendance_radius).replace(/[^0-9]/g, '') : ''" placeholder="Hanya Angka" />
    </template>

    <template #field-phone="{ form }">
      <input type="text" class="form-control bg-light border-0 px-3 py-2 shadow-none rounded-3" v-model="form.phone" @input="onPhoneInput(form)" placeholder="628xxxxxxxxxx" />
    </template>
  </CrudPage>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import CrudPage from '../components/CrudPage.vue'
import MapPicker from '../components/MapPicker.vue'
import CitySelect from '../components/CitySelect.vue'
import api from '../api'

const provinces = ref([])
const fileInputRef = ref(null)
const logoPreview = ref(null)

onMounted(async () => {
  await loadProvinces()
})

const onInitialForm = () => {
  logoPreview.value = null
  if (fileInputRef.value) fileInputRef.value.value = ''
  return { attendance_radius: 20 }
}

const onMapDataToForm = (row) => {
  logoPreview.value = null
  if (fileInputRef.value) fileInputRef.value.value = ''
  return { ...row }
}

const onLogoChange = (e, form) => {
  const file = e.target.files?.[0]
  if (file) {
    form.logo = file
    logoPreview.value = URL.createObjectURL(file)
  }
}

const removeLogo = (form) => {
  form.logo = null
  delete form.logo_url
  logoPreview.value = null
  if (fileInputRef.value) {
    fileInputRef.value.value = ''
  }
}

const loadProvinces = async () => {
  try {
    const { data } = await api.get('/regions/provinces')
    provinces.value = data.data
  } catch (e) {
    console.error(e)
  }
}

const onPhoneInput = (form) => {
  if (!form.phone) return
  let val = String(form.phone).replace(/[^0-9]/g, '')
  if (val.startsWith('0')) val = '62' + val.substring(1)
  else if (val.startsWith('8')) val = '628' + val.substring(1)
  form.phone = val
}
</script>
