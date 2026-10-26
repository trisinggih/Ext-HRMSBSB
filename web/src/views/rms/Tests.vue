<template>
  <div>
    <!-- Header -->
    <div class="row mb-4 align-items-center">
      <div class="col-sm-6">
        <h4 class="mb-0 font-size-18">Kelola Test (RMS)</h4>
        <p class="text-muted mt-1 mb-0">Bank test & atur soal pilihan ganda untuk rekrutmen</p>
      </div>
      <div class="col-sm-6 text-sm-end mt-3 mt-sm-0">
        <button @click="openCreate" class="btn btn-action-add px-3 py-2 waves-effect waves-light shadow-sm" style="border-radius:7px; font-size: 0.85rem;">
          <i class="mdi mdi-plus-circle-outline font-size-15 me-1"></i> Buat Test
        </button>
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
                    <th>Nama</th>
                    <th>Lowongan</th>
                    <th>Durasi</th>
                    <th>Soal</th>
                    <th>Passing</th>
                    <th>Status</th>
                    <th class="text-end">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="t in tests" :key="t.id">
                    <td class="fw-medium text-dark">{{ t.name }}</td>
                    <td>
                      <span v-if="t.recruitment_job">{{ t.recruitment_job.title }}</span>
                      <span v-else class="text-muted font-size-12">Test umum</span>
                    </td>
                    <td>{{ t.duration_minutes ?? '-' }} mnt</td>
                    <td>{{ (t.questions || []).length }} soal</td>
                    <td>{{ t.passing_score ?? '-' }}%</td>
                    <td>
                      <span :class="['badge font-size-12', t.status ? 'bg-success' : 'bg-secondary']">
                        {{ t.status ? 'Aktif' : 'Nonaktif' }}
                      </span>
                    </td>
                    <td class="text-end">
                      <div class="d-flex justify-content-end gap-1">
                        <button @click="$router.push(`/hrms/recruitment/tests/${t.id}`)" class="btn-action-detail" title="Kelola Soal">
                          <i class="mdi mdi-format-list-bulleted-type"></i> Soal
                        </button>
                        <button @click="openEdit(t)" class="btn-action-edit" title="Edit Test">
                          <i class="mdi mdi-pencil-outline"></i> Edit
                        </button>
                        <button @click="remove(t)" class="btn-action-delete" title="Hapus Test">
                          <i class="mdi mdi-trash-can-outline"></i> Hapus
                        </button>
                      </div>
                    </td>
                  </tr>
                  <tr v-if="!tests.length">
                    <td colspan="7" class="text-center py-4 text-muted">
                      {{ loading ? 'Memuat data...' : 'Belum ada test' }}
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
            <h5 class="modal-title">{{ editingId ? 'Edit' : 'Buat' }} Test</h5>
            <button type="button" class="btn-close" @click="modal = false" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Nama <span class="text-danger">*</span></label>
              <input type="text" v-model="form.name" class="form-control" placeholder="Contoh: Tes Psikologi">
            </div>
            
            <div class="mb-3">
              <label class="form-label">Terikat Lowongan <span class="text-muted font-size-12">(opsional)</span></label>
              <select v-model="form.recruitment_job_id" class="form-select">
                <option value="">Test umum</option>
                <option v-for="j in jobs" :key="j.id" :value="j.id">{{ j.title }}</option>
              </select>
            </div>

            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">Durasi (menit)</label>
                <input type="number" v-model.number="form.duration_minutes" min="1" class="form-control">
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Passing Score (%)</label>
                <input type="number" v-model.number="form.passing_score" min="0" max="100" class="form-control">
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label">Deskripsi</label>
              <textarea v-model="form.description" rows="2" class="form-control" placeholder="Penjelasan singkat test ini..."></textarea>
            </div>
            
            <div class="form-check form-switch mb-0">
              <input class="form-check-input" type="checkbox" id="testStatus" v-model="form.status">
              <label class="form-check-label" for="testStatus">Status Aktif</label>
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
import { ref, onMounted } from 'vue'
import api from '../../api'

const tests = ref([])
const jobs = ref([])
const meta = ref(null)
const page = ref(1)
const loading = ref(false)
const saving = ref(false)
const error = ref('')
const modal = ref(false)
const editingId = ref(null)
const form = ref({})

const load = async () => {
  loading.value = true; error.value = ''
  try {
    const { data } = await api.get('/recruitment/admin/tests', { params: { page: page.value } })
    tests.value = data.data?.data ?? data.data ?? []
    meta.value = data.data?.meta ?? data.meta ?? null
  } catch (e) { error.value = e.response?.data?.message || 'Gagal memuat data' }
  finally { loading.value = false }
}

const loadJobs = async () => {
  try {
    const { data } = await api.get('/recruitment/admin/jobs', { params: { per_page: 100 } })
    jobs.value = data.data?.data ?? data.data ?? []
  } catch (e) { /* non-fatal */ }
}

const defaultForm = () => ({ name: '', recruitment_job_id: '', description: '', duration_minutes: 15, passing_score: 70, status: true })

const openCreate = () => { editingId.value = null; form.value = defaultForm(); modal.value = true }
const openEdit = (t) => {
  editingId.value = t.id
  form.value = { ...t, recruitment_job_id: t.recruitment_job_id ?? '' }
  modal.value = true
}

const save = async () => {
  saving.value = true; error.value = ''
  const payload = { ...form.value }
  if (!payload.recruitment_job_id) payload.recruitment_job_id = null
  try {
    if (editingId.value) await api.put(`/recruitment/admin/tests/${editingId.value}`, payload)
    else await api.post('/recruitment/admin/tests', payload)
    modal.value = false; load()
  } catch (e) {
    error.value = e.response?.data?.errors ? Object.values(e.response.data.errors)[0][0] : (e.response?.data?.message || 'Gagal menyimpan')
  } finally { saving.value = false }
}

const remove = async (t) => {
  if (!confirm(`Hapus test "${t.name}"?`)) return
  try { await api.delete(`/recruitment/admin/tests/${t.id}`); load() }
  catch (e) { error.value = e.response?.data?.message || 'Gagal menghapus' }
}

onMounted(() => { load(); loadJobs() })
</script>
