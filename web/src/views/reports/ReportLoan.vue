<template>
  <div class="row">
    <div class="col-12">
      <div class="page-title-box d-sm-flex align-items-center justify-content-between">
        <h4 class="mb-sm-0">Laporan Kasbon Karyawan</h4>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <!-- Filter Section -->
      <div class="row mb-4 align-items-end">
        <div class="col-md-3">
          <label class="form-label">Status Pinjaman</label>
          <select v-model="filters.status" class="form-select">
            <option value="">Semua Status</option>
            <option value="active">Aktif / Belum Lunas</option>
            <option value="paid">Lunas</option>
          </select>
        </div>
        <div class="col-md-9 text-end">
          <button class="btn btn-primary me-2" @click="fetchData" :disabled="loading">
            <i class="mdi mdi-filter"></i> Tampilkan
          </button>
          <button class="btn btn-danger" @click="downloadPdf" :disabled="loading || data.length === 0">
            <i class="mdi mdi-file-pdf-box"></i> Cetak PDF
          </button>
        </div>
      </div>

      <!-- Table Section -->
      <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle">
          <thead class="table-light">
            <tr>
              <th width="50">No</th>
              <th>Nama Karyawan</th>
              <th>Departemen</th>
              <th class="text-end">Total Pinjaman</th>
              <th class="text-end">Cicilan/Bulan</th>
              <th class="text-end">Sisa Saldo</th>
              <th class="text-center">Status</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="7" class="text-center py-4">
                <div class="spinner-border text-primary" role="status"></div>
              </td>
            </tr>
            <tr v-else-if="data.length === 0">
              <td colspan="7" class="text-center py-4 text-muted">Tidak ada data kasbon/pinjaman</td>
            </tr>
            <tr v-else v-for="(item, index) in data" :key="item.id">
              <td class="text-center">{{ index + 1 }}</td>
              <td class="fw-bold">{{ item.employee?.name }}</td>
              <td>{{ item.employee?.department?.name || '-' }}</td>
              <td class="text-end">{{ money(item.total_loan) }}</td>
              <td class="text-end">{{ money(item.installment_amount) }}</td>
              <td class="text-end fw-bold text-danger">{{ money(item.remaining_balance) }}</td>
              <td class="text-center">
                <span class="badge" :class="item.status === 'paid' ? 'bg-success' : 'bg-warning'">
                  {{ item.status === 'paid' ? 'Lunas' : 'Belum Lunas' }}
                </span>
              </td>
            </tr>
          </tbody>
          <tfoot v-if="data.length > 0" class="table-light">
            <tr>
              <th colspan="3" class="text-end">TOTAL KESELURUHAN</th>
              <th class="text-end">{{ money(totalLoanSum) }}</th>
              <th class="text-end">-</th>
              <th class="text-end fw-bold text-danger">{{ money(totalBalanceSum) }}</th>
              <th></th>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../../api'

const filters = ref({
  status: 'active'
})
const data = ref([])
const loading = ref(false)

const money = (val) => {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(val || 0)
}

const totalLoanSum = computed(() => data.value.reduce((sum, item) => sum + Number(item.total_loan || 0), 0))
const totalBalanceSum = computed(() => data.value.reduce((sum, item) => sum + Number(item.remaining_balance || 0), 0))

const fetchData = async () => {
  loading.value = true
  try {
    const params = new URLSearchParams()
    if (filters.value.status) params.append('status', filters.value.status)
    
    const res = await api.get(`/reports/loans?${params.toString()}`)
    data.value = res.data.data
  } catch (e) {
    alert('Gagal mengambil data laporan')
  } finally {
    loading.value = false
  }
}

const downloadPdf = () => {
  const params = new URLSearchParams()
  params.append('type', 'loan')
  if (filters.value.status) params.append('status', filters.value.status)
  const token = localStorage.getItem('hrms_token')
  params.append('token', token)

  window.open(`${import.meta.env.VITE_API_URL}/reports/export-pdf?${params.toString()}`, '_blank')
}

onMounted(() => {
  fetchData()
})
</script>
