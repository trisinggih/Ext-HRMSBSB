<template>
  <div>
    <div class="mb-4">
      <h2 class="text-xl font-semibold text-slate-800">Banner Utama (Hero)</h2>
      <p class="text-sm text-slate-500">Ubah teks informasi dan gambar poster pada Banner Utama.</p>
    </div>

    <div v-if="error" class="alert alert-danger">{{ error }}</div>
    <div v-if="success" class="alert alert-success">{{ success }}</div>

    <div class="row g-4 mb-5">
      <!-- Edit Column -->
      <div class="col-lg-7">
        <div class="bg-white border rounded-sm shadow-sm h-100">
          <div class="border-b px-4 py-3 bg-slate-50/50">
            <h3 class="font-medium text-slate-800 text-sm m-0">Banner Pengaturan</h3>
          </div>
          <div class="p-5 space-y-5">
            <div class="row g-4">
              <div class="col-md-6">
                <label class="form-label text-slate-600 text-xs font-medium">Badge Text</label>
                <input v-model="form.hero_badge" type="text" class="form-control text-sm py-2" placeholder="RACING SPECIFICATION..." />
              </div>
              <div class="col-md-6">
                <label class="form-label text-slate-600 text-xs font-medium">Button Text</label>
                <input v-model="form.hero_cta_catalog" type="text" class="form-control text-sm py-2" placeholder="Lihat Produk Unggulan" />
              </div>
            </div>

            <div class="row g-4">
              <div class="col-md-6">
                <label class="form-label text-slate-600 text-xs font-medium">Judul Baris 1</label>
                <input v-model="form.hero_title_line1" type="text" class="form-control text-sm py-2" placeholder="RIDE WITH" />
              </div>
              <div class="col-md-6">
                <label class="form-label text-slate-600 text-xs font-medium">Judul Baris 2 (Highlight)</label>
                <input v-model="form.hero_title_line2" type="text" class="form-control text-sm py-2" placeholder="REAL PERFORMANCE" />
              </div>
            </div>

            <div>
              <label class="form-label text-slate-600 text-xs font-medium">Deskripsi Banner</label>
              <div class="border rounded-sm">
                <QuillEditor v-model:content="form.hero_subtitle" contentType="html" theme="snow" class="min-h-[200px]" placeholder="Tuliskan deskripsi promosi di sini..." />
              </div>
            </div>

            <div class="border-t pt-4">
              <label class="form-label text-slate-600 text-xs font-medium mb-3">Featured Image</label>
              <div class="d-flex align-items-center gap-4">
                <div class="border rounded bg-slate-50 d-flex align-items-center justify-content-center" style="width: 120px; height: 80px; overflow: hidden;">
                  <img v-if="form.hero_image_url" :src="form.hero_image_url" class="w-100 h-100 object-fit-cover" />
                  <span v-else class="text-slate-400 text-xs">No image</span>
                </div>
                <div>
                  <div class="d-flex gap-2 mb-2">
                    <label class="btn btn-outline-secondary btn-sm mb-0 cursor-pointer">
                      Pilih Gambar
                      <input type="file" class="d-none" accept="image/*" @change="uploadImage" :disabled="uploading" />
                    </label>
                    <button v-if="form.hero_image_url" @click="form.hero_image_url = ''" class="btn btn-link text-danger btn-sm p-0 text-decoration-none">Hapus</button>
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
          <!-- Preview Area mimicking Hero.tsx -->
          <div class="flex-1 relative w-full overflow-hidden bg-gradient-to-b from-white via-slate-50 to-slate-100/70 p-6 border-b border-slate-200">
            <!-- Atmospheric Glow -->
            <div class="absolute -top-32 left-1/4 w-96 h-96 rounded-full bg-teal-500/10 blur-3xl pointer-events-none"></div>
            <div class="absolute top-1/2 -right-24 w-[30rem] h-[30rem] rounded-full bg-cyan-500/10 blur-[120px] pointer-events-none"></div>
            
            <div class="relative z-10 flex flex-col gap-6">
              <!-- Badge -->
              <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-teal-50 border border-teal-200 w-fit">
                <span class="text-[11px] text-[#008779] tracking-widest uppercase font-bold">
                  {{ form.hero_badge || 'RACING SPECIFICATION & STREET DURABILITY' }}
                </span>
              </div>
              
              <!-- Title -->
              <div class="flex flex-col gap-2">
                <h1 class="text-slate-900 tracking-tight uppercase font-extrabold text-3xl sm:text-4xl leading-tight">
                  {{ form.hero_title_line1 || 'RIDE WITH' }} <br />
                  <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#008779] via-teal-600 to-cyan-600">
                    {{ form.hero_title_line2 || 'REAL PERFORMANCE' }}
                  </span>
                </h1>
                <div class="text-slate-600 leading-relaxed max-w-2xl mt-2 text-sm sm:text-base quill-content" v-html="form.hero_subtitle || 'Deskripsi banner...'"></div>
              </div>

              <!-- CTA -->
              <div class="flex flex-wrap items-center gap-4 pt-1">
                <a class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-full bg-[#008779] text-white text-sm shadow-[0_4px_16px_rgba(0,135,121,0.25)]">
                  <span>{{ form.hero_cta_catalog || 'Lihat Produk Unggulan' }}</span>
                </a>
              </div>

              <!-- Image -->
              <div class="relative mt-4 rounded-xl overflow-hidden bg-white border border-slate-200 shadow-sm max-w-sm">
                <img :src="form.hero_image_url || '/assets/images/company/1.webp'" class="w-full h-auto object-cover" />
                <div class="absolute bottom-2 left-2 right-2 z-20 p-2 rounded-lg bg-white/95 backdrop-blur-md border border-slate-200/80 shadow-sm flex flex-col">
                  <span class="text-[9px] text-[#008779] uppercase font-bold">Jaringan Bengkel</span>
                  <span class="text-slate-900 text-[10px]">Tersedia di Seluruh Pelosok</span>
                </div>
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
  hero_badge: '',
  hero_title_line1: '',
  hero_title_line2: '',
  hero_subtitle: '',
  hero_cta_catalog: '',
  hero_image_url: ''
})

const saving = ref(false)
const uploading = ref(false)
const error = ref('')
const success = ref('')

onMounted(() => {
  fetchData()
})

const fetchData = async () => {
  try {
    const { data } = await api.get('/cms/hero')
    if (data.data) {
      form.value = {
        hero_badge: data.data.hero_badge || '',
        hero_title_line1: data.data.hero_title_line1 || '',
        hero_title_line2: data.data.hero_title_line2 || '',
        hero_subtitle: data.data.hero_subtitle || '',
        hero_cta_catalog: data.data.hero_cta_catalog || '',
        hero_image_url: data.data.hero_image_url || ''
      }
    }
  } catch (e) {
    error.value = 'Gagal memuat data Banner Utama'
  }
}

const uploadImage = async (e) => {
  const file = e.target.files[0]
  if (!file) return
  uploading.value = true; error.value = ''; success.value = ''
  
  const fd = new FormData()
  fd.append('file', file)
  try {
    const { data } = await api.post('/cms/media', fd)
    if (data.data && data.data.url) {
      form.value.hero_image_url = data.data.url
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
    await api.post('/cms/hero', form.value)
    success.value = 'Data Banner Utama berhasil disimpan!'
  } catch (e) {
    error.value = e.response?.data?.message || 'Gagal menyimpan perubahan'
  } finally {
    saving.value = false
    setTimeout(() => success.value = '', 3000)
  }
}
</script>
