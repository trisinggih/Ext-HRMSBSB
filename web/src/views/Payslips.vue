<template>
  <div>
    <!-- Banner Informasi Periode (Theme Gradient Hijau, Minimalis Tanpa Banyak Icon) -->
    <div v-if="selectedPeriodInfo" class="period-banner-card mb-4 shadow-sm rounded-4 overflow-hidden border border-0">
      <!-- Header Bar Gradient Hijau -->
      <div class="period-banner-header p-4 text-white">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
          <div>
            <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
              <span class="badge bg-white text-teal px-2 py-1 fs-7 fw-bold shadow-xs">
                PERIODE PAYROLL
              </span>
              <span v-if="selectedPeriodInfo.code" class="badge bg-white bg-opacity-20 text-white px-2 py-1 fs-7 fw-normal">
                {{ selectedPeriodInfo.code }}
              </span>
              <span class="badge px-3 py-1 fs-7 rounded-pill fw-semibold" :class="statusBadgeClass(selectedPeriodInfo.status)">
                {{ selectedPeriodInfo.status?.toUpperCase() }}
              </span>
            </div>
            <h4 class="fw-bold text-white mb-1">{{ selectedPeriodInfo.name }}</h4>
            <p class="text-white-80 mb-0 small" v-if="selectedPeriodInfo.company?.name">
              {{ selectedPeriodInfo.company.name }}
            </p>
          </div>
          <div class="d-flex align-items-center gap-2">
            <button @click="openAttendanceSummaryModal" class="btn btn-light btn-sm text-teal fw-bold px-3 shadow-xs rounded-3">
              <i class="mdi mdi-calendar-check me-1"></i> Informasi Absensi
            </button>
            <button @click="exportExcelReport" class="btn btn-light btn-sm text-success fw-bold px-3 shadow-xs rounded-3" :disabled="exportingExcel">
              <i class="mdi mdi-file-excel me-1"></i> {{ exportingExcel ? 'Mengekspor...' : 'Export Excel' }}
            </button>
            <button v-if="selectedPeriodInfo.status !== 'closed'" @click="cancelCurrentPeriod" class="btn btn-danger btn-sm px-3 shadow-sm rounded-3">
              <i class="mdi mdi-close-circle me-1"></i> Batalkan / Hapus Periode
            </button>
          </div>
        </div>
      </div>

      <!-- Stat Cards Row (Minimalis Key-Value Cards) -->
      <div class="bg-white p-3 border-top">
        <div class="row g-3">
          <!-- Stat 1: Rentang Tanggal -->
          <div class="col-6 col-md-3">
            <div class="p-3 rounded-3 bg-light border-0 h-100">
              <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.5px;">Rentang Tanggal</div>
              <div class="fw-bold text-dark fs-7 mt-1">
                {{ formatDate(selectedPeriodInfo.start_date) }} – {{ formatDate(selectedPeriodInfo.end_date) }}
              </div>
            </div>
          </div>

          <!-- Stat 2: Tipe Siklus -->
          <div class="col-6 col-md-3">
            <div class="p-3 rounded-3 bg-light border-0 h-100">
              <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.5px;">Tipe Siklus</div>
              <div class="fw-bold text-dark fs-7 mt-1">
                {{ formatCycleLabel(selectedPeriodInfo.cycle_type) }}
              </div>
            </div>
          </div>

          <!-- Stat 3: Total Slip Gaji -->
          <div class="col-6 col-md-3">
            <div class="p-3 rounded-3 bg-light border-0 h-100">
              <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.5px;">Total Slip Gaji</div>
              <div class="fw-bold text-dark fs-6 mt-1">
                {{ reportData.length }} <span class="fs-7 fw-normal text-muted">Karyawan</span>
              </div>
            </div>
          </div>

          <!-- Stat 4: Total Gaji Bersih (THP) -->
          <div class="col-6 col-md-3">
            <div class="p-3 rounded-3 bg-light border-0 h-100">
              <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.5px;">Total Gaji Bersih</div>
              <div class="fw-bold text-teal fs-6 mt-1">
                {{ money(totalPeriodNetSalary) }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <CrudPage
      ref="crudPageRef"
      :title="pageTitle"
      singular="Slip Gaji"
      endpoint="/payroll/runs"
      :columns="columns"
      :creatable="false"
      :filters="filters"
      :base-filters="baseFilters"
      :hide-header="!!selectedPeriodInfo"
      @loaded="r => reportData = r"
    >
      <template #header-actions>
        <button class="btn btn-outline-success btn-sm ms-2" @click="exportExcelReport" :disabled="exportingExcel">
          <i class="mdi mdi-file-excel"></i> Export Excel
        </button>
        <button class="btn btn-outline-secondary btn-sm ms-2" @click="printReport">
          <i class="mdi mdi-printer"></i> Cetak Laporan
        </button>
      </template>
      <template #actions="{ row }">
        <button @click="viewDetail(row.id)" class="btn-action-detail" title="Detail Slip Gaji">
          <i class="mdi mdi-eye-outline"></i> Detail
        </button>
        <button @click="printDirect(row.id)" class="btn-action-secondary" title="Cetak PDF">
          <i class="mdi mdi-file-pdf-box text-danger"></i> PDF
        </button>
        <button @click="sendWa(row)" class="btn-action-success" title="Kirim via WA">
          <i class="mdi mdi-whatsapp"></i> WA
        </button>
      </template>
    </CrudPage>

    <!-- =========================================
         MODAL SLIP GAJI DETAIL
    ========================================== -->
    <div v-if="detail" class="modal fade show" tabindex="-1" style="display:block; background:rgba(0,0,0,0.65);">
      <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">

          <!-- Header Modal (tidak ikut print) -->
          <div class="modal-header bg-dark text-white no-print">
            <h5 class="modal-title mb-0 fw-semibold">
              <i class="mdi mdi-receipt me-2"></i>Slip Gaji — {{ detail.employee?.name }}
            </h5>
            <div class="d-flex gap-2 ms-auto">
              <button @click="downloadPdf(detail.id, 'Slip_Gaji_' + (detail.employee?.name || 'Karyawan') + '.pdf')" class="btn btn-sm btn-danger">
                <i class="mdi mdi-file-pdf-box me-1"></i>Cetak PDF
              </button>
              <button type="button" class="btn-close btn-close-white" @click="detail = null"></button>
            </div>
          </div>

          <!-- ======= AREA CETAK ======= -->
          <div class="modal-body p-0" id="printable-payslip">
            <div class="slip-root">

              <!-- ====== HEADER PERUSAHAAN (FLAT COLOR) ====== -->
              <div class="slip-company-header">
                <div class="d-flex align-items-center justify-content-between gap-3">
                  <div class="company-left d-flex align-items-center gap-3">
                    <img v-if="effectiveLogoUrl"
                      :src="effectiveLogoUrl"
                      class="company-logo" alt="Logo">
                    <div class="company-text-group">
                      <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                        <span class="company-name-big">{{ companyInfo?.name || 'PERUSAHAAN' }}</span>
                        <span v-if="branchName" class="company-branch-badge">
                          <i class="mdi mdi-store-marker-outline me-1"></i>{{ branchName }}
                        </span>
                      </div>
                      <div class="company-address" v-if="displayAddress">{{ displayAddress }}</div>
                      <div class="company-contact" v-if="displayPhone || displayEmail">
                        <span v-if="displayPhone"><i class="mdi mdi-phone me-1"></i>Telp: {{ displayPhone }}</span>
                        <span v-if="displayPhone && displayEmail" class="mx-2 text-white-50">|</span>
                        <span v-if="displayEmail"><i class="mdi mdi-email-outline me-1"></i>Email: {{ displayEmail }}</span>
                      </div>
                    </div>
                  </div>
                  <div class="slip-title-block text-end flex-shrink-0">
                    <div class="slip-title-text">SLIP GAJI KARYAWAN</div>
                    <div class="slip-period-badge">
                      <div class="slip-period-name" :title="detail.period?.name">
                        <span class="text-white-50 me-1">PERIODE:</span>{{ detail.period?.name || 'Payroll' }}
                      </div>
                      <div class="slip-period-dates">
                        <i class="mdi mdi-calendar-range me-1"></i>{{ formatDate(detail.period?.start_date) }} &ndash; {{ formatDate(detail.period?.end_date) }}
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- ====== INFO KARYAWAN ====== -->
              <div class="slip-employee-block">
                <div class="row g-0">
                  <div class="col-md-5 pe-md-3">
                    <div class="row g-2">
                      <div class="col-12">
                        <div class="emp-label">NIK</div>
                        <div class="emp-value emp-value-big text-dark">{{ detail.employee?.employee_code || '-' }}</div>
                      </div>
                      <div class="col-12 mt-2">
                        <div class="emp-label">NAMA KARYAWAN</div>
                        <div class="emp-value emp-value-big text-dark">{{ detail.employee?.name }}</div>
                      </div>
                      <div class="col-6 mt-2">
                        <div class="emp-label">DEPARTEMEN</div>
                        <div class="emp-value emp-value-medium text-dark">{{ detail.employee?.department?.name || '-' }}</div>
                      </div>
                      <div class="col-6 mt-2">
                        <div class="emp-label">JABATAN</div>
                        <div class="emp-value emp-value-medium text-dark">{{ detail.employee?.position?.name || '-' }}</div>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-7 ps-md-4 border-start">
                    <div class="row g-2">
                      <div class="col-6">
                        <div class="emp-label">CABANG</div>
                        <div class="emp-value emp-value-medium text-dark">{{ branchName || detail.employee?.branch?.name || '-' }}</div>
                      </div>
                      <div class="col-6">
                        <div class="emp-label">STATUS KARYAWAN</div>
                        <div class="emp-value emp-value-medium text-dark">{{ formatStatus(detail.employee?.employment_status) }}</div>
                      </div>
                      <div class="col-6 mt-2">
                        <div class="emp-label">TOTAL HARI PERIODE</div>
                        <div class="emp-value emp-value-medium text-dark">{{ detail.attendance?.total_period_days || '-' }} Hari</div>
                      </div>
                      <div class="col-6 mt-2">
                        <div class="emp-label">TGL BERGABUNG</div>
                        <div class="emp-value emp-value-medium text-dark">{{ detail.employee?.join_date || '-' }}</div>
                      </div>
                      <div class="col-12 mt-2">
                        <div class="emp-label">KEHADIRAN PERIODE</div>
                        <div class="emp-value emp-value-medium text-dark">
                          {{ detail.attendance?.hadir ?? 0 }} Masuk /
                          {{ detail.attendance?.libur ?? 0 }} Libur /
                          {{ detail.attendance?.cuti ?? 0 }} Cuti
                          <span v-if="(detail.attendance?.alpha || 0) > 0" class="text-danger fw-bold">
                            / {{ detail.attendance?.alpha }} Alpha
                          </span>
                        </div>
                      </div>
                      <div class="col-6 mt-2" v-if="hasBpjsKesehatan">
                        <div class="emp-label">BPJS KESEHATAN</div>
                        <div class="emp-value text-dark">{{ detail.employee?.bpjs_kesehatan || '-' }}</div>
                      </div>
                      <div class="col-6 mt-2" v-if="hasBpjsKetenagakerjaan">
                        <div class="emp-label">BPJS KETENAGAKERJAAN</div>
                        <div class="emp-value text-dark">{{ detail.employee?.bpjs_ketenagakerjaan || '-' }}</div>
                      </div>
                      <div class="col-6 mt-2" v-if="hasTax">
                        <div class="emp-label">NPWP</div>
                        <div class="emp-value text-dark">{{ detail.employee?.npwp || '-' }}</div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- ====== INFORMASI BONUS & INSENTIF (DARI INPUTAN BONUS) ====== -->
              <div v-if="detail.rewards?.length" class="px-4 py-2.5 bg-success-subtle border-bottom border-success-subtle">
                <div class="d-flex align-items-center justify-content-between mb-2">
                  <span class="fw-bold text-success font-size-12">
                    <i class="mdi mdi-gift-outline me-1"></i>Informasi Bonus & Insentif (Inputan Bonus):
                  </span>
                  <span class="badge bg-success font-size-11">
                    Total: {{ money(totalRewardsAmount) }}
                  </span>
                </div>
                <div class="row g-2">
                  <div v-for="r in detail.rewards" :key="r.id" class="col-md-6">
                    <div class="p-2 bg-white rounded border border-success-subtle font-size-12 shadow-xs">
                      <div class="d-flex justify-content-between align-items-start">
                        <div>
                          <strong class="text-dark">{{ r.title || 'Bonus' }}</strong>
                          <span class="badge bg-light text-secondary border ms-1 font-size-10">{{ formatRewardType(r.type) }}</span>
                        </div>
                        <span class="fw-bold text-success">{{ money(r.amount) }}</span>
                      </div>
                      <div v-if="r.description" class="text-muted font-size-11 mt-1">
                        {{ r.description }}
                      </div>
                      <div v-if="r.reward_date" class="text-muted font-size-10 mt-0.5">
                        Tgl Diberikan: {{ formatDate(r.reward_date) }}
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- ====== INFORMASI POTONGAN LAIN-LAIN (DARI INPUTAN POTONGAN) ====== -->
              <div v-if="detail.deductions?.length" class="px-4 py-2.5 bg-danger-subtle border-bottom border-danger-subtle">
                <div class="d-flex align-items-center justify-content-between mb-2">
                  <span class="fw-bold text-danger font-size-12">
                    <i class="mdi mdi-cash-minus me-1"></i>Informasi Potongan Lain-Lain (Inputan Potongan):
                  </span>
                  <span class="badge bg-danger font-size-11">
                    Total: {{ money(totalDeductionsAmount) }}
                  </span>
                </div>
                <div class="row g-2">
                  <div v-for="d in detail.deductions" :key="d.id" class="col-md-6">
                    <div class="p-2 bg-white rounded border border-danger-subtle font-size-12 shadow-xs">
                      <div class="d-flex justify-content-between align-items-start">
                        <div>
                          <strong class="text-dark">{{ d.title || 'Potongan Lain-Lain' }}</strong>
                          <span class="badge bg-light text-secondary border ms-1 font-size-10">{{ formatDeductionType(d.type) }}</span>
                        </div>
                        <span class="fw-bold text-danger">- {{ money(d.amount) }}</span>
                      </div>
                      <div v-if="d.description" class="text-muted font-size-11 mt-1">
                        {{ d.description }}
                      </div>
                      <div v-if="d.deduction_date" class="text-muted font-size-10 mt-0.5">
                        Tgl Pemotongan: {{ formatDate(d.deduction_date) }}
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- ====== TABEL PENERIMAAN & POTONGAN ====== -->
              <div class="slip-body">
                <div class="row g-0">
                  <!-- Penerimaan -->
                  <div class="col-md-6 slip-col-left">
                    <div class="slip-col-header penerimaan-header">PENERIMAAN</div>
                    <div class="slip-col-subheader d-flex justify-content-between align-items-center">
                      <span>RINCIAN PENDAPATAN</span>
                      <span>HARI KERJA: <strong>{{ detail.attendance?.work_days || '-' }} HARI</strong></span>
                    </div>
                    <table class="table table-sm slip-table">
                      <tbody>
                        <!-- Gaji Pokok jika tidak ada di details -->
                        <tr v-if="earningDetails.length === 0">
                          <td>Gaji Pokok / Pendapatan</td>
                          <td class="text-end fw-semibold">{{ money(detail.gross_salary) }}</td>
                        </tr>
                        <tr v-for="d in earningDetails" :key="d.id">
                          <td>
                            {{ d.label }}
                            <small v-if="d.quantity && d.rate" class="text-muted d-block">
                              ({{ d.quantity }}× {{ money(d.rate) }})
                            </small>
                          </td>
                          <td class="text-end fw-semibold">{{ money(d.amount) }}</td>
                        </tr>
                      </tbody>
                      <tfoot>
                        <tr class="slip-total-row">
                          <td>TOTAL PENERIMAAN</td>
                          <td class="text-end fw-bold">{{ money(totalEarnings) }}</td>
                        </tr>
                      </tfoot>
                    </table>
                  </div>

                  <!-- Potongan -->
                  <div class="col-md-6 slip-col-right border-start">
                    <div class="slip-col-header potongan-header">POTONGAN</div>
                    <div class="slip-col-subheader d-flex justify-content-between align-items-center">
                      <span>RINCIAN PEMOTONGAN</span>
                      <span class="text-muted font-size-11">{{ deductionDetails.length }} Item</span>
                    </div>
                    <table class="table table-sm slip-table">
                      <tbody>
                        <tr v-if="deductionDetails.length === 0">
                          <td colspan="2" class="text-muted text-center py-2">Tidak ada potongan</td>
                        </tr>
                        <tr v-for="d in deductionDetails" :key="d.id">
                          <td>
                            {{ d.label }}
                            <small v-if="d.quantity && d.rate" class="text-muted d-block">
                              ({{ d.quantity }}× {{ money(d.rate) }})
                            </small>
                          </td>
                          <td class="text-end text-danger fw-semibold">{{ money(d.amount) }}</td>
                        </tr>
                      </tbody>
                      <tfoot>
                        <tr class="slip-total-row">
                          <td>TOTAL POTONGAN</td>
                          <td class="text-end text-danger fw-bold">{{ money(totalDeductions) }}</td>
                        </tr>
                      </tfoot>
                    </table>
                  </div>
                </div>
              </div>

              <!-- ====== TAKE HOME PAY ====== -->
              <div class="slip-thp-row">
                <div class="row g-0 align-items-center">
                  <div class="col-md-6 text-center border-end py-2 px-3">
                    <span class="thp-label">Total Penerimaan:</span>
                    <span class="thp-earning ms-1">{{ money(totalEarnings) }}</span>
                    <span class="thp-sep mx-2">−</span>
                    <span class="thp-label">Total Potongan:</span>
                    <span class="thp-deduction ms-1">{{ money(totalDeductions) }}</span>
                  </div>
                  <div class="col-md-6 text-center py-2 px-3">
                    <span class="thp-label">GAJI YANG DITERIMA:</span>
                    <span class="thp-net ms-2">{{ money(detail.net_salary) }}</span>
                  </div>
                </div>
              </div>

              <!-- ====== INFO BPJS & PAJAK ====== -->
              <div v-if="hasBpjs || hasTax" class="slip-info-row">
                <div class="row g-0">
                  <div v-if="hasBpjsKesehatan" class="col border-end px-3 py-2">
                    <div class="info-label">BPJS KESEHATAN</div>
                    <div class="info-value">{{ detail.employee?.bpjs_kesehatan || '-' }}</div>
                    <div class="info-label mt-1">Potongan BPJS Kes.</div>
                    <div class="info-value text-danger">{{ money(detail.total_bpjs || 0) }}</div>
                  </div>
                  <div v-if="hasBpjsKetenagakerjaan" class="col border-end px-3 py-2">
                    <div class="info-label">BPJS KETENAGAKERJAAN</div>
                    <div class="info-value">{{ detail.employee?.bpjs_ketenagakerjaan || '-' }}</div>
                    <div class="info-label mt-1">Potongan BPJS TK.</div>
                    <div class="info-value text-danger">-</div>
                  </div>
                  <div v-if="hasTax" class="col px-3 py-2">
                    <div class="info-label">NPWP</div>
                    <div class="info-value">{{ detail.employee?.npwp || '-' }}</div>
                    <div class="info-label mt-1">PPh 21</div>
                    <div class="info-value text-danger">{{ money(detail.total_tax || 0) }}</div>
                  </div>
                </div>
              </div>

              <!-- ====== CATATAN RESMI (TANDA TANGAN DIHILANGKAN) ====== -->
              <div class="slip-confidential py-3 bg-light border-top text-center">
                <div class="fw-semibold text-dark font-size-12 mb-0.5">
                  Dokumen Elektronik Resmi HRMS — Sah tanpa tanda tangan basah
                </div>
                <div class="text-muted font-size-11">
                  Dokumen ini RAHASIA, hanya diperuntukkan bagi karyawan yang namanya tertera pada dokumen ini.
                </div>
              </div>

              <!-- ====== VISUAL DIVIDER ANTAR HALAMAN (NO-PRINT) ====== -->
              <div class="page-break-divider no-print text-center my-4 py-2 bg-light border-top border-bottom text-muted fw-semibold font-size-12">
                Halaman 2: Lampiran Rekapitulasi & Log Absensi Kehadiran
              </div>

              <!-- =========================================
                   HALAMAN 2: REKAPITULASI & LOG ABSENSI
              ========================================== -->
              <div class="slip-page-2">
                <!-- Header Perusahaan Halaman 2 (KOP KONSISTEN) -->
                <div class="slip-company-header">
                  <div class="d-flex align-items-center justify-content-between gap-3">
                    <div class="company-left d-flex align-items-center gap-3">
                      <img v-if="effectiveLogoUrl"
                        :src="effectiveLogoUrl"
                        class="company-logo" alt="Logo">
                      <div class="company-text-group">
                        <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                          <span class="company-name-big">{{ companyInfo?.name || 'PERUSAHAAN' }}</span>
                          <span v-if="branchName" class="company-branch-badge">
                            <i class="mdi mdi-store-marker-outline me-1"></i>{{ branchName }}
                          </span>
                        </div>
                        <div class="company-address" v-if="displayAddress">{{ displayAddress }}</div>
                        <div class="company-contact" v-if="displayPhone || displayEmail">
                          <span v-if="displayPhone"><i class="mdi mdi-phone me-1"></i>Telp: {{ displayPhone }}</span>
                          <span v-if="displayPhone && displayEmail" class="mx-2 text-white-50">|</span>
                          <span v-if="displayEmail"><i class="mdi mdi-email-outline me-1"></i>Email: {{ displayEmail }}</span>
                        </div>
                      </div>
                    </div>
                    <div class="slip-title-block text-end flex-shrink-0">
                      <div class="slip-title-text">REKAPITULASI & LOG ABSENSI</div>
                      <div class="slip-period-badge">
                        <div class="slip-period-name" :title="detail.period?.name">
                          <span class="text-white-50 me-1">PERIODE:</span>{{ detail.period?.name || 'Payroll' }}
                        </div>
                        <div class="slip-period-dates">
                          <i class="mdi mdi-calendar-range me-1"></i>{{ formatDate(detail.period?.start_date) }} &ndash; {{ formatDate(detail.period?.end_date) }}
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Info Karyawan & Ringkasan Kehadiran Halaman 2 -->
                <div class="slip-employee-block bg-light-subtle">
                  <div class="row g-2">
                    <div class="col-md-3">
                      <div class="emp-label">NAMA KARYAWAN</div>
                      <div class="emp-value emp-value-medium text-dark">{{ detail.employee?.name }}</div>
                      <div class="text-muted font-size-11">{{ detail.employee?.employee_code || '-' }}</div>
                    </div>
                    <div class="col-md-3">
                      <div class="emp-label">DEPARTEMEN / JABATAN</div>
                      <div class="emp-value font-size-12">{{ detail.employee?.department?.name || '-' }} / {{ detail.employee?.position?.name || '-' }}</div>
                      <div class="text-muted font-size-11">{{ branchName || detail.employee?.branch?.name || '-' }}</div>
                    </div>
                    <div class="col-md-3">
                      <div class="emp-label">TOTAL HARI PERIODE</div>
                      <div class="emp-value emp-value-medium text-dark">{{ detail.attendance?.total_period_days || '-' }} Hari</div>
                      <div class="font-size-11 text-success fw-semibold">Hadir: {{ detail.attendance?.hadir ?? 0 }} Hari</div>
                    </div>
                    <div class="col-md-3">
                      <div class="emp-label">LIBUR / CUTI / ALPHA</div>
                      <div class="emp-value font-size-12">
                        {{ detail.attendance?.libur ?? 0 }} Libur / {{ detail.attendance?.cuti ?? 0 }} Cuti
                        <span v-if="(detail.attendance?.alpha || 0) > 0" class="text-danger fw-bold"> / {{ detail.attendance?.alpha }} Alpha</span>
                      </div>
                      <div class="text-muted font-size-11">
                        Terlambat: {{ detail.attendance?.late_minutes ?? 0 }} mnt | Lembur: {{ detail.attendance?.overtime_minutes ?? 0 }} mnt
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Tabel Rincian Log Kehadiran Harian -->
                <div class="p-3">
                  <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="fw-bold text-dark mb-0 font-size-13">
                      Rincian Log Kehadiran Harian ({{ detail.attendance_logs?.length || 0 }} Hari)
                    </h6>
                    <span class="text-muted font-size-11">Rentang: {{ formatDate(detail.period?.start_date) }} s/d {{ formatDate(detail.period?.end_date) }}</span>
                  </div>

                  <div class="table-responsive border rounded-2">
                    <table class="table table-sm table-hover mb-0 font-size-12 align-middle">
                      <thead class="table-light">
                        <tr>
                          <th class="text-center py-2 px-2" style="width: 35px;">No</th>
                          <th class="py-2">Tanggal & Hari</th>
                          <th class="py-2">Shift</th>
                          <th class="py-2 text-center" style="width: 70px;">Masuk</th>
                          <th class="py-2 text-center" style="width: 70px;">Keluar</th>
                          <th class="py-2 text-center" style="width: 75px;">Terlambat</th>
                          <th class="py-2 text-center" style="width: 75px;">Lembur</th>
                          <th class="py-2 text-center" style="width: 80px;">Jam Kerja</th>
                          <th class="py-2 text-center" style="width: 90px;">Status</th>
                          <th class="py-2">Keterangan</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="log in (detail.attendance_logs || [])" :key="log.no">
                          <td class="text-center text-muted font-size-11">{{ log.no }}</td>
                          <td class="fw-medium text-dark">
                            {{ log.day_name }}, {{ log.formatted_date }}
                          </td>
                          <td class="text-muted font-size-11">{{ log.shift }}</td>
                          <td class="text-center fw-bold" :class="log.clock_in !== '-' ? 'text-dark' : 'text-muted'">{{ log.clock_in }}</td>
                          <td class="text-center fw-bold" :class="log.clock_out !== '-' ? 'text-dark' : 'text-muted'">{{ log.clock_out }}</td>
                          <td class="text-center font-size-11" :class="log.late_minutes > 0 ? 'text-danger fw-bold' : 'text-muted'">
                            {{ log.late_minutes > 0 ? log.late_minutes + ' mnt' : '-' }}
                          </td>
                          <td class="text-center font-size-11" :class="log.overtime_minutes > 0 ? 'text-primary fw-bold' : 'text-muted'">
                            {{ log.overtime_minutes > 0 ? log.overtime_minutes + ' mnt' : '-' }}
                          </td>
                          <td class="text-center font-size-11">
                            {{ log.working_hours > 0 ? log.working_hours.toFixed(1) + ' jam' : '-' }}
                          </td>
                          <td class="text-center">
                            <span class="badge font-size-11 px-2 py-0.5 rounded-pill" :class="getAttendanceLogBadgeClass(log.status)">
                              {{ getAttendanceLogStatusLabel(log.status) }}
                            </span>
                          </td>
                          <td class="text-muted font-size-11">{{ log.notes || '-' }}</td>
                        </tr>
                        <tr v-if="!detail.attendance_logs || detail.attendance_logs.length === 0">
                          <td colspan="10" class="text-center py-4 text-muted">
                            Tidak ada rincian log kehadiran untuk periode ini.
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>

                  <!-- Catatan Halaman 2 -->
                  <div class="slip-confidential mt-3 py-2 border-top text-center">
                    <div class="text-muted font-size-11">
                      Halaman 2: Lampiran Rekapitulasi & Rincian Log Absensi Karyawan — Dokumen Elektronik Resmi HRMS.
                    </div>
                  </div>
                </div>
              </div>

            </div>
          </div>
          <!-- ======= END AREA CETAK ======= -->

        </div>
      </div>
    </div>

    <!-- =========================================
         MODAL INFORMASI ABSENSI PERIODE
    ========================================== -->
    <div v-if="showAttendanceSummaryModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 1065;">
      <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
          <div class="modal-header text-white pt-3 px-4 pb-3" style="background: linear-gradient(135deg, #005b52 0%, #008779 100%);">
            <h5 class="modal-title fw-bold fs-5 text-white">
              <i class="mdi mdi-calendar-check me-2"></i>Informasi Kehadiran & Absensi Periode — {{ selectedPeriodInfo?.name }}
            </h5>
            <button type="button" class="btn-close btn-close-white" @click="showAttendanceSummaryModal = false"></button>
          </div>
          <div class="modal-body p-4 bg-light">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
              <div class="text-muted small">
                <i class="mdi mdi-calendar me-1"></i>Rentang Tanggal: <strong>{{ formatDate(selectedPeriodInfo?.start_date) }} - {{ formatDate(selectedPeriodInfo?.end_date) }}</strong>
                <span class="ms-3"><i class="mdi mdi-account-group me-1"></i>Total: <strong>{{ attendanceSummaryList.length }} Karyawan</strong></span>
              </div>
              <div style="width: 280px;">
                <input v-model="attendanceSearchQuery" type="text" class="form-control form-control-sm rounded-3 shadow-xs" placeholder="🔍 Cari nama / NIK..." />
              </div>
            </div>

            <div class="table-responsive bg-white rounded-3 shadow-sm border" style="max-height: 450px; overflow-y: auto;">
              <table class="table table-hover table-striped align-middle mb-0" style="font-size: 13px;">
                <thead class="table-dark sticky-top">
                  <tr>
                    <th>No</th>
                    <th>NIK</th>
                    <th>Nama Karyawan</th>
                    <th>Departemen</th>
                    <th class="text-center">Total Hari</th>
                    <th class="text-center text-success">Hadir</th>
                    <th class="text-center text-info">Libur</th>
                    <th class="text-center text-primary">Cuti</th>
                    <th class="text-center text-danger">Alpha</th>
                    <th class="text-end">Rate / Hari</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(emp, i) in attendanceSummaryList" :key="emp.id">
                    <td>{{ i + 1 }}</td>
                    <td><span class="badge bg-secondary">{{ emp.nik }}</span></td>
                    <td class="fw-bold text-dark">{{ emp.name }}</td>
                    <td class="text-muted">{{ emp.department }}</td>
                    <td class="text-center fw-semibold">{{ emp.total_period_days }} Hari</td>
                    <td class="text-center text-success fw-bold">{{ emp.hadir }}</td>
                    <td class="text-center text-info fw-semibold">{{ emp.libur }}</td>
                    <td class="text-center text-primary fw-semibold">{{ emp.cuti }}</td>
                    <td class="text-center">
                      <span v-if="emp.alpha > 0" class="badge bg-danger px-2 py-1">{{ emp.alpha }} Alpha</span>
                      <span v-else class="text-muted">0</span>
                    </td>
                    <td class="text-end fw-semibold">{{ money(emp.rate_per_day) }}</td>
                  </tr>
                  <tr v-if="attendanceSummaryList.length === 0">
                    <td colspan="10" class="text-center text-muted py-4">Tidak ada data absensi ditemukan.</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          <div class="modal-footer border-top-0 px-4 pb-3 pt-2 bg-light">
            <button type="button" class="btn btn-secondary px-4 rounded-3" @click="showAttendanceSummaryModal = false">Tutup</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Print Laporan (hidden) -->
    <div id="printable-report" style="display:none;">
      <div class="d-flex align-items-center justify-content-center gap-3 mb-3">
        <img v-if="companyStore.logoUrl" :src="companyStore.logoUrl" style="max-height: 48px; max-width: 120px; object-fit: contain;" alt="Logo" />
        <div class="text-center">
          <h4 class="fw-bold mb-0">Laporan Payroll Karyawan</h4>
          <p class="text-muted mb-0">Periode: {{ reportData[0]?.period?.name || '-' }}</p>
        </div>
      </div>
      <table class="table table-bordered table-sm" style="font-size:12px;">
        <thead style="background:#1a3c5e; color:white;">
          <tr>
            <th>No</th><th>NIK</th><th>Nama</th><th>Jabatan</th><th>Dept</th>
            <th>Gaji Pokok</th><th>Total Penerimaan</th><th>Total Potongan</th>
            <th>Gaji Bersih</th><th>Status</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(row, i) in reportData" :key="row.id">
            <td>{{ i+1 }}</td>
            <td>{{ row.employee?.employee_code || '-' }}</td>
            <td>{{ row.employee?.name || '-' }}</td>
            <td>{{ row.employee?.position?.name || '-' }}</td>
            <td>{{ row.employee?.department?.name || '-' }}</td>
            <td class="text-end">{{ money(row.gross_salary) }}</td>
            <td class="text-end">{{ money(+row.gross_salary + +row.total_allowance) }}</td>
            <td class="text-end">{{ money(+row.total_deduction + +row.total_bpjs + +row.total_tax) }}</td>
            <td class="text-end fw-bold">{{ money(row.net_salary) }}</td>
            <td class="text-capitalize text-center">{{ row.status }}</td>
          </tr>
        </tbody>
        <tfoot>
          <tr class="fw-bold" style="background:#f8f9fa;">
            <td colspan="5" class="text-end">TOTAL</td>
            <td class="text-end">{{ money(reportData.reduce((s,r)=>s+ +r.gross_salary,0)) }}</td>
            <td class="text-end">{{ money(reportData.reduce((s,r)=>s+ +r.gross_salary + +r.total_allowance,0)) }}</td>
            <td class="text-end">{{ money(reportData.reduce((s,r)=>s+ +r.total_deduction + +r.total_bpjs + +r.total_tax,0)) }}</td>
            <td class="text-end">{{ money(reportData.reduce((s,r)=>s+ +r.net_salary,0)) }}</td>
            <td></td>
          </tr>
        </tfoot>
      </table>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import api from '../api'
import CrudPage from '../components/CrudPage.vue'
import { useCompanyStore } from '../stores/companyStore'

const route = useRoute()
const companyStore = useCompanyStore()
const crudPageRef = ref(null)
const detail     = ref(null)
const reportData = ref([])
const isPrintingReport = ref(false)
const periodsList = ref([])

const companyInfo = computed(() => {
  return detail.value?.company || detail.value?.employee?.company || null
})

const effectiveLogoUrl = computed(() => {
  return companyInfo.value?.logo || null
})

const branchInfo = computed(() => {
  return detail.value?.company?.branch || detail.value?.employee?.branch || detail.value?.period?.branch || detail.value?.assignment?.branch || null
})

const branchName = computed(() => {
  return branchInfo.value?.name || detail.value?.company?.branch_name || detail.value?.employee?.branch_name || null
})

const displayAddress = computed(() => {
  return branchInfo.value?.address || companyInfo.value?.address || ''
})

const displayPhone = computed(() => {
  return branchInfo.value?.phone || companyInfo.value?.phone || ''
})

const displayEmail = computed(() => {
  return branchInfo.value?.email || companyInfo.value?.email || ''
})

const money = (v) => 'Rp ' + Number(v || 0).toLocaleString('id-ID')
const formatStatus = (s) => {
  const map = { permanent: 'Karyawan Tetap', contract: 'Kontrak', probation: 'Probation', internship: 'Magang' }
  return map[s] || s || '-'
}

const formatDate = (d) => {
  if (!d) return '-'
  try {
    const parsed = new Date(d)
    if (isNaN(parsed.getTime())) return d
    return parsed.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
  } catch (e) {
    return d
  }
}

const formatCycleLabel = (cycle) => {
  const map = {
    daily: 'Harian (Daily)',
    weekly_payment: 'Pembayaran Mingguan',
    weekly_payslip: 'Slip Gaji Mingguan (Bulanan)',
    monthly: 'Bulanan (Monthly)'
  }
  return map[cycle] || cycle || '-'
}

const statusBadgeClass = (status) => {
  if (status === 'closed' || status === 'approved') return 'bg-success text-white'
  if (status === 'processing') return 'bg-primary text-white'
  if (status === 'draft') return 'bg-warning text-dark'
  return 'bg-secondary text-white'
}

const baseFilters = computed(() => {
  const pId = route.query.period || route.query.payroll_period_id
  return pId ? { payroll_period_id: pId } : {}
})

const selectedPeriodId = computed(() => {
  return route.query.period || route.query.payroll_period_id || crudPageRef.value?.filterValues?.payroll_period_id || null
})

const selectedPeriodInfo = computed(() => {
  const pId = selectedPeriodId.value
  if (pId) {
    const found = periodsList.value.find(p => String(p.id) === String(pId))
    if (found) return found
  }
  return reportData.value?.[0]?.period || null
})

const totalPeriodNetSalary = computed(() => {
  if (!reportData.value || !reportData.value.length) return 0
  return reportData.value.reduce((acc, row) => acc + Number(row.net_salary || 0), 0)
})

const pageTitle = computed(() => {
  if (selectedPeriodInfo.value) {
    return `Slip Gaji — ${selectedPeriodInfo.value.name}`
  }
  return 'Slip Gaji (Payslips)'
})

const columns = [
  { key: 'employee',   label: 'Nama Karyawan',   format: (v, row) => row.employee?.name  || '-' },
  { key: 'employee',   label: 'Departemen',      format: (v, row) => row.employee?.department?.name || '-' },
  { key: 'gross_salary',    label: 'Penerimaan', format: money },
  { key: 'total_deduction', label: 'Potongan',   format: (v, row) => money(+row.total_deduction + +row.total_bpjs + +row.total_tax) },
  { key: 'net_salary', label: 'Gaji Bersih',     format: money },
  { key: 'status',     label: 'Status' },
]

const showAttendanceSummaryModal = ref(false)
const attendanceSearchQuery = ref('')

const attendanceSummaryList = computed(() => {
  if (!reportData.value) return []
  return reportData.value.map(row => {
    const att = row.attendance || row.breakdown?.attendance || {}
    const vars = row.breakdown?.variables || {}
    return {
      id: row.id,
      name: row.employee?.name || '-',
      nik: row.employee?.employee_code || '-',
      department: row.employee?.department?.name || '-',
      position: row.employee?.position?.name || '-',
      total_period_days: att.total_period_days || vars.total_period_days || 0,
      work_days: att.work_days || vars.total_working_days || 0,
      hadir: att.hadir ?? vars.total_attendance_days ?? 0,
      libur: att.libur ?? vars.total_day_off ?? 0,
      cuti: att.cuti ?? vars.total_paid_leave_days ?? 0,
      alpha: att.alpha ?? vars.total_unpaid_leave_days ?? 0,
      rate_per_day: att.rate_per_day || vars.rate_per_day || 0,
      rate_per_hour: att.rate_per_hour || vars.rate_per_hour || 0,
    }
  }).filter(item => {
    if (!attendanceSearchQuery.value) return true
    const q = attendanceSearchQuery.value.toLowerCase()
    return item.name.toLowerCase().includes(q) || item.nik.toLowerCase().includes(q) || item.department.toLowerCase().includes(q)
  })
})

const openAttendanceSummaryModal = () => {
  showAttendanceSummaryModal.value = true
}

const filters = ref([
  { key: 'payroll_period_id', label: 'Pilih Periode', options: [] }
])

const earningDetails = computed(() =>
  detail.value?.details?.filter(x => x.type === 'earning' || x.type === 'benefit') ?? []
)
const deductionDetails = computed(() =>
  detail.value?.details?.filter(x => x.type === 'deduction' || x.type === 'tax' || x.type === 'bpjs') ?? []
)

const totalEarnings = computed(() => {
  if (!detail.value) return 0
  if (earningDetails.value.length > 0) {
    return earningDetails.value.reduce((a, d) => a + +d.amount, 0)
  }
  return +(detail.value.gross_salary || 0)
})
const totalDeductions = computed(() =>
  deductionDetails.value.reduce((a, d) => a + +d.amount, 0)
)

const totalRewardsAmount = computed(() => {
  if (!detail.value?.rewards?.length) return 0
  return detail.value.rewards.reduce((sum, r) => sum + Number(r.amount || 0), 0)
})

const formatRewardType = (type) => {
  const map = {
    bonus: 'Bonus',
    thr: 'THR',
    incentive: 'Insentif',
    performance: 'Prestasi',
    overtime: 'Lembur',
    other: 'Lainnya'
  }
  return map[type] || type || 'Bonus'
}

const totalDeductionsAmount = computed(() => {
  if (!detail.value?.deductions?.length) return 0
  return detail.value.deductions.reduce((sum, d) => sum + Number(d.amount || 0), 0)
})

const formatDeductionType = (type) => {
  const map = {
    kerusakan: 'Ganti Rugi',
    denda: 'Denda',
    pinjaman: 'Kasbon / Pinjaman',
    klaim: 'Penyesuaian',
    other: 'Lainnya'
  }
  return map[type] || type || 'Potongan'
}

const getAttendanceLogBadgeClass = (status) => {
  const map = {
    present: 'bg-success text-white',
    late: 'bg-warning text-dark',
    day_off: 'bg-info text-white',
    paid_leave: 'bg-primary text-white',
    leave: 'bg-primary text-white',
    sick: 'bg-warning text-dark',
    permission: 'bg-info text-dark',
    unpaid_leave: 'bg-danger text-white',
    absent: 'bg-danger text-white',
    alpha: 'bg-danger text-white',
  }
  return map[status] || 'bg-secondary text-white'
}

const getAttendanceLogStatusLabel = (status) => {
  const map = {
    present: 'Hadir',
    late: 'Terlambat',
    day_off: 'Libur',
    paid_leave: 'Cuti',
    leave: 'Cuti',
    sick: 'Sakit',
    permission: 'Izin',
    unpaid_leave: 'Unpaid / Khusus',
    absent: 'Alpha',
    alpha: 'Alpha',
  }
  return map[status] || status || '-'
}

const hasBpjs = computed(() => {
  if (detail.value?.has_bpjs !== undefined) return Boolean(detail.value.has_bpjs)
  return hasBpjsKesehatan.value || hasBpjsKetenagakerjaan.value
})

const hasBpjsKesehatan = computed(() => {
  if (detail.value?.has_bpjs_kesehatan !== undefined) return Boolean(detail.value.has_bpjs_kesehatan)
  if (Number(detail.value?.total_bpjs || 0) > 0) return true
  return detail.value?.details?.some(d => {
    const lbl = (d.label || '').toLowerCase()
    return (d.type === 'bpjs' || lbl.includes('bpjs')) && (lbl.includes('kes') || !lbl.includes('tk'))
  }) || false
})

const hasBpjsKetenagakerjaan = computed(() => {
  if (detail.value?.has_bpjs_ketenagakerjaan !== undefined) return Boolean(detail.value.has_bpjs_ketenagakerjaan)
  return detail.value?.details?.some(d => {
    const lbl = (d.label || '').toLowerCase()
    return (d.type === 'bpjs' || lbl.includes('bpjs')) && (lbl.includes('tk') || lbl.includes('ketenagakerjaan') || lbl.includes('jht') || lbl.includes('jp'))
  }) || false
})

const hasTax = computed(() => {
  if (detail.value?.has_tax !== undefined) return Boolean(detail.value.has_tax)
  if (Number(detail.value?.total_tax || 0) > 0) return true
  return detail.value?.details?.some(d => {
    const lbl = (d.label || '').toLowerCase()
    return d.type === 'tax' || lbl.includes('pph') || lbl.includes('pajak')
  }) || false
})

const cancelCurrentPeriod = async () => {
  if (!selectedPeriodInfo.value) return
  if (!confirm(`Yakin ingin membatalkan & menghapus (soft delete) periode "${selectedPeriodInfo.value.name}"? Semua slip gaji pada periode ini juga akan dibatalkan.`)) return
  try {
    const res = await api.post(`/payroll/periods/${selectedPeriodInfo.value.id}/cancel`)
    alert(res.data.message || 'Periode berhasil dibatalkan (soft delete)')
    window.location.href = '/hrms/payroll-periods'
  } catch (e) {
    alert(e.response?.data?.message || 'Gagal membatalkan periode')
  }
}

const viewDetail = async (id) => {
  try {
    const { data } = await api.get(`/payroll/runs/${id}`)
    detail.value = data.data
  } catch (e) {
    alert(e.response?.data?.message || 'Gagal ambil detail')
  }
}

const downloadPdf = async (id, fileName = 'Slip_Gaji.pdf') => {
  try {
    const res = await api.get(`/payroll/runs/${id}/pdf`, { responseType: 'blob' })
    const blob = new Blob([res.data], { type: 'application/pdf' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = fileName
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
  } catch (e) {
    if (e.response?.data instanceof Blob) {
      try {
        const text = await e.response.data.text()
        const json = JSON.parse(text)
        alert(json.message || 'Gagal mendownload PDF slip gaji')
        return
      } catch (err) {}
    }
    alert('Gagal mendownload PDF slip gaji')
  }
}

const printDirect = (id) => downloadPdf(id)

const sendWa = (row) => {
  const phone = row.employee?.phone || ''
  if (!phone) { alert('Nomor HP karyawan belum terdaftar.'); return }
  const fp   = phone.startsWith('0') ? '62' + phone.substring(1) : phone.replace(/^\+/, '')
  const text = `Halo *${row.employee?.name}*,\n\nBerikut ringkasan Slip Gaji Anda:\n` +
    `📅 Periode: *${row.period?.name || '-'}*\n\n` +
    `✅ Total Penerimaan: *${money(+row.gross_salary + +row.total_allowance)}*\n` +
    `❌ Total Potongan: *${money(+row.total_deduction + +row.total_bpjs + +row.total_tax)}*\n` +
    `💰 Gaji Bersih: *${money(row.net_salary)}*\n\n` +
    `Untuk detail lengkap, silakan login ke aplikasi HRMS.\n\nTerima kasih.`
  window.open(`https://wa.me/${fp}?text=${encodeURIComponent(text)}`, '_blank')
}

const printPayslip = () => {
  const el = document.getElementById('printable-payslip')
  if (!el) return
  const styles = `
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
      * { box-sizing: border-box; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
      body { margin: 0; padding: 15px; font-family: 'Segoe UI', Arial, sans-serif; font-size: 13px; color: #1e293b; background: #fff; }
      .slip-root { max-width: 860px; margin: auto; border: 1px solid #cbd5e1; border-radius: 4px; overflow: hidden; }
      .slip-company-header { padding: 14px 20px 12px; background: #1a3c5e !important; border-bottom: 3px solid #008779 !important; color: #ffffff !important; }
      .company-left { flex: 1 1 auto; min-width: 0; }
      .company-logo { max-height: 48px; max-width: 110px; object-fit: contain; }
      .company-name-big { font-size: 19px; font-weight: 800; letter-spacing: 0.5px; color: #ffffff !important; line-height: 1.2; text-transform: uppercase; white-space: nowrap; }
      .company-branch-badge { background: rgba(0, 135, 121, 0.35) !important; border: 1px solid #008779 !important; color: #5eead4 !important; font-size: 10.5px; font-weight: 700; padding: 2px 7px; border-radius: 3px; display: inline-block; white-space: nowrap; }
      .company-address, .company-contact { font-size: 11px; color: #e2e8f0 !important; line-height: 1.35; margin-top: 2px; }
      .slip-title-block { flex: 0 0 auto; max-width: 48%; }
      .slip-title-text { font-size: 16px; font-weight: 800; letter-spacing: 1.2px; color: #ffffff !important; line-height: 1.2; text-transform: uppercase; }
      .slip-period-badge { background: rgba(0, 0, 0, 0.3) !important; border: 1px solid rgba(255, 255, 255, 0.25) !important; color: #ffffff !important; padding: 4px 10px; border-radius: 4px; display: inline-block; margin-top: 4px; text-align: right; line-height: 1.3; }
      .slip-period-name { font-size: 11px; font-weight: 700; color: #ffffff !important; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 360px; }
      .slip-period-dates { font-size: 10.5px; color: #cbd5e1 !important; font-weight: 500; margin-top: 2px; }
      .slip-employee-block { padding: 16px 20px; border-bottom: 2px solid #cbd5e1; background: #fafbfc; }
      .emp-label { font-size: 10px; font-weight: 700; color: #64748b; letter-spacing: 0.5px; text-transform: uppercase; margin-bottom: 2px; }
      .emp-value-big { font-size: 14px; font-weight: 700; color: #0f172a; }
      .emp-value-medium { font-size: 13px; font-weight: 600; color: #1e293b; }
      .emp-value { font-size: 12.5px; font-weight: 600; color: #334155; }
      .border-start { border-left: 1px solid #e2e8f0 !important; }
      .border-end { border-right: 1px solid #e2e8f0 !important; }
      .slip-body { border-bottom: 2px solid #cbd5e1; }
      .slip-col-left, .slip-col-right { padding: 0; }
      .slip-col-header { font-size: 12px; font-weight: 800; letter-spacing: 1.5px; padding: 8px 16px; color: white !important; }
      .penerimaan-header { background: #1a3c5e !important; }
      .potongan-header { background: #c0392b !important; }
      .slip-col-subheader { font-size: 11px; padding: 6px 16px; background: #f1f5f9 !important; border-bottom: 1px solid #e2e8f0; font-weight: 600; color: #475569; }
      .slip-table { margin: 0; font-size: 12.5px; width: 100%; border-collapse: collapse; }
      .slip-table td { padding: 6px 16px; border: none; border-bottom: 1px solid #f1f5f9; }
      .slip-total-row td { font-weight: 800; font-size: 13px; padding: 8px 16px; border-top: 2px solid #cbd5e1 !important; background: #f8fafc !important; }
      .slip-thp-row { padding: 12px 20px; background: #1a3c5e !important; color: white !important; }
      .thp-label { font-size: 11.5px; color: #cbd5e1 !important; font-weight: 600; }
      .thp-earning { font-weight: 700; color: #ffffff !important; }
      .thp-deduction { color: #fca5a5 !important; font-weight: 700; }
      .thp-sep { color: #94a3b8 !important; }
      .thp-net { font-size: 18px; font-weight: 800; color: #86efac !important; }
      .slip-info-row { border-top: 1px solid #e2e8f0; font-size: 12px; background: #f8fafc; }
      .info-label { font-size: 10px; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
      .info-value { font-weight: 600; font-size: 12px; color: #0f172a; }
      .slip-confidential { font-size: 10px; color: #64748b; text-align: center; padding: 10px; border-top: 1px dashed #cbd5e1; background: #f8fafc !important; }
      .slip-page-2 { margin-top: 15px; }
      @media print {
        body { padding: 0; }
        .slip-root { border: none !important; border-radius: 0 !important; }
        .page-break-divider { display: none !important; }
        .no-print { display: none !important; }
        .slip-page-2 {
          page-break-before: always !important;
          break-before: page !important;
        }
      }
    </style>
  `
  const win = window.open('', '_blank')
  win.document.write(`<!DOCTYPE html><html><head><title>Slip Gaji & Absensi</title>${styles}</head><body>${el.innerHTML}</body></html>`)
  win.document.close()
  win.focus()
  setTimeout(() => { win.print(); win.close() }, 600)
}

const printReport = () => {
  const el = document.getElementById('printable-report')
  if (!el) return
  el.style.display = 'block'
  const win = window.open('', '_blank')
  win.document.write(`<!DOCTYPE html><html>
    <head><title>Laporan Payroll</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>body{padding:20px;font-size:12px;} th{background:#1a3c5e!important;color:white!important;}</style>
    </head><body>${el.innerHTML}</body></html>`)
  win.document.close()
  win.focus()
  setTimeout(() => { win.print(); win.close(); el.style.display = 'none' }, 600)
}

const exportingExcel = ref(false)
const exportExcelReport = async () => {
  const periodId = route.query.period || selectedPeriodInfo.value?.id
  if (!periodId) {
    alert('Pilih periode payroll terlebih dahulu untuk diekspor.')
    return
  }
  if (exportingExcel.value) return
  exportingExcel.value = true
  try {
    const res = await api.get(`/payroll/periods/${periodId}/export-excel`, { responseType: 'blob' })
    const pName = selectedPeriodInfo.value?.name ? selectedPeriodInfo.value.name.replace(/\s+/g, '_') : periodId
    const filename = `Laporan_Gaji_Mekanik_${pName}.xlsx`
    const url = window.URL.createObjectURL(new Blob([res.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', filename)
    document.body.appendChild(link)
    link.click()
    link.parentNode.removeChild(link)
    window.URL.revokeObjectURL(url)
  } catch (e) {
    console.error('Export excel error', e)
    alert('Gagal mengekspor data Excel.')
  } finally {
    exportingExcel.value = false
  }
}

onMounted(async () => {
  if (!companyStore.company) {
    companyStore.fetchCompany()
  }
  try {
    const { data } = await api.get('/payroll/periods?per_page=100')
    const periods = data.data?.data || []
    periodsList.value = periods
    filters.value[0].options = periods.map(p => ({ value: p.id, label: p.name }))
  } catch (e) {}
})
</script>

<style scoped>
/* ===== PERIOD DASHBOARD BANNER ===== */
.period-banner-card {
  background: #ffffff;
  transition: all 0.3s ease;
}
.period-banner-header {
  background: linear-gradient(135deg, #005b52 0%, #008779 50%, #02aab0 100%);
  position: relative;
  overflow: hidden;
}
.text-white-80 {
  color: rgba(255, 255, 255, 0.85);
}
.text-teal {
  color: #008779 !important;
}
.fs-7 {
  font-size: 0.8rem;
}
.shadow-xs {
  box-shadow: 0 1px 3px rgba(0,0,0,0.08);
}

/* ===== SLIP ROOT ===== */
.slip-root {
  max-width: 860px;
  margin: 0 auto;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  overflow: hidden;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
  background: #ffffff;
}

/* ===== COMPANY HEADER (FLAT COLOR) ===== */
.slip-company-header {
  padding: 14px 20px 12px;
  background-color: #1a3c5e;
  border-bottom: 3px solid #008779;
  color: #ffffff;
}
.company-left {
  flex: 1 1 auto;
  min-width: 0;
}
.company-text-group {
  min-width: 0;
}
.company-logo {
  max-height: 48px;
  max-width: 110px;
  object-fit: contain;
  display: block;
}
.company-name-big {
  font-size: 19px;
  font-weight: 800;
  letter-spacing: 0.5px;
  color: #ffffff;
  line-height: 1.2;
  text-transform: uppercase;
  white-space: nowrap;
}
.company-branch-badge {
  background: rgba(0, 135, 121, 0.35);
  border: 1px solid #008779;
  color: #5eead4;
  font-size: 11px;
  font-weight: 700;
  padding: 2px 8px;
  border-radius: 4px;
  letter-spacing: 0.3px;
  white-space: nowrap;
  display: inline-flex;
  align-items: center;
}
.company-address, .company-contact {
  font-size: 11px;
  color: #e2e8f0;
  line-height: 1.35;
  margin-top: 2px;
}
.slip-title-block {
  flex: 0 0 auto;
  max-width: 48%;
}
.slip-title-text {
  font-size: 16px;
  font-weight: 800;
  letter-spacing: 1.2px;
  color: #ffffff;
  line-height: 1.2;
  text-transform: uppercase;
}
.slip-period-badge {
  background: rgba(0, 0, 0, 0.3);
  border: 1px solid rgba(255, 255, 255, 0.25);
  color: #ffffff;
  padding: 4px 10px;
  border-radius: 4px;
  display: inline-block;
  margin-top: 4px;
  text-align: right;
  line-height: 1.3;
}
.slip-period-name {
  font-size: 11px;
  font-weight: 700;
  color: #ffffff;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 360px;
}
.slip-period-dates {
  font-size: 10.5px;
  color: #cbd5e1;
  font-weight: 500;
  margin-top: 2px;
}

/* ===== EMPLOYEE INFO ===== */
.slip-employee-block {
  padding: 16px 20px;
  border-bottom: 2px solid #e2e8f0;
  background: #fafbfc;
}
.emp-label {
  font-size: 10px;
  font-weight: 700;
  color: #64748b;
  letter-spacing: 0.5px;
  text-transform: uppercase;
  margin-bottom: 2px;
}
.emp-value-big {
  font-size: 14px;
  font-weight: 700;
  color: #0f172a;
}
.emp-value-medium {
  font-size: 13px;
  font-weight: 600;
  color: #1e293b;
}
.emp-value {
  font-size: 12.5px;
  font-weight: 600;
  color: #334155;
}

/* ===== BODY - PENERIMAAN/POTONGAN ===== */
.slip-body {
  border-bottom: 2px solid #cbd5e1;
}
.slip-col-header {
  font-size: 12px;
  font-weight: 800;
  letter-spacing: 1.5px;
  padding: 8px 16px;
  color: white;
}
.penerimaan-header {
  background: #1a3c5e;
}
.potongan-header {
  background: #c0392b;
}
.slip-col-subheader {
  font-size: 11px;
  padding: 6px 16px;
  background: #f1f5f9;
  border-bottom: 1px solid #e2e8f0;
  font-weight: 600;
  color: #475569;
}
.slip-table {
  margin: 0;
  font-size: 13px;
}
.slip-table :deep(td) {
  padding: 6px 16px;
  border: none;
  border-bottom: 1px solid #f1f5f9;
}
.slip-total-row :deep(td) {
  font-weight: 800;
  font-size: 13px;
  padding: 8px 16px;
  border-top: 2px solid #cbd5e1 !important;
  background: #f8fafc;
}

/* ===== THP ===== */
.slip-thp-row {
  padding: 12px 20px;
  background-color: #1a3c5e;
  color: white;
}
.thp-label {
  font-size: 11.5px;
  color: #cbd5e1;
  font-weight: 600;
}
.thp-earning {
  font-weight: 700;
  color: #ffffff;
}
.thp-deduction {
  color: #fca5a5;
  font-weight: 700;
}
.thp-net {
  font-size: 18px;
  font-weight: 800;
  color: #86efac;
}
.thp-sep {
  color: #94a3b8;
}

/* ===== INFO BPJS ===== */
.slip-info-row {
  border-top: 1px solid #e2e8f0;
  font-size: 12px;
  background: #f8fafc;
}
.info-label {
  font-size: 10px;
  color: #64748b;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.8px;
}
.info-value {
  font-weight: 600;
  font-size: 12px;
  color: #0f172a;
}

/* ===== CATATAN RESMI & HALAMAN 2 ===== */
.slip-confidential {
  font-size: 11px;
  color: #64748b;
  text-align: center;
  padding: 10px 16px;
  border-top: 1px dashed #cbd5e1;
  background: #f8fafc;
}
.slip-page-2 {
  background: #ffffff;
}
.page-break-divider {
  border-style: dashed !important;
}

@media print {
  * {
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
  }
  .slip-root {
    border: none !important;
    border-radius: 0 !important;
  }
  .page-break-divider,
  .no-print {
    display: none !important;
  }
  .slip-page-2 {
    page-break-before: always !important;
    break-before: page !important;
  }
}
</style>
