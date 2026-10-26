<template>
  <div>
    <!-- Header -->
    <div class="row mb-4 align-items-center">
      <div class="col-sm-6">
        <button @click="$router.back()" class="btn-back mb-2"><i class="mdi mdi-arrow-left me-1"></i> Kembali</button>
        <h4 class="mb-0 font-size-18 fw-bold text-dark">Detail Kandidat: <span class="text-muted">{{ c?.name }}</span></h4>
      </div>
      <div class="col-sm-6 text-sm-end mt-3 mt-sm-0">
        <div class="d-inline-flex gap-2">
          <button v-if="c" @click="changeStatus(c.status)" class="btn btn-primary waves-effect waves-light">
            Update Status
          </button>
          <button v-if="c && c.status !== 'hired'" @click="hireCandidate" class="btn btn-success waves-effect waves-light">
            Hire 🎉
          </button>
        </div>
      </div>
    </div>

    <div v-if="error" class="alert alert-danger mb-4">{{ error }}</div>
    <div v-if="flash" class="alert alert-success mb-4">{{ flash }}</div>

    <div v-if="!c && loading" class="text-center text-muted py-5 font-size-14">
      <div class="spinner-border text-primary" role="status"></div>
      <div class="mt-2">Memuat...</div>
    </div>

    <template v-if="c">
      <!-- Header card -->
      <div class="card mb-4 shadow-sm">
        <div class="card-body">
          <div class="d-flex align-items-center gap-4">
            <img v-if="c.photo" :src="c.photo" class="rounded-circle bg-light" style="width: 64px; height: 64px; object-fit: cover;" />
            <div v-else class="rounded-circle bg-light d-flex align-items-center justify-content-center text-secondary font-size-24 fw-bold" style="width: 64px; height: 64px;">
              {{ initials(c.name) }}
            </div>
            <div class="flex-grow-1">
              <h5 class="font-size-18 mb-1 text-dark fw-semibold">{{ c.name }}</h5>
              <p class="text-muted mb-1 font-size-14">{{ c.email }} &bull; {{ c.phone || '-' }}</p>
              <p class="font-size-13 text-muted mb-0">{{ c.address || '-' }} &bull; Kelamin: {{ genderLabel(c.gender) }} &bull; TTL: {{ c.birth_date || '-' }}</p>
            </div>
            <div class="text-end">
              <span class="badge font-size-13 py-2 px-3" :class="statusClass(c.status)">{{ statusLabel(c.status) }}</span>
            </div>
          </div>
          <div class="mt-4 pt-3 border-top row font-size-14 text-muted">
            <div class="col-6 col-md-3 mb-2 mb-md-0"><span>Sumber:</span> <span class="fw-medium text-dark ms-1">{{ c.source || '-' }}</span></div>
            <div class="col-6 col-md-3 mb-2 mb-md-0"><span>NIK:</span> <span class="fw-medium text-dark ms-1">{{ c.ktp_number || '-' }}</span></div>
            <div class="col-6 col-md-3"><span>Bergabung:</span> <span class="fw-medium text-dark ms-1">{{ c.applied_date || '-' }}</span></div>
          </div>
        </div>
      </div>

      <div class="row">
        <!-- Main Content -->
        <div class="col-lg-8">
          
          <!-- Lamaran -->
          <div class="card mb-4 shadow-sm">
            <div class="card-header bg-transparent border-bottom">
              <h5 class="card-title mb-0">Lamaran</h5>
            </div>
            <div class="card-body p-0">
              <div v-if="!c.applications?.length" class="p-4 text-center text-muted font-size-14">Belum ada lamaran</div>
              
              <div v-for="(app, idx) in c.applications" :key="app.id" :class="{'border-bottom': idx !== c.applications.length - 1}" class="p-4">
                <div class="d-flex align-items-center justify-content-between mb-2">
                  <div>
                    <h6 class="font-size-15 mb-1 text-dark">{{ app.job?.title || 'Lowongan' }}</h6>
                    <p class="font-size-13 text-muted mb-0">Lamaran #{{ app.id }} &bull; {{ fmtDate(app.applied_at) }} &bull; {{ app.job?.code }}</p>
                  </div>
                  <span class="badge px-2 py-1" :class="statusClass(app.status)">{{ statusLabel(app.status) }}</span>
                </div>
                <p v-if="app.notes" class="font-size-13 text-muted mb-2">{{ app.notes }}</p>
                
                <!-- Actions per application -->
                <div class="d-flex flex-wrap gap-2 mt-3">
                  <button @click="openInviteTest(app)" class="btn btn-sm btn-soft-primary">Undang Test</button>
                  <button @click="openScheduleInterview(app)" class="btn btn-sm btn-soft-info">Jadwal Interview</button>
                  <template v-for="itv in (app.interviews || [])" :key="itv.id">
                    <button v-if="itv.status === 'scheduled' || itv.status === 'confirmed'"
                      @click="openInterviewResult(app, itv)" class="btn btn-sm btn-soft-warning">
                      Hasil Interview
                    </button>
                  </template>
                  <button v-if="app.status !== 'hired'" @click="hireApp(app)" class="btn btn-sm btn-soft-success">Hire</button>
                </div>
                
                <!-- interviews list -->
                <div v-if="app.interviews?.length" class="mt-4 bg-light rounded-3 p-3">
                  <p class="font-size-12 fw-semibold text-muted text-uppercase mb-2">Wawancara</p>
                  <div v-for="(itv, iidx) in app.interviews" :key="itv.id" class="font-size-13 text-secondary py-2" :class="{'border-bottom border-light': iidx !== app.interviews.length - 1}">
                    <span class="fw-medium text-dark">{{ itv.mode }}</span> &bull; {{ fmtDate(itv.scheduled_at) }} &bull;
                    <span class="badge" :class="itv.status === 'completed' ? 'bg-success' : 'bg-secondary'">{{ itv.status }}</span>
                    <span v-if="itv.decided" class="ms-2 fw-semibold" :class="itv.decided === 'hire' ? 'text-success' : 'text-danger'">→ {{ itv.decided }}</span>
                    <span v-if="itv.rating" class="ms-2 text-warning fw-bold"><i class="mdi mdi-star"></i> {{ itv.rating }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Dokumen -->
          <div class="card mb-4 shadow-sm">
            <div class="card-header bg-transparent border-bottom">
              <h5 class="card-title mb-0">Dokumen (Verifikasi)</h5>
            </div>
            <div class="card-body p-0">
              <div v-if="!c.documents?.length" class="p-4 text-center text-muted font-size-14">Belum ada dokumen</div>
              
              <div v-for="(doc, ddx) in c.documents" :key="doc.id" class="p-3 d-flex align-items-center justify-content-between gap-3" :class="{'border-bottom': ddx !== c.documents.length - 1}">
                <div class="d-flex align-items-center gap-3" style="min-width: 0;">
                  <div class="avatar-sm">
                    <span class="avatar-title bg-light text-primary rounded">
                      <i class="mdi mdi-file-document-outline font-size-20"></i>
                    </span>
                  </div>
                  <div style="min-width: 0;">
                    <h6 class="font-size-14 mb-1 text-dark text-truncate">{{ doc.original_name || doc.document_type }}</h6>
                    <p class="font-size-12 text-muted mb-0">{{ doc.document_type }} &bull; {{ doc.is_verified ? 'Terverifikasi' : 'Belum' }}
                      <span v-if="doc.notes" class="ms-1 text-warning">({{ doc.notes }})</span>
                    </p>
                  </div>
                </div>
                <div class="d-flex gap-2 flex-shrink-0">
                  <a v-if="doc.file_url" :href="doc.file_url" target="_blank" class="btn btn-sm btn-light">Buka</a>
                  <button v-if="!doc.is_verified" @click="verifyDoc(doc, true, '')" class="btn btn-sm btn-success">Verifikasi</button>
                  <button v-else @click="verifyDoc(doc, false, 'Dibuka lagi utk cek ulang')" class="btn btn-sm btn-outline-danger">Batalkan</button>
                </div>
              </div>
            </div>
          </div>
          
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
          <!-- Timeline / ringkasan -->
          <div class="card mb-4 shadow-sm">
            <div class="card-body">
              <h5 class="card-title mb-3">Ringkasan Proses</h5>
              <ul class="list-unstyled mb-0 font-size-14">
                <li class="d-flex justify-content-between mb-2"><span class="text-muted">Total lamaran</span><span class="fw-medium">{{ (c.applications||[]).length }}</span></li>
                <li class="d-flex justify-content-between mb-2"><span class="text-muted">Total dokumen</span><span class="fw-medium">{{ (c.documents||[]).length }}</span></li>
                <li class="d-flex justify-content-between mb-2"><span class="text-muted">Dok. terverifikasi</span><span class="fw-medium text-success">{{ (c.documents||[]).filter(d=>d.is_verified).length }}</span></li>
                <li class="d-flex justify-content-between"><span class="text-muted">Status</span><span class="fw-medium">{{ statusLabel(c.status) }}</span></li>
              </ul>
            </div>
          </div>

          <div class="card bg-dark text-white shadow-sm mb-4">
            <div class="card-body">
              <h5 class="card-title text-white mb-3">Alur Rekrutmen</h5>
              <ol class="list-unstyled font-size-13 mb-0" style="color: #cbd5e1; line-height: 1.8;">
                <li>1. Applied → Review Dokumen</li>
                <li>2. Undang Test → Test Selesai</li>
                <li>3. Jadwal Interview → Hasil</li>
                <li>4. Shortlisted → Offered</li>
                <li><strong class="text-white">5. HIRE (buat karyawan otomatis)</strong></li>
              </ol>
            </div>
          </div>
        </div>
      </div>
    </template>

    <!-- Modal: Update status -->
    <div v-if="modalStatus" class="modal fade show d-block" tabindex="-1" style="background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px);">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
          <div class="modal-header border-bottom-0 pt-4 px-4 pb-2">
            <h5 class="modal-title fw-bold fs-4 text-dark">Update Status Kandidat</h5>
            <button type="button" class="btn-close shadow-none" @click="modalStatus = false" aria-label="Close"></button>
          </div>
          <div class="modal-body px-4 pb-4 pt-2">
            <div class="mb-3">
              <label class="form-label fw-semibold text-secondary small">Status</label>
              <select v-model="statusForm.status" class="form-select bg-light border-0 px-3 py-2 shadow-none rounded-3">
                <option v-for="s in allStatuses" :key="s" :value="s">{{ statusLabel(s) }}</option>
              </select>
            </div>
            <div class="mb-3" v-if="c?.applications?.length">
              <label class="form-label fw-semibold text-secondary small">Terapkan ke lamaran</label>
              <select v-model="statusForm.application_id" class="form-select bg-light border-0 px-3 py-2 shadow-none rounded-3">
                <option :value="null">Tidak</option>
                <option v-for="app in c.applications" :key="app.id" :value="app.id">Lamaran #{{ app.id }} — {{ app.job?.title }}</option>
              </select>
            </div>
            <div class="mb-4">
              <label class="form-label fw-semibold text-secondary small">Catatan</label>
              <textarea v-model="statusForm.notes" rows="3" class="form-control bg-light border-0 px-3 py-2 shadow-none rounded-3"></textarea>
            </div>
            <div class="text-end border-top pt-3">
              <button @click="modalStatus = false" class="btn btn-light me-2 waves-effect fw-medium px-4 rounded-3">Batal</button>
              <button @click="saveStatus" :disabled="saving" class="btn btn-primary waves-effect waves-light fw-medium px-4 rounded-3 shadow-sm">
                <i v-if="saving" class="mdi mdi-spin mdi-loading me-1"></i> {{ saving ? 'Menyimpan...' : 'Simpan' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal: Invite test -->
    <div v-if="modalInvite" class="modal fade show d-block" tabindex="-1" style="background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px);">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
          <div class="modal-header border-bottom-0 pt-4 px-4 pb-2">
            <h5 class="modal-title fw-bold fs-4 text-dark">Undang Test</h5>
            <button type="button" class="btn-close shadow-none" @click="modalInvite = false" aria-label="Close"></button>
          </div>
          <div class="modal-body px-4 pb-4 pt-2">
            <div class="mb-3">
              <label class="form-label fw-semibold text-secondary small">Pilih Test</label>
              <select v-model="inviteForm.test_id" class="form-select bg-light border-0 px-3 py-2 shadow-none rounded-3">
                <option value="">-- Pilih --</option>
                <option v-for="t in tests" :key="t.id" :value="t.id">{{ t.name }} <span v-if="t.recruitment_job">({{ t.recruitment_job.title }})</span></option>
              </select>
            </div>
            <div class="mb-4">
              <label class="form-label fw-semibold text-secondary small">Catatan</label>
              <input v-model="inviteForm.notes" class="form-control bg-light border-0 px-3 py-2 shadow-none rounded-3" placeholder="Tambahkan catatan jika ada" />
            </div>
            <div class="text-end border-top pt-3">
              <button @click="modalInvite = false" class="btn btn-light me-2 waves-effect fw-medium px-4 rounded-3">Batal</button>
              <button @click="saveInvite" :disabled="saving" class="btn btn-info text-white waves-effect waves-light fw-medium px-4 rounded-3 shadow-sm">
                <i v-if="saving" class="mdi mdi-spin mdi-loading me-1"></i> {{ saving ? 'Menyimpan...' : 'Undang' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal: Schedule interview -->
    <div v-if="modalInterview" class="modal fade show d-block" tabindex="-1" style="background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px);">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
          <div class="modal-header border-bottom-0 pt-4 px-4 pb-2">
            <h5 class="modal-title fw-bold fs-4 text-dark">Jadwal Interview</h5>
            <button type="button" class="btn-close shadow-none" @click="modalInterview = false" aria-label="Close"></button>
          </div>
          <div class="modal-body px-4 pb-4 pt-2">
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label fw-semibold text-secondary small">Mode</label>
                <select v-model="intForm.mode" class="form-select bg-light border-0 px-3 py-2 shadow-none rounded-3">
                  <option value="offline">Offline</option><option value="online">Online</option>
                </select>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label fw-semibold text-secondary small">Waktu</label>
                <input v-model="intForm.scheduled_at" type="datetime-local" class="form-control bg-light border-0 px-3 py-2 shadow-none rounded-3" />
              </div>
            </div>
            <div class="mb-3" v-if="intForm.mode === 'offline'">
              <label class="form-label fw-semibold text-secondary small">Lokasi</label>
              <input v-model="intForm.location" class="form-control bg-light border-0 px-3 py-2 shadow-none rounded-3" placeholder="Misal: Ruang Meeting 1" />
            </div>
            <div class="mb-3" v-if="intForm.mode === 'online'">
              <label class="form-label fw-semibold text-secondary small">Meeting Link</label>
              <input v-model="intForm.meeting_link" class="form-control bg-light border-0 px-3 py-2 shadow-none rounded-3" placeholder="https://meet.google.com/..." />
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold text-secondary small">Interviewer ID (opsional)</label>
              <input v-model.number="intForm.interviewer_id" type="number" class="form-control bg-light border-0 px-3 py-2 shadow-none rounded-3" placeholder="ID Karyawan pewawancara" />
            </div>
            <div class="mb-4">
              <label class="form-label fw-semibold text-secondary small">Catatan</label>
              <input v-model="intForm.notes" class="form-control bg-light border-0 px-3 py-2 shadow-none rounded-3" placeholder="Catatan tambahan" />
            </div>
            <div class="text-end border-top pt-3">
              <button @click="modalInterview = false" class="btn btn-light me-2 waves-effect fw-medium px-4 rounded-3">Batal</button>
              <button @click="saveInterview" :disabled="saving" class="btn btn-warning waves-effect waves-light fw-medium px-4 rounded-3 shadow-sm">
                <i v-if="saving" class="mdi mdi-spin mdi-loading me-1"></i> {{ saving ? 'Menyimpan...' : 'Jadwalkan' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal: Interview result -->
    <div v-if="modalResult" class="modal fade show d-block" tabindex="-1" style="background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px);">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
          <div class="modal-header border-bottom-0 pt-4 px-4 pb-2">
            <h5 class="modal-title fw-bold fs-4 text-dark">Hasil Interview</h5>
            <button type="button" class="btn-close shadow-none" @click="modalResult = false" aria-label="Close"></button>
          </div>
          <div class="modal-body px-4 pb-4 pt-2">
            <div class="mb-3">
              <label class="form-label fw-semibold text-secondary small">Keputusan</label>
              <select v-model="resForm.decided" class="form-select bg-light border-0 px-3 py-2 shadow-none rounded-3">
                <option value="hire">Hire — Lolos</option>
                <option value="reject">Reject — Gagal</option>
                <option value="hold">Hold — Tahan</option>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold text-secondary small">Rating (1-5)</label>
              <input v-model.number="resForm.rating" type="number" min="1" max="5" class="form-control bg-light border-0 px-3 py-2 shadow-none rounded-3" />
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold text-secondary small">Status</label>
              <select v-model="resForm.status" class="form-select bg-light border-0 px-3 py-2 shadow-none rounded-3">
                <option value="completed">Completed</option><option value="confirmed">Confirmed</option><option value="scheduled">Scheduled</option>
              </select>
            </div>
            <div class="mb-4">
              <label class="form-label fw-semibold text-secondary small">Catatan Hasil</label>
              <textarea v-model="resForm.result_notes" rows="3" class="form-control bg-light border-0 px-3 py-2 shadow-none rounded-3"></textarea>
            </div>
            <div class="text-end border-top pt-3">
              <button @click="modalResult = false" class="btn btn-light me-2 waves-effect fw-medium px-4 rounded-3">Batal</button>
              <button @click="saveResult" :disabled="saving" class="btn btn-success waves-effect waves-light fw-medium px-4 rounded-3 shadow-sm">
                <i v-if="saving" class="mdi mdi-spin mdi-loading me-1"></i> {{ saving ? 'Menyimpan...' : 'Simpan' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import api from '../../api'

const route = useRoute()
const cid = route.params.id
const c = ref(null)
const tests = ref([])
const loading = ref(false)
const saving = ref(false)
const error = ref('')
const flash = ref('')

const allStatuses = ['applied','document_review','test_invited','test_done','interview_invited','interview_done','shortlisted','offered','hired','rejected','on_hold']

// Modals state
const modalStatus = ref(false)
const modalInvite = ref(false)
const modalInterview = ref(false)
const modalResult = ref(false)
const statusForm = ref({})
const inviteForm = ref({})
const intForm = ref({})
const resForm = ref({})
let ctxApp = ref(null)
let ctxItv = ref(null)

const statusLabel = (s) => ({
  applied: 'Applied', document_review: 'Review Dokumen', test_invited: 'Diundang Test',
  test_done: 'Test Selesai', interview_invited: 'Diundang Interview', interview_done: 'Interview Selesai',
  shortlisted: 'Shortlisted', offered: 'Offered', hired: 'Hired', rejected: 'Rejected', on_hold: 'On Hold',
}[s] || s)

const statusClass = (s) => ({
  applied: 'badge-soft-secondary', document_review: 'badge-soft-primary',
  test_invited: 'badge-soft-info', test_done: 'badge-soft-success',
  interview_invited: 'badge-soft-warning', interview_done: 'badge-soft-primary',
  shortlisted: 'badge-soft-info', offered: 'badge-soft-warning',
  hired: 'badge-soft-success', rejected: 'badge-soft-danger', on_hold: 'badge-soft-secondary',
}[s] || 'bg-light text-dark')

const initials = (name) => (name || '?').split(' ').slice(0, 2).map(w => w[0]).join('').toUpperCase()
const genderLabel = (g) => g === 'male' ? 'Laki-laki' : g === 'female' ? 'Perempuan' : '-'
const fmtDate = (d) => d ? new Date(d).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta', dateStyle: 'short', timeStyle: 'short' }) : '-'

const flashMsg = (m) => { flash.value = m; setTimeout(() => flash.value = '', 3000) }

const load = async () => {
  loading.value = true; error.value = ''
  try {
    const { data } = await api.get(`/recruitment/admin/candidates/${cid}`)
    c.value = data.data
  } catch (e) { error.value = e.response?.data?.message || 'Gagal memuat detail' }
  finally { loading.value = false }
}

const loadTests = async () => {
  try {
    const { data } = await api.get('/recruitment/admin/tests', { params: { per_page: 100 } })
    tests.value = data.data?.data ?? data.data ?? []
  } catch (e) { /* non-fatal */ }
}

// ---- Status ----
const changeStatus = (cur) => { statusForm.value = { status: cur, application_id: null, notes: '' }; modalStatus.value = true }
const saveStatus = async () => {
  saving.value = true; error.value = ''
  try {
    const { data } = await api.post(`/recruitment/admin/candidates/${cid}/status`, statusForm.value)
    flashMsg(data.message || 'Status diperbarui'); modalStatus.value = false; load()
  } catch (e) { error.value = e.response?.data?.message || 'Gagal update status' }
  finally { saving.value = false }
}

// ---- Dokumen verify ----
const verifyDoc = async (doc, verified, notes) => {
  try {
    const { data } = await api.put(`/recruitment/admin/candidates/${cid}/documents/${doc.id}/verify`, { is_verified: verified, notes: notes || null })
    flashMsg(data.message || 'Dokumen diperbarui'); load()
  } catch (e) { error.value = e.response?.data?.message || 'Gagal verifikasi dokumen' }
}

// ---- Invite test ----
const openInviteTest = (app) => { ctxApp.value = app; inviteForm.value = { test_id: '', notes: '' }; modalInvite.value = true }
const saveInvite = async () => {
  if (!inviteForm.value.test_id) { error.value = 'Pilih test dulu'; return }
  saving.value = true; error.value = ''
  try {
    const { data } = await api.post(`/recruitment/admin/applications/${ctxApp.value.id}/invite-test`, inviteForm.value)
    flashMsg(data.message || 'Kandidat diundang test'); modalInvite.value = false; load()
  } catch (e) { error.value = e.response?.data?.errors ? Object.values(e.response.data.errors)[0][0] : (e.response?.data?.message || 'Gagal undang test') }
  finally { saving.value = false }
}

// ---- Schedule interview ----
const openScheduleInterview = (app) => {
  ctxApp.value = app
  intForm.value = { mode: 'offline', scheduled_at: '', location: '', meeting_link: '', interviewer_id: null, notes: '' }
  modalInterview.value = true
}
const saveInterview = async () => {
  if (!intForm.value.scheduled_at) { error.value = 'Waktu wajib diisi'; return }
  saving.value = true; error.value = ''
  try {
    const { data } = await api.post(`/recruitment/admin/applications/${ctxApp.value.id}/schedule-interview`, intForm.value)
    flashMsg(data.message || 'Interview dijadwalkan'); modalInterview.value = false; load()
  } catch (e) { error.value = e.response?.data?.message || 'Gagal jadwalkan interview' }
  finally { saving.value = false }
}

// ---- Interview result ----
const openInterviewResult = (app, itv) => {
  ctxApp.value = app; ctxItv.value = itv
  resForm.value = { decided: '', rating: null, status: 'completed', result_notes: '' }
  modalResult.value = true
}
const saveResult = async () => {
  if (!resForm.value.decided) { error.value = 'Pilih keputusan dulu'; return }
  saving.value = true; error.value = ''
  try {
    const { data } = await api.post(`/recruitment/admin/applications/${ctxApp.value.id}/interviews/${ctxItv.value.id}/result`, resForm.value)
    flashMsg(data.message || 'Hasil interview disimpan'); modalResult.value = false; load()
  } catch (e) { error.value = e.response?.data?.message || 'Gagal simpan hasil interview' }
  finally { saving.value = false }
}

// ---- Hire ----
const hireApp = async (app) => {
  if (!confirm(`Hire kandidat ini? Karyawan baru akan dibuat otomatis.`)) return
  try {
    const { data } = await api.post(`/recruitment/admin/applications/${app.id}/hire`)
    alert(`✅ ${data.message}\n\nKaryawan: ${data.data?.employee?.name} (${data.data?.employee?.employee_id})\nPassword default: ${data.data?.default_password}`)
    load()
  } catch (e) {
    error.value = e.response?.data?.errors ? Object.values(e.response.data.errors)[0][0] : (e.response?.data?.message || 'Gagal hire')
  }
}
const hireCandidate = async () => {
  const app = (c.value?.applications || [])[0]
  if (!app) { error.value = 'Kandidat belum punya lamaran'; return }
  await hireApp(app)
}

onMounted(() => { load(); loadTests() })
</script>
