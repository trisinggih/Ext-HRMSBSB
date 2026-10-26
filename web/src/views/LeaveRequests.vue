<template>
  <CrudPage
    title="Permohonan Cuti & Izin"
    singular="Permohonan"
    endpoint="/leave-requests/admin"
    :creatable="false"
    :disableActions="true"
    :columns="[
      { key: 'employee', label: 'Karyawan', sub: 'name' },
      { key: 'leave_type', label: 'Tipe', sub: 'name' },
      { key: 'start_date', label: 'Mulai' },
      { key: 'end_date', label: 'Selesai' },
      { key: 'total_days', label: 'Hari' },
      { key: 'reason', label: 'Alasan' },
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
    <template #cell-employee="{ value }">
      <span class="fw-bold text-dark">{{ value || '-' }}</span>
    </template>
    
    <template #cell-leave_type="{ value }">
      <span class="fw-semibold text-muted">{{ value || '-' }}</span>
    </template>

    <template #cell-total_days="{ value }">
      <span class="badge bg-light text-dark font-size-12 px-2 py-1 border">{{ value }} Hari</span>
    </template>

    <template #cell-reason="{ value }">
      <div style="max-width: 250px" class="text-truncate text-muted" :title="value">{{ value }}</div>
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
</script>
