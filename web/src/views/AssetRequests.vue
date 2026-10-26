<template>
  <div>
    <!-- Header (outside card, standard diagonal cut tab) -->
    <div class="d-flex align-items-stretch mb-0" style="min-height: 42px;">
      <div style="
        background: var(--bs-primary, #008779);
        color: #fff;
        padding: 10px 40px 10px 20px;
        clip-path: polygon(0 0, calc(100% - 24px) 0, 100% 100%, 0 100%);
        display: flex;
        align-items: center;
        min-width: 200px;
        max-width: 50%;
      ">
        <h4 class="mb-0 font-size-16 fw-semibold text-white text-nowrap" style="letter-spacing: 0.01em;">Pengajuan Aset & Inventaris</h4>
      </div>
      <div class="flex-grow-1 d-flex align-items-center justify-content-end gap-2 ps-3" style="padding-bottom: 10px;">
        <!-- Opsi Dropdown -->
        <div class="dropdown">
          <button
            class="btn btn-action-secondary px-3 py-2 waves-effect shadow-none"
            type="button"
            data-bs-toggle="dropdown"
            aria-expanded="false"
            style="border-radius:7px; font-size: 0.85rem;"
          >
            <i class="mdi mdi-tune-vertical-variant me-1"></i> Opsi
          </button>
          <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 py-2" style="border-radius:10px; min-width:190px;">
            <li>
              <router-link to="/hrms/assets" class="dropdown-item py-2 px-3">
                <i class="mdi mdi-archive-outline me-2 text-info"></i> Data Aset
              </router-link>
            </li>
            <li>
              <router-link to="/hrms/asset-repairs" class="dropdown-item py-2 px-3">
                <i class="mdi mdi-tools me-2 text-warning"></i> Perbaikan Alat
              </router-link>
            </li>
            <li>
              <router-link to="/hrms/asset-approval-settings" class="dropdown-item py-2 px-3">
                <i class="mdi mdi-cog-outline me-2 text-primary"></i> Konfigurasi Approval
              </router-link>
            </li>
            <li><hr class="dropdown-divider my-1"></li>
            <li>
              <a class="dropdown-item py-2 px-3" href="#" @click.prevent="exportData">
                <i class="mdi mdi-file-excel-outline me-2 text-success"></i> Export Excel
              </a>
            </li>
          </ul>
        </div>

        <!-- Tombol Tambah (Sesuai Tab Aktif) -->
        <router-link
          v-if="activeTab === 'requests'"
          to="/hrms/asset-requests/create"
          class="btn btn-action-add px-3 py-2 waves-effect waves-light shadow-sm text-decoration-none"
          style="border-radius:7px; font-size: 0.85rem;"
        >
          <i class="mdi mdi-plus-circle-outline font-size-15 me-1"></i> Tambah Pengajuan
        </router-link>
        <button
          v-if="activeTab === 'approvals'"
          type="button"
          class="btn btn-action-add px-3 py-2 waves-effect waves-light shadow-sm"
          style="border-radius:7px; font-size: 0.85rem;"
          @click="openCreateApprovalSettingModal"
        >
          <i class="mdi mdi-plus-circle-outline font-size-15 me-1"></i> Tambah Konfigurasi
        </button>
      </div>
    </div>

    <!-- Main Card -->
    <div class="card">
      <div class="card-body">
        <!-- Sub-Navigation Switcher -->
        <div class="d-flex gap-2 border-bottom pb-3 mb-4 align-items-center">
          <button
            type="button"
            class="btn btn-sm px-3 py-2 fw-semibold"
            :class="activeTab === 'requests' ? 'btn-primary shadow-sm' : 'btn-light text-muted'"
            style="border-radius: 8px;"
            @click="activeTab = 'requests'"
          >
            Daftar Pengajuan Aset
            <span class="badge ms-1" :class="activeTab === 'requests' ? 'bg-white text-primary' : 'bg-secondary text-white'">
              {{ totalItems || requests.length }}
            </span>
          </button>
          <button
            type="button"
            class="btn btn-sm px-3 py-2 fw-semibold"
            :class="activeTab === 'approvals' ? 'btn-primary shadow-sm' : 'btn-light text-muted'"
            style="border-radius: 8px;"
            @click="activeTab = 'approvals'"
          >
            Konfigurasi Approval Aset
            <span class="badge ms-1" :class="activeTab === 'approvals' ? 'bg-white text-primary' : 'bg-secondary text-white'">
              {{ approvalSettings.length }}
            </span>
          </button>
        </div>

        <!-- ==================== TAB 1: DAFTAR PENGAJUAN ASET ==================== -->
        <div v-if="activeTab === 'requests'">
          <!-- Filter & Search Controls -->
          <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
            <!-- Left: Tampilkan X data -->
            <div class="d-flex align-items-center text-muted">
              <span class="me-2 text-nowrap">Tampilkan</span>
              <select v-model="perPage" @change="fetchData(1)" class="form-select form-select-sm w-auto" style="border-radius: 8px; font-weight: 500;">
                <option :value="10">10</option>
                <option :value="25">25</option>
                <option :value="50">50</option>
                <option :value="100">100</option>
              </select>
              <span class="ms-2 text-nowrap">data</span>
            </div>

            <!-- Right: Filter dropdowns & search -->
            <div class="d-flex align-items-center flex-wrap gap-2">
              <!-- Tipe Pengajuan -->
              <div>
                <select v-model="filterType" @change="fetchData(1)" class="form-select form-select-sm border-0 bg-light text-secondary w-auto" style="border-radius: 8px; font-weight: 500; min-width: 170px; box-shadow: 0 2px 4px rgba(0,0,0,0.03);">
                  <option value="">Semua Tipe Pengajuan</option>
                  <optgroup label="Permohonan Peralatan Kerja">
                    <option value="pengadaan_baru">Pengadaan Baru Peralatan</option>
                    <option value="pergantian_alat">Pergantian Alat</option>
                  </optgroup>
                  <optgroup label="Perlengkapan Kantor / Rutin">
                    <option value="kebutuhan_karyawan_baru">Aset Karyawan Baru</option>
                    <option value="kebutuhan_rutin">Rutin (ATK & Kebersihan)</option>
                    <option value="kebutuhan_perbaikan">Perbaikan Sarana</option>
                  </optgroup>
                </select>
              </div>

              <!-- Status -->
              <div>
                <select v-model="filterStatus" @change="fetchData(1)" class="form-select form-select-sm border-0 bg-light text-secondary w-auto" style="border-radius: 8px; font-weight: 500; min-width: 140px; box-shadow: 0 2px 4px rgba(0,0,0,0.03);">
                  <option value="">Semua Status</option>
                  <option value="pending">Menunggu</option>
                  <option value="approved">Disetujui</option>
                  <option value="purchased">Sudah Dibeli</option>
                  <option value="rejected">Ditolak</option>
                </select>
              </div>

              <!-- Perusahaan -->
              <div>
                <select v-model="filterCompany" @change="fetchData(1)" class="form-select form-select-sm border-0 bg-light text-secondary w-auto" style="border-radius: 8px; font-weight: 500; min-width: 160px; box-shadow: 0 2px 4px rgba(0,0,0,0.03);">
                  <option value="">Semua Perusahaan</option>
                  <option v-for="c in companies" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
              </div>

              <div v-if="searchQuery || filterType || filterStatus || filterCompany">
                <button
                  type="button"
                  class="btn btn-outline-secondary btn-sm"
                  style="border-radius: 8px;"
                  @click="resetFilters"
                >
                  Reset
                </button>
              </div>

              <!-- Search -->
              <div class="position-relative" style="width: 220px;">
                <div class="input-group input-group-sm bg-light" style="border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.03); overflow: hidden;">
                  <span class="input-group-text bg-transparent border-0 text-muted ps-3 pe-2">
                    <i class="mdi mdi-magnify font-size-16"></i>
                  </span>
                  <input
                    type="search"
                    v-model="searchQuery"
                    @keyup.enter="fetchData(1)"
                    @search="e => { if (!searchQuery) fetchData(1) }"
                    class="form-control border-0 bg-transparent fw-medium ps-0"
                    style="box-shadow: none;"
                    placeholder="Pencarian cepat..."
                  />
                </div>
              </div>
            </div>
          </div>

          <!-- Tabel Data Pengajuan Aset -->
          <div class="table-responsive">
            <table class="table table-striped table-bordered table-hover mb-0">
              <thead class="custom-thead-gradient">
                <tr>
                  <th style="width: 50px;" class="text-center align-middle text-white border-0">No.</th>
                  <th class="text-white border-0 align-middle">Pemohon & Unit</th>
                  <th class="text-white border-0 align-middle">Tipe Pengajuan</th>
                  <th class="text-white border-0 align-middle">Nama Barang / Peralatan</th>
                  <th class="text-white border-0 align-middle">Keterangan & Alasan</th>
                  <th class="text-white border-0 align-middle">Jumlah & Estimasi</th>
                  <th class="text-center text-white border-0 align-middle">Status Approval</th>
                  <th class="text-center text-white border-0 align-middle" style="min-width: 200px;">Aksi & Dokumen</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="loading">
                  <td colspan="8" class="text-center py-4 text-muted">Memuat data permintaan aset...</td>
                </tr>
                <tr v-else-if="requests.length === 0">
                  <td colspan="8" class="text-center py-4 text-muted">Belum ada data pengajuan aset.</td>
                </tr>
                <tr v-for="(row, idx) in requests" :key="row.id">
                  <td class="text-center align-middle text-muted small">
                    {{ (currentPage - 1) * perPage + idx + 1 }}
                  </td>
                  <td>
                    <div class="fw-semibold text-dark">{{ row.requester?.name || '-' }}</div>
                    <div class="text-muted small">
                      {{ row.department?.name ? 'Dept: ' + row.department.name : (row.company?.name || '-') }}
                    </div>
                    <div v-if="row.submission_month" class="text-secondary small">
                      Bulan: {{ row.submission_month }}
                    </div>
                  </td>
                  <td>
                    <span v-if="row.request_type === 'pengadaan_baru'" class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                      Pengadaan Baru Alat
                    </span>
                    <span v-else-if="row.request_type === 'pergantian_alat'" class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1">
                      Pergantian Alat
                    </span>
                    <span v-else-if="row.request_type === 'kebutuhan_karyawan_baru'" class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                      Karyawan Baru
                    </span>
                    <span v-else-if="row.request_type === 'kebutuhan_perbaikan'" class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1">
                      Perbaikan Sarana
                    </span>
                    <span v-else class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1">
                      Rutin (ATK & Kebersihan)
                    </span>
                  </td>
                  <td>
                    <div class="fw-semibold text-dark">{{ row.item_name }}</div>
                    <div v-if="row.brand || row.model" class="text-muted small">
                      <span v-if="row.brand" class="me-1">Merek: <strong class="text-secondary">{{ row.brand }}</strong></span>
                      <span v-if="row.model">Tipe: <strong class="text-secondary">{{ row.model }}</strong></span>
                    </div>
                    <div v-if="row.item_size" class="text-muted small">
                      Ukuran: <span class="fw-medium text-dark">{{ row.item_size }}</span>
                    </div>
                    <div v-if="row.items && row.items.length > 1" class="text-primary small">
                      Total {{ row.items.length }} rincian item
                    </div>
                  </td>
                  <td>
                    <div class="text-dark small">
                      Penempatan: <span class="fw-medium">{{ row.placement || row.for_who || '-' }}</span>
                    </div>
                    <div class="text-muted small text-truncate" style="max-width: 200px;" :title="row.purpose">
                      {{ row.purpose }}
                    </div>
                  </td>
                  <td>
                    <div class="fw-medium text-dark">
                      {{ row.quantity || 1 }} unit
                    </div>
                    <div v-if="row.estimated_price" class="text-success small fw-semibold">
                      Rp {{ Number(row.estimated_price).toLocaleString('id-ID') }}
                    </div>
                  </td>
                  <td class="text-center align-middle">
                    <div class="d-flex flex-column gap-1 align-items-center">
                      <!-- Level 1: Mengetahui -->
                      <div class="d-flex align-items-center gap-1">
                        <small class="text-muted">Mengetahui:</small>
                        <span v-if="row.acknowledged_by || isStepApproved(row, 1)" class="badge bg-success-subtle text-success border border-success-subtle px-1">
                          OK
                        </span>
                        <span v-else-if="row.status === 'rejected'" class="badge bg-danger-subtle text-danger border border-danger-subtle px-1">
                          Ditolak
                        </span>
                        <span v-else class="badge bg-warning-subtle text-warning border border-warning-subtle px-1">
                          Menunggu
                        </span>
                      </div>
                      <!-- Level 2: Disetujui -->
                      <div class="d-flex align-items-center gap-1">
                        <small class="text-muted">Disetujui:</small>
                        <span v-if="row.approved_by || isStepApproved(row, 2)" class="badge bg-success-subtle text-success border border-success-subtle px-1">
                          Disetujui
                        </span>
                        <span v-else-if="row.status === 'rejected'" class="badge bg-danger-subtle text-danger border border-danger-subtle px-1">
                          Ditolak
                        </span>
                        <span v-else class="badge bg-warning-subtle text-warning border border-warning-subtle px-1">
                          Menunggu
                        </span>
                      </div>
                      <!-- Status Barang -->
                      <span v-if="row.status === 'purchased'" class="badge bg-success text-white px-2 py-0">
                        Sudah Jadi Aset
                      </span>
                    </div>
                  </td>
                  <td class="text-center align-middle">
                    <div class="d-flex flex-column align-items-center gap-1">
                      <!-- Tombol CETAK FORM PDF -->
                      <button
                        type="button"
                        class="btn btn-outline-secondary btn-sm w-100 py-1"
                        style="font-size: 0.8rem; border-radius: 6px;"
                        @click="printPdf(row)"
                        :title="isEquipmentForm(row.request_type) ? 'Cetak Formulir Permohonan Peralatan Kerja' : 'Cetak Formulir Pengajuan Perlengkapan Kantor'"
                      >
                        {{ isEquipmentForm(row.request_type) ? 'Cetak Form Permohonan Alat' : 'Cetak Form Perlengkapan' }}
                      </button>

                      <!-- Alur Approval Step: Jika belum di-approve level 1 -->
                      <div v-if="!row.acknowledged_by && row.status === 'pending'" class="d-flex gap-1 w-100 mt-1">
                        <button
                          type="button"
                          class="btn btn-outline-success btn-sm flex-fill py-1"
                          style="font-size: 0.75rem; border-radius: 6px;"
                          @click="submitApprovalStep(row, 1, 'approved')"
                        >
                          Mengetahui
                        </button>
                        <button
                          type="button"
                          class="btn btn-outline-danger btn-sm flex-fill py-1"
                          style="font-size: 0.75rem; border-radius: 6px;"
                          @click="submitApprovalStep(row, 1, 'rejected')"
                        >
                          Tolak
                        </button>
                      </div>

                      <!-- Alur Approval Step: Jika sudah diketahui tapi belum disetujui -->
                      <div v-else-if="row.acknowledged_by && !row.approved_by && row.status === 'pending'" class="d-flex gap-1 w-100 mt-1">
                        <button
                          type="button"
                          class="btn btn-outline-primary btn-sm flex-fill py-1"
                          style="font-size: 0.75rem; border-radius: 6px;"
                          @click="submitApprovalStep(row, 2, 'approved')"
                        >
                          Setujui Final
                        </button>
                        <button
                          type="button"
                          class="btn btn-outline-danger btn-sm flex-fill py-1"
                          style="font-size: 0.75rem; border-radius: 6px;"
                          @click="submitApprovalStep(row, 2, 'rejected')"
                        >
                          Tolak
                        </button>
                      </div>

                      <!-- Jika barang sudah disetujui tapi belum dibeli: sediakan tombol Beli & Jadikan Aset -->
                      <button
                        v-if="(row.status === 'approved' || (row.acknowledged_by && row.approved_by)) && row.status !== 'purchased'"
                        type="button"
                        class="btn btn-success btn-sm w-100 py-1 mt-1"
                        style="font-size: 0.78rem; border-radius: 6px;"
                        @click="openPurchaseModal(row)"
                      >
                        Beli & Jadikan Aset
                      </button>

                      <!-- Jika barang sudah dibeli -->
                      <div v-if="row.status === 'purchased'" class="w-100 text-center">
                        <small v-if="row.asset?.inventory_number" class="text-muted fw-monospace" style="font-size: 0.75rem;">
                          {{ row.asset.inventory_number }}
                        </small>
                      </div>

                      <!-- Tombol Ubah & Hapus -->
                      <div class="d-flex gap-1 w-100 mt-1 justify-content-center">
                        <router-link
                          :to="`/hrms/asset-requests/${row.id}/edit`"
                          class="btn-action-edit flex-fill text-center text-decoration-none"
                          title="Edit Pengajuan"
                        >
                          <i class="mdi mdi-pencil-outline"></i> Edit
                        </router-link>
                        <button
                          type="button"
                          class="btn-action-delete flex-fill"
                          title="Hapus Pengajuan"
                          @click="deleteRow(row)"
                        >
                          <i class="mdi mdi-trash-can-outline"></i> Hapus
                        </button>
                      </div>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div v-if="totalItems > 0" class="row align-items-center mt-3">
            <div class="col-sm-6">
              <div class="text-muted text-sm-start mt-2 mt-sm-0">
                Menampilkan {{ fromItem }} sampai {{ toItem }} dari {{ totalItems }} data (halaman {{ currentPage }} dari {{ lastPage }})
              </div>
            </div>
            <div class="col-sm-6">
              <div class="float-sm-end">
                <ul class="pagination pagination-rounded mb-0">
                  <li class="page-item" :class="{ disabled: currentPage <= 1 }">
                    <a class="page-link" href="#" @click.prevent="currentPage > 1 && fetchData(currentPage - 1)">‹</a>
                  </li>
                  <li v-for="p in visiblePages" :key="p" class="page-item" :class="{ active: currentPage === p }">
                    <a class="page-link" href="#" @click.prevent="fetchData(p)">{{ p }}</a>
                  </li>
                  <li class="page-item" :class="{ disabled: currentPage >= lastPage }">
                    <a class="page-link" href="#" @click.prevent="currentPage < lastPage && fetchData(currentPage + 1)">›</a>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>

        <!-- ==================== TAB 2: KONFIGURASI APPROVAL ASET ==================== -->
        <div v-if="activeTab === 'approvals'">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
              <h6 class="mb-0 fw-bold text-dark font-size-15">Daftar Konfigurasi Approval Pengajuan Aset</h6>
              <p class="text-muted small mb-0">Atur pejabat yang Mengetahui (Level 1) dan yang Menyetujui (Level 2) untuk pengadaan aset.</p>
            </div>
            <button
              type="button"
              class="btn btn-action-add btn-sm px-3 waves-effect waves-light shadow-sm"
              style="border-radius: 7px;"
              @click="openCreateApprovalSettingModal"
            >
              <i class="mdi mdi-plus-circle-outline font-size-14 me-1"></i> Tambah Konfigurasi
            </button>
          </div>

          <div class="table-responsive">
            <table class="table table-striped table-bordered table-hover mb-0">
              <thead class="custom-thead-gradient">
                <tr>
                  <th style="width: 50px;" class="text-center align-middle text-white border-0">No.</th>
                  <th class="text-white border-0 align-middle">Nama Aturan</th>
                  <th class="text-white border-0 align-middle">Lingkup (Perusahaan / Cabang / Dept)</th>
                  <th class="text-white border-0 align-middle">Tipe Pengajuan</th>
                  <th class="text-white border-0 align-middle">Tahap 1: Mengetahui</th>
                  <th class="text-white border-0 align-middle">Tahap 2: Disetujui</th>
                  <th class="text-center text-white border-0 align-middle">Status</th>
                  <th class="text-center text-white border-0 align-middle" style="width: 140px;">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="loadingApprovalSettings">
                  <td colspan="8" class="text-center py-4 text-muted">Memuat konfigurasi approval aset...</td>
                </tr>
                <tr v-else-if="approvalSettings.length === 0">
                  <td colspan="8" class="text-center py-4 text-muted">
                    Belum ada konfigurasi approval aset khusus. Sistem otomatis menggunakan Manager Departemen (Mengetahui) dan Direktur/Pimpinan (Disetujui).
                  </td>
                </tr>
                <tr v-for="(setting, sIdx) in approvalSettings" :key="setting.id">
                  <td class="text-center align-middle text-muted small">{{ sIdx + 1 }}</td>
                  <td>
                    <div class="fw-semibold text-dark">{{ setting.name }}</div>
                    <small class="text-muted">{{ setting.notes || '-' }}</small>
                  </td>
                  <td>
                    <div class="text-dark small">
                      {{ setting.company?.name || 'Semua Perusahaan' }}
                    </div>
                    <div class="text-muted small">
                      Cabang: {{ setting.branch?.name || 'Semua Cabang' }}
                    </div>
                    <div v-if="setting.department" class="text-secondary small">
                      Dept: {{ setting.department.name }}
                    </div>
                  </td>
                  <td>
                    <span v-if="setting.request_type === 'kebutuhan_karyawan_baru'" class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                      Karyawan Baru
                    </span>
                    <span v-else-if="setting.request_type === 'kebutuhan_perbaikan'" class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1">
                      Perbaikan Sarana
                    </span>
                    <span v-else-if="setting.request_type === 'kebutuhan_rutin'" class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1">
                      Rutin ATK & Kebersihan
                    </span>
                    <span v-else class="badge bg-light text-dark border px-2 py-1">
                      Semua Tipe
                    </span>
                  </td>
                  <td>
                    <div class="fw-medium text-dark small">
                      {{ setting.mengetahui_type === 'department_manager' ? 'Manager Departemen' : (setting.mengetahuiEmployee?.name || 'Karyawan Tertentu') }}
                    </div>
                    <small class="text-muted">Tingkat 1 (Mengetahui)</small>
                  </td>
                  <td>
                    <div class="fw-medium text-dark small">
                      {{ setting.disetujui_type === 'director' ? 'Direktur / Pimpinan' : (setting.disetujui_type === 'finance_manager' ? 'Finance Manager' : (setting.disetujuiEmployee?.name || 'Approver Tertentu')) }}
                    </div>
                    <small class="text-muted">Tingkat 2 (Disetujui)</small>
                  </td>
                  <td class="text-center align-middle">
                    <span v-if="setting.is_active" class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                      Aktif
                    </span>
                    <span v-else class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1">
                      Nonaktif
                    </span>
                  </td>
                  <td class="text-center align-middle">
                    <div class="d-flex justify-content-center gap-1">
                      <button
                        type="button"
                        class="btn-action-edit"
                        title="Edit Konfigurasi"
                        @click="openEditApprovalSettingModal(setting)"
                      >
                        <i class="mdi mdi-pencil-outline"></i> Edit
                      </button>
                      <button
                        type="button"
                        class="btn-action-delete"
                        title="Hapus Konfigurasi"
                        @click="deleteApprovalSetting(setting)"
                      >
                        <i class="mdi mdi-trash-can-outline"></i> Hapus
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>



    <!-- ==================== MODAL FORM KONFIGURASI APPROVAL ASET ==================== -->
    <div v-if="showApprovalSettingModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5); z-index: 1050;">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 rounded-4 shadow-lg">
          <div class="modal-header border-0 pt-4 px-4 pb-2">
            <h5 class="modal-title fw-bold text-dark">
              {{ approvalSettingForm.id ? 'Ubah Konfigurasi Approval Aset' : 'Tambah Konfigurasi Approval Aset' }}
            </h5>
            <button type="button" class="btn-close shadow-none" @click="showApprovalSettingModal = false"></button>
          </div>
          <form @submit.prevent="saveApprovalSetting">
            <div class="modal-body px-4 py-3">
              <div class="row g-3">
                <div class="col-12">
                  <label class="form-label small fw-semibold text-secondary">Nama Konfigurasi <span class="text-danger">*</span></label>
                  <input type="text" v-model="approvalSettingForm.name" required class="form-control bg-light border-0" placeholder="Contoh: Approval Pengadaan Cabang Depok" />
                </div>

                <div class="col-md-4">
                  <label class="form-label small fw-semibold text-secondary">Perusahaan</label>
                  <select v-model="approvalSettingForm.company_id" class="form-select bg-light border-0">
                    <option :value="null">-- Berlaku Semua Perusahaan --</option>
                    <option v-for="c in companies" :key="c.id" :value="c.id">{{ c.name }}</option>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-semibold text-secondary">Cabang</label>
                  <select v-model="approvalSettingForm.branch_id" class="form-select bg-light border-0">
                    <option :value="null">-- Berlaku Semua Cabang --</option>
                    <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-semibold text-secondary">Departemen</label>
                  <select v-model="approvalSettingForm.department_id" class="form-select bg-light border-0">
                    <option :value="null">-- Berlaku Semua Departemen --</option>
                    <option v-for="d in departments" :key="d.id" :value="d.id">{{ d.name }}</option>
                  </select>
                </div>

                <div class="col-md-6">
                  <label class="form-label small fw-semibold text-secondary">Tipe Pengajuan</label>
                  <select v-model="approvalSettingForm.request_type" class="form-select bg-light border-0">
                    <option :value="null">Semua Tipe Pengajuan</option>
                    <option value="kebutuhan_karyawan_baru">Kebutuhan Aset Karyawan Baru</option>
                    <option value="kebutuhan_rutin">Kebutuhan Rutin (ATK & Kebersihan)</option>
                    <option value="kebutuhan_perbaikan">Perbaikan Lampu & Kabel</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-semibold text-secondary">Status Aktif</label>
                  <select v-model="approvalSettingForm.is_active" class="form-select bg-light border-0">
                    <option :value="true">Aktif</option>
                    <option :value="false">Nonaktif</option>
                  </select>
                </div>

                <!-- TINGKAT 1: MENGETAHUI -->
                <div class="col-12 p-3 bg-light rounded-3 border">
                  <label class="form-label small fw-bold text-dark mb-2">Tingkat 1: Mengetahui (Reviewer)</label>
                  <div class="row g-2">
                    <div class="col-md-6">
                      <select v-model="approvalSettingForm.mengetahui_type" class="form-select border-0">
                        <option value="department_manager">Manager Departemen Terkait</option>
                        <option value="specific_employee">Pilih Pegawai Tertentu</option>
                      </select>
                    </div>
                    <div class="col-md-6" v-if="approvalSettingForm.mengetahui_type === 'specific_employee'">
                      <select v-model="approvalSettingForm.mengetahui_employee_id" class="form-select border-0">
                        <option :value="null">-- Pilih Pegawai --</option>
                        <option v-for="e in employees" :key="e.id" :value="e.id">{{ e.name }}</option>
                      </select>
                    </div>
                  </div>
                </div>

                <!-- TINGKAT 2: DISETUJUI -->
                <div class="col-12 p-3 bg-light rounded-3 border">
                  <label class="form-label small fw-bold text-dark mb-2">Tingkat 2: Disetujui (Final Approver)</label>
                  <div class="row g-2">
                    <div class="col-md-6">
                      <select v-model="approvalSettingForm.disetujui_type" class="form-select border-0">
                        <option value="director">Direktur / Pimpinan</option>
                        <option value="finance_manager">Finance Manager</option>
                        <option value="ga_manager">General Affair (GA) Manager</option>
                        <option value="specific_employee">Pilih Pegawai Tertentu</option>
                      </select>
                    </div>
                    <div class="col-md-6" v-if="approvalSettingForm.disetujui_type === 'specific_employee'">
                      <select v-model="approvalSettingForm.disetujui_employee_id" class="form-select border-0">
                        <option :value="null">-- Pilih Pegawai --</option>
                        <option v-for="e in employees" :key="e.id" :value="e.id">{{ e.name }}</option>
                      </select>
                    </div>
                  </div>
                </div>

                <div class="col-12">
                  <label class="form-label small fw-semibold text-secondary">Catatan / Keterangan</label>
                  <textarea v-model="approvalSettingForm.notes" rows="2" class="form-control bg-light border-0" placeholder="Keterangan alur persetujuan aset ini..."></textarea>
                </div>
              </div>
            </div>
            <div class="modal-footer border-0 px-4 pb-4 pt-2">
              <button type="button" class="btn btn-action-secondary me-2 waves-effect px-4 py-2" style="border-radius: 7px;" @click="showApprovalSettingModal = false">Batal</button>
              <button type="submit" class="btn btn-action-add waves-effect waves-light px-4 py-2 shadow-sm" style="border-radius: 7px;" :disabled="submittingApprovalSetting">
                <i v-if="submittingApprovalSetting" class="mdi mdi-spin mdi-loading me-1"></i>
                {{ submittingApprovalSetting ? 'Menyimpan...' : 'Simpan Konfigurasi' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- ==================== MODAL KONFIRMASI JADI ASET ==================== -->
    <div v-if="showPurchaseModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5); z-index: 1060;">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg">
          <div class="modal-header border-0 pt-4 px-4 pb-2">
            <h5 class="modal-title fw-bold text-success">
              Tandai Barang Sudah Dibeli & Daftarkan Aset
            </h5>
            <button type="button" class="btn-close shadow-none" @click="showPurchaseModal = false"></button>
          </div>
          <form @submit.prevent="submitPurchase">
            <div class="modal-body px-4 py-3">
              <p class="text-muted small mb-3">
                Barang yang telah dibeli akan otomatis dibuatkan nomor inventaris dan terdaftar pada Data Aset perusahaan.
              </p>

              <div class="p-3 bg-light rounded-3 mb-3 small">
                <div class="row g-2">
                  <div class="col-4 text-secondary">Barang:</div>
                  <div class="col-8 fw-semibold text-dark">{{ activeRow?.item_name }}</div>
                  <div class="col-4 text-secondary">Penempatan:</div>
                  <div class="col-8 text-dark">{{ activeRow?.placement || activeRow?.for_who }}</div>
                  <div class="col-4 text-secondary">Keperluan:</div>
                  <div class="col-8 text-dark">{{ activeRow?.purpose }}</div>
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label small fw-semibold text-secondary">Tanggal Pembelian <span class="text-danger">*</span></label>
                <input type="date" v-model="purchaseForm.purchase_date" required class="form-control bg-light border-0" />
              </div>

              <div class="mb-3">
                <label class="form-label small fw-semibold text-secondary">Nomor Seri / SN (Opsional)</label>
                <input type="text" v-model="purchaseForm.serial_number" class="form-control bg-light border-0" placeholder="Nomor seri pada fisik barang (jika ada)" />
              </div>

              <div class="mb-3">
                <label class="form-label small fw-semibold text-secondary">Karyawan Pemegang Aset</label>
                <select v-model="purchaseForm.employee_id" class="form-select bg-light border-0">
                  <option :value="null">-- Simpan sebagai Aset Tersedia (Di Gudang / Kantor) --</option>
                  <option v-for="emp in employees" :key="emp.id" :value="emp.id">{{ emp.name }}</option>
                </select>
                <div class="form-text small text-muted">Jika dipilih, status aset otomatis 'Dipinjamkan' ke karyawan tersebut.</div>
              </div>

              <div class="mb-3">
                <label class="form-label small fw-semibold text-secondary">Catatan Pembelian</label>
                <input type="text" v-model="purchaseForm.notes" class="form-control bg-light border-0" placeholder="Contoh: Dibeli di Toko X garansi 1 tahun" />
              </div>
            </div>
            <div class="modal-footer border-0 px-4 pb-4 pt-2">
              <button type="button" class="btn btn-action-secondary me-2 waves-effect px-4 py-2" style="border-radius: 7px;" @click="showPurchaseModal = false">Batal</button>
              <button type="submit" class="btn btn-action-success waves-effect waves-light px-4 py-2 shadow-sm" style="border-radius: 7px;" :disabled="purchasing">
                <i v-if="purchasing" class="mdi mdi-spin mdi-loading me-1"></i>
                {{ purchasing ? 'Memproses...' : 'Konfirmasi Jadi Aset' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { toast } from 'vue3-toastify'
import api from '../api'

const activeTab = ref('requests')

// Data Tab 1
const requests = ref([])
const loading = ref(false)
const searchQuery = ref('')
const filterType = ref('')
const filterStatus = ref('')
const filterCompany = ref('')

const perPage = ref(10)
const currentPage = ref(1)
const totalItems = ref(0)
const lastPage = ref(1)
const fromItem = ref(0)
const toItem = ref(0)

const visiblePages = computed(() => {
  const pages = []
  const max = lastPage.value || 1
  const curr = currentPage.value || 1
  let start = Math.max(1, curr - 2)
  let end = Math.min(max, curr + 2)
  for (let i = start; i <= end; i++) {
    pages.push(i)
  }
  return pages
})

const companies = ref([])
const branches = ref([])
const departments = ref([])
const assetTypes = ref([])
const employees = ref([])

// Data Tab 2 (Approval Settings)
const approvalSettings = ref([])
const loadingApprovalSettings = ref(false)
const showApprovalSettingModal = ref(false)
const submittingApprovalSetting = ref(false)
const approvalSettingForm = ref({
  id: null,
  name: '',
  company_id: null,
  branch_id: null,
  department_id: null,
  request_type: null,
  mengetahui_type: 'department_manager',
  mengetahui_employee_id: null,
  disetujui_type: 'director',
  disetujui_employee_id: null,
  is_active: true,
  notes: '',
})

// Helpers Kategori Formulir
const isEquipmentForm = (type) => type === 'pengadaan_baru' || type === 'pergantian_alat'


// Modal Pembelian / Jadikan Aset
const showPurchaseModal = ref(false)
const purchasing = ref(false)
const activeRow = ref(null)
const purchaseForm = ref({
  purchase_date: new Date().toISOString().substring(0, 10),
  serial_number: '',
  employee_id: null,
  notes: '',
})

const isStepApproved = (row, stepLevel) => {
  if (!row.approvals) return false
  const app = row.approvals.find(a => a.level === stepLevel)
  return app && app.status === 'approved'
}

const fetchData = async (page = 1) => {
  loading.value = true
  if (typeof page === 'number') {
    currentPage.value = page
  }
  try {
    const { data } = await api.get('/asset-requests', {
      params: {
        page: currentPage.value,
        per_page: perPage.value,
        search: searchQuery.value || undefined,
        request_type: filterType.value || undefined,
        status: filterStatus.value || undefined,
        company_id: filterCompany.value || undefined,
      }
    })
    if (data && Array.isArray(data.data)) {
      requests.value = data.data
      totalItems.value = data.total !== undefined ? data.total : data.data.length
      currentPage.value = data.current_page || currentPage.value
      lastPage.value = data.last_page || 1
      fromItem.value = data.from !== undefined ? data.from : ((currentPage.value - 1) * perPage.value + (requests.value.length ? 1 : 0))
      toItem.value = data.to !== undefined ? data.to : (fromItem.value + requests.value.length - 1)
    } else if (Array.isArray(data)) {
      requests.value = data
      totalItems.value = data.length
      lastPage.value = 1
      fromItem.value = requests.value.length ? 1 : 0
      toItem.value = requests.value.length
    } else {
      requests.value = []
      totalItems.value = 0
    }
  } catch (e) {
    console.error('Gagal mengambil data permintaan aset', e)
  } finally {
    loading.value = false
  }
}

const fetchApprovalSettings = async () => {
  loadingApprovalSettings.value = true
  try {
    const { data } = await api.get('/asset-approval-settings')
    approvalSettings.value = data.data || []
  } catch (e) {
    console.error('Gagal mengambil konfigurasi approval aset', e)
  } finally {
    loadingApprovalSettings.value = false
  }
}

const extractArray = (res) => {
  if (!res) return []
  const d = res.data !== undefined ? res.data : res
  if (Array.isArray(d)) return d
  if (d && Array.isArray(d.data)) return d.data
  if (d && d.data && Array.isArray(d.data.data)) return d.data.data
  return []
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

const resetFilters = () => {
  searchQuery.value = ''
  filterType.value = ''
  filterStatus.value = ''
  filterCompany.value = ''
  fetchData(1)
}



// Approval step handler
const submitApprovalStep = async (row, level, status) => {
  const levelTitle = level === 1 ? 'Mengetahui' : 'Disetujui'
  const actionText = status === 'approved' ? 'menyetujui' : 'menolak'
  if (!confirm(`Konfirmasi untuk ${actionText} tahap "${levelTitle}" pada pengajuan ini?`)) return

  try {
    await api.post(`/asset-requests/${row.id}/approve-step`, {
      level,
      status,
    })
    fetchData()
  } catch (e) {
    alert('Gagal memproses persetujuan: ' + (e.response?.data?.message || e.message))
  }
}

// Print PDF Handler
const printPdf = (row) => {
  const token = localStorage.getItem('token') || ''
  const baseUrl = api.defaults.baseURL || '/api/v1'
  const url = `${baseUrl}/asset-requests/${row.id}/pdf`
  window.open(url, '_blank')
}

// Purchase Handler
const openPurchaseModal = (row) => {
  activeRow.value = row
  purchaseForm.value = {
    purchase_date: new Date().toISOString().substring(0, 10),
    serial_number: '',
    employee_id: row.for_employee_id || row.requester_id || null,
    notes: 'Pengadaan dari pengajuan: ' + row.purpose,
  }
  showPurchaseModal.value = true
}

const submitPurchase = async () => {
  if (!activeRow.value) return
  purchasing.value = true
  try {
    await api.post(`/asset-requests/${activeRow.value.id}/purchase`, purchaseForm.value)
    showPurchaseModal.value = false
    activeRow.value = null
    fetchData()
    alert('Barang berhasil ditandai sudah dibeli dan resmi terdaftar di Data Aset!')
  } catch (e) {
    alert('Gagal memproses pembelian aset: ' + (e.response?.data?.message || e.message))
  } finally {
    purchasing.value = false
  }
}

const deleteRow = async (row) => {
  if (!confirm(`Hapus pengajuan aset "${row.item_name}"?`)) return
  try {
    await api.delete(`/asset-requests/${row.id}`)
    fetchData()
  } catch (e) {
    alert('Gagal menghapus pengajuan: ' + (e.response?.data?.message || e.message))
  }
}

// Approval Settings CRUD
const openCreateApprovalSettingModal = () => {
  approvalSettingForm.value = {
    id: null,
    name: '',
    company_id: null,
    branch_id: null,
    department_id: null,
    request_type: null,
    mengetahui_type: 'department_manager',
    mengetahui_employee_id: null,
    disetujui_type: 'director',
    disetujui_employee_id: null,
    is_active: true,
    notes: '',
  }
  showApprovalSettingModal.value = true
}

const openEditApprovalSettingModal = (setting) => {
  approvalSettingForm.value = {
    id: setting.id,
    name: setting.name,
    company_id: setting.company_id,
    branch_id: setting.branch_id,
    department_id: setting.department_id,
    request_type: setting.request_type,
    mengetahui_type: setting.mengetahui_type,
    mengetahui_employee_id: setting.mengetahui_employee_id,
    disetujui_type: setting.disetujui_type,
    disetujui_employee_id: setting.disetujui_employee_id,
    is_active: Boolean(setting.is_active),
    notes: setting.notes || '',
  }
  showApprovalSettingModal.value = true
}

const saveApprovalSetting = async () => {
  submittingApprovalSetting.value = true
  try {
    if (approvalSettingForm.value.id) {
      await api.put(`/asset-approval-settings/${approvalSettingForm.value.id}`, approvalSettingForm.value)
    } else {
      await api.post('/asset-approval-settings', approvalSettingForm.value)
    }
    showApprovalSettingModal.value = false
    fetchApprovalSettings()
  } catch (e) {
    alert('Gagal menyimpan konfigurasi approval: ' + (e.response?.data?.message || e.message))
  } finally {
    submittingApprovalSetting.value = false
  }
}

const deleteApprovalSetting = async (setting) => {
  if (!confirm(`Hapus konfigurasi approval "${setting.name}"?`)) return
  try {
    await api.delete(`/asset-approval-settings/${setting.id}`)
    fetchApprovalSettings()
  } catch (e) {
    alert('Gagal menghapus konfigurasi approval: ' + (e.response?.data?.message || e.message))
  }
}

const exporting = ref(false)
const exportData = async () => {
  if (exporting.value) return
  exporting.value = true
  try {
    toast.info('Menyiapkan file Excel...')
    const params = {
      search: searchQuery.value,
      request_type: filterType.value,
      status: filterStatus.value,
      company_id: filterCompany.value,
      export: 1
    }
    const response = await api.get('/asset-requests', { params, responseType: 'blob' })
    const filename = `Pengajuan_Aset_${new Date().toISOString().slice(0, 10)}.xlsx`
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', filename)
    document.body.appendChild(link)
    link.click()
    link.parentNode.removeChild(link)
    window.URL.revokeObjectURL(url)
    toast.success('Download Excel berhasil')
  } catch (e) {
    console.error('Export error', e)
    toast.error('Gagal mengekspor data Excel')
  } finally {
    exporting.value = false
  }
}

onMounted(() => {
  fetchData()
  fetchApprovalSettings()
  loadDropdowns()
})
</script>

<style scoped>
.custom-thead-gradient th {
  background: var(--bs-primary, #124d32) !important;
  color: white !important;
  border-bottom: 0 !important;
}

.modal.fade.show {
  animation: fadeIn 0.2s ease-out;
}

.modal-content {
  animation: slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes slideUp {
  from { opacity: 0; transform: translateY(20px) scale(0.98); }
  to { opacity: 1; transform: translateY(0) scale(1); }
}
</style>
