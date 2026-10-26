<template>
  <div>
    <div class="flex items-center justify-between mb-4">
      <div>
        <h2 class="text-xl font-semibold text-slate-800">Keunggulan</h2>
        <p class="text-sm text-slate-500">Kelola daftar keunggulan perusahaan yang tampil di halaman utama.</p>
      </div>
      <button @click="openCreate" class="btn btn-primary">
        + Tambah Keunggulan
      </button>
    </div>

    <div v-if="error" class="alert alert-danger">{{ error }}</div>

    <div class="row g-4">
      <div v-for="adv in advantages" :key="adv.id" class="col-md-6 col-lg-4">
        <div class="bg-white border rounded-sm shadow-sm h-100 hover:shadow transition-shadow">
          <div class="p-4 relative">
            <div class="d-flex justify-content-between align-items-start mb-4">
              <div class="bg-slate-50 border rounded-sm p-2 d-flex align-items-center justify-content-center" style="width:50px; height:50px;">
                <img v-if="adv.image_url" :src="adv.image_url" class="w-100 h-100 object-fit-contain" />
                <span v-else class="text-slate-400 text-xs">Icon</span>
              </div>
              <div class="dropdown">
                <button class="btn btn-sm btn-link text-slate-500 p-0 text-decoration-none" type="button" data-bs-toggle="dropdown">
                  <span class="fs-5 leading-none">&vellip;</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border py-1 rounded-sm text-sm">
                  <li><a class="dropdown-item cursor-pointer text-slate-700 py-1.5" @click="openEdit(adv)">Edit</a></li>
                  <li><a class="dropdown-item text-danger cursor-pointer py-1.5" @click="remove(adv)">Hapus</a></li>
                </ul>
              </div>
            </div>
            <h5 class="text-base font-bold text-slate-800 mb-2">{{ adv.title }}</h5>
            <!-- Using v-html for Quill content preview safely -->
            <div class="text-slate-600 text-sm line-clamp-3" v-html="adv.content || '-'"></div>
          </div>
        </div>
      </div>
      <div v-if="!advantages.length && !loading" class="col-12 text-center p-5 border border-dashed border-slate-300 rounded-sm bg-slate-50 text-slate-500 text-sm">
        Belum ada data keunggulan.<br>
        Klik "Tambah Keunggulan" di atas untuk mulai membuat daftar.
      </div>
    </div>

    <!-- Modal -->
    <div v-if="modal" class="fixed inset-0 bg-slate-900/40 flex items-center justify-center z-50 p-4 transition-opacity" @click.self="modal = false">
      <div class="bg-white rounded-sm shadow-lg w-full max-w-2xl flex flex-col max-h-[90vh]">
        <div class="p-4 border-b bg-slate-50/50 flex justify-between items-center">
          <h3 class="text-sm font-medium text-slate-800 m-0">{{ editingId ? 'Edit' : 'Tambah' }} Keunggulan</h3>
          <button @click="modal = false" class="text-slate-400 hover:text-slate-700 bg-transparent border-0 text-xl leading-none p-0">&times;</button>
        </div>
        <div class="p-5 space-y-4 overflow-y-auto">
          <div>
            <label class="form-label text-slate-600 text-xs font-medium">Judul Keunggulan <span class="text-danger">*</span></label>
            <input v-model="form.title" class="form-control text-sm py-2" placeholder="Misal: Harga Kompetitif" />
          </div>
          <div>
            <label class="form-label text-slate-600 text-xs font-medium">Deskripsi Singkat</label>
            <div class="border rounded-sm">
              <QuillEditor v-model:content="form.content" contentType="html" theme="snow" class="min-h-[150px]" placeholder="Jelaskan secara singkat mengapa hal ini menjadi keunggulan..." />
            </div>
          </div>
          <div class="border-t pt-3 mt-4">
            <label class="form-label text-slate-600 text-xs font-medium mb-3">Icon / Gambar</label>
            <div class="d-flex align-items-center gap-4">
              <div class="border rounded-sm bg-slate-50 flex items-center justify-center overflow-hidden" style="width: 60px; height: 60px;">
                <img v-if="form.image_url" :src="form.image_url" class="w-full h-full object-contain p-1" />
                <span v-else class="text-slate-400 text-xs">Icon</span>
              </div>
              <div>
                <div class="d-flex gap-2 mb-2">
                  <label class="btn btn-outline-secondary btn-sm mb-0 cursor-pointer">
                    Pilih Gambar
                    <input type="file" class="d-none" accept="image/*" @change="uploadImage" :disabled="uploading" />
                  </label>
                  <button v-if="form.image_url" @click="form.image_url = ''" class="btn btn-link text-danger btn-sm p-0 text-decoration-none">Hapus</button>
                </div>
                <div class="text-slate-500 text-[11px]">Format: PNG transparan disarankan. Maksimal 1MB.</div>
              </div>
            </div>
          </div>
        </div>
        <div class="p-4 border-t bg-slate-50/50 flex justify-end gap-2 mt-auto">
          <button @click="modal = false" class="btn btn-light btn-sm px-4">Batal</button>
          <button @click="save" :disabled="saving" class="btn btn-primary btn-sm px-4 shadow-sm">
            {{ saving ? 'Menyimpan...' : 'Simpan' }}
          </button>
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

const advantages = ref([])
const loading = ref(false)
const saving = ref(false)
const uploading = ref(false)
const error = ref('')
const modal = ref(false)
const editingId = ref(null)
const form = ref({ title: '', content: '', image_url: '' })

const load = async () => {
  loading.value = true; error.value = ''
  try {
    const { data } = await api.get('/cms/advantages')
    advantages.value = data.data || []
  } catch (e) {
    error.value = 'Gagal memuat data'
  } finally {
    loading.value = false
  }
}

const openCreate = () => { editingId.value = null; form.value = { title: '', content: '', image_url: '' }; modal.value = true }
const openEdit = (item) => { editingId.value = item.id; form.value = { ...item }; modal.value = true }

const uploadImage = async (e) => {
  const file = e.target.files[0]; if (!file) return;
  uploading.value = true
  const fd = new FormData(); fd.append('file', file)
  try {
    const { data } = await api.post('/cms/media', fd)
    if (data.data?.url) form.value.image_url = data.data.url
  } catch (er) { alert('Gagal upload gambar') }
  finally { uploading.value = false; e.target.value = '' }
}

const save = async () => {
  if (!form.value.title) return alert('Judul wajib diisi')
  saving.value = true; error.value = ''
  try {
    if (editingId.value) {
      await api.put(`/cms/advantages/${editingId.value}`, form.value)
    } else {
      await api.post('/cms/advantages', form.value)
    }
    modal.value = false; load()
  } catch (e) {
    error.value = e.response?.data?.message || 'Gagal menyimpan'
  } finally { saving.value = false }
}

const remove = async (item) => {
  if (!confirm(`Hapus keunggulan "${item.title}"?`)) return
  try { await api.delete(`/cms/advantages/${item.id}`); load() }
  catch (e) { error.value = 'Gagal menghapus' }
}

onMounted(load)
</script>
