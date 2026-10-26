<template>
  <div class="container-fluid py-3">
    <!-- Header Page -->
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h4 class="mb-1 text-primary font-weight-bold">
          <i class="mdi mdi-account-star me-2"></i>Penilaian KPI & Permohonan Kenaikan Gaji (Probation)
        </h4>
        <p class="text-muted small mb-0">Evaluasi karyawan masa percobaan & permohonan kenaikan status/gaji sesuai standar PEBYMOTOR</p>
      </div>
      <div>
        <button class="btn btn-primary" @click="openEvaluationModal()">
          <i class="mdi mdi-plus me-1"></i> Tambah Penilaian Probation
        </button>
      </div>
    </div>

    <!-- Filter Card -->
    <div class="card shadow-sm border-0 mb-3">
      <div class="card-body p-3">
        <div class="row g-2 align-items-center">
          <div class="col-md-4">
            <div class="input-group input-group-sm">
              <span class="input-group-text"><i class="mdi mdi-magnify"></i></span>
              <input v-model="searchQuery" type="text" class="form-control" placeholder="Cari Nama / NIK..." @keyup.enter="fetchEvaluations" />
            </div>
          </div>
          <div class="col-md-3">
            <select v-model="decisionFilter" class="form-select form-select-sm" @change="fetchEvaluations">
              <option value="">Semua Keputusan Akhir</option>
              <option value="passed_permanent">Diangkat Karyawan Tetap</option>
              <option value="extend_probation">Perpanjang Probation</option>
              <option value="terminated">Diberhentikan</option>
            </select>
          </div>
          <div class="col-md-3">
            <select v-model="statusFilter" class="form-select form-select-sm" @change="fetchEvaluations">
              <option value="">Semua Status Approval</option>
              <option value="draft">Draft</option>
              <option value="submitted">Submitted</option>
              <option value="approved">Approved</option>
            </select>
          </div>
          <div class="col-md-2 text-end">
            <button class="btn btn-sm btn-outline-secondary w-100" @click="resetFilters">Reset</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Evaluations Table -->
    <div class="card shadow-sm border-0">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th style="width: 50px;">No</th>
                <th>Karyawan</th>
                <th>Jabatan / Dept</th>
                <th>Masa Probation</th>
                <th>Skor Final (1-100)</th>
                <th>Keputusan Akhir</th>
                <th>Status Approval</th>
                <th style="width: 220px;" class="text-center">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="loading">
                <td colspan="8" class="text-center py-4">
                  <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                  <span class="ms-2">Memuat data penilaian probation...</span>
                </td>
              </tr>
              <tr v-else-if="evaluations.length === 0">
                <td colspan="8" class="text-center py-4 text-muted">Belum ada data penilaian probation.</td>
              </tr>
              <tr v-for="(item, index) in evaluations" :key="item.id">
                <td>{{ (pagination.current_page - 1) * pagination.per_page + index + 1 }}</td>
                <td>
                  <div class="fw-bold">{{ item.employee?.name }}</div>
                  <small class="text-muted">NIK: {{ item.employee?.nip || item.employee?.employee_id || '-' }}</small>
                </td>
                <td>
                  <div>{{ item.employee?.primary_assignment?.position?.name || '-' }}</div>
                  <small class="text-muted">{{ item.employee?.primary_assignment?.department?.name || '-' }}</small>
                </td>
                <td>
                  <div>{{ formatDate(item.probation_start_date) }}</div>
                  <small class="badge bg-light text-dark border">{{ item.probation_period_text || '3 Bulan' }}</small>
                </td>
                <td>
                  <div class="fw-bold fs-6" :class="scoreColor(item.final_score_hundred)">
                    {{ parseFloat(item.final_score_hundred || 0).toFixed(1) }}
                  </div>
                  <small class="badge bg-soft-info text-info">{{ item.predicate || '-' }}</small>
                </td>
                <td>
                  <span class="badge" :class="decisionBadgeClass(item.final_decision)">
                    {{ decisionLabel(item.final_decision) }}
                  </span>
                </td>
                <td>
                  <span class="badge" :class="statusBadgeClass(item.status)">
                    {{ item.status ? item.status.toUpperCase() : 'DRAFT' }}
                  </span>
                </td>
                <td class="text-center">
                  <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-primary" title="Edit Form Penilaian" @click="editEvaluation(item)">
                      <i class="mdi mdi-pencil"></i> Evaluasi
                    </button>
                    <button class="btn btn-outline-success" title="Form Kenaikan Gaji" @click="openSalaryProposalModal(item)">
                      <i class="mdi mdi-cash-multiple"></i> Gaji
                    </button>
                    <button class="btn btn-outline-danger" title="Download PDF Penilaian" @click="downloadEvaluationPdf(item)">
                      <i class="mdi mdi-file-pdf-box"></i>
                    </button>
                    <button v-if="item.salary_proposal" class="btn btn-outline-info" title="Download PDF Kenaikan Gaji" @click="downloadSalaryPdf(item)">
                      <i class="mdi mdi-file-document-outline"></i>
                    </button>
                    <button class="btn btn-outline-dark" title="Hapus" @click="deleteEvaluation(item)">
                      <i class="mdi mdi-trash-can"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="pagination.total > 0" class="card-footer bg-white border-0 d-flex justify-content-between align-items-center py-2">
        <small class="text-muted">Total {{ pagination.total }} data</small>
        <nav>
          <ul class="pagination pagination-sm mb-0">
            <li class="page-item" :class="{ disabled: pagination.current_page === 1 }">
              <button class="page-link" @click="changePage(pagination.current_page - 1)">Prev</button>
            </li>
            <li class="page-item active"><span class="page-link">{{ pagination.current_page }}</span></li>
            <li class="page-item" :class="{ disabled: pagination.current_page === pagination.last_page }">
              <button class="page-link" @click="changePage(pagination.current_page + 1)">Next</button>
            </li>
          </ul>
        </nav>
      </div>
    </div>

    <!-- MODAL 1: Form Penilaian Karyawan Probation -->
    <div class="modal fade" id="evaluationModal" tabindex="-1" aria-hidden="true" ref="evaluationModalRef">
      <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header bg-primary text-white py-2">
            <h5 class="modal-title fs-6">
              <i class="mdi mdi-file-document-edit me-1"></i> Form Penilaian Karyawan Probation (PEBYMOTOR)
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="saveEvaluation">
              <!-- Section 1: Data Karyawan -->
              <div class="row g-3 mb-3 bg-light p-3 rounded border">
                <div class="col-12"><h6 class="fw-bold text-primary mb-0"><i class="mdi mdi-account-id me-1"></i> Data Karyawan & Penilaian</h6></div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold">Pilih Karyawan <span class="text-danger">*</span></label>
                  <select v-model="form.employee_id" class="form-select form-select-sm" :disabled="isEdit" @change="onEmployeeSelect" required>
                    <option value="">-- Pilih Karyawan --</option>
                    <option v-for="emp in employeesList" :key="emp.id" :value="emp.id">
                      {{ emp.name }} ({{ emp.nip || emp.employee_id || 'No NIP' }})
                    </option>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold">Atasan Langsung (Evaluator)</label>
                  <select v-model="form.evaluator_id" class="form-select form-select-sm">
                    <option value="">-- Pilih Atasan --</option>
                    <option v-for="emp in employeesList" :key="emp.id" :value="emp.id">{{ emp.name }}</option>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold">Tanggal Penilaian</label>
                  <input v-model="form.evaluation_date" type="date" class="form-control form-control-sm" @change="onPeriodDateChange" required />
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold">Tgl Mulai Bekerja (Join)</label>
                  <input v-model="form.probation_start_date" type="date" class="form-control form-control-sm" @change="onPeriodDateChange" />
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold">Masa Probation</label>
                  <input v-model="form.probation_period_text" type="text" class="form-control form-control-sm" placeholder="cth: 3 Bulan" />
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold">Status Approval</label>
                  <select v-model="form.status" class="form-select form-select-sm">
                    <option value="draft">Draft</option>
                    <option value="submitted">Submitted</option>
                    <option value="approved">Approved</option>
                  </select>
                </div>
              </div>

              <!-- Section 2: Rekap Absensi & Catatan Pelanggaran -->
              <div class="row g-3 mb-3">
                <div class="col-md-7">
                  <div class="card border h-100">
                    <div class="card-header bg-secondary text-white py-1 px-3 small fw-bold d-flex justify-content-between align-items-center">
                      <span><i class="mdi mdi-calendar-clock me-1"></i>Periode Absensi Karyawan</span>
                      <button type="button" class="btn btn-xs btn-outline-light py-0 px-2" :disabled="!form.employee_id || loadingSummary" @click="() => fetchAttendanceSummary(false)">
                        <i class="mdi mdi-refresh me-1" :class="{ 'mdi-spin': loadingSummary }"></i>Tarik Ulang Absensi & SP
                      </button>
                    </div>
                    <div class="card-body p-2">
                      <div class="d-flex align-items-center justify-content-between px-1 mb-2">
                        <small class="text-muted" style="font-size: 0.75rem;">
                          <i class="mdi mdi-information-outline me-1"></i>Ditarik dari tanggal join <strong>{{ form.probation_start_date || 'Awal Join' }}</strong> s/d <strong>{{ form.evaluation_date || 'Hari ini' }}</strong>
                        </small>
                      </div>
                      <div class="row g-2 text-center">
                        <div class="col">
                          <label class="small text-muted d-block">Alpa</label>
                          <input v-model.number="form.absent_days" type="number" min="0" class="form-control form-control-sm text-center" :class="{ 'border-danger text-danger fw-bold': form.absent_days > 0 }" />
                        </div>
                        <div class="col">
                          <label class="small text-muted d-block">Ijin</label>
                          <input v-model.number="form.permit_days" type="number" min="0" class="form-control form-control-sm text-center" />
                        </div>
                        <div class="col">
                          <label class="small text-muted d-block">Surat Dokter</label>
                          <input v-model.number="form.sick_days" type="number" min="0" class="form-control form-control-sm text-center" />
                        </div>
                        <div class="col">
                          <label class="small text-muted d-block">Pulang ½ hari</label>
                          <input v-model.number="form.half_days" type="number" min="0" class="form-control form-control-sm text-center" />
                        </div>
                        <div class="col">
                          <label class="small text-muted d-block">Pulang Kesiangan</label>
                          <input v-model.number="form.late_days" type="number" min="0" class="form-control form-control-sm text-center" />
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="col-md-5">
                  <div class="card border h-100">
                    <div class="card-header bg-secondary text-white py-1 small fw-bold">Catatan Pelanggaran [ √ ]</div>
                    <div class="card-body p-2">
                      <div class="small text-muted mb-2" style="font-size: 0.75rem;">
                        <i class="mdi mdi-alert-circle-outline me-1"></i>Otomatis dicek dari SP di periode join s/d penilaian
                      </div>
                      <div class="form-check mb-1">
                        <input v-model="form.has_oral_warning" type="checkbox" class="form-check-input" id="oralWarn" />
                        <label class="form-check-label small" for="oralWarn">Peringatan Lisan</label>
                      </div>
                      <div class="form-check mb-1">
                        <input v-model="form.has_sp1" type="checkbox" class="form-check-input" id="sp1" />
                        <label class="form-check-label small" for="sp1">Surat Peringatan 1 (SP-1)</label>
                      </div>
                      <div class="form-check mb-1">
                        <input v-model="form.has_sp2" type="checkbox" class="form-check-input" id="sp2" />
                        <label class="form-check-label small" for="sp2">Surat Peringatan 2 (SP-2)</label>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Section 3: Tabel Penilaian 10 Aspek -->
              <div class="card border mb-3">
                <div class="card-header bg-dark text-white py-1 d-flex justify-content-between align-items-center">
                  <span class="small fw-bold">Tabel Penilaian (10 Aspek Baku PEBYMOTOR)</span>
                  <span class="badge bg-warning text-dark">Total Skor: {{ computedTotalScore }} / 500 (Skala 100: {{ computedScoreHundred }} - {{ computedPredicate }})</span>
                </div>
                <div class="card-body p-0">
                  <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0" style="font-size: 12px;">
                      <thead class="table-light text-center">
                        <tr>
                          <th style="width: 40%;">Aspek Penilaian</th>
                          <th style="width: 10%;">Bobot (%)</th>
                          <th style="width: 15%;">Nilai (1-5)</th>
                          <th style="width: 15%;">Skor (Bobot x Nilai)</th>
                          <th style="width: 20%;">Catatan</th>
                        </tr>
                      </thead>
                      <tbody>
                        <template v-for="(aspect, idx) in form.aspect_scores" :key="aspect.key">
                          <!-- Section Header row if category changes -->
                          <tr v-if="idx === 0 || aspect.category !== form.aspect_scores[idx-1].category" class="table-secondary fw-bold">
                            <td colspan="5">{{ aspect.category }}</td>
                          </tr>
                          <tr>
                            <td>{{ aspect.name }}</td>
                            <td class="text-center font-bold">{{ aspect.weight }}%</td>
                            <td class="text-center">
                              <select v-model.number="aspect.rating" class="form-select form-select-sm text-center fw-bold text-primary">
                                <option :value="5">5 - Sangat Baik</option>
                                <option :value="4">4 - Baik</option>
                                <option :value="3">3 - Cukup</option>
                                <option :value="2">2 - Kurang</option>
                                <option :value="1">1 - Sangat Kurang</option>
                              </select>
                            </td>
                            <td class="text-center fw-bold">{{ (aspect.weight || 0) * (aspect.rating || 0) }}</td>
                            <td>
                              <input v-model="aspect.notes" type="text" class="form-control form-control-sm" placeholder="Catatan..." />
                            </td>
                          </tr>
                        </template>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>

              <!-- Section 4: Komentar Atasan & Keputusan Akhir -->
              <div class="row g-3 mb-3">
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Kelebihan Karyawan</label>
                  <textarea v-model="form.strengths" class="form-control form-control-sm" rows="3" placeholder="Poin-poin kelebihan karyawan..."></textarea>
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Area Yang Perlu Ditingkatkan</label>
                  <textarea v-model="form.improvements" class="form-control form-control-sm" rows="3" placeholder="Poin-poin area peningkatan..."></textarea>
                </div>
                <div class="col-12">
                  <div class="p-3 bg-light rounded border">
                    <label class="form-label small fw-bold d-block text-primary">Keputusan Akhir :</label>
                    <div class="d-flex flex-wrap gap-4">
                      <div class="form-check">
                        <input v-model="form.final_decision" class="form-check-input" type="radio" value="passed_permanent" id="dec1" />
                        <label class="form-check-label fw-bold text-success" for="dec1">Diangkat Menjadi Karyawan Tetap</label>
                      </div>
                      <div class="form-check">
                        <input v-model="form.final_decision" class="form-check-input" type="radio" value="extend_probation" id="dec2" />
                        <label class="form-check-label fw-bold text-warning" for="dec2">Perpanjang Masa Probation</label>
                      </div>
                      <div class="form-check">
                        <input v-model="form.final_decision" class="form-check-input" type="radio" value="terminated" id="dec3" />
                        <label class="form-check-label fw-bold text-danger" for="dec3">Diberhentikan / Putus Kerja</label>
                      </div>
                    </div>
                    <div class="mt-2">
                      <label class="form-label small">Catatan Keputusan :</label>
                      <textarea v-model="form.decision_notes" class="form-control form-control-sm" rows="2" placeholder="Catatan tambahan mengenai keputusan..."></textarea>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Signatures Approver -->
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Manager Department</label>
                  <select v-model="form.approved_by_dept_manager" class="form-select form-select-sm">
                    <option value="">-- Pilih Manager Dept --</option>
                    <option v-for="emp in employeesList" :key="emp.id" :value="emp.id">{{ emp.name }}</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Manager HRD</label>
                  <select v-model="form.approved_by_hrd_manager" class="form-select form-select-sm">
                    <option value="">-- Pilih Manager HRD --</option>
                    <option v-for="emp in employeesList" :key="emp.id" :value="emp.id">{{ emp.name }}</option>
                  </select>
                </div>
              </div>

              <div class="modal-footer px-0 pb-0 mt-3 border-top pt-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary btn-sm" :disabled="saving">
                  <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
                  <i v-else class="mdi mdi-content-save me-1"></i> Simpan Form Penilaian
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL 2: Form Permohonan Kenaikan Gaji -->
    <div class="modal fade" id="salaryModal" tabindex="-1" aria-hidden="true" ref="salaryModalRef">
      <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header bg-success text-white py-2">
            <h5 class="modal-title fs-6">
              <i class="mdi mdi-cash-plus me-1"></i> Form Permohonan Kenaikan Gaji (PEBYMOTOR)
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="saveSalaryProposal">
              <div class="alert alert-info py-2 small mb-3">
                Karyawan: <strong>{{ activeEvaluation?.employee?.name }}</strong> | Masa Kerja: <strong>{{ salaryForm.employment_duration_text || '3 Bulan' }}</strong>
              </div>

              <div class="row g-2 mb-3">
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Tanggal Permohonan</label>
                  <input v-model="salaryForm.proposal_date" type="date" class="form-control form-control-sm" required />
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Masa Kerja (Text)</label>
                  <input v-model="salaryForm.employment_duration_text" type="text" class="form-control form-control-sm" placeholder="cth: 3 Bulan" />
                </div>
              </div>

              <!-- Tabel Breakdown Gaji -->
              <h6 class="fw-bold text-success mb-2"><i class="mdi mdi-table me-1"></i> Rincian USULAN KENAIKAN GAJI</h6>
              <div class="table-responsive mb-3">
                <table class="table table-bordered align-middle text-center" style="font-size: 11px;">
                  <thead class="table-light">
                    <tr>
                      <th>Komponen Gaji</th>
                      <th style="width: 25%;">Gaji Saat Ini (Rp)</th>
                      <th style="width: 25%;">Usulan Kenaikan (Rp)</th>
                      <th style="width: 25%;" class="bg-soft-success text-success">Gaji Baru (Rp)</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td class="text-start">Gaji Harian</td>
                      <td><input v-model.number="salaryForm.current_daily_salary" type="number" class="form-control form-control-sm text-end" /></td>
                      <td><input v-model.number="salaryForm.proposed_daily_salary_increase" type="number" class="form-control form-control-sm text-end" /></td>
                      <td class="fw-bold text-end">{{ formatCurrency((salaryForm.current_daily_salary || 0) + (salaryForm.proposed_daily_salary_increase || 0)) }}</td>
                    </tr>
                    <tr>
                      <td class="text-start">Gaji Bulanan</td>
                      <td><input v-model.number="salaryForm.current_monthly_salary" type="number" class="form-control form-control-sm text-end" /></td>
                      <td><input v-model.number="salaryForm.proposed_monthly_salary_increase" type="number" class="form-control form-control-sm text-end" /></td>
                      <td class="fw-bold text-end">{{ formatCurrency((salaryForm.current_monthly_salary || 0) + (salaryForm.proposed_monthly_salary_increase || 0)) }}</td>
                    </tr>
                    <tr>
                      <td class="text-start">Bonus Kerajinan</td>
                      <td><input v-model.number="salaryForm.current_attendance_bonus" type="number" class="form-control form-control-sm text-end" /></td>
                      <td><input v-model.number="salaryForm.proposed_attendance_bonus_increase" type="number" class="form-control form-control-sm text-end" /></td>
                      <td class="fw-bold text-end">{{ formatCurrency((salaryForm.current_attendance_bonus || 0) + (salaryForm.proposed_attendance_bonus_increase || 0)) }}</td>
                    </tr>
                    <tr>
                      <td class="text-start">Tunjangan Jabatan</td>
                      <td><input v-model.number="salaryForm.current_position_allowance" type="number" class="form-control form-control-sm text-end" /></td>
                      <td><input v-model.number="salaryForm.proposed_position_allowance_increase" type="number" class="form-control form-control-sm text-end" /></td>
                      <td class="fw-bold text-end">{{ formatCurrency((salaryForm.current_position_allowance || 0) + (salaryForm.proposed_position_allowance_increase || 0)) }}</td>
                    </tr>
                    <tr>
                      <td class="text-start">Tunjangan Leader</td>
                      <td><input v-model.number="salaryForm.current_leader_allowance" type="number" class="form-control form-control-sm text-end" /></td>
                      <td><input v-model.number="salaryForm.proposed_leader_allowance_increase" type="number" class="form-control form-control-sm text-end" /></td>
                      <td class="fw-bold text-end">{{ formatCurrency((salaryForm.current_leader_allowance || 0) + (salaryForm.proposed_leader_allowance_increase || 0)) }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <!-- Alasan Kenaikan Gaji -->
              <h6 class="fw-bold text-success mb-2"><i class="mdi mdi-format-list-numbered me-1"></i> ALASAN KENAIKAN GAJI (Poin 1-5)</h6>
              <div class="row g-2 mb-3">
                <div v-for="i in 5" :key="i" class="col-12">
                  <div class="input-group input-group-sm">
                    <span class="input-group-text fw-bold">{{ i }}.</span>
                    <input v-model="salaryForm.reasons[i-1]" type="text" class="form-control" :placeholder="`Alasan poin ${i}...`" />
                  </div>
                </div>
              </div>

              <!-- Signatures & Approvers -->
              <h6 class="fw-bold text-success mb-2"><i class="mdi mdi-draw me-1"></i> Tanda Tangan & Persetujuan</h6>
              <div class="row g-2 mb-3">
                <div class="col-md-4">
                  <label class="form-label small">Yang Mengusulkan</label>
                  <select v-model="salaryForm.proposed_by" class="form-select form-select-sm">
                    <option value="">-- Pilih --</option>
                    <option v-for="emp in employeesList" :key="emp.id" :value="emp.id">{{ emp.name }}</option>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label small">Manager Area</label>
                  <select v-model="salaryForm.area_manager_id" class="form-select form-select-sm">
                    <option value="">-- Pilih --</option>
                    <option v-for="emp in employeesList" :key="emp.id" :value="emp.id">{{ emp.name }}</option>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label small">Manager Accounting</label>
                  <select v-model="salaryForm.accounting_manager_id" class="form-select form-select-sm">
                    <option value="">-- Pilih --</option>
                    <option v-for="emp in employeesList" :key="emp.id" :value="emp.id">{{ emp.name }}</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label small">Manager HRD</label>
                  <select v-model="salaryForm.hrd_manager_id" class="form-select form-select-sm">
                    <option value="">-- Pilih --</option>
                    <option v-for="emp in employeesList" :key="emp.id" :value="emp.id">{{ emp.name }}</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label small">Direktur Utama</label>
                  <select v-model="salaryForm.director_id" class="form-select form-select-sm">
                    <option value="">-- Pilih --</option>
                    <option v-for="emp in employeesList" :key="emp.id" :value="emp.id">{{ emp.name }}</option>
                  </select>
                </div>
              </div>

              <div class="modal-footer px-0 pb-0 border-top pt-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-success btn-sm" :disabled="savingSalary">
                  <span v-if="savingSalary" class="spinner-border spinner-border-sm me-1"></span>
                  <i v-else class="mdi mdi-content-save me-1"></i> Simpan Permohonan Gaji
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../../api'
import { toast } from 'vue3-toastify'

// State
const loading = ref(false)
const saving = ref(false)
const savingSalary = ref(false)
const evaluations = ref([])
const employeesList = ref([])
const searchQuery = ref('')
const decisionFilter = ref('')
const statusFilter = ref('')
const pagination = ref({ current_page: 1, last_page: 1, total: 0, per_page: 15 })

const isEdit = ref(false)
const activeEvaluation = ref(null)

const evaluationModalRef = ref(null)
const salaryModalRef = ref(null)
let evaluationModalInstance = null
let salaryModalInstance = null

// Form State Evaluation
const form = ref({
  id: null,
  employee_id: '',
  evaluator_id: '',
  evaluation_date: new Date().toISOString().split('T')[0],
  probation_start_date: '',
  probation_end_date: '',
  probation_period_text: '3 Bulan',
  absent_days: 0,
  permit_days: 0,
  sick_days: 0,
  half_days: 0,
  late_days: 0,
  has_oral_warning: false,
  has_sp1: false,
  has_sp2: false,
  aspect_scores: [],
  strengths: '',
  improvements: '',
  final_decision: 'passed_permanent',
  decision_notes: '',
  status: 'submitted',
  approved_by_dept_manager: '',
  approved_by_hrd_manager: '',
})

// Form State Salary Proposal
const salaryForm = ref({
  proposal_date: new Date().toISOString().split('T')[0],
  employment_duration_text: '3 Bulan',
  current_daily_salary: 0,
  current_monthly_salary: 0,
  current_attendance_bonus: 0,
  current_position_allowance: 0,
  current_leader_allowance: 0,
  proposed_daily_salary_increase: 0,
  proposed_monthly_salary_increase: 0,
  proposed_attendance_bonus_increase: 0,
  proposed_position_allowance_increase: 0,
  proposed_leader_allowance_increase: 0,
  reasons: ['', '', '', '', ''],
  proposed_by: '',
  area_manager_id: '',
  accounting_manager_id: '',
  hrd_manager_id: '',
  director_id: '',
})

// Computeds
const computedTotalScore = computed(() => {
  if (!form.value.aspect_scores) return 0
  return form.value.aspect_scores.reduce((sum, asp) => sum + ((asp.weight || 0) * (asp.rating || 0)), 0)
})

const computedScoreHundred = computed(() => {
  return (computedTotalScore.value / 5.0).toFixed(1)
})

const computedPredicate = computed(() => {
  const score = parseFloat(computedScoreHundred.value)
  if (score >= 85) return 'Sangat Baik'
  if (score >= 70) return 'Baik'
  if (score >= 60) return 'Cukup'
  if (score >= 50) return 'Kurang'
  return 'Sangat Kurang'
})

// Methods
const fetchEvaluations = async (page = 1) => {
  loading.value = true
  try {
    const res = await api.get('/kpi/probation', {
      params: {
        page,
        search: searchQuery.value,
        final_decision: decisionFilter.value,
        status: statusFilter.value,
      }
    })
    evaluations.value = res.data.data.data
    pagination.value = {
      current_page: res.data.data.current_page,
      last_page: res.data.data.last_page,
      total: res.data.data.total,
      per_page: res.data.data.per_page,
    }
  } catch (err) {
    toast.error('Gagal mengambil data penilaian probation')
  } finally {
    loading.value = false
  }
}

const fetchEmployees = async () => {
  try {
    const res = await api.get('/employees', { params: { per_page: 500 } })
    employeesList.value = res.data.data?.data || res.data.data || []
  } catch (err) {
    console.error(err)
  }
}

const loadingSummary = ref(false)

const onEmployeeSelect = async () => {
  if (!form.value.employee_id) return
  await fetchAttendanceSummary(true)
}

const onPeriodDateChange = () => {
  if (form.value.employee_id) {
    fetchAttendanceSummary(false)
  }
}

const fetchAttendanceSummary = async (isInitial = false) => {
  if (!form.value.employee_id) return
  loadingSummary.value = true
  try {
    const res = await api.get(`/kpi/probation/employee-summary/${form.value.employee_id}`, {
      params: {
        start_date: form.value.probation_start_date || undefined,
        evaluation_date: form.value.evaluation_date || undefined
      }
    })
    const summary = res.data.data
    
    // Auto fill dates and details on initial select
    if (isInitial) {
      if (summary.period?.start_date && !form.value.probation_start_date) {
        form.value.probation_start_date = summary.period.start_date
      } else if (summary.assignment?.join_date && !form.value.probation_start_date) {
        form.value.probation_start_date = summary.assignment.join_date.substring(0, 10)
      } else if (summary.employee?.join_date && !form.value.probation_start_date) {
        form.value.probation_start_date = summary.employee.join_date.substring(0, 10)
      }

      if (summary.assignment?.supervisor_id && !form.value.evaluator_id) {
        form.value.evaluator_id = summary.assignment.supervisor_id
      }
      
      if (!form.value.aspect_scores || form.value.aspect_scores.length === 0) {
        form.value.aspect_scores = JSON.parse(JSON.stringify(summary.default_aspects))
      }
    }

    // Auto fill attendance & warnings based on period join s/d penilaian
    form.value.absent_days = summary.attendance_summary?.absent_days ?? 0
    form.value.permit_days = summary.attendance_summary?.permit_days ?? 0
    form.value.sick_days = summary.attendance_summary?.sick_days ?? 0
    form.value.half_days = summary.attendance_summary?.half_days ?? 0
    form.value.late_days = summary.attendance_summary?.late_days ?? 0

    form.value.has_oral_warning = !!summary.disciplinary_summary?.has_oral_warning
    form.value.has_sp1 = !!summary.disciplinary_summary?.has_sp1
    form.value.has_sp2 = !!summary.disciplinary_summary?.has_sp2

    if (!isInitial) {
      toast.success('Data absensi & SP berhasil ditarik dari periode join s/d penilaian')
    }
  } catch (err) {
    toast.error('Gagal mengambil summary data karyawan')
  } finally {
    loadingSummary.value = false
  }
}

const openEvaluationModal = () => {
  isEdit.value = false
  form.value = {
    id: null,
    employee_id: '',
    evaluator_id: '',
    evaluation_date: new Date().toISOString().split('T')[0],
    probation_start_date: '',
    probation_end_date: '',
    probation_period_text: '3 Bulan',
    absent_days: 0,
    permit_days: 0,
    sick_days: 0,
    half_days: 0,
    late_days: 0,
    has_oral_warning: false,
    has_sp1: false,
    has_sp2: false,
    aspect_scores: getDefaultAspects(),
    strengths: '',
    improvements: '',
    final_decision: 'passed_permanent',
    decision_notes: '',
    status: 'submitted',
    approved_by_dept_manager: '',
    approved_by_hrd_manager: '',
  }
  getEvaluationModal()?.show()
}

const editEvaluation = (item) => {
  isEdit.value = true
  form.value = {
    id: item.id,
    employee_id: item.employee_id,
    evaluator_id: item.evaluator_id || '',
    evaluation_date: item.evaluation_date ? item.evaluation_date.substring(0, 10) : '',
    probation_start_date: item.probation_start_date ? item.probation_start_date.substring(0, 10) : '',
    probation_end_date: item.probation_end_date ? item.probation_end_date.substring(0, 10) : '',
    probation_period_text: item.probation_period_text || '3 Bulan',
    absent_days: item.absent_days || 0,
    permit_days: item.permit_days || 0,
    sick_days: item.sick_days || 0,
    half_days: item.half_days || 0,
    late_days: item.late_days || 0,
    has_oral_warning: !!item.has_oral_warning,
    has_sp1: !!item.has_sp1,
    has_sp2: !!item.has_sp2,
    aspect_scores: item.aspect_scores || getDefaultAspects(),
    strengths: item.strengths || '',
    improvements: item.improvements || '',
    final_decision: item.final_decision || 'passed_permanent',
    decision_notes: item.decision_notes || '',
    status: item.status || 'submitted',
    approved_by_dept_manager: item.approved_by_dept_manager || '',
    approved_by_hrd_manager: item.approved_by_hrd_manager || '',
  }
  getEvaluationModal()?.show()
}

const saveEvaluation = async () => {
  saving.value = true
  try {
    if (isEdit.value) {
      await api.put(`/kpi/probation/${form.value.id}`, form.value)
      toast.success('Evaluasi probation berhasil diperbarui')
    } else {
      await api.post('/kpi/probation', form.value)
      toast.success('Evaluasi probation berhasil disimpan')
    }
    getEvaluationModal()?.hide()
    fetchEvaluations()
  } catch (err) {
    toast.error(err.response?.data?.message || 'Gagal menyimpan evaluasi')
  } finally {
    saving.value = false
  }
}

const deleteEvaluation = async (item) => {
  if (!confirm(`Hapus penilaian probation untuk ${item.employee?.name}?`)) return
  try {
    await api.delete(`/kpi/probation/${item.id}`)
    toast.success('Data berhasil dihapus')
    fetchEvaluations()
  } catch (err) {
    toast.error('Gagal menghapus data')
  }
}

const getEvaluationModal = () => {
  if (!evaluationModalInstance && typeof window !== 'undefined' && window.bootstrap) {
    evaluationModalInstance = new window.bootstrap.Modal(evaluationModalRef.value)
  }
  return evaluationModalInstance
}

const getSalaryModal = () => {
  if (!salaryModalInstance && typeof window !== 'undefined' && window.bootstrap) {
    salaryModalInstance = new window.bootstrap.Modal(salaryModalRef.value)
  }
  return salaryModalInstance
}

const openSalaryProposalModal = async (item) => {
  activeEvaluation.value = item
  try {
    // Auto-fetch employee salary summary
    const summaryRes = await api.get(`/kpi/probation/employee-summary/${item.employee_id}`)
    const salaryData = summaryRes.data.data.current_salary

    const proposal = item.salary_proposal || {}

    salaryForm.value = {
      proposal_date: proposal.proposal_date ? proposal.proposal_date.substring(0, 10) : new Date().toISOString().split('T')[0],
      employment_duration_text: proposal.employment_duration_text || item.probation_period_text || '3 Bulan',
      current_daily_salary: proposal.current_daily_salary !== undefined ? parseFloat(proposal.current_daily_salary) : salaryData.daily,
      current_monthly_salary: proposal.current_monthly_salary !== undefined ? parseFloat(proposal.current_monthly_salary) : salaryData.monthly,
      current_attendance_bonus: proposal.current_attendance_bonus !== undefined ? parseFloat(proposal.current_attendance_bonus) : salaryData.attendance_bonus,
      current_position_allowance: proposal.current_position_allowance !== undefined ? parseFloat(proposal.current_position_allowance) : salaryData.position_allowance,
      current_leader_allowance: proposal.current_leader_allowance !== undefined ? parseFloat(proposal.current_leader_allowance) : salaryData.leader_allowance,
      proposed_daily_salary_increase: proposal.proposed_daily_salary_increase ? parseFloat(proposal.proposed_daily_salary_increase) : 0,
      proposed_monthly_salary_increase: proposal.proposed_monthly_salary_increase ? parseFloat(proposal.proposed_monthly_salary_increase) : 0,
      proposed_attendance_bonus_increase: proposal.proposed_attendance_bonus_increase ? parseFloat(proposal.proposed_attendance_bonus_increase) : 0,
      proposed_position_allowance_increase: proposal.proposed_position_allowance_increase ? parseFloat(proposal.proposed_position_allowance_increase) : 0,
      proposed_leader_allowance_increase: proposal.proposed_leader_allowance_increase ? parseFloat(proposal.proposed_leader_allowance_increase) : 0,
      reasons: proposal.reasons && proposal.reasons.length === 5 ? proposal.reasons : ['', '', '', '', ''],
      proposed_by: proposal.proposed_by || '',
      area_manager_id: proposal.area_manager_id || '',
      accounting_manager_id: proposal.accounting_manager_id || '',
      hrd_manager_id: proposal.hrd_manager_id || '',
      director_id: proposal.director_id || '',
    }
    getSalaryModal()?.show()
  } catch (err) {
    toast.error('Gagal memuat form permohonan kenaikan gaji')
  }
}

const saveSalaryProposal = async () => {
  if (!activeEvaluation.value) return
  savingSalary.value = true
  try {
    await api.post(`/kpi/probation/${activeEvaluation.value.id}/salary-proposal`, salaryForm.value)
    toast.success('Permohonan kenaikan gaji berhasil disimpan')
    getSalaryModal()?.hide()
    fetchEvaluations()
  } catch (err) {
    toast.error(err.response?.data?.message || 'Gagal menyimpan permohonan kenaikan gaji')
  } finally {
    savingSalary.value = false
  }
}

const downloadEvaluationPdf = async (item) => {
  try {
    const res = await api.get(`/kpi/probation/${item.id}/export-evaluation-pdf`, { responseType: 'blob' })
    const url = window.URL.createObjectURL(new Blob([res.data], { type: 'application/pdf' }))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `Form_Penilaian_Probation_${item.employee?.name || 'Employee'}.pdf`)
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
  } catch (err) {
    toast.error('Gagal men-download PDF Penilaian')
  }
}

const downloadSalaryPdf = async (item) => {
  try {
    const res = await api.get(`/kpi/probation/${item.id}/export-salary-pdf`, { responseType: 'blob' })
    const url = window.URL.createObjectURL(new Blob([res.data], { type: 'application/pdf' }))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `Form_Permohonan_Kenaikan_Gaji_${item.employee?.name || 'Employee'}.pdf`)
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
  } catch (err) {
    toast.error('Gagal men-download PDF Permohonan Gaji')
  }
}

const changePage = (page) => {
  if (page >= 1 && page <= pagination.value.last_page) {
    fetchEvaluations(page)
  }
}

const resetFilters = () => {
  searchQuery.value = ''
  decisionFilter.value = ''
  statusFilter.value = ''
  fetchEvaluations()
}

const formatDate = (dateStr) => {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
}

const formatCurrency = (val) => {
  return new Intl.NumberFormat('id-ID').format(val || 0)
}

const scoreColor = (score) => {
  const s = parseFloat(score || 0)
  if (s >= 85) return 'text-success'
  if (s >= 70) return 'text-primary'
  if (s >= 60) return 'text-warning'
  return 'text-danger'
}

const decisionLabel = (d) => {
  return {
    passed_permanent: 'Karyawan Tetap',
    extend_probation: 'Perpanjang Probation',
    terminated: 'Diberhentikan',
  }[d] || d
}

const decisionBadgeClass = (d) => {
  return {
    passed_permanent: 'bg-success',
    extend_probation: 'bg-warning text-dark',
    terminated: 'bg-danger',
  }[d] || 'bg-secondary'
}

const statusBadgeClass = (s) => {
  return {
    draft: 'bg-secondary',
    submitted: 'bg-info',
    approved: 'bg-success',
    rejected: 'bg-danger',
  }[s] || 'bg-secondary'
}

const getDefaultAspects = () => [
  { category: 'A. Kinerja (40%)', key: 'kinerja_1', name: 'Pemahaman terhadap pekerjaan', weight: 10, rating: 4, score: 40, notes: '' },
  { category: 'A. Kinerja (40%)', key: 'kinerja_2', name: 'Kualitas hasil kerja', weight: 10, rating: 4, score: 40, notes: '' },
  { category: 'A. Kinerja (40%)', key: 'kinerja_3', name: 'Ketelitian dan akurasi kerja', weight: 10, rating: 4, score: 40, notes: '' },
  { category: 'A. Kinerja (40%)', key: 'kinerja_4', name: 'Produktivitas dan penyelesaian tugas', weight: 10, rating: 4, score: 40, notes: '' },
  { category: 'B. Sikap Kerja (30%)', key: 'sikap_1', name: 'Disiplin dan kepatuhan terhadap aturan', weight: 10, rating: 4, score: 40, notes: '' },
  { category: 'B. Sikap Kerja (30%)', key: 'sikap_2', name: 'Kehadiran dan ketepatan waktu', weight: 10, rating: 4, score: 40, notes: '' },
  { category: 'B. Sikap Kerja (30%)', key: 'sikap_3', name: 'Tanggung jawab terhadap pekerjaan', weight: 10, rating: 4, score: 40, notes: '' },
  { category: 'C. Kerja Sama & Komunikasi (20%)', key: 'kerjasama_1', name: 'Kerja sama dalam tim', weight: 10, rating: 4, score: 40, notes: '' },
  { category: 'C. Kerja Sama & Komunikasi (20%)', key: 'kerjasama_2', name: 'Komunikasi dengan rekan kerja dan atasan', weight: 10, rating: 4, score: 40, notes: '' },
  { category: 'D. Adaptasi & Pengembangan Diri (10%)', key: 'adaptasi_1', name: 'Kemampuan beradaptasi dengan lingkungan kerja', weight: 5, rating: 4, score: 20, notes: '' },
  { category: 'D. Adaptasi & Pengembangan Diri (10%)', key: 'adaptasi_2', name: 'Inisiatif dan kemauan belajar', weight: 5, rating: 4, score: 20, notes: '' },
]

onMounted(() => {
  if (typeof window !== 'undefined' && window.bootstrap) {
    evaluationModalInstance = new window.bootstrap.Modal(evaluationModalRef.value)
    salaryModalInstance = new window.bootstrap.Modal(salaryModalRef.value)
  }
  fetchEvaluations()
  fetchEmployees()
})
</script>

<style scoped>
.bg-soft-info {
  background-color: #e0f2fe;
}
.bg-soft-success {
  background-color: #dcfce7;
}
</style>
