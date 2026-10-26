<template>
  <div class="pb-5">
    <!-- Page Header -->


    <!-- Loading -->
    <div v-if="loading" class="card shadow-none border">
      <div class="card-body text-center py-5">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-3 text-muted mb-0">Memuat data perusahaan...</p>
      </div>
    </div>

    <!-- Content -->
    <form v-else @submit.prevent="save">
      <div class="row g-4">

        <!-- LEFT: Identitas Visual -->
        <div class="col-xl-4 col-lg-5">
          <!-- Logo Card -->
          <div class="card shadow-none border mb-4">
            <div class="card-body p-4">
              <h6 class="fw-semibold mb-1">Logo Perusahaan</h6>
              <p class="text-muted font-size-13 mb-4">Maksimal ukuran file 2MB (JPG/PNG).</p>

              <!-- Logo Preview -->
              <div v-if="logoPreview" class="preview-container mb-3 text-center">
                <img :src="logoPreview" alt="Logo Preview" class="img-fluid" />
              </div>

              <!-- Logo Dropzone -->
              <div class="upload-zone text-center" :class="{ 'upload-zone--active': isDragging }"
                @dragover.prevent="isDragging = true" @dragleave.prevent="isDragging = false"
                @drop.prevent="handleDrop" @click="$refs.fileInput.click()">
                <p class="mb-1 fw-medium text-dark">Pilih atau letakkan logo</p>
                <p class="mb-0 text-muted font-size-12">Klik untuk mencari file</p>
                <input type="file" ref="fileInput" @change="handleFileSelect" class="d-none" accept="image/jpeg,image/png,image/jpg" />
              </div>
            </div>
          </div>

          <!-- Favicon Card -->
          <div class="card shadow-none border d-none">
            <div class="card-body p-4">
              <h6 class="fw-semibold mb-1">Favicon</h6>
              <p class="text-muted font-size-13 mb-4">Ikon kecil pada tab browser (Maks 1MB).</p>

              <!-- Favicon Preview -->
              <div v-if="faviconPreview" class="d-flex align-items-center mb-3">
                <div class="favicon-preview-wrapper me-3">
                  <img :src="faviconPreview" alt="Favicon" />
                </div>
                <div>
                  <span class="badge bg-light text-dark border">Aktif</span>
                </div>
              </div>

              <!-- Favicon Dropzone -->
              <div class="upload-zone text-center p-3" :class="{ 'upload-zone--active': isDraggingFavicon }"
                @dragover.prevent="isDraggingFavicon = true" @dragleave.prevent="isDraggingFavicon = false"
                @drop.prevent="handleFaviconDrop" @click="$refs.faviconInput.click()">
                <p class="mb-1 fw-medium text-dark font-size-13">Pilih favicon</p>
                <input type="file" ref="faviconInput" @change="handleFaviconSelect" class="d-none" accept="image/png,image/x-icon,image/vnd.microsoft.icon,image/jpeg" />
              </div>
            </div>
          </div>
        </div>

        <!-- RIGHT: Informasi Perusahaan -->
        <div class="col-xl-8 col-lg-7">
          <div class="card shadow-none border h-100">
            <div class="card-body p-4 p-xl-5 d-flex flex-column">

              <div class="row g-4 mb-5">
                <div class="col-12">
                  <label class="form-label fw-medium text-dark">Nama Grup Perusahaan <span class="text-danger">*</span></label>
                  <input type="text" v-model="form.name" class="form-control p-2" placeholder="Masukkan nama perusahaan" required />
                </div>
              </div>

              <h5 class="fw-semibold mb-4 border-bottom pb-3">Kontak &amp; Alamat</h5>

              <div class="row g-4 mb-4">
                <div class="col-md-6">
                  <label class="form-label fw-medium text-dark">Email Perusahaan</label>
                  <input type="email" v-model="form.email" class="form-control p-2" placeholder="contoh@perusahaan.com" />
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-medium text-dark">Nomor Telepon</label>
                  <input type="text" v-model="form.phone" class="form-control p-2" placeholder="Contoh: 021-8000000" />
                </div>
                <div class="col-12">
                  <label class="form-label fw-medium text-dark">Alamat Lengkap</label>
                  <textarea v-model="form.address" class="form-control p-2" rows="4" placeholder="Tuliskan alamat lengkap perusahaan..."></textarea>
                </div>
              </div>

              <div class="mt-auto pt-0 text-end">
                <button type="submit" :disabled="saving" class="btn btn-primary px-4 py-2 fw-medium shadow-sm">
                  <span v-if="saving">
                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                    Menyimpan...
                  </span>
                  <span v-else>Simpan Perubahan</span>
                </button>
              </div>

            </div>
          </div>
        </div>

      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { toast } from 'vue3-toastify'
import api from '../api'
import { useCompanyStore } from '../stores/companyStore'

const companyStore = useCompanyStore()
const loading = ref(false)
const saving = ref(false)
const companyId = ref(null)
const form = ref({ name: '', email: '', phone: '', address: '', is_active: true })
const logoFile = ref(null)
const logoPreview = ref(null)
const isDragging = ref(false)
const faviconFile = ref(null)
const faviconPreview = ref(null)
const isDraggingFavicon = ref(false)

const resolveUrl = (path) => {
  if (!path) return null
  if (path.startsWith('http')) return path
  const base = (import.meta.env.VITE_API_URL || 'http://127.0.0.1:8011/api/v1').replace('/api/v1', '')
  return `${base}/storage/${path}`
}

const load = async () => {
  loading.value = true
  try {
    const { data } = await api.get('/company-groups')
    const list = data.data?.data ?? data.data ?? []
    if (list.length > 0) {
      const comp = list[0]
      companyId.value = comp.id
      Object.keys(form.value).forEach(key => { if (comp[key] !== undefined) form.value[key] = comp[key] })
      logoPreview.value = resolveUrl(comp.logo)
      faviconPreview.value = resolveUrl(comp.favicon)
    }
  } catch (e) {
    toast.error('Gagal memuat data perusahaan.')
  } finally {
    loading.value = false
  }
}

const handleDrop = (e) => { isDragging.value = false; processFile(e.dataTransfer.files[0]) }
const handleFileSelect = (e) => processFile(e.target.files[0])
const processFile = (file) => {
  if (!file) return
  if (!file.type.match('image.*')) { toast.error('Harap pilih file gambar (JPEG, PNG).'); return }
  if (file.size > 2 * 1024 * 1024) { toast.error('Ukuran file logo maksimal 2MB.'); return }
  logoFile.value = file
  logoPreview.value = URL.createObjectURL(file)
}

const handleFaviconDrop = (e) => { isDraggingFavicon.value = false; processFaviconFile(e.dataTransfer.files[0]) }
const handleFaviconSelect = (e) => processFaviconFile(e.target.files[0])
const processFaviconFile = (file) => {
  if (!file) return
  if (!file.type.match('image.*') && file.type !== 'image/x-icon' && file.type !== 'image/vnd.microsoft.icon') {
    toast.error('Harap pilih file favicon valid.'); return
  }
  if (file.size > 1 * 1024 * 1024) { toast.error('Ukuran file favicon maksimal 1MB.'); return }
  faviconFile.value = file
  faviconPreview.value = URL.createObjectURL(file)
}

const save = async () => {
  saving.value = true
  try {
    const formData = new FormData()
    Object.keys(form.value).forEach(key => {
      if (form.value[key] !== null && form.value[key] !== undefined)
        formData.append(key, typeof form.value[key] === 'boolean' ? (form.value[key] ? '1' : '0') : form.value[key])
    })
    if (logoFile.value) formData.append('logo', logoFile.value)
    if (faviconFile.value) formData.append('favicon', faviconFile.value)
    if (companyId.value) {
      formData.append('_method', 'PUT')
      await api.post(`/company-groups/${companyId.value}`, formData, { headers: { 'Content-Type': 'multipart/form-data' } })
    } else {
      await api.post(`/company-groups`, formData, { headers: { 'Content-Type': 'multipart/form-data' } })
    }
    toast.success('Profil perusahaan berhasil disimpan!')
    await companyStore.fetchCompany()
  } catch (e) {
    toast.error(e.response?.data?.message || 'Gagal menyimpan data.')
  } finally {
    saving.value = false
  }
}

onMounted(() => load())
</script>

<style scoped>
.preview-container {
  background-color: #0c768a;
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: 8px;
  padding: 1.5rem;
  min-height: 140px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.preview-container img {
  max-height: 100px;
  object-fit: contain;
}
.favicon-preview-wrapper {
  width: 48px;
  height: 48px;
  background-color: #f8f9fa;
  border: 1px solid #e9ecef;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.favicon-preview-wrapper img {
  width: 24px;
  height: 24px;
  object-fit: contain;
}
.upload-zone {
  border: 1px dashed #ced4da;
  border-radius: 8px;
  padding: 1.25rem 1rem;
  cursor: pointer;
  background-color: #ffffff;
  transition: all 0.2s ease-in-out;
}
.upload-zone:hover, .upload-zone--active {
  border-color: #0d6efd;
  background-color: rgba(13, 110, 253, 0.03);
}
</style>
