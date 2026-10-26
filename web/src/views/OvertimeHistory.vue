<template>
  <CrudPage
    title="History Lembur"
    singular="Riwayat Lembur"
    endpoint="/overtime-requests"
    :creatable="false"
    :disableActions="true"
    :columns="[
      { key: 'employee', label: 'Karyawan', sub: 'name' },
      { key: 'date', label: 'Tanggal' },
      { key: 'shift', label: 'Shift', sub: 'name' },
      { key: 'time_window', label: 'Pengajuan' },
      { key: 'execution', label: 'Pelaksanaan (Absen)' },
      { key: 'status', label: 'Status' },
    ]"
    :filters="[
      { key: 'status', label: 'Status', options: [
        { value: 'pending', label: 'Pending' },
        { value: 'approved', label: 'Disetujui' },
        { value: 'rejected', label: 'Ditolak' }
      ]}
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

    <template #cell-execution="{ row }">
      <div v-if="row.attendance?.overtime_clock_in_at">
        <div class="text-success font-size-12"><i class="mdi mdi-login"></i> {{ formatTime(row.attendance.overtime_clock_in_at) }}</div>
        <div class="text-danger font-size-12"><i class="mdi mdi-logout"></i> {{ row.attendance.overtime_clock_out_at ? formatTime(row.attendance.overtime_clock_out_at) : 'Belum Out' }}</div>
      </div>
      <span v-else class="text-muted font-size-12">-</span>
    </template>
    
    <template #cell-status="{ value }">
      <span class="badge font-size-12 px-3 py-1 rounded-pill" :class="statusClass(value)">
        {{ statusLabel(value) }}
      </span>
    </template>
  </CrudPage>
</template>

<script setup>
import CrudPage from '../components/CrudPage.vue'

const statusClass = (s) => ({
  pending: 'bg-warning-subtle text-warning',
  approved: 'bg-success-subtle text-success',
  rejected: 'bg-danger-subtle text-danger',
}[s] || 'bg-light text-dark')

const statusLabel = (s) => ({
  pending: 'Pending',
  approved: 'Disetujui',
  rejected: 'Ditolak',
}[s] || s || '-')

const formatTime = (timeStr) => {
  if (!timeStr) return ''
  if (timeStr.includes('T') || timeStr.includes(' ')) {
    const d = new Date(timeStr)
    return d.toTimeString().substring(0, 5)
  }
  return timeStr.substring(0, 5)
}
</script>
