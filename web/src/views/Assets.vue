<template>
  <CrudPage
    title="Manajemen Aset & Inventaris"
    singular="Aset"
    endpoint="/assets"
    modal-size="modal-lg"
    :columns="[
      { key: 'inventory_number', label: 'Kode Aset / No. Inventaris' },
      { key: 'name', label: 'Nama Aset & Kategori' },
      { key: 'quantity', label: 'Jumlah / Stok', width: '130px' },
      { key: 'company', label: 'Perusahaan' },
      { key: 'branch', label: 'Cabang' },
      { key: 'status', label: 'Status Stok' },
      { key: 'employee', label: 'Status Distribusi (Pemegang)' },
      { key: 'serial_number', label: 'Nomor Seri & Pembelian' }
    ]"
    :form-fields="[
      { key: 'company_id', label: 'Perusahaan', type: 'relation', relationEndpoint: '/companies', required: true, colClass: 'col-md-6' },
      { key: 'branch_id', label: 'Cabang Penempatan', type: 'relation', relationEndpoint: '/branches', dependsOn: 'company_id', colClass: 'col-md-6' },
      { key: 'type', label: 'Jenis / Kategori Aset', type: 'relation', relationEndpoint: '/asset-types', required: true, colClass: 'col-md-6' },
      { key: 'inventory_number', label: 'Kode Aset / No. Inventaris', placeholder: 'Auto-generated jika dikosongkan', colClass: 'col-md-6' },
      { key: 'name', label: 'Nama Aset / Barang', required: true, placeholder: 'Contoh: Laptop Lenovo ThinkPad / Kertas HVS A4', colClass: 'col-md-6' },
      { key: 'quantity', label: 'Jumlah / Kuantitas', type: 'number', required: true, colClass: 'col-md-3' },
      { key: 'unit', label: 'Satuan', type: 'select', required: true, colClass: 'col-md-3', options: [
        { value: 'Unit', label: 'Unit' },
        { value: 'Pcs', label: 'Pcs' },
        { value: 'Rim', label: 'Rim' },
        { value: 'Box', label: 'Box' },
        { value: 'Pack', label: 'Pack' },
        { value: 'Set', label: 'Set' },
        { value: 'Roll', label: 'Roll' },
        { value: 'Lusin', label: 'Lusin' },
      ]},
      { key: 'serial_number', label: 'Nomor Seri / SN (Opsional)', placeholder: 'Nomor seri perangkat fisik (kosongkan jika ATK)', colClass: 'col-md-6' },
      { key: 'purchase_date', label: 'Tanggal Pembelian', type: 'date', colClass: 'col-md-6' },
      { key: 'status', label: 'Status Stok / Ketersediaan', type: 'select', required: true, colClass: 'col-md-6', options: [
        { value: 'Tersedia', label: 'Tersedia (Stok di Gudang / Kantor)' },
        { value: 'Dipinjamkan', label: 'Didistribusikan (Dipinjamkan ke Karyawan)' },
        { value: 'Pemeliharaan', label: 'Dalam Pemeliharaan / Perbaikan' },
      ]},
      { key: 'employee_id', label: 'Karyawan Pemegang (Distribusi)', type: 'relation', relationEndpoint: '/employees', colClass: 'col-md-6', showIf: (f) => f.status === 'Dipinjamkan' },
      { key: 'notes', label: 'Catatan / Spesifikasi Aset', type: 'textarea', colClass: 'col-12' },
    ]"
    :filters="[
      { key: 'status', label: 'Status', options: [
        { value: 'Tersedia', label: 'Tersedia (Gudang)' },
        { value: 'Dipinjamkan', label: 'Didistribusikan' },
        { value: 'Pemeliharaan', label: 'Pemeliharaan' },
      ]},
      { key: 'type', label: 'Jenis Aset', relationEndpoint: '/asset-types' },
      { key: 'company_id', label: 'Perusahaan', relationEndpoint: '/companies' },
      { key: 'branch_id', label: 'Cabang', relationEndpoint: '/branches' },
    ]"
    :initial-form="() => ({ quantity: 1, unit: 'Unit', status: 'Tersedia', purchase_date: new Date().toISOString().substring(0, 10) })"
  >

    <template #cell-inventory_number="{ value }">
      <span class="badge bg-light text-dark border font-mono px-2 py-1">{{ value || '-' }}</span>
    </template>

    <template #cell-name="{ value, row }">
      <div class="fw-bold text-dark">{{ value }}</div>
      <small class="text-muted font-size-11">Kategori: {{ row.asset_type?.name || row.type || '-' }}</small>
    </template>

    <template #cell-quantity="{ value, row }">
      <span class="badge bg-light text-dark border px-2 py-1 fw-bold fs-6">{{ value ?? 1 }}</span>
      <div class="text-secondary small font-size-11 mt-1">{{ row.unit || 'Unit' }}</div>
    </template>

    <template #cell-company="{ row }">
      <span class="fw-medium text-dark">{{ row.company?.name || '-' }}</span>
    </template>

    <template #cell-branch="{ row }">
      <span>{{ row.branch?.name || '-' }}</span>
    </template>

    <template #cell-status="{ value }">
      <span v-if="value === 'Tersedia'" class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
        Tersedia (Gudang)
      </span>
      <span v-else-if="value === 'Dipinjamkan'" class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
        Didistribusikan
      </span>
      <span v-else-if="value === 'Pemeliharaan'" class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1">
        Pemeliharaan
      </span>
      <span v-else class="badge bg-light text-secondary border px-2 py-1">
        {{ value || '-' }}
      </span>
    </template>

    <template #cell-employee="{ row }">
      <div v-if="row.status === 'Dipinjamkan' && row.employee" class="text-dark small fw-semibold">
        {{ row.employee.name }}
      </div>
      <div v-else-if="row.status === 'Tersedia'" class="text-success small fw-medium">
        Tersedia di Gudang / Kantor
      </div>
      <div v-else class="text-muted small">
        {{ row.status === 'Pemeliharaan' ? 'Dalam servis/perbaikan' : '-' }}
      </div>
    </template>

    <template #cell-serial_number="{ value, row }">
      <div class="text-dark small">{{ value ? 'SN: ' + value : '-' }}</div>
      <small v-if="row.purchase_date" class="text-muted">Beli: {{ row.purchase_date }}</small>
    </template>

    <template #actions="{ row, openEdit, remove }">
      <button type="button" @click="openEdit(row)" class="btn-action-edit" title="Edit Aset">
        <i class="mdi mdi-pencil-outline"></i> Edit
      </button>
      <button type="button" @click="remove(row)" class="btn-action-delete" title="Hapus Aset">
        <i class="mdi mdi-trash-can-outline"></i> Hapus
      </button>
    </template>
  </CrudPage>
</template>

<script setup>
import CrudPage from '../components/CrudPage.vue'
</script>
