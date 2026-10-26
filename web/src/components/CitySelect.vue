<template>
  <select class="form-select bg-light border-0 px-3 py-2 shadow-none rounded-3" v-model="form.city" :disabled="!form.province || loading">
    <option value="">{{ loading ? 'Memuat Kota...' : 'Pilih Kota' }}</option>
    <option v-for="c in cities" :key="c.code" :value="c.name">{{ c.name }}</option>
  </select>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import api from '../api'

const props = defineProps({
  form: { type: Object, required: true }
})

const cities = ref([])
const loading = ref(false)

// Shared province cache across instances to prevent duplicate requests and race conditions
let cachedProvinces = window.__hrms_provinces_cache || null
let provincePromise = window.__hrms_provinces_promise || null

const fetchProvinces = async () => {
  if (cachedProvinces && cachedProvinces.length > 0) {
    return cachedProvinces
  }
  if (provincePromise) {
    return await provincePromise
  }
  provincePromise = api.get('/regions/provinces')
    .then(res => {
      cachedProvinces = res.data?.data || []
      window.__hrms_provinces_cache = cachedProvinces
      return cachedProvinces
    })
    .catch(err => {
      console.error('Gagal mengambil daftar provinsi', err)
      return []
    })
    .finally(() => {
      provincePromise = null
    })
  return await provincePromise
}

const loadCities = async (provName) => {
  if (!provName) {
    cities.value = []
    return
  }
  loading.value = true
  try {
    const list = await fetchProvinces()
    const selectedProv = list.find(p => p.name === provName)
    if (selectedProv) {
      const { data } = await api.get(`/regions/cities/${selectedProv.code}`)
      cities.value = data.data || []
    } else {
      cities.value = []
    }
  } catch (e) {
    console.error('Gagal memuat kota', e)
    cities.value = []
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  if (props.form.province) {
    await loadCities(props.form.province)
  }
})

watch(() => props.form.province, async (newProv, oldProv) => {
  if (newProv !== oldProv) {
    // Jika user mengganti provinsi secara manual (bukan inisialisasi awal dengan nilai sama)
    if (oldProv !== undefined && newProv !== oldProv) {
      props.form.city = ''
    }
    await loadCities(newProv)
  }
}, { immediate: true })
</script>
