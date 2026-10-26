<template>
  <div class="kpi-indicators-page">
    <!-- Top Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
      <div>
        <div class="d-flex align-items-center gap-2 mb-1">
          <h5 class="fw-bold text-dark mb-0">Indikator Kinerja & Poin Penilaian KPI</h5>
          <!-- Division Scope Badge for Employee Login -->
          <span v-if="isEmployeeUser && currentActiveDivision" class="badge bg-primary-subtle text-primary border border-primary-subtle font-size-12 px-2.5 py-1 fw-semibold">
            Divisi: {{ currentActiveDivision }}
          </span>
          <span v-else-if="!isEmployeeUser" class="badge bg-secondary-subtle text-secondary border border-secondary-subtle font-size-11 px-2 py-1">
            Mode Administrator HR
          </span>
        </div>
        <p class="text-muted small mb-0">
          Standar indikator kinerja, bobot, frekuensi, rumus ukur, target, serta rubrik capaian (Grade A s/d E)
          <span v-if="isEmployeeUser && currentActiveDivision">
            khusus untuk divisi <strong>{{ currentActiveDivision }}</strong>.
          </span>
          <span v-else>
            untuk seluruh jabatan dan divisi.
          </span>
        </p>
      </div>

      <!-- Header Actions -->
      <div class="d-flex align-items-center gap-2">
        <div class="btn-group p-1 bg-light rounded-3 border" role="group">
          <button 
            type="button" 
            class="btn btn-sm px-3 rounded-2 fw-medium transition-all"
            :class="activeTab === 'rubric' ? 'btn-primary shadow-sm text-white' : 'btn-light text-muted'"
            @click="activeTab = 'rubric'"
          >
            Katalog & Rubrik Penilaian
          </button>
          <button 
            type="button" 
            class="btn btn-sm px-3 rounded-2 fw-medium transition-all"
            :class="activeTab === 'crud' ? 'btn-primary shadow-sm text-white' : 'btn-light text-muted'"
            @click="activeTab = 'crud'"
          >
            Kelola Master Data
          </button>
        </div>
      </div>
    </div>

    <!-- TAB 1: KATALOG & POIN PENILAIAN (RUBRIK) -->
    <div v-if="activeTab === 'rubric'">
      <!-- Filter Card -->
      <div class="card border-0 shadow-sm rounded-3 mb-3">
        <div class="card-body p-3">
          <div class="row g-2 align-items-center">
            
            <!-- Case 1: Employee with Single Division (Locked & Informational) -->
            <div v-if="isEmployeeUser && visibleDivisionOptions.length <= 1" class="col-12 col-md-5">
              <label class="form-label font-size-11 text-secondary fw-semibold mb-1">Divisi Anda</label>
              <div class="d-flex align-items-center gap-2 p-1.5 px-2 bg-light rounded-2 border">
                <span class="badge bg-primary text-white font-size-11 px-2 py-1 rounded-pill">
                  {{ currentActiveDivision || 'Divisi Ditugaskan' }}
                </span>
                <span class="font-size-12 text-muted">
                  {{ userDepartmentName ? `(Dept. ${userDepartmentName})` : '' }}
                </span>
                <span class="badge bg-white text-secondary border font-size-10 rounded-pill ms-auto">
                  {{ filteredGroups.length }} Jabatan
                </span>
              </div>
            </div>

            <!-- Case 2: Employee with Multiple Managed Divisions (Scoped Pills) -->
            <div v-else-if="isEmployeeUser && visibleDivisionOptions.length > 1" class="col-12 col-md-5">
              <label class="form-label font-size-11 text-secondary fw-semibold mb-1">Pilih Divisi Dikelola</label>
              <div class="d-flex flex-wrap gap-1">
                <button
                  v-for="div in visibleDivisionOptions"
                  :key="div"
                  class="btn btn-sm rounded-pill font-size-12 px-3 py-1"
                  :class="selectedDivision === div ? 'btn-primary text-white fw-bold shadow-sm' : 'btn-outline-secondary'"
                  @click="selectedDivision = div; selectedPosition = 'ALL'"
                >
                  {{ div }}
                  <span class="badge bg-white text-primary ms-1 font-size-10 rounded-pill" v-if="selectedDivision === div">
                    {{ countPositionsByDivision(div) }}
                  </span>
                </button>
              </div>
            </div>

            <!-- Case 3: Admin User (Can browse all divisions or select specific) -->
            <div v-else class="col-12 col-md-5">
              <label class="form-label font-size-11 text-secondary fw-semibold mb-1">Filter Divisi / Departemen</label>
              <select v-model="selectedDivision" class="form-select form-select-sm bg-light border-0" @change="selectedPosition = 'ALL'">
                <option value="ALL">Semua Divisi & Departemen ({{ allDivisionsInTemplates.length }} Divisi)</option>
                <option v-for="div in allDivisionsInTemplates" :key="div" :value="div">
                  {{ div }} ({{ countPositionsByDivision(div) }} Jabatan)
                </option>
              </select>
            </div>

            <!-- Filter Jabatan -->
            <div class="col-12 col-md-4">
              <label class="form-label font-size-11 text-secondary fw-semibold mb-1">Filter Jabatan</label>
              <select v-model="selectedPosition" class="form-select form-select-sm bg-light border-0">
                <option value="ALL">
                  Semua Jabatan 
                  <span v-if="selectedDivision !== 'ALL'">({{ filteredPositionOptions.length }})</span>
                </option>
                <option v-for="pos in filteredPositionOptions" :key="pos" :value="pos">
                  {{ pos }}
                </option>
              </select>
            </div>

            <!-- Search Filter -->
            <div class="col-12 col-md-3">
              <label class="form-label font-size-11 text-secondary fw-semibold mb-1">Cari Indikator / Rumus</label>
              <input 
                v-model="searchQuery" 
                type="text" 
                class="form-control form-control-sm bg-light border-0" 
                placeholder="Ketik kata kunci..." 
              />
            </div>
          </div>
        </div>
      </div>

      <!-- Clean Rubric Score Reference Bar -->
      <div class="rubric-reference-card p-3 rounded-3 mb-4 border bg-white shadow-sm">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
          <div class="d-flex flex-wrap align-items-center gap-2">
            <span class="text-secondary fw-semibold font-size-12 me-1">Skala Capaian (0 - 100):</span>
            <div class="rubric-pill grade-a">
              <span class="pill-code">A (100)</span>
              <span class="pill-desc">Sangat Baik (≥ 100%)</span>
            </div>
            <div class="rubric-pill grade-b">
              <span class="pill-code">B (85)</span>
              <span class="pill-desc">Baik (90% – 99%)</span>
            </div>
            <div class="rubric-pill grade-c">
              <span class="pill-code">C (70)</span>
              <span class="pill-desc">Cukup (80% – 89%)</span>
            </div>
            <div class="rubric-pill grade-d">
              <span class="pill-code">D (60)</span>
              <span class="pill-desc">Kurang (70% – 79%)</span>
            </div>
            <div class="rubric-pill grade-e">
              <span class="pill-code">E (40)</span>
              <span class="pill-desc">Sangat Kurang (&lt; 70%)</span>
            </div>
          </div>

          <button 
            type="button"
            class="btn btn-sm btn-light border font-size-11 px-3 py-1 text-secondary text-nowrap rounded-2"
            @click="expandAll = !expandAll"
          >
            {{ expandAll ? 'Tutup Semua Rincian Poin' : 'Buka Semua Rincian Poin' }}
          </button>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loadingRubric" class="text-center py-5">
        <div class="spinner-border text-primary spinner-border-sm" role="status"></div>
        <p class="text-muted small mt-2">Memuat katalog indikator KPI...</p>
      </div>

      <!-- Empty State -->
      <div v-else-if="filteredGroups.length === 0" class="card border-0 shadow-sm rounded-3 text-center py-5 text-muted bg-white">
        <h6 class="fw-bold text-dark mb-1">Indikator Tidak Ditemukan</h6>
        <p class="small text-muted mb-0">
          <span v-if="isEmployeeUser">
            Tidak ada indikator yang sesuai dengan divisi Anda atau kata kunci pencarian.
          </span>
          <span v-else>
            Coba ubah kata kunci pencarian atau filter divisi/jabatan.
          </span>
        </p>
      </div>

      <!-- Indicator Groups per Position -->
      <div v-else class="space-y-4">
        <div 
          v-for="group in filteredGroups" 
          :key="group.positionName + '_' + group.divisionName" 
          class="card border-0 shadow-sm rounded-3 mb-4 overflow-hidden position-group-card"
        >
          <!-- Position Card Header -->
          <div class="card-header bg-white border-bottom p-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
              <div class="d-flex align-items-center gap-2">
                <span class="badge bg-light text-primary border font-size-11 px-2 py-1">
                  {{ group.divisionName }}
                </span>
                <span v-if="group.departmentName && group.departmentName !== group.divisionName" class="badge bg-light text-secondary border font-size-10 px-2 py-0.5">
                  Dept: {{ group.departmentName }}
                </span>
                <h6 class="fw-bold text-dark mb-0 font-size-15">
                  {{ group.positionName }}
                </h6>
              </div>
              <small class="text-muted font-size-11 d-block mt-1">
                {{ group.indicators.length }} Indikator Standar Terdaftar
              </small>
            </div>
            <div class="d-flex align-items-center gap-2">
              <span class="badge bg-success-subtle text-success border border-success-subtle font-size-12 px-2.5 py-1 fw-semibold">
                Total Bobot: 100%
              </span>
              <button 
                type="button" 
                @click="openEditIndicatorsModal(group)" 
                class="btn btn-sm btn-primary rounded-2 px-3 py-1 font-size-12 fw-medium shadow-sm d-flex align-items-center gap-1.5"
                title="Sesuaikan indikator penilaian untuk anak buah jabatan ini"
              >
                <i class="mdi mdi-playlist-edit font-size-14"></i>
                <span>Ubah Indikator Penilaian</span>
              </button>
            </div>
          </div>

          <!-- Indicator Table -->
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 font-size-13">
              <thead class="table-light font-size-11 text-secondary text-uppercase">
                <tr>
                  <th style="width: 45px;" class="text-center">No</th>
                  <th style="min-width: 260px;">Indikator KPI & Cara Ukur</th>
                  <th style="width: 80px;" class="text-center">Bobot</th>
                  <th style="width: 100px;" class="text-center">Frekuensi</th>
                  <th style="min-width: 170px;">Target Standar</th>
                  <th style="min-width: 320px;">Rubrik Poin Penilaian (Grade A – E)</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(ind, idx) in group.indicators" :key="ind.id || idx">
                  <td class="text-center fw-medium text-muted font-size-12">{{ idx + 1 }}</td>
                  <td>
                    <div class="fw-semibold text-dark">{{ ind.name }}</div>
                    <div v-if="ind.calculation_method" class="font-size-11 text-secondary mt-1">
                      <span class="text-muted fst-normal">Rumus:</span> {{ ind.calculation_method }}
                    </div>
                  </td>
                  <td class="text-center">
                    <span class="badge bg-primary-subtle text-primary fw-bold font-size-12 px-2 py-1 border border-primary-subtle">
                      {{ ind.weight }}%
                    </span>
                  </td>
                  <td class="text-center">
                    <span class="badge rounded-pill font-size-11 px-2.5 py-1" :class="frequencyBadgeClass(ind.frequency)">
                      {{ frequencyLabel(ind.frequency) }}
                    </span>
                  </td>
                  <td>
                    <div class="font-size-12 text-dark bg-light px-2.5 py-1.5 rounded-2 border">
                      <span class="text-secondary font-size-11">Target:</span> {{ ind.target || '100% dari target' }}
                    </div>
                  </td>
                  <td>
                    <!-- Scoring Points Accordion -->
                    <div class="scoring-points-container">
                      <button 
                        type="button"
                        class="btn btn-xs py-1 px-2 rounded-2 font-size-11 mb-2 border text-secondary"
                        :class="isRubricExpanded(ind.id) ? 'btn-light border-secondary-subtle' : 'btn-outline-secondary'"
                        @click="toggleSingleRubric(ind.id)"
                      >
                        {{ isRubricExpanded(ind.id) ? 'Sembunyikan Poin (5 Level)' : 'Lihat Standar Poin (5 Level)' }}
                      </button>

                      <!-- Points List -->
                      <div v-if="isRubricExpanded(ind.id)" class="d-flex flex-column gap-1 scoring-ladder">
                        <div 
                          v-for="point in (ind.scoring_points || defaultScoringPoints(ind))" 
                          :key="point.point"
                          class="p-2 rounded-2 border font-size-11 d-flex align-items-start gap-2"
                          :class="pointRowClass(point.point)"
                        >
                          <span class="badge font-size-10 px-2 py-0.5 rounded-pill flex-shrink-0" :class="pointBadgeClass(point.point)">
                            Poin {{ point.point }} ({{ point.grade }} - {{ point.score }})
                          </span>
                          <div>
                            <strong class="d-block text-dark font-size-11 mb-0.5">{{ point.label }}</strong>
                            <span class="text-secondary font-size-11">{{ point.criteria }}</span>
                          </div>
                        </div>
                      </div>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- TAB 2: KELOLA MASTER DATA CRUD (CRUDPAGE) -->
    <div v-show="activeTab === 'crud'">
      <CrudPage
        ref="crudRef"
        title="Master Indikator KPI"
        singular="Indikator"
        endpoint="/kpi/indicators"
        :columns="[
          { key: 'name', label: 'Nama Indikator' },
          { key: 'category', label: 'Kategori / Dept' },
          { key: 'frequency', label: 'Frekuensi' },
          { key: 'calculation_method', label: 'Rumus / Cara Ukur' },
          { key: 'measurement_unit', label: 'Satuan' },
          { key: 'type', label: 'Tipe' },
        ]"
        :form-fields="formFields"
        :filters="[
          { key: 'type', label: 'Tipe', options: [{ value: 'individual', label: 'Individual' }, { value: 'division', label: 'Divisi' }, { value: 'both', label: 'Keduanya' }] },
          { key: 'is_active', label: 'Status', options: [{ value: 1, label: 'Aktif' }, { value: 0, label: 'Nonaktif' }] },
        ]"
      >
        <template #cell-frequency="{ value }">
          <span v-if="value" class="badge bg-light text-primary border">
            {{ { B: 'Bulanan', M: 'Mingguan', H: 'Harian', K: 'Kuartalan', I: 'Insidental' }[value] || value }}
          </span>
          <span v-else class="text-muted">-</span>
        </template>

        <template #cell-calculation_method="{ value }">
          <span v-if="value" class="small text-muted">{{ value }}</span>
          <span v-else class="text-muted">-</span>
        </template>

        <template #cell-type="{ value }">
          <span class="badge" :class="{ 'bg-primary': value === 'individual', 'bg-success': value === 'division', 'bg-warning text-dark': value === 'both' }">
            {{ { individual: 'Individual', division: 'Divisi', both: 'Keduanya' }[value] || value }}
          </span>
        </template>

        <template #form="{ form }">
          <div class="row g-3">
            <div class="col-md-8">
              <label class="form-label fw-semibold small mb-1">Nama Indikator <span class="text-danger">*</span></label>
              <input v-model="form.name" type="text" class="form-control" required placeholder="Contoh: Pencapaian Target Omzet" />
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold small mb-1">Kode</label>
              <input v-model="form.code" type="text" class="form-control" placeholder="IND-001" />
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold small mb-1">Kategori / Departemen</label>
              <input v-model="form.category" type="text" class="form-control" placeholder="Sales & Marketing / Operational / FAT / HR & GA" />
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold small mb-1">Frekuensi</label>
              <select v-model="form.frequency" class="form-select">
                <option value="">Pilih Frekuensi...</option>
                <option value="H">Harian (H)</option>
                <option value="M">Mingguan (M)</option>
                <option value="B">Bulanan (B)</option>
                <option value="K">Kuartalan (K)</option>
                <option value="I">Insidental (I)</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold small mb-1">Satuan Pengukuran</label>
              <input v-model="form.measurement_unit" type="text" class="form-control" placeholder="%, unit, transaksi, Rp..." />
            </div>
            <div class="col-md-12">
              <label class="form-label fw-semibold small mb-1">Rumus / Cara Ukur</label>
              <input v-model="form.calculation_method" type="text" class="form-control" placeholder="Contoh: Realisasi Omzet ÷ Target Omzet x 100%" />
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold small mb-1">Tipe <span class="text-danger">*</span></label>
              <select v-model="form.type" class="form-select" required>
                <option value="individual">Individual (per orang)</option>
                <option value="division">Divisi</option>
                <option value="both">Keduanya</option>
              </select>
            </div>
            <div class="col-md-8">
              <label class="form-label fw-semibold small mb-1">Skala Penilaian Default</label>
              <Multiselect v-model="form.rating_scale_id" :options="async (q) => fetchOptions('/kpi/rating-scales', q)" searchable placeholder="Pilih skala penilaian..." />
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold small mb-1">Deskripsi & Catatan Tambahan</label>
              <textarea v-model="form.description" rows="3" class="form-control" placeholder="Keterangan lebih lanjut mengenai indikator kinerja ini..."></textarea>
            </div>
          </div>
        </template>
      </CrudPage>
    </div>

    <!-- Modal Sesuaikan Indikator Penilaian Anak Buah -->
    <div v-if="showEditIndicatorsModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(15, 23, 42, 0.6); z-index: 1055;">
      <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-3 overflow-hidden">
          
          <!-- Modal Header -->
          <div class="modal-header bg-light border-bottom p-3">
            <div>
              <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                <span class="badge bg-primary text-white font-size-11 px-2.5 py-1 rounded-pill">
                  Pengaturan Indikator KPI Anak Buah
                </span>
                <span class="badge bg-light text-primary border font-size-11">
                  {{ selectedGroup?.divisionName }}
                </span>
                <span v-if="selectedGroup?.departmentName" class="badge bg-light text-secondary border font-size-10">
                  Dept: {{ selectedGroup?.departmentName }}
                </span>
              </div>
              <h5 class="modal-title fw-bold text-dark mb-0 font-size-16">
                Ubah Indikator Jabatan: {{ selectedGroup?.positionName }}
              </h5>
            </div>
            <div class="d-flex align-items-center gap-3">
              <span 
                class="badge font-size-12 px-3 py-1.5 rounded-pill fw-bold shadow-xs"
                :class="editTotalWeight === 100 ? 'bg-success text-white' : 'bg-danger text-white'"
              >
                Total Bobot: {{ editTotalWeight }}% {{ editTotalWeight === 100 ? '✓ (Sesuai 100%)' : '⚠ (Harus 100%)' }}
              </span>
              <button type="button" class="btn-close" @click="showEditIndicatorsModal = false"></button>
            </div>
          </div>

          <!-- Modal Body -->
          <div class="modal-body p-3">
            <div v-if="editTotalWeight !== 100" class="alert alert-warning py-2 px-3 font-size-12 mb-3 d-flex align-items-center gap-2">
              <i class="mdi mdi-alert-circle-outline font-size-16 text-warning-emphasis"></i>
              <span>Total bobot saat ini bernilai <strong>{{ editTotalWeight }}%</strong>. Disarankan total bobot berjumlah tepat <strong>100%</strong> agar penilaian anak buah menghasilkan evaluasi kinerja yang seimbang.</span>
            </div>

            <!-- Table of editable indicators -->
            <div class="table-responsive border rounded-3 mb-3 bg-white">
              <table class="table table-hover align-middle mb-0 font-size-13">
                <thead class="table-light font-size-11 text-secondary text-uppercase">
                  <tr>
                    <th style="width: 40px;" class="text-center">No</th>
                    <th style="min-width: 250px;">Indikator KPI</th>
                    <th style="width: 95px;" class="text-center">Bobot (%)</th>
                    <th style="width: 140px;" class="text-center">Frekuensi</th>
                    <th style="min-width: 180px;">Target Capaian</th>
                    <th style="min-width: 220px;">Rumus / Cara Ukur</th>
                    <th style="width: 60px;" class="text-center">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(item, idx) in editingIndicators" :key="idx">
                    <td class="text-center text-muted font-size-12 fw-medium">{{ idx + 1 }}</td>
                    <td>
                      <!-- Dropdown Master Indicator -->
                      <select 
                        v-model="item.indicator_id" 
                        @change="onSelectMasterIndicator(item)" 
                        class="form-select form-select-sm font-size-12 fw-semibold"
                      >
                        <option v-for="ind in rawIndicators" :key="ind.id" :value="ind.id">
                          {{ ind.name }} ({{ ind.category || 'General' }})
                        </option>
                      </select>
                      <small class="text-muted font-size-11 d-block mt-1" v-if="item.name">
                        Label: {{ item.name }}
                      </small>
                    </td>
                    <td class="text-center">
                      <div class="input-group input-group-sm">
                        <input 
                          type="number" 
                          v-model.number="item.weight" 
                          min="1" 
                          max="100" 
                          step="1"
                          class="form-control form-control-sm text-center fw-bold font-size-12"
                          placeholder="%"
                        />
                        <span class="input-group-text font-size-11 px-1.5">%</span>
                      </div>
                    </td>
                    <td>
                      <select v-model="item.frequency" class="form-select form-select-sm font-size-12">
                        <option value="H">Harian (H)</option>
                        <option value="M">Mingguan (M)</option>
                        <option value="B">Bulanan (B)</option>
                        <option value="K">Kuartalan (K)</option>
                        <option value="I">Insidental (I)</option>
                      </select>
                    </td>
                    <td>
                      <input 
                        type="text" 
                        v-model="item.target" 
                        class="form-control form-control-sm font-size-12" 
                        placeholder="Contoh: 100% dari target atau 5 transaksi/hari"
                      />
                    </td>
                    <td>
                      <input 
                        type="text" 
                        v-model="item.calculation_method" 
                        class="form-control form-control-sm font-size-12 text-muted" 
                        placeholder="Contoh: (Realisasi / Target) x 100%"
                      />
                    </td>
                    <td class="text-center">
                      <button 
                        type="button" 
                        @click="removeEditItem(idx)" 
                        class="btn btn-outline-danger btn-sm p-1 rounded-2" 
                        title="Hapus indikator dari jabatan ini"
                      >
                        <i class="mdi mdi-trash-can-outline font-size-14"></i>
                      </button>
                    </td>
                  </tr>

                  <tr v-if="editingIndicators.length === 0">
                    <td colspan="7" class="text-center py-4 text-muted small">
                      Belum ada indikator. Silakan klik tombol "+ Tambah Indikator" di bawah.
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Bottom Actions -->
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
              <button 
                type="button" 
                @click="addNewEditItem" 
                class="btn btn-outline-primary btn-sm rounded-2 d-inline-flex align-items-center gap-1 font-size-12 px-3 py-1.5 fw-medium"
              >
                <i class="mdi mdi-plus-circle-outline"></i>
                <span>Tambah Indikator untuk Jabatan Ini</span>
              </button>

              <div class="small text-muted font-size-12">
                Perubahan indikator akan diterapkan pada lembar penilaian KPI anak buah untuk jabatan ini.
              </div>
            </div>
          </div>

          <!-- Modal Footer -->
          <div class="modal-footer bg-light border-top p-2.5 d-flex justify-content-between align-items-center">
            <span class="font-size-12 text-muted">
              {{ editingIndicators.length }} indikator terpasang
            </span>
            <div class="d-flex align-items-center gap-2">
              <button 
                type="button" 
                class="btn btn-secondary btn-sm rounded-2 px-3 py-1.5 font-size-12" 
                @click="showEditIndicatorsModal = false"
              >
                Batal
              </button>
              <button 
                type="button" 
                class="btn btn-primary btn-sm rounded-2 px-4 py-1.5 font-size-12 fw-medium shadow-sm d-inline-flex align-items-center gap-1"
                :disabled="savingIndicators"
                @click="saveIndicators"
              >
                <span v-if="savingIndicators" class="spinner-border spinner-border-sm me-1"></span>
                <i v-else class="mdi mdi-check-circle-outline"></i>
                <span>{{ savingIndicators ? 'Menyimpan...' : 'Simpan Perubahan Indikator' }}</span>
              </button>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useAuthStore } from '../../stores/auth'
import CrudPage from '../../components/CrudPage.vue'
import Multiselect from '@vueform/multiselect'
import '@vueform/multiselect/themes/default.css'
import api from '../../api'
import { toast } from 'vue3-toastify'
import 'vue3-toastify/dist/index.css'

const auth = useAuthStore()

const activeTab = ref('rubric')
const loadingRubric = ref(true)
const rawIndicators = ref([])
const rawTemplates = ref([])

const selectedDivision = ref('ALL')
const selectedPosition = ref('ALL')
const searchQuery = ref('')
const expandAll = ref(false)
const expandedMap = ref({})

// Check if current user is logged in as an employee (from /login)
const isEmployeeUser = computed(() => {
  return auth.userType === 'employee'
})

// Scoped divisions list for employee
const userScopedDivisions = computed(() => {
  if (!isEmployeeUser.value) return []

  const divs = []
  // 1. Managed divisions from supervisor assignments
  if (auth.employee?.managed_divisions && Array.isArray(auth.employee.managed_divisions)) {
    auth.employee.managed_divisions.forEach(d => {
      if (d.name && !divs.includes(d.name)) divs.push(d.name)
    })
  }
  // 2. Direct division
  if (auth.employee?.division && !divs.includes(auth.employee.division)) {
    divs.push(auth.employee.division)
  }

  return divs
})

const userDepartmentName = computed(() => {
  return auth.employee?.department || ''
})

const formFields = [
  { key: 'name', label: 'Nama', required: true },
  { key: 'code', label: 'Kode' },
  { key: 'category', label: 'Kategori' },
  { key: 'frequency', label: 'Frekuensi' },
  { key: 'calculation_method', label: 'Rumus / Cara Ukur' },
  { key: 'type', label: 'Tipe', required: true },
  { key: 'measurement_unit', label: 'Satuan' },
  { key: 'rating_scale_id', label: 'Skala Penilaian' },
  { key: 'description', label: 'Deskripsi' },
]

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

const pointRowClass = (point) => {
  const p = parseInt(point)
  if (p === 5) return 'bg-success-subtle border-success-subtle'
  if (p === 4) return 'bg-primary-subtle border-primary-subtle'
  if (p === 3) return 'bg-warning-subtle border-warning-subtle'
  if (p === 2) return 'bg-danger-subtle border-danger-subtle'
  return 'bg-light border-secondary-subtle'
}

const pointBadgeClass = (point) => {
  const p = parseInt(point)
  if (p === 5) return 'bg-success text-white'
  if (p === 4) return 'bg-primary text-white'
  if (p === 3) return 'bg-warning text-dark'
  if (p === 2) return 'bg-danger text-white'
  return 'bg-dark text-white'
}

const isRubricExpanded = (id) => {
  if (expandAll.value) return true
  return !!expandedMap.value[id]
}

const toggleSingleRubric = (id) => {
  expandedMap.value[id] = !expandedMap.value[id]
}

const defaultScoringPoints = (ind) => {
  return [
    { point: 5, grade: 'A', score: 100, label: 'Sangat Baik', criteria: 'Capaian ≥ 100% target standar yang ditentukan.' },
    { point: 4, grade: 'B', score: 85, label: 'Baik', criteria: 'Capaian 90% - 99.9% target standar dengan kualitas baik.' },
    { point: 3, grade: 'C', score: 70, label: 'Cukup', criteria: 'Capaian 80% - 89.9% target standar (mendekati target).' },
    { point: 2, grade: 'D', score: 60, label: 'Kurang', criteria: 'Capaian 70% - 79.9% target standar (di bawah ekspektasi).' },
    { point: 1, grade: 'E', score: 40, label: 'Sangat Kurang', criteria: 'Capaian < 70% target standar (perlu pembinaan khusus).' },
  ]
}

// Group indicators by Position from loaded templates
const groupedPositionData = computed(() => {
  const templateMap = new Map()

  rawTemplates.value.forEach(tmpl => {
    const posName = tmpl.position?.name || tmpl.name.replace(/^KPI - /, '')
    const deptName = tmpl.position?.department?.name || tmpl.template_indicators?.[0]?.indicator?.category || 'General'
    const divName = tmpl.division?.name || tmpl.position?.division?.name || deptName || 'General'

    // Group templates by normalized base position name and division
    const basePos = posName.toUpperCase().replace(/\s*\(CABANG.*?\)/i, '').trim()
    const mapKey = `${basePos}___${divName}`

    if (!templateMap.has(mapKey)) {
      templateMap.set(mapKey, {
        positionName: basePos,
        originalPositionName: posName,
        departmentName: deptName,
        divisionName: divName,
        templateId: tmpl.id,
        positionId: tmpl.position_id,
        divisionId: tmpl.division_id,
        ratingScaleId: tmpl.rating_scale_id,
        indicators: (tmpl.template_indicators || []).map(ti => ({
          id: ti.indicator?.id || ti.id,
          indicator_id: ti.indicator_id || ti.indicator?.id || ti.id,
          no: (ti.sort_order ?? 0) + 1,
          name: ti.indicator?.name || 'Indikator',
          weight: parseFloat(ti.weight) || 0,
          frequency: ti.frequency || ti.indicator?.frequency || 'B',
          calculation_method: ti.calculation_method || ti.indicator?.calculation_method || '',
          target: ti.target || '100% dari target',
          scoring_points: ti.scoring_points || ti.indicator?.scoring_points || null,
        }))
      })
    }
  })

  return Array.from(templateMap.values())
})

// All unique divisions available across templates
const allDivisionsInTemplates = computed(() => {
  const set = new Set()
  groupedPositionData.value.forEach(g => {
    if (g.divisionName) set.add(g.divisionName)
  })
  return Array.from(set).sort()
})

// Visible division options (strict scoping if employee login)
const visibleDivisionOptions = computed(() => {
  if (isEmployeeUser.value) {
    if (userScopedDivisions.value.length > 0) {
      // Find template divisions that match user scoped divisions
      const matched = allDivisionsInTemplates.value.filter(tmplDiv => {
        return userScopedDivisions.value.some(userDiv => {
          const t = tmplDiv.toLowerCase().trim()
          const u = userDiv.toLowerCase().trim()
          return t === u || t.includes(u) || u.includes(t)
        })
      })
      if (matched.length > 0) return matched
      return userScopedDivisions.value
    }

    // If employee has no division set but has department
    const userDept = (auth.employee?.department || '').toLowerCase().trim()
    if (userDept) {
      const matched = groupedPositionData.value
        .filter(g => g.departmentName.toLowerCase().includes(userDept.slice(0, 4)))
        .map(g => g.divisionName)
      const unique = Array.from(new Set(matched)).sort()
      if (unique.length > 0) return unique
    }

    return []
  }

  return allDivisionsInTemplates.value
})

// Active division label for badge
const currentActiveDivision = computed(() => {
  if (selectedDivision.value !== 'ALL' && selectedDivision.value) {
    return selectedDivision.value
  }
  if (visibleDivisionOptions.value.length === 1) {
    return visibleDivisionOptions.value[0]
  }
  if (userScopedDivisions.value.length > 0) {
    return userScopedDivisions.value.join(', ')
  }
  return ''
})

const countPositionsByDivision = (div) => {
  return groupedPositionData.value.filter(g => g.divisionName.toLowerCase() === div.toLowerCase()).length
}

// Filtered positions based on division
const filteredPositionOptions = computed(() => {
  return groupedPositionData.value
    .filter(g => {
      if (selectedDivision.value === 'ALL') {
        if (isEmployeeUser.value) {
          return visibleDivisionOptions.value.some(v => v.toLowerCase() === g.divisionName.toLowerCase())
        }
        return true
      }
      return g.divisionName.toLowerCase() === selectedDivision.value.toLowerCase()
    })
    .map(g => g.positionName)
})

// Filtered groups to render
const filteredGroups = computed(() => {
  const q = searchQuery.value.trim().toLowerCase()
  const targetDiv = selectedDivision.value

  return groupedPositionData.value.filter(g => {
    // 1. Division restriction
    if (isEmployeeUser.value) {
      // Must belong to one of user's visible scoped divisions
      const isAllowedDivision = visibleDivisionOptions.value.some(allowedDiv => {
        const t = g.divisionName.toLowerCase().trim()
        const a = allowedDiv.toLowerCase().trim()
        return t === a || t.includes(a) || a.includes(t)
      })
      if (!isAllowedDivision) return false

      // If a specific division is selected among allowed
      if (targetDiv !== 'ALL' && targetDiv) {
        const t = g.divisionName.toLowerCase().trim()
        const target = targetDiv.toLowerCase().trim()
        if (t !== target && !t.includes(target) && !target.includes(t)) return false
      }
    } else {
      // Admin filter
      if (targetDiv !== 'ALL' && targetDiv) {
        if (g.divisionName.toLowerCase() !== targetDiv.toLowerCase()) return false
      }
    }

    // 2. Position Filter
    if (selectedPosition.value !== 'ALL' && selectedPosition.value) {
      if (g.positionName !== selectedPosition.value) return false
    }

    // 3. Search Query Filter
    if (q) {
      const matchPos = g.positionName.toLowerCase().includes(q)
      const matchDept = g.departmentName.toLowerCase().includes(q)
      const matchDiv = g.divisionName.toLowerCase().includes(q)
      const matchInd = g.indicators.some(i => 
        i.name.toLowerCase().includes(q) || 
        (i.calculation_method && i.calculation_method.toLowerCase().includes(q)) ||
        (i.target && i.target.toLowerCase().includes(q))
      )
      if (!matchPos && !matchDept && !matchDiv && !matchInd) return false
    }

    return true
  })
})

const initializeDefaultDivision = () => {
  if (isEmployeeUser.value && visibleDivisionOptions.value.length > 0) {
    selectedDivision.value = visibleDivisionOptions.value[0]
  } else {
    selectedDivision.value = 'ALL'
  }
}

watch(visibleDivisionOptions, () => {
  initializeDefaultDivision()
})

const loadData = async () => {
  loadingRubric.value = true
  try {
    const [indRes, tmplRes] = await Promise.all([
      api.get('/kpi/indicators', { params: { per_page: 200 } }),
      api.get('/kpi/templates', { params: { per_page: 200, is_active: 1 } })
    ])
    rawIndicators.value = indRes.data?.data?.data || indRes.data?.data || []
    rawTemplates.value = tmplRes.data?.data?.data || tmplRes.data?.data || []
    initializeDefaultDivision()
  } catch (err) {
    console.error('Failed to load KPI indicators and templates:', err)
  } finally {
    loadingRubric.value = false
  }
}

const fetchOptions = async (endpoint, query, filters = {}) => {
  try {
    const { data } = await api.get(endpoint, { params: { search: query, per_page: 50, ...filters } })
    return (data.data?.data || data.data || []).map(item => ({ value: item.id, label: item.name }))
  } catch { return [] }
}

const showEditIndicatorsModal = ref(false)
const selectedGroup = ref(null)
const editingIndicators = ref([])
const savingIndicators = ref(false)

const editTotalWeight = computed(() => {
  return editingIndicators.value.reduce((sum, item) => sum + (parseFloat(item.weight) || 0), 0)
})

const openEditIndicatorsModal = (group) => {
  selectedGroup.value = group
  editingIndicators.value = group.indicators.map(ind => ({
    indicator_id: ind.indicator_id || ind.id,
    name: ind.name,
    weight: parseFloat(ind.weight) || 0,
    frequency: ind.frequency || 'B',
    calculation_method: ind.calculation_method || '',
    target: ind.target || '100% dari target',
    scoring_points: ind.scoring_points || null,
  }))
  showEditIndicatorsModal.value = true
}

const onSelectMasterIndicator = (item) => {
  const master = rawIndicators.value.find(i => i.id === item.indicator_id)
  if (master) {
    item.name = master.name
    if (!item.calculation_method && master.calculation_method) {
      item.calculation_method = master.calculation_method
    }
    if (!item.frequency && master.frequency) {
      item.frequency = master.frequency
    }
  }
}

const addNewEditItem = () => {
  const defaultMaster = rawIndicators.value[0] || {}
  editingIndicators.value.push({
    indicator_id: defaultMaster.id || null,
    name: defaultMaster.name || 'Indikator Baru',
    weight: 10,
    frequency: defaultMaster.frequency || 'B',
    calculation_method: defaultMaster.calculation_method || '',
    target: '100% dari target',
    scoring_points: null
  })
}

const removeEditItem = (index) => {
  editingIndicators.value.splice(index, 1)
}

const saveIndicators = async () => {
  if (editingIndicators.value.length === 0) {
    toast.error('Minimal harus ada 1 indikator penilaian')
    return
  }

  const hasInvalid = editingIndicators.value.some(i => !i.indicator_id)
  if (hasInvalid) {
    toast.error('Pastikan semua baris indikator sudah dipilih dari daftar')
    return
  }

  savingIndicators.value = true
  try {
    const payload = {
      indicators: editingIndicators.value.map(item => ({
        indicator_id: item.indicator_id,
        weight: parseFloat(item.weight) || 0,
        frequency: item.frequency || 'B',
        target: item.target || '100% dari target',
        calculation_method: item.calculation_method || null,
        scoring_points: item.scoring_points || null,
      }))
    }

    if (selectedGroup.value?.templateId) {
      await api.put(`/kpi/templates/${selectedGroup.value.templateId}`, payload)
    } else {
      await api.post('/kpi/templates', {
        name: `KPI - ${selectedGroup.value.positionName}`,
        scope: 'position',
        position_id: selectedGroup.value.positionId,
        division_id: selectedGroup.value.divisionId,
        is_active: 1,
        ...payload
      })
    }

    toast.success(`Indikator penilaian untuk jabatan ${selectedGroup.value.positionName} berhasil diperbarui!`)
    showEditIndicatorsModal.value = false
    await loadData()
  } catch (err) {
    console.error('Failed to save indicators:', err)
    toast.error(err.response?.data?.message || 'Gagal menyimpan perubahan indikator')
  } finally {
    savingIndicators.value = false
  }
}

onMounted(() => {
  loadData()
})
</script>

<style scoped>
.kpi-indicators-page {
  color: #1e293b;
}

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
.font-size-15 {
  font-size: 15px;
}

.position-group-card {
  border: 1px solid #e2e8f0 !important;
  background-color: #ffffff;
}

/* Rubric Reference Bar */
.rubric-reference-card {
  border-color: #e2e8f0;
}

.rubric-pill {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 10px;
  border-radius: 6px;
  font-size: 11px;
  font-weight: 500;
  border: 1px solid transparent;
}

.rubric-pill .pill-code {
  font-weight: 700;
  padding: 1px 5px;
  border-radius: 4px;
  font-size: 10px;
}

.rubric-pill.grade-a {
  background-color: #f0fdf4;
  border-color: #bbf7d0;
  color: #166534;
}
.rubric-pill.grade-a .pill-code {
  background-color: #16a34a;
  color: #ffffff;
}

.rubric-pill.grade-b {
  background-color: #f0f9ff;
  border-color: #bae6fd;
  color: #0369a1;
}
.rubric-pill.grade-b .pill-code {
  background-color: #0284c7;
  color: #ffffff;
}

.rubric-pill.grade-c {
  background-color: #fefce8;
  border-color: #fef08a;
  color: #854d0e;
}
.rubric-pill.grade-c .pill-code {
  background-color: #ca8a04;
  color: #ffffff;
}

.rubric-pill.grade-d {
  background-color: #fef2f2;
  border-color: #fecaca;
  color: #991b1b;
}
.rubric-pill.grade-d .pill-code {
  background-color: #dc2626;
  color: #ffffff;
}

.rubric-pill.grade-e {
  background-color: #f8fafc;
  border-color: #cbd5e1;
  color: #334155;
}
.rubric-pill.grade-e .pill-code {
  background-color: #475569;
  color: #ffffff;
}

.scoring-ladder {
  max-width: 520px;
}

.btn-xs {
  font-size: 11px;
  line-height: 1.3;
}
</style>
