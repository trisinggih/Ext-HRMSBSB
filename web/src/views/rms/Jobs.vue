<template>
  <CrudPage
    title="Lowongan (RMS)"
    singular="Lowongan"
    endpoint="/recruitment/admin/jobs"
    :columns="columns"
    :formFields="formFields"
    :filters="filters"
    :initialForm="initialForm"
  >
    <template #cell-position="{ row }">
      <span v-if="row.position">{{ row.position.name }}</span>
      <span v-if="row.department" class="text-muted"> &bull; {{ row.department.name }}</span>
      <span v-if="!row.position && !row.department">-</span>
    </template>

    <template #cell-vacancy_quota="{ value }">
      {{ value ?? '-' }}
    </template>

    <template #cell-salary="{ row }">
      {{ fmtSalary(row) }}
    </template>

    <template #cell-status="{ value }">
      <span :class="['badge font-size-12', statusClass(value)]">{{ value }}</span>
    </template>

    <!-- Custom Actions Slot -->
    <template #actions="{ row, openEdit, remove }">
      <router-link :to="`/karir/jobs/${row.id}`" target="_blank" class="btn-action-detail" title="Lihat Lowongan">
        <i class="mdi mdi-eye-outline"></i> Detail
      </router-link>
      <button @click="openEdit(row)" class="btn-action-edit" title="Edit Lowongan">
        <i class="mdi mdi-pencil-outline"></i> Edit
      </button>
      <button @click="remove(row)" class="btn-action-delete" title="Hapus Lowongan">
        <i class="mdi mdi-trash-can-outline"></i> Hapus
      </button>
    </template>
  </CrudPage>
</template>

<script setup>
import CrudPage from '../../components/CrudPage.vue'

const columns = [
  { key: 'code', label: 'Kode' },
  { key: 'title', label: 'Judul' },
  { key: 'position', label: 'Posisi / Dept' },
  { key: 'vacancy_quota', label: 'Kuota' },
  { key: 'salary', label: 'Gaji' },
  { key: 'application_deadline', label: 'Deadline' },
  { key: 'status', label: 'Status' }
]

const filters = [
  { key: 'status', label: 'Status', options: [
    { value: 'open', label: 'Open' },
    { value: 'draft', label: 'Draft' },
    { value: 'closed', label: 'Closed' }
  ]}
]

const formFields = [
  { key: 'code', label: 'Kode Lowongan', type: 'text', required: true },
  { key: 'title', label: 'Judul', type: 'text', required: true },
  { key: 'company_id', label: 'Perusahaan', type: 'relation', relationEndpoint: '/companies' },
  { key: 'branch_id', label: 'Cabang', type: 'relation', relationEndpoint: '/branches' },
  { key: 'department_id', label: 'Departemen', type: 'relation', relationEndpoint: '/departments' },
  { key: 'position_id', label: 'Jabatan', type: 'relation', relationEndpoint: '/positions' },
  { key: 'vacancy_quota', label: 'Kuota', type: 'number' },
  { key: 'salary_min', label: 'Gaji Min', type: 'number' },
  { key: 'salary_max', label: 'Gaji Max', type: 'number' },
  { key: 'status', label: 'Status', type: 'select', options: [
      { value: 'draft', label: 'Draft' },
      { value: 'open', label: 'Open' },
      { value: 'closed', label: 'Closed' }
  ]},
  { key: 'application_deadline', label: 'Deadline Lamaran', type: 'date' },
  { key: 'description', label: 'Deskripsi', type: 'textarea' },
  { key: 'requirements', label: 'Persyaratan', type: 'textarea' }
]

const initialForm = () => ({
  code: '', title: '', company_id: '', branch_id: '', department_id: '', position_id: '',
  vacancy_quota: 1, description: '', requirements: '', salary_min: null, salary_max: null,
  status: 'draft', application_deadline: '',
})

const statusClass = (s) => ({
  draft: 'bg-secondary',
  open: 'bg-success',
  closed: 'bg-danger',
}[s] || 'bg-secondary')

const fmtSalary = (j) => {
  if (j.salary_min == null && j.salary_max == null) return '-'
  const f = (n) => n == null ? '' : new Intl.NumberFormat('id-ID').format(n)
  if (j.salary_min != null && j.salary_max != null) return `Rp${f(j.salary_min)} - ${f(j.salary_max)}`
  return `Rp${f(j.salary_min ?? j.salary_max)}`
}
</script>
