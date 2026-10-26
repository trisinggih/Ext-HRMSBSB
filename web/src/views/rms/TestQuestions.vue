<template>
  <div>
    <!-- Header -->
    <div class="row mb-4 align-items-center">
      <div class="col-sm-8">
        <button @click="$router.back()" class="btn-back mb-2"><i class="mdi mdi-arrow-left me-1"></i> Kembali</button>
        <h4 class="mb-1 font-size-18 fw-bold text-dark">Soal Test: <span class="text-muted">{{ test?.name }}</span></h4>
        <p class="text-muted mb-0 font-size-13">Kelola soal pilihan ganda (tolak ukur passing: <strong class="text-dark">{{ test?.passing_score }}%</strong>)</p>
      </div>
      <div class="col-sm-4 text-sm-end mt-3 mt-sm-0">
        <button @click="openCreate" class="btn btn-action-add px-3 py-2 waves-effect waves-light shadow-sm" style="border-radius:7px; font-size: 0.85rem;">
          <i class="mdi mdi-plus-circle-outline font-size-15 me-1"></i> Tambah Soal
        </button>
      </div>
    </div>

    <div v-if="error" class="alert alert-danger mb-4">{{ error }}</div>

    <div v-if="test && test.recruitment_job" class="alert alert-info border-0 shadow-sm mb-4 d-flex align-items-center">
      <i class="mdi mdi-information-outline font-size-20 me-2"></i>
      <div>
        Terikat lowongan: <strong>{{ test.recruitment_job.title }}</strong> &bull; Durasi {{ test.duration_minutes }} menit &bull; {{ (test.questions||[]).length }} soal
      </div>
    </div>

    <div v-if="!questions.length && !loading" class="card border border-dashed shadow-none mb-4">
      <div class="card-body py-5 text-center text-muted font-size-14">
        <i class="mdi mdi-file-document-outline font-size-24 d-block mb-2 text-secondary"></i>
        Belum ada soal. Tambahkan soal pilihan ganda.
      </div>
    </div>

    <div v-for="(q, idx) in questions" :key="q.id" class="card mb-3 shadow-sm">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
          <h5 class="font-size-15 fw-medium text-dark mb-0 lh-base">
            <span class="text-muted me-2">{{ idx + 1 }}.</span>{{ q.question }}
          </h5>
          <div class="d-flex gap-1 flex-shrink-0">
            <button @click="openEdit(q)" class="btn-action-edit" title="Edit Soal"><i class="mdi mdi-pencil-outline"></i> Edit</button>
            <button @click="remove(q)" class="btn-action-delete" title="Hapus Soal"><i class="mdi mdi-trash-can-outline"></i> Hapus</button>
          </div>
        </div>
        
        <div class="row ps-4 ms-2 mt-2 g-2">
          <div v-for="opt in ['A','B','C','D']" :key="opt" class="col-md-6">
            <div class="d-flex align-items-center gap-2 p-2 rounded" :class="q.correct_answer === opt ? 'bg-success bg-opacity-10 text-success fw-medium border border-success' : 'text-secondary'">
              <span class="avatar-title rounded-circle font-size-12" style="width: 24px; height: 24px;" :class="q.correct_answer === opt ? 'bg-success text-white' : 'bg-light text-muted border'">{{ opt }}</span>
              <span>{{ q[`option_${opt.toLowerCase()}`] }}</span>
              <span v-if="q.correct_answer === opt" class="font-size-11 ms-auto text-success"><i class="mdi mdi-check-circle"></i> Kunci</span>
            </div>
          </div>
        </div>
        <p class="font-size-12 text-muted mt-3 ps-4 ms-2 mb-0">Skor bobot: <strong class="text-dark">{{ q.score ?? 1 }}</strong></p>
      </div>
    </div>

    <!-- Modal Question -->
    <div v-if="modal" class="modal fade show d-block" tabindex="-1" style="background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px);">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
          <div class="modal-header border-bottom-0 pt-4 px-4 pb-2">
            <h5 class="modal-title fw-bold fs-4 text-dark">{{ editingId ? 'Edit' : 'Tambah' }} Soal</h5>
            <button type="button" class="btn-close shadow-none" @click="modal = false" aria-label="Close"></button>
          </div>
          <div class="modal-body px-4 pb-4 pt-2">
            <div class="mb-3">
              <label class="form-label fw-semibold text-secondary small">Pertanyaan *</label>
              <textarea v-model="form.question" rows="3" class="form-control bg-light border-0 px-3 py-2 shadow-none rounded-3" placeholder="Tulis soal di sini..."></textarea>
            </div>
            <div class="row g-3 mb-4">
              <div v-for="opt in ['A','B','C','D']" :key="opt" class="col-md-6">
                <label class="form-label fw-semibold text-secondary small">Opsi {{ opt }}</label>
                <input v-model="form[`option_${opt.toLowerCase()}`]" class="form-control bg-light border-0 px-3 py-2 shadow-none rounded-3" :placeholder="`Jawaban Opsi ${opt}`" />
              </div>
            </div>
            <div class="row mb-4">
              <div class="col-md-6 mb-3 mb-md-0">
                <label class="form-label fw-semibold text-secondary small">Kunci Jawaban *</label>
                <select v-model="form.correct_answer" class="form-select bg-light border-0 px-3 py-2 shadow-none rounded-3">
                  <option value="A">A</option><option value="B">B</option><option value="C">C</option><option value="D">D</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold text-secondary small">Skor Bobot</label>
                <input v-model.number="form.score" type="number" min="1" class="form-control bg-light border-0 px-3 py-2 shadow-none rounded-3" />
              </div>
            </div>
            <div class="text-end border-top pt-3">
              <button @click="modal = false" class="btn btn-light me-2 waves-effect fw-medium px-4 rounded-3">Batal</button>
              <button @click="save" :disabled="saving" class="btn btn-primary waves-effect waves-light fw-medium px-4 rounded-3 shadow-sm">
                <i v-if="saving" class="mdi mdi-spin mdi-loading me-1"></i> {{ saving ? 'Menyimpan...' : 'Simpan' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import api from '../../api'

const route = useRoute()
const testId = route.params.id
const test = ref(null)
const questions = ref([])
const loading = ref(false)
const saving = ref(false)
const error = ref('')
const modal = ref(false)
const editingId = ref(null)
const form = ref({})

const load = async () => {
  loading.value = true; error.value = ''
  try {
    const { data } = await api.get(`/recruitment/admin/tests/${testId}`)
    test.value = data.data
    questions.value = data.data?.questions ?? []
  } catch (e) { error.value = e.response?.data?.message || 'Gagal memuat data' }
  finally { loading.value = false }
}

const defaultForm = () => ({ question: '', option_a: '', option_b: '', option_c: '', option_d: '', correct_answer: 'A', score: 1 })

const openCreate = () => { editingId.value = null; form.value = defaultForm(); modal.value = true }
const openEdit = (q) => { editingId.value = q.id; form.value = { ...q }; modal.value = true }

const save = async () => {
  saving.value = true; error.value = ''
  try {
    const url = `/recruitment/admin/tests/${testId}/questions`
    if (editingId.value) await api.put(`${url}/${editingId.value}`, form.value)
    else await api.post(url, form.value)
    modal.value = false; load()
  } catch (e) {
    error.value = e.response?.data?.errors ? Object.values(e.response.data.errors)[0][0] : (e.response?.data?.message || 'Gagal menyimpan')
  } finally { saving.value = false }
}

const remove = async (q) => {
  if (!confirm('Hapus soal ini?')) return
  try { await api.delete(`/recruitment/admin/tests/${testId}/questions/${q.id}`); load() }
  catch (e) { error.value = e.response?.data?.message || 'Gagal menghapus' }
}

onMounted(load)
</script>
