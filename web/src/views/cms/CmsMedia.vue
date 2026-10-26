<template>
  <div>
    <!-- Header -->
    <div class="row mb-4 align-items-center">
      <div class="col-sm-6">
        <h4 class="mb-0 font-size-18">Media CMS</h4>
        <p class="text-muted mt-1 mb-0">Upload & kelola file (gambar/dokumen) company profile</p>
      </div>
      <div class="col-sm-6 text-sm-end mt-3 mt-sm-0">
        <label class="btn btn-primary waves-effect waves-light mb-0 cursor-pointer">
          <i class="mdi mdi-upload me-1"></i> Upload Media
          <input type="file" class="d-none" @change="upload" />
        </label>
      </div>
    </div>

    <!-- Alert -->
    <div v-if="error" class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
      {{ error }}
      <button type="button" class="btn-close" @click="error = ''" aria-label="Close"></button>
    </div>
    <div v-if="flash" class="alert alert-success alert-dismissible fade show mb-4" role="alert">
      {{ flash }}
      <button type="button" class="btn-close" @click="flash = ''" aria-label="Close"></button>
    </div>

    <!-- Media Grid -->
    <div class="row">
      <div v-for="m in media" :key="m.id" class="col-xl-3 col-md-4 col-sm-6">
        <div class="card">
          <div class="card-body p-0">
            <!-- Image / Icon container -->
            <div class="bg-light d-flex align-items-center justify-content-center" style="height: 160px; overflow: hidden;">
              <img v-if="isImage(m)" :src="m.url" class="img-fluid h-100 w-100 object-fit-cover" :alt="m.filename" style="object-fit: cover;" />
              <i v-else class="mdi mdi-file-document-outline display-4 text-muted"></i>
            </div>
          </div>
          <div class="card-footer bg-transparent border-top">
            <h5 class="font-size-14 text-truncate mb-1" :title="m.filename">{{ m.filename }}</h5>
            <p class="text-muted mb-2 font-size-12">{{ m.mime }} · {{ fmtSize(m.size) }}</p>
            <div class="d-flex justify-content-between align-items-center">
              <a v-if="m.url" :href="m.url" target="_blank" class="btn btn-outline-info btn-sm">
                <i class="mdi mdi-eye"></i> Lihat
              </a>
              <span v-else></span>
              <button @click="remove(m)" class="btn btn-outline-danger btn-sm">
                <i class="mdi mdi-delete"></i> Hapus
              </button>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Empty State -->
      <div v-if="!media.length && !loading" class="col-12">
        <div class="text-center p-5 border border-dashed rounded text-muted">
          <i class="mdi mdi-tray-arrow-up display-4 mb-3"></i>
          <h5>Belum ada media</h5>
          <p>Klik tombol "Upload Media" untuk menambahkan gambar atau dokumen.</p>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="meta && meta.last_page > 1" class="row align-items-center mt-4">
      <div class="col-sm-6">
        <p class="text-muted mb-0">Halaman {{ meta.current_page }} dari {{ meta.last_page }}</p>
      </div>
      <div class="col-sm-6">
        <ul class="pagination pagination-sm m-0 float-sm-end">
          <li class="page-item" :class="{ disabled: meta.current_page <= 1 }">
            <button class="page-link" @click="page--">Prev</button>
          </li>
          <li class="page-item" :class="{ disabled: meta.current_page >= meta.last_page }">
            <button class="page-link" @click="page++">Next</button>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../../api'

const media = ref([])
const meta = ref(null)
const page = ref(1)
const loading = ref(false)
const uploading = ref(false)
const error = ref('')
const flash = ref('')

const isImage = (m) => (m.mime || '').startsWith('image/')
const fmtSize = (s) => {
  if (s == null) return '-'
  if (s < 1024) return `${s} B`
  if (s < 1024 * 1024) return `${(s / 1024).toFixed(1)} KB`
  return `${(s / 1024 / 1024).toFixed(1)} MB`
}

const load = async () => {
  loading.value = true; error.value = ''
  try {
    const { data } = await api.get('/cms/media', { params: { page: page.value } })
    media.value = data.data?.data ?? data.data ?? []
    meta.value = data.data?.meta ?? data.meta ?? null
  } catch (e) { error.value = e.response?.data?.message || 'Gagal memuat media' }
  finally { loading.value = false }
}

const upload = async (e) => {
  const file = e.target.files[0]
  if (!file) return
  uploading.value = true; error.value = ''
  const fd = new FormData()
  fd.append('file', file)
  try {
    const { data } = await api.post('/cms/media', fd)
    flash.value = data.message || 'Media terunggah'
    setTimeout(() => flash.value = '', 2500)
    page.value = 1; load()
  } catch (er) { error.value = er.response?.data?.message || 'Gagal upload' }
  finally { uploading.value = false; e.target.value = '' }
}

const remove = async (m) => {
  if (!confirm(`Hapus media "${m.filename}"?`)) return
  try { await api.delete(`/cms/media/${m.id}`); load() }
  catch (e) { error.value = e.response?.data?.message || 'Gagal menghapus' }
}

onMounted(load)
</script>
