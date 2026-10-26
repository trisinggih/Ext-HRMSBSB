<template>
  <div class="mutation-create-page pb-5">
    <!-- Top Bar Navigation & Actions -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 pt-1">
      <div class="d-flex align-items-center gap-3">
        <router-link to="/hrms/mutations" class="btn btn-outline-secondary bg-white border px-3 py-2 rounded-3 text-dark d-flex align-items-center gap-2 shadow-xs">
          <i class="mdi mdi-arrow-left font-size-16"></i>
          <span class="fw-medium">Kembali</span>
        </router-link>
        <div>
          <h4 class="mb-0 fw-bold text-dark">Buat Surat Mutasi & Promosi Karyawan</h4>
          <p class="text-muted small mb-0">Formulir pengajuan mutasi cabang/departemen, rotasi kerja, atau promosi jabatan</p>
        </div>
      </div>
      <div class="d-flex align-items-center gap-2">
        <router-link to="/hrms/mutations" class="btn btn-light bg-white border px-4 py-2 rounded-3 text-secondary">
          Batal
        </router-link>
        <button 
          type="button" 
          class="btn px-4 py-2 rounded-3 d-flex align-items-center gap-2 shadow-sm text-white"
          style="background-color: #008779; border-color: #008779;"
          :disabled="saving"
          @click="handleSubmit"
        >
          <span v-if="saving" class="spinner-border spinner-border-sm" role="status"></span>
          <i v-else class="mdi mdi-check"></i>
          <span>{{ saving ? 'Menyimpan...' : 'Kirim Pengajuan Mutasi' }}</span>
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

    <!-- Form Container -->
    <form @submit.prevent="handleSubmit">
      <div class="row g-4">
        
        <!-- KOLOM UTAMA (KIRI / TENGAH) -->
        <div class="col-lg-8">
          
          <!-- CARD 1: IDENTITAS KARYAWAN -->
          <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white border-bottom-0 pt-4 px-4 pb-0 d-flex justify-content-between align-items-center">
              <div class="d-flex align-items-center gap-2">
                <span class="step-badge">Langkah 1</span>
                <h5 class="card-title fw-bold text-dark mb-0 font-size-16">Pilih Karyawan yang Diajukan</h5>
              </div>
              <span v-if="selectedEmployee" class="badge rounded-pill px-3 py-1.5 font-size-12 fw-medium" style="background-color: #dcfce7; color: #15803d;">
                <i class="mdi mdi-check-circle me-1"></i> Data Terpilih
              </span>
            </div>
            <div class="card-body p-4">
              <div class="mb-3">
                <label class="form-label fw-bold small text-dark mb-1">
                  Cari & Pilih Karyawan <span class="text-danger">*</span>
                </label>
                <div class="position-relative">
                  <select 
                    v-model="form.employee_id" 
                    @change="onEmployeeChange"
                    class="form-select border rounded-3 py-2 px-3 shadow-none fw-medium"
                    style="border-color: #cbd5e1; font-size: 14px; background-color: #ffffff;"
                    required
                  >
                    <option value="">-- Pilih Karyawan --</option>
                    <option v-for="emp in employeesList" :key="emp.id" :value="emp.id">
                      {{ emp.name || (emp.first_name + ' ' + (emp.last_name || '')) }} ({{ emp.employee_id || emp.nik || 'N/A' }}) - {{ emp.assignments?.[0]?.position?.name || 'Staf' }}
                    </option>
                  </select>
                </div>
              </div>

              <!-- Loading Indicator saat fetch detail -->
              <div v-if="loadingEmployee" class="text-center py-4 text-muted">
                <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                <span>Memuat data penempatan karyawan...</span>
              </div>

              <!-- Info Karyawan Terpilih -->
              <div v-else-if="selectedEmployee" class="p-3 rounded-4 mt-3" style="background-color: #f8fafc; border: 1px solid #e2e8f0;">
                <div class="d-flex flex-wrap align-items-center gap-3">
                  <div class="avatar-md bg-white rounded-circle shadow-sm border d-flex align-items-center justify-content-center fw-bold font-size-18" style="width: 54px; height: 54px; color: #008779;">
                    <img 
                      v-if="selectedEmployee.photo" 
                      :src="getPhotoUrl(selectedEmployee.photo)" 
                      class="rounded-circle w-100 h-100 object-fit-cover" 
                      alt="avatar" 
                    />
                    <span v-else>{{ (selectedEmployee.name || 'K').substring(0, 2).toUpperCase() }}</span>
                  </div>
                  <div class="flex-grow-1">
                    <h5 class="fw-bold text-dark mb-1 font-size-16">
                      {{ selectedEmployee.name }}
                      <span class="badge rounded-pill ms-2 font-size-11" style="background-color: #64748b; color: #fff;">{{ selectedEmployee.employee_id || '-' }}</span>
                    </h5>
                    <div class="text-secondary small d-flex flex-wrap gap-3">
                      <span><i class="mdi mdi-email-outline me-1"></i>{{ selectedEmployee.email || '-' }}</span>
                      <span><i class="mdi mdi-phone-outline me-1"></i>{{ selectedEmployee.phone || '-' }}</span>
                      <span class="badge rounded-pill px-2.5 py-1" style="background-color: #e0f2fe; color: #0369a1;">
                        <i class="mdi mdi-account-badge-outline me-1"></i>{{ currentPlacement.employment_status || 'Tetap' }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- CARD 2: PERBANDINGAN PENEMPATAN (SIDE-BY-SIDE: ASAL VS TUJUAN BARU) -->
          <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white border-bottom-0 pt-4 px-4 pb-0">
              <div class="d-flex align-items-center gap-2">
                <span class="step-badge">Langkah 2</span>
                <h5 class="card-title fw-bold text-dark mb-0 font-size-16">Perbandingan Penempatan Kerja (Asal vs Tujuan)</h5>
              </div>
              <p class="text-muted small mb-0 mt-1">Bandingkan formasi penempatan saat ini dengan target penempatan baru yang dituju</p>
            </div>
            
            <div class="card-body p-4">
              <div class="row g-4">
                
                <!-- KOLOM KIRI: PENEMPATAN SAAT INI (ASAL) -->
                <div class="col-md-6 border-end-md">
                  <div class="p-3 rounded-4 border mb-3 h-100" style="background-color: #f8fafc; border-color: #e2e8f0 !important;">
                    <div class="d-flex align-items-center gap-2 mb-2">
                      <span class="badge rounded-pill px-2.5 py-1 font-size-11" style="background-color: #64748b; color: #ffffff;">POSISI ASAL</span>
                      <h6 class="fw-bold mb-0 text-dark">Penempatan Saat Ini</h6>
                    </div>
                    <small class="text-muted d-block mb-3 font-size-12">Data penempatan aktif karyawan saat ini</small>

                    <div v-if="!selectedEmployee" class="text-center py-5 text-muted">
                      <i class="mdi mdi-account-arrow-right-outline font-size-36 text-secondary opacity-50 d-block mb-2"></i>
                      <div class="font-size-13 fw-semibold text-secondary">Belum ada karyawan yang dipilih</div>
                      <div class="font-size-12 text-muted mt-1">Pilih karyawan pada Langkah 1 untuk melihat penempatan saat ini</div>
                    </div>

                    <div v-else class="d-flex flex-column gap-3">
                      <div>
                        <div class="font-size-11 fw-bold text-muted text-uppercase tracking-wider">PERUSAHAAN</div>
                        <div class="fw-bold text-dark font-size-14">{{ currentPlacement.company_name || '(Belum diset)' }}</div>
                      </div>
                      <div>
                        <div class="font-size-11 fw-bold text-muted text-uppercase tracking-wider">CABANG</div>
                        <div class="fw-semibold text-dark font-size-14">{{ currentPlacement.branch_name || '-' }}</div>
                      </div>
                      <div>
                        <div class="font-size-11 fw-bold text-muted text-uppercase tracking-wider">DEPARTEMEN</div>
                        <div class="fw-semibold text-dark font-size-14">{{ currentPlacement.department_name || '-' }}</div>
                      </div>
                      <div>
                        <div class="font-size-11 fw-bold text-muted text-uppercase tracking-wider">DIVISI</div>
                        <div class="fw-semibold text-dark font-size-14">{{ currentPlacement.division_name || '-' }}</div>
                      </div>
                      <div>
                        <div class="font-size-11 fw-bold text-muted text-uppercase tracking-wider">JABATAN FUNGSIONAL</div>
                        <div class="fw-bold font-size-14" style="color: #008779;">{{ currentPlacement.position_name || '-' }}</div>
                      </div>
                      <div>
                        <div class="font-size-11 fw-bold text-muted text-uppercase tracking-wider">ATASAN LANGSUNG</div>
                        <div class="fw-semibold text-dark font-size-14">{{ currentPlacement.supervisor_name || '-' }}</div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- KOLOM KANAN: PENEMPATAN BARU (TUJUAN) -->
                <div class="col-md-6">
                  <div class="p-3 rounded-4 border mb-3 h-100" style="background-color: #f0fdfa; border: 1.5px solid #99f6e4 !important;">
                    <div class="d-flex align-items-center gap-2 mb-2">
                      <span class="badge rounded-pill px-2.5 py-1 font-size-11" style="background-color: #008779; color: #ffffff;">TARGET BARU</span>
                      <h6 class="fw-bold mb-0" style="color: #008779;">Penempatan Baru (Tujuan)</h6>
                    </div>
                    <small class="d-block mb-3 font-size-12" style="color: #0d9488;">Tentukan formasi penempatan baru yang akan dituju</small>

                    <div class="d-flex flex-column gap-3">
                      <!-- Perusahaan Baru -->
                      <div>
                        <label class="form-label small fw-bold text-dark mb-1">Perusahaan Baru <span class="text-danger">*</span></label>
                        <select v-model="form.new_company_id" @change="onCompanyChange" class="form-select border shadow-none rounded-3 py-2 px-3" style="background-color: #ffffff; border-color: #99f6e4; color: #0f172a; font-size: 13.5px;" required>
                          <option value="">-- Pilih Perusahaan --</option>
                          <option v-for="c in companiesList" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                      </div>

                      <!-- Area / Cabang Baru -->
                      <div>
                        <label class="form-label small fw-bold text-dark mb-1">Area / Cabang Baru</label>
                        <select v-model="form.new_branch_id" class="form-select border shadow-none rounded-3 py-2 px-3" style="background-color: #ffffff; border-color: #99f6e4; color: #0f172a; font-size: 13.5px;">
                          <option value="">-- Tetap sama / Pilih Cabang Baru --</option>
                          <option v-for="b in filteredBranches" :key="b.id" :value="b.id">{{ b.label || b.name }}</option>
                        </select>
                      </div>

                      <!-- Departemen Baru -->
                      <div>
                        <label class="form-label small fw-bold text-dark mb-1">Departemen Baru <span class="text-danger">*</span></label>
                        <select v-model="form.new_department_id" class="form-select border shadow-none rounded-3 py-2 px-3" style="background-color: #ffffff; border-color: #99f6e4; color: #0f172a; font-size: 13.5px;" required>
                          <option value="">-- Tetap sama / Pilih Departemen Baru --</option>
                          <option v-for="d in filteredDepartments" :key="d.id" :value="d.id">{{ d.name }}</option>
                        </select>
                      </div>

                      <!-- Divisi Baru -->
                      <div>
                        <label class="form-label small fw-bold text-dark mb-1">Divisi Baru</label>
                        <select v-model="form.new_division_id" class="form-select border shadow-none rounded-3 py-2 px-3" style="background-color: #ffffff; border-color: #99f6e4; color: #0f172a; font-size: 13.5px;">
                          <option value="">-- Tetap sama / Pilih Divisi Baru --</option>
                          <option v-for="dv in filteredDivisions" :key="dv.id" :value="dv.id">{{ dv.name }}</option>
                        </select>
                      </div>

                      <!-- Jabatan Fungsional Baru -->
                      <div>
                        <label class="form-label small fw-bold text-dark mb-1">Jabatan Fungsional Baru <span class="text-danger">*</span></label>
                        <select 
                          v-model="form.new_position_id" 
                          class="form-select border shadow-none rounded-3 py-2 px-3" 
                          style="background-color: #ffffff; border-color: #99f6e4; color: #0f172a; font-size: 13.5px;" 
                          required
                        >
                          <option value="">-- Pilih Jabatan Baru --</option>
                          <option v-for="p in filteredPositions" :key="p.id" :value="p.id">{{ p.label || p.name }}</option>
                        </select>
                      </div>

                      <!-- Atasan Baru -->
                      <div>
                        <label class="form-label small fw-bold text-dark mb-1">Atasan Langsung Baru (Opsional)</label>
                        <select v-model="form.new_supervisor_id" class="form-select border shadow-none rounded-3 py-2 px-3" style="background-color: #ffffff; border-color: #99f6e4; color: #0f172a; font-size: 13.5px;">
                          <option value="">-- Tetap sama / Pilih Atasan Baru --</option>
                          <option v-for="s in employeesList" :key="s.id" :value="s.id">
                            {{ s.name }} ({{ s.assignments?.[0]?.position?.name || 'Staf' }})
                          </option>
                        </select>
                      </div>

                    </div>
                  </div>
                </div>

              </div>
            </div>
          </div>

          <!-- CARD 3: CATATAN & ALASAN MUTASI -->
          <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white border-bottom-0 pt-4 px-4 pb-0">
              <div class="d-flex align-items-center gap-2">
                <span class="step-badge">Langkah 3</span>
                <h5 class="card-title fw-bold text-dark mb-0 font-size-16">Alasan & Pertimbangan Pengajuan</h5>
              </div>
            </div>
            <div class="card-body p-4">
              <div class="mb-0">
                <label class="form-label small fw-bold text-dark mb-1">Alasan / Catatan Resmi Mutasi</label>
                <textarea 
                  v-model="form.reason" 
                  rows="3" 
                  class="form-control rounded-3 py-2 px-3 shadow-none" 
                  style="background-color: #f8fafc; border: 1px solid #cbd5e1; font-size: 13.5px;"
                  placeholder="Contoh: Pemenuhan formasi baru di cabang Ciledug sesuai hasil evaluasi kinerja..."
                ></textarea>
                <small class="text-muted font-size-12 mt-1 d-block">Catatan ini akan tercantum pada Surat Keputusan (Memo) Mutasi.</small>
              </div>
            </div>
          </div>

        </div>

        <!-- KOLOM KANAN (SIDEBAR PENGATURAN) -->
        <div class="col-lg-4">
          
          <!-- TIPE PENGATURAN & TANGGAL EFEKTIF -->
          <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white border-bottom-0 pt-4 px-4 pb-0">
              <h5 class="card-title fw-bold text-dark mb-0 font-size-15">Tipe & Tanggal Efektif</h5>
            </div>
            <div class="card-body p-4">
              <!-- Tipe Pengajuan -->
              <div class="mb-4">
                <label class="form-label small fw-bold text-dark mb-2">Tipe Pengajuan <span class="text-danger">*</span></label>
                <div class="d-flex flex-column gap-2">
                  <div 
                    class="type-selection-card d-flex align-items-center gap-3 p-3 rounded-3 transition-all"
                    :class="{ 'active-mutasi': form.type === 'mutasi' }"
                    @click="form.type = 'mutasi'"
                  >
                    <div class="radio-indicator" :class="{ 'checked-mutasi': form.type === 'mutasi' }">
                      <div v-if="form.type === 'mutasi'" class="radio-dot-mutasi"></div>
                    </div>
                    <div>
                      <div class="type-title" :class="{ 'text-mutasi': form.type === 'mutasi' }">Mutasi (Transfer / Rotasi)</div>
                      <div class="type-sub">Perpindahan penempatan cabang, unit, atau departemen</div>
                    </div>
                  </div>

                  <div 
                    class="type-selection-card d-flex align-items-center gap-3 p-3 rounded-3 transition-all"
                    :class="{ 'active-promosi': form.type === 'promosi' }"
                    @click="form.type = 'promosi'"
                  >
                    <div class="radio-indicator" :class="{ 'checked-promosi': form.type === 'promosi' }">
                      <div v-if="form.type === 'promosi'" class="radio-dot-promosi"></div>
                    </div>
                    <div>
                      <div class="type-title" :class="{ 'text-promosi': form.type === 'promosi' }">Promosi (Kenaikan Jabatan)</div>
                      <div class="type-sub">Peningkatan jenjang karier atau tingkatan fungsional</div>
                    </div>
                  </div>

                  <div 
                    class="type-selection-card d-flex align-items-center gap-3 p-3 rounded-3 transition-all"
                    :class="{ 'active-demosi': form.type === 'demosi' }"
                    @click="form.type = 'demosi'"
                  >
                    <div class="radio-indicator" :class="{ 'checked-demosi': form.type === 'demosi' }">
                      <div v-if="form.type === 'demosi'" class="radio-dot-demosi"></div>
                    </div>
                    <div>
                      <div class="type-title" :class="{ 'text-demosi': form.type === 'demosi' }">Demosi (Penurunan Level / Jabatan)</div>
                      <div class="type-sub">Penyesuaian tingkatan level, golongan, atau penugasan fungsional</div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Tanggal Efektif -->
              <div class="mb-3">
                <label class="form-label small fw-bold text-dark mb-1">Tanggal Efektif Berlaku <span class="text-danger">*</span></label>
                <input 
                  type="date" 
                  v-model="form.effective_date" 
                  class="form-control rounded-3 py-2 px-3 shadow-none fw-medium" 
                  style="background-color: #f8fafc; border: 1px solid #cbd5e1; font-size: 13.5px;"
                  required 
                />
                <small class="text-muted font-size-12 mt-1 d-block">Tanggal mulai berlakunya penempatan dan pembagian gaji prorata.</small>
              </div>

              <!-- NIP Baru (Opsional / Jika Mutasi Perusahaan) -->
              <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <label class="form-label small fw-bold text-dark mb-0">NIP / ID Karyawan Baru</label>
                  <span class="badge bg-light text-secondary border font-size-10">Opsional / Beda Entitas</span>
                </div>
                <input 
                  type="text" 
                  v-model="form.new_nip" 
                  class="form-control rounded-3 py-2 px-3 shadow-none fw-medium" 
                  style="background-color: #f8fafc; border: 1px solid #cbd5e1; font-size: 13.5px;"
                  :placeholder="selectedEmployee ? (selectedEmployee.employee_id || selectedEmployee.nik || 'Contoh: PM100001') : 'Contoh: PM100001'" 
                />
                <small class="text-muted font-size-11 mt-1 d-block">
                  Isi jika mutasi ke perusahaan/entitas baru yang format NIP-nya berbeda. Kosongkan jika NIP tetap sama.
                </small>
              </div>

              <!-- Prorata Penggajian Switch -->
              <div class="mb-2 p-2.5 rounded-3 border" style="background-color: #f8fafc; border-color: #e2e8f0;">
                <div class="form-check form-switch m-0 d-flex align-items-center justify-content-between">
                  <div>
                    <label class="form-check-label small fw-bold text-dark d-block mb-0" for="proratePayrollSwitch">Prorata Penggajian</label>
                    <small class="text-muted font-size-11">Prorata gaji berjalan di PT asal & PT tujuan sesuai tgl efektif.</small>
                  </div>
                  <input class="form-check-input ms-2" type="checkbox" role="switch" id="proratePayrollSwitch" v-model="form.prorate_payroll">
                </div>
              </div>
            </div>
          </div>

          <!-- CARD: ALUR PERSETUJUAN (APPROVAL WORKFLOW) -->
          <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white border-bottom-0 pt-4 px-4 pb-0 d-flex justify-content-between align-items-center">
              <div>
                <h5 class="card-title fw-bold text-dark mb-0 font-size-15">Alur Persetujuan (Approval)</h5>
                <small class="text-muted font-size-12">Urutan verifikasi dan pengesahan memo mutasi</small>
              </div>
            </div>
            <div class="card-body p-4">
              <div class="p-3 rounded-3 font-size-12 mb-3 d-flex align-items-start gap-2" style="background-color: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af;">
                <i class="mdi mdi-information font-size-16 mt-n1" style="color: #2563eb;"></i>
                <div>Persetujuan akan diproses berjenjang dari <b>Tingkat 1</b> hingga selesai. Minimal tentukan 1 approver wajib.</div>
              </div>

              <!-- List Approver Steps -->
              <div class="d-flex flex-column gap-3 mb-3">
                <div 
                  v-for="(step, idx) in approverSteps" 
                  :key="idx" 
                  class="p-3 rounded-3 position-relative"
                  style="background-color: #f8fafc; border: 1px solid #e2e8f0;"
                >
                  <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="d-flex align-items-center gap-1.5">
                      <span 
                        class="badge rounded-pill font-size-11 fw-semibold"
                        :style="idx === 0 ? 'background-color: #008779; color: #fff;' : 'background-color: #64748b; color: #fff;'"
                      >
                        Tingkat {{ idx + 1 }} {{ idx === 0 ? '(Wajib)' : '(Opsional)' }}
                      </span>
                      <span v-if="idx === 0" class="text-danger font-size-12 fw-bold">*</span>
                    </div>
                    <button 
                      v-if="idx > 0" 
                      type="button" 
                      @click="removeApproverStep(idx)" 
                      class="btn btn-sm btn-link text-danger p-0 text-decoration-none font-size-12 d-flex align-items-center gap-1"
                    >
                      <i class="mdi mdi-trash-can-outline font-size-14"></i>
                      <span>Hapus</span>
                    </button>
                  </div>

                  <select 
                    v-model="step.employee_id" 
                    class="form-select bg-white border py-2 px-3 shadow-none rounded-3 font-size-13" 
                    style="border-color: #cbd5e1;"
                    :required="idx === 0"
                  >
                    <option value="">-- Pilih Approver Tingkat {{ idx + 1 }} --</option>
                    <option v-for="emp in employeesList" :key="emp.id" :value="emp.id">
                      {{ emp.name }} ({{ emp.assignments?.[0]?.position?.name || 'Staf' }} - {{ emp.assignments?.[0]?.department?.name || 'Dept' }})
                    </option>
                  </select>
                </div>
              </div>

              <!-- Tombol Tambah Tingkat Approver -->
              <button 
                type="button" 
                v-if="approverSteps.length < 5" 
                @click="addApproverStep" 
                class="btn btn-sm w-100 py-2 rounded-3 d-flex align-items-center justify-content-center gap-2 transition-all"
                style="border: 1.5px dashed #008779; color: #008779; background-color: #f0fdfa; font-weight: 600;"
              >
                <i class="mdi mdi-plus-circle font-size-16"></i>
                <span>Tambah Tingkat Approver (+1)</span>
              </button>
            </div>
          </div>

          <!-- CARD SUMMARY SUBMIT -->
          <div class="card border-0 shadow-sm rounded-4 p-4 text-white" style="background: linear-gradient(135deg, #008779 0%, #005a51 100%);">
            <div class="d-flex align-items-center gap-2 mb-2">
              <i class="mdi mdi-send-check font-size-22"></i>
              <h5 class="fw-bold mb-0 text-white">Siap Mengirim?</h5>
            </div>
            <p class="font-size-13 text-white-50 mb-3">
              Setelah dikirim, permohonan mutasi akan berstatus <b>Pending</b> dan otomatis muncul di notifikasi dan daftar persetujuan (approval) mobile/web approver terpilih.
            </p>
            <button 
              type="button" 
              class="btn btn-light fw-bold w-100 py-2.5 rounded-3 shadow-sm d-flex align-items-center justify-content-center gap-2"
              style="color: #008779;"
              :disabled="saving"
              @click="handleSubmit"
            >
              <span v-if="saving" class="spinner-border spinner-border-sm me-2" role="status"></span>
              <i v-else class="mdi mdi-send me-1"></i>
              <span>{{ saving ? 'Memproses Pengajuan...' : 'Kirim Pengajuan Mutasi Sekarang' }}</span>
            </button>
          </div>

        </div>

      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { toast } from 'vue3-toastify'
import api from '../api'

const router = useRouter()

const saving = ref(false)
const errorMessage = ref('')
const loadingEmployee = ref(false)

const employeesList = ref([])
const companiesList = ref([])
const branchesList = ref([])
const departmentsList = ref([])
const divisionsList = ref([])
const positionsList = ref([])

const selectedEmployee = ref(null)

const form = reactive({
  employee_id: '',
  type: 'mutasi',
  effective_date: new Date().toISOString().substring(0, 10),
  new_company_id: '',
  new_branch_id: '',
  new_department_id: '',
  new_division_id: '',
  new_position_id: '',
  new_supervisor_id: '',
  new_nip: '',
  prorate_payroll: true,
  reason: '',
})

// Dinamis approver steps
const approverSteps = ref([
  { employee_id: '' }
])

const addApproverStep = () => {
  if (approverSteps.value.length < 5) {
    approverSteps.value.push({ employee_id: '' })
  }
}

const removeApproverStep = (idx) => {
  approverSteps.value.splice(idx, 1)
}

// Current placement reader
const currentPlacement = computed(() => {
  if (!selectedEmployee.value) return {}
  const primary = selectedEmployee.value.assignments?.find(a => a.is_primary) || selectedEmployee.value.assignments?.[0]
  if (!primary) return {}

  return {
    company_name: primary.company?.name || '-',
    branch_name: primary.branch?.name || '-',
    department_name: primary.department?.name || '-',
    division_name: primary.division?.name || '-',
    position_name: primary.position?.name || '-',
    supervisor_name: primary.supervisor?.name || '-',
    employment_status: primary.employment_status === 'permanent' ? 'Tetap (Permanent)' : (primary.employment_status || 'Tetap'),
  }
})

// Filtered options based on selected new company/branch/department/division
const filteredBranches = computed(() => {
  return branchesList.value.map(b => {
    const comp = companiesList.value.find(c => String(c.id) === String(b.company_id))
    const compName = b.company?.name || comp?.name || ''
    return {
      ...b,
      label: compName ? `${b.name} (${compName})` : b.name
    }
  })
})

const filteredDepartments = computed(() => {
  return departmentsList.value
})

const filteredDivisions = computed(() => {
  return divisionsList.value
})

const filteredPositions = computed(() => {
  if (form.new_division_id) {
    const matching = positionsList.value.filter(p => String(p.division_id) === String(form.new_division_id))
    const others = positionsList.value.filter(p => String(p.division_id) !== String(form.new_division_id))
    if (matching.length > 0) {
      return [
        ...matching.map(p => ({ ...p, label: `★ ${p.name}` })),
        ...others.map(p => ({ ...p, label: p.name }))
      ]
    }
  }
  return positionsList.value.map(p => ({ ...p, label: p.name }))
})

const onCompanyChange = () => {}
const onBranchChange = () => {}
const onDepartmentChange = () => {}
const onDivisionChange = () => {}

const getPhotoUrl = (photo) => {
  if (!photo) return ''
  if (photo.startsWith('http')) return photo
  return `http://localhost:8011/storage/${photo}`
}

onMounted(async () => {
  try {
    const [empRes, compRes, branchRes, deptRes, divRes, posRes] = await Promise.all([
      api.get('/employees?per_page=1000&is_active=1'),
      api.get('/companies?per_page=1000'),
      api.get('/branches?per_page=1000'),
      api.get('/departments?per_page=1000'),
      api.get('/divisions?per_page=1000'),
      api.get('/positions?per_page=1000'),
    ])

    employeesList.value = empRes.data?.data?.data || empRes.data?.data || []
    companiesList.value = compRes.data?.data?.data || compRes.data?.data || []
    branchesList.value = branchRes.data?.data?.data || branchRes.data?.data || []
    departmentsList.value = deptRes.data?.data?.data || deptRes.data?.data || []
    divisionsList.value = divRes.data?.data?.data || divRes.data?.data || []
    positionsList.value = posRes.data?.data?.data || posRes.data?.data || []
  } catch (e) {
    console.error('Gagal memuat master data', e)
  }
})

const onEmployeeChange = async () => {
  if (!form.employee_id) {
    selectedEmployee.value = null
    return
  }

  loadingEmployee.value = true
  try {
    const res = await api.get(`/employees/${form.employee_id}`)
    selectedEmployee.value = res.data?.data || null

    // Default target company to same as old company initially
    const primary = selectedEmployee.value?.assignments?.find(a => a.is_primary) || selectedEmployee.value?.assignments?.[0]
    if (primary) {
      form.new_company_id = primary.company_id || ''
      form.new_branch_id = primary.branch_id || ''
      form.new_department_id = primary.department_id || ''
      form.new_division_id = primary.division_id || ''
      form.new_position_id = primary.position_id || ''
    }
  } catch (e) {
    console.error('Gagal memuat data detail karyawan', e)
    toast.error('Gagal mengambil data detail karyawan')
  } finally {
    loadingEmployee.value = false
  }
}

const handleSubmit = async () => {
  errorMessage.value = ''

  if (!form.employee_id) {
    errorMessage.value = 'Silakan pilih karyawan yang akan diajukan mutasi.'
    toast.error(errorMessage.value)
    return
  }

  if (!form.new_company_id) {
    errorMessage.value = 'Perusahaan baru wajib dipilih.'
    toast.error(errorMessage.value)
    return
  }

  if (!form.new_department_id) {
    errorMessage.value = 'Departemen baru wajib dipilih.'
    toast.error(errorMessage.value)
    return
  }

  if (!form.new_position_id) {
    errorMessage.value = 'Jabatan fungsional baru wajib dipilih.'
    toast.error(errorMessage.value)
    return
  }

  if (!form.effective_date) {
    errorMessage.value = 'Tanggal efektif berlaku wajib diisi.'
    toast.error(errorMessage.value)
    return
  }

  // Filter approver IDs (non-empty)
  const validApproverIds = approverSteps.value
    .map(s => s.employee_id)
    .filter(id => Boolean(id))

  if (validApproverIds.length === 0) {
    errorMessage.value = 'Minimal tentukan 1 Approver untuk menyetujui mutasi ini.'
    toast.error(errorMessage.value)
    return
  }

  saving.value = true
  try {
    const payload = {
      employee_id: form.employee_id,
      type: form.type,
      effective_date: form.effective_date,
      new_company_id: form.new_company_id,
      new_branch_id: form.new_branch_id || null,
      new_department_id: form.new_department_id,
      new_division_id: form.new_division_id || null,
      new_position_id: form.new_position_id,
      new_supervisor_id: form.new_supervisor_id || null,
      new_nip: form.new_nip ? form.new_nip.trim() : null,
      prorate_payroll: form.prorate_payroll,
      reason: form.reason || null,
      approvers: validApproverIds,
    }

    const res = await api.post('/employee-mutations', payload)
    toast.success(res.data?.message || 'Surat mutasi berhasil dibuat!')
    router.push('/hrms/mutations')
  } catch (e) {
    const msg = e.response?.data?.message || 'Gagal menyimpan surat mutasi.'
    errorMessage.value = msg
    toast.error(msg)
  } finally {
    saving.value = false
  }
}
</script>

<style scoped>
.mutation-create-page {
  animation: fadeIn 0.25s ease-out;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(6px); }
  to { opacity: 1; transform: translateY(0); }
}

.step-badge {
  background-color: #e6f4f2;
  color: #008779;
  font-weight: 700;
  border-radius: 50rem;
  padding: 6px 14px;
  font-size: 12px;
  display: inline-block;
}

.type-selection-card {
  cursor: pointer;
  border: 1.5px solid #e2e8f0;
  background-color: #ffffff;
  user-select: none;
}
.type-selection-card:hover {
  border-color: #cbd5e1;
  background-color: #f8fafc;
}
.type-selection-card.active-mutasi {
  border-color: #008779;
  background-color: #f0fdfa;
}
.type-selection-card.active-promosi {
  border-color: #16a34a;
  background-color: #f0fdf4;
}
.type-selection-card.active-demosi {
  border-color: #ea580c;
  background-color: #fff7ed;
}

.type-title {
  font-size: 14px;
  font-weight: 700;
  color: #1e293b;
}
.text-mutasi {
  color: #008779;
}
.text-promosi {
  color: #15803d;
}
.text-demosi {
  color: #c2410c;
}

.type-sub {
  font-size: 12px;
  color: #64748b;
  margin-top: 2px;
}

.radio-indicator {
  width: 20px;
  height: 20px;
  border-radius: 50%;
  border: 2px solid #cbd5e1;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  background: #ffffff;
}
.radio-indicator.checked-mutasi {
  border-color: #008779;
}
.radio-indicator.checked-promosi {
  border-color: #16a34a;
}
.radio-indicator.checked-demosi {
  border-color: #ea580c;
}
.radio-dot-mutasi {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background-color: #008779;
}
.radio-dot-promosi {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background-color: #16a34a;
}
.radio-dot-demosi {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background-color: #ea580c;
}

.transition-all {
  transition: all 0.2s ease-in-out;
}

.shadow-xs {
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

.tracking-wider {
  letter-spacing: 0.05em;
}

@media (min-width: 768px) {
  .border-end-md {
    border-right: 1px dashed #e2e8f0;
  }
}
</style>
