<template>
  <CrudPage
    title="Terminasi (PHK / Resign)"
    endpoint="/terminations"
    :columns="columns"
    :form-fields="formFields"
  >
    <template #cell-status="{ row }">
      <span class="badge text-capitalize" :class="{
        'bg-warning': row.status === 'proposed',
        'bg-primary': row.status === 'approved',
        'bg-success': row.status === 'completed',
        'bg-danger': row.status === 'cancelled',
      }">{{ row.status }}</span>
    </template>
    
    <template #cell-type="{ row }">
      <span class="text-uppercase fw-semibold">{{ (row.type || '-').replace('_', ' ') }}</span>
    </template>
    
    <template #cell-employee="{ row }">
      <span class="fw-bold text-dark">{{ row.employee?.name || '-' }}</span>
    </template>

    <template #actions="{ row, openEdit, remove }">
      <button
        @click="downloadPdf(row)"
        class="btn-action-secondary"
        title="Cetak Surat Terminasi (Export PDF)"
        :disabled="downloadingId === row.id"
      >
        <span v-if="downloadingId === row.id" class="spinner-border spinner-border-sm me-1"></span>
        <i v-else class="mdi mdi-file-pdf-box text-danger"></i>
        <span>Surat</span>
      </button>
      <button @click="openEdit(row)" class="btn-action-edit" title="Edit">
        <i class="mdi mdi-pencil-outline"></i> Edit
      </button>
      <button @click="remove(row)" class="btn-action-delete" title="Hapus">
        <i class="mdi mdi-trash-can-outline"></i> Hapus
      </button>
    </template>
  </CrudPage>
</template>

<script setup>
import { ref } from 'vue'
import CrudPage from './CrudPage.vue'
import api from '../api'

const columns = [
  { key: 'employee', label: 'Karyawan', sub: 'name' },
  { key: 'type', label: 'Tipe Terminasi' },
  { key: 'termination_date', label: 'Tanggal Terminasi' },
  { key: 'status', label: 'Status' },
]

const formFields = [
  { key: 'employee_id', label: 'Karyawan', type: 'relation', relationEndpoint: '/employees', required: true },
  { key: 'type', label: 'Tipe Terminasi', type: 'select', options: [
    { value: 'resignation', label: 'Resign' },
    { value: 'phk', label: 'PHK' },
    { value: 'contract_end', label: 'Habis Kontrak' },
    { value: 'retirement', label: 'Pensiun' },
    { value: 'termination', label: 'Pemberhentian' },
    { value: 'other', label: 'Lainnya' },
  ], required: true },
  { key: 'termination_date', label: 'Tanggal Terminasi', type: 'date', required: true },
  { key: 'reason', label: 'Alasan', type: 'textarea' },
  { key: 'status', label: 'Status', type: 'select', options: [
    { value: 'proposed', label: 'Diajukan' },
    { value: 'approved', label: 'Disetujui' },
    { value: 'completed', label: 'Selesai' },
    { value: 'cancelled', label: 'Dibatalkan' },
  ] },
]

const downloadingId = ref(null)

const downloadPdf = async (row) => {
  downloadingId.value = row.id
  try {
    const res = await api.get(`/terminations/${row.id}/pdf`, { responseType: 'blob' })
    const blob = new Blob([res.data], { type: 'application/pdf' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    const empName = row.employee?.name ? row.employee.name.replace(/\s+/g, '_') : 'Karyawan'
    link.setAttribute('download', `Surat_Terminasi_${empName}_${row.termination_date || ''}.pdf`)
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
  } catch (err) {
    alert('Gagal mencetak PDF surat terminasi. Silakan coba lagi.')
  } finally {
    downloadingId.value = null
  }
}
</script>
