<template>
  <div>
    <!-- Header -->
    <div class="row mb-4 align-items-center">
      <div class="col-sm-6">
        <h4 class="mb-0 font-size-18">Kandidat (RMS)</h4>
        <p class="text-muted mt-1 mb-0">Kelola pelamar, status, & proses rekrutmen</p>
      </div>
      <div class="col-sm-6 text-sm-end mt-3 mt-sm-0">
        <a href="/karir" target="_blank" class="btn btn-secondary waves-effect waves-light">
          Buka Halaman Karir <i class="mdi mdi-arrow-right ms-1"></i>
        </a>
      </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
      <div class="card-body p-3">
        <div class="row align-items-center gap-3 gap-md-0">
          <div class="col-md-3">
            <div class="search-box">
              <div class="position-relative">
                <input type="text" v-model="filters.search" @input="debouncedLoad" class="form-control rounded bg-light border-light" placeholder="Cari nama / email...">
                <i class="mdi mdi-magnify search-icon"></i>
              </div>
            </div>
          </div>
          <div class="col-md-3 mt-3 mt-md-0">
            <select v-model="filters.status" @change="page = 1; load()" class="form-select">
              <option value="">Semua Status</option>
              <option v-for="s in allStatuses" :key="s" :value="s">{{ statusLabel(s) }}</option>
            </select>
          </div>
          <div class="col-md-3 mt-3 mt-md-0">
            <select v-model="filters.job_id" @change="page = 1; load()" class="form-select">
              <option value="">Semua Lowongan</option>
              <option v-for="j in jobs" :key="j.id" :value="j.id">{{ j.title }}</option>
            </select>
          </div>
          <div class="col-md-3 mt-3 mt-md-0">
            <select v-model="filters.verified" @change="page = 1; load()" class="form-select">
              <option value="">Semua Dokumen</option>
              <option value="1">Dokumen Terverifikasi</option>
              <option value="0">Dokumen Belum Verifikasi</option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <div v-if="error" class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
      {{ error }}
      <button type="button" class="btn-close" @click="error = ''" aria-label="Close"></button>
    </div>

    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-bordered table-nowrap table-hover mb-0 align-middle">
                <thead class="table-light">
                  <tr>
                    <th>Kandidat</th>
                    <th>Kontak</th>
                    <th>Lamaran</th>
                    <th>Dokumen</th>
                    <th>Status</th>
                    <th class="text-end">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="c in candidates" :key="c.id">
                    <td>
                      <div class="d-flex align-items-center">
                        <img v-if="c.photo" :src="c.photo" class="avatar-xs rounded-circle me-2" alt="avatar" style="object-fit: cover;">
                        <div v-else class="avatar-xs rounded-circle me-2 bg-primary-subtle text-primary d-flex align-items-center justify-content-center fw-bold">
                          {{ initials(c.name) }}
                        </div>
                        <div>
                          <h5 class="font-size-14 mb-1 text-dark">{{ c.name }}</h5>
                          <p class="text-muted mb-0 font-size-12">Bergabung {{ c.applied_date || '-' }}</p>
                        </div>
                      </div>
                    </td>
                    <td>
                      <p class="mb-0 text-dark">{{ c.email }}</p>
                      <p class="text-muted mb-0 font-size-12">{{ c.phone || '-' }}</p>
                    </td>
                    <td>{{ (c.applications || []).length }} lamaran</td>
                    <td>
                      <span :class="['badge font-size-12', docVerified(c) ? 'bg-success' : 'bg-warning']">
                        {{ docCount(c) }} &bull; {{ docVerified(c) ? 'OK' : 'Perlu cek' }}
                      </span>
                    </td>
                    <td>
                      <span :class="['badge font-size-12', statusClass(c.status)]">{{ statusLabel(c.status) }}</span>
                    </td>
                    <td class="text-end">
                      <router-link :to="`/hrms/recruitment/candidates/${c.id}`" class="btn-action-detail" title="Detail Kandidat">
                        <i class="mdi mdi-eye-outline"></i> Detail
                      </router-link>
                    </td>
                  </tr>
                  <tr v-if="!candidates.length">
                    <td colspan="6" class="text-center py-4 text-muted">
                      {{ loading ? 'Memuat data...' : 'Belum ada kandidat' }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div v-if="meta && meta.last_page > 1" class="row align-items-center mt-4">
              <div class="col-sm-6">
                <p class="text-muted mb-0">Halaman {{ meta.current_page }} dari {{ meta.last_page }}</p>
              </div>
              <div class="col-sm-6">
                <ul class="pagination pagination-sm m-0 float-sm-end">
                  <li class="page-item" :class="{ disabled: meta.current_page <= 1 }">
                    <button class="page-link" @click="page--">Prev</button>
                  </li>
                  <li class="page-item" :class="{ disabled: meta.current_page >= meta.last_page }">
                    <button class="page-link" @click="page++">Next</button>
                  </li>
                </ul>
              </div>
            </div>

          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import api from '../../api'

const candidates = ref([])
const jobs = ref([])
const meta = ref(null)
const page = ref(1)
const loading = ref(false)
const error = ref('')
const filters = reactive({ search: '', status: '', job_id: '', verified: '' })

const allStatuses = ['applied','document_review','test_invited','test_done','interview_invited','interview_done','shortlisted','offered','hired','rejected','on_hold']

const statusLabel = (s) => ({
  applied: 'Applied', document_review: 'Review Dokumen', test_invited: 'Diundang Test',
  test_done: 'Test Selesai', interview_invited: 'Diundang Interview', interview_done: 'Interview Selesai',
  shortlisted: 'Shortlisted', offered: 'Offered', hired: 'Hired', rejected: 'Rejected', on_hold: 'On Hold',
}[s] || s)

const statusClass = (s) => ({
  applied: 'bg-secondary', document_review: 'bg-info',
  test_invited: 'bg-primary', test_done: 'bg-success',
  interview_invited: 'bg-primary', interview_done: 'bg-success',
  shortlisted: 'bg-dark', offered: 'bg-warning',
  hired: 'bg-success', rejected: 'bg-danger', on_hold: 'bg-warning',
}[s] || 'bg-secondary')

let searchTimer = null
const debouncedLoad = () => { clearTimeout(searchTimer); searchTimer = setTimeout(() => { page.value = 1; load() }, 400) }

const initials = (name) => (name || '?').split(' ').slice(0, 2).map(w => w[0]).join('').toUpperCase()
const docCount = (c) => (c.documents || []).length
const docVerified = (c) => (c.documents || []).some(d => d.is_verified)

const load = async () => {
  loading.value = true; error.value = ''
  try {
    const params = {
      page: page.value,
      search: filters.search || undefined,
      status: filters.status || undefined,
      job_id: filters.job_id || undefined,
      verified: filters.verified === '' ? undefined : filters.verified,
    }
    const { data } = await api.get('/recruitment/admin/candidates', { params })
    candidates.value = data.data?.data ?? data.data ?? []
    meta.value = data.data?.meta ?? data.meta ?? null
  } catch (e) { error.value = e.response?.data?.message || 'Gagal memuat data' }
  finally { loading.value = false }
}

const loadJobs = async () => {
  try {
    const { data } = await api.get('/recruitment/admin/jobs', { params: { per_page: 100 } })
    jobs.value = data.data?.data ?? data.data ?? []
  } catch (e) { /* non-fatal */ }
}

onMounted(() => { load(); loadJobs() })
</script>
