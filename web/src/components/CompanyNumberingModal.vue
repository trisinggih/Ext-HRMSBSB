<template>
  <div class="modal fade" id="companyNumberingModal" tabindex="-1" aria-hidden="true" ref="modalRef">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <form @submit.prevent="save">
          <div class="modal-header">
            <h5 class="modal-title">Pengaturan Penomoran Otomatis</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div v-if="loading" class="text-center py-4">
              <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
              </div>
            </div>
            
            <div v-else>
              <div class="alert alert-info mb-4">
                Mengatur format penomoran untuk perusahaan <strong>{{ company?.name }}</strong>.<br>
                Variabel yang tersedia: <code>[YYYY]</code> (Tahun 4 digit), <code>[YY]</code> (Tahun 2 digit), <code>[MM]</code> (Bulan), <code>[DD]</code> (Tanggal), <code>[ID]</code> (Nomor Urut Counter).
              </div>

              <div class="row" v-for="(item, index) in settings" :key="item.type">
                <div class="col-12 mb-2"><strong class="text-capitalize">{{ formatTypeLabel(item.type) }}</strong></div>
                <div class="col-md-8 mb-3">
                  <label class="form-label">Format</label>
                  <input type="text" v-model="item.format" class="form-control" required :placeholder="`Contoh: ${getDefaultFormat(item.type)}`" />
                </div>
                <div class="col-md-4 mb-3">
                  <label class="form-label">Counter (Berikutnya)</label>
                  <input type="number" v-model="item.counter" class="form-control" required min="1" />
                </div>
                <div class="col-12 mb-3"><hr v-if="index !== settings.length - 1"></div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-primary" :disabled="saving || loading">
              <i v-if="saving" class="mdi mdi-spin mdi-loading me-1"></i> Simpan Pengaturan
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../api'
import { toast } from 'vue3-toastify'

const modalRef = ref(null)
let modalInstance = null

const company = ref(null)
const loading = ref(false)
const saving = ref(false)

const types = ['employee', 'asset', 'department', 'branch']
const settings = ref([])

onMounted(() => {
  if (window.bootstrap) {
    modalInstance = new window.bootstrap.Modal(modalRef.value)
  }
})

const formatTypeLabel = (type) => {
  const labels = {
    'employee': 'Karyawan (NIP)',
    'asset': 'Aset',
    'department': 'Departemen',
    'branch': 'Cabang'
  }
  return labels[type] || type
}

const getDefaultFormat = (type) => {
  const defaults = {
    'employee': 'EMP-[YYYY][MM]-[ID]',
    'asset': 'AST-[YYYY]-[ID]',
    'department': 'DPT-[ID]',
    'branch': 'BRC-[ID]'
  }
  return defaults[type] || '[ID]'
}

const open = async (companyData) => {
  company.value = companyData
  modalInstance.show()
  await loadSettings()
}

const loadSettings = async () => {
  loading.value = true
  try {
    const { data } = await api.get(`/companies/${company.value.id}/numberings`)
    const existing = data.data || {}
    
    settings.value = types.map(t => {
      return {
        type: t,
        format: existing[t]?.format || getDefaultFormat(t),
        counter: existing[t]?.counter || 1
      }
    })
  } catch (error) {
    toast.error('Gagal memuat pengaturan penomoran')
    modalInstance.hide()
  } finally {
    loading.value = false
  }
}

const save = async () => {
  saving.value = true
  try {
    await api.post(`/companies/${company.value.id}/numberings`, {
      numberings: settings.value
    })
    toast.success('Pengaturan penomoran berhasil disimpan')
    modalInstance.hide()
  } catch (error) {
    toast.error(error.response?.data?.message || 'Gagal menyimpan pengaturan')
  } finally {
    saving.value = false
  }
}

defineExpose({ open })
</script>
