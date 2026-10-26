<template>
  <div>
    <CrudPage
      title="Notifikasi"
      singular="Notifikasi"
      endpoint="/notifications"
      :columns="columns"
      :filters="filters"
      :creatable="false"
      :disableActions="true"
      ref="crudRef"
    >
      <template #header-actions>
        <button @click="openSend" class="btn btn-primary waves-effect waves-light">
          <i class="ri-send-plane-fill align-middle me-1"></i> Kirim Personal
        </button>
        <button @click="openBroadcast" class="btn btn-info text-white waves-effect waves-light">
          <i class="ri-broadcast-fill align-middle me-1"></i> Broadcast
        </button>
      </template>

      <!-- Cell formatting -->
      <template #cell-type="{ value }">
        <span class="badge" :class="typeClass(value)">
          {{ value || '-' }}
        </span>
      </template>

      <template #cell-title="{ value }">
        <span class="fw-medium">{{ value }}</span>
      </template>

      <template #cell-body="{ value }">
        <div class="text-truncate" style="max-width: 250px;" :title="value">
          {{ value || '-' }}
        </div>
      </template>

      <template #cell-is_read="{ value }">
        <span class="badge" :class="value ? 'bg-light text-secondary' : 'bg-success text-white'">
          {{ value ? 'Dibaca' : 'Baru' }}
        </span>
      </template>

      <template #cell-created_at="{ value }">
        {{ fmt(value) }}
      </template>
    </CrudPage>

    <!-- Modal Send -->
    <div v-if="sendModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px);">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4" style="overflow: visible;">
          <div class="modal-header border-bottom-0 pt-4 px-4 pb-2">
            <h5 class="modal-title fw-bold fs-4 text-dark">Kirim Notifikasi Personal</h5>
            <button type="button" class="btn-close shadow-none" @click="sendModal = false" aria-label="Close"></button>
          </div>
          <div class="modal-body px-4 pb-4 pt-2" style="overflow: visible;">
            <div v-if="error" class="alert alert-danger mb-3">{{ error }}</div>
            <form @submit.prevent="submitSend">
              <div class="mb-3">
                <label class="form-label fw-semibold text-secondary small">Karyawan <span class="text-danger">*</span></label>
                <Multiselect
                  v-model="sendForm.employee_id"
                  :options="fetchEmployees"
                  :searchable="true"
                  :clear-on-select="false"
                  placeholder="Cari karyawan..."
                  :resolve-on-load="true"
                  :delay="300"
                  :min-chars="0"
                  class="rounded-3 border-0 bg-light shadow-none custom-multiselect"
                  required
                />
              </div>
              <div class="mb-3">
                <label class="form-label fw-semibold text-secondary small">Judul <span class="text-danger">*</span></label>
                <input v-model="sendForm.title" type="text" required class="form-control bg-light border-0 px-3 py-2 shadow-none rounded-3" placeholder="Judul pesan" />
              </div>
              <div class="mb-3">
                <label class="form-label fw-semibold text-secondary small">Isi Pesan</label>
                <textarea v-model="sendForm.body" rows="3" class="form-control bg-light border-0 px-3 py-2 shadow-none rounded-3 resize-none" placeholder="Isi detail notifikasi"></textarea>
              </div>
              <div class="mb-4">
                <label class="form-label fw-semibold text-secondary small">Tipe</label>
                <input v-model="sendForm.type" type="text" class="form-control bg-light border-0 px-3 py-2 shadow-none rounded-3" placeholder="manual" />
              </div>
              <div class="text-end border-top pt-3">
                <button type="button" @click="sendModal = false" class="btn btn-light me-2 waves-effect fw-medium px-4 rounded-3">Batal</button>
                <button type="submit" :disabled="sending || !sendForm.employee_id" class="btn btn-primary waves-effect waves-light fw-medium px-4 rounded-3 shadow-sm">
                  <i v-if="sending" class="mdi mdi-spin mdi-loading me-1"></i>
                  {{ sending ? 'Mengirim...' : 'Kirim' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Broadcast -->
    <div v-if="bcastModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px);">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
          <div class="modal-header border-bottom-0 pt-4 px-4 pb-2">
            <h5 class="modal-title fw-bold fs-4 text-dark">Broadcast Notifikasi</h5>
            <button type="button" class="btn-close shadow-none" @click="bcastModal = false" aria-label="Close"></button>
          </div>
          <div class="modal-body px-4 pb-4 pt-2">
            <div v-if="error" class="alert alert-danger mb-3">{{ error }}</div>
            <p class="text-muted small mb-3">Kirim ke semua karyawan aktif, atau filter berdasarkan ID karyawan.</p>
            <form @submit.prevent="submitBroadcast">
              <div class="mb-3">
                <label class="form-label fw-semibold text-secondary small">Pilih Karyawan (Opsional)</label>
                <Multiselect
                  v-model="bcastForm.employee_ids"
                  :options="fetchEmployees"
                  mode="multiple"
                  :searchable="true"
                  :clear-on-select="false"
                  placeholder="Pilih karyawan (kosongkan untuk semua)..."
                  :resolve-on-load="true"
                  :delay="300"
                  :min-chars="0"
                  class="rounded-3 border-0 bg-light shadow-none custom-multiselect"
                />
              </div>
              <div class="mb-3">
                <label class="form-label fw-semibold text-secondary small">Judul <span class="text-danger">*</span></label>
                <input v-model="bcastForm.title" type="text" required class="form-control bg-light border-0 px-3 py-2 shadow-none rounded-3" placeholder="Judul broadcast" />
              </div>
              <div class="mb-3">
                <label class="form-label fw-semibold text-secondary small">Isi Pesan</label>
                <textarea v-model="bcastForm.body" rows="3" class="form-control bg-light border-0 px-3 py-2 shadow-none rounded-3 resize-none" placeholder="Isi pesan (opsional)"></textarea>
              </div>
              <div class="mb-4">
                <label class="form-label fw-semibold text-secondary small">Tipe</label>
                <input v-model="bcastForm.type" type="text" class="form-control bg-light border-0 px-3 py-2 shadow-none rounded-3" placeholder="broadcast" />
              </div>
              <div class="text-end border-top pt-3">
                <button type="button" @click="bcastModal = false" class="btn btn-light me-2 waves-effect fw-medium px-4 rounded-3">Batal</button>
                <button type="submit" :disabled="sending" class="btn btn-info text-white waves-effect waves-light fw-medium px-4 rounded-3 shadow-sm">
                  <i v-if="sending" class="mdi mdi-spin mdi-loading me-1"></i>
                  {{ sending ? 'Memproses...' : 'Broadcast' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref } from 'vue'
import { toast } from 'vue3-toastify'
import api from '../api'
import CrudPage from '../components/CrudPage.vue'
import Multiselect from '@vueform/multiselect'
import '@vueform/multiselect/themes/default.css'

const crudRef = ref(null)

const columns = [
  { key: 'type', label: 'Type' },
  { key: 'title', label: 'Judul' },
  { key: 'body', label: 'Isi' },
  { key: 'is_read', label: 'Status' },
  { key: 'created_at', label: 'Waktu' }
]

const filters = [
  { key: 'type', label: 'Tipe', options: [
    { value: 'manual', label: 'Manual' },
    { value: 'broadcast', label: 'Broadcast' },
    { value: 'leave', label: 'Cuti (Leave)' },
    { value: 'sick_leave', label: 'Sakit (Sick Leave)' },
    { value: 'payroll', label: 'Penggajian (Payroll)' },
    { value: 'announcement', label: 'Pengumuman' },
  ]},
  { key: 'is_read', label: 'Status', options: [
    { value: '0', label: 'Baru' },
    { value: '1', label: 'Dibaca' },
  ]}
]

const typeClass = (t) => ({
  manual: 'bg-secondary bg-opacity-10 text-secondary',
  broadcast: 'bg-info bg-opacity-10 text-info',
  leave: 'bg-primary bg-opacity-10 text-primary',
  sick_leave: 'bg-success bg-opacity-10 text-success',
  payroll: 'bg-warning bg-opacity-10 text-warning',
  announcement: 'bg-danger bg-opacity-10 text-danger',
}[t] || 'bg-light text-dark')

const fmt = (iso) => {
  if (!iso) return '-'
  const d = new Date(iso)
  return d.toLocaleString('id-ID', { timeZone: 'Asia/Jakarta', day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

const sendModal = ref(false)
const bcastModal = ref(false)
const sending = ref(false)
const error = ref('')

const sendForm = ref({ employee_id: '', title: '', body: '', type: 'manual' })
const bcastForm = ref({ employee_ids: [], title: '', body: '', type: 'broadcast' })

const fetchEmployees = async (query) => {
  try {
    const { data } = await api.get('/employees', { params: { search: query, per_page: 100 } })
    const list = data.data?.data || data.data || []
    return list.map(item => ({
      value: item.id,
      label: `${item.name} (${item.employee_id || item.employee_code})`
    }))
  } catch (e) {
    return []
  }
}

const openSend = () => {
  sendForm.value = { employee_id: '', title: '', body: '', type: 'manual' }
  error.value = ''
  sendModal.value = true
}

const submitSend = async () => {
  sending.value = true; error.value = ''
  const payload = {
    employee_id: Number(sendForm.value.employee_id),
    title: sendForm.value.title,
    body: sendForm.value.body || null,
    type: sendForm.value.type || 'manual',
  }
  try {
    const { data } = await api.post('/notifications/send', payload)
    toast.success(data.message || 'Notifikasi terkirim')
    sendModal.value = false
    crudRef.value?.load()
  } catch (e) {
    error.value = e.response?.data?.message || 'Gagal mengirim notifikasi'
  } finally { sending.value = false }
}

const openBroadcast = () => {
  bcastForm.value = { employee_ids: [], title: '', body: '', type: 'broadcast' }
  error.value = ''
  bcastModal.value = true
}

const submitBroadcast = async () => {
  sending.value = true; error.value = ''
  const payload = {
    title: bcastForm.value.title,
    body: bcastForm.value.body || null,
    type: bcastForm.value.type || 'broadcast',
  }
  if (bcastForm.value.employee_ids && bcastForm.value.employee_ids.length > 0) {
    payload.employee_ids = bcastForm.value.employee_ids
  }
  try {
    const { data } = await api.post('/notifications/broadcast', payload)
    toast.success(data.message || `Broadcast terkirim ke ${data.data?.sent ?? 0} karyawan`)
    bcastModal.value = false
    crudRef.value?.load()
  } catch (e) {
    error.value = e.response?.data?.message || 'Gagal melakukan broadcast'
  } finally { sending.value = false }
}
</script>
