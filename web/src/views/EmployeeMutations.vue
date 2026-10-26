<template>
  <div>
    <CrudPage
      title="Mutasi & Promosi"
      singular="Surat Mutasi"
      endpoint="/employee-mutations"
      :creatable="false"
      :exportable="true"
      :importable="false"
      :columns="[
        { key: 'memo_number', label: 'No. Memo' },
        { key: 'type', label: 'Tipe' },
        { key: 'employee', label: 'Karyawan' },
        { key: 'old_placement', label: 'Penempatan Asal' },
        { key: 'new_placement', label: 'Penempatan Baru' },
        { key: 'effective_date', label: 'Tgl Efektif' },
        { key: 'status', label: 'Status' },
      ]"
      :filters="[
        { key: 'type', label: 'Filter Tipe', options: [{value: 'mutasi', label: 'Mutasi'}, {value: 'promosi', label: 'Promosi'}, {value: 'demosi', label: 'Demosi'}] },
        { key: 'status', label: 'Filter Status', options: [{value: 'pending', label: 'Pending'}, {value: 'approved', label: 'Approved'}, {value: 'rejected', label: 'Rejected'}] }
      ]"
      ref="crudPageRef"
    >
      <template #header-actions>
        <router-link to="/hrms/mutations/create" class="btn btn-primary px-4 waves-effect waves-light shadow-sm" style="border-radius:6px;">
          <i class="mdi mdi-plus me-1"></i> Buat Surat Mutasi
        </router-link>
      </template>

      <template #cell-memo_number="{ row }">
        <span class="badge bg-light text-dark border font-mono fw-bold px-2 py-1">
          {{ row.memo_number || '-' }}
        </span>
      </template>

      <template #cell-employee="{ row }">
        <div class="fw-semibold text-dark">{{ row.employee?.name || (row.employee?.first_name + ' ' + (row.employee?.last_name || '')) }}</div>
        <div class="text-muted small font-mono">{{ row.employee?.employee_id || row.employee?.nik || '-' }}</div>
      </template>
      
      <template #cell-type="{ row }">
        <span 
          class="badge px-2.5 py-1 text-uppercase fw-semibold" 
          :class="{
            'bg-success': row.type === 'promosi',
            'bg-warning text-dark': row.type === 'demosi',
            'bg-primary': row.type === 'mutasi' || !row.type
          }"
          :style="row.type !== 'demosi' ? 'color: #ffffff !important;' : ''"
        >
          {{ row.type === 'promosi' ? 'Promosi Jabatan' : (row.type === 'demosi' ? 'Demosi Level' : 'Mutasi (Transfer)') }}
        </span>
      </template>

      <template #cell-old_placement="{ row }">
        <div class="small fw-semibold text-dark">{{ row.old_position?.name || '-' }}</div>
        <div class="text-muted small">{{ row.old_company?.name || '-' }} &bull; {{ row.old_department?.name || '-' }}</div>
      </template>

      <template #cell-new_placement="{ row }">
        <div class="small fw-bold text-primary">{{ row.new_position?.name || '-' }}</div>
        <div class="text-muted small">{{ row.new_company?.name || '-' }} &bull; {{ row.new_department?.name || '-' }}</div>
        <div v-if="row.new_nip" class="mt-1">
          <span class="badge bg-info-subtle text-info border border-info-subtle font-mono font-size-11">
            <i class="mdi mdi-card-account-details-outline me-1"></i>NIP Baru: {{ row.new_nip }}
          </span>
        </div>
      </template>

      <template #cell-effective_date="{ row }">
        <div class="small text-nowrap fw-medium text-dark">{{ row.effective_date || '-' }}</div>
      </template>
      
      <template #cell-status="{ row }">
        <span class="badge" :class="{
          'bg-warning text-dark': row.status === 'pending',
          'bg-success': row.status === 'approved',
          'bg-danger': row.status === 'rejected',
        }">{{ row.status?.toUpperCase() }}</span>
      </template>

      <template #actions="{ row, remove }">
        <button @click="downloadPdf(row.id)" class="btn-action-secondary" title="Download PDF Surat Mutasi">
          <i class="mdi mdi-file-pdf-box text-danger"></i> PDF
        </button>
        <button @click="remove(row)" class="btn-action-delete" title="Hapus Mutasi">
          <i class="mdi mdi-trash-can-outline"></i> Hapus
        </button>
      </template>
    </CrudPage>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { toast } from 'vue3-toastify'
import api from '../api'
import CrudPage from '../components/CrudPage.vue'

const crudPageRef = ref(null)

const downloadPdf = async (id) => {
  try {
    toast.info('Menyiapkan file PDF...')
    const res = await api.get(`/employee-mutations/${id}/pdf`, { responseType: 'blob' })
    const file = new Blob([res.data], { type: 'application/pdf' })
    const fileURL = URL.createObjectURL(file)
    window.open(fileURL, '_blank')
  } catch (err) {
    console.error('Gagal mengambil blob PDF, fallback ke direct URL:', err)
    const baseUrl = import.meta.env.VITE_API_URL || 'http://127.0.0.1:8011/api/v1'
    window.open(`${baseUrl}/employee-mutations/${id}/pdf`, '_blank')
  }
}
</script>
