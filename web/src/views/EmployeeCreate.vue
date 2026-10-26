<template>
  <div class="employee-create-page pb-5">
    <!-- Top Bar Navigation & Actions -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 pt-1">
      <div class="d-flex align-items-center gap-3">
        <router-link to="/hrms/employees" class="btn btn-light bg-white border shadow-sm px-3 py-2 rounded-3 text-secondary d-flex align-items-center gap-2">
          <i class="mdi mdi-arrow-left font-size-16"></i>
          <span class="fw-medium">Kembali</span>
        </router-link>
        <div>
          <h4 class="mb-0 fw-bold text-dark">Tambah Karyawan Baru</h4>
          <p class="text-muted small mb-0">Isi informasi data diri dan penempatan kerja karyawan</p>
        </div>
      </div>
      <div class="d-flex align-items-center gap-2">
        <router-link to="/hrms/employees" class="btn btn-outline-secondary px-4 py-2 rounded-3">
          Batal
        </router-link>
        <button 
          type="button" 
          class="btn btn-primary px-4 py-2 rounded-3 d-flex align-items-center gap-2 shadow-sm"
          :disabled="saving"
          @click="handleSubmit"
        >
          <span v-if="saving" class="spinner-border spinner-border-sm" role="status"></span>
          <i v-else class="mdi mdi-check"></i>
          <span>{{ saving ? 'Menyimpan...' : 'Simpan Karyawan' }}</span>
        </button>
      </div>
    </div>

    <!-- Alert Error jika ada -->
    <div v-if="errorMessage" class="alert alert-danger alert-dismissible fade show rounded-3 mb-4 shadow-sm" role="alert">
      <div class="d-flex align-items-center gap-2">
        <i class="mdi mdi-alert-circle font-size-18"></i>
        <div>{{ errorMessage }}</div>
      </div>
      <button type="button" class="btn-close" @click="errorMessage = ''" aria-label="Close"></button>
    </div>

    <form @submit.prevent="handleSubmit">
      <div class="row g-4">
        <!-- Kolom Kiri: Foto Profil & Info Tambahan -->
        <div class="col-lg-4 col-xl-3">
          <!-- Foto Profil Card -->
          <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white border-bottom-0 pt-4 px-4 pb-0">
              <h5 class="card-title fw-bold text-dark mb-0 font-size-15">Foto Profil</h5>
            </div>
            <div class="card-body p-4 text-center">
              <div 
                class="photo-dropzone d-flex flex-column align-items-center justify-content-center p-3 border rounded-4 bg-light position-relative overflow-hidden mb-3"
                :class="{'border-primary bg-primary bg-opacity-10': isDragging}"
                style="border: 2px dashed #d0d7de !important; cursor: pointer; min-height: 220px; transition: all 0.2s ease;"
                @dragover.prevent="isDragging = true"
                @dragleave.prevent="isDragging = false"
                @drop.prevent="onDrop"
                @click="triggerFileInput"
              >
                <img 
                  v-if="photoPreview" 
                  :src="photoPreview" 
                  class="img-fluid w-100 h-100 position-absolute top-0 start-0" 
                  style="object-fit: cover;" 
                  alt="Preview Foto" 
                />
                <div v-else class="text-center p-2 position-relative" style="z-index: 2;">
                  <div class="avatar-md mx-auto mb-3 bg-white rounded-circle shadow-sm d-flex align-items-center justify-content-center text-primary">
                    <i class="mdi mdi-cloud-upload font-size-24"></i>
                  </div>
                  <p class="mb-1 fw-semibold text-dark font-size-14">Drag & Drop Foto</p>
                  <p class="text-muted small mb-0">atau klik untuk memilih file</p>
                </div>
                <input 
                  type="file" 
                  ref="fileInputRef" 
                  @change="onFileSelect" 
                  class="d-none" 
                  accept="image/jpeg,image/png,image/jpg" 
                />
              </div>

              <div v-if="form.photo" class="d-flex justify-content-center gap-2">
                <button type="button" class="btn btn-sm btn-outline-danger px-3 rounded-pill" @click="clearPhoto">
                  <i class="mdi mdi-trash-can-outline me-1"></i> Hapus Foto
                </button>
              </div>
              <small class="text-muted d-block mt-2 font-size-12">Format: JPG, PNG. Ukuran maks. 2MB</small>
            </div>
          </div>

          <!-- Status & Pengaturan Akses Card -->
          <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white border-bottom-0 pt-4 px-4 pb-0">
              <h5 class="card-title fw-bold text-dark mb-0 font-size-15">Pengaturan Akun</h5>
            </div>
            <div class="card-body p-4">
              <div class="mb-3">
                <label class="form-label small fw-semibold text-secondary mb-1">Status Karyawan</label>
                <select v-model="form.employment_status" class="form-select bg-light border-0 px-3 py-2 rounded-3">
                  <option value="permanent">Tetap (Permanent)</option>
                  <option value="contract">Kontrak</option>
                  <option value="probation">Masa Percobaan (Probation)</option>
                  <option value="intern">Magang (Intern)</option>
                </select>
              </div>

              <div class="mb-3">
                <label class="form-label small fw-semibold text-secondary mb-1">Status Login</label>
                <div class="form-check form-switch pt-1">
                  <input class="form-check-input" type="checkbox" role="switch" id="statusLoginSwitch" v-model="form.is_active">
                  <label class="form-check-label small ms-1 fw-medium" for="statusLoginSwitch">
                    {{ form.is_active ? 'Akun Aktif' : 'Akun Nonaktif' }}
                  </label>
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label small fw-semibold text-secondary mb-1">Tipe Pekerja</label>
                <div class="form-check form-switch pt-1">
                  <input class="form-check-input" type="checkbox" role="switch" id="dailyWorkerSwitch" v-model="form.is_daily_worker">
                  <label class="form-check-label small ms-1" for="dailyWorkerSwitch">Pekerja Harian Lepas</label>
                </div>
              </div>

              <div class="mb-0">
                <label class="form-label small fw-semibold text-secondary mb-1">Lokasi Presensi Absensi</label>
                <select v-model="form.attendance_location_type" class="form-select bg-light border-0 px-3 py-2 rounded-3 small">
                  <option value="assigned_branch">Hanya Cabang Penempatan</option>
                  <option value="all_branches">Semua Cabang Perusahaan</option>
                  <option value="anywhere">Bebas Presensi (Di Mana Saja)</option>
                </select>
                <small class="text-muted font-size-11 d-block mt-1">
                  {{ form.attendance_location_type === 'anywhere' ? 'Karyawan bebas absen di mana saja tanpa radius GPS.' : (form.attendance_location_type === 'all_branches' ? 'Karyawan berhak absen di semua cabang perusahaan.' : 'Karyawan hanya berhak absen di cabang penempatan kerjanya.') }}
                </small>
              </div>
            </div>
          </div>
        </div>

        <!-- Kolom Kanan: Form Utama -->
        <div class="col-lg-8 col-xl-9">
          <!-- Card 1: Data Pribadi & Akun -->
          <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white border-bottom-0 pt-4 px-4 pb-2">
              <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary-subtle text-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">1</span>
                <h5 class="card-title fw-bold text-dark mb-0 font-size-16">Informasi Data Diri & Akun</h5>
              </div>
            </div>
            <div class="card-body px-4 pb-4 pt-2">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label small fw-semibold text-secondary mb-1">NIP (Nomor Induk Pegawai)</label>
                  <input 
                    v-model="form.employee_id" 
                    type="text" 
                    class="form-control bg-light border-0 px-3 py-2 rounded-3" 
                    placeholder="Kosongkan untuk generate otomatis oleh sistem" 
                  />
                  <small class="text-muted font-size-11">Jika dikosongkan, NIP akan dibuat otomatis berdasarkan tanggal masuk.</small>
                </div>

                <div class="col-md-6">
                  <label class="form-label small fw-semibold text-secondary mb-1">Nama Lengkap <span class="text-danger">*</span></label>
                  <input 
                    v-model="form.name" 
                    type="text" 
                    class="form-control bg-light border-0 px-3 py-2 rounded-3" 
                    required 
                    placeholder="Masukkan nama lengkap" 
                  />
                </div>

                <div class="col-md-6">
                  <label class="form-label small fw-semibold text-secondary mb-1">Nama Panggilan</label>
                  <input 
                    v-model="form.nickname" 
                    type="text" 
                    class="form-control bg-light border-0 px-3 py-2 rounded-3" 
                    placeholder="Nama panggilan (opsional)" 
                  />
                </div>

                <div class="col-md-6">
                  <label class="form-label small fw-semibold text-secondary mb-1">Email <span class="text-danger">*</span></label>
                  <input 
                    v-model="form.email" 
                    type="email" 
                    class="form-control bg-light border-0 px-3 py-2 rounded-3" 
                    required 
                    placeholder="nama@perusahaan.com" 
                  />
                </div>

                <div class="col-md-6">
                  <label class="form-label small fw-semibold text-secondary mb-1">Password Akun <span class="text-danger">*</span></label>
                  <input 
                    v-model="form.password" 
                    type="password" 
                    class="form-control bg-light border-0 px-3 py-2 rounded-3" 
                    required 
                    placeholder="Minimal 6 karakter" 
                  />
                </div>

                <div class="col-md-6">
                  <label class="form-label small fw-semibold text-secondary mb-1">Nomor Telepon / WhatsApp</label>
                  <input 
                    v-model="form.phone" 
                    type="text" 
                    class="form-control bg-light border-0 px-3 py-2 rounded-3" 
                    placeholder="Contoh: 081234567890" 
                  />
                </div>

                <div class="col-md-4">
                  <label class="form-label small fw-semibold text-secondary mb-1">Jenis Kelamin</label>
                  <select v-model="form.gender" class="form-select bg-light border-0 px-3 py-2 rounded-3">
                    <option value="">-- Pilih --</option>
                    <option value="male">Laki-laki</option>
                    <option value="female">Perempuan</option>
                  </select>
                </div>

                <div class="col-md-4">
                  <label class="form-label small fw-semibold text-secondary mb-1">Status Pernikahan</label>
                  <select v-model="form.marital_status" class="form-select bg-light border-0 px-3 py-2 rounded-3">
                    <option value="single">Belum Kawin (Single)</option>
                    <option value="married">Kawin (Married)</option>
                  </select>
                </div>

                <div class="col-md-4">
                  <label class="form-label small fw-semibold text-secondary mb-1">Tanggal Lahir</label>
                  <input 
                    v-model="form.birth_date" 
                    type="date" 
                    class="form-control bg-light border-0 px-3 py-2 rounded-3" 
                  />
                </div>

                <div class="col-md-6">
                  <label class="form-label small fw-semibold text-secondary mb-1">Tanggal Masuk (Join Date)</label>
                  <input 
                    v-model="form.join_date" 
                    type="date" 
                    class="form-control bg-light border-0 px-3 py-2 rounded-3" 
                  />
                </div>

                <div class="col-md-6">
                  <label class="form-label small fw-semibold text-secondary mb-1">No. KTP (NIK)</label>
                  <input 
                    v-model="form.ktp_number" 
                    type="text" 
                    class="form-control bg-light border-0 px-3 py-2 rounded-3" 
                    placeholder="16 digit nomor NIK KTP" 
                  />
                </div>

                <div class="col-12">
                  <label class="form-label small fw-semibold text-secondary mb-1">Alamat Tempat Tinggal</label>
                  <textarea 
                    v-model="form.address" 
                    class="form-control bg-light border-0 px-3 py-2 rounded-3" 
                    rows="2" 
                    placeholder="Alamat lengkap domisili karyawan"
                  ></textarea>
                </div>
              </div>
            </div>
          </div>

          <!-- Card 2: Penempatan & Struktur Organisasi -->
          <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white border-bottom-0 pt-4 px-4 pb-2">
              <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div class="d-flex align-items-center gap-2">
                  <span class="badge bg-primary-subtle text-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">2</span>
                  <div>
                    <h5 class="card-title fw-bold text-dark mb-0 font-size-16">Penempatan & Jabatan</h5>
                    <small class="text-muted">Atur entitas perusahaan, penempatan cabang, divisi, dan jabatan karyawan</small>
                  </div>
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm px-3 rounded-3 d-flex align-items-center gap-1" @click="addAssignment">
                  <i class="mdi mdi-plus"></i> Tambah Posisi Lain
                </button>
              </div>
            </div>

            <div class="card-body px-4 pb-4 pt-2">
              <!-- Loop Penempatan / Assignments -->
              <div 
                v-for="(assign, index) in form.assignments" 
                :key="index" 
                class="assignment-item card border border-light-subtle rounded-3 shadow-none bg-light bg-opacity-50 mb-3"
              >
                <div class="card-header bg-transparent border-bottom d-flex align-items-center justify-content-between py-2 px-3">
                  <div class="d-flex align-items-center gap-2">
                    <span class="fw-bold font-size-14 text-dark">Posisi #{{ index + 1 }}</span>
                    <span v-if="assign.is_primary" class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                      Posisi Utama
                    </span>
                  </div>
                  <div class="d-flex align-items-center gap-2">
                    <button 
                      v-if="!assign.is_primary" 
                      type="button" 
                      class="btn btn-sm btn-link text-primary text-decoration-none p-0 me-2"
                      @click="setPrimaryAssignment(index)"
                    >
                      Jadikan Posisi Utama
                    </button>
                    <button 
                      v-if="form.assignments.length > 1" 
                      type="button" 
                      class="btn btn-sm btn-outline-danger py-1 px-2 rounded-2"
                      @click="removeAssignment(index)"
                      title="Hapus Posisi Ini"
                    >
                      <i class="mdi mdi-trash-can-outline"></i>
                    </button>
                  </div>
                </div>

                <div class="card-body p-3">
                  <div class="row g-3">
                    <!-- Perusahaan Penggajian -->
                    <div class="col-md-6">
                      <label class="form-label small fw-semibold text-secondary mb-1">
                        Perusahaan (Entitas Penggajian) <span class="text-danger">*</span>
                      </label>
                      <Multiselect 
                        v-model="assign.company_id" 
                        :options="async (q) => fetchOptions('/companies', q)" 
                        @change="handleCompanyChange(assign)" 
                        searchable 
                        class="custom-multiselect rounded-3 border-0 bg-white" 
                        placeholder="Pilih Perusahaan Penggajian" 
                      />
                    </div>

                    <!-- Area Penempatan (Cabang & Perusahaan) -->
                    <div class="col-md-6">
                      <label class="form-label small fw-semibold text-secondary mb-1">
                        Area Penempatan
                      </label>
                      <Multiselect 
                        v-model="assign.branch_id" 
                        :options="async (q) => fetchBranchOptions(q)" 
                        @change="(val) => handleBranchPlacementChange(assign, val)" 
                        searchable 
                        class="custom-multiselect rounded-3 border-0 bg-white" 
                        placeholder="Pilih Cabang / Area Penempatan" 
                      />
                    </div>

                    <!-- Departemen -->
                    <div class="col-md-6">
                      <label class="form-label small fw-semibold text-secondary mb-1">Departemen</label>
                      <Multiselect 
                        v-model="assign.department_id" 
                        :options="async (q) => fetchOptions('/departments', q)" 
                        searchable 
                        class="custom-multiselect rounded-3 border-0 bg-white" 
                        placeholder="Pilih Departemen" 
                      />
                    </div>

                    <!-- Divisi -->
                    <div class="col-md-6">
                      <label class="form-label small fw-semibold text-secondary mb-1">Divisi</label>
                      <Multiselect 
                        v-model="assign.division_id" 
                        :options="async (q) => fetchOptions('/divisions', q)" 
                        searchable 
                        class="custom-multiselect rounded-3 border-0 bg-white" 
                        placeholder="Pilih Divisi" 
                      />
                    </div>

                    <!-- Jabatan -->
                    <div class="col-md-6">
                      <label class="form-label small fw-semibold text-secondary mb-1">Jabatan</label>
                      <Multiselect 
                        :key="'pos_' + assign.division_id" 
                        v-model="assign.position_id" 
                        :options="async (q) => fetchPositionOptions(q, assign.division_id)" 
                        searchable 
                        class="custom-multiselect rounded-3 border-0 bg-white" 
                        placeholder="Pilih Jabatan" 
                      />
                    </div>

                    <!-- Atasan -->
                    <div class="col-md-6">
                      <label class="form-label small fw-semibold text-secondary mb-1">Atasan Langsung (Supervisor)</label>
                      <Multiselect 
                        v-model="assign.supervisor_id" 
                        :options="async (q) => fetchOptions('/employees', q)" 
                        searchable 
                        class="custom-multiselect rounded-3 border-0 bg-white" 
                        placeholder="Pilih Atasan" 
                      />
                    </div>

                    <!-- Tanggal Masuk Posisi -->
                    <div class="col-md-3">
                      <label class="form-label small fw-semibold text-secondary mb-1">Tgl Masuk Posisi Ini</label>
                      <input 
                        v-model="assign.join_date" 
                        type="date" 
                        class="form-control bg-white border-0 px-3 py-2 rounded-3 shadow-none" 
                      />
                    </div>

                    <!-- Status Posisi -->
                    <div class="col-md-3">
                      <label class="form-label small fw-semibold text-secondary mb-1">Status Karyawan</label>
                      <select v-model="assign.employment_status" class="form-select bg-white border-0 px-3 py-2 rounded-3 shadow-none">
                        <option value="permanent">Tetap</option>
                        <option value="contract">Kontrak</option>
                        <option value="probation">Percobaan</option>
                        <option value="internship">Magang</option>
                      </select>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Card 3: Identitas Resmi & Pajak (Opsional) -->
          <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white border-bottom-0 pt-4 px-4 pb-2">
              <div class="d-flex align-items-center gap-2">
                <span class="badge bg-secondary-subtle text-secondary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">3</span>
                <h5 class="card-title fw-bold text-dark mb-0 font-size-16">Pajak & Jaminan Sosial (Opsional)</h5>
              </div>
            </div>
            <div class="card-body px-4 pb-4 pt-2">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label small fw-semibold text-secondary mb-1">Nomor NPWP</label>
                  <input 
                    v-model="form.npwp" 
                    type="text" 
                    class="form-control bg-light border-0 px-3 py-2 rounded-3" 
                    placeholder="Contoh: 01.234.567.8-901.000" 
                  />
                </div>

                <div class="col-md-6">
                  <label class="form-label small fw-semibold text-secondary mb-1">Status PTKP</label>
                  <select v-model="form.ptkp_status" class="form-select bg-light border-0 px-3 py-2 rounded-3">
                    <option value="">-- Pilih PTKP --</option>
                    <option value="TK/0">TK/0 (Tidak Kawin, 0 Tanggungan)</option>
                    <option value="TK/1">TK/1 (Tidak Kawin, 1 Tanggungan)</option>
                    <option value="TK/2">TK/2 (Tidak Kawin, 2 Tanggungan)</option>
                    <option value="TK/3">TK/3 (Tidak Kawin, 3 Tanggungan)</option>
                    <option value="K/0">K/0 (Kawin, 0 Tanggungan)</option>
                    <option value="K/1">K/1 (Kawin, 1 Tanggungan)</option>
                    <option value="K/2">K/2 (Kawin, 2 Tanggungan)</option>
                    <option value="K/3">K/3 (Kawin, 3 Tanggungan)</option>
                  </select>
                </div>

                <div class="col-md-6">
                  <label class="form-label small fw-semibold text-secondary mb-1">No. BPJS Kesehatan</label>
                  <input 
                    v-model="form.bpjs_kesehatan" 
                    type="text" 
                    class="form-control bg-light border-0 px-3 py-2 rounded-3" 
                    placeholder="Nomor kartu BPJS Kesehatan" 
                  />
                </div>

                <div class="col-md-6">
                  <label class="form-label small fw-semibold text-secondary mb-1">No. BPJS Ketenagakerjaan</label>
                  <input 
                    v-model="form.bpjs_ketenagakerjaan" 
                    type="text" 
                    class="form-control bg-light border-0 px-3 py-2 rounded-3" 
                    placeholder="Nomor kartu BPJS Ketenagakerjaan" 
                  />
                </div>
              </div>
            </div>
          </div>

          <!-- Bottom Actions Bar -->
          <div class="d-flex align-items-center justify-content-end gap-3 pt-2">
            <router-link to="/hrms/employees" class="btn btn-outline-secondary px-4 py-2 rounded-3">
              Batal
            </router-link>
            <button 
              type="button" 
              class="btn btn-primary px-5 py-2 rounded-3 d-flex align-items-center gap-2 shadow"
              :disabled="saving"
              @click="handleSubmit"
            >
              <span v-if="saving" class="spinner-border spinner-border-sm" role="status"></span>
              <i v-else class="mdi mdi-check"></i>
              <span>{{ saving ? 'Menyimpan...' : 'Simpan Karyawan' }}</span>
            </button>
          </div>
        </div>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import Multiselect from '@vueform/multiselect'
import '@vueform/multiselect/themes/default.css'
import { toast } from 'vue3-toastify'
import api from '../api'

const router = useRouter()
const saving = ref(false)
const errorMessage = ref('')
const isDragging = ref(false)
const fileInputRef = ref(null)
const photoPreview = ref(null)

const form = reactive({
  employee_id: '',
  name: '',
  nickname: '',
  email: '',
  password: '',
  phone: '',
  gender: '',
  marital_status: 'single',
  birth_date: '',
  join_date: new Date().toISOString().slice(0, 10),
  employment_status: 'permanent',
  is_active: true,
  is_daily_worker: false,
  can_clock_in_anywhere: false,
  attendance_location_type: 'assigned_branch',
  allowed_branch_ids: [],
  address: '',
  ktp_number: '',
  npwp: '',
  ptkp_status: '',
  bpjs_kesehatan: '',
  bpjs_ketenagakerjaan: '',
  photo: null,
  assignments: [
    {
      company_id: null,
      placement_company_id: null,
      branch_id: null,
      department_id: null,
      division_id: null,
      position_id: null,
      supervisor_id: null,
      join_date: new Date().toISOString().slice(0, 10),
      employment_status: 'permanent',
      is_primary: true
    }
  ]
})

const triggerFileInput = () => {
  if (fileInputRef.value) fileInputRef.value.click()
}

const onFileSelect = (e) => {
  const file = e.target.files[0]
  if (file) {
    handlePhotoFile(file)
  }
}

const onDrop = (e) => {
  isDragging.value = false
  const file = e.dataTransfer.files[0]
  if (file && file.type.startsWith('image/')) {
    handlePhotoFile(file)
  }
}

const handlePhotoFile = (file) => {
  if (file.size > 2 * 1024 * 1024) {
    toast.error('Ukuran foto maksimal 2MB')
    return
  }
  form.photo = file
  photoPreview.value = URL.createObjectURL(file)
}

const clearPhoto = () => {
  form.photo = null
  photoPreview.value = null
  if (fileInputRef.value) fileInputRef.value.value = ''
}

const fetchOptions = async (endpoint, query, filters = {}) => {
  try {
    const { data } = await api.get(endpoint, { params: { search: query, per_page: 50, ...filters } })
    return (data.data?.data || data.data || []).map(item => ({
      value: item.id,
      label: item.name || item.title || item.employee_id || item.id
    }))
  } catch (e) {
    return []
  }
}

const fetchBranchOptions = async (query) => {
  try {
    const { data } = await api.get('/branches', { params: { search: query, per_page: 100 } })
    const items = data.data?.data || data.data || []
    return items.map(b => {
      const compName = b.company?.name || ''
      return {
        value: b.id,
        label: compName ? `${b.name} (${compName})` : b.name,
        company_id: b.company_id
      }
    })
  } catch (e) {
    return []
  }
}

const fetchPositionOptions = async (query, divisionId) => {
  try {
    const { data } = await api.get('/positions', { params: { search: query, per_page: 100 } })
    const items = data.data?.data || data.data || []
    if (divisionId) {
      const matching = items.filter(p => String(p.division_id) === String(divisionId))
      const others = items.filter(p => String(p.division_id) !== String(divisionId))
      if (matching.length > 0) {
        return [
          ...matching.map(p => ({ value: p.id, label: `★ ${p.name}` })),
          ...others.map(p => ({ value: p.id, label: p.name }))
        ]
      }
    }
    return items.map(p => ({ value: p.id, label: p.name }))
  } catch (e) {
    return []
  }
}

const handleBranchPlacementChange = async (assign, branchId) => {
  assign.branch_id = branchId
  if (branchId) {
    try {
      const res = await api.get(`/branches/${branchId}`)
      if (res.data?.data?.company_id) {
        assign.placement_company_id = res.data.data.company_id
      } else {
        assign.placement_company_id = assign.company_id || null
      }
    } catch (e) {
      assign.placement_company_id = assign.company_id || null
    }
  } else {
    assign.placement_company_id = assign.company_id || null
  }
}

const handleCompanyChange = (assign) => {
  if (!assign.branch_id) {
    assign.placement_company_id = assign.company_id || null
  }
}

const handlePlacementCompanyChange = () => {}
const handleBranchChange = () => {}
const handleDepartmentChange = () => {}
const handleDivisionChange = () => {}

const addAssignment = () => {
  form.assignments.push({
    company_id: null,
    placement_company_id: null,
    branch_id: null,
    department_id: null,
    division_id: null,
    position_id: null,
    supervisor_id: null,
    join_date: form.join_date || new Date().toISOString().slice(0, 10),
    employment_status: form.employment_status || 'permanent',
    is_primary: form.assignments.length === 0
  })
}

const removeAssignment = (index) => {
  const isPrimary = form.assignments[index].is_primary
  form.assignments.splice(index, 1)
  if (isPrimary && form.assignments.length > 0) {
    form.assignments[0].is_primary = true
  }
}

const setPrimaryAssignment = (index) => {
  form.assignments.forEach((a, i) => {
    a.is_primary = (i === index)
  })
}

const handleSubmit = async () => {
  errorMessage.value = ''

  // Validasi dasar
  if (!form.name || !form.name.trim()) {
    toast.error('Nama Lengkap wajib diisi')
    return
  }
  if (!form.email || !form.email.trim()) {
    toast.error('Email wajib diisi')
    return
  }
  if (!form.password || !form.password.trim()) {
    toast.error('Password akun wajib diisi')
    return
  }
  if (!form.assignments || form.assignments.length === 0) {
    toast.error('Minimal satu penempatan harus diisi')
    return
  }
  for (let i = 0; i < form.assignments.length; i++) {
    if (!form.assignments[i].company_id) {
      toast.error(`Perusahaan (Entitas Penggajian) pada Posisi #${i + 1} wajib dipilih`)
      return
    }
  }

  saving.value = true
  try {
    const formData = new FormData()

    // Append file jika ada
    if (form.photo instanceof File) {
      formData.append('photo', form.photo)
    }

    // Append fields biasa
    const directFields = [
      'employee_id', 'name', 'nickname', 'email', 'password', 'phone', 
      'gender', 'marital_status', 'birth_date', 'join_date', 'employment_status',
      'address', 'ktp_number', 'npwp', 'ptkp_status', 'bpjs_kesehatan', 'bpjs_ketenagakerjaan'
    ]

    directFields.forEach(key => {
      if (form[key] !== null && form[key] !== undefined && form[key] !== '') {
        formData.append(key, form[key])
      }
    })

    formData.append('is_active', form.is_active ? '1' : '0')
    formData.append('is_daily_worker', form.is_daily_worker ? '1' : '0')
    const isAnywhere = form.attendance_location_type === 'anywhere' || form.can_clock_in_anywhere
    formData.append('can_clock_in_anywhere', isAnywhere ? '1' : '0')
    formData.append('attendance_location_type', form.attendance_location_type || 'assigned_branch')
    if (form.attendance_location_type === 'specific_branches' && Array.isArray(form.allowed_branch_ids)) {
      formData.append('allowed_branch_ids', JSON.stringify(form.allowed_branch_ids))
    }

    // Clean assignments
    const cleanedAssignments = form.assignments.map(a => ({
      company_id: a.company_id,
      placement_company_id: a.placement_company_id || null,
      branch_id: a.branch_id || null,
      department_id: a.department_id || null,
      division_id: a.division_id || null,
      position_id: a.position_id || null,
      supervisor_id: a.supervisor_id || null,
      join_date: a.join_date || form.join_date || null,
      employment_status: a.employment_status || 'permanent',
      is_primary: !!a.is_primary
    }))

    formData.append('assignments', JSON.stringify(cleanedAssignments))

    const response = await api.post('/employees', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })

    toast.success('Karyawan berhasil ditambahkan!')
    const newId = response.data?.data?.id
    if (newId) {
      router.push(`/hrms/employees/${newId}`)
    } else {
      router.push('/hrms/employees')
    }
  } catch (err) {
    console.error(err)
    const errData = err.response?.data
    let msg = errData?.message || 'Gagal menyimpan data karyawan'
    if (errData?.errors) {
      const firstKey = Object.keys(errData.errors)[0]
      if (firstKey && errData.errors[firstKey][0]) {
        msg = errData.errors[firstKey][0]
      }
    }
    errorMessage.value = msg
    toast.error(msg)
  } finally {
    saving.value = false
  }
}
</script>

<style scoped>
.custom-multiselect {
  --ms-bg: #ffffff;
  --ms-border-color: #e2e8f0;
  --ms-radius: 0.5rem;
  --ms-font-size: 0.9rem;
  --ms-py: 0.5rem;
  --ms-px: 0.85rem;
  --ms-ring-width: 0px;
  --ms-placeholder-color: #94a3b8;
}

.custom-multiselect.is-open {
  --ms-border-color: var(--bs-primary);
}

.photo-dropzone:hover {
  background-color: #f1f5f9 !important;
  border-color: #94a3b8 !important;
}

.avatar-md {
  width: 54px;
  height: 54px;
}
</style>
