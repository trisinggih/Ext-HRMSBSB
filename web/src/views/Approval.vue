<template>
  <div>
    <CrudPage
      ref="crudRef"
      title="Approval Requests"
      singular="Approval"
      endpoint="/approval/requests"
      :creatable="false"
      :disableActions="true"
      :columns="[
        { key: 'id', label: 'ID' },
        { key: 'requester', label: 'Pemohon', sub: 'name' },
        { key: 'module', label: 'Modul' },
        { key: 'workflow', label: 'Workflow', sub: 'name' },
        { key: 'current_step', label: 'Step' },
        { key: 'status', label: 'Status' },
        { key: 'created_at', label: 'Diajukan' },
        { key: 'actions', label: 'Aksi' }
      ]"
      :filters="[
        { key: 'status', label: 'Status', options: [
          { value: 'pending', label: 'Pending' },
          { value: 'in_review', label: 'In Review' },
          { value: 'approved', label: 'Approved' },
          { value: 'rejected', label: 'Rejected' },
          { value: 'cancelled', label: 'Cancelled' }
        ]},
        { key: 'module', label: 'Modul', options: [
          { value: 'leave', label: 'Cuti' },
          { value: 'permission', label: 'Izin' },
          { value: 'sick_leave', label: 'Cuti Sakit' },
          { value: 'shift_swap', label: 'Tukar Shift' },
          { value: 'workday_swap', label: 'Tukar Hari' },
          { value: 'loan_request', label: 'Kasbon' },
          { value: 'overtime', label: 'Lembur' },
          { value: 'reimbursement', label: 'Reimbursement' },
          { value: 'employee_mutations', label: 'Mutasi Karyawan' },
          { value: 'asset_request', label: 'Pengajuan Aset' }
        ]}
      ]"
    >
      <template #cell-id="{ value }">
        <span class="text-muted fw-medium">#{{ value }}</span>
      </template>

      <template #cell-requester="{ value }">
        <span class="fw-semibold text-dark">{{ value || '-' }}</span>
      </template>
      
      <template #cell-module="{ value }">
        {{ moduleLabel(value) }}
      </template>

      <template #cell-current_step="{ value }">
        {{ value ?? 0 }}
      </template>

      <template #cell-status="{ value }">
        <span class="badge" :class="statusBadgeClass(value)">
          {{ statusLabel(value) }}
        </span>
      </template>

      <template #cell-created_at="{ value }">
        <span class="text-muted">{{ fmt(value) }}</span>
      </template>

      <template #cell-actions="{ row }">
        <div class="d-flex gap-1 justify-content-end">
          <button @click="openDetail(row)" class="btn-action-detail" title="Detail">
            <i class="mdi mdi-eye-outline"></i> Detail
          </button>
          <button @click="deleteRequest(row)" class="btn-action-delete" title="Hapus">
            <i class="mdi mdi-trash-can-outline"></i> Hapus
          </button>
        </div>
      </template>
    </CrudPage>

    <!-- Modal Detail -->
    <div v-if="detail" class="modal fade show d-block" tabindex="-1" style="background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 1055;">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
          <div class="modal-header border-bottom-0 pt-4 px-4 pb-2">
            <h5 class="modal-title fw-bold fs-4 text-dark">Detail Approval #{{ detail.id }}</h5>
            <button type="button" class="btn-close shadow-none" @click="detail = null" aria-label="Close"></button>
          </div>
          <div class="modal-body px-4 pb-4 pt-2">
            
            <div class="row g-3 mb-4">
              <div class="col-sm-6 col-md-3">
                <p class="text-muted text-uppercase font-size-12 fw-semibold mb-1">Pemohon</p>
                <p class="fw-semibold text-dark mb-0">{{ detail.requester?.name || '-' }}</p>
              </div>
              <div class="col-sm-6 col-md-3">
                <p class="text-muted text-uppercase font-size-12 fw-semibold mb-1">Modul</p>
                <p class="fw-semibold text-dark mb-0">{{ moduleLabel(detail.module) }}</p>
              </div>
              <div class="col-sm-6 col-md-3">
                <p class="text-muted text-uppercase font-size-12 fw-semibold mb-1">Workflow</p>
                <p class="fw-semibold text-dark mb-0">{{ detail.workflow?.name || '-' }}</p>
              </div>
              <div class="col-sm-6 col-md-3">
                <p class="text-muted text-uppercase font-size-12 fw-semibold mb-1">Status</p>
                <span class="badge" :class="statusBadgeClass(detail.status)">
                  {{ statusLabel(detail.status) }}
                </span>
              </div>
            </div>

            <!-- Asset Items Detail (if module is asset_request) -->
            <div v-if="detail.module === 'asset_request'" class="mb-4">
              <div class="card border border-light-subtle shadow-none bg-light-subtle rounded-3 mb-3">
                <div class="card-body p-3">
                  <div class="row g-2 font-size-13">
                    <div class="col-md-6" v-if="detail.payload?.purpose">
                      <span class="text-muted d-block font-size-11">Tujuan / Kebutuhan:</span>
                      <span class="fw-medium text-dark">{{ detail.payload.purpose }}</span>
                    </div>
                    <div class="col-md-6" v-if="detail.payload?.for_who">
                      <span class="text-muted d-block font-size-11">Untuk / Penempatan:</span>
                      <span class="fw-medium text-dark">{{ detail.payload.for_who }} {{ detail.payload.placement && detail.payload.placement !== detail.payload.for_who ? `(${detail.payload.placement})` : '' }}</span>
                    </div>
                    <div class="col-md-12" v-if="detail.payload?.notes">
                      <span class="text-muted d-block font-size-11">Catatan / Keterangan:</span>
                      <span class="text-secondary">{{ detail.payload.notes }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <p class="text-muted text-uppercase font-size-12 fw-semibold mb-2">Rincian Barang</p>
              <div class="table-responsive border rounded-3 bg-white">
                <table class="table table-sm table-hover align-middle mb-0 font-size-13">
                  <thead class="table-light font-size-12 text-muted">
                    <tr>
                      <th class="ps-3 py-2">No</th>
                      <th>Nama Barang</th>
                      <th>Ukuran</th>
                      <th class="text-center">Jumlah</th>
                      <th class="text-end">Harga Satuan</th>
                      <th class="text-end pe-3">Total Estimasi</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(it, idx) in (detail.payload?.items || [{ item_name: detail.payload?.item_name, quantity: detail.payload?.quantity, unit_price: detail.payload?.unit_price, estimated_price: detail.payload?.estimated_price, notes: detail.payload?.notes }])" :key="idx">
                      <td class="ps-3 text-muted">{{ idx + 1 }}</td>
                      <td>
                        <span class="fw-semibold text-dark">{{ it.item_name || '-' }}</span>
                        <small v-if="it.notes" class="d-block text-muted">{{ it.notes }}</small>
                      </td>
                      <td class="text-muted">{{ it.item_size || '-' }}</td>
                      <td class="text-center fw-medium">{{ it.quantity || 1 }} unit</td>
                      <td class="text-end text-muted">{{ it.unit_price ? formatRupiah(it.unit_price) : '-' }}</td>
                      <td class="text-end pe-3 fw-bold text-success">{{ it.estimated_price ? formatRupiah(it.estimated_price) : '-' }}</td>
                    </tr>
                  </tbody>
                  <tfoot v-if="detail.payload?.estimated_price" class="table-light">
                    <tr>
                      <th colspan="5" class="ps-3 py-2 text-end fw-bold">Total Estimasi:</th>
                      <th class="text-end pe-3 py-2 fw-bold text-success font-size-14">{{ formatRupiah(detail.payload.estimated_price) }}</th>
                    </tr>
                  </tfoot>
                </table>
              </div>
            </div>

            <div v-else class="mb-4">
              <p class="text-muted text-uppercase font-size-12 fw-semibold mb-2">Payload (Data)</p>
              <div class="bg-light p-3 rounded text-muted font-size-13" style="max-height: 200px; overflow-y: auto;">
                <pre class="mb-0" style="white-space: pre-wrap;">{{ JSON.stringify(detail.payload, null, 2) }}</pre>
              </div>
            </div>

            <div v-if="detail.logs?.length">
              <p class="text-muted text-uppercase font-size-12 fw-semibold mb-2">Log Approval</p>
              <div class="border rounded p-0">
                <ul class="list-group list-group-flush">
                  <li v-for="log in detail.logs" :key="log.id" class="list-group-item p-3 bg-light">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                      <span class="fw-semibold text-dark">{{ log.approver_name || 'Admin' }}</span>
                      <span class="text-muted font-size-12">{{ fmt(log.created_at, true) }}</span>
                    </div>
                    <p class="mb-1 text-muted">Aksi: <span class="fw-semibold">{{ logLabel(log.action) }}</span></p>
                    <p v-if="log.note" class="mb-0 text-muted font-size-13 fst-italic">Catatan: {{ log.note }}</p>
                  </li>
                </ul>
              </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
              <button type="button" @click="deleteDetailRequest(detail)" class="btn btn-outline-danger fw-medium px-4 rounded-3" :disabled="deleting">
                <i class="mdi mdi-trash-can me-1"></i> {{ deleting ? 'Menghapus...' : 'Hapus Approval' }}
              </button>
              <button type="button" @click="detail = null" class="btn btn-light fw-medium px-4 rounded-3">Tutup</button>
            </div>

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

const crudRef = ref(null)
const detail = ref(null)
const deleting = ref(false)

const statusBadgeClass = (s) => ({
  pending: 'bg-warning',
  in_review: 'bg-info',
  approved: 'bg-success',
  rejected: 'bg-danger',
  cancelled: 'bg-secondary',
}[s] || 'bg-secondary')

const statusLabel = (s) => ({
  pending: 'Pending',
  in_review: 'In Review',
  approved: 'Approved',
  rejected: 'Rejected',
  cancelled: 'Cancelled',
}[s] || s || '-')

const moduleLabel = (m) => ({
  leave: 'Cuti',
  permission: 'Izin',
  sick_leave: 'Cuti Sakit',
  late: 'Keterlambatan',
  shift_swap: 'Tukar Shift',
  workday_swap: 'Tukar Hari',
  loan_request: 'Kasbon',
  overtime: 'Lembur',
  reimbursement: 'Reimbursement',
  employee_mutations: 'Mutasi Karyawan',
  asset_request: 'Pengajuan Aset'
}[m] || m || '-')

const logLabel = (a) => ({
  approve: 'Setujui',
  reject: 'Tolak',
  submit: 'Diajukan',
  cancel: 'Dibatalkan',
}[a] || a || '-')

const fmt = (iso, withDate = false) => {
  if (!iso) return '-'
  const d = new Date(iso)
  const time = d.toLocaleTimeString('id-ID', { timeZone: 'Asia/Jakarta', hour: '2-digit', minute: '2-digit' })
  if (withDate) return d.toLocaleDateString('id-ID', { timeZone: 'Asia/Jakarta', day: '2-digit', month: 'short' }) + ' ' + time
  return time
}

const formatRupiah = (val) => {
  if (val === null || val === undefined) return '-'
  const num = Number(val)
  if (isNaN(num)) return val
  return 'Rp ' + Math.round(num).toLocaleString('id-ID')
}

const openDetail = async (r) => {
  try {
    const { data } = await api.get(`/approval/requests/${r.id}`)
    detail.value = data.data ?? data
  } catch (e) {
    toast.error(e.response?.data?.message || 'Gagal memuat detail')
  }
}

const deleteRequest = async (row) => {
  if (!confirm(`Apakah Anda yakin ingin menghapus approval request #${row.id}?`)) return
  try {
    await api.delete(`/approval/requests/${row.id}`)
    toast.success('Approval request berhasil dihapus')
    if (crudRef.value?.load) {
      crudRef.value.load()
    }
  } catch (e) {
    toast.error(e.response?.data?.message || 'Gagal menghapus approval request')
  }
}

const deleteDetailRequest = async (item) => {
  if (!item || !item.id) return
  if (!confirm(`Apakah Anda yakin ingin menghapus approval request #${item.id}?`)) return
  deleting.value = true
  try {
    await api.delete(`/approval/requests/${item.id}`)
    toast.success('Approval request berhasil dihapus')
    detail.value = null
    if (crudRef.value?.load) {
      crudRef.value.load()
    }
  } catch (e) {
    toast.error(e.response?.data?.message || 'Gagal menghapus approval request')
  } finally {
    deleting.value = false
  }
}
</script>

<style scoped>
.modal.fade.show {
  animation: fadeIn 0.2s ease-out;
}
.modal-content {
  animation: slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}
@keyframes slideUp {
  from { opacity: 0; transform: translateY(20px) scale(0.98); }
  to { opacity: 1; transform: translateY(0) scale(1); }
}
</style>
