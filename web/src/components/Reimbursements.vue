<template>
  <CrudPage
    title="Reimbursement"
    endpoint="/reimbursements"
    :columns="columns"
    :form-fields="formFields"
  >
    <template #cell-status="{ row }">
      <span class="badge" :class="{
        'bg-warning': row.status === 'pending',
        'bg-success': row.status === 'approved',
        'bg-danger': row.status === 'rejected',
      }">{{ row.status }}</span>
    </template>
    
    <template #cell-amount="{ row }">
      Rp {{ Number(row.amount).toLocaleString('id-ID') }}
    </template>
    
    <template #cell-employee="{ row }">
      <span class="fw-bold">{{ row.employee?.name || '-' }}</span>
    </template>

    <template #cell-approver="{ row }">
      {{ row.approver?.name || '-' }}
    </template>

    <template #cell-attachment="{ row }">
      <a v-if="row.attachment" :href="row.attachment.startsWith('http') ? row.attachment : 'http://localhost:8000/storage/' + row.attachment" target="_blank" class="btn btn-sm btn-outline-info">
        <i class="mdi mdi-eye"></i> Lihat Bukti
      </a>
      <span v-else class="text-muted">-</span>
    </template>
  </CrudPage>
</template>

<script setup>
import { ref } from 'vue'
import CrudPage from './CrudPage.vue'

const columns = [
  { key: 'employee', label: 'Karyawan', sub: 'name' },
  { key: 'date', label: 'Tanggal' },
  { key: 'type', label: 'Tipe' },
  { key: 'amount', label: 'Nominal' },
  { key: 'status', label: 'Status' },
  { key: 'approver', label: 'Disetujui Oleh', sub: 'name' },
  { key: 'attachment', label: 'Lampiran' },
]

const formFields = [
  { key: 'employee_id', label: 'Karyawan', type: 'relation', relationEndpoint: '/employees', required: true },
  { key: 'date', label: 'Tanggal', type: 'date', required: true },
  { key: 'type', label: 'Tipe', type: 'select', options: [
    { value: 'medical', label: 'Medical' },
    { value: 'transport', label: 'Transport' },
    { value: 'meal', label: 'Meal' },
    { value: 'other', label: 'Other' },
  ], required: true },
  { key: 'amount', label: 'Nominal', type: 'number', required: true },
  { key: 'description', label: 'Keterangan', type: 'textarea' },
  { key: 'attachment', label: 'Lampiran Bukti', type: 'file', accept: 'image/*,application/pdf' },
]
</script>
