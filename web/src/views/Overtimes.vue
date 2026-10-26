<template>
  <CrudPage
    title="Pengajuan Lembur"
    singular="Pengajuan"
    endpoint="/overtime-requests"
    :creatable="false"
    :disableActions="true"
    :baseFilters="{ status: 'pending' }"
    :columns="[
      { key: 'employee', label: 'Karyawan', sub: 'name' },
      { key: 'date', label: 'Tanggal' },
      { key: 'shift', label: 'Shift', sub: 'name' },
      { key: 'time_window', label: 'Waktu Pengajuan' },
      { key: 'reason', label: 'Alasan' },
    ]"
  >
    <template #cell-employee="{ value, row }">
      <div class="fw-bold text-dark">{{ value || '-' }}</div>
      <div class="text-muted font-size-12">{{ row.employee?.employee_id || '-' }}</div>
    </template>

    <template #cell-date="{ value }">
      <span class="fw-semibold">{{ value }}</span>
    </template>
    
    <template #cell-shift="{ value, row }">
      <div class="fw-semibold text-muted">{{ value || '-' }}</div>
      <div class="font-size-12 text-muted" v-if="row.shift">
        {{ row.shift.clock_in }} - {{ row.shift.clock_out }}
      </div>
    </template>

    <template #cell-time_window="{ row }">
      <div class="text-dark">
        {{ formatTime(row.requested_start_time) }} - {{ formatTime(row.requested_end_time) }}
      </div>
    </template>

    <template #cell-reason="{ value }">
      <div style="max-width: 250px" class="text-truncate text-muted" :title="value">{{ value }}</div>
    </template>
    
    <template #actions="{ row, loadData }">
      <button @click="approve(row.id, loadData)" class="btn-action-success" title="Setujui">
        <i class="mdi mdi-check"></i> Setujui
      </button>
      <button @click="reject(row.id, loadData)" class="btn-action-delete" title="Tolak">
        <i class="mdi mdi-close"></i> Tolak
      </button>
    </template>
  </CrudPage>
</template>

<script setup>
import { toast } from 'vue3-toastify'
import api from '../api'
import CrudPage from '../components/CrudPage.vue'

const formatTime = (timeStr) => {
  if (!timeStr) return ''
  if (timeStr.includes('T') || timeStr.includes(' ')) {
    const d = new Date(timeStr)
    return d.toTimeString().substring(0, 5)
  }
  return timeStr.substring(0, 5)
}

const approve = async (id, refreshFn) => {
  if (!confirm('Approve pengajuan ini?')) return
  try {
    await api.post(`/overtime-requests/${id}/approve`)
    toast.success('Pengajuan disetujui')
    if (refreshFn) refreshFn()
  } catch (e) {
    toast.error('Gagal menyetujui')
  }
}

const reject = async (id, refreshFn) => {
  const reason = prompt('Alasan penolakan:')
  if (reason === null) return
  try {
    await api.post(`/overtime-requests/${id}/reject`, { rejection_reason: reason })
    toast.success('Pengajuan ditolak')
    if (refreshFn) refreshFn()
  } catch (e) {
    toast.error('Gagal menolak')
  }
}
</script>
