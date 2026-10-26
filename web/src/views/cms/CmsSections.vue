<template>
  <div>
    <!-- Header -->
    <div class="row mb-4 align-items-center">
      <div class="col-sm-6">
        <h4 class="mb-0 font-size-18">Seksi CMS</h4>
        <p class="text-muted mt-1 mb-0">Seksi konten company profile (hero, tentang, visi-misi, kontak, dll)</p>
      </div>
      <div class="col-sm-6 text-sm-end mt-3 mt-sm-0">
        <button @click="openCreate" class="btn btn-primary waves-effect waves-light">
          <i class="mdi mdi-plus me-1"></i> Tambah Seksi
        </button>
      </div>
    </div>

    <!-- Filter -->
    <div class="card mb-4">
      <div class="card-body p-3">
        <div class="row align-items-center gap-3 gap-md-0">
          <div class="col-md-6">
            <select v-model="filters.page_id" @change="page = 1; load()" class="form-select">
              <option value="">Semua Halaman</option>
              <option value="0">Tanpa halaman (landing)</option>
              <option v-for="p in pages" :key="p.id" :value="p.id">{{ p.title }}</option>
            </select>
          </div>
          <div class="col-md-6 mt-3 mt-md-0">
            <div class="search-box">
              <div class="position-relative">
                <input type="text" v-model="filters.key" @input="debouncedLoad" class="form-control rounded bg-light border-light" placeholder="Cari key...">
                <i class="mdi mdi-magnify search-icon"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Alert -->
    <div v-if="error" class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
      {{ error }}
      <button type="button" class="btn-close" @click="error = ''" aria-label="Close"></button>
    </div>

    <!-- Table -->
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-bordered table-nowrap table-hover mb-0 align-middle">
                <thead class="table-light">
                  <tr>
                    <th>Key</th>
                    <th>Judul</th>
                    <th>Halaman</th>
                    <th>Urutan</th>
                    <th>Aktif</th>
                    <th class="text-end">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="s in sections" :key="s.id">
                    <td class="font-size-13"><code>{{ s.key }}</code></td>
                    <td class="fw-medium text-dark">{{ s.title || '-' }}</td>
                    <td class="text-muted">{{ s.page ? s.page.title : (s.page_id ? '#'+s.page_id : 'Landing') }}</td>
                    <td>{{ s.sort_order }}</td>
                    <td>
                      <span :class="['badge font-size-12', s.is_active ? 'bg-success' : 'bg-secondary']">
                        {{ s.is_active ? 'Aktif' : 'Nonaktif' }}
                      </span>
                    </td>
                    <td class="text-end">
                      <button @click="openEdit(s)" class="btn btn-outline-primary btn-sm me-1" title="Edit">
                        <i class="mdi mdi-pencil"></i>
                      </button>
                      <button @click="remove(s)" class="btn btn-outline-danger btn-sm" title="Hapus">
                        <i class="mdi mdi-delete"></i>
                      </button>
                    </td>
                  </tr>
                  <tr v-if="!sections.length">
                    <td colspan="6" class="text-center py-4 text-muted">
                      {{ loading ? 'Memuat data...' : 'Belum ada seksi' }}
                    </td>
                  </tr>
                </tbody>
              </table>
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
        </div>
      </div>
    </div>

    <!-- Modal Form -->
    <div v-if="modal" class="modal fade show" tabindex="-1" style="display: block; background: rgba(0,0,0,0.5);" @click.self="modal = false">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">{{ editingId ? 'Edit' : 'Tambah' }} Seksi</h5>
            <button type="button" class="btn-close" @click="modal = false" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">Key <span class="text-danger">*</span></label>
                <input type="text" v-model="form.key" class="form-control" placeholder="hero / tentang / kontak">
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Judul</label>
                <input type="text" v-model="form.title" class="form-control">
              </div>
            </div>
            
            <div class="mb-3">
              <label class="form-label">Halaman</label>
              <select v-model="form.page_id" class="form-select">
                <option value="">Tanpa halaman (landing)</option>
                <option v-for="p in pages" :key="p.id" :value="p.id">{{ p.title }}</option>
              </select>
            </div>

            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">Urutan</label>
                <input type="number" v-model.number="form.sort_order" min="0" class="form-control">
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Image URL</label>
                <input type="text" v-model="form.image_url" class="form-control" placeholder="https://... ">
              </div>
            </div>
            
            <div class="mb-3">
              <label class="form-label">Konten</label>
              <textarea v-model="form.content" rows="4" class="form-control"></textarea>
            </div>

            <div class="form-check form-switch mb-0">
              <input class="form-check-input" type="checkbox" id="sectionStatus" v-model="form.is_active">
              <label class="form-check-label" for="sectionStatus">Aktif</label>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" @click="modal = false" class="btn btn-secondary waves-effect">Batal</button>
            <button type="button" @click="save" :disabled="saving" class="btn btn-primary waves-effect waves-light">
              {{ saving ? 'Menyimpan...' : 'Simpan' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import api from '../../api'

const sections = ref([])
const pages = ref([])
const meta = ref(null)
const page = ref(1)
const loading = ref(false)
const saving = ref(false)
const error = ref('')
const modal = ref(false)
const editingId = ref(null)
const form = ref({})
const filters = reactive({ page_id: '', key: '' })

let searchTimer = null
const debouncedLoad = () => { clearTimeout(searchTimer); searchTimer = setTimeout(() => { page.value = 1; load() }, 400) }

const load = async () => {
  loading.value = true; error.value = ''
  try {
    const params = {
      page: page.value,
      key: filters.key || undefined,
      page_id: filters.page_id === '' ? undefined : (filters.page_id === '0' ? '' : filters.page_id),
    }
    const { data } = await api.get('/cms/sections', { params })
    sections.value = data.data?.data ?? data.data ?? []
    meta.value = data.data?.meta ?? data.meta ?? null
  } catch (e) { error.value = e.response?.data?.message || 'Gagal memuat data' }
  finally { loading.value = false }
}

const loadPages = async () => {
  try {
    const { data } = await api.get('/cms/pages', { params: { per_page: 100 } })
    pages.value = data.data?.data ?? data.data ?? []
  } catch (e) { /* non-fatal */ }
}

const defaultForm = () => ({ key: '', title: '', page_id: '', content: '', image_url: '', sort_order: 0, is_active: true })
const openCreate = () => { editingId.value = null; form.value = defaultForm(); modal.value = true }
const openEdit = (s) => { editingId.value = s.id; form.value = { ...s, page_id: s.page_id ?? '' }; modal.value = true }

const save = async () => {
  saving.value = true; error.value = ''
  const payload = { ...form.value }
  if (!payload.page_id) payload.page_id = null
  try {
    if (editingId.value) await api.put(`/cms/sections/${editingId.value}`, payload)
    else await api.post('/cms/sections', payload)
    modal.value = false; load()
  } catch (e) {
    error.value = e.response?.data?.errors ? Object.values(e.response.data.errors)[0][0] : (e.response?.data?.message || 'Gagal menyimpan')
  } finally { saving.value = false }
}

const remove = async (s) => {
  if (!confirm(`Hapus seksi "${s.key}"?`)) return
  try { await api.delete(`/cms/sections/${s.id}`); load() }
  catch (e) { error.value = e.response?.data?.message || 'Gagal menghapus' }
}

onMounted(() => { load(); loadPages() })
</script>
