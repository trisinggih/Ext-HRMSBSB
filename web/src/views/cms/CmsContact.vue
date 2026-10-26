<template>
  <div>
    <div class="mb-4">
      <h2 class="text-xl font-bold text-slate-800">Informasi Kontak</h2>
      <p class="text-sm text-slate-500">Ubah detail alamat dan kontak yang ditampilkan di website.</p>
    </div>

    <div v-if="error" class="alert alert-danger">{{ error }}</div>
    <div v-if="success" class="alert alert-success">{{ success }}</div>

    <div class="row g-4">
      <div class="col-lg-7">
        <div class="bg-white border rounded-sm shadow-sm h-100">
          <div class="border-b px-4 py-3 bg-slate-50/50">
            <h3 class="font-medium text-slate-800 text-sm m-0">Lokasi & Alamat</h3>
          </div>
          <div class="p-5 space-y-4">
            <div>
              <label class="form-label text-slate-600 text-xs font-medium">Alamat Lengkap</label>
              <textarea v-model="form.address" rows="3" class="form-control text-sm" placeholder="Jalan Raya No. 1..."></textarea>
            </div>

            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label text-slate-600 text-xs font-medium">Kota</label>
                <input type="text" v-model="form.city" class="form-control text-sm py-2" placeholder="Jakarta">
              </div>
              <div class="col-md-4">
                <label class="form-label text-slate-600 text-xs font-medium">Provinsi</label>
                <input type="text" v-model="form.province" class="form-control text-sm py-2" placeholder="DKI Jakarta">
              </div>
              <div class="col-md-4">
                <label class="form-label text-slate-600 text-xs font-medium">Kode Pos</label>
                <input type="text" v-model="form.postal_code" class="form-control text-sm py-2" placeholder="10110">
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-5">
        <div class="bg-white border rounded-sm shadow-sm h-100">
          <div class="border-b px-4 py-3 bg-slate-50/50">
            <h3 class="font-medium text-slate-800 text-sm m-0">Kontak Utama</h3>
          </div>
          <div class="p-5 space-y-4">
            <div>
              <label class="form-label text-slate-600 text-xs font-medium">Nomor Telepon</label>
              <input type="text" v-model="form.phone" class="form-control text-sm py-2" placeholder="021-...">
            </div>
            <div>
              <label class="form-label text-slate-600 text-xs font-medium">Alamat Email</label>
              <input type="email" v-model="form.email" class="form-control text-sm py-2" placeholder="info@perusahaan.com">
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <div class="mt-4 text-end">
      <button @click="save" :disabled="saving" class="btn btn-primary btn-sm px-5 shadow-sm">
        {{ saving ? 'Menyimpan...' : 'Simpan Perubahan' }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../../api'

const form = ref({ phone: '', email: '', address: '', city: '', province: '', postal_code: '' })
const saving = ref(false)
const error = ref('')
const success = ref('')

const load = async () => {
  try {
    const { data } = await api.get('/cms/contact')
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
    await api.post('/cms/contact', form.value)
    success.value = 'Data Informasi Kontak berhasil disimpan!'
  } catch (e) {
    error.value = e.response?.data?.message || 'Gagal menyimpan perubahan'
  } finally {
    saving.value = false
    setTimeout(() => success.value = '', 3000)
  }
}

onMounted(load)
</script>
