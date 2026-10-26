<template>
  <div class="row">
    <div class="col-md-6 mb-3">
      <label class="form-label fw-semibold">Provinsi</label>
      <select class="form-select" v-model="form.province" @change="onProvinceChange">
        <option value="">Pilih Provinsi</option>
        <option v-for="prov in provinces" :key="prov.code" :value="prov.name">{{ prov.name }}</option>
      </select>
    </div>
    <div class="col-md-6 mb-3">
      <label class="form-label fw-semibold">Kota</label>
      <select class="form-select" v-model="form.city" :disabled="!form.province">
        <option value="">Pilih Kota</option>
        <option v-for="c in cities" :key="c.code" :value="c.name">{{ c.name }}</option>
      </select>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import api from '../api'

const props = defineProps({
  form: { type: Object, required: true }
})

const provinces = ref([])
const cities = ref([])

onMounted(async () => {
  await loadProvinces()
  if (props.form.province) {
    loadCitiesForProvince(props.form.province)
  }
})

watch(() => props.form.province, (newVal) => {
  if (newVal) {
    loadCitiesForProvince(newVal)
  } else {
    cities.value = []
  }
})

const loadProvinces = async () => {
  try {
    const { data } = await api.get('/regions/provinces')
    provinces.value = data.data
  } catch (e) {
    console.error(e)
  }
}

const loadCitiesForProvince = async (provinceName) => {
  try {
    // Wait for provinces to load if they haven't
    if (provinces.value.length === 0) {
      await loadProvinces()
    }
    const selectedProv = provinces.value.find(p => p.name === provinceName)
    if (selectedProv) {
      const { data } = await api.get(`/regions/cities/${selectedProv.code}`)
      cities.value = data.data
    }
  } catch (e) {
    console.error(e)
  }
}

const onProvinceChange = () => {
  props.form.city = '' // reset city when province changes
  if (props.form.province) {
    loadCitiesForProvince(props.form.province)
  } else {
    cities.value = []
  }
}
</script>
