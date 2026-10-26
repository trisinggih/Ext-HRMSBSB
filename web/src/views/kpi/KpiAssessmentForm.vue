<template>
  <div class="kpi-assessment-page">
    <!-- Non-print Header & Toolbar -->
    <div class="d-flex justify-content-between align-items-center mb-3 no-print flex-wrap gap-2">
      <div class="d-flex align-items-center gap-3">
        <button 
          @click="goBack"
          class="btn-back"
          title="Kembali ke Detail Penilaian KPI Periode"
        >
          &larr; Kembali
        </button>
        <div>
          <h5 class="mb-0 fw-bold text-dark">Form Evaluasi KPI</h5>
          <small class="text-muted">Lembar Penilaian Kinerja Karyawan Berdasarkan Frekuensi & Periode</small>
        </div>
      </div>

      <div v-if="assessment" class="d-flex gap-2 align-items-center flex-wrap">
        <span class="badge px-3 py-2 rounded-pill font-size-12 fw-semibold" :class="statusBadgeClass(assessment.status)">
          {{ statusLabel(assessment.status) }}
        </span>

        <button 
          @click="exportPdf" 
          class="btn btn-outline-secondary btn-sm rounded-2 px-3 py-2 fw-medium" 
          :disabled="downloading"
        >
          <span v-if="downloading" class="spinner-border spinner-border-sm me-1"></span>
          Export PDF
        </button>

        <button 
          v-if="assessment.status === 'in_review'" 
          @click="approveAssessment" 
          class="btn btn-success btn-sm rounded-2 px-3 py-2 fw-medium"
        >
          Setujui Penilaian
        </button>

        <button 
          v-if="['in_review', 'approved'].includes(assessment.status)" 
          @click="resetAssessment" 
          class="btn btn-outline-danger btn-sm rounded-2 px-3 py-2 fw-medium"
        >
          Reset Penilaian
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="text-center py-5 no-print">
      <div class="spinner-border text-primary mb-2" style="width: 2.5rem; height: 2.5rem;"></div>
      <div class="text-muted small">Memuat lembar penilaian KPI...</div>
    </div>

    <!-- Main Content (Screen View & Print Area) -->
    <div v-else-if="assessment" id="printable-assessment">

      <!-- PDF / Print Header Only -->
      <div class="print-only mb-4 border-bottom pb-3">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h3 class="fw-bold mb-1 text-primary">HRMS - LEMBAR PENILAIAN KPI</h3>
            <p class="mb-0 text-muted small">
              No. Transaksi: <strong>{{ assessment.transaction_number || ('KPI-' + assessment.id) }}</strong> | Laporan Evaluasi dan Penilaian Kinerja Karyawan
            </p>
          </div>
          <div class="text-end">
            <span class="badge bg-dark text-white px-3 py-2">STATUS: {{ statusLabel(assessment.status).toUpperCase() }}</span>
            <div class="small text-muted mt-1">Dicetak pada: {{ currentDateStr }}</div>
          </div>
        </div>
      </div>

      <!-- Information & Scorecard Grid -->
      <div class="row g-3 mb-3">
        <!-- Profile & Metadata Card -->
        <div class="col-lg-7 col-md-12">
          <div class="card border-0 shadow-sm h-100 rounded-3">
            <div class="card-body p-3">
              <!-- Employee Header info -->
              <div class="d-flex align-items-center gap-3 pb-3 mb-3 border-bottom">
                <div class="avatar-circle flex-shrink-0">
                  {{ (assessment.employee?.name || 'K')[0].toUpperCase() }}
                </div>
                <div>
                  <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                    <h6 class="mb-0 fw-bold text-dark fs-6">{{ assessment.employee?.name }}</h6>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace font-size-11 px-2.5 py-1 fw-bold">
                      No. Transaksi: {{ assessment.transaction_number || ('KPI-' + assessment.id) }}
                    </span>
                  </div>
                  <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="badge bg-light text-secondary border font-size-11">
                      NIP: {{ assessment.employee?.employee_id || '-' }}
                    </span>
                    <span class="badge bg-light text-secondary border font-size-11">
                      {{ assessment.employee?.position?.name || assessment.employee?.primary_assignment?.position?.name || 'Tanpa Jabatan' }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- Metadata Grid -->
              <div class="row g-2 font-size-13">
                <div class="col-sm-6">
                  <div class="p-2-5 bg-light rounded-2 border border-light">
                    <div class="text-muted font-size-11 mb-0-5">Divisi / Departemen</div>
                    <div class="fw-semibold text-dark d-flex align-items-center gap-1 flex-wrap">
                      <span>{{ assessment.employee?.division?.name || assessment.employee?.department?.name || assessment.employee?.primary_assignment?.department?.name || '—' }}</span>
                      <span v-if="assessment.employee?.department_group" class="badge font-size-10 px-2 py-0-5" :class="departmentBadgeClass(assessment.employee?.department_group)">
                        {{ assessment.employee?.department_group }}
                      </span>
                    </div>
                  </div>
                </div>
                <div class="col-sm-6">
                  <div class="p-2-5 bg-light rounded-2 border border-light">
                    <div class="text-muted font-size-11 mb-0-5">Atasan / Penilai</div>
                    <div class="fw-semibold text-dark">{{ assessment.reviewer?.name || '—' }}</div>
                  </div>
                </div>
                <div class="col-sm-6">
                  <div class="p-2-5 bg-light rounded-2 border border-light">
                    <div class="text-muted font-size-11 mb-0-5">Periode & Fase Evaluasi</div>
                    <div class="fw-semibold text-dark d-flex align-items-center gap-1 flex-wrap">
                      <span>{{ assessment.period?.name || '-' }}</span>
                      <span v-if="assessment.period?.type" class="badge font-size-10 px-2 py-0-5" :class="phaseBadgeClass(assessment.period.type)">
                        {{ phaseLabel(assessment.period.type) }}
                      </span>
                    </div>
                  </div>
                </div>
                <div class="col-sm-6">
                  <div class="p-2-5 bg-light rounded-2 border border-light">
                    <div class="text-muted font-size-11 mb-0-5">Template KPI & Skala</div>
                    <div class="fw-semibold text-dark">
                      {{ assessment.template?.name || '—' }}
                      <span class="text-muted font-size-11 fw-normal">(Skala 0 – {{ maxScore }})</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Score Summary Card -->
        <div class="col-lg-5 col-md-12">
          <div class="card border-0 shadow-sm h-100 rounded-3 text-center p-3 d-flex flex-column align-items-center justify-content-center bg-white">
            <span class="text-uppercase small fw-semibold text-muted mb-1" style="letter-spacing: 0.05em;">
              {{ selectedFrequencyTab !== 'all' ? `Skor Capaian (${activeTabLabel})` : 'Skor Akhir KPI' }}
            </span>
            <div class="display-5 fw-bold mb-1" :class="scoreTextColor(displayScore)">
              {{ displayScore !== null ? displayScore : '—' }}
            </div>
            
            <div v-if="displayScore !== null" class="mb-2">
              <span class="badge px-3 py-1 font-size-12 fw-semibold rounded-pill" :class="predikatBadgeClass(displayScore)">
                {{ predikatText(displayScore) }}
              </span>
            </div>

            <!-- Progress Bar -->
            <div class="w-100 px-3 my-1" v-if="displayScore !== null">
              <div class="progress" style="height: 6px; border-radius: 4px; background-color: #e2e8f0;">
                <div 
                  class="progress-bar transition-all"
                  :class="scoreProgressBarColor(displayScore)"
                  :style="{ width: Math.min((parseFloat(displayScore) / maxScore * 100), 100) + '%' }"
                ></div>
              </div>
            </div>

            <!-- Detail Progres Berdasarkan Tab Frekuensi Aktif -->
            <div class="d-flex align-items-center justify-content-between w-100 px-3 mt-2 font-size-11 text-muted">
              <span>Progres {{ activeTabLabel }}: <strong>{{ activeTabCompletedCount }} / {{ activeTabIndicators.length }}</strong></span>
              <span>Bobot: <strong>{{ activeTabWeight }}%</strong></span>
            </div>

            <div v-if="selectedFrequencyTab !== 'all' && overallCalculatedScore !== null" class="font-size-11 text-muted mt-1 w-100 px-3 text-end">
              Total Skor Keseluruhan: <strong class="text-dark">{{ overallCalculatedScore }}</strong>
            </div>

            <div class="font-size-11 text-muted mt-2 border-top pt-2 w-100">
              <span v-if="assessment.approved_at">
                Disetujui: {{ formatDate(assessment.approved_at) }}
              </span>
              <span v-else-if="assessment.reviewed_at">
                Terakhir Dinilai: {{ formatDate(assessment.reviewed_at) }}
              </span>
              <span v-else>
                Status: Belum Selesai Dinilai
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Indicators Form / Table Section -->
      <div class="card border-0 shadow-sm rounded-3 mb-3 overflow-hidden">
        
        <!-- Frequency / Period Filter Tab Bar -->
        <div class="card-header bg-white py-3 border-bottom no-print">
          <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
            <div>
              <h6 class="mb-0 fw-bold text-dark font-size-14">Penilaian Indikator Berdasarkan Frekuensi & Periode</h6>
              <small class="text-muted">Pilih frekuensi penilaian di bawah ini atau nilai seluruh indikator sekaligus</small>
            </div>
            <div class="d-flex align-items-center gap-2">
              <button 
                type="button" 
                class="btn btn-sm rounded-2 px-3 font-size-12"
                :class="showAllRubrics ? 'btn-primary text-white shadow-sm' : 'btn-outline-secondary'"
                @click="toggleAllRubrics"
              >
                {{ showAllRubrics ? 'Sembunyikan Rubrik Detail' : 'Tampilkan Rubrik Detail' }}
              </button>
            </div>
          </div>

          <!-- Tab Selector Frekuensi -->
          <div class="d-flex flex-wrap gap-1-5 pt-1 align-items-center">
            <!-- Tab Sesuai Periode -->
            <button
              v-if="hasPeriodMatchIndicators"
              type="button"
              class="btn btn-sm font-size-12 px-3 py-1-5 rounded-pill transition-all d-flex align-items-center gap-1"
              :class="selectedFrequencyTab === 'period_match' ? 'btn-primary text-white shadow-sm fw-bold' : 'btn-outline-primary bg-white'"
              @click="selectedFrequencyTab = 'period_match'"
            >
              <span>Sesuai Periode: {{ frequencyLabel(periodFreqCode) }}</span>
              <span class="badge rounded-pill ms-1" :class="selectedFrequencyTab === 'period_match' ? 'bg-white text-primary' : 'bg-primary-subtle text-primary border'">
                {{ periodMatchCount }} ({{ activeTabWeight }}%)
              </span>
            </button>

            <!-- Tab Semua Frekuensi -->
            <button
              type="button"
              class="btn btn-sm font-size-12 px-3 py-1-5 rounded-pill transition-all"
              :class="selectedFrequencyTab === 'all' ? 'btn-primary text-white shadow-sm fw-bold' : 'btn-outline-secondary bg-white'"
              @click="selectedFrequencyTab = 'all'"
            >
              Semua Frekuensi
              <span class="badge rounded-pill ms-1" :class="selectedFrequencyTab === 'all' ? 'bg-white text-primary' : 'bg-light text-secondary border'">
                {{ templateIndicators.length }}
              </span>
            </button>

            <!-- Tab per Frekuensi (Bulanan, Harian, Mingguan, Kuartalan, dsb) -->
            <button
              v-for="freq in distinctFrequencies"
              :key="freq.code"
              type="button"
              class="btn btn-sm font-size-12 px-3 py-1-5 rounded-pill transition-all d-flex align-items-center gap-1"
              :class="selectedFrequencyTab === freq.code ? 'btn-primary text-white shadow-sm fw-bold' : 'btn-outline-secondary bg-white'"
              @click="selectedFrequencyTab = freq.code"
            >
              <span>{{ freq.label }}</span>
              <span class="badge rounded-pill ms-1" :class="selectedFrequencyTab === freq.code ? 'bg-white text-primary' : 'bg-light text-secondary border'">
                {{ freq.count }} ({{ freq.totalWeight }}%)
              </span>
            </button>
          </div>

          <!-- Alert / Info Banner Kesesuaian Periode -->
          <div v-if="selectedFrequencyTab === 'period_match'" class="alert alert-info border-0 rounded-2 py-2 px-3 mt-2 mb-0 font-size-12 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
              Menampilkan khusus <strong>{{ activeTabIndicators.length }} indikator {{ frequencyLabel(periodFreqCode) }}</strong> yang jatuh tempo pada <strong>{{ phaseLabel(assessment.period?.type) }}</strong> (Bobot: {{ activeTabWeight }}%).
            </div>
            <span class="text-secondary font-size-11">
              Gunakan tab frekuensi lain untuk mengevaluasi indikator berkala lainnya.
            </span>
          </div>

          <div v-else-if="!hasPeriodMatchIndicators && selectedFrequencyTab === 'all'" class="alert alert-light border rounded-2 py-2 px-3 mt-2 mb-0 font-size-12 text-muted">
            Periode evaluasi ini bertipe <strong>{{ phaseLabel(assessment.period?.type) }}</strong>. Template KPI ini memiliki indikator berkala (Bulanan, Mingguan, Harian) yang dapat dinilai sesuai tab frekuensi di atas.
          </div>
        </div>

        <!-- Table View -->
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="font-size-12 text-uppercase">
                <tr>
                  <th style="width: 45px;" class="text-center">No</th>
                  <th style="min-width: 280px;">Indikator & Target</th>
                  <th style="width: 75px;" class="text-center">Bobot</th>
                  <th style="width: 100px;" class="text-center">Frekuensi</th>
                  <th style="min-width: 320px;">Penilaian Capaian & Rubrik</th>
                  <th style="min-width: 200px;">Catatan Evaluasi</th>
                </tr>
              </thead>
              <tbody class="font-size-13">
                
                <!-- Grouped Indicators Loop -->
                <template v-for="group in groupedIndicators" :key="group.code">
                  <!-- Section Header per Frekuensi (Ditampilkan saat menampilkan lebih dari 1 grup frekuensi) -->
                  <tr v-if="groupedIndicators.length > 1" class="bg-light border-top border-bottom">
                    <td colspan="6" class="py-2 px-3">
                      <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                          <span class="badge font-size-11 px-2-5 py-1 rounded-pill" :class="group.badgeClass">
                            {{ group.label }}
                          </span>
                          <span class="fw-bold text-dark font-size-13">Indikator Frekuensi {{ group.label }}</span>
                          <span v-if="group.isPeriodMatch" class="badge bg-success-subtle text-success border border-success-subtle font-size-10 px-2 py-0-5">
                            Sesuai Fase Periode Evaluasi
                          </span>
                        </div>
                        <div class="text-secondary font-size-12">
                          <span>{{ group.indicators.length }} Indikator</span> &bull; 
                          <span>Subtotal Bobot: <strong class="text-dark">{{ group.totalWeight }}%</strong></span>
                        </div>
                      </div>
                    </td>
                  </tr>

                  <!-- Baris Tiap Indikator di dalam grup ini -->
                  <tr v-for="(ti, index) in group.indicators" :key="ti.id">
                    <td class="text-center text-muted fw-semibold">{{ getIndicatorGlobalIndex(ti) }}</td>
                    <td>
                      <div class="d-flex align-items-start gap-1 flex-wrap mb-1">
                        <span class="fw-bold text-dark">{{ ti.indicator?.name }}</span>
                        <span class="badge bg-light text-secondary border font-size-10">{{ ti.indicator?.category || 'Umum' }}</span>
                      </div>

                      <div v-if="ti.target || ti.indicator?.target" class="text-secondary font-size-12 mb-0-5">
                        <span class="fw-semibold text-dark">Target:</span> {{ ti.target || ti.indicator?.target }}
                      </div>

                      <div v-if="ti.calculation_method || ti.indicator?.calculation_method" class="text-muted font-size-11 mb-1">
                        <span class="fw-semibold text-secondary">Rumus Ukur:</span> {{ ti.calculation_method || ti.indicator?.calculation_method }}
                      </div>

                      <!-- Toggle individual rubric details if not expanded globally -->
                      <div v-if="hasRubric(ti)" class="no-print mt-1">
                        <button 
                          type="button" 
                          class="btn-link-clean font-size-11"
                          @click="toggleRubric(ti.id)"
                        >
                          {{ isRubricVisible(ti.id) ? 'Sembunyikan Kriteria Poin' : 'Lihat Kriteria Poin Lengkap' }}
                        </button>
                      </div>

                      <!-- Expandable Rubric Details Table/List -->
                      <div v-if="isRubricVisible(ti.id) && hasRubric(ti)" class="mt-2 p-2 bg-light rounded-2 border font-size-11 no-print">
                        <div class="fw-bold text-dark mb-1 pb-1 border-bottom d-flex justify-content-between align-items-center">
                          <span class="text-secondary">Standar Kriteria Penilaian:</span>
                          <span class="text-muted font-size-10">Pilih salah satu poin di sebelah kanan</span>
                        </div>
                        <div class="d-flex flex-column gap-1">
                          <div 
                            v-for="pt in getRubricPoints(ti)" 
                            :key="pt.point"
                            class="p-1-5 rounded-2 d-flex align-items-start gap-2 border bg-white"
                            :class="isPointSelected(ti, pt.score) ? 'border-primary bg-primary-subtle' : 'border-light'"
                          >
                            <span class="badge font-size-10 px-2 py-0-5 flex-shrink-0" :class="pointBadgeClass(pt.point)">
                              Poin {{ pt.point }} ({{ pt.grade }} &bull; {{ pt.score }})
                            </span>
                            <div class="flex-grow-1">
                              <strong class="text-dark">{{ pt.label }}:</strong>
                              <span class="text-secondary ms-1">{{ pt.criteria }}</span>
                            </div>
                          </div>
                        </div>
                      </div>
                    </td>

                    <!-- Bobot -->
                    <td class="text-center">
                      <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 font-size-12 fw-bold">
                        {{ parseFloat(ti.weight) }}%
                      </span>
                    </td>

                    <!-- Frekuensi -->
                    <td class="text-center">
                      <span class="badge font-size-11 px-2 py-1 rounded-pill" :class="frequencyBadgeClass(ti.frequency || ti.indicator?.frequency)">
                        {{ frequencyLabel(ti.frequency || ti.indicator?.frequency) }}
                      </span>
                      <div v-if="isPeriodMatch(ti)" class="mt-1">
                        <span class="badge bg-success-subtle text-success border border-success-subtle font-size-10 px-1-5 py-0-5" title="Sesuai dengan fase periode evaluasi aktif">
                          Sesuai Periode
                        </span>
                      </div>
                    </td>

                    <!-- Penilaian & Rubrik -->
                    <td>
                      <!-- Interactive Screen View -->
                      <div class="no-print">
                        <!-- Quick Rubric Buttons (Pills) -->
                        <div v-if="hasRubric(ti)" class="mb-2">
                          <div class="text-muted font-size-11 mb-1 fw-semibold">Pilih Poin Capaian:</div>
                          <div class="d-flex flex-wrap gap-1">
                            <button
                              v-for="pt in getRubricPoints(ti)"
                              :key="pt.point"
                              type="button"
                              class="btn btn-sm font-size-11 py-1 px-2 rounded-2 transition-all"
                              :class="isPointSelected(ti, pt.score) ? 'btn-primary text-white shadow-sm fw-bold' : 'btn-outline-secondary bg-white'"
                              :disabled="assessment.status === 'approved'"
                              :title="`${pt.label}: ${pt.criteria}`"
                              @click="applyPointScore(ti, pt.score)"
                            >
                              Poin {{ pt.point }} ({{ pt.score }})
                            </button>
                          </div>
                        </div>

                        <!-- Dropdown & Direct Numeric Input -->
                        <div class="d-flex align-items-center gap-2">
                          <select 
                            v-model="scoreMap[ti.id].rating_level_id"
                            @change="onLevelChange(ti)"
                            class="form-select form-select-sm"
                            :disabled="assessment.status === 'approved'"
                            style="min-width: 140px;"
                          >
                            <option :value="null">— Pilih Level —</option>
                            <option v-for="level in ratingLevels" :key="level.id" :value="level.id">
                              {{ level.label }} ({{ parseFloat(level.score) }})
                            </option>
                          </select>

                          <div class="input-group input-group-sm" style="width: 110px;">
                            <input 
                              v-model.number="scoreMap[ti.id].raw_score"
                              type="number"
                              step="0.01"
                              min="0"
                              :max="maxScore"
                              class="form-control form-control-sm text-center fw-bold"
                              placeholder="Skor"
                              title="Nilai capaian (dapat diisi manual atau via poin rubrik)"
                              :disabled="assessment.status === 'approved'"
                            />
                          </div>
                        </div>

                        <!-- Calculated Score & Weighted Display -->
                        <div v-if="scoreMap[ti.id].raw_score !== null && scoreMap[ti.id].raw_score !== undefined" class="mt-1-5 d-flex align-items-center gap-2 font-size-11">
                          <span class="badge" :class="scoreBadgeClass(scoreMap[ti.id].raw_score)">
                            Skor: {{ scoreMap[ti.id].raw_score }}
                          </span>
                          <span class="text-secondary fw-medium" v-if="ti.weight">
                            Nilai Bobot: {{ ((parseFloat(scoreMap[ti.id].raw_score || 0) * parseFloat(ti.weight || 0)) / 100).toFixed(2) }}
                          </span>
                        </div>
                      </div>

                      <!-- Print View for Level Selection -->
                      <div class="print-only fw-bold font-size-13">
                        <span v-if="scoreMap[ti.id].raw_score !== null">
                          {{ getSelectedLevelLabel(ti) }} (Skor: {{ scoreMap[ti.id].raw_score }})
                        </span>
                        <span v-else class="text-muted">—</span>
                      </div>
                    </td>

                    <!-- Catatan Evaluasi -->
                    <td>
                      <!-- Input Catatan (Non-Print) -->
                      <textarea 
                        v-model="scoreMap[ti.id].notes"
                        rows="2"
                        class="form-control form-control-sm no-print font-size-12"
                        placeholder="Catatan evaluasi indikator ini..."
                        :disabled="assessment.status === 'approved'"
                      ></textarea>

                      <!-- Print View Catatan -->
                      <div class="print-only font-size-12 text-secondary">
                        {{ scoreMap[ti.id].notes || '—' }}
                      </div>
                    </td>
                  </tr>
                </template>

                <tr v-if="activeTabIndicators.length === 0">
                  <td colspan="6" class="text-center text-muted py-5 font-size-13">
                    Tidak ada indikator pada pilihan frekuensi ini.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Reviewer Notes & Action Footer -->
        <div class="card-footer bg-light p-3 border-top no-print" v-if="assessment.status !== 'approved'">
          <div class="row g-3 align-items-end">
            <div class="col-md-7">
              <label class="form-label fw-semibold text-secondary small mb-1">
                Catatan & Rekomendasi Evaluator (Umum)
              </label>
              <textarea 
                v-model="reviewerNotes" 
                rows="2" 
                class="form-control font-size-13" 
                placeholder="Berikan saran pengembangan, apresiasi kerja, atau masukan untuk evaluasi karyawan ini..."
              ></textarea>
            </div>
            <div class="col-md-5">
              <div class="d-flex flex-column align-items-md-end gap-2">
                <div class="d-flex align-items-center gap-3 font-size-12 flex-wrap">
                  <span class="text-muted">Bobot Frekuensi Ini: <strong>{{ activeTabWeight }}%</strong></span>
                  <span class="text-muted">Estimasi Skor: <strong class="text-primary fs-6">{{ displayScore || '—' }}</strong></span>
                </div>
                <button 
                  class="btn btn-primary px-4 py-2 fw-semibold rounded-2 w-100 w-md-auto shadow-sm" 
                  @click="submitScores" 
                  :disabled="saving"
                >
                  <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
                  {{ saving ? 'Menyimpan...' : 'Simpan & Hitung Nilai' }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Saved Reviewer Notes Section (When Approved or Readonly) -->
      <div v-if="assessment.status === 'approved' && assessment.reviewer_notes" class="card border-0 shadow-sm rounded-3 mb-3">
        <div class="card-body p-3">
          <h6 class="fw-semibold text-secondary font-size-12 text-uppercase mb-2">
            Catatan & Rekomendasi Evaluator
          </h6>
          <p class="mb-0 text-dark font-size-13 bg-light p-3 rounded-2 border-start border-3 border-primary">
            {{ assessment.reviewer_notes }}
          </p>
        </div>
      </div>

      <!-- PDF / Print Signatures Block -->
      <div class="print-only mt-5 pt-4 border-top">
        <div class="row text-center font-size-13">
          <div class="col-4">
            <p class="mb-5 text-muted">Karyawan Yang Dinilai,</p>
            <div class="fw-bold text-dark border-bottom border-dark d-inline-block pb-1" style="min-width: 180px;">
              {{ assessment.employee?.name }}
            </div>
            <div class="small text-muted mt-1">NIP: {{ assessment.employee?.employee_id || '-' }}</div>
          </div>
          <div class="col-4">
            <p class="mb-5 text-muted">Atasan / Penilai,</p>
            <div class="fw-bold text-dark border-bottom border-dark d-inline-block pb-1" style="min-width: 180px;">
              {{ assessment.reviewer?.name || '________________' }}
            </div>
            <div class="small text-muted mt-1">Evaluator</div>
          </div>
          <div class="col-4">
            <p class="mb-5 text-muted">Mengetahui (HRD),</p>
            <div class="fw-bold text-dark border-bottom border-dark d-inline-block pb-1" style="min-width: 180px;">
              ____________________
            </div>
            <div class="small text-muted mt-1">HR Department</div>
          </div>
        </div>
      </div>

    </div>

    <!-- Empty State -->
    <div v-else class="text-center py-5 text-muted no-print">
      <h6 class="mt-2 text-dark fw-bold">Data Penilaian Tidak Ditemukan</h6>
      <p class="small text-muted">Silakan kembali ke daftar penilaian KPI.</p>
      <router-link to="/hrms/kpi/assessments" class="btn btn-outline-secondary btn-sm">
        Kembali ke Daftar
      </router-link>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../../api'
import { toast } from 'vue3-toastify'
import 'vue3-toastify/dist/index.css'
import Swal from 'sweetalert2'

const route = useRoute()
const router = useRouter()
const loading = ref(true)
const saving = ref(false)
const downloading = ref(false)
const assessment = ref(null)

const goBack = () => {
  if (assessment.value?.period_id) {
    router.push(`/hrms/kpi/periods/${assessment.value.period_id}/assessments`)
  } else if (window.history.length > 1) {
    router.back()
  } else {
    router.push('/hrms/kpi/periods')
  }
}
const templateIndicators = ref([])
const ratingLevels = ref([])
const scoreMap = ref({})
const reviewerNotes = ref('')
const showAllRubrics = ref(false)
const expandedRubric = ref({})
const selectedFrequencyTab = ref('all')

const toggleAllRubrics = () => {
  showAllRubrics.value = !showAllRubrics.value
  expandedRubric.value = {}
}

const toggleRubric = (id) => {
  if (showAllRubrics.value) {
    expandedRubric.value[id] = expandedRubric.value[id] === false ? true : false
  } else {
    expandedRubric.value[id] = !expandedRubric.value[id]
  }
}

const isRubricVisible = (id) => {
  if (expandedRubric.value[id] !== undefined) {
    return expandedRubric.value[id]
  }
  return showAllRubrics.value
}

const hasRubric = (ti) => {
  const pts = ti.scoring_points || ti.indicator?.scoring_points
  return Array.isArray(pts) && pts.length > 0
}

const getRubricPoints = (ti) => {
  const pts = ti.scoring_points || ti.indicator?.scoring_points
  if (!Array.isArray(pts)) return []
  return [...pts].sort((a, b) => (parseInt(b.point) || 0) - (parseInt(a.point) || 0))
}

const isPointSelected = (ti, score) => {
  if (!scoreMap.value[ti.id] || scoreMap.value[ti.id].raw_score === null || scoreMap.value[ti.id].raw_score === undefined) return false
  return Math.abs(parseFloat(scoreMap.value[ti.id].raw_score) - parseFloat(score)) < 0.5
}

const applyPointScore = (ti, score) => {
  if (assessment.value?.status === 'approved') return
  scoreMap.value[ti.id].raw_score = parseFloat(score)
  const matchLevel = ratingLevels.value.find(l => Math.abs(parseFloat(l.score) - parseFloat(score)) < 0.1)
  if (matchLevel) {
    scoreMap.value[ti.id].rating_level_id = matchLevel.id
  }
}

const onLevelChange = (ti) => {
  const levelId = scoreMap.value[ti.id]?.rating_level_id
  const level = ratingLevels.value.find(l => l.id === levelId)
  if (level) {
    scoreMap.value[ti.id].raw_score = parseFloat(level.score)
  }
}

// ── FREKUENSI & PERIODE MAPPING & COMPUTED ─────────────────────

const periodFreqCode = computed(() => {
  const type = String(assessment.value?.period?.type || '').toLowerCase().trim()
  const map = {
    daily: 'H',
    weekly: 'M',
    monthly: 'B',
    quarterly: 'K',
    semesterly: 'S',
    annual: 'T'
  }
  return map[type] || null
})

const isPeriodMatch = (ti) => {
  if (!periodFreqCode.value) return false
  const f = String(ti.frequency || ti.indicator?.frequency || '').trim().toUpperCase()
  return f === periodFreqCode.value
}

const distinctFrequencies = computed(() => {
  const map = {}
  templateIndicators.value.forEach(ti => {
    const code = String(ti.frequency || ti.indicator?.frequency || 'OTHER').trim().toUpperCase()
    if (!map[code]) {
      map[code] = {
        code,
        label: frequencyLabel(code),
        badgeClass: frequencyBadgeClass(code),
        count: 0,
        totalWeight: 0,
      }
    }
    map[code].count++
    map[code].totalWeight += (parseFloat(ti.weight) || 0)
  })

  const order = ['H', 'M', 'B', 'K', 'S', 'T', 'I', 'OTHER']
  return Object.values(map).sort((a, b) => order.indexOf(a.code) - order.indexOf(b.code))
})

const hasPeriodMatchIndicators = computed(() => {
  if (!periodFreqCode.value) return false
  return templateIndicators.value.some(ti => {
    const f = String(ti.frequency || ti.indicator?.frequency || '').trim().toUpperCase()
    return f === periodFreqCode.value
  })
})

const periodMatchCount = computed(() => {
  if (!periodFreqCode.value) return 0
  return templateIndicators.value.filter(ti => {
    const f = String(ti.frequency || ti.indicator?.frequency || '').trim().toUpperCase()
    return f === periodFreqCode.value
  }).length
})

const activeTabIndicators = computed(() => {
  if (selectedFrequencyTab.value === 'all') {
    return templateIndicators.value
  }
  if (selectedFrequencyTab.value === 'period_match') {
    if (periodFreqCode.value) {
      const matched = templateIndicators.value.filter(ti => {
        const f = String(ti.frequency || ti.indicator?.frequency || '').trim().toUpperCase()
        return f === periodFreqCode.value
      })
      if (matched.length > 0) return matched
    }
    return templateIndicators.value
  }
  return templateIndicators.value.filter(ti => {
    const f = String(ti.frequency || ti.indicator?.frequency || '').trim().toUpperCase()
    return f === selectedFrequencyTab.value
  })
})

const groupedIndicators = computed(() => {
  const groups = {}
  activeTabIndicators.value.forEach(ti => {
    const f = String(ti.frequency || ti.indicator?.frequency || 'OTHER').trim().toUpperCase()
    if (!groups[f]) {
      groups[f] = {
        code: f,
        label: frequencyLabel(f),
        badgeClass: frequencyBadgeClass(f),
        isPeriodMatch: f === periodFreqCode.value,
        totalWeight: 0,
        indicators: []
      }
    }
    groups[f].indicators.push(ti)
    groups[f].totalWeight += (parseFloat(ti.weight) || 0)
  })

  const order = ['H', 'M', 'B', 'K', 'S', 'T', 'I', 'OTHER']
  return Object.values(groups).sort((a, b) => {
    if (a.isPeriodMatch && !b.isPeriodMatch) return -1
    if (!a.isPeriodMatch && b.isPeriodMatch) return 1
    return order.indexOf(a.code) - order.indexOf(b.code)
  })
})

const activeTabLabel = computed(() => {
  if (selectedFrequencyTab.value === 'all') return 'Semua Frekuensi'
  if (selectedFrequencyTab.value === 'period_match') return `Sesuai Periode (${phaseLabel(assessment.value?.period?.type)})`
  return `Frekuensi ${frequencyLabel(selectedFrequencyTab.value)}`
})

const activeTabWeight = computed(() => {
  return activeTabIndicators.value.reduce((acc, ti) => acc + (parseFloat(ti.weight) || 0), 0)
})

const activeTabCompletedCount = computed(() => {
  return activeTabIndicators.value.filter(ti => {
    const s = scoreMap.value[ti.id]?.raw_score
    return s !== null && s !== undefined && s !== '' && !isNaN(s)
  }).length
})

const activeTabScore = computed(() => {
  if (!activeTabIndicators.value.length) return null
  let totalW = 0
  let weightedSum = 0
  let hasAny = false

  activeTabIndicators.value.forEach(ti => {
    const s = scoreMap.value[ti.id]?.raw_score
    const w = parseFloat(ti.weight) || 0
    if (s !== null && s !== undefined && s !== '' && !isNaN(s)) {
      hasAny = true
      totalW += w
      weightedSum += (parseFloat(s) * w)
    }
  })

  if (!hasAny || totalW === 0) return null
  return (weightedSum / totalW).toFixed(2)
})

const overallCalculatedScore = computed(() => {
  if (!templateIndicators.value.length) return null
  let totalW = 0
  let weightedSum = 0
  let hasAny = false

  templateIndicators.value.forEach(ti => {
    const s = scoreMap.value[ti.id]?.raw_score
    const w = parseFloat(ti.weight) || 0
    if (s !== null && s !== undefined && s !== '' && !isNaN(s)) {
      hasAny = true
      totalW += w
      weightedSum += (parseFloat(s) * w)
    }
  })

  if (!hasAny || totalW === 0) return null
  return (weightedSum / totalW).toFixed(2)
})

const displayScore = computed(() => {
  if (selectedFrequencyTab.value !== 'all' && activeTabScore.value !== null) {
    return activeTabScore.value
  }
  if (overallCalculatedScore.value !== null) {
    return overallCalculatedScore.value
  }
  if (assessment.value?.final_score !== null && assessment.value?.final_score !== undefined) {
    return parseFloat(assessment.value.final_score).toFixed(2)
  }
  return null
})

const getIndicatorGlobalIndex = (ti) => {
  const idx = templateIndicators.value.findIndex(item => item.id === ti.id)
  return idx >= 0 ? idx + 1 : 1
}

const maxScore = computed(() => {
  if (ratingLevels.value.length > 0) {
    return Math.max(...ratingLevels.value.map(l => parseFloat(l.score) || 0))
  }
  return 100
})

const totalWeight = computed(() => {
  return templateIndicators.value.reduce((acc, ti) => acc + (parseFloat(ti.weight) || 0), 0)
})

const departmentBadgeClass = (grp) => {
  const g = String(grp || '').toUpperCase()
  if (g === 'FAT') return 'bg-info-subtle text-info border border-info-subtle'
  if (g === 'HRGA') return 'bg-purple-subtle text-purple border'
  if (g === 'SALES') return 'bg-success-subtle text-success border border-success-subtle'
  if (g === 'OPERASIONAL') return 'bg-warning-subtle text-warning-emphasis border border-warning-subtle'
  return 'bg-light text-secondary border'
}

const phaseLabel = (type) => ({
  daily: 'Harian (Daily)',
  weekly: 'Mingguan (Weekly)',
  monthly: 'Bulanan (Monthly)',
  quarterly: 'Kuartalan (Quarterly)',
  semesterly: 'Semesteran',
  annual: 'Tahunan'
}[type] || type || 'Kuartalan')

const phaseBadgeClass = (type) => ({
  daily: 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
  weekly: 'bg-info-subtle text-info border border-info-subtle',
  monthly: 'bg-primary-subtle text-primary border border-primary-subtle',
  quarterly: 'bg-dark-subtle text-dark border border-dark-subtle',
  semesterly: 'bg-secondary-subtle text-secondary border',
  annual: 'bg-danger-subtle text-danger border'
}[type] || 'bg-light text-muted border')

const pointBadgeClass = (point) => {
  const p = parseInt(point)
  if (p === 5) return 'bg-success text-white'
  if (p === 4) return 'bg-primary text-white'
  if (p === 3) return 'bg-warning text-dark'
  if (p === 2) return 'bg-danger text-white'
  return 'bg-dark text-white'
}

const currentDateStr = new Date().toLocaleDateString('id-ID', {
  year: 'numeric', month: 'long', day: 'numeric'
})

const statusBadgeClass = (s) => ({
  'bg-light text-secondary border': s === 'pending',
  'bg-warning-subtle text-warning-emphasis border border-warning-subtle': s === 'in_review',
  'bg-success-subtle text-success border border-success-subtle': s === 'approved'
}[s] || 'bg-light text-secondary border')

const statusLabel = (s) => ({
  pending: 'Menunggu',
  in_review: 'Sedang Dinilai',
  approved: 'Disetujui'
})[s] || s

const scoreTextColor = (score) => {
  if (score === null || score === undefined) return 'text-muted'
  const s = parseFloat(score)
  if (maxScore.value > 10) {
    return s >= 85 ? 'text-success' : s >= 75 ? 'text-primary' : s >= 60 ? 'text-warning' : 'text-danger'
  }
  return s >= 4.0 ? 'text-success' : s >= 3.0 ? 'text-primary' : s >= 2.0 ? 'text-warning' : 'text-danger'
}

const scoreProgressBarColor = (score) => {
  const s = parseFloat(score)
  if (maxScore.value > 10) {
    return s >= 85 ? 'bg-success' : s >= 75 ? 'bg-primary' : s >= 60 ? 'bg-warning' : 'bg-danger'
  }
  return s >= 4.0 ? 'bg-success' : s >= 3.0 ? 'bg-primary' : s >= 2.0 ? 'bg-warning' : 'bg-danger'
}

const scoreBadgeClass = (score) => {
  const s = parseFloat(score)
  if (maxScore.value > 10) {
    return s >= 85 ? 'bg-success-subtle text-success border border-success-subtle' :
           s >= 75 ? 'bg-primary-subtle text-primary border border-primary-subtle' :
           s >= 60 ? 'bg-warning-subtle text-warning-emphasis border border-warning-subtle' :
           'bg-danger-subtle text-danger border border-danger-subtle'
  }
  return s >= 4.0 ? 'bg-success-subtle text-success border border-success-subtle' :
         s >= 3.0 ? 'bg-primary-subtle text-primary border border-primary-subtle' :
         s >= 2.0 ? 'bg-warning-subtle text-warning-emphasis border border-warning-subtle' :
         'bg-danger-subtle text-danger border border-danger-subtle'
}

const predikatText = (score) => {
  if (score === null || score === undefined) return '—'
  const s = parseFloat(score)
  if (maxScore.value > 10) {
    if (s >= 90) return 'Sangat Baik (A)'
    if (s >= 80) return 'Baik (B)'
    if (s >= 70) return 'Cukup (C)'
    if (s >= 60) return 'Kurang (D)'
    return 'Sangat Kurang (E)'
  }
  if (s >= 4.5) return 'Sangat Baik (A)'
  if (s >= 3.5) return 'Baik (B)'
  if (s >= 2.5) return 'Cukup (C)'
  if (s >= 1.5) return 'Kurang (D)'
  return 'Sangat Kurang (E)'
}

const predikatBadgeClass = (score) => {
  const s = parseFloat(score)
  if (maxScore.value > 10) {
    if (s >= 90) return 'bg-success-subtle text-success border border-success-subtle'
    if (s >= 80) return 'bg-primary-subtle text-primary border border-primary-subtle'
    if (s >= 70) return 'bg-warning-subtle text-warning-emphasis border border-warning-subtle'
    if (s >= 60) return 'bg-danger-subtle text-danger border border-danger-subtle'
    return 'bg-dark text-white'
  }
  if (s >= 4.5) return 'bg-success-subtle text-success border border-success-subtle'
  if (s >= 3.5) return 'bg-primary-subtle text-primary border border-primary-subtle'
  if (s >= 2.5) return 'bg-warning-subtle text-warning-emphasis border border-warning-subtle'
  if (s >= 1.5) return 'bg-danger-subtle text-danger border border-danger-subtle'
  return 'bg-dark text-white'
}

const frequencyLabel = (f) => {
  if (!f) return '-'
  const upper = String(f).trim().toUpperCase()
  const map = {
    'B': 'Bulanan',
    'BULANAN': 'Bulanan',
    'M': 'Mingguan',
    'MINGGUAN': 'Mingguan',
    'H': 'Harian',
    'HARIAN': 'Harian',
    'K': 'Kuartalan',
    'KUARTALAN': 'Kuartalan',
    'S': 'Semesteran',
    'SEMESTERAN': 'Semesteran',
    'T': 'Tahunan',
    'TAHUNAN': 'Tahunan',
    'A': 'Tahunan',
    'I': 'Insidental',
    'INSIDENTAL': 'Insidental'
  }
  return map[upper] || f
}

const frequencyBadgeClass = (f) => {
  if (!f) return 'bg-light text-muted border'
  const upper = String(f).trim().toUpperCase()
  if (upper === 'B' || upper === 'BULANAN') return 'bg-primary-subtle text-primary border border-primary-subtle'
  if (upper === 'M' || upper === 'MINGGUAN') return 'bg-info-subtle text-info border border-info-subtle'
  if (upper === 'H' || upper === 'HARIAN') return 'bg-warning-subtle text-warning-emphasis border border-warning-subtle'
  if (upper === 'K' || upper === 'KUARTALAN') return 'bg-secondary-subtle text-secondary border border-secondary-subtle'
  if (upper === 'I' || upper === 'INSIDENTAL') return 'bg-danger-subtle text-danger border border-danger-subtle'
  return 'bg-light text-dark border'
}

const getSelectedLevelLabel = (ti) => {
  const levelId = scoreMap.value[ti.id]?.rating_level_id
  const level = ratingLevels.value.find(l => l.id === levelId)
  return level ? level.label : '—'
}

const formatDate = (dateStr) => {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('id-ID', {
    day: 'numeric', month: 'short', year: 'numeric'
  })
}

// ── TRANSAKSI 1: SIMPAN SKOR (BERDASARKAN FREKUENSI / PERIODE) ─
const submitScores = async () => {
  // Ambil hanya indikator yang telah diisi skor secara valid
  const scoredItems = templateIndicators.value.filter(ti => {
    const s = scoreMap.value[ti.id]?.raw_score
    return s !== null && s !== undefined && s !== '' && !isNaN(s)
  })

  if (scoredItems.length === 0) {
    toast.warning('Silakan berikan nilai capaian pada minimal satu indikator terlebih dahulu.')
    return
  }

  // Jika sedang membuka tab frekuensi tertentu dan ada indikator di tab tersebut yang belum terisi
  const unfilledInActiveTab = activeTabIndicators.value.filter(ti => {
    const s = scoreMap.value[ti.id]?.raw_score
    return s === null || s === undefined || s === '' || isNaN(s)
  }).length

  if (unfilledInActiveTab > 0 && selectedFrequencyTab.value !== 'all') {
    const result = await Swal.fire({
      title: 'Terdapat Indikator Belum Dinilai',
      text: `Pada ${activeTabLabel.value}, terdapat ${unfilledInActiveTab} indikator yang belum diisi. Tetap simpan nilai indikator yang sudah diisi? Indikator frekuensi lain tidak akan terpengaruh.`,
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#008779',
      cancelButtonColor: '#6c757d',
      confirmButtonText: 'Ya, Simpan Nilai',
      cancelButtonText: 'Batal'
    })
    if (!result.isConfirmed) return
  }

  const scores = scoredItems.map(ti => ({
    template_indicator_id: ti.id,
    rating_level_id: scoreMap.value[ti.id]?.rating_level_id || null,
    raw_score: parseFloat(scoreMap.value[ti.id].raw_score),
    notes: scoreMap.value[ti.id]?.notes || '',
  }))

  saving.value = true
  try {
    const { data } = await api.post(`/kpi/assessments/${route.params.id}/score`, {
      scores,
      reviewer_notes: reviewerNotes.value,
    })
    toast.success(`Penilaian berhasil disimpan! Skor Akhir: ${data.final_score}`)
    if (data.data) {
      assessment.value = data.data
      assessment.value.final_score = data.final_score
    }
  } catch (e) {
    toast.error(e.response?.data?.message || 'Gagal menyimpan penilaian')
  } finally {
    saving.value = false
  }
}

// ── TRANSAKSI 2: SETUJUI PENILAIAN ────────────────────────────
const approveAssessment = async () => {
  const result = await Swal.fire({
    title: 'Setujui Penilaian KPI?',
    text: 'Setelah disetujui, lembar penilaian ini akan dikunci dan nilai tidak dapat diubah kembali.',
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#008779',
    cancelButtonColor: '#6c757d',
    confirmButtonText: 'Ya, Setujui',
    cancelButtonText: 'Batal'
  })

  if (!result.isConfirmed) return

  try {
    const { data } = await api.post(`/kpi/assessments/${route.params.id}/approve`)
    toast.success('Penilaian KPI berhasil disetujui')
    if (data.data) {
      assessment.value = data.data
    } else {
      assessment.value.status = 'approved'
    }
  } catch (e) {
    toast.error('Gagal menyetujui penilaian')
  }
}

// ── TRANSAKSI 3: RESET PENILAIAN ──────────────────────────────
const resetAssessment = async () => {
  const result = await Swal.fire({
    title: 'Reset Penilaian KPI?',
    text: 'Semua nilai indikator dan catatan evaluasi yang telah diinput akan dikosongkan kembali ke status Menunggu.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#dc2626',
    cancelButtonColor: '#6c757d',
    confirmButtonText: 'Ya, Reset Penilaian',
    cancelButtonText: 'Batal'
  })

  if (!result.isConfirmed) return

  try {
    const { data } = await api.post(`/kpi/assessments/${route.params.id}/reset`)
    toast.success('Penilaian berhasil direset')
    if (data.data) {
      assessment.value = data.data
      reviewerNotes.value = ''
      templateIndicators.value.forEach(ti => {
        scoreMap.value[ti.id] = {
          rating_level_id: null,
          raw_score: null,
          notes: '',
        }
      })
    } else {
      window.location.reload()
    }
  } catch (e) {
    toast.error('Gagal me-reset penilaian')
  }
}

// ── TRANSAKSI 4: EXPORT PDF ───────────────────────────────────
const exportPdf = async () => {
  downloading.value = true
  try {
    const response = await api.get(`/kpi/assessments/${route.params.id}/pdf`, {
      responseType: 'blob'
    })
    const url = window.URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }))
    const link = document.createElement('a')
    link.href = url
    const empName = (assessment.value?.employee?.name || 'Assessment').replace(/ /g, '_')
    link.setAttribute('download', `KPI_Assessment_${empName}.pdf`)
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
    toast.success('File PDF KPI berhasil di-download')
  } catch (e) {
    toast.error('Gagal mengunduh file PDF')
  } finally {
    downloading.value = false
  }
}

onMounted(async () => {
  try {
    const { data } = await api.get(`/kpi/assessments/${route.params.id}`)
    assessment.value = data.data
    templateIndicators.value = data.data.template?.template_indicators || []
    ratingLevels.value = data.data.template?.rating_scale?.levels || []
    reviewerNotes.value = data.data.reviewer_notes || ''

    templateIndicators.value.forEach(ti => {
      const existing = (data.data.scores || []).find(s => s.template_indicator_id === ti.id)
      scoreMap.value[ti.id] = {
        rating_level_id: existing?.rating_level_id || null,
        raw_score: existing?.raw_score !== undefined && existing?.raw_score !== null ? parseFloat(existing.raw_score) : null,
        notes: existing?.notes || '',
      }
    })

    // Otomatis aktifkan tab 'period_match' jika ada indikator yang cocok dengan fase periode
    if (hasPeriodMatchIndicators.value) {
      selectedFrequencyTab.value = 'period_match'
    } else {
      selectedFrequencyTab.value = 'all'
    }
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.font-size-10 {
  font-size: 10px;
}
.font-size-11 {
  font-size: 11px;
}
.font-size-12 {
  font-size: 12px;
}
.font-size-13 {
  font-size: 13px;
}
.font-size-14 {
  font-size: 14px;
}

.p-1-5 {
  padding: 0.375rem;
}
.p-2-5 {
  padding: 0.625rem;
}
.mb-0-5 {
  margin-bottom: 0.125rem;
}
.mt-1-5 {
  margin-top: 0.375rem;
}
.gap-1-5 {
  gap: 0.375rem;
}
.px-1-5 {
  padding-left: 0.375rem;
  padding-right: 0.375rem;
}
.px-2-5 {
  padding-left: 0.625rem;
  padding-right: 0.625rem;
}
.py-0-5 {
  padding-top: 0.125rem;
  padding-bottom: 0.125rem;
}
.py-1-5 {
  padding-top: 0.375rem;
  padding-bottom: 0.375rem;
}

.avatar-circle {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: rgba(0, 135, 121, 0.12);
  color: #008779;
  font-weight: 700;
  font-size: 1.25rem;
  display: flex;
  align-items: center;
  justify-content: center;
}

.btn-link-clean {
  background: none;
  border: none;
  padding: 0;
  color: #008779;
  text-decoration: underline;
  cursor: pointer;
  font-weight: 500;
}
.btn-link-clean:hover {
  color: #0c768a;
}

.transition-all {
  transition: all 0.2s ease-in-out;
}

.print-only {
  display: none !important;
}

@media print {
  .no-print, nav, header, sidebar, .navbar, .footer, .btn, .btn-back {
    display: none !important;
  }

  .print-only {
    display: block !important;
  }

  body, #app, .kpi-assessment-page {
    background: #fff !important;
    color: #000 !important;
    padding: 0 !important;
    margin: 0 !important;
  }

  .card {
    border: 1px solid #ddd !important;
    box-shadow: none !important;
  }

  .table {
    border: 1px solid #ddd !important;
  }

  .table th, .table td {
    padding: 6px 10px !important;
    border-color: #ccc !important;
  }
}
</style>
