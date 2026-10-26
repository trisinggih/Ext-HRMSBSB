<template>
  <div>
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h4 class="font-size-18 mb-0">Skala Penilaian KPI</h4>
        <p class="text-muted mb-0 small">Kelola tingkatan nilai dinamis untuk sistem penilaian KPI</p>
      </div>
      <button class="btn btn-primary" @click="openAdd">
        <i class="mdi mdi-plus me-1"></i> Tambah Skala
      </button>
    </div>

    <!-- List -->
    <div class="row g-4">
      <div class="col-md-6 col-lg-4" v-for="scale in scales" :key="scale.id">
        <div class="card h-100">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-3">
              <div>
                <h6 class="fw-bold mb-1">{{ scale.name }}</h6>
                <p v-if="scale.description" class="text-muted small mb-0">{{ scale.description }}</p>
              </div>
              <div class="d-flex gap-1">
                <button class="btn btn-sm btn-outline-info" @click="openEdit(scale)"><i class="mdi mdi-pencil"></i></button>
                <button class="btn btn-sm btn-outline-danger" @click="remove(scale)"><i class="mdi mdi-trash-can"></i></button>
              </div>
            </div>
            <!-- Levels -->
            <div v-if="scale.levels?.length" class="d-flex flex-column gap-1">
              <div v-for="level in scale.levels" :key="level.id"
                   class="d-flex justify-content-between align-items-center rounded-2 px-2 py-1"
                   style="background:#f8f9fa;">
                <span class="small fw-semibold">{{ level.label }}</span>
                <span class="badge bg-primary">{{ level.score }}</span>
              </div>
            </div>
            <div v-else class="text-muted small fst-italic">Belum ada level</div>
          </div>
        </div>
      </div>
      <div v-if="loading" class="col-12 text-center py-5 text-muted">Memuat...</div>
      <div v-if="!loading && scales.length === 0" class="col-12 text-center py-5 text-muted">
        Belum ada skala penilaian. Klik "Tambah Skala" untuk membuat yang pertama.
      </div>
    </div>

    <!-- Modal -->
    <div v-if="showModal" class="modal d-block" tabindex="-1" style="background:rgba(0,0,0,.4);">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title fw-bold">{{ editingId ? 'Edit Skala Penilaian' : 'Tambah Skala Penilaian' }}</h5>
            <button type="button" class="btn-close" @click="showModal = false"></button>
          </div>
          <div class="modal-body">
            <div class="row g-3 mb-4">
              <div class="col-md-8">
                <label class="form-label fw-semibold small mb-1">Nama Skala <span class="text-danger">*</span></label>
                <input v-model="form.name" type="text" class="form-control" required placeholder="Contoh: Skala 1-5, Bintang..." />
              </div>
              <div class="col-md-12">
                <label class="form-label fw-semibold small mb-1">Deskripsi</label>
                <input v-model="form.description" type="text" class="form-control" placeholder="Keterangan singkat skala ini" />
              </div>
            </div>

            <!-- Level Management -->
            <div class="d-flex justify-content-between align-items-center mb-2">
              <h6 class="fw-bold mb-0">Tingkat Nilai (Levels)</h6>
              <button class="btn btn-sm btn-outline-success" @click="addLevel">
                <i class="mdi mdi-plus me-1"></i> Tambah Level
              </button>
            </div>
            <div class="table-responsive">
              <table class="table table-bordered align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th style="width:40%">Label</th>
                    <th style="width:20%">Nilai (Score)</th>
                    <th style="width:32%">Deskripsi</th>
                    <th style="width:8%"></th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(level, idx) in form.levels" :key="idx">
                    <td><input v-model="level.label" class="form-control form-control-sm" placeholder="Sangat Baik" /></td>
                    <td><input v-model.number="level.score" type="number" step="0.01" class="form-control form-control-sm" placeholder="5" /></td>
                    <td><input v-model="level.description" class="form-control form-control-sm" placeholder="Keterangan..." /></td>
                    <td class="text-center">
                      <button class="btn btn-sm btn-outline-danger p-1" @click="removeLevel(idx)">
                        <i class="mdi mdi-close"></i>
                      </button>
                    </td>
                  </tr>
                  <tr v-if="form.levels.length === 0">
                    <td colspan="4" class="text-center text-muted fst-italic py-3">Belum ada level. Klik "Tambah Level".</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          <div class="modal-footer">
            <button class="btn btn-secondary" @click="showModal = false">Batal</button>
            <button class="btn btn-primary" @click="save" :disabled="saving">
              <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
              Simpan
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
import { toast } from 'vue3-toastify'
import 'vue3-toastify/dist/index.css'
const loading = ref(true)
const saving = ref(false)
const showModal = ref(false)
const editingId = ref(null)
const scales = ref([])

const emptyForm = () => ({ name: '', description: '', is_active: true, levels: [] })
const form = ref(emptyForm())

const fetchScales = async () => {
  loading.value = true
  try {
    const { data } = await api.get('/kpi/rating-scales', { params: { per_page: 100 } })
    scales.value = data.data?.data || data.data || []
  } catch (e) {
    toast.error('Gagal memuat skala penilaian')
  } finally {
    loading.value = false
  }
}

const openAdd = () => {
  editingId.value = null
  form.value = emptyForm()
  showModal.value = true
}

const openEdit = (scale) => {
  editingId.value = scale.id
  form.value = {
    name: scale.name,
    description: scale.description || '',
    is_active: scale.is_active,
    levels: (scale.levels || []).map(l => ({ ...l })),
  }
  showModal.value = true
}

const addLevel = () => {
  form.value.levels.push({ label: '', score: '', description: '', sort_order: form.value.levels.length })
}

const removeLevel = (idx) => {
  form.value.levels.splice(idx, 1)
}

const save = async () => {
  if (!form.value.name) { toast.warning('Nama skala wajib diisi'); return }
  saving.value = true
  try {
    if (editingId.value) {
      await api.put(`/kpi/rating-scales/${editingId.value}`, form.value)
      toast.success('Skala berhasil diperbarui')
    } else {
      await api.post('/kpi/rating-scales', form.value)
      toast.success('Skala berhasil dibuat')
    }
    showModal.value = false
    fetchScales()
  } catch (e) {
    toast.error(e.response?.data?.message || 'Gagal menyimpan')
  } finally {
    saving.value = false
  }
}

const remove = async (scale) => {
  if (!confirm(`Hapus skala "${scale.name}"?`)) return
  try {
    await api.delete(`/kpi/rating-scales/${scale.id}`)
    toast.success('Skala dihapus')
    fetchScales()
  } catch (e) {
    toast.error('Gagal menghapus')
  }
}

onMounted(fetchScales)
</script>
