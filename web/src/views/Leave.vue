<template>
  <div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4 align-items-end">
      <div class="col-md-8">
        <h4 class="card-title mb-1 font-size-22 fw-bold">Cuti & Izin Saya</h4>
        <p class="text-muted mb-0 font-size-14">Lihat saldo dan riwayat pengajuan cuti Anda (Pengajuan baru hanya via Aplikasi Mobile).</p>
      </div>
      <div class="col-md-4 text-md-end mt-3 mt-md-0">
        <button @click="loadAll" class="btn btn-outline-secondary btn-sm rounded-pill px-3 shadow-sm">
          <i class="mdi mdi-refresh me-1"></i> Refresh Data
        </button>
      </div>
    </div>

    <!-- Alerts -->
    <div v-if="error" class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
      <i class="mdi mdi-alert-circle-outline me-2"></i> {{ error }}
      <button type="button" class="btn-close" @click="error = ''"></button>
    </div>

    <div class="row">
      <!-- Balances -->
      <div class="col-xl-4 mb-4">
        <div class="card shadow border-0 h-100">
          <div class="card-header bg-transparent border-bottom-0 pt-4 pb-0 d-flex align-items-center">
            <div class="avatar-sm me-3">
              <span class="avatar-title rounded-circle bg-primary-subtle text-primary font-size-22 shadow-sm">
                <i class="mdi mdi-wallet-outline"></i>
              </span>
            </div>
            <div>
              <h5 class="card-title mb-0">Saldo Cuti Anda</h5>
              <p class="text-muted mb-0 font-size-13">Tahun {{ currentYear }}</p>
            </div>
          </div>
          <div class="card-body mt-3">
            <div class="table-responsive">
              <table class="table align-middle table-nowrap table-hover mb-0">
                <thead class="table-light">
                  <tr class="text-muted font-size-12 text-uppercase">
                    <th>Tipe Cuti</th>
                    <th class="text-center">Sisa</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="b in balances" :key="b.id || b.leave_type_id">
                    <td>
                      <span class="fw-semibold text-dark">{{ b.leave_type?.name || b.leave_type_name || '-' }}</span>
                      <div class="text-muted font-size-12">Kuota: {{ b.quota }}</div>
                    </td>
                    <td class="text-center">
                      <span class="badge font-size-14 px-2 py-1" :class="b.remaining > 0 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'">
                        {{ b.remaining }} Hari
                      </span>
                    </td>
                  </tr>
                  <tr v-if="!balances.length">
                    <td colspan="2" class="text-center py-4 text-muted">
                      {{ balanceLoading ? 'Memuat saldo...' : 'Belum ada data saldo cuti.' }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- Riwayat Cuti -->
      <div class="col-xl-8 mb-4">
        <div class="card shadow border-0 h-100">
          <div class="card-header bg-transparent border-bottom-0 pt-4 pb-0 d-flex align-items-center">
            <div class="avatar-sm me-3">
              <span class="avatar-title rounded-circle bg-info-subtle text-info font-size-22 shadow-sm">
                <i class="mdi mdi-format-list-bulleted"></i>
              </span>
            </div>
            <h5 class="card-title mb-0">Riwayat Pengajuan</h5>
          </div>
          <div class="card-body mt-3">
            <div class="table-responsive">
              <table class="table align-middle table-nowrap table-hover mb-0">
                <thead class="table-light">
                  <tr class="text-muted font-size-12 text-uppercase">
                    <th>Tipe Cuti</th>
                    <th>Tgl Mulai</th>
                    <th>Tgl Selesai</th>
                    <th class="text-center">Durasi</th>
                    <th>Alasan</th>
                    <th class="text-end">Status</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="r in myLeaves" :key="r.id">
                    <td><span class="fw-bold text-dark">{{ r.leave_type?.name || '-' }}</span></td>
                    <td class="text-muted">{{ r.start_date }}</td>
                    <td class="text-muted">{{ r.end_date }}</td>
                    <td class="text-center"><span class="badge bg-light text-dark font-size-12 px-2 py-1 border">{{ r.total_days }} Hari</span></td>
                    <td style="max-width: 200px" class="text-truncate text-muted" :title="r.reason">{{ r.reason }}</td>
                    <td class="text-end">
                      <span class="badge font-size-12 px-3 py-1 rounded-pill" :class="statusClass(r.status)">
                        {{ statusLabel(r.status) }}
                      </span>
                    </td>
                  </tr>
                  <tr v-if="!myLeaves.length">
                    <td colspan="6" class="text-center py-5">
                      <div class="text-muted">
                        <i class="mdi mdi-folder-outline font-size-24 d-block mb-2"></i>
                        {{ leavesLoading ? 'Memuat riwayat pengajuan...' : 'Belum ada riwayat pengajuan cuti.' }}
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../api'

const error = ref('')
const currentYear = new Date().getFullYear()

// Balances
const balances = ref([])
const balanceLoading = ref(false)

// My leaves
const myLeaves = ref([])
const leavesLoading = ref(false)

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

const loadBalances = async () => {
  balanceLoading.value = true
  try {
    const { data } = await api.get('/leave/balance', { params: { year: currentYear } })
    balances.value = data.data ?? []
    balances.value = balances.value.map((b) => {
      const type = b.leave_type || { name: b.leave_type_name, code: b.leave_type_code };
      return { ...b, leave_type: type };
    });
  } catch (e) { 
    if(e.response?.status === 403) {
      error.value = "Fitur riwayat Cuti ditujukan khusus untuk Karyawan (Self-Service). Sebagai Admin Utama, Anda tidak memiliki data cuti personal."
    } else {
      error.value = e.response?.data?.message || 'Gagal memuat saldo' 
    }
  }
  finally { balanceLoading.value = false }
}

const loadMyLeaves = async () => {
  leavesLoading.value = true
  try {
    const { data } = await api.get('/leave/request', { params: { per_page: 100 } })
    myLeaves.value = data.data?.data ?? data.data ?? []
  } catch (e) { 
    if(e.response?.status !== 403) {
      error.value = e.response?.data?.message || 'Gagal memuat daftar cuti' 
    }
  }
  finally { leavesLoading.value = false }
}

const loadAll = () => {
  error.value = ''
  loadBalances()
  loadMyLeaves()
}

onMounted(loadAll)
</script>
