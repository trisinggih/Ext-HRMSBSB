<template>
  <CrudPage
    title="Karyawan (Employee)"
    singular="Karyawan"
    endpoint="/employees"
    :columns="[
      { key: 'photo', label: 'Foto', type: 'image', width: '70px' },
      { key: 'employee_id', label: 'NIP', hidden: true },
      { key: 'name', label: 'Nama' },
      { key: 'company', label: 'Perusahaan (Legal)' },
      { key: 'branch', label: 'Area Penempatan' },
      { key: 'department', label: 'Departemen' },
      { key: 'position', label: 'Jabatan' },
      { key: 'phone', label: 'Nomor HP' },
      { key: 'email', label: 'Email' },
      { key: 'is_active', label: 'Status' }
    ]"
    :form-fields="[
      { key: 'photo', label: 'Foto Profil', type: 'file', accept: 'image/*' },
      { key: 'employee_id', label: 'NIP', required: true },
      { key: 'name', label: 'Nama', required: true },
      { key: 'email', label: 'Email', type: 'email', required: true },
      { key: 'password', label: 'Password (kosongkan utk default)', type: 'password' },
      { key: 'phone', label: 'Telepon' },
      { key: 'assignments', label: 'Assignments', type: 'custom' },
      { key: 'is_active', label: 'Status Login', type: 'select', options: [
        { value: true, label: 'Aktif' },
        { value: false, label: 'Nonaktif' },
      ]},

    ]"
    :filters="[
      { key: 'company_id', label: 'Perusahaan (Legal)', relationEndpoint: '/companies' },
      { key: 'branch_id', label: 'Area Penempatan', relationEndpoint: '/branches' },
      { key: 'department_id', label: 'Departemen', relationEndpoint: '/departments' },
      { key: 'division_id', label: 'Divisi', relationEndpoint: '/divisions' },
      { key: 'position_id', label: 'Jabatan', relationEndpoint: '/positions' },
      { key: 'employment_status', label: 'Status Karyawan', options: [{value:'permanent', label:'Tetap'}, {value:'contract', label:'Kontrak'}, {value:'probation', label:'Probation'}, {value:'intern', label:'Magang'}] }
    ]"
    :initial-form="() => ({ is_daily_worker: 0, assignments: [{ employment_status: 'permanent', is_primary: true, placement_company_id: null }] })"
    :creatable="false"
    :export-fields="employeeExportFields"
    export-filename="Data_Karyawan"
  >
    <template #header-actions>
      <div class="d-flex align-items-center gap-2">
        <router-link to="/hrms/employees/create" class="btn btn-action-add px-3 py-2 waves-effect waves-light shadow-sm" style="border-radius:7px; font-size: 0.85rem;">
          <i class="mdi mdi-plus-circle-outline font-size-15 me-1"></i> Tambah Karyawan
        </router-link>
      </div>
    </template>

    <template #cell-photo="{ value, row }">
      <img :src="value ? (value.startsWith('http') ? value : 'http://localhost:8011/storage/' + value) : 'https://ui-avatars.com/api/?name=' + encodeURIComponent(row.name || 'User') + '&background=random'" 
           @error="e => e.target.src = 'https://ui-avatars.com/api/?name=' + encodeURIComponent(row.name || 'User') + '&background=random'"
           class="rounded-circle img-thumbnail shadow-sm" 
           style="width: 45px; height: 45px; object-fit: cover;" 
           alt="avatar" />
    </template>

    <template #cell-employee_id="{ value, row }">
      <span class="badge bg-light text-dark border font-mono px-2 py-1">{{ value || row.nip || '-' }}</span>
    </template>

    <template #cell-name="{ value, row }">
      <div class="fw-bold text-dark">{{ value }}</div>
      <small class="text-muted font-size-11">NIP: {{ row.employee_id || row.nip || '-' }}</small>
    </template>

    <template #cell-company="{ row }">
      <span class="fw-medium text-dark">{{ row.assignments?.[0]?.company?.name || '-' }}</span>
    </template>
    
    <template #cell-is_active="{ value }">
      <span class="badge" :class="value ? 'bg-success' : 'bg-danger'">
        {{ value ? 'Aktif' : 'Nonaktif' }}
      </span>
    </template>

    <template #cell-branch="{ row }">
      <div class="fw-medium text-dark">{{ row.assignments?.[0]?.branch?.name || '-' }}</div>
      <div v-if="row.assignments?.[0]?.branch?.company?.name" class="text-muted font-size-11">
        ({{ row.assignments?.[0]?.branch?.company?.name }})
      </div>
      <div v-if="row.assignments?.[0]?.placement_company && row.assignments?.[0]?.placement_company_id !== row.assignments?.[0]?.company_id" class="mt-1">
        <span class="badge bg-warning-subtle text-dark border" style="font-size: 0.7rem;">
          <i class="mdi mdi-map-marker-radius me-1"></i>Penempatan: {{ row.assignments?.[0]?.placement_company?.name }}
        </span>
      </div>
    </template>

    <template #cell-department="{ row }">
      <span class="fw-medium text-dark">{{ row.assignments?.[0]?.department?.name || '-' }}</span>
    </template>

    <template #cell-position="{ row }">
      {{ row.assignments?.[0]?.position?.name || '-' }}
    </template>

    <template #actions="{ row }">
      <router-link :to="`/hrms/employees/${row.id}`" class="btn-action-detail" title="Detail Karyawan">
        <i class="mdi mdi-eye-outline"></i> Detail
      </router-link>
    </template>
  </CrudPage>
</template>

<script setup>
import CrudPage from '../components/CrudPage.vue'

const employeeExportFields = [
  // 1. Identitas Karyawan
  { key: 'employee_id', label: 'NIP / ID Karyawan', category: 'Identitas Karyawan', default: true },
  { key: 'name', label: 'Nama Lengkap', category: 'Identitas Karyawan', default: true },
  { key: 'nickname', label: 'Nama Panggilan', category: 'Identitas Karyawan', default: false },
  { key: 'gender', label: 'Jenis Kelamin', category: 'Identitas Karyawan', default: true },
  { key: 'phone', label: 'Nomor HP / Telepon', category: 'Identitas Karyawan', default: true },
  { key: 'email', label: 'Email', category: 'Identitas Karyawan', default: true },
  { key: 'birth_date', label: 'Tanggal Lahir', category: 'Identitas Karyawan', default: false },
  { key: 'marital_status', label: 'Status Pernikahan', category: 'Identitas Karyawan', default: false },
  { key: 'is_active', label: 'Status Akun (Aktif/Nonaktif)', category: 'Identitas Karyawan', default: true },

  // 2. Penempatan & Struktur Organisasi
  { key: 'assignments.0.company.name', label: 'Perusahaan (PT)', category: 'Penempatan & Organisasi', default: true },
  { key: 'assignments.0.placement_company.name', label: 'Perusahaan Penempatan', category: 'Penempatan & Organisasi', default: false },
  { key: 'assignments.0.branch.name', label: 'Cabang', category: 'Penempatan & Organisasi', default: true },
  { key: 'assignments.0.department.name', label: 'Departemen', category: 'Penempatan & Organisasi', default: true },
  { key: 'assignments.0.division.name', label: 'Divisi', category: 'Penempatan & Organisasi', default: true },
  { key: 'assignments.0.position.name', label: 'Jabatan', category: 'Penempatan & Organisasi', default: true },
  { key: 'assignments.0.supervisor.name', label: 'Atasan Langsung', category: 'Penempatan & Organisasi', default: false },
  { key: 'assignments.0.join_date', label: 'Tanggal Bergabung (Join Date)', category: 'Penempatan & Organisasi', default: true },
  { key: 'assignments.0.employment_status', label: 'Status Kepegawaian', category: 'Penempatan & Organisasi', default: true },
  { key: 'assignments.0.salary_cycle_type', label: 'Siklus Penggajian', category: 'Penempatan & Organisasi', default: false },
  { key: 'is_daily_worker', label: 'Pekerja Harian Lepas', category: 'Penempatan & Organisasi', default: false },
  { key: 'can_clock_in_anywhere', label: 'Bebas Absen Dimana Saja', category: 'Penempatan & Organisasi', default: false },

  // 3. Administrasi, Pajak & Legalitas
  { key: 'ktp_number', label: 'Nomor KTP (NIK)', category: 'Administrasi & Pajak', default: false },
  { key: 'npwp', label: 'Nomor NPWP', category: 'Administrasi & Pajak', default: false },
  { key: 'ptkp_status', label: 'Status PTKP', category: 'Administrasi & Pajak', default: false },
  { key: 'bpjs_kesehatan', label: 'No. BPJS Kesehatan', category: 'Administrasi & Pajak', default: false },
  { key: 'bpjs_ketenagakerjaan', label: 'No. BPJS Ketenagakerjaan', category: 'Administrasi & Pajak', default: false },
  { key: 'address', label: 'Alamat KTP', category: 'Administrasi & Pajak', default: false },
  { key: 'domicile_address', label: 'Alamat Domisili', category: 'Administrasi & Pajak', default: false },
]
</script>

