<template>
  <div>
    <!-- Header -->
    <div class="row mb-4 align-items-center">
      <div class="col-sm-6">
        <h4 class="mb-0 font-size-18">Halaman CMS</h4>
        <p class="text-muted mt-1 mb-0">Halaman statis company profile (slug publik: /tentang-cms/pages/{slug})</p>
      </div>
      <div class="col-sm-6 text-sm-end mt-3 mt-sm-0">
        <button @click="openCreate" class="btn btn-primary waves-effect waves-light">
          <i class="mdi mdi-plus me-1"></i> Tambah Halaman
        </button>
      </div>
    </div>

    <!-- Filter -->
    <div class="card mb-4">
      <div class="card-body p-3">
        <div class="row align-items-center gap-3 gap-md-0">
          <div class="col-md-12">
            <div class="search-box">
              <div class="position-relative">
                <input type="text" v-model="filters.search" @input="debouncedLoad" class="form-control rounded bg-light border-light" placeholder="Cari judul / slug...">
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
                    <th>Judul</th>
                    <th>Slug</th>
                    <th>Seksi</th>
                    <th>Status</th>
                    <th class="text-end">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="p in pages" :key="p.id">
                    <td class="fw-medium text-dark">{{ p.title }}</td>
                    <td class="text-muted">{{ p.slug }}</td>
                    <td>{{ (p.sections || []).length }}</td>
                    <td>
                      <span :class="['badge font-size-12', p.status ? 'bg-success' : 'bg-secondary']">
                        {{ p.status ? 'Publish' : 'Draft' }}
                      </span>
                    </td>
                    <td class="text-end">
                      <a :href="`/tentang-cms/pages/${p.slug}`" target="_blank" class="btn btn-outline-info btn-sm me-1" title="Lihat">
                        <i class="mdi mdi-eye"></i>
                      </a>
                      <button @click="openEdit(p)" class="btn btn-outline-primary btn-sm me-1" title="Edit">
                        <i class="mdi mdi-pencil"></i>
                      </button>
                      <button @click="remove(p)" class="btn btn-outline-danger btn-sm" title="Hapus">
                        <i class="mdi mdi-delete"></i>
                      </button>
                    </td>
                  </tr>
                  <tr v-if="!pages.length">
                    <td colspan="5" class="text-center py-4 text-muted">
                      {{ loading ? 'Memuat data...' : 'Belum ada halaman' }}
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
            <h5 class="modal-title">{{ editingId ? 'Edit' : 'Tambah' }} Halaman</h5>
            <button type="button" class="btn-close" @click="modal = false" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Judul <span class="text-danger">*</span></label>
              <input type="text" v-model="form.title" class="form-control">
            </div>
            <div class="mb-3">
              <label class="form-label">Slug <span class="text-danger">*</span></label>
              <input type="text" v-model="form.slug" class="form-control" placeholder="tentang-kami">
            </div>
            <div class="mb-3">
              <label class="form-label">Konten</label>
              <textarea v-model="form.content" rows="5" class="form-control"></textarea>
            </div>
            
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">Meta Title</label>
                <input type="text" v-model="form.meta_title" class="form-control">
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Meta Description</label>
                <input type="text" v-model="form.meta_description" class="form-control">
              </div>
            </div>

            <div class="form-check form-switch mb-0">
              <input class="form-check-input" type="checkbox" id="pageStatus" v-model="form.status">
              <label class="form-check-label" for="pageStatus">Publish</label>
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

const pages = ref([])
const meta = ref(null)
const page = ref(1)
const loading = ref(false)
const saving = ref(false)
const error = ref('')
const modal = ref(false)
const editingId = ref(null)
const form = ref({})
const filters = reactive({ search: '' })

let searchTimer = null
const debouncedLoad = () => { clearTimeout(searchTimer); searchTimer = setTimeout(() => { page.value = 1; load() }, 400) }

const load = async () => {
  loading.value = true; error.value = ''
  try {
    const { data } = await api.get('/cms/pages', { params: { page: page.value, search: filters.search || undefined } })
    pages.value = data.data?.data ?? data.data ?? []
    meta.value = data.data?.meta ?? data.meta ?? null
  } catch (e) { error.value = e.response?.data?.message || 'Gagal memuat data' }
  finally { loading.value = false }
}

const defaultForm = () => ({ slug: '', title: '', content: '', meta_title: '', meta_description: '', status: true })
const openCreate = () => { editingId.value = null; form.value = defaultForm(); modal.value = true }
const openEdit = (p) => { editingId.value = p.id; form.value = { ...p }; modal.value = true }

const save = async () => {
  saving.value = true; error.value = ''
  try {
    if (editingId.value) await api.put(`/cms/pages/${editingId.value}`, form.value)
    else await api.post('/cms/pages', form.value)
    modal.value = false; load()
  } catch (e) {
    error.value = e.response?.data?.errors ? Object.values(e.response.data.errors)[0][0] : (e.response?.data?.message || 'Gagal menyimpan')
  } finally { saving.value = false }
}

const remove = async (p) => {
  if (!confirm(`Hapus halaman "${p.title}"?`)) return
  try { await api.delete(`/cms/pages/${p.id}`); load() }
  catch (e) { error.value = e.response?.data?.message || 'Gagal menghapus' }
}

onMounted(load)
</script>
