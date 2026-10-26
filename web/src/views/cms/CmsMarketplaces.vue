<template>
  <div>
    <div class="mb-4">
      <h2 class="text-xl font-bold text-slate-800">Marketplace & Kontak</h2>
      <p class="text-sm text-slate-500">Kelola tautan menuju toko resmi di berbagai platform e-commerce dan sosial media.</p>
    </div>

    <div v-if="error" class="alert alert-danger">{{ error }}</div>
    <div v-if="success" class="alert alert-success">{{ success }}</div>

    <div class="bg-white border rounded-sm shadow-sm max-w-2xl mb-10">
      <div class="border-b px-4 py-3 bg-slate-50/50">
        <h3 class="font-medium text-slate-800 text-sm m-0">Tautan Platform</h3>
      </div>
      
      <div class="p-5 space-y-4">
        <!-- Shopee -->
        <div class="row align-items-center">
          <div class="col-md-3">
            <label class="form-label text-slate-600 text-xs font-medium mb-0">Shopee</label>
          </div>
          <div class="col-md-9">
            <input type="text" v-model="form.shopee" class="form-control text-sm py-2" placeholder="https://shopee.co.id/...">
          </div>
        </div>
        
        <!-- Tokopedia -->
        <div class="row align-items-center">
          <div class="col-md-3">
            <label class="form-label text-slate-600 text-xs font-medium mb-0">Tokopedia</label>
          </div>
          <div class="col-md-9">
            <input type="text" v-model="form.tokopedia" class="form-control text-sm py-2" placeholder="https://tokopedia.com/...">
          </div>
        </div>

        <!-- Lazada -->
        <div class="row align-items-center">
          <div class="col-md-3">
            <label class="form-label text-slate-600 text-xs font-medium mb-0">Lazada</label>
          </div>
          <div class="col-md-9">
            <input type="text" v-model="form.lazada" class="form-control text-sm py-2" placeholder="https://lazada.co.id/...">
          </div>
        </div>

        <!-- Blibli -->
        <div class="row align-items-center">
          <div class="col-md-3">
            <label class="form-label text-slate-600 text-xs font-medium mb-0">Blibli</label>
          </div>
          <div class="col-md-9">
            <input type="text" v-model="form.blibli" class="form-control text-sm py-2" placeholder="https://blibli.com/...">
          </div>
        </div>

        <hr class="border-slate-200">

        <!-- TikTok Shop -->
        <div class="row align-items-center">
          <div class="col-md-3">
            <label class="form-label text-slate-600 text-xs font-medium mb-0">TikTok Shop</label>
          </div>
          <div class="col-md-9">
            <input type="text" v-model="form.tiktok" class="form-control text-sm py-2" placeholder="https://tiktok.com/...">
          </div>
        </div>

        <!-- WhatsApp -->
        <div class="row align-items-center">
          <div class="col-md-3">
            <label class="form-label text-slate-600 text-xs font-medium mb-0">WhatsApp</label>
          </div>
          <div class="col-md-9">
            <input type="text" v-model="form.whatsapp" class="form-control text-sm py-2" placeholder="https://wa.me/...">
            <div class="form-text text-slate-500 text-[11px] mt-1">Gunakan format https://wa.me/628xxx</div>
          </div>
        </div>
      </div>
      
      <div class="bg-slate-50/50 p-4 border-t text-end">
        <button @click="save" :disabled="saving" class="btn btn-primary btn-sm px-4 shadow-sm">
          {{ saving ? 'Menyimpan...' : 'Simpan Perubahan' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../../api'

const form = ref({ shopee: '', tokopedia: '', lazada: '', blibli: '', tiktok: '', whatsapp: '' })
const saving = ref(false)
const error = ref('')
const success = ref('')

const load = async () => {
  try {
    const { data } = await api.get('/cms/marketplaces')
    if (data.data) {
      form.value = { ...form.value, ...data.data }
    }
  } catch (e) {
    error.value = 'Gagal memuat data'
  }
}

const save = async () => {
  saving.value = true; error.value = ''; success.value = ''
  try {
    await api.post('/cms/marketplaces', form.value)
    success.value = 'Data Marketplace berhasil disimpan!'
  } catch (e) {
    error.value = e.response?.data?.message || 'Gagal menyimpan perubahan'
  } finally {
    saving.value = false
    setTimeout(() => success.value = '', 3000)
  }
}

onMounted(load)
</script>
