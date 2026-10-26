<template>
  <div>
    <div class="mb-4">
      <h2 class="text-xl font-semibold text-slate-800">Tentang Kami</h2>
      <p class="text-sm text-slate-500">Ubah teks informasi dan gambar utama profil perusahaan.</p>
    </div>

    <div v-if="error" class="alert alert-danger">{{ error }}</div>
    <div v-if="success" class="alert alert-success">{{ success }}</div>

    <div class="row g-4 mb-5">
      <!-- Edit Column -->
      <div class="col-lg-7">
        <div class="bg-white border rounded-sm shadow-sm h-100">
          <div class="border-b px-4 py-3 bg-slate-50/50">
            <h3 class="font-medium text-slate-800 text-sm m-0">Pengaturan Tentang Kami</h3>
          </div>
          <div class="p-5 space-y-5">
            <div>
              <label class="form-label text-slate-600 text-xs font-medium">Judul (Title)</label>
              <input v-model="form.title" type="text" class="form-control text-sm py-2" placeholder="Mengenai Perusahaan" />
            </div>

            <div>
              <label class="form-label text-slate-600 text-xs font-medium">Teks Informasi</label>
              <div class="border rounded-sm">
                <QuillEditor v-model:content="form.content" contentType="html" theme="snow" class="min-h-[250px]" placeholder="Tuliskan profil atau deskripsi tentang perusahaan Anda..." />
              </div>
            </div>

            <div class="border-t pt-4">
              <label class="form-label text-slate-600 text-xs font-medium mb-3">Featured Image</label>
              <div class="d-flex align-items-center gap-4">
                <div class="border rounded bg-slate-50 d-flex align-items-center justify-content-center" style="width: 120px; height: 80px; overflow: hidden;">
                  <img v-if="form.image_url" :src="form.image_url" class="w-100 h-100 object-fit-cover" />
                  <span v-else class="text-slate-400 text-xs">No image</span>
                </div>
                <div>
                  <div class="d-flex gap-2 mb-2">
                    <label class="btn btn-outline-secondary btn-sm mb-0 cursor-pointer">
                      Pilih Gambar
                      <input type="file" class="d-none" accept="image/*" @change="uploadImage" :disabled="uploading" />
                    </label>
                    <button v-if="form.image_url" @click="form.image_url = ''" class="btn btn-link text-danger btn-sm p-0 text-decoration-none">Hapus</button>
                  </div>
                  <p class="text-xs text-slate-500 mb-0">Format: JPG/PNG. Max: 2MB.</p>
                </div>
              </div>
            </div>
          </div>
          <div class="bg-slate-50/50 p-4 border-t text-end">
            <button @click="save" :disabled="saving" class="btn btn-primary btn-sm px-4">
              {{ saving ? 'Menyimpan...' : 'Simpan Perubahan' }}
            </button>
          </div>
        </div>
      </div>

      <!-- Preview Column -->
      <div class="col-lg-5">
        <div class="bg-white border rounded-sm shadow-sm h-100 flex flex-col">
          <div class="border-b px-4 py-3 bg-slate-50/50">
            <h3 class="font-medium text-slate-800 text-sm m-0">Live Preview</h3>
          </div>
          <div class="flex-1 p-6 bg-slate-50 overflow-y-auto">
            <div class="max-w-md w-full mx-auto flex flex-col gap-4">
              <!-- Mimicking AboutPillars.tsx styling -->
              <span class="text-[11px] text-[#008779] uppercase tracking-widest font-bold">
                ENGINEERED INTEGRITY
              </span>
              <h2 class="text-slate-900 uppercase tracking-tight font-extrabold text-2xl sm:text-3xl leading-tight">
                {{ form.title || 'Mengenai Perusahaan' }}
              </h2>
              <div class="text-slate-600 text-sm leading-relaxed whitespace-pre-line quill-content" v-html="form.content || 'Profil perusahaan...'"></div>
              
              <div v-if="form.image_url" class="mt-2">
                <img :src="form.image_url" class="rounded-xl shadow-md w-full h-auto object-cover max-h-48" />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../../api'
import { QuillEditor } from '@vueup/vue-quill'
import '@vueup/vue-quill/dist/vue-quill.snow.css'

const form = ref({
  title: 'Mengenai Jangeby Official',
  content: '',
  image_url: ''
})
const saving = ref(false)
const uploading = ref(false)
const error = ref('')
const success = ref('')

const load = async () => {
  try {
    const { data } = await api.get('/cms/about')
    if (data.data) {
      form.value = {
        title: data.data.title || 'Mengenai Jangeby Official',
        content: data.data.content || '',
        image_url: data.data.image_url || ''
      }
    }
  } catch (e) {
    error.value = 'Gagal memuat data Tentang Kami'
  }
}

const uploadImage = async (e) => {
  const file = e.target.files[0]
  if (!file) return
  uploading.value = true; error.value = ''; success.value = ''
  
  const fd = new FormData()
  fd.append('file', file)
  try {
    // Memanfaatkan endpoint media yang sudah ada
    const { data } = await api.post('/cms/media', fd)
    if (data.data && data.data.url) {
      form.value.image_url = data.data.url
      success.value = 'Gambar berhasil diunggah. Jangan lupa klik Simpan Perubahan.'
    }
  } catch (er) {
    error.value = er.response?.data?.message || 'Gagal upload gambar'
  } finally {
    uploading.value = false; e.target.value = ''
  }
}

const save = async () => {
  saving.value = true; error.value = ''; success.value = ''
  try {
    await api.post('/cms/about', form.value)
    success.value = 'Data Tentang Kami berhasil disimpan!'
  } catch (e) {
    error.value = e.response?.data?.message || 'Gagal menyimpan perubahan'
  } finally {
    saving.value = false
    setTimeout(() => success.value = '', 3000)
  }
}

onMounted(load)
</script>
