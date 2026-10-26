<template>
  <div class="asset-request-form-page pb-5">
    <!-- Top Bar Navigation & Actions -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 pt-1">
      <div class="d-flex align-items-center gap-3">
        <router-link
          to="/hrms/asset-requests"
          class="btn btn-light bg-white border shadow-sm px-3 py-2 rounded-3 text-secondary d-flex align-items-center gap-2"
        >
          <span class="fw-medium">&larr; Kembali</span>
        </router-link>
        <div>
          <h4 class="mb-0 fw-bold text-dark">
            {{ isEdit ? 'Ubah Pengajuan Aset & Inventaris' : 'Tambah Pengajuan Aset & Inventaris' }}
          </h4>
          <p class="text-muted small mb-0">
            {{ isEquipmentForm(form.request_type) 
              ? 'Formulir resmi permohonan pergantian alat atau pengadaan baru peralatan kerja' 
              : 'Formulir permohonan kebutuhan perlengkapan kantor rutin, sarana, atau karyawan baru' }}
          </p>
        </div>
      </div>
      <div class="d-flex align-items-center gap-2">
        <router-link to="/hrms/asset-requests" class="btn btn-outline-secondary px-4 py-2 rounded-3">
          Batal
        </router-link>
        <button
          type="button"
          class="btn btn-primary px-4 py-2 rounded-3 d-flex align-items-center gap-2 shadow-sm"
          :disabled="submitting || loadingData"
          @click="submitForm"
        >
          <span v-if="submitting" class="spinner-border spinner-border-sm" role="status"></span>
          <span>{{ submitting ? 'Menyimpan...' : (isEdit ? 'Simpan Perubahan' : 'Kirim Pengajuan') }}</span>
        </button>
      </div>
    </div>

    <!-- Alert Notifikasi Error -->
    <div v-if="errorMessage" class="alert alert-danger alert-dismissible fade show rounded-3 mb-4 shadow-sm" role="alert">
      <div class="d-flex align-items-center gap-2">
        <div>{{ errorMessage }}</div>
      </div>
      <button type="button" class="btn-close" @click="errorMessage = ''" aria-label="Close"></button>
    </div>

    <!-- Loading State -->
    <div v-if="loadingData" class="card border-0 shadow-sm rounded-4 p-5 text-center">
      <div class="spinner-border text-primary mx-auto mb-3" role="status"></div>
      <p class="text-muted mb-0">Memuat data formulir pengajuan...</p>
    </div>

    <!-- Form Content -->
    <form v-else @submit.prevent="submitForm">
      <!-- 1. KATEGORI FORMULIR (SWITCHER) -->
      <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
          <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div>
              <h5 class="fw-bold text-dark mb-1">Pilih Kategori Formulir</h5>
              <p class="text-muted small mb-0">Tentukan jenis dokumen transaksi yang ingin diajukan.</p>
            </div>
            <span class="badge px-3 py-2" :class="isEquipmentForm(form.request_type) ? 'bg-primary-subtle text-primary border border-primary-subtle' : 'bg-success-subtle text-success border border-success-subtle'">
              {{ isEquipmentForm(form.request_type) ? 'Format: Formulir Peralatan Kerja' : 'Format: Form Perlengkapan Kantor' }}
            </span>
          </div>

          <div class="row g-3">
            <div class="col-md-6">
              <div
                class="card p-3 border-2 h-100 transition-all cursor-pointer"
                :class="isEquipmentForm(form.request_type) ? 'border-primary bg-primary-subtle bg-opacity-25 shadow-sm' : 'border-light-subtle bg-light'"
                @click="switchFormCategory('equipment')"
                style="cursor: pointer; border-radius: 12px;"
              >
                <div class="d-flex align-items-start gap-3">
                  <div class="form-check mt-1">
                    <input class="form-check-input" type="radio" :checked="isEquipmentForm(form.request_type)">
                  </div>
                  <div>
                    <h6 class="fw-bold text-dark mb-1">Formulir Permohonan Peralatan Kerja</h6>
                    <p class="text-muted small mb-2">
                      Khusus untuk permohonan <strong>Pergantian Alat</strong> (rusak/usang) atau <strong>Pengadaan Baru</strong> unit peralatan kerja.
                    </p>
                    <span class="badge bg-white text-secondary border small">Output: PDF Formulir Peralatan Kerja</span>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-md-6">
              <div
                class="card p-3 border-2 h-100 transition-all cursor-pointer"
                :class="!isEquipmentForm(form.request_type) ? 'border-primary bg-primary-subtle bg-opacity-25 shadow-sm' : 'border-light-subtle bg-light'"
                @click="switchFormCategory('office')"
                style="cursor: pointer; border-radius: 12px;"
              >
                <div class="d-flex align-items-start gap-3">
                  <div class="form-check mt-1">
                    <input class="form-check-input" type="radio" :checked="!isEquipmentForm(form.request_type)">
                  </div>
                  <div>
                    <h6 class="fw-bold text-dark mb-1">Pengajuan Perlengkapan Kantor (Rutin/ATK)</h6>
                    <p class="text-muted small mb-2">
                      Untuk pengajuan multi-item ATK, perlengkapan kebersihan departemen, perbaikan sarana, dan karyawan baru.
                    </p>
                    <span class="badge bg-white text-secondary border small">Output: PDF Form Tabel Multi-Item</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ==================== BAGIAN A: FORMULIR PERMOHONAN PERALATAN KERJA ==================== -->
      <template v-if="isEquipmentForm(form.request_type)">
        <!-- Opsi Permohonan: Pergantian Alat vs Pengadaan Baru -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
          <div class="card-header bg-white border-bottom-0 pt-4 px-4 pb-0">
            <h5 class="fw-bold text-dark mb-1">Jenis Permohonan Alat</h5>
            <p class="text-muted small mb-0">Pilih apakah pengajuan ini untuk pergantian alat kerja atau pengadaan unit baru.</p>
          </div>
          <div class="card-body p-4">
            <div class="row g-3">
              <div class="col-md-6">
                <div
                  class="card p-3 border text-center h-100"
                  :class="{ 'border-primary bg-primary-subtle text-primary fw-bold': form.request_type === 'pergantian_alat' }"
                  @click="form.request_type = 'pergantian_alat'"
                  style="cursor: pointer; border-radius: 10px;"
                >
                  <div class="form-check d-inline-block text-start mb-1">
                    <input class="form-check-input" type="radio" :checked="form.request_type === 'pergantian_alat'">
                    <label class="form-check-label fw-bold ms-1">Pergantian Alat</label>
                  </div>
                  <div class="small text-muted">Untuk alat kerja yang rusak, usang, atau tidak layak pakai lagi.</div>
                </div>
              </div>
              <div class="col-md-6">
                <div
                  class="card p-3 border text-center h-100"
                  :class="{ 'border-primary bg-primary-subtle text-primary fw-bold': form.request_type === 'pengadaan_baru' }"
                  @click="form.request_type = 'pengadaan_baru'"
                  style="cursor: pointer; border-radius: 10px;"
                >
                  <div class="form-check d-inline-block text-start mb-1">
                    <input class="form-check-input" type="radio" :checked="form.request_type === 'pengadaan_baru'">
                    <label class="form-check-label fw-bold ms-1">Pengadaan Baru</label>
                  </div>
                  <div class="small text-muted">Untuk penambahan peralatan kerja baru penunjang aktivitas kerja.</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Identitas Karyawan Yang Mengajukan -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
          <div class="card-header bg-white border-bottom-0 pt-4 px-4 pb-0">
            <h5 class="fw-bold text-dark mb-1">Data Karyawan Yang Mengajukan</h5>
            <p class="text-muted small mb-0">Informasi karyawan yang mengajukan permohonan peralatan kerja.</p>
          </div>
          <div class="card-body p-4">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label small fw-semibold text-secondary">Perusahaan <span class="text-danger">*</span></label>
                <select v-model="form.company_id" required class="form-select bg-light border-0" @change="onCompanyChange">
                  <option :value="null">-- Pilih Perusahaan --</option>
                  <option v-for="c in companies" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-semibold text-secondary">Cabang</label>
                <select v-model="form.branch_id" class="form-select bg-light border-0" @change="onBranchChange">
                  <option :value="null">-- {{ form.company_id ? 'Pilih Cabang' : 'Pilih Perusahaan Terlebih Dahulu' }} --</option>
                  <option v-for="b in filteredBranches" :key="b.id" :value="b.id">{{ b.name }}</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-semibold text-secondary">Departemen</label>
                <select v-model="form.department_id" class="form-select bg-light border-0" @change="onDepartmentChange">
                  <option :value="null">-- {{ form.branch_id ? 'Pilih Departemen' : 'Pilih Cabang Terlebih Dahulu' }} --</option>
                  <option v-for="d in filteredDepartments" :key="d.id" :value="d.id">{{ d.name }}</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-semibold text-secondary">Karyawan Pemohon <span class="text-danger">*</span></label>
                <select v-model="form.requester_id" required class="form-select bg-light border-0" @change="onRequesterChange">
                  <option :value="null">-- Pilih Karyawan Pemohon ({{ filteredEmployees.length }} tersedia) --</option>
                  <option v-for="emp in filteredEmployees" :key="emp.id" :value="emp.id">
                    {{ emp.employee_id ? emp.employee_id + ' - ' : '' }}{{ emp.name }}
                  </option>
                </select>
              </div>
            </div>
          </div>
        </div>

        <!-- Detail Peralatan Kerja -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
          <div class="card-header bg-white border-bottom-0 pt-4 px-4 pb-0">
            <h5 class="fw-bold text-dark mb-1">Spesifikasi Peralatan Kerja</h5>
            <p class="text-muted small mb-0">Rincian jenis alat, merek, tipe, spesifikasi teknis, serta alasan permohonan.</p>
          </div>
          <div class="card-body p-4">
            <div class="row g-3">
              <div class="col-12">
                <label class="form-label small fw-semibold text-secondary">Jenis Peralatan Kerja <span class="text-danger">*</span></label>
                <input
                  type="text"
                  v-model="form.item_name"
                  required
                  class="form-control bg-light border-0"
                  placeholder="Contoh: Laptop, Komputer PC, Monitor, Mouse & Keyboard, Mesin Bor..."
                />
              </div>

              <div class="col-md-6">
                <label class="form-label small fw-semibold text-secondary">Merek</label>
                <input
                  type="text"
                  v-model="form.brand"
                  class="form-control bg-light border-0"
                  placeholder="Contoh: Lenovo, ASUS, HP, Logitech, Makita..."
                />
              </div>

              <div class="col-md-6">
                <label class="form-label small fw-semibold text-secondary">Tipe / Model</label>
                <input
                  type="text"
                  v-model="form.model"
                  class="form-control bg-light border-0"
                  placeholder="Contoh: ThinkPad L14 Gen 4, MX Master 3S..."
                />
              </div>

              <div class="col-md-6">
                <label class="form-label small fw-semibold text-secondary">Jumlah Unit</label>
                <input
                  type="number"
                  v-model.number="form.quantity"
                  min="1"
                  class="form-control bg-light border-0"
                  placeholder="1"
                />
              </div>

              <div class="col-md-6">
                <label class="form-label small fw-semibold text-secondary">Estimasi Harga (Opsional - Rp)</label>
                <input
                  type="number"
                  v-model.number="form.estimated_price"
                  min="0"
                  class="form-control bg-light border-0"
                  placeholder="0"
                />
              </div>

              <div class="col-12">
                <label class="form-label small fw-semibold text-secondary">Spesifikasi</label>
                <textarea
                  v-model="form.specifications"
                  rows="3"
                  class="form-control bg-light border-0"
                  placeholder="Contoh: Prosesor Core i7, RAM 16GB, SSD 512GB NVMe, Layar 14 inch FHD..."
                ></textarea>
              </div>

              <div class="col-12">
                <label class="form-label small fw-semibold text-secondary">
                  {{ form.request_type === 'pergantian_alat' ? 'Alasan Penggantian' : 'Alasan Pengadaan Baru' }} <span class="text-danger">*</span>
                </label>
                <textarea
                  v-model="form.purpose"
                  required
                  rows="4"
                  class="form-control bg-light border-0"
                  :placeholder="form.request_type === 'pergantian_alat' 
                    ? 'Jelaskan alasan penggantian alat (misal: alat kerja lama rusak komponen utama, sering mati total, tidak bisa dipakai bekerja lagi)...' 
                    : 'Jelaskan alasan pengadaan baru alat kerja ini...'"
                ></textarea>
              </div>
            </div>
          </div>
        </div>
      </template>

      <!-- ==================== BAGIAN B: PENGAJUAN PERLENGKAPAN KANTOR (RUTIN/ATK) ==================== -->
      <template v-else>
        <!-- Tipe Pengajuan Rutin -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
          <div class="card-header bg-white border-bottom-0 pt-4 px-4 pb-0">
            <h5 class="fw-bold text-dark mb-1">Tipe Kebutuhan Perlengkapan Kantor</h5>
            <p class="text-muted small mb-0">Pilih jenis peruntukan perlengkapan yang diajukan.</p>
          </div>
          <div class="card-body p-4">
            <div class="row g-3">
              <div class="col-md-4">
                <div
                  class="card p-3 border text-center h-100"
                  :class="{ 'border-primary bg-primary-subtle text-primary fw-bold': form.request_type === 'kebutuhan_karyawan_baru' }"
                  @click="form.request_type = 'kebutuhan_karyawan_baru'"
                  style="cursor: pointer; border-radius: 10px;"
                >
                  <div class="small fw-semibold">Kebutuhan Aset Karyawan Baru</div>
                  <div class="text-muted small mt-1" style="font-size: 0.78rem;">Meja, kursi, atau alat kerja khusus staf baru</div>
                </div>
              </div>
              <div class="col-md-4">
                <div
                  class="card p-3 border text-center h-100"
                  :class="{ 'border-primary bg-primary-subtle text-primary fw-bold': form.request_type === 'kebutuhan_rutin' }"
                  @click="form.request_type = 'kebutuhan_rutin'"
                  style="cursor: pointer; border-radius: 10px;"
                >
                  <div class="small fw-semibold">Kebutuhan Rutin Departemen</div>
                  <div class="text-muted small mt-1" style="font-size: 0.78rem;">Stok ATK, kertas, perlengkapan kebersihan, dll</div>
                </div>
              </div>
              <div class="col-md-4">
                <div
                  class="card p-3 border text-center h-100"
                  :class="{ 'border-primary bg-primary-subtle text-primary fw-bold': form.request_type === 'kebutuhan_perbaikan' }"
                  @click="form.request_type = 'kebutuhan_perbaikan'"
                  style="cursor: pointer; border-radius: 10px;"
                >
                  <div class="small fw-semibold">Perbaikan Lampu, Kabel & Sarana</div>
                  <div class="text-muted small mt-1" style="font-size: 0.78rem;">Penggantian sarana kelistrikan / fasilitas kantor</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Header Data (Perusahaan, Cabang, Departemen, Bulan, Pemohon) -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
          <div class="card-header bg-white border-bottom-0 pt-4 px-4 pb-0">
            <h5 class="fw-bold text-dark mb-1">Informasi Lokasi & Pemohon</h5>
            <p class="text-muted small mb-0">Tentukan unit perusahaan dan karyawan pemohon perlengkapan.</p>
          </div>
          <div class="card-body p-4">
            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label small fw-semibold text-secondary">Perusahaan <span class="text-danger">*</span></label>
                <select v-model="form.company_id" required class="form-select bg-light border-0" @change="onCompanyChange">
                  <option :value="null">-- Pilih Perusahaan --</option>
                  <option v-for="c in companies" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label small fw-semibold text-secondary">Cabang</label>
                <select v-model="form.branch_id" class="form-select bg-light border-0" @change="onBranchChange">
                  <option :value="null">-- {{ form.company_id ? 'Pilih Cabang' : 'Pilih Perusahaan Terlebih Dahulu' }} --</option>
                  <option v-for="b in filteredBranches" :key="b.id" :value="b.id">{{ b.name }}</option>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label small fw-semibold text-secondary">Departemen</label>
                <select v-model="form.department_id" class="form-select bg-light border-0" @change="onDepartmentChange">
                  <option :value="null">-- {{ form.branch_id ? 'Pilih Departemen' : 'Pilih Cabang Terlebih Dahulu' }} --</option>
                  <option v-for="d in filteredDepartments" :key="d.id" :value="d.id">{{ d.name }}</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-semibold text-secondary">Pemohon (Karyawan) <span class="text-danger">*</span></label>
                <select v-model="form.requester_id" required class="form-select bg-light border-0" @change="onRequesterChange">
                  <option :value="null">-- Pilih Karyawan Pemohon ({{ filteredEmployees.length }} tersedia) --</option>
                  <option v-for="emp in filteredEmployees" :key="emp.id" :value="emp.id">
                    {{ emp.employee_id ? emp.employee_id + ' - ' : '' }}{{ emp.name }}
                  </option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-semibold text-secondary">Bulan Pengajuan</label>
                <input type="text" v-model="form.submission_month" class="form-control bg-light border-0" placeholder="Contoh: Oktober 2026" />
              </div>

              <!-- Variasi Tambahan Sesuai Sub-Tipe -->
              <div v-if="form.request_type === 'kebutuhan_karyawan_baru'" class="col-12 pt-2">
                <div class="row g-3 p-3 bg-light rounded-3 border">
                  <div class="col-md-6">
                    <label class="form-label small fw-semibold text-secondary">Karyawan Baru Yang Dituju</label>
                    <input type="text" v-model="form.for_who" class="form-control bg-white border" placeholder="Nama karyawan baru / Posisi baru" />
                  </div>
                  <div class="col-md-6">
                    <label class="form-label small fw-semibold text-secondary">Penempatan Lokasi / Meja</label>
                    <input type="text" v-model="form.placement" class="form-control bg-white border" placeholder="Contoh: Ruang Front Office / Lantai 2" />
                  </div>
                </div>
              </div>

              <div v-else-if="form.request_type === 'kebutuhan_perbaikan'" class="col-12 pt-2">
                <div class="row g-3 p-3 bg-light rounded-3 border">
                  <div class="col-md-6">
                    <label class="form-label small fw-semibold text-secondary">Keterangan Ukuran Sarana <span class="text-danger">*</span></label>
                    <input type="text" v-model="form.item_size" class="form-control bg-white border" placeholder="Contoh: Kabel 50m / Lampu 18 Watt / Pipa 1 inch" />
                    <div class="form-text small text-muted">Wajib dicantumkan untuk kebutuhan perbaikan fisik fasilitas.</div>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label small fw-semibold text-secondary">Lokasi Penempatan Sarana <span class="text-danger">*</span></label>
                    <input type="text" v-model="form.placement" class="form-control bg-white border" placeholder="Contoh: Bengkel Utama / Ruang Kasir" />
                  </div>
                </div>
              </div>

              <div v-else class="col-12 pt-2">
                <div class="row g-3 p-3 bg-light rounded-3 border">
                  <div class="col-md-6">
                    <label class="form-label small fw-semibold text-secondary">Untuk Unit / Departemen</label>
                    <input type="text" v-model="form.for_who" class="form-control bg-white border" placeholder="Contoh: Seluruh Cabang / Departemen GA" />
                  </div>
                  <div class="col-md-6">
                    <label class="form-label small fw-semibold text-secondary">Lokasi Penempatan Barang</label>
                    <input type="text" v-model="form.placement" class="form-control bg-white border" placeholder="Contoh: Gudang ATK / Dapur Kantor" />
                  </div>
                </div>
              </div>

              <!-- Keperluan Pengajuan -->
              <div class="col-12">
                <label class="form-label small fw-semibold text-secondary">Keperluan / Alasan Pengajuan <span class="text-danger">*</span></label>
                <textarea
                  v-model="form.purpose"
                  required
                  rows="2"
                  class="form-control bg-light border-0"
                  placeholder="Jelaskan tujuan dan urgensi pengajuan perlengkapan kantor ini..."
                ></textarea>
              </div>
            </div>
          </div>
        </div>

        <!-- Tabel Multi-Item Pengajuan -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
          <div class="card-header bg-white border-bottom-0 pt-4 px-4 pb-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
              <h5 class="fw-bold text-dark mb-1">Daftar Item Perlengkapan</h5>
              <p class="text-muted small mb-0">Rincian nama item barang, spesifikasi ukuran, kuantitas, dan estimasi harga.</p>
            </div>
            <button type="button" class="btn btn-sm btn-outline-primary px-3 rounded-pill" @click="addItemRow">
              + Tambah Baris Item
            </button>
          </div>
          <div class="card-body p-4">
            <div class="table-responsive border rounded-3">
              <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                  <tr class="small text-secondary">
                    <th style="width: 40px;" class="text-center">#</th>
                    <th style="min-width: 200px;">Nama Item <span class="text-danger">*</span></th>
                    <th style="min-width: 150px;">Keterangan Ukuran / Spek</th>
                    <th style="width: 80px;" class="text-center">Qty</th>
                    <th style="min-width: 150px;">Penempatan</th>
                    <th style="min-width: 150px;">Estimasi Harga Total (Rp)</th>
                    <th style="width: 50px;"></th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(it, itIdx) in form.items" :key="itIdx">
                    <td class="text-center text-muted small">{{ itIdx + 1 }}</td>
                    <td>
                      <input type="text" v-model="it.item_name" required class="form-control form-control-sm border-0 bg-light" placeholder="Nama item barang" />
                    </td>
                    <td>
                      <input type="text" v-model="it.item_size" class="form-control form-control-sm border-0 bg-light" placeholder="Ukuran / spek" />
                    </td>
                    <td>
                      <input type="number" v-model.number="it.quantity" min="1" class="form-control form-control-sm border-0 bg-light text-center" />
                    </td>
                    <td>
                      <input type="text" v-model="it.placement" class="form-control form-control-sm border-0 bg-light" placeholder="Lokasi penempatan" />
                    </td>
                    <td>
                      <input type="number" v-model.number="it.estimated_price" min="0" class="form-control form-control-sm border-0 bg-light text-end" placeholder="0" @input="recalculateTotal" />
                    </td>
                    <td class="text-center">
                      <button
                        v-if="form.items.length > 1"
                        type="button"
                        class="btn btn-sm text-danger p-0 border-0"
                        @click="removeItemRow(itIdx)"
                        title="Hapus baris"
                      >
                        ✕
                      </button>
                    </td>
                  </tr>
                  <tr class="table-light fw-bold">
                    <td colspan="5" class="text-center text-secondary small">Total Estimasi Harga Pengajuan</td>
                    <td class="text-end text-success">
                      Rp {{ Number(calculateFormTotal()).toLocaleString('id-ID') }}
                    </td>
                    <td></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </template>

      <!-- Bottom Submit Button Bar -->
      <div class="d-flex justify-content-end align-items-center gap-2 pt-2">
        <router-link to="/hrms/asset-requests" class="btn btn-light px-4 py-2 rounded-3 border">
          Batal
        </router-link>
        <button
          type="submit"
          class="btn btn-primary px-5 py-2 rounded-3 shadow-sm"
          :disabled="submitting"
        >
          {{ submitting ? 'Menyimpan...' : (isEdit ? 'Simpan Perubahan' : 'Kirim Pengajuan') }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../api'

const route = useRoute()
const router = useRouter()

const isEdit = computed(() => !!route.params.id)
const requestId = computed(() => route.params.id)

const loadingData = ref(false)
const submitting = ref(false)
const errorMessage = ref('')

const companies = ref([])
const branches = ref([])
const departments = ref([])
const assetTypes = ref([])
const employees = ref([])

const form = ref({
  id: null,
  request_type: 'pengadaan_baru',
  item_name: '',
  brand: '',
  model: '',
  specifications: '',
  item_size: '',
  placement: '',
  for_who: '',
  purpose: '',
  requester_id: null,
  for_employee_id: null,
  asset_type_id: null,
  quantity: 1,
  estimated_price: null,
  company_id: null,
  branch_id: null,
  department_id: null,
  submission_month: new Intl.DateTimeFormat('id-ID', { month: 'long', year: 'numeric' }).format(new Date()),
  items: [
    { item_name: '', item_size: '', quantity: 1, placement: '', estimated_price: 0 }
  ]
})

const isEquipmentForm = (type) => type === 'pengadaan_baru' || type === 'pergantian_alat'

const switchFormCategory = (category) => {
  if (category === 'equipment') {
    if (!isEquipmentForm(form.value.request_type)) {
      form.value.request_type = 'pengadaan_baru'
    }
  } else {
    if (isEquipmentForm(form.value.request_type)) {
      form.value.request_type = 'kebutuhan_rutin'
    }
  }
}

// Helper untuk mengurai response data array dari API Laravel (baik paginated resource maupun collection biasa)
const extractArray = (res) => {
  if (!res) return []
  const d = res.data !== undefined ? res.data : res
  if (Array.isArray(d)) return d
  if (d && Array.isArray(d.data)) return d.data
  if (d && d.data && Array.isArray(d.data.data)) return d.data.data
  return []
}

// Cascading Computed: Cabang sesuai Perusahaan yang dipilih
const filteredBranches = computed(() => {
  if (!form.value.company_id) {
    return branches.value
  }
  return branches.value.filter(b => b.company_id == form.value.company_id)
})

// Cascading Computed: Departemen sesuai Cabang / Perusahaan yang dipilih
const filteredDepartments = computed(() => {
  let list = departments.value
  if (form.value.branch_id) {
    list = list.filter(d => d.branch_id == form.value.branch_id)
  } else if (form.value.company_id) {
    const validBranchIds = branches.value
      .filter(b => b.company_id == form.value.company_id)
      .map(b => b.id)
    list = list.filter(d => validBranchIds.includes(d.branch_id) || d.company_id == form.value.company_id)
  }
  return list
})

// Cascading Computed: Karyawan sesuai Departemen / Cabang / Perusahaan yang dipilih
const filteredEmployees = computed(() => {
  let list = employees.value
  if (form.value.department_id) {
    list = list.filter(emp => {
      const primary = emp.primary_assignment || (emp.assignments && emp.assignments.find(a => a.is_primary)) || (emp.assignments && emp.assignments[0])
      return (emp.assignments || []).some(a => a.department_id == form.value.department_id) || (primary && primary.department_id == form.value.department_id)
    })
  } else if (form.value.branch_id) {
    list = list.filter(emp => {
      const primary = emp.primary_assignment || (emp.assignments && emp.assignments.find(a => a.is_primary)) || (emp.assignments && emp.assignments[0])
      return (emp.assignments || []).some(a => a.branch_id == form.value.branch_id) || (primary && primary.branch_id == form.value.branch_id)
    })
  } else if (form.value.company_id) {
    list = list.filter(emp => {
      const primary = emp.primary_assignment || (emp.assignments && emp.assignments.find(a => a.is_primary)) || (emp.assignments && emp.assignments[0])
      return (emp.assignments || []).some(a => a.company_id == form.value.company_id) || (primary && primary.company_id == form.value.company_id)
    })
  }
  return list
})

// Trigger saat memilih Perusahaan
const onCompanyChange = () => {
  if (form.value.branch_id) {
    const valid = filteredBranches.value.some(b => b.id == form.value.branch_id)
    if (!valid) {
      form.value.branch_id = null
      form.value.department_id = null
    }
  }
  if (form.value.department_id) {
    const valid = filteredDepartments.value.some(d => d.id == form.value.department_id)
    if (!valid) {
      form.value.department_id = null
    }
  }
  if (form.value.requester_id) {
    const valid = filteredEmployees.value.some(e => e.id == form.value.requester_id)
    if (!valid) {
      form.value.requester_id = null
    }
  }
}

// Trigger saat memilih Cabang
const onBranchChange = () => {
  if (form.value.branch_id) {
    const branch = branches.value.find(b => b.id == form.value.branch_id)
    if (branch && branch.company_id && form.value.company_id != branch.company_id) {
      form.value.company_id = branch.company_id
    }
  }
  if (form.value.department_id) {
    const valid = filteredDepartments.value.some(d => d.id == form.value.department_id)
    if (!valid) {
      form.value.department_id = null
    }
  }
  if (form.value.requester_id) {
    const valid = filteredEmployees.value.some(e => e.id == form.value.requester_id)
    if (!valid) {
      form.value.requester_id = null
    }
  }
}

// Trigger saat memilih Departemen
const onDepartmentChange = () => {
  if (form.value.department_id) {
    const dept = departments.value.find(d => d.id == form.value.department_id)
    if (dept && dept.branch_id && form.value.branch_id != dept.branch_id) {
      form.value.branch_id = dept.branch_id
      const branch = branches.value.find(b => b.id == dept.branch_id)
      if (branch && branch.company_id) {
        form.value.company_id = branch.company_id
      }
    }
  }
  if (form.value.requester_id) {
    const valid = filteredEmployees.value.some(e => e.id == form.value.requester_id)
    if (!valid) {
      form.value.requester_id = null
    }
  }
}

// Trigger saat memilih Karyawan Pemohon (Auto-fill Perusahaan, Cabang, Departemen)
const onRequesterChange = () => {
  if (!form.value.requester_id) return
  const emp = employees.value.find(e => e.id == form.value.requester_id)
  if (emp) {
    const assignment = emp.primary_assignment || (emp.assignments && emp.assignments.find(a => a.is_primary)) || (emp.assignments && emp.assignments[0])
    if (assignment) {
      if (assignment.company_id) form.value.company_id = assignment.company_id
      if (assignment.branch_id) form.value.branch_id = assignment.branch_id
      if (assignment.department_id) form.value.department_id = assignment.department_id
    }
  }
}

const addItemRow = () => {
  form.value.items.push({
    item_name: '',
    item_size: form.value.item_size || '',
    quantity: 1,
    placement: form.value.placement || '',
    estimated_price: 0,
  })
}

const removeItemRow = (idx) => {
  form.value.items.splice(idx, 1)
}

const calculateFormTotal = () => {
  let total = 0
  for (const it of form.value.items) {
    total += Number(it.estimated_price || 0)
  }
  return total || form.value.estimated_price || 0
}

const recalculateTotal = () => {
  form.value.estimated_price = calculateFormTotal()
}

const loadDropdowns = async () => {
  try {
    const [compRes, branchRes, deptRes, typesRes, empRes] = await Promise.all([
      api.get('/companies', { params: { per_page: 200 } }),
      api.get('/branches', { params: { per_page: 200 } }),
      api.get('/departments', { params: { per_page: 200 } }),
      api.get('/asset-types', { params: { per_page: 200 } }),
      api.get('/employees', { params: { per_page: 500 } }),
    ])
    companies.value = extractArray(compRes)
    branches.value = extractArray(branchRes)
    departments.value = extractArray(deptRes)
    assetTypes.value = extractArray(typesRes)
    employees.value = extractArray(empRes)
  } catch (e) {
    console.error('Gagal mengambil referensi dropdown', e)
  }
}

const fetchRequestDetail = async () => {
  if (!isEdit.value) return
  loadingData.value = true
  try {
    const { data } = await api.get(`/asset-requests/${requestId.value}`)
    const row = data.data || data
    if (row) {
      let initialItems = []
      if (row.items && row.items.length > 0) {
        initialItems = row.items.map(it => ({
          item_name: it.item_name,
          item_size: it.item_size || '',
          quantity: it.quantity || 1,
          placement: it.placement || '',
          estimated_price: it.estimated_price || 0,
        }))
      } else {
        initialItems = [{
          item_name: row.item_name || '',
          item_size: row.specifications || row.item_size || '',
          quantity: row.quantity || 1,
          placement: row.placement || row.for_who || '',
          estimated_price: row.estimated_price || 0,
        }]
      }

      form.value = {
        id: row.id,
        request_type: row.request_type || 'pengadaan_baru',
        item_name: row.item_name || '',
        brand: row.brand || '',
        model: row.model || '',
        specifications: row.specifications || '',
        item_size: row.item_size || '',
        placement: row.placement || row.for_who || '',
        for_who: row.for_who || '',
        purpose: row.purpose || '',
        requester_id: row.requester_id || null,
        for_employee_id: row.for_employee_id || null,
        asset_type_id: row.asset_type_id || null,
        quantity: row.quantity || 1,
        estimated_price: row.estimated_price || null,
        company_id: row.company_id || null,
        branch_id: row.branch_id || null,
        department_id: row.department_id || null,
        submission_month: row.submission_month || '',
        items: initialItems,
      }
    }
  } catch (e) {
    errorMessage.value = 'Gagal memuat detail pengajuan aset: ' + (e.response?.data?.message || e.message)
  } finally {
    loadingData.value = false
  }
}

const submitForm = async () => {
  errorMessage.value = ''

  if (isEquipmentForm(form.value.request_type)) {
    if (!form.value.item_name) {
      errorMessage.value = 'Jenis Peralatan Kerja wajib diisi'
      return
    }
    if (!form.value.purpose) {
      errorMessage.value = 'Alasan permohonan wajib diisi'
      return
    }
    form.value.items = [
      {
        item_name: form.value.item_name,
        item_size: form.value.specifications || '',
        quantity: form.value.quantity || 1,
        placement: '',
        estimated_price: form.value.estimated_price || 0,
      }
    ]
  } else {
    if (form.value.items.length > 0 && form.value.items[0].item_name) {
      form.value.item_name = form.value.items[0].item_name
      if (!form.value.item_size) {
        form.value.item_size = form.value.items[0].item_size
      }
      if (!form.value.placement) {
        form.value.placement = form.value.items[0].placement
      }
    }
    form.value.estimated_price = calculateFormTotal()
  }

  submitting.value = true
  try {
    if (isEdit.value) {
      await api.put(`/asset-requests/${form.value.id}`, form.value)
    } else {
      await api.post('/asset-requests', form.value)
    }
    router.push('/hrms/asset-requests')
  } catch (e) {
    errorMessage.value = 'Gagal menyimpan pengajuan aset: ' + (e.response?.data?.message || e.message)
  } finally {
    submitting.value = false
  }
}

onMounted(async () => {
  await loadDropdowns()
  if (isEdit.value) {
    await fetchRequestDetail()
  }
})
</script>

<style scoped>
.cursor-pointer {
  cursor: pointer;
}
.transition-all {
  transition: all 0.2s ease-in-out;
}
</style>
