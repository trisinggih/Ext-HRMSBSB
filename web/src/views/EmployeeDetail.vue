<template>
  <div>
    <div class="d-flex align-items-center mb-4">
      <router-link to="/hrms/employees" class="btn-back me-3">
        <i class="mdi mdi-arrow-left"></i> Kembali
      </router-link>
      <h4 class="mb-0 fw-bold text-dark">Detail Karyawan</h4>
    </div>

    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status"></div>
    </div>
    <div v-else-if="employee" class="row">
      <!-- Profile Header -->
      <div class="col-12 mb-4">
        <div class="card border-0 shadow-sm rounded-4">
          <div class="card-body p-4">
            <div class="row align-items-center">
              <div class="col-auto d-flex gap-3">
                <div class="text-center">
                  <img :src="employee.photo || 'https://ui-avatars.com/api/?name=' + employee.name" 
                       class="rounded-circle img-thumbnail border-2 mb-2" style="width: 100px; height: 100px; object-fit: cover; border-color: var(--bs-primary) !important;" alt="Profile Image">
                  <small class="d-block text-muted">Profil</small>
                </div>
                <div class="text-center" v-if="employee.face_photo">
                  <img :src="employee.face_photo" 
                       class="rounded-circle img-thumbnail border-2 mb-2" style="width: 100px; height: 100px; object-fit: cover; border-color: #4CAF50 !important;" alt="Face Photo">
                  <small class="d-block text-success"><i class="mdi mdi-face-recognition me-0.5"></i> Wajah (Face Lock)</small>
                </div>
                <div class="text-center" v-if="employee.signature_url">
                  <div class="rounded-3 img-thumbnail border-2 mb-2 d-flex align-items-center justify-content-center bg-white p-1" style="width: 100px; height: 100px; border-color: #0284c7 !important;">
                    <img :src="employee.signature_url" style="max-width: 90px; max-height: 90px; object-fit: contain;" alt="Tanda Tangan Digital">
                  </div>
                  <small class="d-block text-info"><i class="mdi mdi-draw me-0.5"></i> TTD Digital</small>
                </div>
              </div>
              <div class="col">
                <div class="d-flex align-items-center gap-2 mb-1">
                  <h4 class="mb-0 fw-bold text-dark">{{ employee.name }}</h4>
                  <span class="badge px-2 py-1" :class="employee.is_active ? 'bg-success text-white' : 'bg-danger-subtle text-danger'">
                    {{ employee.is_active ? 'Aktif' : 'Nonaktif' }}
                  </span>
                </div>
                <div class="text-secondary small mb-2">
                  <span>{{ primaryAssignment?.position?.name || '-' }}</span>
                  <span class="mx-1">&bull;</span>
                  <span>NIP: {{ employee.employee_id }}</span>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2">
                  <span class="badge bg-light text-dark border">
                    Perusahaan: {{ primaryAssignment?.company?.name || '-' }}
                  </span>
                  <span v-if="primaryAssignment?.placement_company && primaryAssignment.placement_company.id !== primaryAssignment.company_id" class="badge bg-warning-subtle text-dark border border-warning-subtle">
                    Penempatan: {{ primaryAssignment.placement_company.name }} ({{ primaryAssignment.branch?.name || '-' }})
                  </span>
                  <span v-else-if="primaryAssignment?.branch" class="badge bg-light text-secondary border">
                    Cabang: {{ primaryAssignment.branch.name }}
                  </span>
                  <span class="badge border" :class="employee.attendance_location_type === 'anywhere' || employee.can_clock_in_anywhere ? 'bg-success-subtle text-success border-success-subtle' : 'bg-primary-subtle text-primary border-primary-subtle'" :title="'Aturan lokasi absensi karyawan'">
                    <i class="mdi mdi-map-marker-radius-outline me-1"></i>
                    <template v-if="employee.attendance_location_type === 'anywhere' || employee.can_clock_in_anywhere">
                      Absen: Bebas Lokasi
                    </template>
                    <template v-else-if="employee.attendance_location_type === 'all_branches'">
                      Absen: Semua Cabang
                    </template>
                    <template v-else-if="employee.attendance_location_type === 'specific_branches'">
                      Absen: Cabang Khusus ({{ employee.allowed_branch_ids?.length || 0 }})
                    </template>
                    <template v-else>
                      Absen: Hanya Cabang Penempatan
                    </template>
                  </span>
                </div>
              </div>
              <div class="col-auto ms-auto d-none d-lg-block">
                <div class="d-flex gap-4">
                  <div>
                    <label class="text-muted mb-1 d-block small fw-semibold">Email</label>
                    <span class="text-dark fw-medium">{{ employee.email }}</span>
                  </div>
                  <div>
                    <label class="text-muted mb-1 d-block small fw-semibold">Telepon</label>
                    <span class="text-dark fw-medium">{{ employee.phone || '-' }}</span>
                  </div>
                  <div>
                    <label class="text-muted mb-1 d-block small fw-semibold">Departemen</label>
                    <span class="text-dark fw-medium">
                      {{ primaryAssignment?.department?.name || '-' }}
                      <small v-if="primaryAssignment?.department?.branch?.name || primaryAssignment?.branch?.name" class="text-muted d-block font-size-11">
                        (Cabang: {{ primaryAssignment?.department?.branch?.name || primaryAssignment?.branch?.name }})
                      </small>
                    </span>
                  </div>
                  <div>
                    <label class="text-muted mb-1 d-block small fw-semibold">Divisi</label>
                    <span class="text-dark fw-medium">{{ primaryAssignment?.division?.name || '-' }}</span>
                  </div>
                  <div>
                    <label class="text-muted mb-1 d-block small fw-semibold">Sisa Cuti</label>
                    <span class="badge bg-primary-subtle text-primary font-size-13 px-2 py-1">{{ (annualLeaveRemaining !== null && annualLeaveRemaining !== undefined) ? `${annualLeaveRemaining} Hari` : '-' }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Content Tabs -->
      <div class="col-12">
        <div class="card">
          <div class="card-body p-0">
            <!-- Nav tabs -->
            <ul class="nav nav-tabs employee-tabs" role="tablist">
              <li class="nav-item">
                <a class="nav-link active" data-bs-toggle="tab" href="#info" role="tab">Informasi Dasar</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#family" role="tab">Keluarga</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#shifts" role="tab">Jadwal Shift</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#devices" role="tab" @click="loadDevices">Perangkat Login</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#approval" role="tab" @click="loadApprovalData">Set Approval</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#assets" role="tab" @click="loadAssets">Aset</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#documents" role="tab" @click="loadDocuments">Dokumen</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#leave_balances" role="tab" @click="loadLeaveBalances">Jatah Cuti</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#mutations" role="tab" @click="loadMutationHistory">Karir & Mutasi</a>
              </li>
            </ul>

            <!-- Tab panes -->
            <div class="tab-content p-4">
              <!-- INFORMASI DASAR -->
              <div class="tab-pane active" id="info" role="tabpanel">
                <form @submit.prevent="updateEmployee">
                  <h5 class="mb-4">Update Data Pribadi</h5>
                  <div class="row">
                    <div class="col-md-6 mb-3">
                      <label class="form-label">Nama Lengkap</label>
                      <input v-model="editForm.name" type="text" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                      <label class="form-label">Nama Panggilan</label>
                      <input v-model="editForm.nickname" type="text" class="form-control">
                    </div>
                    <div class="col-md-6 mb-3">
                      <label class="form-label">NIP (Nomor Induk Pegawai)</label>
                      <input v-model="editForm.employee_id" type="text" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                      <label class="form-label">NIK (KTP)</label>
                      <input v-model="editForm.ktp_number" type="text" class="form-control">
                    </div>
                    <div class="col-md-6 mb-3">
                      <label class="form-label">NPWP</label>
                      <input v-model="editForm.npwp" type="text" class="form-control">
                    </div>
                    <div class="col-md-6 mb-3">
                      <label class="form-label">Status Pernikahan</label>
                      <select v-model="editForm.marital_status" class="form-select">
                        <option value="single">Belum Kawin</option>
                        <option value="married">Kawin</option>
                      </select>
                    </div>
                    <div class="col-md-6 mb-3">
                      <label class="form-label">Status PTKP (Pajak)</label>
                      <select v-model="editForm.ptkp_status" class="form-select bg-light" disabled>
                        <option value="">-- Tidak Ada / Default --</option>
                        <option value="TK/0">TK/0 - Tidak Kawin Tanpa Tanggungan</option>
                        <option value="TK/1">TK/1 - Tidak Kawin 1 Tanggungan</option>
                        <option value="TK/2">TK/2 - Tidak Kawin 2 Tanggungan</option>
                        <option value="TK/3">TK/3 - Tidak Kawin 3 Tanggungan</option>
                        <option value="K/0">K/0 - Kawin Tanpa Tanggungan</option>
                        <option value="K/1">K/1 - Kawin 1 Tanggungan</option>
                        <option value="K/2">K/2 - Kawin 2 Tanggungan</option>
                        <option value="K/3">K/3 - Kawin 3 Tanggungan</option>
                      </select>
                    </div>
                    <div class="col-md-6 mb-3">
                      <label class="form-label">Tanggal Lahir</label>
                      <input v-model="editForm.birth_date" type="date" class="form-control">
                    </div>
                    <div class="col-md-6 mb-3">
                      <label class="form-label">Jenis Kelamin</label>
                      <select v-model="editForm.gender" class="form-select">
                        <option value="">-- Pilih --</option>
                        <option value="male">Laki-laki</option>
                        <option value="female">Perempuan</option>
                      </select>
                    </div>
                    <div class="col-md-6 mb-3">
                      <label class="form-label">No. Telepon (HP)</label>
                      <input v-model="editForm.phone" type="text" class="form-control">
                    </div>
                    <div class="col-md-6 mb-3">
                      <label class="form-label">Foto Profil</label>
                      <input type="file" @change="handlePhotoUpload" class="form-control" accept="image/*">
                    </div>
                    <div class="col-md-12 mb-3">
                      <label class="form-label">Alamat Sesuai KTP</label>
                      <textarea v-model="editForm.address" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="col-md-6 mb-3">
                      <label class="form-label">Alamat Domisili</label>
                      <textarea v-model="editForm.domicile_address" class="form-control" rows="2" placeholder="Isi jika berbeda dengan KTP"></textarea>
                    </div>
                    <div class="col-md-6 mb-3">
                      <label class="form-label">Alamat Tinggal</label>
                      <textarea v-model="editForm.residential_address" class="form-control" rows="2" placeholder="Alamat tinggal saat ini"></textarea>
                    </div>
                    <div class="col-md-6 mb-3">
                      <label class="form-label">No. BPJS Kesehatan</label>
                      <input v-model="editForm.bpjs_kesehatan" type="text" class="form-control">
                    </div>
                    <div class="col-md-6 mb-3">
                      <label class="form-label">No. BPJS Ketenagakerjaan</label>
                      <input v-model="editForm.bpjs_ketenagakerjaan" type="text" class="form-control">
                    </div>
                  </div>

                  <hr class="my-4">
                  <h5 class="mb-4">Data Kepegawaian & Akun</h5>
                  <div class="row">
                    <div class="col-md-6 mb-3">
                      <label class="form-label">Username (Email)</label>
                      <input v-model="editForm.email" type="email" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                      <label class="form-label">Ganti Password</label>
                      <input v-model="editForm.password" type="password" class="form-control" placeholder="Kosongkan jika tidak diganti">
                    </div>
                    <div class="col-md-6 mb-3">
                      <label class="form-label">Akses Akun & Mobile</label>
                      <select v-model="editForm.is_active" class="form-select">
                        <option :value="1">Ya (Aktif)</option>
                        <option :value="0">Tidak (Nonaktif)</option>
                      </select>
                      <small class="text-muted d-block mt-1">Pilih Tidak untuk menonaktifkan akun karyawan.</small>
                    </div>

                    <div class="col-md-6 mb-3">
                      <label class="form-label">Akses Portal Web Karyawan</label>
                      <select v-model="editForm.web_access_enabled" class="form-select">
                        <option :value="1">Aktif (Dapat Login Web)</option>
                        <option :value="0">Nonaktif (Tanpa Akses Web)</option>
                      </select>
                      <small class="text-muted d-block mt-1">Izinkan karyawan ini login ke Portal Web HRMS.</small>
                    </div>

                    <div class="col-md-6 mb-3" v-if="editForm.web_access_enabled == 1">
                      <label class="form-label">Role Akses Web</label>
                      <select v-model="editForm.web_role" class="form-select">
                        <option value="">Pilih Role Web...</option>
                        <option value="super_admin">Super Admin</option>
                        <option value="staff_hr">Staff HR</option>
                        <option value="spv">Supervisor / Head (SPV)</option>
                      </select>
                      <small class="text-muted d-block mt-1">Menentukan hak akses menu di portal web.</small>
                    </div>

                    <!-- Pengaturan Lokasi Presensi (Cabang) -->
                    <div class="col-12 mb-3">
                      <div class="card border rounded-3 shadow-none bg-white">
                        <div class="card-header bg-light bg-opacity-50 py-2 px-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                          <div class="d-flex align-items-center gap-2">
                            <i class="mdi mdi-map-marker-multiple text-primary font-size-16"></i>
                            <span class="fw-semibold text-dark font-size-13">Pengaturan Lokasi Presensi (Cabang Absensi)</span>
                          </div>
                          <div class="d-flex align-items-center gap-2">
                            <button 
                              type="button" 
                              class="btn btn-sm btn-link text-primary text-decoration-none py-0 px-1 font-size-12 fw-medium"
                              @click="selectAllBranches"
                              v-if="editForm.allowed_branch_ids && editForm.allowed_branch_ids.length < branchesData.length"
                            >
                              + Pilih Semua Cabang
                            </button>
                            <span class="text-muted font-size-11" v-if="editForm.allowed_branch_ids && editForm.allowed_branch_ids.length < branchesData.length && editForm.allowed_branch_ids.length > 0">•</span>
                            <button 
                              type="button" 
                              class="btn btn-sm btn-link text-danger text-decoration-none py-0 px-1 font-size-12 fw-medium"
                              @click="clearAllBranches"
                              v-if="editForm.allowed_branch_ids && editForm.allowed_branch_ids.length > 0"
                            >
                              Kosongkan
                            </button>
                            <span 
                              class="badge font-size-11 rounded-pill ms-1"
                              :class="editForm.allowed_branch_ids && editForm.allowed_branch_ids.length === branchesData.length 
                                ? 'bg-success-subtle text-success border border-success-subtle' 
                                : (!editForm.allowed_branch_ids || editForm.allowed_branch_ids.length === 0 
                                  ? 'bg-danger-subtle text-danger border border-danger-subtle' 
                                  : 'bg-primary-subtle text-primary border border-primary-subtle')"
                            >
                              {{ editForm.allowed_branch_ids && editForm.allowed_branch_ids.length === branchesData.length ? 'Semua Cabang Diizinkan (' + branchesData.length + ')' : `${editForm.allowed_branch_ids?.length || 0} dari ${branchesData.length} Cabang` }}
                            </span>
                          </div>
                        </div>

                        <div class="card-body p-3">
                          <div class="text-muted font-size-12 mb-2">
                            Daftar cabang tempat karyawan berhak melakukan presensi absensi. Klik tanda silang (×) untuk mencabut hak absen pada cabang terkait.
                          </div>

                          <!-- Wadah Chip Cabang Tunggal (Clean & Unified Tag List) -->
                          <div class="p-2.5 rounded-3 border bg-light bg-opacity-40">
                            <div class="d-flex flex-wrap gap-2 align-items-center">
                              <!-- Label Chip Cabang Aktif -->
                              <span 
                                v-for="b in selectedSpecificBranches" 
                                :key="b.id"
                                class="branch-pill d-inline-flex align-items-center gap-1.5 py-1 px-2.5 rounded-pill font-size-12 bg-white border shadow-xs"
                              >
                                <i class="mdi mdi-map-marker text-primary"></i>
                                <span class="fw-medium text-dark">{{ b.name }}</span>
                                <span class="text-muted font-size-11">({{ b.attendance_radius || 20 }}m)</span>
                                <span v-if="!b.latitude || !b.longitude" class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill font-size-9 px-1.5 py-0.5">No GPS</span>
                                <button 
                                  type="button" 
                                  class="btn-remove-tag ms-1" 
                                  @click="removeAllowedBranch(b.id)"
                                  title="Hapus cabang ini"
                                  aria-label="Remove"
                                >
                                  &times;
                                </button>
                              </span>

                              <!-- Dropdown Tambah Cabang (+ Tambah Cabang jika ada yang belum terpilih) -->
                              <div class="d-inline-flex align-items-center" v-if="unselectedBranches.length > 0">
                                <select 
                                  class="form-select form-select-sm py-1 px-2.5 font-size-12 rounded-pill border-dashed bg-white text-primary fw-medium cursor-pointer shadow-none"
                                  style="width: auto; min-width: 155px; border-color: #3b82f6;"
                                  @change="addBranchFromSelect($event)"
                                >
                                  <option value="" disabled selected>+ Tambah Cabang...</option>
                                  <option v-for="ub in unselectedBranches" :key="ub.id" :value="ub.id">
                                    + {{ ub.name }} ({{ ub.attendance_radius || 20 }}m)
                                  </option>
                                </select>
                              </div>

                              <!-- Empty State jika tidak ada yang dipilih -->
                              <div v-if="!editForm.allowed_branch_ids || editForm.allowed_branch_ids.length === 0" class="text-danger small font-size-12 py-1 w-100 text-center">
                                <i class="mdi mdi-alert-circle-outline me-1"></i>
                                Belum ada cabang yang diizinkan. Karyawan tidak dapat melakukan absensi mobile di cabang manapun.
                              </div>
                            </div>
                          </div>

                          <!-- Switch Bebas Presensi -->
                          <div class="mt-2.5 pt-2 border-top d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <div class="form-check form-switch m-0">
                              <input 
                                class="form-check-input cursor-pointer" 
                                type="checkbox" 
                                role="switch" 
                                id="canClockInAnywhereSwitch" 
                                v-model="editForm.can_clock_in_anywhere" 
                                :true-value="1" 
                                :false-value="0"
                                @change="editForm.attendance_location_type = editForm.can_clock_in_anywhere == 1 ? 'anywhere' : 'specific_branches'"
                              />
                              <label class="form-check-label small text-secondary fw-medium cursor-pointer" for="canClockInAnywhereSwitch">
                                Bebas Presensi (Dapat absen di mana saja tanpa batasan radius GPS)
                              </label>
                            </div>
                            <span class="text-muted font-size-11" v-if="editForm.can_clock_in_anywhere == 1">
                              *Karyawan ini dapat clock-in/out dari mana saja.
                            </span>
                          </div>
                        </div>
                      </div>
                    </div>

                    <div class="col-12 mt-4 mb-3">
                      <div class="d-flex justify-content-between align-items-center border-bottom pb-2">
                        <div>
                          <h5 class="fw-bold text-dark mb-0 font-size-15">Penempatan Karyawan</h5>
                          <span class="text-muted small">Atur perusahaan asal, lokasi penempatan kerja, cabang, dan departemen karyawan.</span>
                        </div>
                        <button type="button" class="btn btn-sm btn-primary" @click="addAssignment">
                          + Tambah Posisi
                        </button>
                      </div>
                    </div>
                    
                    <div class="col-12">
                      <div v-for="(assign, index) in editForm.assignments" :key="index" class="card border shadow-sm mb-4 rounded-3 overflow-hidden">
                        <div class="card-header bg-light py-2 px-3 border-bottom d-flex justify-content-between align-items-center">
                          <div class="d-flex align-items-center gap-2">
                            <span class="fw-bold text-dark font-size-14">Posisi #{{ index + 1 }}</span>
                            <span v-if="assign.is_primary" class="badge bg-success-subtle text-success border border-success-subtle font-size-11">Penempatan Utama</span>
                            <span v-else class="badge bg-white text-secondary border font-size-11">Posisi Tambahan</span>
                          </div>
                          <div class="d-flex align-items-center gap-3">
                            <div class="form-check form-switch m-0" v-if="!assign.is_primary">
                              <input class="form-check-input" type="checkbox" role="switch" :id="'primarySwitch' + index" @change="setPrimaryAssignment(index)">
                              <label class="form-check-label small text-muted" :for="'primarySwitch' + index">Jadikan Utama</label>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2 font-size-12" @click="removeAssignment(index)" v-if="editForm.assignments.length > 1">
                              Hapus Posisi
                            </button>
                          </div>
                        </div>

                        <div class="card-body p-3 bg-white">
                          <!-- Penanda jika ditempatkan di perusahaan berbeda -->
                          <div v-if="assign.placement_company_id && assign.placement_company_id != assign.company_id" class="alert alert-warning border border-warning-subtle py-2 px-3 small rounded-2 mb-3">
                            <i class="mdi mdi-information-outline me-1"></i><strong>Status Penempatan:</strong> Karyawan ini bertugas di lokasi anak/mitra perusahaan <span class="badge bg-warning text-dark">{{ getCompanyName(assign.placement_company_id) }}</span>, namun administrasi kontrak dan slip gaji tetap diproses oleh perusahaan legal asal.
                          </div>

                          <!-- Bagian 1: Entitas & Lokasi Penempatan -->
                          <div class="mb-3">
                            <div class="fw-bold text-secondary small text-uppercase pb-1 border-bottom mb-2 font-size-11" style="letter-spacing: 0.5px;">
                              1. Entitas & Lokasi Penempatan
                            </div>
                            <div class="row g-2">
                              <div class="col-md-6 mb-1">
                                <label class="form-label small mb-1 fw-semibold text-dark">Perusahaan Penggajian (Legal) <span class="text-danger">*</span></label>
                                <select v-model="assign.company_id" class="form-select bg-white" @change="handleCompanyChange(assign)">
                                  <option value="">-- Pilih Perusahaan Penggajian --</option>
                                  <option v-for="opt in companyOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                                </select>
                                <div class="form-text text-muted font-size-11">Perusahaan yang menaungi status kontrak dan penggajian.</div>
                              </div>
                              <div class="col-md-6 mb-1">
                                <label class="form-label small mb-1 fw-semibold text-dark">Area Penempatan</label>
                                <select v-model="assign.branch_id" class="form-select bg-white" @change="handleBranchPlacementChange(assign)">
                                  <option value="">-- Pilih Cabang / Area Penempatan --</option>
                                  <option v-for="opt in branchPlacementOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                                </select>
                                <div class="form-text text-muted font-size-11">Pilih cabang penempatan (bisa cabang dari perusahaan lain).</div>
                              </div>
                            </div>
                          </div>

                          <!-- Bagian 2: Jabatan & Struktur Organisasi -->
                          <div class="mb-3">
                            <div class="fw-bold text-secondary small text-uppercase pb-1 border-bottom mb-2 font-size-11" style="letter-spacing: 0.5px;">
                              2. Jabatan & Struktur Organisasi
                            </div>
                            <div class="row g-2">
                              <div class="col-md-3 mb-1">
                                <label class="form-label small mb-1 fw-semibold text-dark">Departemen</label>
                                <select v-model="assign.department_id" class="form-select bg-white">
                                  <option value="">-- Pilih Departemen --</option>
                                  <option v-for="opt in departmentOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                                </select>
                              </div>
                              <div class="col-md-3 mb-1">
                                <label class="form-label small mb-1 fw-semibold text-dark">Divisi</label>
                                <select v-model="assign.division_id" class="form-select bg-white">
                                  <option value="">-- Pilih Divisi --</option>
                                  <option v-for="opt in divisionOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                                </select>
                              </div>
                              <div class="col-md-3 mb-1">
                                <label class="form-label small mb-1 fw-semibold text-dark">Jabatan</label>
                                <select v-model="assign.position_id" class="form-select bg-white">
                                  <option value="">-- Pilih Jabatan --</option>
                                  <option v-for="opt in getAvailablePositions(assign)" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                                </select>
                              </div>
                              <div class="col-md-3 mb-1">
                                <label class="form-label small mb-1 fw-semibold text-dark">Atasan Langsung</label>
                                <select v-model="assign.supervisor_id" class="form-select bg-white">
                                  <option value="">-- Pilih Atasan --</option>
                                  <option v-for="opt in employeeOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                                </select>
                              </div>
                            </div>
                          </div>

                          <!-- Bagian 3: Hubungan Kerja & Skema Gaji -->
                          <div class="mb-3">
                            <div class="fw-bold text-secondary small text-uppercase pb-1 border-bottom mb-2 font-size-11" style="letter-spacing: 0.5px;">
                              3. Status Kepegawaian & Skema Penggajian
                            </div>
                            <div class="row g-2">
                              <div class="col-md-4 mb-1">
                                <label class="form-label small mb-1 fw-semibold text-dark">Status Karyawan</label>
                                <select v-model="assign.employment_status" class="form-select bg-white">
                                  <option value="internship">Magang</option>
                                  <option value="probation">Percobaan</option>
                                  <option value="permanent">Tetap</option>
                                  <option value="contract">Kontrak</option>
                                </select>
                              </div>
                              <div class="col-md-4 mb-1">
                                <label class="form-label small mb-1 fw-semibold text-dark">Tanggal Mulai Posisi</label>
                                <input v-model="assign.join_date" type="date" class="form-control bg-white" />
                              </div>
                              <div class="col-md-4 mb-1">
                                <label class="form-label small mb-1 fw-semibold text-dark">Skema Pembayaran Gaji</label>
                                <select v-model="assign.salary_cycle_type" class="form-select bg-white">
                                  <option value="">-- Pilih Skema Pembayaran --</option>
                                  <option value="MONTHLY_PAID_MONTHLY">Gaji Bulanan</option>
                                  <option value="DAILY_PAID_DAILY">Gaji Harian</option>
                                </select>
                              </div>
                            </div>
                          </div>

                          <!-- Bagian 4: Komponen Gaji Aktif (Disembunyikan untuk Level Staff) -->
                          <div v-if="!isStaffUser" class="pt-1">
                            <div class="d-flex justify-content-between align-items-center pb-1 border-bottom mb-2">
                              <span class="fw-bold text-secondary small text-uppercase font-size-11" style="letter-spacing: 0.5px;">
                                4. Komponen Gaji Aktif
                              </span>
                              <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 font-size-12" @click="pullAssignmentSalary(assign)" :disabled="pullingSalary || !assign.salary_cycle_type">
                                Tarik Komponen dari Jabatan
                              </button>
                            </div>
                            
                            <div v-if="!assign.salary_cycle_type" class="col-12 py-3 text-center text-muted border rounded bg-light mt-1 small">
                              Pilih Skema Pembayaran Gaji terlebih dahulu untuk mengaktifkan daftar komponen gaji posisi ini.
                            </div>
                                <div v-else class="row px-2">
                                  <div class="col-12" v-if="getComponentsForAssignment(assign).filter(c => c.calculation_method === 'FIXED_AMOUNT').length > 0">
                                    <h6 class="text-primary small fw-bold mb-2 border-bottom pb-1">Komponen Nominal (Wajib Diisi jika dicentang)</h6>
                                  </div>
                                  <div class="col-md-4 col-sm-6 mb-2" v-for="comp in getComponentsForAssignment(assign).filter(c => c.calculation_method === 'FIXED_AMOUNT')" :key="comp.id">
                                    <div class="form-check mb-1">
                                      <input class="form-check-input" type="checkbox" :id="'comp' + index + comp.id" 
                                        :checked="isAssignmentCompSelected(assign, comp.id)" 
                                        :disabled="isComponentRequired(assign, comp.id)"
                                        @change="toggleAssignmentComp(assign, comp.id, $event.target.checked)">
                                      <label class="form-check-label small" :for="'comp' + index + comp.id" :class="{'text-muted': isComponentRequired(assign, comp.id)}">
                                        {{ comp.name }} <span v-if="isComponentRequired(assign, comp.id)" class="text-danger">*</span>
                                      </label>
                                    </div>
                                    <div v-if="isAssignmentCompSelected(assign, comp.id)" class="ps-4">
                                      <input type="text" class="form-control form-control-sm border-primary" placeholder="Wajib Diisi (Nominal)" required
                                        :value="formatInputNominal(getAssignmentCompAmount(assign, comp.id))"
                                        @input="updateAssignmentCompAmount(assign, comp.id, $event.target.value)">
                                    </div>
                                  </div>

                                  <div class="col-12 mt-3" v-if="getComponentsForAssignment(assign).filter(c => c.calculation_method !== 'FIXED_AMOUNT').length > 0">
                                    <h6 class="text-primary small fw-bold mb-2 border-bottom pb-1">Komponen Formula (Otomatis)</h6>
                                  </div>
                                  <div class="col-md-4 col-sm-6 mb-2" v-for="comp in getComponentsForAssignment(assign).filter(c => c.calculation_method !== 'FIXED_AMOUNT')" :key="comp.id">
                                    <div class="form-check mb-1">
                                      <input class="form-check-input" type="checkbox" :id="'comp' + index + comp.id" 
                                        :checked="isAssignmentCompSelected(assign, comp.id)" 
                                        @change="toggleAssignmentComp(assign, comp.id, $event.target.checked)">
                                      <label class="form-check-label small" :for="'comp' + index + comp.id">
                                        {{ comp.name }}
                                      </label>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                  <div class="text-end">
                    <button type="submit" class="btn btn-primary" :disabled="savingEmployee">
                      {{ savingEmployee ? 'Menyimpan...' : 'Simpan Perubahan' }}
                    </button>
                  </div>
                </form>
              </div>

              <!-- KELUARGA -->
              <div class="tab-pane" id="family" role="tabpanel">
                <CrudPage 
                  title="Anggota Keluarga"
                  singular="Keluarga"
                  endpoint="/families"
                  :hideHeader="true"
                  :hideCard="true"
                  :baseFilters="{ employee_id: route.params.id }"
                  :columns="[
                    { key: 'name', label: 'Nama' },
                    { key: 'relation', label: 'Hubungan' },
                    { key: 'birth_date', label: 'Tgl Lahir' },
                    { key: 'phone', label: 'Telepon' },
                    { key: 'is_dependent', label: 'Tanggungan BPJS', format: val => val ? 'Ya' : 'Tidak' }
                  ]"
                  :formFields="[
                    { key: 'name', label: 'Nama Lengkap', required: true },
                    { key: 'relation', label: 'Hubungan', required: true, type: 'select', options: [
                      {value:'spouse', label:'Suami/Istri'},
                      {value:'child', label:'Anak'},
                      {value:'parent', label:'Orang Tua'}
                    ]},
                    { key: 'birth_date', label: 'Tanggal Lahir', type: 'date' },
                    { key: 'phone', label: 'Nomor Telepon' },
                    { key: 'occupation', label: 'Pekerjaan' },
                    { key: 'domicile_address', label: 'Alamat Domisili' },
                    { key: 'residential_address', label: 'Alamat Tinggal' },
                    { key: 'is_dependent', label: 'Masuk Tanggungan BPJS?', type: 'select', required: true, options: [{value:true, label:'Ya'}, {value:false, label:'Tidak'}] },
                    { key: 'is_tax_dependent', label: 'Masuk Tanggungan Pajak (PTKP)?', type: 'select', required: true, options: [{value:true, label:'Ya'}, {value:false, label:'Tidak'}] }
                  ]"
                  :initialForm="() => ({ employee_id: employee.id, is_dependent: 1, is_tax_dependent: 1 })"
                />
              </div>

              <!-- SHIFT -->
              <div class="tab-pane" id="shifts" role="tabpanel">
                <CrudPage 
                  title="Jadwal Shift Bulanan"
                  singular="Jadwal"
                  endpoint="/employee-shifts"
                  :hideHeader="true"
                  :hideCard="true"
                  ref="crudShifts"
                  :defaultPerPage="50"
                  :baseFilters="{ employee_id: route.params.id }"
                  :filters="[
                    { key: 'month', label: 'Bulan', type: 'month', default: currentMonth }
                  ]"
                  :columns="[
                    { key: 'effective_date', label: 'Tanggal' },
                    { key: 'shift', sub: 'name', label: 'Shift', format: val => val || 'Libur' },
                    { key: 'shift', sub: 'clock_in', label: 'Clock In', format: val => val || '-' },
                    { key: 'shift', sub: 'clock_out', label: 'Clock Out', format: val => val || '-' }
                  ]"
                  :formFields="[
                    { key: 'effective_date', label: 'Tanggal Berlaku', type: 'date', required: true },
                    { key: 'shift_id', label: 'Shift', type: 'select', required: true, options: shiftOptions }
                  ]"
                  :initialForm="() => ({ employee_id: employee.id, is_active: 1 })"
                >
                  <template v-slot:header-actions>
                    <button class="btn btn-danger btn-sm waves-effect waves-light" @click="showGenerateModal = true" title="Generate Shift">
                      <img src="../assets/generate.svg" alt="" width="12" height="12" style="margin-right: 5px;" > Generate
                    </button>
                    <button class="btn btn-warning btn-sm waves-effect waves-light" @click="showPatternModal = true" title="Konfigurasi Pola Shift">
                      <img src="../assets/settingshift.svg" alt="" width="12" height="12" style="margin-right: 5px;" > Pola Shift
                    </button>
                  </template>
                </CrudPage>

                <!-- Modal Generate Shift -->
                <div v-if="showGenerateModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px);">
                  <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow-lg rounded-4">
                      <div class="modal-header border-bottom-0 pt-4 px-4 pb-2">
                        <h5 class="modal-title fw-bold fs-4 text-dark">Generate Shift Otomatis</h5>
                        <button type="button" class="btn-close shadow-none" @click="showGenerateModal = false" aria-label="Close"></button>
                      </div>
                      <div class="modal-body px-4 pb-4 pt-2">
                        <p class="text-muted mb-4">Generate jadwal shift sebulan penuh berdasarkan pola shift yang sudah dikonfigurasi.</p>
                        <form @submit.prevent="generateShifts">
                          <div class="row">
                            <div class="col-md-6 mb-3">
                              <label class="form-label">Bulan</label>
                              <select v-model="generateForm.month" class="form-select bg-light border-0 shadow-none" required>
                                <option v-for="m in 12" :key="m" :value="m">{{ m }}</option>
                              </select>
                            </div>
                            <div class="col-md-6 mb-3">
                              <label class="form-label">Tahun</label>
                              <input v-model="generateForm.year" type="number" class="form-control bg-light border-0 shadow-none" required>
                            </div>
                          </div>
                          <div class="text-end mt-2">
                            <button type="button" class="btn btn-light me-2 fw-medium px-4 rounded-3" @click="showGenerateModal = false">Batal</button>
                            <button type="submit" class="btn btn-primary fw-medium px-4 rounded-3 shadow-sm" :disabled="generatingShifts">
                              <i class="ri-calendar-event-line me-1"></i>
                              {{ generatingShifts ? 'Generating...' : 'Generate' }}
                            </button>
                          </div>
                        </form>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Modal Konfigurasi Pola -->
                <div v-if="showPatternModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px);">
                  <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow-lg rounded-4">
                      <div class="modal-header border-bottom-0 pt-4 px-4 pb-2">
                        <h5 class="modal-title fw-bold fs-4 text-dark">Pola Shift Mingguan</h5>
                        <button type="button" class="btn-close shadow-none" @click="showPatternModal = false" aria-label="Close"></button>
                      </div>
                      <div class="modal-body px-4 pb-4 pt-2">
                        <form @submit.prevent="saveShiftPattern">
                          <div class="table-responsive mb-3">
                            <table class="table table-bordered">
                              <thead>
                                <tr>
                                  <th>Hari</th>
                                  <th>Pilih Shift (Kosongkan = Libur)</th>
                                </tr>
                              </thead>
                              <tbody>
                                <tr v-for="(day, idx) in days" :key="idx">
                                  <td>{{ day }}</td>
                                  <td>
                                    <select v-model="patternForm[idx]" class="form-select bg-light border-0 shadow-none">
                                      <option :value="null">-- Libur --</option>
                                      <option v-for="sh in shiftOptions" :key="sh.value" :value="sh.value">{{ sh.label }}</option>
                                    </select>
                                  </td>
                                </tr>
                              </tbody>
                            </table>
                          </div>
                          <div class="text-end">
                            <button type="button" class="btn btn-light me-2 waves-effect fw-medium px-4 rounded-3" @click="showPatternModal = false">Batal</button>
                            <button type="submit" class="btn btn-primary fw-medium px-4 rounded-3 shadow-sm" :disabled="savingPattern">
                              {{ savingPattern ? 'Menyimpan...' : 'Simpan Pola' }}
                            </button>
                          </div>
                        </form>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- PERANGKAT LOGIN (DEVICE LOCK) -->
              <div class="tab-pane" id="devices" role="tabpanel">
                <div class="row mb-4 align-items-center">
                  <div class="col-sm-12 col-md-5 mb-3 mb-md-0">
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                      <div class="d-flex align-items-center text-muted">
                        <span class="me-2 text-nowrap">Tampilkan</span>
                        <select class="form-select form-select-sm" style="width: 70px; border-radius: 8px; font-weight: 500;" disabled>
                          <option value="10">10</option>
                        </select>
                        <span class="ms-2 text-nowrap">data</span>
                      </div>
                      <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-primary waves-effect waves-light" @click="loadDevices">
                          <i class="uim uim-refresh"></i> Refresh Data
                        </button>
                      </div>
                    </div>
                  </div>
                </div>

                <div v-if="loadingDevices" class="text-center py-4">
                  <div class="spinner-border text-primary" role="status"></div>
                </div>

                <div v-else-if="devices.length === 0" class="text-center py-4 text-muted">
                  <p class="mt-2">Belum ada perangkat yang terdaftar</p>
                </div>

                <div v-else class="table-responsive">
                  <table class="table table-bordered table-nowrap">
                    <thead class="table-light">
                      <tr>
                        <th>Nama Perangkat</th>
                        <th>Platform</th>
                        <th>Device ID</th>
                        <th>Status</th>
                        <th>Terakhir Aktif</th>
                        <th>Terdaftar</th>
                        <th>Aksi</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="dev in devices" :key="dev.id">
                        <td><strong>{{ dev.device_name || '–' }}</strong></td>
                        <td><span class="badge bg-secondary text-capitalize">{{ dev.platform || '–' }}</span></td>
                        <td><code class="text-xs">{{ dev.device_id ? dev.device_id.substring(0, 16) + '...' : '–' }}</code></td>
                        <td>
                          <span :class="{
                            'badge bg-success': dev.status === 'approved',
                            'badge bg-warning text-dark': dev.status === 'pending',
                            'badge bg-danger': dev.status === 'rejected',
                          }">
                            {{ dev.status === 'approved' ? '✅ Disetujui' : dev.status === 'pending' ? '⏳ Menunggu' : '❌ Ditolak' }}
                          </span>
                        </td>
                        <td>{{ dev.last_active_at ? new Date(dev.last_active_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) : '–' }}</td>
                        <td>{{ dev.created_at ? new Date(dev.created_at).toLocaleDateString('id-ID', { timeZone: 'Asia/Jakarta' }) : '–' }}</td>
                        <td>
                          <div class="d-flex gap-2">
                            <button v-if="dev.status !== 'approved'"
                              class="btn btn-success btn-sm"
                              :disabled="approvingDevice === dev.id"
                              @click="approveDevice(dev)">
                              {{ approvingDevice === dev.id ? '...' : 'Setujui' }}
                            </button>
                            <button v-if="dev.status !== 'rejected'"
                              class="btn btn-danger btn-sm"
                              :disabled="approvingDevice === dev.id"
                              @click="rejectDevice(dev)">
                              Tolak
                            </button>
                            <span v-if="dev.status === 'approved'" class="text-muted small">Aktif</span>
                          </div>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                <div class="alert alert-info mt-3" role="alert">
                  <strong>Kebijakan Device Lock:</strong> Karyawan hanya diizinkan login dari <strong>1 perangkat</strong> berstatus Disetujui.
                  Jika karyawan berganti handphone, perangkat baru akan muncul berstatus <em>Menunggu</em> dan harus disetujui Admin.
                  Menyetujui perangkat baru akan otomatis mencabut akses perangkat lama.
                </div>
              </div>

              <!-- APPROVAL -->
              <div class="tab-pane" id="approval" role="tabpanel">
                <div v-if="approvalLoading" class="text-center py-5">
                  <div class="spinner-border text-primary"></div>
                  <p class="mt-2 text-muted">Memuat data approval...</p>
                </div>
                <div v-else class="row">
                  <!-- Sidebar Mini -->
                  <div class="col-md-4 col-lg-3 border-end mb-4 mb-md-0">
                    <h6 class="mb-3 text-secondary text-uppercase fw-bold" style="font-size: 0.75rem; letter-spacing: 0.5px;">Modul Pengajuan</h6>
                    <div class="list-group list-group-flush" style="margin-left: -1rem; margin-right: -1rem;">
                      <button v-for="m in approvalModules" :key="m.key"
                        class="list-group-item list-group-item-action border-0 py-3 px-4"
                        :class="activeApprovalModule === m.key ? 'active fw-medium bg-primary text-white' : 'text-dark'"
                        @click="selectApprovalModule(m.key)" style="border-radius: 0;">
                        {{ m.label }}
                      </button>
                    </div>
                  </div>
                  
                  <!-- Konten Kanan -->
                  <div class="col-md-8 col-lg-9 px-md-4">
                    <div v-if="activeApprovalModule">
                      <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                          <h5 class="mb-1">Alur Persetujuan: {{ approvalModules.find(m=>m.key===activeApprovalModule)?.label }}</h5>
                          <p class="text-muted small mb-0">Atur siapa yang harus menyetujui pengajuan secara bertingkat.</p>
                        </div>
                        <button class="btn btn-sm btn-outline-primary" @click="addApprovalStep">Tambah Tingkat</button>
                      </div>

                      <div class="card border border-light shadow-none">
                        <div class="card-body">
                          <div v-if="currentSteps.length === 0" class="text-center py-5 text-muted">
                            <p class="mb-0">Belum ada tingkat persetujuan. Klik "Tambah Tingkat" di atas untuk menambahkan.</p>
                          </div>
                          
                          <div v-for="(step, idx) in currentSteps" :key="idx" class="d-flex align-items-end gap-3 mb-3 p-3 border rounded-3 bg-light">
                            <div class="text-center" style="min-width: 40px;">
                              <span class="badge bg-secondary rounded-circle px-2 py-2">{{ idx+1 }}</span>
                            </div>
                            <div class="flex-grow-1">
                              <label class="form-label small text-secondary">Approver Tingkat {{ idx+1 }}</label>
                              <select v-model="step.approver_id" class="form-select form-select-sm">
                                <option value="">-- Pilih Karyawan --</option>
                                <option v-for="emp in allEmployees" :key="emp.id" :value="emp.id">{{ emp.name }} ({{ emp.employee_id }})</option>
                              </select>
                            </div>
                            <div>
                              <label class="form-label small text-secondary">Opsional</label>
                              <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" v-model="step.is_optional">
                              </div>
                            </div>
                            <button class="btn btn-sm btn-outline-danger" @click="removeApprovalStep(idx)">Hapus</button>
                          </div>
                          
                          <div class="d-flex justify-content-end gap-2 mt-4" v-if="currentSteps.length > 0">
                            <button class="btn btn-outline-danger btn-sm" @click="deleteModuleLines" :disabled="savingApproval">Hapus Semua</button>
                            <button class="btn btn-primary btn-sm px-4" @click="saveApprovalLines" :disabled="savingApproval">
                              {{ savingApproval ? 'Menyimpan...' : 'Simpan Alur' }}
                            </button>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div v-else class="text-center py-5 text-muted border rounded-3 bg-light">
                      <p class="mb-0">Silakan pilih modul di sebelah kiri untuk mengatur alur persetujuan.</p>
                    </div>
                  </div>
                </div>
              </div><!-- /#approval -->

              <!-- ASET -->
              <div class="tab-pane" id="assets" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-4">
                  <h5 class="mb-0">Daftar Aset Perusahaan</h5>
                  <button class="btn btn-sm btn-outline-secondary" @click="loadAssets">
                    <i class="uim uim-refresh"></i> Refresh
                  </button>
                </div>

                <div v-if="loadingAssets" class="text-center py-4">
                  <div class="spinner-border text-primary" role="status"></div>
                </div>

                <div v-else-if="assets.length === 0" class="text-center py-4 text-muted">
                  <i class="ri-macbook-line fs-1 mb-2 d-block"></i>
                  <p class="mt-2">Belum ada aset yang dipinjamkan</p>
                </div>

                <div v-else class="table-responsive">
                  <table class="table table-bordered table-nowrap">
                    <thead class="table-light">
                      <tr>
                        <th>Serial Number</th>
                        <th>Nama Aset</th>
                        <th>Tipe</th>
                        <th>Status</th>
                        <th>Tanggal Pembelian</th>
                        <th>Keterangan</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="asset in assets" :key="asset.id">
                        <td><code>{{ asset.serial_number || '-' }}</code></td>
                        <td><strong>{{ asset.name || '-' }}</strong></td>
                        <td>{{ asset.type || '-' }}</td>
                        <td>
                          <span :class="{
                            'badge bg-success': asset.status === 'Tersedia' || asset.status === 'Dipinjam',
                            'badge bg-warning': asset.status === 'Perbaikan',
                            'badge bg-danger': asset.status === 'Rusak'
                          }">{{ asset.status || '-' }}</span>
                        </td>
                        <td>{{ asset.purchase_date || '-' }}</td>
                        <td>{{ asset.notes || '-' }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div><!-- /#assets -->

              <!-- DOKUMEN, PENDIDIKAN, PELATIHAN -->
              <div class="tab-pane" id="documents" role="tabpanel">
                
                <!-- Navigasi Kategori (Pill Switcher) -->
                <div class="d-flex align-items-center justify-content-between mb-4 pb-3 border-bottom flex-wrap gap-2">
                  <div class="nav nav-pills bg-light p-1 rounded-3" role="tablist">
                    <button 
                      type="button" 
                      class="nav-link btn-sm py-1 px-3 rounded-2 fw-medium border-0 transition" 
                      :class="activeDocTab === 'all' ? 'active bg-white text-primary shadow-sm' : 'text-secondary'"
                      @click="activeDocTab = 'all'"
                    >
                      Semua Bagian
                    </button>
                    <button 
                      type="button" 
                      class="nav-link btn-sm py-1 px-3 rounded-2 fw-medium border-0 transition" 
                      :class="activeDocTab === 'documents' ? 'active bg-white text-primary shadow-sm' : 'text-secondary'"
                      @click="activeDocTab = 'documents'"
                    >
                      Dokumen Berkas ({{ documents.length }})
                    </button>
                    <button 
                      type="button" 
                      class="nav-link btn-sm py-1 px-3 rounded-2 fw-medium border-0 transition" 
                      :class="activeDocTab === 'educations' ? 'active bg-white text-primary shadow-sm' : 'text-secondary'"
                      @click="activeDocTab = 'educations'"
                    >
                      Riwayat Pendidikan ({{ educations.length }})
                    </button>
                    <button 
                      type="button" 
                      class="nav-link btn-sm py-1 px-3 rounded-2 fw-medium border-0 transition" 
                      :class="activeDocTab === 'trainings' ? 'active bg-white text-primary shadow-sm' : 'text-secondary'"
                      @click="activeDocTab = 'trainings'"
                    >
                      Pelatihan & Sertifikasi ({{ trainings.length }})
                    </button>
                  </div>

                  <div class="text-muted small">
                    Data administrasi dan kualifikasi karyawan
                  </div>
                </div>

                <!-- BAGIAN 1: DOKUMEN BERKAS -->
                <div v-show="activeDocTab === 'all' || activeDocTab === 'documents'" class="card border rounded-3 mb-4 shadow-none">
                  <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom flex-wrap gap-2">
                    <div>
                      <h6 class="mb-1 fw-bold text-dark">Dokumen Berkas</h6>
                      <p class="text-muted small mb-0">Berkas identitas dan kepegawaian (CV, KTP, KK, SKCK, Ijazah, dll)</p>
                    </div>
                    <button 
                      type="button" 
                      class="btn btn-sm px-3 fw-medium" 
                      :class="showDocumentForm ? 'btn-light text-secondary' : 'btn-primary'"
                      @click="showDocumentForm = !showDocumentForm"
                    >
                      {{ showDocumentForm ? 'Batal' : '+ Tambah Dokumen' }}
                    </button>
                  </div>

                  <!-- Form Unggah Dokumen -->
                  <div v-if="showDocumentForm" class="p-3 bg-light bg-opacity-50 border-bottom">
                    <div class="row g-3 align-items-end">
                      <div class="col-md-4">
                        <label class="form-label small fw-semibold text-secondary mb-1">Jenis Dokumen <span class="text-danger">*</span></label>
                        <select v-model="documentForm.document_type" class="form-select form-select-sm bg-white">
                          <option value="">-- Pilih Jenis Dokumen --</option>
                          <option value="CV">CV / Resume</option>
                          <option value="KTP">KTP</option>
                          <option value="KK">Kartu Keluarga (KK)</option>
                          <option value="SKCK">SKCK</option>
                          <option value="Ijazah">Ijazah</option>
                          <option value="Kontrak Kerja">Kontrak Kerja</option>
                          <option value="Sertifikat">Sertifikat</option>
                          <option value="Lainnya">Lainnya</option>
                        </select>
                      </div>
                      <div class="col-md-5">
                        <label class="form-label small fw-semibold text-secondary mb-1">Pilih File <span class="text-danger">*</span></label>
                        <input type="file" @change="handleDocumentFile" class="form-control form-control-sm bg-white" accept=".pdf,.jpg,.jpeg,.png">
                        <div class="form-text text-muted font-size-11 mt-1">Mendukung file PDF, JPG, PNG (Maks. 5 MB)</div>
                      </div>
                      <div class="col-md-3 d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-light flex-fill fw-medium" @click="showDocumentForm = false">Batal</button>
                        <button type="button" class="btn btn-sm btn-primary flex-fill fw-medium" @click="uploadDocument" :disabled="uploadingDocument">
                          {{ uploadingDocument ? 'Mengunggah...' : 'Unggah File' }}
                        </button>
                      </div>
                    </div>
                  </div>

                  <!-- Tabel Dokumen -->
                  <div class="card-body p-0">
                    <div v-if="loadingDocuments" class="text-center py-4">
                      <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                    </div>
                    <div v-else-if="documents.length === 0" class="text-center py-5 text-muted">
                      <p class="mb-0 small">Belum ada dokumen berkas yang diunggah.</p>
                    </div>
                    <div v-else class="table-responsive">
                      <table class="table table-hover align-middle mb-0 font-size-13">
                        <thead class="table-light">
                          <tr>
                            <th class="ps-3" style="width: 200px;">Jenis Dokumen</th>
                            <th>Nama File</th>
                            <th style="width: 130px;">Ukuran</th>
                            <th style="width: 160px;">Tanggal Unggah</th>
                            <th class="text-end pe-3" style="width: 180px;">Aksi</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="doc in documents" :key="doc.id">
                            <td class="ps-3">
                              <span class="badge bg-light text-primary border px-2 py-1">{{ doc.document_type }}</span>
                            </td>
                            <td>
                              <div class="fw-medium text-dark">{{ doc.file_name }}</div>
                            </td>
                            <td class="text-muted">{{ formatDocFileSize(doc.file_size) }}</td>
                            <td class="text-muted">{{ formatDocDate(doc.created_at) }}</td>
                            <td class="text-end pe-3">
                              <a :href="getFileUrl(doc.file_path)" target="_blank" class="btn btn-sm btn-light text-primary py-1 px-2 me-2">Buka File</a>
                              <button type="button" class="btn btn-sm btn-light text-danger py-1 px-2" @click="deleteDocument(doc.id)">Hapus</button>
                            </td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>

                <!-- BAGIAN 2: RIWAYAT PENDIDIKAN -->
                <div v-show="activeDocTab === 'all' || activeDocTab === 'educations'" class="card border rounded-3 mb-4 shadow-none">
                  <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom flex-wrap gap-2">
                    <div>
                      <h6 class="mb-1 fw-bold text-dark">Riwayat Pendidikan</h6>
                      <p class="text-muted small mb-0">Jenjang pendidikan formal dan perguruan tinggi</p>
                    </div>
                    <button 
                      type="button" 
                      class="btn btn-sm px-3 fw-medium" 
                      :class="showEducationForm ? 'btn-light text-secondary' : 'btn-primary'"
                      @click="showEducationForm = !showEducationForm"
                    >
                      {{ showEducationForm ? 'Batal' : '+ Tambah Pendidikan' }}
                    </button>
                  </div>

                  <!-- Form Pendidikan -->
                  <div v-if="showEducationForm" class="p-3 bg-light bg-opacity-50 border-bottom">
                    <div class="row g-3">
                      <div class="col-md-3">
                        <label class="form-label small fw-semibold text-secondary mb-1">Tingkat / Jenjang <span class="text-danger">*</span></label>
                        <select v-model="educationForm.level" class="form-select form-select-sm bg-white">
                          <option value="">-- Pilih Jenjang --</option>
                          <option value="SMA/SMK">SMA / SMK</option>
                          <option value="D3">D3</option>
                          <option value="D4">D4</option>
                          <option value="S1">S1</option>
                          <option value="S2">S2</option>
                          <option value="S3">S3</option>
                          <option value="Lainnya">Lainnya</option>
                        </select>
                      </div>
                      <div class="col-md-5">
                        <label class="form-label small fw-semibold text-secondary mb-1">Nama Institusi / Sekolah <span class="text-danger">*</span></label>
                        <input type="text" v-model="educationForm.institution_name" class="form-control form-control-sm bg-white" placeholder="Contoh: Universitas Indonesia">
                      </div>
                      <div class="col-md-4">
                        <label class="form-label small fw-semibold text-secondary mb-1">Jurusan / Program Studi</label>
                        <input type="text" v-model="educationForm.major" class="form-control form-control-sm bg-white" placeholder="Contoh: Sistem Informasi">
                      </div>
                      <div class="col-md-3">
                        <label class="form-label small fw-semibold text-secondary mb-1">Tahun Masuk</label>
                        <input type="text" v-model="educationForm.start_year" class="form-control form-control-sm bg-white" placeholder="YYYY">
                      </div>
                      <div class="col-md-3">
                        <label class="form-label small fw-semibold text-secondary mb-1">Tahun Lulus</label>
                        <input type="text" v-model="educationForm.end_year" class="form-control form-control-sm bg-white" placeholder="YYYY">
                      </div>
                      <div class="col-md-3">
                        <label class="form-label small fw-semibold text-secondary mb-1">IPK / Nilai</label>
                        <input type="number" step="0.01" v-model="educationForm.gpa" class="form-control form-control-sm bg-white" placeholder="Contoh: 3.75">
                      </div>
                      <div class="col-md-3 d-flex align-items-end gap-2">
                        <button type="button" class="btn btn-sm btn-light flex-fill fw-medium" @click="showEducationForm = false">Batal</button>
                        <button type="button" class="btn btn-sm btn-primary flex-fill fw-medium" @click="saveEducation" :disabled="savingEducation">
                          {{ savingEducation ? 'Menyimpan...' : 'Simpan Pendidikan' }}
                        </button>
                      </div>
                    </div>
                  </div>

                  <!-- Tabel Pendidikan -->
                  <div class="card-body p-0">
                    <div v-if="loadingEducation" class="text-center py-4">
                      <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                    </div>
                    <div v-else-if="educations.length === 0" class="text-center py-5 text-muted">
                      <p class="mb-0 small">Belum ada riwayat pendidikan yang dicatat.</p>
                    </div>
                    <div v-else class="table-responsive">
                      <table class="table table-hover align-middle mb-0 font-size-13">
                        <thead class="table-light">
                          <tr>
                            <th class="ps-3" style="width: 140px;">Jenjang</th>
                            <th>Institusi</th>
                            <th>Jurusan</th>
                            <th style="width: 150px;">Periode</th>
                            <th style="width: 110px;">IPK / Nilai</th>
                            <th class="text-end pe-3" style="width: 100px;">Aksi</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="edu in educations" :key="edu.id">
                            <td class="ps-3">
                              <span class="badge bg-light text-dark border px-2 py-1">{{ edu.level }}</span>
                            </td>
                            <td>
                              <div class="fw-semibold text-dark">{{ edu.institution_name }}</div>
                            </td>
                            <td>{{ edu.major || '-' }}</td>
                            <td class="text-muted">{{ edu.start_year || '-' }} — {{ edu.end_year || '-' }}</td>
                            <td><span class="fw-medium text-dark">{{ edu.gpa || '-' }}</span></td>
                            <td class="text-end pe-3">
                              <button type="button" class="btn btn-sm btn-light text-danger py-1 px-2" @click="deleteEducation(edu.id)">Hapus</button>
                            </td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>

                <!-- BAGIAN 3: PELATIHAN & SERTIFIKASI -->
                <div v-show="activeDocTab === 'all' || activeDocTab === 'trainings'" class="card border rounded-3 mb-4 shadow-none">
                  <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom flex-wrap gap-2">
                    <div>
                      <h6 class="mb-1 fw-bold text-dark">Pelatihan & Sertifikasi</h6>
                      <p class="text-muted small mb-0">Kursus, sertifikasi profesi, dan pelatihan keahlian</p>
                    </div>
                    <button 
                      type="button" 
                      class="btn btn-sm px-3 fw-medium" 
                      :class="showTrainingForm ? 'btn-light text-secondary' : 'btn-primary'"
                      @click="showTrainingForm = !showTrainingForm"
                    >
                      {{ showTrainingForm ? 'Batal' : '+ Tambah Pelatihan' }}
                    </button>
                  </div>

                  <!-- Form Pelatihan -->
                  <div v-if="showTrainingForm" class="p-3 bg-light bg-opacity-50 border-bottom">
                    <div class="row g-3">
                      <div class="col-md-6">
                        <label class="form-label small fw-semibold text-secondary mb-1">Nama Pelatihan / Sertifikasi <span class="text-danger">*</span></label>
                        <input type="text" v-model="trainingForm.training_name" class="form-control form-control-sm bg-white" placeholder="Contoh: Certified Scrum Master">
                      </div>
                      <div class="col-md-6">
                        <label class="form-label small fw-semibold text-secondary mb-1">Lembaga Penyelenggara</label>
                        <input type="text" v-model="trainingForm.organizer" class="form-control form-control-sm bg-white" placeholder="Contoh: Scrum Alliance">
                      </div>
                      <div class="col-md-3">
                        <label class="form-label small fw-semibold text-secondary mb-1">Tanggal Mulai</label>
                        <input type="date" v-model="trainingForm.start_date" class="form-control form-control-sm bg-white">
                      </div>
                      <div class="col-md-3">
                        <label class="form-label small fw-semibold text-secondary mb-1">Tanggal Selesai</label>
                        <input type="date" v-model="trainingForm.end_date" class="form-control form-control-sm bg-white">
                      </div>
                      <div class="col-md-3">
                        <label class="form-label small fw-semibold text-secondary mb-1">No. Sertifikat</label>
                        <input type="text" v-model="trainingForm.certificate_number" class="form-control form-control-sm bg-white" placeholder="No. ID Sertifikat">
                      </div>
                      <div class="col-md-3 d-flex align-items-end gap-2">
                        <button type="button" class="btn btn-sm btn-light flex-fill fw-medium" @click="showTrainingForm = false">Batal</button>
                        <button type="button" class="btn btn-sm btn-primary flex-fill fw-medium" @click="saveTraining" :disabled="savingTraining">
                          {{ savingTraining ? 'Menyimpan...' : 'Simpan Pelatihan' }}
                        </button>
                      </div>
                    </div>
                  </div>

                  <!-- Tabel Pelatihan -->
                  <div class="card-body p-0">
                    <div v-if="loadingTraining" class="text-center py-4">
                      <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                    </div>
                    <div v-else-if="trainings.length === 0" class="text-center py-5 text-muted">
                      <p class="mb-0 small">Belum ada riwayat pelatihan atau sertifikasi.</p>
                    </div>
                    <div v-else class="table-responsive">
                      <table class="table table-hover align-middle mb-0 font-size-13">
                        <thead class="table-light">
                          <tr>
                            <th class="ps-3">Nama Pelatihan</th>
                            <th>Penyelenggara</th>
                            <th style="width: 220px;">Periode</th>
                            <th style="width: 170px;">No. Sertifikat</th>
                            <th class="text-end pe-3" style="width: 100px;">Aksi</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="trn in trainings" :key="trn.id">
                            <td class="ps-3">
                              <div class="fw-semibold text-dark">{{ trn.training_name }}</div>
                            </td>
                            <td>{{ trn.organizer || '-' }}</td>
                            <td class="text-muted">
                              <span v-if="trn.start_date || trn.end_date">
                                {{ formatDocDate(trn.start_date) }} — {{ formatDocDate(trn.end_date) }}
                              </span>
                              <span v-else>-</span>
                            </td>
                            <td>
                              <span class="font-monospace text-secondary small">{{ trn.certificate_number || '-' }}</span>
                            </td>
                            <td class="text-end pe-3">
                              <button type="button" class="btn btn-sm btn-light text-danger py-1 px-2" @click="deleteTraining(trn.id)">Hapus</button>
                            </td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>

              </div><!-- /#documents -->

              <!-- LEAVE BALANCES -->
              <div class="tab-pane" id="leave_balances" role="tabpanel">
                
                <!-- Ringkasan Statistik Cuti (KPI Cards) -->
                <div class="row g-3 mb-4">
                  <div class="col-6 col-md-3">
                    <div class="card border rounded-3 p-3 h-100 shadow-none bg-light bg-opacity-50">
                      <div class="text-secondary small fw-medium mb-1">Total Hak Cuti</div>
                      <div class="fs-4 fw-bold text-dark">{{ leaveSummary.totalQuota }} <span class="fs-6 fw-normal text-muted">hari</span></div>
                      <div class="text-muted font-size-11 mt-1">Akumulasi seluruh tipe cuti</div>
                    </div>
                  </div>
                  <div class="col-6 col-md-3">
                    <div class="card border rounded-3 p-3 h-100 shadow-none bg-light bg-opacity-50">
                      <div class="text-secondary small fw-medium mb-1">Cuti Digunakan</div>
                      <div class="fs-4 fw-bold text-danger">{{ leaveSummary.totalUsed }} <span class="fs-6 fw-normal text-muted">hari</span></div>
                      <div class="text-muted font-size-11 mt-1">Telah disetujui tahun ini</div>
                    </div>
                  </div>
                  <div class="col-6 col-md-3">
                    <div class="card border rounded-3 p-3 h-100 shadow-none bg-light bg-opacity-50">
                      <div class="text-secondary small fw-medium mb-1">Total Sisa Saldo</div>
                      <div class="fs-4 fw-bold text-success">{{ leaveSummary.totalRemaining }} <span class="fs-6 fw-normal text-muted">hari</span></div>
                      <div class="text-muted font-size-11 mt-1">Gabungan sisa saldo aktif</div>
                    </div>
                  </div>
                  <div class="col-6 col-md-3">
                    <div class="card border rounded-3 p-3 h-100 shadow-none bg-light bg-opacity-50">
                      <div class="text-secondary small fw-medium mb-1">Sisa Cuti Tahunan</div>
                      <div class="fs-4 fw-bold text-primary">{{ leaveSummary.annualRemaining }} <span class="fs-6 fw-normal text-muted">hari</span></div>
                      <div class="text-muted font-size-11 mt-1">Hak cuti reguler tahunan</div>
                    </div>
                  </div>
                </div>

                <!-- Kartu Pengelolaan Cuti -->
                <div class="card border rounded-3 shadow-none mb-4">
                  <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom flex-wrap gap-2">
                    <div>
                      <h6 class="mb-1 fw-bold text-dark">Jatah & Saldo Cuti Karyawan</h6>
                      <p class="text-muted small mb-0">Kelola dan sesuaikan kuota cuti tahunan berdasarkan master cuti perusahaan</p>
                    </div>

                    <div class="d-flex align-items-center gap-2 flex-wrap">
                      <div class="d-flex align-items-center bg-light px-2 py-1 rounded-2 border">
                        <span class="text-secondary small me-2 font-size-11 text-nowrap">Tahun:</span>
                        <select v-model="selectedLeaveYear" @change="handleLeaveYearChange" class="form-select form-select-sm border-0 bg-transparent text-dark p-0 fw-bold" style="width: 75px; box-shadow: none;">
                          <option v-for="y in [2024, 2025, 2026, 2027, 2028]" :key="y" :value="y">{{ y }}</option>
                        </select>
                      </div>

                      <button 
                        type="button" 
                        class="btn btn-sm btn-primary px-3 fw-medium" 
                        @click="openCreateLeaveBalance"
                      >
                        + Atur Saldo Cuti
                      </button>
                    </div>
                  </div>

                  <!-- Tabel Saldo Cuti -->
                  <div class="card-body p-0">
                    <div v-if="loadingLeaveBalances" class="text-center py-4">
                      <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                    </div>
                    <div v-else-if="leaveBalances.length === 0" class="text-center py-5 text-muted">
                      <p class="mb-0 small">Belum ada data jatah cuti untuk tahun {{ selectedLeaveYear }}.</p>
                    </div>
                    <div v-else class="table-responsive">
                      <table class="table table-hover align-middle mb-0 font-size-13">
                        <thead class="table-light">
                          <tr>
                            <th class="ps-3">Tipe Cuti</th>
                            <th style="width: 100px;">Tahun</th>
                            <th style="width: 140px;">Default Master</th>
                            <th style="width: 130px;">Jatah (Kuota)</th>
                            <th style="width: 120px;">Terpakai</th>
                            <th style="width: 130px;">Sisa Saldo</th>
                            <th class="text-end pe-3" style="width: 140px;">Aksi</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="b in leaveBalances" :key="b.id">
                            <td class="ps-3">
                              <div class="fw-semibold text-dark">{{ b.leave_type?.name || leaveTypes.find(t => t.id == b.leave_type_id)?.name || '-' }}</div>
                              <div class="text-muted font-size-11" v-if="b.leave_type?.code || leaveTypes.find(t => t.id == b.leave_type_id)?.code">
                                Kode: {{ b.leave_type?.code || leaveTypes.find(t => t.id == b.leave_type_id)?.code }}
                              </div>
                            </td>
                            <td>{{ b.year }}</td>
                            <td class="text-muted">{{ getLeaveTypeDefault(b) }} hari</td>
                            <td>
                              <span class="fw-semibold text-dark">{{ Math.round(Number(b.quota) || 0) }}</span> hari
                            </td>
                            <td>
                              <span :class="Math.round(Number(b.used) || 0) > 0 ? 'text-danger fw-semibold' : 'text-muted'">{{ Math.round(Number(b.used) || 0) }}</span> hari
                            </td>
                            <td>
                              <span class="badge px-2 py-1" :class="Math.round(Number(b.remaining) || 0) > 0 ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-light text-secondary border'">
                                {{ Math.round(Number(b.remaining) || 0) }} hari
                              </span>
                            </td>
                            <td class="text-end pe-3">
                              <button type="button" class="btn btn-sm btn-light text-primary py-1 px-2 me-2" @click="openEditLeaveBalance(b)">Edit</button>
                              <button type="button" class="btn btn-sm btn-light text-danger py-1 px-2" @click="deleteLeaveBalance(b.id)">Hapus</button>
                            </td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>

                <!-- Modal Atur / Edit Jatah Cuti -->
                <div v-if="showLeaveBalanceForm" class="modal fade show d-block" tabindex="-1" style="background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px);">
                  <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow-lg rounded-4">
                      <div class="modal-header border-bottom-0 pt-4 px-4 pb-2">
                        <div>
                          <h5 class="modal-title fw-bold fs-5 text-dark mb-1">
                            {{ editingLeaveBalanceId ? 'Edit Jatah Cuti' : 'Atur Jatah Cuti' }}
                          </h5>
                          <p class="text-muted small mb-0">
                            {{ editingLeaveBalanceId ? 'Perbarui kuota cuti untuk karyawan ini' : 'Pilih jenis cuti dan tentukan kuota hari' }}
                          </p>
                        </div>
                        <button type="button" class="btn-close shadow-none" @click="showLeaveBalanceForm = false" aria-label="Close"></button>
                      </div>

                      <form @submit.prevent="saveLeaveBalance">
                        <div class="modal-body px-4 py-3">
                          <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary mb-1">Tipe Cuti <span class="text-danger">*</span></label>
                            <select v-model="leaveBalanceForm.leave_type_id" @change="handleLeaveTypeChange" class="form-select bg-light border-0 shadow-none" :disabled="!!editingLeaveBalanceId" required>
                              <option value="">-- Pilih Tipe Cuti --</option>
                              <option v-for="t in leaveTypes" :key="t.id" :value="t.id">
                                {{ t.name }} (Master: {{ Math.round(Number(t.default_days) || 0) }} hari)
                              </option>
                            </select>
                            <div class="form-text text-muted font-size-11 mt-1" v-if="selectedLeaveTypeDefault > 0 && !editingLeaveBalanceId">
                              Standar master untuk cuti ini: {{ Math.round(Number(selectedLeaveTypeDefault) || 0) }} hari kerja.
                            </div>
                          </div>

                          <div class="row g-3">
                            <div class="col-md-5">
                              <label class="form-label small fw-semibold text-secondary mb-1">Tahun <span class="text-danger">*</span></label>
                              <input type="number" v-model.number="leaveBalanceForm.year" class="form-control bg-light border-0 shadow-none" :disabled="!!editingLeaveBalanceId" required>
                            </div>
                            <div class="col-md-7">
                              <label class="form-label small fw-semibold text-secondary mb-1">Jatah / Kuota (Hari) <span class="text-danger">*</span></label>
                              <div class="input-group">
                                <input type="number" step="1" min="0" v-model.number="leaveBalanceForm.quota" class="form-control bg-light border-0 shadow-none" placeholder="Contoh: 12" required>
                                <span class="input-group-text bg-light border-0 text-muted small">Hari</span>
                              </div>
                            </div>
                          </div>

                          <div v-if="editingLeaveBalanceId" class="mt-3 p-2 bg-light rounded-3 font-size-12 text-secondary">
                            Catatan: Perubahan kuota akan langsung memperbarui kalkulasi sisa saldo cuti aktif.
                          </div>
                        </div>

                        <div class="modal-footer border-top-0 px-4 pb-4 pt-2">
                          <button type="button" class="btn btn-light fw-medium px-4 rounded-3" @click="showLeaveBalanceForm = false">Batal</button>
                          <button type="submit" class="btn btn-primary fw-medium px-4 rounded-3 shadow-sm" :disabled="savingLeaveBalance">
                            {{ savingLeaveBalance ? 'Menyimpan...' : (editingLeaveBalanceId ? 'Simpan Perubahan' : 'Simpan Jatah Cuti') }}
                          </button>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>

              </div><!-- /#leave_balances -->

              <!-- RIWAYAT MUTASI & KARIR -->
              <div class="tab-pane" id="mutations" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                  <div>
                    <h5 class="fw-bold text-dark mb-1">Riwayat Karir, Penempatan & Mutasi</h5>
                    <p class="text-muted small mb-0">Daftar penugasan entitas/perusahaan, rotasi posisi, promosi, dan demosi karyawan</p>
                  </div>
                  <router-link to="/hrms/mutations/create" class="btn btn-sm btn-primary px-3 fw-medium rounded-3 shadow-sm d-flex align-items-center gap-1">
                    <i class="mdi mdi-plus-circle-outline font-size-14"></i>
                    <span>Ajukan Mutasi / Demosi / Promosi</span>
                  </router-link>
                </div>

                <!-- Bagian 1: Riwayat Penugasan (Assignments Timeline) -->
                <div class="card border rounded-3 shadow-none mb-4">
                  <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="mb-0 fw-bold text-dark">
                      <i class="mdi mdi-briefcase-account-outline text-primary me-1.5"></i>
                      Daftar Penugasan & Entitas Perusahaan (Assignments)
                    </h6>
                  </div>
                  <div class="card-body p-0">
                    <div class="table-responsive">
                      <table class="table table-hover align-middle mb-0 font-size-13">
                        <thead class="table-light">
                          <tr>
                            <th class="ps-3">Jabatan & Posisi</th>
                            <th>Perusahaan & Cabang</th>
                            <th>NIP / ID Penugasan</th>
                            <th>Masa Berlaku</th>
                            <th class="text-center">Status</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="(assign, aIdx) in (employee?.assignments || [])" :key="assign.id || aIdx">
                            <td class="ps-3">
                              <div class="fw-bold text-dark">{{ assign.position?.name || '-' }}</div>
                              <div class="text-muted font-size-11">{{ assign.department?.name || '-' }} &bull; {{ assign.division?.name || '-' }}</div>
                            </td>
                            <td>
                              <div class="fw-semibold text-dark">{{ assign.company?.name || '-' }}</div>
                              <div class="text-muted font-size-11">{{ assign.branch?.name || '-' }}</div>
                            </td>
                            <td>
                              <span class="badge bg-light text-dark border font-mono font-size-12">
                                {{ assign.nip || employee?.employee_id || '-' }}
                              </span>
                            </td>
                            <td>
                              <div class="small fw-medium text-dark">
                                {{ assign.join_date || '-' }} s/d {{ assign.end_date || 'Aktif Sekarang' }}
                              </div>
                            </td>
                            <td class="text-center">
                              <span v-if="assign.is_primary" class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 font-size-11">
                                <i class="mdi mdi-check-circle me-1"></i>Penempatan Utama
                              </span>
                              <span v-else class="badge bg-light text-secondary border px-2 py-1 font-size-11">
                                {{ assign.end_date ? 'Riwayat Penugasan' : 'Posisi Tambahan' }}
                              </span>
                            </td>
                          </tr>
                          <tr v-if="!employee?.assignments || employee.assignments.length === 0">
                            <td colspan="5" class="text-center py-4 text-muted">Belum ada data penugasan tercatat.</td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>

                <!-- Bagian 2: Surat Keputusan (SK) Mutasi -->
                <div class="card border rounded-3 shadow-none mb-0">
                  <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-dark">
                      <i class="mdi mdi-file-document-outline text-info me-1.5"></i>
                      Riwayat Surat Keputusan (SK Mutasi / Promosi / Demosi)
                    </h6>
                    <button @click="loadMutationHistory" class="btn btn-sm btn-light border text-secondary py-1 px-2.5">
                      <i class="mdi mdi-refresh me-1" :class="{ 'mdi-spin': loadingMutations }"></i> Refresh
                    </button>
                  </div>
                  <div class="card-body p-0">
                    <div v-if="loadingMutations" class="text-center py-4 text-muted">
                      <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                      <span>Memuat riwayat SK mutasi...</span>
                    </div>
                    <div v-else-if="mutationHistory.length === 0" class="text-center py-5 text-muted">
                      <i class="mdi mdi-file-document-remove-outline font-size-36 text-secondary opacity-50 d-block mb-2"></i>
                      <div class="font-size-13 fw-semibold text-secondary">Belum ada riwayat mutasi resmi</div>
                      <div class="font-size-12 text-muted mt-1">Seluruh pengajuan mutasi, promosi, atau demosi akan tercatat di sini.</div>
                    </div>
                    <div v-else class="table-responsive">
                      <table class="table table-hover align-middle mb-0 font-size-13">
                        <thead class="table-light">
                          <tr>
                            <th class="ps-3">No. Memo</th>
                            <th>Tipe</th>
                            <th>Formasi Asal</th>
                            <th>Formasi Target Baru</th>
                            <th>Tgl Efektif</th>
                            <th class="text-center">Status</th>
                            <th class="text-end pe-3">Aksi</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="mut in mutationHistory" :key="mut.id">
                            <td class="ps-3">
                              <span class="badge bg-light text-dark border font-mono font-size-12">
                                {{ mut.memo_number || '-' }}
                              </span>
                            </td>
                            <td>
                              <span 
                                class="badge px-2 py-1 text-uppercase font-size-11"
                                :class="{
                                  'bg-success': mut.type === 'promosi',
                                  'bg-warning text-dark': mut.type === 'demosi',
                                  'bg-primary': mut.type === 'mutasi' || !mut.type
                                }"
                                :style="mut.type !== 'demosi' ? 'color: #ffffff !important;' : ''"
                              >
                                {{ mut.type === 'promosi' ? 'Promosi' : (mut.type === 'demosi' ? 'Demosi' : 'Mutasi') }}
                              </span>
                            </td>
                            <td>
                              <div class="small fw-semibold text-dark">{{ mut.old_position?.name || '-' }}</div>
                              <div class="text-muted font-size-11">{{ mut.old_company?.name || '-' }}</div>
                            </td>
                            <td>
                              <div class="small fw-bold text-primary">{{ mut.new_position?.name || '-' }}</div>
                              <div class="text-muted font-size-11">{{ mut.new_company?.name || '-' }}</div>
                              <div v-if="mut.new_nip" class="mt-0.5">
                                <span class="badge bg-info-subtle text-info border border-info-subtle font-mono font-size-10">
                                  NIP Baru: {{ mut.new_nip }}
                                </span>
                              </div>
                            </td>
                            <td>
                              <div class="small fw-medium text-dark">{{ mut.effective_date || '-' }}</div>
                            </td>
                            <td class="text-center">
                              <span class="badge" :class="{
                                'bg-warning text-dark': mut.status === 'pending',
                                'bg-success': mut.status === 'approved',
                                'bg-danger': mut.status === 'rejected',
                              }">{{ mut.status?.toUpperCase() }}</span>
                            </td>
                            <td class="text-end pe-3">
                              <button @click="downloadMutationPdf(mut.id)" class="btn btn-sm btn-outline-danger py-0 px-2 font-size-11" title="Download PDF SK Mutasi">
                                <i class="mdi mdi-file-pdf-box me-1"></i>PDF
                              </button>
                            </td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div><!-- /#mutations -->

            </div>
          </div>
        </div>
      </div>
      </div>
    </div>
  
    <!-- Modal Simulasi Gaji -->
    <div class="modal fade" id="simulationModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title fw-bold">Hasil Simulasi Gaji</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body" v-if="simulationResult">
            <div class="row text-center mb-4">
              <div class="col-4">
                <p class="text-muted mb-1 small">Total Pendapatan</p>
                <h5 class="text-success fw-bold">{{ formatInputNominal(simulationResult.gross_salary) }}</h5>
              </div>
              <div class="col-4 border-start border-end">
                <p class="text-muted mb-1 small">Total Potongan</p>
                <h5 class="text-danger fw-bold">{{ formatInputNominal(simulationResult.total_deduction || 0) }}</h5>
              </div>
              <div class="col-4">
                <p class="text-muted mb-1 small">Take Home Pay (THP)</p>
                <h5 class="text-primary fw-bold">{{ formatInputNominal(simulationResult.net_salary) }}</h5>
              </div>
            </div>

            <h6 class="fw-bold mb-3 border-bottom pb-2">Rincian Komponen</h6>
            <div class="table-responsive">
              <table class="table table-sm table-bordered">
                <thead class="table-light">
                  <tr>
                    <th>Nama Komponen</th>
                    <th class="text-center">Tipe</th>
                    <th class="text-end">Nominal</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(det, idx) in simulationResult.details" :key="idx">
                    <td>{{ det.component_name }}</td>
                    <td class="text-center">
                      <span class="badge" :class="det.type === 'allowance' ? 'bg-success' : 'bg-danger'">
                        {{ det.type === 'allowance' ? 'Pendapatan' : 'Potongan' }}
                      </span>
                    </td>
                    <td class="text-end" :class="det.type === 'allowance' ? 'text-success' : 'text-danger'">
                      {{ det.type === 'allowance' ? '' : '-' }} {{ formatInputNominal(det.amount) }}
                    </td>
                  </tr>
                  <tr v-if="!simulationResult.details || simulationResult.details.length === 0">
                    <td colspan="3" class="text-center text-muted">Tidak ada rincian komponen</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          <div class="modal-footer bg-light">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
          </div>
        </div>
      </div>
    </div>

</template>

<script setup>

const simulationResult = ref(null)

const inheritedComponents = computed(() => {
  if (!employee.value || !employee.value.assignments) return []
  const primary = employee.value.assignments.find(a => a.is_primary)
  if (!primary || !primary.position || !primary.position.payroll_components) return []
  return primary.position.payroll_components
})


const pullingSalary = ref(false)

const pullSalaryComponents = async () => {
  if (!confirm('Apakah Anda yakin ingin menarik komponen gaji default dari jabatan? Tindakan ini akan menimpa (override) komponen yang sama jika sudah ada.')) return
  
  pullingSalary.value = true
  try {
    const { data } = await api.post(`/employees/${employee.value.id}/pull-salary-components`)
    if (data.success) {
      toast.success(data.message)
      setTimeout(() => {
        window.location.reload()
      }, 1000)
    } else {
      toast.error(data.message || 'Gagal menarik data')
    }
  } catch (e) {
    console.error(e)
    toast.error(e.response?.data?.message || 'Terjadi kesalahan saat menarik data dari jabatan')
  } finally {
    pullingSalary.value = false
  }
}
const formatRupiah = (val) => {
  if (!val) return 'Rp 0'
  return 'Rp ' + Number(val).toLocaleString('id-ID')
}



import { ref, onMounted, reactive, computed } from 'vue'
import { useRoute } from 'vue-router'
import { toast } from 'vue3-toastify'
import api from '../api'
import CrudPage from '../components/CrudPage.vue'
import Multiselect from '@vueform/multiselect'
import '@vueform/multiselect/themes/default.css'
import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()

const isStaffUser = computed(() => {
  if (auth.isSuperAdmin || auth.userType === 'admin') return false
  if (auth.isStaffHr || auth.webRole === 'staff_hr') return true
  const roleLabel = (auth.currentUserRoleLabel || '').toLowerCase()
  if (roleLabel.includes('staff')) return true
  return false
})

const route = useRoute()
const employee = ref(null)
const loading = ref(true)

const savingEmployee = ref(false)
const editForm = ref({})

// Assets Data
const assets = ref([])
const loadingAssets = ref(false)
const assetsLoaded = ref(false)

const loadAssets = async () => {
  if (assetsLoaded.value) return
  loadingAssets.value = true
  try {
    const { data } = await api.get(`/employees/${route.params.id}/assets`)
    assets.value = data.data
    assetsLoaded.value = true
  } catch (error) {
    console.error('Failed to load assets', error)
    toast.error('Gagal memuat data aset')
  } finally {
    loadingAssets.value = false
  }
}

// Shifts Data
const shiftOptions = ref([])
const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu']
const patternForm = ref({0:null, 1:null, 2:null, 3:null, 4:null, 5:null, 6:null})
const savingPattern = ref(false)
const now = new Date()
const currentMonth = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`

// Dokumen, Pendidikan, Pelatihan
const activeDocTab = ref('all')
const documents = ref([])
const educations = ref([])
const trainings = ref([])

const formatDocFileSize = (bytes) => {
  if (!bytes || bytes === 0) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

const formatDocDate = (val) => {
  if (!val) return '-'
  try {
    return new Date(val).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
  } catch (e) {
    return val
  }
}

const loadingDocuments = ref(false)
const loadingEducation = ref(false)
const loadingTraining = ref(false)

const showDocumentForm = ref(false)
const showEducationForm = ref(false)
const showTrainingForm = ref(false)

const uploadingDocument = ref(false)
const savingEducation = ref(false)
const savingTraining = ref(false)

const documentForm = reactive({
  document_type: '',
  file: null
})

const educationForm = reactive({
  level: '',
  institution_name: '',
  major: '',
  start_year: '',
  end_year: '',
  gpa: ''
})

const trainingForm = reactive({
  training_name: '',
  organizer: '',
  start_date: '',
  end_date: '',
  certificate_number: ''
})

const getFileUrl = (path) => {
  return `${import.meta.env.VITE_API_URL.replace('/api/v1', '')}/storage/${path}`
}

const handleDocumentFile = (e) => {
  documentForm.file = e.target.files[0]
}

const loadDocuments = async () => {
  try {
    loadingDocuments.value = true
    const res = await api.get(`/employees/${route.params.id}/documents`)
    documents.value = res.data
  } catch (error) {
    console.error(error)
  } finally {
    loadingDocuments.value = false
  }
  loadEducations()
  loadTrainings()
}

const loadEducations = async () => {
  try {
    loadingEducation.value = true
    const res = await api.get(`/employees/${route.params.id}/educations`)
    educations.value = res.data
  } catch (error) {
    console.error(error)
  } finally {
    loadingEducation.value = false
  }
}

const loadTrainings = async () => {
  try {
    loadingTraining.value = true
    const res = await api.get(`/employees/${route.params.id}/trainings`)
    trainings.value = res.data
  } catch (error) {
    console.error(error)
  } finally {
    loadingTraining.value = false
  }
}

const uploadDocument = async () => {
  if (!documentForm.document_type || !documentForm.file) {
    return toast.error('Pilih jenis dokumen dan file terlebih dahulu')
  }
  
  const formData = new FormData()
  formData.append('document_type', documentForm.document_type)
  formData.append('file', documentForm.file)

  try {
    uploadingDocument.value = true
    await api.post(`/employees/${route.params.id}/documents`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    toast.success('Dokumen berhasil diunggah')
    documentForm.document_type = ''
    documentForm.file = null
    showDocumentForm.value = false
    loadDocuments()
  } catch (error) {
    toast.error('Gagal mengunggah dokumen')
  } finally {
    uploadingDocument.value = false
  }
}

const deleteDocument = async (id) => {
  if (!confirm('Hapus dokumen ini?')) return
  try {
    await api.delete(`/employees/${route.params.id}/documents/${id}`)
    toast.success('Dokumen dihapus')
    loadDocuments()
  } catch (error) {
    toast.error('Gagal menghapus dokumen')
  }
}

const saveEducation = async () => {
  if (!educationForm.level || !educationForm.institution_name) {
    return toast.error('Tingkat dan Institusi wajib diisi')
  }
  
  try {
    savingEducation.value = true
    await api.post(`/employees/${route.params.id}/educations`, educationForm)
    toast.success('Pendidikan berhasil disimpan')
    Object.assign(educationForm, { level: '', institution_name: '', major: '', start_year: '', end_year: '', gpa: '' })
    showEducationForm.value = false
    loadEducations()
  } catch (error) {
    toast.error('Gagal menyimpan pendidikan')
  } finally {
    savingEducation.value = false
  }
}

const deleteEducation = async (id) => {
  if (!confirm('Hapus riwayat pendidikan ini?')) return
  try {
    await api.delete(`/employees/${route.params.id}/educations/${id}`)
    toast.success('Pendidikan dihapus')
    loadEducations()
  } catch (error) {
    toast.error('Gagal menghapus pendidikan')
  }
}

const saveTraining = async () => {
  if (!trainingForm.training_name) {
    return toast.error('Nama pelatihan wajib diisi')
  }
  
  try {
    savingTraining.value = true
    await api.post(`/employees/${route.params.id}/trainings`, trainingForm)
    toast.success('Pelatihan berhasil disimpan')
    Object.assign(trainingForm, { training_name: '', organizer: '', start_date: '', end_date: '', certificate_number: '' })
    showTrainingForm.value = false
    loadTrainings()
  } catch (error) {
    toast.error('Gagal menyimpan pelatihan')
  } finally {
    savingTraining.value = false
  }
}

const deleteTraining = async (id) => {
  if (!confirm('Hapus riwayat pelatihan ini?')) return
  try {
    await api.delete(`/employees/${route.params.id}/trainings/${id}`)
    toast.success('Pelatihan dihapus')
    loadTrainings()
  } catch (error) {
    toast.error('Gagal menghapus pelatihan')
  }
}

const generateForm = ref({
  year: new Date().getFullYear(),
  month: new Date().getMonth() + 1
})
const generatingShifts = ref(false)
const showGenerateModal = ref(false)
const showPatternModal = ref(false)
const crudShifts = ref(null)

// Payroll Data
const componentsData = ref([])
const filteredComponentsData = computed(() => {
  if (!employee.value) return componentsData.value
  return componentsData.value
})

const getComponentsForAssignment = (assign) => {
  if (!assign || !assign.salary_cycle_type) return componentsData.value
  return componentsData.value.filter(c => {
    let schemas = c.payment_schema;
    if (typeof schemas === 'string') {
      try { schemas = JSON.parse(schemas); } catch(e) { schemas = []; }
    }
    if (!schemas || !Array.isArray(schemas)) return true
    return schemas.includes(assign.salary_cycle_type)
  })
}

const salaryStructureOptions = ref([])
const primaryAssignment = computed(() => {
  if (!employee.value?.assignments || !employee.value.assignments.length) return {}
  return employee.value.assignments.find(a => a.is_primary) || employee.value.assignments[0]
})



const simulatePayroll = async () => {
  simulatingPayroll.value = true
  try {
    const res = await api.get(`/employees/${route.params.id}/simulate-payroll`)
    simulationData.value = res.data.data
    showSimulationModal.value = true
  } catch (error) {
    toast.error(error.response?.data?.message || 'Gagal generate simulasi')
  } finally {
    simulatingPayroll.value = false
  }
}

const componentOptionsMonthly = computed(() => filteredComponentsData.value.filter(c => c.category === 'monthly' || !c.category).map(c => ({ value: c.id, label: c.name })))
const componentOptionsAnnual = computed(() => filteredComponentsData.value.filter(c => c.category === 'annual').map(c => ({ value: c.id, label: c.name })))
const componentOptionsLoan = computed(() => filteredComponentsData.value.filter(c => c.category === 'loan').map(c => ({ value: c.id, label: c.name })))

const departmentOptions = ref([])
const divisionOptions = ref([])
const companyOptions = ref([])
const branchOptions = ref([])
const branchesData = ref([])
const positionOptions = ref([])
const employeeOptions = ref([])

const selectedSpecificBranches = computed(() => {
  const ids = (editForm.value?.allowed_branch_ids || []).map(Number)
  return branchesData.value.filter(b => ids.includes(Number(b.id)))
})

const unselectedBranches = computed(() => {
  const selectedIds = (editForm.value?.allowed_branch_ids || []).map(Number)
  return branchesData.value.filter(b => !selectedIds.includes(Number(b.id)))
})

const addBranchFromSelect = (event) => {
  const branchId = Number(event.target.value)
  if (!branchId) return
  if (!editForm.value.allowed_branch_ids) {
    editForm.value.allowed_branch_ids = []
  }
  if (!editForm.value.allowed_branch_ids.includes(branchId)) {
    editForm.value.allowed_branch_ids.push(branchId)
  }
  event.target.value = ''
}

const removeAllowedBranch = (branchId) => {
  if (!editForm.value?.allowed_branch_ids) return
  editForm.value.allowed_branch_ids = editForm.value.allowed_branch_ids
    .map(Number)
    .filter(id => id !== Number(branchId))
}

const selectAllBranches = () => {
  if (!editForm.value) return
  editForm.value.allowed_branch_ids = branchesData.value.map(b => Number(b.id))
}

const clearAllBranches = () => {
  if (!editForm.value) return
  editForm.value.allowed_branch_ids = []
}

const branchPlacementOptions = computed(() => {
  return branchesData.value.map(b => {
    const comp = companyOptions.value.find(c => String(c.value) === String(b.company_id))
    const compName = b.company?.name || comp?.label || ''
    return {
      value: b.id,
      label: compName ? `${b.name} (${compName})` : b.name,
      company_id: b.company_id
    }
  })
})

const getFilteredBranches = () => branchPlacementOptions.value
const getFilteredDepartments = () => departmentOptions.value
const getFilteredDivisions = () => divisionOptions.value

const getAvailablePositions = (assign) => {
  if (assign?.division_id) {
    const matching = positionOptions.value.filter(p => String(p.division_id) === String(assign.division_id))
    const others = positionOptions.value.filter(p => String(p.division_id) !== String(assign.division_id))
    if (matching.length > 0) {
      return [
        ...matching.map(p => ({ ...p, label: `★ ${p.label}` })),
        ...others
      ]
    }
  }
  return positionOptions.value
}

const getFilteredPositions = (assign) => getAvailablePositions(assign)

const getCompanyName = (companyId) => {
  const c = companyOptions.value.find(co => String(co.value) === String(companyId))
  return c ? c.label : ''
}

const handleBranchPlacementChange = (assign) => {
  if (assign.branch_id) {
    const branch = branchesData.value.find(b => String(b.id) === String(assign.branch_id))
    if (branch && branch.company_id) {
      assign.placement_company_id = branch.company_id
    } else {
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
  if (!editForm.value.assignments) editForm.value.assignments = []
  editForm.value.assignments.push({
    company_id: '',
    placement_company_id: null,
    branch_id: '',
    department_id: '',
    division_id: '',
    position_id: '',
    supervisor_id: '',
    join_date: '',
    employment_status: 'permanent',
    salary_cycle_type: '',
    payroll_components: [],
    is_primary: editForm.value.assignments.length === 0
  })
}

const removeAssignment = (index) => {
  if (editForm.value.assignments[index].is_primary && editForm.value.assignments.length > 1) {
    editForm.value.assignments.splice(index, 1)
    editForm.value.assignments[0].is_primary = true
  } else {
    editForm.value.assignments.splice(index, 1)
  }
}

const setPrimaryAssignment = (index) => {
  editForm.value.assignments.forEach((a, i) => {
    a.is_primary = (i === index)
  })
}

const photoFile = ref(null)

const handlePhotoUpload = (e) => {
  if (e.target.files.length > 0) {
    photoFile.value = e.target.files[0]
  }
}

const loadData = async () => {
  try {
    const { data } = await api.get(`/employees/${route.params.id}/details`)
    employee.value = data.data
    // Map to edit form
    editForm.value = {
      name: employee.value.name,
      nickname: employee.value.nickname || '',
      employee_id: employee.value.employee_id,
      email: employee.value.email,
      phone: employee.value.phone,
      ktp_number: employee.value.ktp_number,
      npwp: employee.value.npwp,
      ptkp_status: employee.value.ptkp_status || '',
      bpjs_kesehatan: employee.value.bpjs_kesehatan || '',
      bpjs_ketenagakerjaan: employee.value.bpjs_ketenagakerjaan || '',
      marital_status: employee.value.marital_status || 'single',
      birth_date: employee.value.birth_date,
      gender: employee.value.gender,
      address: employee.value.address,
      domicile_address: employee.value.domicile_address,
      residential_address: employee.value.residential_address,
      assignments: employee.value.assignments || [],
      password: '',
      is_active: employee.value.is_active ? 1 : 0,
      web_access_enabled: employee.value.web_access_enabled ? 1 : 0,
      web_role: employee.value.web_role || '',
      can_clock_in_anywhere: (employee.value.can_clock_in_anywhere || employee.value.attendance_location_type === 'anywhere') ? 1 : 0,
      attendance_location_type: (employee.value.can_clock_in_anywhere || employee.value.attendance_location_type === 'anywhere') ? 'anywhere' : (employee.value.attendance_location_type || 'specific_branches'),
      allowed_branch_ids: Array.isArray(employee.value.allowed_branch_ids) && employee.value.allowed_branch_ids.length > 0
        ? employee.value.allowed_branch_ids.map(Number)
        : (branchesData.value.length > 0 ? branchesData.value.map(b => Number(b.id)) : []),
      is_daily_worker: employee.value.is_daily_worker ? 1 : 0,
      mobile_access_status: employee.value.mobile_access_status || 'allowed'
    }

    loadShiftPatterns()
    loadMutationHistory()
  } catch (error) {
    console.error('Failed to load employee details', error)
  } finally {
    loading.value = false
  }
}

const loadDependencies = async () => {
  const safeGet = (url, params = {}) => api.get(url, { params }).catch(err => {
    console.warn(`Failed to fetch ${url}`, err)
    return { data: { data: [] } }
  })

  const [shiftsRes, compsRes, deptsRes, divsRes, compsArr, branchesArr, positionsArr, structuresRes, employeesRes] = await Promise.all([
    safeGet('/shifts', { per_page: 100 }),
    safeGet('/payroll-components', { per_page: 100 }),
    safeGet('/departments', { per_page: 100 }),
    safeGet('/divisions', { per_page: 100 }),
    safeGet('/companies', { per_page: 100 }),
    safeGet('/branches', { per_page: 100 }),
    safeGet('/positions', { per_page: 100 }),
    safeGet('/salary-structures', { per_page: 100 }),
    safeGet('/employees', { per_page: 500 })
  ])
  const getArray = (res) => res?.data?.data?.data ?? res?.data?.data?.items ?? res?.data?.data ?? []
  
  shiftOptions.value = getArray(shiftsRes).map(s => ({ value: s.id, label: s.name + ' (' + s.clock_in + ' - ' + s.clock_out + ')' }))
  componentsData.value = getArray(compsRes)
  salaryStructureOptions.value = getArray(structuresRes).map(s => ({ value: s.id, label: s.name }))
  
  companyOptions.value = getArray(compsArr).map(c => ({ value: c.id, label: c.name }))
  branchesData.value = getArray(branchesArr)
  branchOptions.value = branchesData.value.map(b => ({ value: b.id, label: b.name, company_id: b.company_id }))
  
  // Defaultkan ke seluruh cabang jika employee belum memiliki konfigurasi spesifik
  if (!editForm.value.allowed_branch_ids || editForm.value.allowed_branch_ids.length === 0) {
    editForm.value.allowed_branch_ids = branchesData.value.map(b => Number(b.id))
  }
  departmentOptions.value = getArray(deptsRes).map(d => ({ value: d.id, label: d.name, branch_id: d.branch_id }))
  divisionOptions.value = getArray(divsRes).map(d => ({ value: d.id, label: d.name, department_id: d.department_id }))
  positionOptions.value = getArray(positionsArr).map(p => ({ value: p.id, label: p.name, division_id: p.division_id, department_id: p.department_id, branch_id: p.branch_id }))
  employeeOptions.value = getArray(employeesRes).map(e => ({ value: e.id, label: e.name }))
}

const loadShiftPatterns = async () => {
  try {
    const { data } = await api.get('/employee-shifts/patterns', { params: { employee_id: route.params.id } })
    const patterns = data.data
    patterns.forEach(p => {
      patternForm.value[p.day_of_week] = p.shift_id
    })
  } catch (e) {}
}


// --- Logic for Assignment Salary Components ---

const isAssignmentCompSelected = (assign, compId) => {
  if (!assign || !assign.payroll_components) return false
  return assign.payroll_components.some(c => {
    if (typeof c === 'object') return c.payroll_component_id === compId
    return c === compId
  })
}

const getRequiredComponentIds = (assign) => {
  let requiredIds = new Set()
  if (!assign || !assign.payroll_components) return []
  
  assign.payroll_components.forEach(sc => {
    const compId = typeof sc === 'object' ? sc.payroll_component_id : sc
    const master = componentsData.value.find(c => c.id === compId)
    if (master && master.calculation_method === 'FORMULA' && master.formula) {
      const matches = master.formula.match(/\{([a-zA-Z0-9_]+)\}/g)
      if (matches) {
        matches.forEach(m => {
          const code = m.replace(/[{}]/g, '')
          const reqComp = componentsData.value.find(c => c.code === code)
          if (reqComp) {
            requiredIds.add(reqComp.id)
          }
        })
      }
    }
  })
  return Array.from(requiredIds)
}

const isComponentRequired = (assign, compId) => {
  return getRequiredComponentIds(assign).includes(compId)
}

const toggleAssignmentComp = (assign, compId, isChecked) => {
  if (!assign) return
  if (!assign.payroll_components) assign.payroll_components = []
  
  if (isChecked) {
    if (!isAssignmentCompSelected(assign, compId)) {
      assign.payroll_components.push({
        payroll_component_id: compId,
        fixed_amount: 0
      })
    }
    
    const master = componentsData.value.find(c => c.id === compId)
    if (master && master.calculation_method === 'FORMULA' && master.formula) {
      const matches = master.formula.match(/\{([a-zA-Z0-9_]+)\}/g)
      if (matches) {
        matches.forEach(m => {
          const code = m.replace(/[{}]/g, '')
          const reqComp = componentsData.value.find(c => c.code === code)
          if (reqComp && !isAssignmentCompSelected(assign, reqComp.id)) {
            assign.payroll_components.push({
              payroll_component_id: reqComp.id,
              fixed_amount: 0
            })
          }
        })
      }
    }
  } else {
    assign.payroll_components = assign.payroll_components.filter(c => {
      if (typeof c === 'object') return c.payroll_component_id !== compId
      return c !== compId
    })
  }
}

const getAssignmentCompAmount = (assign, compId) => {
  if (!assign || !assign.payroll_components) return 0
  const c = assign.payroll_components.find(x => typeof x === 'object' && x.payroll_component_id === compId)
  if (!c) return 0
  return c.fixed_amount || 0
}

const formatInputNominal = (val) => {
  if (val == null || val === '') return ''
  return Number(val).toLocaleString('id-ID')
}

const updateAssignmentCompAmount = (assign, compId, val) => {
  if (!assign || !assign.payroll_components) return
  const c = assign.payroll_components.find(x => typeof x === 'object' && x.payroll_component_id === compId)
  if (c) {
    let num = val.replace(/\D/g, '')
    c.fixed_amount = num ? parseInt(num, 10) : 0
  }
}

const pullAssignmentSalary = async (assign) => {
  if (!assign.position_id) {
    toast.warning("Silakan pilih Jabatan terlebih dahulu.")
    return
  }
  
  pullingSalary.value = true
  try {
    const { data } = await api.get(`/positions/${assign.position_id}`)
    if (data.success && data.data && data.data.payroll_components) {
      assign.payroll_components = data.data.payroll_components.map(pc => ({
        payroll_component_id: pc.payroll_component_id,
        fixed_amount: pc.fixed_amount
      }))
      toast.success("Berhasil menarik komponen dari Jabatan.")
    } else {
      toast.info("Jabatan ini tidak memiliki template gaji.")
    }
  } catch (e) {
    console.error(e)
    toast.error("Gagal menarik data gaji dari jabatan.")
  } finally {
    pullingSalary.value = false
  }
}
// ---------------------------------------------

const updateEmployee = async () => {
  savingEmployee.value = true
  try {
    // Sinkronkan mobile_access_status dengan is_active sesuai request (Jadikan satu)
    editForm.value.mobile_access_status = editForm.value.is_active === 1 ? 'approved' : 'rejected'

    const formData = new FormData()
    formData.append('_method', 'PUT')
    Object.keys(editForm.value).forEach(key => {
      if (key === 'assignments') {
        editForm.value.assignments.forEach((assign, index) => {
          Object.keys(assign).forEach(assignKey => {
                          if (assignKey === 'payroll_components') {
                if (!isStaffUser.value && Array.isArray(assign[assignKey])) {
                  assign[assignKey].forEach((comp, compIdx) => {
                    if (typeof comp === 'object') {
                      const cid = comp.payroll_component_id || comp.id;
                      if (cid) formData.append(`assignments[${index}][payroll_components][${compIdx}][payroll_component_id]`, cid);
                      formData.append(`assignments[${index}][payroll_components][${compIdx}][fixed_amount]`, comp.fixed_amount || 0);
                    } else {
                      formData.append(`assignments[${index}][payroll_components][${compIdx}]`, comp);
                    }
                  })
                }
              } else if (assignKey === 'placement_company_id') {
                formData.append(`assignments[${index}][placement_company_id]`, assign[assignKey] ? assign[assignKey] : '')
              } else if (assign[assignKey] !== null && assign[assignKey] !== undefined && assign[assignKey] !== '') {
                let val = assign[assignKey]
                if (typeof val === 'boolean') val = val ? 1 : 0
                formData.append(`assignments[${index}][${assignKey}]`, val)
              }
            })
          })
      } else if (key === 'allowed_branch_ids') {
        const branchIds = Array.isArray(editForm.value.allowed_branch_ids)
          ? editForm.value.allowed_branch_ids.map(Number).filter(Boolean)
          : []
        formData.append('allowed_branch_ids', JSON.stringify(branchIds))
      } else if (key === 'attendance_location_type') {
        const isAnywhere = editForm.value.can_clock_in_anywhere == 1 || editForm.value.attendance_location_type === 'anywhere'
        formData.append('attendance_location_type', isAnywhere ? 'anywhere' : 'specific_branches')
      } else if (key === 'can_clock_in_anywhere') {
        const isAnywhere = editForm.value.can_clock_in_anywhere == 1 || editForm.value.attendance_location_type === 'anywhere'
        formData.append('can_clock_in_anywhere', isAnywhere ? 1 : 0)
      } else if (editForm.value[key] !== null && editForm.value[key] !== undefined && editForm.value[key] !== '') {
        let val = editForm.value[key]
        if (typeof val === 'boolean') val = val ? 1 : 0
        formData.append(key, val)
      }
    })
    
    if (photoFile.value) {
      formData.append('photo', photoFile.value)
    }

    await api.post(`/employees/${route.params.id}`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })

    // Update Web Access Settings
    await api.put(`/employees/${route.params.id}/web-access`, {
      web_access_enabled: editForm.value.web_access_enabled === 1,
      web_role: editForm.value.web_role || null
    })

    toast.success('Profil berhasil diupdate')
    photoFile.value = null
    loadData()
  } catch (error) {
    toast.error('Gagal mengupdate profil: ' + (error.response?.data?.message || error.message))
  } finally {
    savingEmployee.value = false
  }
}

const saveShiftPattern = async () => {
  savingPattern.value = true
  try {
    const patternsArr = []
    for(let i=0; i<=6; i++) {
      patternsArr.push({ day_of_week: i, shift_id: patternForm.value[i] })
    }
    await api.post('/employee-shifts/patterns', {
      employee_id: route.params.id,
      patterns: patternsArr
    })
    toast.success('Pola shift berhasil disimpan')
    showPatternModal.value = false
  } catch (error) {
    toast.error('Gagal menyimpan pola')
  } finally {
    savingPattern.value = false
  }
}

const generateShifts = async () => {
  if (!confirm(`Generate shift untuk bulan ${generateForm.value.month}/${generateForm.value.year}? Data shift yang sudah ada di bulan ini akan tertimpa.`)) return
  generatingShifts.value = true
  try {
    const res = await api.post('/employee-shifts/generate', {
      employee_id: route.params.id,
      year: generateForm.value.year,
      month: generateForm.value.month
    })
    toast.success(res.data.message)
    showGenerateModal.value = false
    crudShifts.value?.load()
  } catch (error) {
    toast.error(error.response?.data?.message || 'Gagal generate shift')
  } finally {
    generatingShifts.value = false
  }
}


// ============================================================
// DEVICE LOCK MANAGEMENT
// ============================================================
const devices = ref([])
const loadingDevices = ref(false)
const approvingDevice = ref(null)

async function loadDevices() {
  if (!employee.value?.id) return
  loadingDevices.value = true
  try {
    const res = await api.get(`/employees/${employee.value.id}/devices`)
    devices.value = res.data.data || []
  } catch (err) {
    toast.error('Gagal memuat daftar perangkat')
  } finally {
    loadingDevices.value = false
  }
}

async function approveDevice(dev) {
  approvingDevice.value = dev.id
  try {
    const res = await api.post(`/employees/${employee.value.id}/devices/${dev.id}/approve`)
    toast.success(res.data.message || 'Perangkat berhasil disetujui')
    await loadDevices()
  } catch (err) {
    toast.error('Gagal menyetujui perangkat')
  } finally {
    approvingDevice.value = null
  }
}

async function rejectDevice(dev) {
  if (!confirm(`Tolak akses perangkat "${dev.device_name}"?`)) return
  approvingDevice.value = dev.id
  try {
    const res = await api.post(`/employees/${employee.value.id}/devices/${dev.id}/reject`)
    toast.success(res.data.message || 'Perangkat berhasil ditolak')
    await loadDevices()
  } catch (err) {
    toast.error('Gagal menolak perangkat')
  } finally {
    approvingDevice.value = null
  }
}

onMounted(() => {
  loadDependencies()
  loadData()
  loadLeaveBalances()
})

// =============================================
// APPROVAL & DELEGASI
// =============================================

const approvalModules = [
  { key: 'leave',            label: 'Cuti',                    icon: '🏖️' },
  { key: 'sick_leave',       label: 'Sakit',                   icon: '🤒' },
  { key: 'permission',       label: 'Ijin',                    icon: '📋' },
  { key: 'permit',           label: 'Permit Dinas',            icon: '🏢' },
  { key: 'reimbursement',    label: 'Reimburs/Klaim',          icon: '💳' },
  { key: 'overtime',         label: 'Lembur',                  icon: '⏱️' },
  { key: 'loan_request',     label: 'Kasbon',                  icon: '💸' },
  { key: 'workday_swap',     label: 'Tukar Hari',              icon: '🔄' },
]

const approvalLoading   = ref(false)
const activeApprovalModule = ref('')
const approvalLines     = ref({})   // { 'leave': [{approver_id, is_optional, ...}], ... }
const currentSteps      = ref([])
const savingApproval    = ref(false)

// Delegation
const delegations       = ref([])
const showDelegationForm = ref(false)
const savingDelegation  = ref(false)
const delegationForm    = ref({ delegate_id: '', module: '', start_date: '', end_date: '', reason: '' })

// All employees for approver / delegate dropdowns
const allEmployees = ref([])

const loadApprovalData = async () => {
  if (approvalLines.value && Object.keys(approvalLines.value).length > 0) return // already loaded
  approvalLoading.value = true
  try {
    const empId = employee.value?.id || route.params.id
    const [linesRes, delegationsRes, empsRes] = await Promise.all([
      api.get(`/employees/${empId}/approval-lines`),
      api.get(`/employees/${empId}/delegations`),
      api.get('/employees', { params: { per_page: 200 } }),
    ])
    approvalLines.value = linesRes.data.data || {}
    delegations.value = delegationsRes.data.data || []
    const empArr = empsRes.data?.data?.data ?? empsRes.data?.data ?? []
    allEmployees.value = empArr.filter(e => e.id !== (employee.value?.id))
  } catch (e) {
    toast.error('Gagal memuat data approval')
  } finally {
    approvalLoading.value = false
  }
}

const selectApprovalModule = (moduleKey) => {
  activeApprovalModule.value = moduleKey
  // Load existing steps for this module
  const existing = approvalLines.value[moduleKey] || []
  currentSteps.value = existing.map(s => ({
    approver_id: s.approver_id,
    is_optional: s.is_optional ?? false,
  }))
}

const addApprovalStep = () => {
  currentSteps.value.push({ approver_id: '', is_optional: false })
}

const removeApprovalStep = (idx) => {
  currentSteps.value.splice(idx, 1)
}

const saveApprovalLines = async () => {
  if (!activeApprovalModule.value) return
  const invalid = currentSteps.value.some(s => !s.approver_id)
  if (invalid) { toast.error('Pilih karyawan approver untuk setiap tingkat'); return }

  savingApproval.value = true
  try {
    const empId = employee.value?.id || route.params.id
    await api.post(`/employees/${empId}/approval-lines`, {
      module: activeApprovalModule.value,
      steps: currentSteps.value,
    })
    // Update local cache
    approvalLines.value[activeApprovalModule.value] = [...currentSteps.value]
    toast.success('Alur persetujuan berhasil disimpan!')
  } catch (e) {
    toast.error(e.response?.data?.message || 'Gagal menyimpan alur persetujuan')
  } finally {
    savingApproval.value = false
  }
}

const deleteModuleLines = async () => {
  if (!activeApprovalModule.value) return
  if (!confirm(`Hapus semua tingkat persetujuan untuk modul "${activeApprovalModule.value}"?`)) return
  savingApproval.value = true
  try {
    const empId = employee.value?.id || route.params.id
    await api.delete(`/employees/${empId}/approval-lines`, { data: { module: activeApprovalModule.value } })
    delete approvalLines.value[activeApprovalModule.value]
    currentSteps.value = []
    toast.success('Alur persetujuan dihapus.')
  } catch (e) {
    toast.error('Gagal menghapus alur persetujuan')
  } finally {
    savingApproval.value = false
  }
}

const saveDelegation = async () => {
  if (!delegationForm.value.delegate_id) { toast.error('Pilih karyawan yang menerima delegasi'); return }
  if (!delegationForm.value.start_date || !delegationForm.value.end_date) { toast.error('Isi tanggal delegasi'); return }
  savingDelegation.value = true
  try {
    const empId = employee.value?.id || route.params.id
    const res = await api.post(`/employees/${empId}/delegations`, delegationForm.value)
    delegations.value.unshift(res.data.data)
    delegationForm.value = { delegate_id: '', module: '', start_date: '', end_date: '', reason: '' }
    showDelegationForm.value = false
    toast.success('Delegasi berhasil ditambahkan!')
  } catch (e) {
    toast.error(e.response?.data?.message || 'Gagal menyimpan delegasi')
  } finally {
    savingDelegation.value = false
  }
}

const deleteDelegation = async (id) => {
  if (!confirm('Hapus delegasi ini?')) return
  try {
    const empId = employee.value?.id || route.params.id
    await api.delete(`/employees/${empId}/delegations/${id}`)
    delegations.value = delegations.value.filter(d => d.id !== id)
    toast.success('Delegasi dihapus.')
  } catch (e) {
    toast.error('Gagal menghapus delegasi')
  }
}

// LEAVE BALANCES
const leaveBalances = ref([])
const leaveTypes = ref([])
const loadingLeaveBalances = ref(false)
const showLeaveBalanceForm = ref(false)
const selectedLeaveYear = ref(new Date().getFullYear())
const editingLeaveBalanceId = ref(null)
const savingLeaveBalance = ref(false)

const leaveBalanceForm = ref({
  leave_type_id: '',
  year: new Date().getFullYear(),
  quota: 0
})

const selectedLeaveTypeDefault = computed(() => {
  const t = leaveTypes.value.find(item => item.id == leaveBalanceForm.value.leave_type_id)
  return t ? (t.default_days ?? 0) : 0
})

const leaveSummary = computed(() => {
  let totalQuota = 0
  let totalUsed = 0
  let totalRemaining = 0
  let annualRemaining = 0

  leaveBalances.value.forEach(b => {
    totalQuota += Math.round(Number(b.quota) || 0)
    totalUsed += Math.round(Number(b.used) || 0)
    totalRemaining += Math.round(Number(b.remaining) || 0)

    const typeName = (b.leave_type?.name || '').toLowerCase()
    const typeCode = (b.leave_type?.code || '').toUpperCase()
    if (typeCode === 'CT' || typeName.includes('tahunan')) {
      annualRemaining += Math.round(Number(b.remaining) || 0)
    }
  })

  return { totalQuota, totalUsed, totalRemaining, annualRemaining }
})

const annualLeaveRemaining = computed(() => {
  if (loadingLeaveBalances.value && leaveBalances.value.length === 0) return null
  if (!leaveBalances.value || leaveBalances.value.length === 0) return 0

  const hasAnnual = leaveBalances.value.some(b => {
    const typeName = (b.leave_type?.name || leaveTypes.value.find(t => t.id == b.leave_type_id)?.name || '').toLowerCase()
    const typeCode = (b.leave_type?.code || leaveTypes.value.find(t => t.id == b.leave_type_id)?.code || '').toUpperCase()
    return typeCode === 'CT' || typeCode === 'ANNUAL' || typeName.includes('tahunan') || typeName.includes('annual')
  })

  return hasAnnual ? leaveSummary.value.annualRemaining : leaveSummary.value.totalRemaining
})

const getLeaveTypeDefault = (b) => {
  if (b.leave_type && b.leave_type.default_days !== null && b.leave_type.default_days !== undefined) {
    return Math.round(Number(b.leave_type.default_days))
  }
  const found = leaveTypes.value.find(t => t.id == b.leave_type_id)
  if (found && found.default_days !== null && found.default_days !== undefined) {
    return Math.round(Number(found.default_days))
  }
  return '-'
}

const handleLeaveTypeChange = () => {
  const selectedType = leaveTypes.value.find(t => t.id == leaveBalanceForm.value.leave_type_id)
  const existing = leaveBalances.value.find(b => b.leave_type_id == leaveBalanceForm.value.leave_type_id && b.year == leaveBalanceForm.value.year)
  if (existing) {
    editingLeaveBalanceId.value = existing.id
    leaveBalanceForm.value.quota = Math.round(Number(existing.quota) || 0)
  } else if (selectedType) {
    editingLeaveBalanceId.value = null
    leaveBalanceForm.value.quota = Math.round(Number(selectedType.default_days) || 0)
  }
}

const handleLeaveYearChange = () => {
  leaveBalanceForm.value.year = selectedLeaveYear.value
  loadLeaveBalances()
}

const openCreateLeaveBalance = () => {
  editingLeaveBalanceId.value = null
  leaveBalanceForm.value = {
    leave_type_id: '',
    year: selectedLeaveYear.value,
    quota: 0
  }
  showLeaveBalanceForm.value = true
}

const openEditLeaveBalance = (b) => {
  editingLeaveBalanceId.value = b.id
  leaveBalanceForm.value = {
    leave_type_id: b.leave_type_id,
    year: b.year,
    quota: Math.round(Number(b.quota) || 0)
  }
  showLeaveBalanceForm.value = true
}

const loadLeaveBalances = async () => {
  if (leaveTypes.value.length === 0) {
    try {
      const res = await api.get('/leave-types', { params: { per_page: 100 } })
      leaveTypes.value = res.data?.data?.data ?? res.data?.data ?? []
    } catch(e) {}
  }
  loadingLeaveBalances.value = true
  try {
    const empId = employee.value?.id || route.params.id
    const res = await api.get('/leave-balances', {
      params: {
        employee_id: empId,
        year: selectedLeaveYear.value,
        per_page: 100
      }
    })
    leaveBalances.value = res.data?.data?.data ?? res.data?.data ?? []
  } catch (error) {
    console.error(error)
  } finally {
    loadingLeaveBalances.value = false
  }
}

const saveLeaveBalance = async () => {
  if (!leaveBalanceForm.value.leave_type_id) return toast.error('Pilih tipe cuti')
  const quotaInt = parseInt(leaveBalanceForm.value.quota, 10)
  if (isNaN(quotaInt) || quotaInt < 0) return toast.error('Jatah cuti harus diisi minimal 0')
  leaveBalanceForm.value.quota = quotaInt
  
  savingLeaveBalance.value = true
  try {
    const empId = employee.value?.id || route.params.id
    if (editingLeaveBalanceId.value) {
      await api.put(`/leave-balances/${editingLeaveBalanceId.value}`, {
        quota: quotaInt
      })
      toast.success('Jatah cuti berhasil diperbarui')
    } else {
      await api.post('/leave-balances', {
        employee_id: empId,
        ...leaveBalanceForm.value
      })
      toast.success('Jatah cuti berhasil disimpan')
    }
    showLeaveBalanceForm.value = false
    editingLeaveBalanceId.value = null
    loadLeaveBalances()
  } catch (error) {
    toast.error(error.response?.data?.message || 'Gagal menyimpan saldo cuti')
  } finally {
    savingLeaveBalance.value = false
  }
}

const deleteLeaveBalance = async (id) => {
  if (!confirm('Hapus saldo cuti ini?')) return
  try {
    await api.delete(`/leave-balances/${id}`)
    toast.success('Saldo cuti berhasil dihapus')
    loadLeaveBalances()
  } catch(e) {
    toast.error('Gagal menghapus saldo cuti')
  }
}

const mutationHistory = ref([])
const loadingMutations = ref(false)

async function loadMutationHistory() {
  if (!employee.value?.id) return
  loadingMutations.value = true
  try {
    const res = await api.get(`/employee-mutations?employee_id=${employee.value.id}`)
    mutationHistory.value = res.data?.data?.data || res.data?.data || []
  } catch (err) {
    console.error('Gagal mengambil riwayat mutasi', err)
  } finally {
    loadingMutations.value = false
  }
}

async function downloadMutationPdf(id) {
  try {
    toast.info('Menyiapkan file PDF...')
    const res = await api.get(`/employee-mutations/${id}/pdf`, { responseType: 'blob' })
    const file = new Blob([res.data], { type: 'application/pdf' })
    const fileURL = URL.createObjectURL(file)
    window.open(fileURL, '_blank')
  } catch (err) {
    const baseUrl = import.meta.env.VITE_API_URL || 'http://127.0.0.1:8011/api/v1'
    window.open(`${baseUrl}/employee-mutations/${id}/pdf`, '_blank')
  }
}

</script>

<style scoped>
/* Employee Detail Tabs Styling */
.employee-tabs {
  background-color: #f8fafc;
  border-bottom: 2px solid #cbd5e1 !important;
  display: flex;
  flex-wrap: nowrap;
  padding: 6px 6px 0 6px;
  gap: 3px;
  margin-bottom: 0;
}

.employee-tabs .nav-item {
  display: flex;
  align-items: stretch;
  flex: 1 1 auto;
}

.employee-tabs .nav-item .nav-link {
  display: flex;
  align-items: center;
  justify-content: center;
  text-align: center;
  width: 100%;
  height: 40px;
  white-space: nowrap;
  font-size: 0.76rem;
  font-weight: 500;
  padding: 0 8px;
  border-radius: 6px 6px 0 0;
  border: 1px solid #cbd5e1;
  border-bottom: none;
  background-color: #e2e8f0;
  color: #475569;
  transition: all 0.15s ease-in-out;
  margin-bottom: 0;
}

.employee-tabs .nav-item .nav-link:hover:not(.active) {
  background-color: #cbd5e1;
  color: #1e293b;
}

.employee-tabs .nav-item .nav-link.active {
  background-color: #d1ece7 !important;
  color: #008779 !important;
  font-weight: 600;
  border: 1px solid #80cbc4 !important;
  border-bottom: 3px solid #008779 !important;
  margin-bottom: -2px;
  z-index: 2;
  box-shadow: 0 -2px 4px rgba(0, 135, 121, 0.08);
}

.employee-tabs .nav-item .nav-link::after {
  display: none !important;
}

.branch-pill {
  border-color: #e2e8f0 !important;
  color: #1e293b;
  transition: all 0.15s ease-in-out;
}

.branch-pill:hover {
  background-color: #f8fafc;
  border-color: #cbd5e1 !important;
}

.btn-remove-tag {
  background: none;
  border: none;
  color: #94a3b8;
  font-size: 14px;
  font-weight: bold;
  line-height: 1;
  padding: 0 2px;
  cursor: pointer;
  transition: color 0.15s ease, transform 0.15s ease;
}

.btn-remove-tag:hover {
  color: #ef4444 !important;
  transform: scale(1.25);
}

.shadow-xs {
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}
</style>
