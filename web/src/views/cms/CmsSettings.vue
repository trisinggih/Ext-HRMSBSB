<template>
  <div>
    <div class="flex items-center justify-between mb-4">
      <div>
        <h2 class="text-xl font-semibold text-slate-800">Setting CMS</h2>
        <p class="text-sm text-slate-500">Pengaturan company profile (key-value). Ditampilkan lewat /cms/site</p>
      </div>
      <button @click="openCreate" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
        + Tambah Setting
      </button>
    </div>

    <div class="bg-white border rounded-xl p-3 mb-4 flex flex-wrap gap-3">
      <input v-model="filters.group" @input="debouncedLoad" placeholder="Filter grup (ex: general, contact...)"
        class="border border-slate-300 rounded-lg px-3 py-2 text-sm flex-1 min-w-[200px]" />
    </div>

    <div v-if="error" class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-3 mb-4">{{ error }}</div>

    <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
      <table class="min-w-full divide-y divide-slate-200">
        <thead class="bg-slate-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Key</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Nilai</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Grup</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Aktif</th>
            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="s in settings" :key="s.id" class="hover:bg-slate-50">
            <td class="px-4 py-3 text-sm font-medium text-slate-800">{{ s.key }}</td>
            <td class="px-4 py-3 text-sm text-slate-600 max-w-xs truncate">{{ s.value || '-' }}</td>
            <td class="px-4 py-3 text-sm text-slate-600">{{ s.group }}</td>
            <td class="px-4 py-3">
              <span class="px-2 py-1 text-xs rounded-full font-semibold" :class="s.is_active ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-600'">{{ s.is_active ? 'Aktif' : 'Nonaktif' }}</span>
            </td>
            <td class="px-4 py-3 text-right text-sm whitespace-nowrap">
              <button @click="openEdit(s)" class="text-blue-600 hover:text-blue-800 mr-3">Edit</button>
              <button @click="remove(s)" class="text-red-500 hover:text-red-700">Hapus</button>
            </td>
          </tr>
          <tr v-if="!settings.length">
            <td colspan="5" class="px-4 py-8 text-center text-sm text-slate-400">{{ loading ? 'Memuat...' : 'Belum ada setting' }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="meta && meta.last_page > 1" class="flex items-center justify-between mt-4 text-sm text-slate-600">
      <span>Hal {{ meta.current_page }} dari {{ meta.last_page }}</span>
      <div class="flex gap-2">
        <button :disabled="meta.current_page <= 1" @click="page--" class="px-3 py-1 border rounded-lg disabled:opacity-40">Prev</button>
        <button :disabled="meta.current_page >= meta.last_page" @click="page++" class="px-3 py-1 border rounded-lg disabled:opacity-40">Next</button>
      </div>
    </div>

    <!-- Modal -->
    <div v-if="modal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4" @click.self="modal = false">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
        <div class="p-5 border-b"><h3 class="text-lg font-semibold text-slate-800">{{ editingKey ? 'Edit' : 'Tambah' }} Setting</h3></div>
        <div class="p-5 space-y-4">
          <div><label class="block text-sm font-medium text-slate-600 mb-1">Key *</label>
            <input v-model="form.key" :disabled="!!editingKey" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm disabled:bg-slate-100" placeholder="company_name" /></div>
          <div><label class="block text-sm font-medium text-slate-600 mb-1">Nilai</label>
            <textarea v-model="form.value" rows="2" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"></textarea></div>
          <div><label class="block text-sm font-medium text-slate-600 mb-1">Grup</label>
            <input v-model="form.group" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" placeholder="general / contact / about" /></div>
          <div><label class="inline-flex items-center text-sm text-slate-700">
            <input v-model="form.is_active" type="checkbox" class="mr-2" /> Aktif
          </label></div>
        </div>
        <div class="p-5 border-t flex justify-end gap-3">
          <button @click="modal = false" class="px-4 py-2 border rounded-lg text-sm text-slate-600">Batal</button>
          <button @click="save" :disabled="saving" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm disabled:opacity-50">{{ saving ? 'Menyimpan...' : 'Simpan' }}</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import api from '../../api'

const settings = ref([])
const meta = ref(null)
const page = ref(1)
const loading = ref(false)
const saving = ref(false)
const error = ref('')
const modal = ref(false)
const editingKey = ref(null)
const form = ref({})
const filters = reactive({ group: '' })

let searchTimer = null
const debouncedLoad = () => { clearTimeout(searchTimer); searchTimer = setTimeout(() => { page.value = 1; load() }, 400) }

const load = async () => {
  loading.value = true; error.value = ''
  try {
    const { data } = await api.get('/cms/settings', { params: { page: page.value, group: filters.group || undefined } })
    settings.value = data.data?.data ?? data.data ?? []
    meta.value = data.data?.meta ?? data.meta ?? null
  } catch (e) { error.value = e.response?.data?.message || 'Gagal memuat data' }
  finally { loading.value = false }
}

const defaultForm = () => ({ key: '', value: '', group: 'general', is_active: true })
const openCreate = () => { editingKey.value = null; form.value = defaultForm(); modal.value = true }
const openEdit = (s) => { editingKey.value = s.key; form.value = { ...s }; modal.value = true }

const save = async () => {
  saving.value = true; error.value = ''
  try {
    await api.post('/cms/settings', form.value)
    modal.value = false; load()
  } catch (e) {
    error.value = e.response?.data?.errors ? Object.values(e.response.data.errors)[0][0] : (e.response?.data?.message || 'Gagal menyimpan')
  } finally { saving.value = false }
}

const remove = async (s) => {
  if (!confirm(`Hapus setting "${s.key}"?`)) return
  try { await api.delete(`/cms/settings/${s.key}`); load() }
  catch (e) { error.value = e.response?.data?.message || 'Gagal menghapus' }
}

onMounted(load)
</script>
