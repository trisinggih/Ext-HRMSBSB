<template>
  <div>
    <CrudPage
      ref="crudPage"
      title="Pengajuan Kasbon"
      singular="Pengajuan"
      endpoint="/loan-requests/admin"
      :creatable="false"
      :disableActions="false"
      :columns="[
        { key: 'employee', label: 'Karyawan', sub: 'name' },
        { key: 'date', label: 'Tanggal Pengajuan' },
        { key: 'amount', label: 'Nominal' },
        { key: 'installment_months', label: 'Cicilan & Tenor' },
        { key: 'repayment_status', label: 'Penarikan di Gaji' },
        { key: 'approval_step', label: 'Tahap Approval' },
        { key: 'reason', label: 'Alasan' },
        { key: 'status', label: 'Status' },
      ]"
      :filters="[
        { key: 'status', label: 'Status', options: [
          { value: 'pending', label: 'Pending' },
          { value: 'in_review', label: 'In Review' },
          { value: 'approved', label: 'Disetujui' },
          { value: 'rejected', label: 'Ditolak' }
        ]}
      ]"
    >
      <template #cell-employee="{ value }">
        <span class="fw-bold text-dark">{{ value || '-' }}</span>
      </template>

      <template #cell-date="{ value, row }">
        <div>
          <span class="fw-semibold text-dark">{{ formatDate(value) }}</span>
          <div v-if="row.created_at" class="text-muted" style="font-size: 11px;">
            Diajukan: {{ formatDateTime(row.created_at) }}
          </div>
        </div>
      </template>

      <template #cell-amount="{ value }">
        <span class="fw-semibold text-success">Rp {{ formatMoney(value) }}</span>
      </template>

      <!-- Kolom Cicilan & Tenor -->
      <template #cell-installment_months="{ row, value }">
        <div>
          <div class="fw-medium text-dark">
            {{ formatTenorText(value) }}
          </div>
          <div class="text-muted small" style="font-size: 11.5px;">
            Rp {{ formatMoney(row.installment_amount || calculateInstallment(row.amount, value)) }}/bln
          </div>
          <button 
            type="button" 
            @click="openInstallmentModal(row)" 
            class="btn btn-link p-0 text-primary fw-medium font-size-11 d-inline-block mt-0.5 text-decoration-none shadow-none"
          >
            Ubah Cicilan
          </button>
        </div>
      </template>

      <!-- Kolom Penarikan di Gaji -->
      <template #cell-repayment_status="{ row }">
        <div>
          <div v-if="row.status === 'approved'">
            <div class="d-flex align-items-center gap-1 font-size-12">
              <span class="text-secondary small">Ditarik:</span>
              <strong class="text-primary">Rp {{ formatMoney(row.total_paid || 0) }}</strong>
            </div>
            <div class="small" :class="(row.remaining_balance || 0) > 0 ? 'text-danger' : 'text-success'" style="font-size: 11.5px;">
              Sisa: Rp {{ formatMoney(row.remaining_balance ?? (row.amount - (row.total_paid || 0))) }}
            </div>
            <button 
              type="button" 
              @click="openRepaymentModal(row)" 
              class="btn btn-link p-0 text-primary fw-medium font-size-11 d-inline-block mt-0.5 text-decoration-none shadow-none"
            >
              Riwayat Penarikan ({{ row.repayments?.length || 0 }})
            </button>
          </div>
          <div v-else-if="row.status === 'rejected'" class="text-muted small">
            <div><span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2 py-0.5 font-size-11">Ditolak</span></div>
            <button 
              type="button" 
              @click="openRepaymentModal(row)" 
              class="btn btn-link p-0 text-secondary fw-medium font-size-11 d-inline-block mt-0.5 text-decoration-none shadow-none"
            >
              Lihat Detail
            </button>
          </div>
          <div v-else>
            <span class="badge bg-light text-muted border font-size-11">Belum Disetujui</span>
            <div class="text-muted mt-0.5" style="font-size: 11px;">
              Estimasi: Rp {{ formatMoney(row.installment_amount || calculateInstallment(row.amount, row.installment_months)) }}/bln
            </div>
            <button 
              type="button" 
              @click="openRepaymentModal(row)" 
              class="btn btn-link p-0 text-primary fw-medium font-size-11 d-inline-block mt-0.5 text-decoration-none shadow-none"
            >
              Rencana Penarikan
            </button>
          </div>
        </div>
      </template>

      <template #cell-approval_step="{ row }">
        <div class="py-1">
          <!-- Case 1: Memiliki Approval Workflow -->
          <div v-if="row.approval?.workflow?.steps?.length">
            <!-- Status Disetujui -->
            <div v-if="row.status === 'approved'">
              <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1 font-size-11">
                Selesai ({{ row.approval.workflow.steps.length }} Tahap)
              </span>
            </div>

            <!-- Status Ditolak -->
            <div v-else-if="row.status === 'rejected'">
              <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2.5 py-1 font-size-11">
                Ditolak pada Tahap {{ row.approval.current_step }}
              </span>
            </div>

            <!-- Status Pending / In Review -->
            <div v-else class="d-flex flex-column gap-1">
              <div>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-0.5 font-size-11">
                  Tahap {{ row.approval.current_step }} dari {{ row.approval.workflow.steps.length }}
                </span>
              </div>
              <div class="small text-muted" style="font-size: 11.5px;">
                Menunggu: <strong class="text-dark">{{ getCurrentApproverName(row) }}</strong>
              </div>
            </div>

            <!-- Tombol Detail Alur Approval -->
            <button 
              type="button" 
              @click="openWorkflowModal(row)" 
              class="btn btn-link p-0 text-primary fw-medium font-size-11 d-inline-block mt-1 text-decoration-none shadow-none"
            >
              Detail Alur ({{ row.approval.workflow.steps.length }} Tahap)
            </button>
          </div>

          <!-- Case 2: Tanpa Approval Workflow (Direct Review HR) -->
          <div v-else>
            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill px-2.5 py-1 font-size-11">
              Review HR Langsung
            </span>
          </div>
        </div>
      </template>

      <template #cell-reason="{ value }">
        <div style="max-width: 180px" class="text-truncate text-muted" :title="value">{{ value }}</div>
      </template>
      
      <template #cell-status="{ value }">
        <span class="badge font-size-12 px-3 py-1 rounded-pill" :class="statusClass(value)">
          {{ statusLabel(value) }}
        </span>
      </template>
      
      <template #actions="{ row }">
        <div class="d-flex align-items-center gap-1 justify-content-end">
          <button 
            v-if="row.status === 'pending' || row.status === 'in_review'" 
            @click="openApprovalModal(row)" 
            class="btn-action-edit" 
            title="Tindak Lanjuti Approval">
            <i class="mdi mdi-checkbox-marked-circle-outline"></i> Tindak Lanjuti
          </button>
          <button 
            @click="openRepaymentModal(row)"
            class="btn-action-detail"
            title="Lihat Riwayat Pembayaran">
            <i class="mdi mdi-history"></i> Riwayat
          </button>
        </div>
      </template>
    </CrudPage>

    <!-- Modal Ubah Cicilan Kasbon -->
    <div v-if="showInstallmentModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px); z-index: 1060; overflow-y: auto;">
      <div class="modal-dialog modal-dialog-centered my-3">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
          <div class="modal-header bg-light border-bottom py-2.5 px-4">
            <div>
              <h5 class="modal-title fw-bold fs-6 text-dark mb-0">Ubah Cicilan Kasbon</h5>
              <div class="text-muted small" style="font-size: 11.5px;">
                {{ selectedInstallmentRequest?.employee?.name }} • Rp {{ formatMoney(selectedInstallmentRequest?.amount) }}
              </div>
            </div>
            <button type="button" class="btn-close shadow-none" @click="showInstallmentModal = false" aria-label="Close"></button>
          </div>
          <div class="modal-body p-3.5" style="max-height: calc(85vh - 110px); overflow-y: auto;">
            
            <div class="p-2.5 bg-light rounded-3 mb-2.5 border">
              <div class="d-flex justify-content-between mb-1 font-size-12">
                <span class="text-secondary">Total Kasbon:</span>
                <span class="fw-bold text-success">Rp {{ formatMoney(selectedInstallmentRequest?.amount) }}</span>
              </div>
              <div class="d-flex justify-content-between mb-1 font-size-12">
                <span class="text-secondary">Tenor Saat Ini:</span>
                <span class="fw-semibold text-dark">{{ formatTenorText(selectedInstallmentRequest?.installment_months) }}</span>
              </div>
              <div class="d-flex justify-content-between font-size-12" v-if="selectedInstallmentRequest?.status === 'approved'">
                <span class="text-secondary">Sisa Tagihan:</span>
                <span class="fw-bold text-danger">Rp {{ formatMoney(selectedInstallmentRequest?.remaining_balance ?? selectedInstallmentRequest?.amount) }}</span>
              </div>
            </div>

            <div class="mb-2.5">
              <label class="form-label fw-semibold text-secondary small mb-1">Tenor Cicilan (Bulan)</label>
              <input 
                type="number" 
                v-model.number="editTenorMonths" 
                @input="handleTenorChange"
                min="1" 
                max="60" 
                class="form-control form-control-sm" 
                placeholder="Contoh: 3"
              />
              <div class="d-flex gap-1 mt-1.5 flex-wrap">
                <button 
                  type="button" 
                  v-for="opt in [1, 2, 3, 4, 6, 10, 12]" 
                  :key="opt"
                  @click="selectQuickTenor(opt)"
                  class="btn btn-sm py-0.5 px-2 rounded-pill font-size-11"
                  :class="editTenorMonths === opt ? 'btn-primary' : 'btn-outline-secondary'"
                >
                  {{ opt }} Bulan
                </button>
              </div>
            </div>

            <div class="mb-2.5">
              <label class="form-label fw-semibold text-secondary small mb-1">Nominal Cicilan per Bulan (Rp)</label>
              <input 
                type="number" 
                v-model.number="editInstallmentAmount" 
                min="0" 
                class="form-control form-control-sm" 
                placeholder="Nominal cicilan bulanan"
              />
              <div class="text-muted small mt-1" style="font-size: 11px;">
                Dihitung: Rp {{ formatMoney(editInstallmentAmount) }} x {{ editTenorMonths }} bulan
              </div>
            </div>

            <div class="mb-2">
              <label class="form-label fw-semibold text-secondary small mb-1">Catatan Perubahan (Opsional)</label>
              <textarea 
                v-model="editInstallmentNote" 
                rows="2" 
                class="form-control form-control-sm" 
                placeholder="Alasan perubahan cicilan atau kesepakatan dengan karyawan..."
              ></textarea>
            </div>

          </div>
          <div class="modal-footer bg-light border-top py-2 px-4 d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-light btn-sm px-3 fw-medium" @click="showInstallmentModal = false">Batal</button>
            <button 
              type="button" 
              class="btn btn-primary btn-sm px-4 fw-medium" 
              @click="saveInstallment" 
              :disabled="savingInstallment"
            >
              {{ savingInstallment ? 'Menyimpan...' : 'Simpan Perubahan' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Riwayat Pembayaran (Ditarik di Gaji) -->
    <div v-if="showRepaymentModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px);">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
          <div class="modal-header bg-light border-bottom py-3 px-4">
            <div>
              <h5 class="modal-title fw-bold fs-5 text-dark mb-0">Riwayat Pembayaran Kasbon (Ditarik di Gaji)</h5>
              <div class="text-muted small mt-0.5">
                {{ selectedRepaymentRequest?.employee?.name }} • Nominal Pinjaman: Rp {{ formatMoney(selectedRepaymentRequest?.amount) }}
              </div>
            </div>
            <button type="button" class="btn-close shadow-none" @click="showRepaymentModal = false" aria-label="Close"></button>
          </div>
          <div class="modal-body p-4" style="max-height: 75vh; overflow-y: auto;">
            
            <!-- Ringkasan Finansial Kasbon -->
            <div class="p-3 bg-light rounded-3 mb-4 border">
              <div class="row g-3">
                <div class="col-sm-6 col-md-3">
                  <span class="text-secondary small d-block">Total Kasbon</span>
                  <span class="fw-bold text-success fs-6">Rp {{ formatMoney(selectedRepaymentRequest?.amount) }}</span>
                </div>
                <div class="col-sm-6 col-md-3">
                  <span class="text-secondary small d-block">Tenor & Cicilan</span>
                  <span class="fw-semibold text-dark">
                    {{ formatTenorText(selectedRepaymentRequest?.installment_months) }}
                  </span>
                  <div class="text-muted small" style="font-size: 11px;">
                    Rp {{ formatMoney(selectedRepaymentRequest?.installment_amount || calculateInstallment(selectedRepaymentRequest?.amount, selectedRepaymentRequest?.installment_months)) }}/bln
                  </div>
                </div>
                <div class="col-sm-6 col-md-3">
                  <span class="text-secondary small d-block">Sudah Ditarik di Gaji</span>
                  <span class="fw-bold text-primary fs-6">
                    Rp {{ formatMoney(selectedRepaymentRequest?.total_paid || 0) }}
                  </span>
                </div>
                <div class="col-sm-6 col-md-3">
                  <span class="text-secondary small d-block">Sisa Tagihan</span>
                  <span class="fw-bold text-danger fs-6">
                    Rp {{ formatMoney(selectedRepaymentRequest?.remaining_balance ?? (selectedRepaymentRequest?.amount - (selectedRepaymentRequest?.total_paid || 0))) }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Tabel Riwayat Penarikan Gaji -->
            <div>
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold text-dark mb-0">
                  Daftar Penarikan Melalui Pemotongan Payroll
                </h6>
                <button 
                  type="button" 
                  @click="openInstallmentModal(selectedRepaymentRequest)" 
                  class="btn btn-outline-primary btn-sm py-1 px-2.5 font-size-11"
                >
                  Ubah Cicilan
                </button>
              </div>

              <div v-if="selectedRepaymentRequest?.repayments?.length" class="table-responsive border rounded-3">
                <table class="table table-sm table-hover mb-0">
                  <thead class="table-light">
                    <tr>
                      <th class="ps-3 py-2 font-size-12" style="width: 50px;">No.</th>
                      <th class="py-2 font-size-12">Periode Payroll</th>
                      <th class="py-2 font-size-12">Tanggal Potongan</th>
                      <th class="py-2 font-size-12 text-end">Potongan Ditarik</th>
                      <th class="py-2 font-size-12 text-center pe-3">Status Gaji</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(item, idx) in selectedRepaymentRequest.repayments" :key="item.id || idx">
                      <td class="ps-3 py-2 text-muted small">{{ idx + 1 }}</td>
                      <td class="py-2">
                        <span class="fw-semibold text-dark small d-block">{{ item.period_name || 'Payroll' }}</span>
                        <span v-if="item.period_code" class="text-muted" style="font-size: 11px;">{{ item.period_code }}</span>
                      </td>
                      <td class="py-2 text-muted small text-nowrap">{{ formatDate(item.paid_at) }}</td>
                      <td class="py-2 text-end fw-bold text-primary small">
                        Rp {{ formatMoney(item.deduction_amount) }}
                      </td>
                      <td class="py-2 text-center pe-3">
                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-0.5 font-size-11">
                          {{ item.status === 'paid' ? 'Lunas Dibayar' : 'Diproses' }}
                        </span>
                      </td>
                    </tr>
                  </tbody>
                  <tfoot class="table-light">
                    <tr>
                      <th colspan="3" class="ps-3 py-2 font-size-12 text-end fw-bold">Total Ditarik:</th>
                      <th class="py-2 text-end font-size-12 fw-bold text-primary">
                        Rp {{ formatMoney(selectedRepaymentRequest.total_paid || 0) }}
                      </th>
                      <th></th>
                    </tr>
                  </tfoot>
                </table>
              </div>
              <div v-else class="p-4 bg-light rounded-3 text-center border">
                <div class="fw-semibold text-dark mb-1">Belum Ada Penarikan Gaji</div>
                <div class="text-muted small" style="max-width: 450px; margin: 0 auto;">
                  Kasbon ini belum pernah ditarik melalui proses payroll bulanan. Potongan cicilan sebesar 
                  <strong>Rp {{ formatMoney(selectedRepaymentRequest?.installment_amount || calculateInstallment(selectedRepaymentRequest?.amount, selectedRepaymentRequest?.installment_months)) }}/bulan</strong> 
                  akan otomatis dipotong saat periode payroll bulanan dijalankan.
                </div>
              </div>
            </div>

          </div>
          <div class="modal-footer bg-light border-top py-2 px-4 d-flex justify-content-end">
            <button type="button" class="btn btn-light btn-sm px-4 fw-medium" @click="showRepaymentModal = false">Tutup</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Detail Alur & Timeline Approval -->
    <div v-if="showWorkflowModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px);">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
          <div class="modal-header bg-light border-bottom py-3 px-4">
            <div>
              <h5 class="modal-title fw-bold fs-5 text-dark mb-0">Informasi Alur Approval Kasbon</h5>
              <div class="text-muted small mt-0.5">
                {{ selectedWorkflowRequest?.employee?.name }} • Rp {{ formatMoney(selectedWorkflowRequest?.amount) }} • Diajukan: {{ formatDate(selectedWorkflowRequest?.date) }}
              </div>
            </div>
            <button type="button" class="btn-close shadow-none" @click="showWorkflowModal = false" aria-label="Close"></button>
          </div>
          <div class="modal-body p-4" style="max-height: 75vh; overflow-y: auto;">
            
            <!-- Summary Header Card -->
            <div class="p-3 bg-light rounded-3 mb-4 border">
              <div class="row g-2">
                <div class="col-sm-6 col-md-3">
                  <span class="text-secondary small d-block">Karyawan</span>
                  <span class="fw-bold text-dark">{{ selectedWorkflowRequest?.employee?.name || '-' }}</span>
                </div>
                <div class="col-sm-6 col-md-3">
                  <span class="text-secondary small d-block">Nominal Kasbon</span>
                  <span class="fw-bold text-success">Rp {{ formatMoney(selectedWorkflowRequest?.amount) }}</span>
                </div>
                <div class="col-sm-6 col-md-3">
                  <span class="text-secondary small d-block">Tenor</span>
                  <span class="fw-semibold text-dark">{{ formatTenorText(selectedWorkflowRequest?.installment_months) }}</span>
                </div>
                <div class="col-sm-6 col-md-3">
                  <span class="text-secondary small d-block">Status Pengajuan</span>
                  <span class="badge font-size-11 px-2.5 py-1 rounded-pill" :class="statusClass(selectedWorkflowRequest?.status)">
                    {{ statusLabel(selectedWorkflowRequest?.status) }}
                  </span>
                </div>
                <div class="col-12 mt-2 pt-2 border-top">
                  <span class="text-secondary small d-block">Alasan Pengajuan</span>
                  <span class="text-dark">{{ selectedWorkflowRequest?.reason || '-' }}</span>
                </div>
              </div>
            </div>

            <!-- Tahapan Approval Workflow (Stepper) -->
            <div class="mb-4">
              <h6 class="fw-bold text-dark mb-3">
                Tahapan Approval ({{ selectedWorkflowRequest?.approval?.workflow?.steps?.length || 0 }} Tahap)
              </h6>

              <div v-if="selectedWorkflowRequest?.approval?.workflow?.steps?.length" class="d-flex flex-column gap-2">
                <div 
                  v-for="step in selectedWorkflowRequest.approval.workflow.steps" 
                  :key="step.id"
                  class="p-3 rounded-3 border d-flex align-items-center justify-content-between"
                  :class="getStepBoxClass(step, selectedWorkflowRequest)"
                >
                  <div class="d-flex align-items-center gap-3">
                    <div 
                      class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold shadow-sm"
                      style="width: 34px; height: 34px;"
                      :class="getStepBadgeClass(step, selectedWorkflowRequest)"
                    >
                      <span>{{ step.order }}</span>
                    </div>
                    <div>
                      <div class="fw-bold text-dark">
                        Tahap {{ step.order }}: {{ step.approver_name || step.approver_employee?.name || 'Approver' }}
                      </div>
                      <div class="text-muted small">
                        Peran: {{ getApproverTypeLabel(step.approver_type) }}
                        <span v-if="step.is_optional" class="badge bg-secondary-subtle text-secondary ms-1">Opsional</span>
                      </div>
                    </div>
                  </div>

                  <div>
                    <span class="badge rounded-pill px-3 py-1.5 font-size-12" :class="getStepStatusBadgeClass(step, selectedWorkflowRequest)">
                      {{ getStepStatusText(step, selectedWorkflowRequest) }}
                    </span>
                  </div>
                </div>
              </div>
              <div v-else class="text-muted small p-3 bg-light rounded-3 text-center">
                Pengajuan ini tidak menggunakan alur bertingkat (Review HR Langsung).
              </div>
            </div>

            <!-- Log & Riwayat Persetujuan -->
            <div>
              <h6 class="fw-bold text-dark mb-3">
                Riwayat Aktivitas & Catatan
              </h6>

              <div v-if="selectedWorkflowRequest?.approval?.logs?.length" class="table-responsive border rounded-3">
                <table class="table table-sm table-hover mb-0">
                  <thead class="table-light">
                    <tr>
                      <th class="ps-3 py-2 font-size-12">Waktu</th>
                      <th class="py-2 font-size-12">Aktor / Approver</th>
                      <th class="py-2 font-size-12">Aksi</th>
                      <th class="py-2 font-size-12">Catatan</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="log in selectedWorkflowRequest.approval.logs" :key="log.id">
                      <td class="ps-3 py-2 text-muted small text-nowrap">{{ formatDateTime(log.created_at) }}</td>
                      <td class="py-2 fw-semibold text-dark small">{{ log.approver_name || log.admin?.name || '-' }}</td>
                      <td class="py-2">
                        <span class="badge font-size-11 px-2 py-0.5 rounded-pill" :class="getLogActionClass(log.action)">
                          {{ getLogActionLabel(log.action) }}
                        </span>
                      </td>
                      <td class="py-2 text-dark small">{{ log.note || '-' }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div v-else class="text-muted small p-3 bg-light rounded-3 text-center">
                Belum ada catatan riwayat aktivitas.
              </div>
            </div>

          </div>
          <div class="modal-footer bg-light border-top py-2 px-4 d-flex justify-content-between">
            <div>
              <button 
                v-if="selectedWorkflowRequest?.status === 'pending' || selectedWorkflowRequest?.status === 'in_review'"
                @click="openApprovalFromWorkflow"
                class="btn btn-primary btn-sm px-3 waves-effect waves-light"
              >
                Tindak Lanjuti Sekarang
              </button>
            </div>
            <button type="button" class="btn btn-light btn-sm px-4 fw-medium" @click="showWorkflowModal = false">Tutup</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Approval Modal (Tindak Lanjuti) -->
    <div v-if="showModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); overflow-y: auto;">
      <div class="modal-dialog modal-dialog-centered my-3">
        <div class="modal-content border-0 shadow-lg rounded-4">
          <div class="modal-header border-bottom-0 pt-3.5 px-4 pb-2">
            <h5 class="modal-title fw-bold fs-5 text-dark">Tindak Lanjuti Kasbon</h5>
            <button type="button" class="btn-close shadow-none" @click="showModal = false" aria-label="Close"></button>
          </div>
          <div class="modal-body px-4 pb-4 pt-2" style="max-height: calc(85vh - 110px); overflow-y: auto;">
            
            <!-- Info Approval Step Saat Ini -->
            <div v-if="selectedRequest?.approval?.workflow?.steps?.length" class="alert alert-info py-2 px-3 mb-3 rounded-3 small border-0 bg-info-subtle text-info-emphasis">
              <div class="fw-bold mb-1">
                Tahap Approval Saat Ini:
              </div>
              <div>
                Tahap <strong>{{ selectedRequest.approval.current_step }}</strong> dari <strong>{{ selectedRequest.approval.workflow.steps.length }}</strong> 
                (Approver: <strong>{{ getCurrentApproverName(selectedRequest) }}</strong>)
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold text-secondary small mb-1">Karyawan</label>
              <div class="form-control bg-light border-0 px-3 py-2 text-dark fw-bold">{{ selectedRequest?.employee?.name }}</div>
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold text-secondary small mb-1">Tanggal Pengajuan</label>
              <div class="form-control bg-light border-0 px-3 py-2 text-dark">{{ formatDate(selectedRequest?.date) }}</div>
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold text-secondary small mb-1">Nominal</label>
              <div class="form-control bg-light border-0 px-3 py-2 text-success fw-bold">Rp {{ formatMoney(selectedRequest?.amount) }}</div>
            </div>

            <!-- Penyesuaian Tenor Cicilan Saat Approval -->
            <div class="mb-3">
              <label class="form-label fw-semibold text-secondary small mb-1">Tenor Cicilan (Bulan)</label>
              <div class="input-group">
                <input 
                  type="number" 
                  v-model.number="approvalTenor" 
                  min="1" 
                  max="60" 
                  class="form-control bg-white" 
                  placeholder="Tenor dalam bulan"
                />
                <span class="input-group-text bg-light text-muted">Bulan</span>
              </div>
              <div class="text-muted small mt-1" style="font-size: 11px;">
                Estimasi Cicilan per Bulan: <strong class="text-primary">Rp {{ formatMoney(calculateInstallment(selectedRequest?.amount, approvalTenor)) }}</strong>
              </div>
            </div>

            <div class="mb-4">
              <label class="form-label fw-semibold text-secondary small mb-1">Catatan HR (Opsional)</label>
              <textarea v-model="approvalNote" class="form-control bg-light border-0 px-3 py-2 shadow-none rounded-3" rows="3" placeholder="Masukkan catatan persetujuan atau penolakan..."></textarea>
            </div>
            <div class="d-flex justify-content-end gap-2">
              <button type="button" class="btn btn-light waves-effect shadow-none text-muted fw-medium px-4 py-2" @click="showModal = false">Batal</button>
              <button type="button" class="btn btn-danger waves-effect waves-light shadow-none fw-medium px-4 py-2" @click="rejectRequest" :disabled="submitting">
                Tolak
              </button>
              <button type="button" class="btn btn-success waves-effect waves-light shadow-none fw-medium px-4 py-2" @click="approveRequest" :disabled="submitting">
                Setujui
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import CrudPage from '../components/CrudPage.vue'
import api from '../api'
import { toast } from 'vue3-toastify'

const crudPage = ref(null)

const showModal = ref(false)
const showWorkflowModal = ref(false)
const showInstallmentModal = ref(false)
const showRepaymentModal = ref(false)

const selectedRequest = ref(null)
const selectedWorkflowRequest = ref(null)
const selectedInstallmentRequest = ref(null)
const selectedRepaymentRequest = ref(null)

const approvalNote = ref('')
const approvalTenor = ref(1)
const submitting = ref(false)

// State ubah cicilan
const editTenorMonths = ref(1)
const editInstallmentAmount = ref(0)
const editInstallmentNote = ref('')
const savingInstallment = ref(false)

const formatMoney = (v) => {
  if (!v) return '0'
  return Math.round(parseFloat(v)).toLocaleString('id-ID')
}

const formatTenorText = (val) => {
  const m = Number(val) || 0
  if (m <= 1) return '1 Bulan (Lunas)'
  return `${m} Bulan`
}

const calculateInstallment = (amount, tenor) => {
  const a = parseFloat(amount) || 0
  const t = Math.max(1, parseInt(tenor) || 1)
  return Math.round(a / t)
}

const formatDate = (val) => {
  if (!val) return '-'
  let d
  if (typeof val === 'string' && val.includes('T')) {
    d = new Date(val)
  } else if (typeof val === 'string' && /^\d{4}-\d{2}-\d{2}/.test(val)) {
    const parts = val.substring(0, 10).split('-')
    d = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]))
  } else {
    d = new Date(val)
  }
  if (isNaN(d.getTime())) return val
  const day = String(d.getDate()).padStart(2, '0')
  const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des']
  return `${day} ${months[d.getMonth()]} ${d.getFullYear()}`
}

const formatDateTime = (val) => {
  if (!val) return '-'
  const d = new Date(val)
  if (isNaN(d.getTime())) return val
  const day = String(d.getDate()).padStart(2, '0')
  const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des']
  const hours = String(d.getHours()).padStart(2, '0')
  const minutes = String(d.getMinutes()).padStart(2, '0')
  return `${day} ${months[d.getMonth()]} ${d.getFullYear()}, ${hours}:${minutes}`
}

const statusClass = (s) => ({
  pending: 'bg-warning-subtle text-warning',
  in_review: 'bg-info-subtle text-info',
  approved: 'bg-success-subtle text-success',
  rejected: 'bg-danger-subtle text-danger',
}[s] || 'bg-light text-dark')

const statusLabel = (s) => ({
  pending: 'Pending',
  in_review: 'In Review',
  approved: 'Disetujui',
  rejected: 'Ditolak',
}[s] || s || '-')

const getApprovalSteps = (row) => {
  return row?.approval?.workflow?.steps || []
}

const getCurrentApproverName = (row) => {
  if (!row?.approval) return 'Review HR Langsung'
  const steps = getApprovalSteps(row)
  if (!steps.length) return 'Review HR Langsung'
  const currentStep = row.approval.current_step || 1
  const activeStep = steps.find(s => s.order === currentStep)
  if (activeStep) {
    return activeStep.approver_name || activeStep.approver_employee?.name || `Tahap ${currentStep}`
  }
  return 'Review HR Langsung'
}

const getApproverTypeLabel = (type) => ({
  supervisor: 'Atasan Langsung',
  hr: 'HRD',
  specific_user: 'Pegawai Tertentu',
}[type] || type || 'Approver')

const isStepPassed = (step, request) => {
  if (!request?.approval) return false
  if (request.status === 'approved') return true
  return step.order < request.approval.current_step
}

const isStepRejected = (step, request) => {
  if (!request?.approval) return false
  return request.status === 'rejected' && step.order === request.approval.current_step
}

const isStepCurrent = (step, request) => {
  if (!request?.approval) return false
  if (request.status === 'approved' || request.status === 'rejected') return false
  return step.order === request.approval.current_step
}

const getStepBoxClass = (step, request) => {
  if (isStepPassed(step, request)) return 'bg-success-subtle border-success-subtle'
  if (isStepRejected(step, request)) return 'bg-danger-subtle border-danger-subtle'
  if (isStepCurrent(step, request)) return 'bg-primary-subtle border-primary-subtle shadow-sm'
  return 'bg-white border-light-subtle'
}

const getStepBadgeClass = (step, request) => {
  if (isStepPassed(step, request)) return 'bg-success'
  if (isStepRejected(step, request)) return 'bg-danger'
  if (isStepCurrent(step, request)) return 'bg-primary'
  return 'bg-secondary'
}

const getStepStatusBadgeClass = (step, request) => {
  if (isStepPassed(step, request)) return 'bg-success text-white'
  if (isStepRejected(step, request)) return 'bg-danger text-white'
  if (isStepCurrent(step, request)) return 'bg-primary text-white'
  return 'bg-light text-muted border'
}

const getStepStatusText = (step, request) => {
  if (isStepPassed(step, request)) return 'Disetujui'
  if (isStepRejected(step, request)) return 'Ditolak di tahap ini'
  if (isStepCurrent(step, request)) return 'Sedang Menunggu Review'
  return 'Menunggu Giliran'
}

const getLogActionClass = (action) => ({
  submit: 'bg-info-subtle text-info',
  approve: 'bg-success-subtle text-success',
  reject: 'bg-danger-subtle text-danger',
  revise: 'bg-warning-subtle text-warning',
  update_installment: 'bg-warning-subtle text-warning',
}[action] || 'bg-light text-dark')

const getLogActionLabel = (action) => ({
  submit: 'Pengajuan Dibuat',
  approve: 'Disetujui',
  reject: 'Ditolak',
  revise: 'Cicilan/Tenor Direvisi',
  update_installment: 'Cicilan Diubah',
}[action] || action || '-')

const openApprovalModal = (row) => {
  selectedRequest.value = row
  approvalNote.value = ''
  approvalTenor.value = Math.max(1, parseInt(row.installment_months) || 1)
  showModal.value = true
}

const openWorkflowModal = (row) => {
  selectedWorkflowRequest.value = row
  showWorkflowModal.value = true
}

const openApprovalFromWorkflow = () => {
  if (!selectedWorkflowRequest.value) return
  const target = selectedWorkflowRequest.value
  showWorkflowModal.value = false
  openApprovalModal(target)
}

const openInstallmentModal = (row) => {
  selectedInstallmentRequest.value = row
  editTenorMonths.value = Math.max(1, parseInt(row.installment_months) || 1)
  editInstallmentAmount.value = row.installment_amount || calculateInstallment(row.amount, editTenorMonths.value)
  editInstallmentNote.value = ''
  showInstallmentModal.value = true
}

const selectQuickTenor = (tenor) => {
  editTenorMonths.value = tenor
  handleTenorChange()
}

const handleTenorChange = () => {
  if (!selectedInstallmentRequest.value) return
  const amount = parseFloat(selectedInstallmentRequest.value.amount) || 0
  const tenor = Math.max(1, parseInt(editTenorMonths.value) || 1)
  editInstallmentAmount.value = Math.round(amount / tenor)
}

const saveInstallment = async () => {
  if (!selectedInstallmentRequest.value) return
  try {
    savingInstallment.value = true
    const res = await api.put(`/loan-requests/admin/${selectedInstallmentRequest.value.id}/installments`, {
      installment_months: editTenorMonths.value,
      installment_amount: editInstallmentAmount.value,
      note: editInstallmentNote.value,
    })
    toast.success('Cicilan kasbon berhasil diperbarui.')
    showInstallmentModal.value = false
    if (crudPage.value?.load) {
      crudPage.value.load()
    }
    if (showRepaymentModal.value && selectedRepaymentRequest.value?.id === selectedInstallmentRequest.value.id) {
      await openRepaymentModal({
        ...selectedRepaymentRequest.value,
        installment_months: editTenorMonths.value,
        installment_amount: editInstallmentAmount.value,
      })
    }
  } catch (err) {
    toast.error(err.response?.data?.message || 'Gagal mengubah cicilan kasbon.')
  } finally {
    savingInstallment.value = false
  }
}

const openRepaymentModal = async (row) => {
  selectedRepaymentRequest.value = row
  showRepaymentModal.value = true

  // Fetch updated repayments data dari server jika diperlukan
  try {
    const res = await api.get(`/loan-requests/admin/${row.id}/repayments`)
    if (res.data?.data) {
      selectedRepaymentRequest.value = {
        ...row,
        ...res.data.data
      }
    }
  } catch (err) {
    console.error('Error fetching repayments:', err)
  }
}

const approveRequest = async () => {
  try {
    submitting.value = true
    await api.post(`/loan-requests/admin/${selectedRequest.value.id}/approve`, {
      note: approvalNote.value || 'Disetujui oleh HR',
      installment_months: approvalTenor.value,
    })
    toast.success('Pengajuan kasbon berhasil disetujui.')
    showModal.value = false
    crudPage.value.load()
  } catch (err) {
    toast.error(err.response?.data?.message || 'Gagal menyetujui pengajuan.')
  } finally {
    submitting.value = false
  }
}

const rejectRequest = async () => {
  try {
    submitting.value = true
    await api.post(`/loan-requests/admin/${selectedRequest.value.id}/reject`, {
      note: approvalNote.value || 'Ditolak oleh HR'
    })
    toast.success('Pengajuan kasbon berhasil ditolak.')
    showModal.value = false
    crudPage.value.load()
  } catch (err) {
    toast.error(err.response?.data?.message || 'Gagal menolak pengajuan.')
  } finally {
    submitting.value = false
  }
}
</script>


