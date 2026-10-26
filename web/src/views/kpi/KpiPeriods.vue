<template>
  <div>
    <CrudPage
      ref="crudRef"
      title="Periode Penilaian KPI"
      singular="Periode"
      endpoint="/kpi/periods"
      :columns="[
        { key: 'name', label: 'Nama Periode' },
        { key: 'type', label: 'Fase Periode' },
        { key: 'department_group', label: 'Divisi / Departemen' },
        { key: 'start_date', label: 'Mulai' },
        { key: 'end_date', label: 'Akhir' },
        { key: 'assessments_count', label: 'Progres Penilaian' },
        { key: 'status', label: 'Status' },
      ]"
      :form-fields="formFields"
      :filters="[
        { key: 'type', label: 'Fase', options: [
          { value: 'daily', label: 'Harian (Daily)' },
          { value: 'weekly', label: 'Mingguan (Weekly)' },
          { value: 'monthly', label: 'Bulanan (Monthly)' },
          { value: 'quarterly', label: 'Kuartalan (Quarterly)' },
          { value: 'semesterly', label: 'Semesteran' },
          { value: 'annual', label: 'Tahunan' },
        ]},
        { key: 'department_group', label: 'Departemen', options: [
          { value: 'all', label: 'Semua Departemen' },
          { value: 'FAT', label: 'FAT (Finance, Acc & Tax)' },
          { value: 'HRGA', label: 'HRGA (HR & GA)' },
          { value: 'Sales', label: 'Sales & Marketing' },
          { value: 'Operasional', label: 'Operasional & Gudang' },
        ]},
        { key: 'status', label: 'Status', options: [{ value: 'draft', label: 'Draft' }, { value: 'active', label: 'Aktif' }, { value: 'closed', label: 'Ditutup' }] },
      ]"
    >
      <template #cell-type="{ value }">
        <span class="badge font-size-11" :class="phaseBadge(value)">
          {{ phaseLabel(value) }}
        </span>
      </template>

      <template #cell-department_group="{ value }">
        <span class="badge font-size-11" :class="deptBadge(value)">
          {{ deptLabel(value) }}
        </span>
      </template>

      <template #cell-assessments_count="{ value, row }">
        <span 
          class="badge font-size-11 px-2.5 py-1 rounded-pill"
          :class="row.assessments_count > 0 && row.completed_assessments_count === row.assessments_count ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-primary-subtle text-primary border border-primary-subtle'"
        >
          <i class="mdi me-1" :class="row.assessments_count > 0 && row.completed_assessments_count === row.assessments_count ? 'mdi-check-all' : 'mdi-clipboard-text-clock-outline'"></i>
          {{ row.assessments_count ?? 0 }} Penilaian
          <span v-if="row.assessments_count > 0" class="ms-1 fw-bold">
            ({{ row.completed_assessments_count ?? 0 }}/{{ row.assessments_count }})
          </span>
        </span>
      </template>

      <template #cell-status="{ value }">
        <span class="badge" :class="{ 'bg-secondary': value === 'draft', 'bg-success': value === 'active', 'bg-dark': value === 'closed' }">
          {{ { draft: 'Draft', active: 'Aktif', closed: 'Ditutup' }[value] || value }}
        </span>
      </template>

      <template #actions="{ row, openEdit, remove }">
        <router-link 
          :to="`/hrms/kpi/periods/${row.id}/assessments`" 
          class="btn-action-view text-decoration-none" 
          title="Buka Lembar Penilaian KPI Karyawan di Periode Ini"
        >
          <i class="mdi mdi-clipboard-edit-outline"></i> Penilaian
        </router-link>
        <router-link 
          :to="`/hrms/kpi/reports?period_id=${row.id}`" 
          class="btn-action-success text-decoration-none" 
          title="Lihat Rekap Hasil Penilaian Periode Ini"
        >
          <i class="mdi mdi-chart-box-outline"></i> Rekap Nilai
        </router-link>
        <button @click="openEdit(row)" class="btn-action-edit" title="Edit Periode">
          <i class="mdi mdi-pencil-outline"></i> Edit
        </button>
        <button @click="remove(row)" class="btn-action-delete" title="Hapus Periode">
          <i class="mdi mdi-trash-can-outline"></i> Hapus
        </button>
      </template>
    </CrudPage>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useAuthStore } from '../../stores/auth'
import CrudPage from '../../components/CrudPage.vue'
import api from '../../api'
import { toast } from 'vue3-toastify'
import 'vue3-toastify/dist/index.css'

const auth = useAuthStore()
const crudRef = ref(null)
const generatingId = ref(null)


const formatDate = (dateStr) => {
  if (!dateStr) return '-'
  try {
    const d = new Date(dateStr)
    return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
  } catch {
    return dateStr
  }
}

const phaseLabel = (val) => ({
  daily: 'Harian (Daily)',
  weekly: 'Mingguan (Weekly)',
  monthly: 'Bulanan (Monthly)',
  quarterly: 'Kuartalan (Quarterly)',
  semesterly: 'Semesteran',
  annual: 'Tahunan'
}[val] || val || 'Bulanan')

const phaseBadge = (val) => ({
  daily: 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
  weekly: 'bg-info-subtle text-info border border-info-subtle',
  monthly: 'bg-primary-subtle text-primary border border-primary-subtle',
  quarterly: 'bg-dark-subtle text-dark border border-dark-subtle',
  semesterly: 'bg-secondary-subtle text-secondary border',
  annual: 'bg-danger-subtle text-danger border'
}[val] || 'bg-light text-muted border')

const deptLabel = (val) => ({
  all: 'Semua Departemen',
  FAT: 'FAT (Finance, Acc & Tax)',
  HRGA: 'HRGA (HR & GA)',
  Sales: 'Sales & Marketing',
  Operasional: 'Operasional & Gudang'
}[val] || val || 'Semua Departemen')

const deptBadge = (val) => {
  const v = String(val || '').toUpperCase()
  if (v === 'FAT') return 'bg-info-subtle text-info border border-info-subtle'
  if (v === 'HRGA') return 'bg-purple-subtle text-purple border'
  if (v === 'SALES') return 'bg-success-subtle text-success border border-success-subtle'
  if (v === 'OPERASIONAL') return 'bg-warning-subtle text-warning-emphasis border border-warning-subtle'
  return 'bg-light text-secondary border'
}

const formFields = [
  { key: 'name', label: 'Nama Periode Penilaian', required: true },
  { key: 'type', label: 'Fase Periode', type: 'select', options: [
    { value: 'daily', label: 'Harian (Daily)' },
    { value: 'weekly', label: 'Mingguan (Weekly)' },
    { value: 'monthly', label: 'Bulanan (Monthly)' },
    { value: 'quarterly', label: 'Kuartalan (Quarterly)' },
    { value: 'semesterly', label: 'Semesteran' },
    { value: 'annual', label: 'Tahunan' },
  ], default: 'monthly'},
  { key: 'department_group', label: 'Target Divisi / Departemen', type: 'select', options: [
    { value: 'all', label: 'Semua Departemen (All)' },
    { value: 'FAT', label: 'FAT (Finance, Accounting & Tax)' },
    { value: 'HRGA', label: 'HRGA (HR & General Affairs)' },
    { value: 'Sales', label: 'Sales & Marketing' },
    { value: 'Operasional', label: 'Operasional (Gudang, Bengkel, Toko, Logistik)' },
  ], default: 'all'},
  { key: 'start_date', label: 'Tanggal Mulai', type: 'date', required: true },
  { key: 'end_date', label: 'Tanggal Akhir', type: 'date', required: true },
  { key: 'review_start_date', label: 'Mulai Penilaian', type: 'date' },
  { key: 'review_end_date', label: 'Akhir Penilaian', type: 'date' },
  { key: 'status', label: 'Status', type: 'select', options: [
    { value: 'draft', label: 'Draft' },
    { value: 'active', label: 'Aktif' },
    { value: 'closed', label: 'Ditutup' },
  ], default: 'active'},
  { key: 'notes', label: 'Catatan / Deskripsi Periode', type: 'textarea' },
]

const generateAssessments = async (period) => {
  const targetMsg = auth.isSpv 
    ? 'seluruh anak buah langsung Anda'
    : (period.department_group && period.department_group !== 'all' ? `khusus divisi ${period.department_group}` : 'seluruh karyawan aktif')
  
  if (!confirm(`Generate assessment untuk ${targetMsg} di periode "${period.name}"? Sistem otomatis menyiapkan lembar penilaian dan nomor transaksi sesuai frekuensi periode ini.`)) return
  generatingId.value = period.id
  try {
    const { data } = await api.post(`/kpi/periods/${period.id}/generate-assessments`, {
      only_subordinates: auth.isSpv ? 1 : 0
    })
    toast.success(data.message)
    // Refresh table and automatically open transactions modal
    crudRef.value?.fetchData()
    await openTransactionsModal(period)
  } catch (e) {
    toast.error(e.response?.data?.message || 'Gagal generate assessment')
  } finally {
    generatingId.value = null
  }
}
</script>
