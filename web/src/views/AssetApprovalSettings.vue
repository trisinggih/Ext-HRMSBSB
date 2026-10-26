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
        <h4 class="mb-0 font-size-16 fw-semibold text-white text-nowrap" style="letter-spacing: 0.01em;">
          Konfigurasi Approval Manajemen Aset
        </h4>
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
              <router-link to="/hrms/asset-requests" class="dropdown-item py-2 px-3">
                <i class="mdi mdi-file-document-edit-outline me-2 text-primary"></i> Pengadaan & Inventaris
              </router-link>
            </li>
            <li>
              <router-link to="/hrms/asset-repairs" class="dropdown-item py-2 px-3">
                <i class="mdi mdi-tools me-2 text-warning"></i> Perbaikan Alat
              </router-link>
            </li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Main Card -->
    <div class="card">
      <div class="card-body">
        <!-- 3 Approval Categories Switcher -->
        <div class="d-flex gap-2 border-bottom pb-3 mb-4 align-items-center flex-wrap">
          <button
            type="button"
            class="btn btn-sm px-3 py-2 fw-semibold"
            :class="activeCategory === 'pengajuan_perlengkapan' ? 'btn-primary shadow-sm' : 'btn-light text-muted'"
            style="border-radius: 8px;"
            @click="switchCategory('pengajuan_perlengkapan')"
          >
            1. Pengajuan Perlengkapan Kantor
          </button>
          <button
            type="button"
            class="btn btn-sm px-3 py-2 fw-semibold"
            :class="activeCategory === 'permohonan_alat' ? 'btn-primary shadow-sm' : 'btn-light text-muted'"
            style="border-radius: 8px;"
            @click="switchCategory('permohonan_alat')"
          >
            2. Permohonan Peralatan Kerja
          </button>
          <button
            type="button"
            class="btn btn-sm px-3 py-2 fw-semibold"
            :class="activeCategory === 'perbaikan_alat' ? 'btn-primary shadow-sm' : 'btn-light text-muted'"
            style="border-radius: 8px;"
            @click="switchCategory('perbaikan_alat')"
          >
            3. Permohonan Perbaikan Alat Kerja
          </button>
        </div>

        <!-- Category Description Banner -->
        <div
          class="p-3 rounded-3 mb-4 border d-flex justify-content-between align-items-center flex-wrap gap-2"
          :class="categoryInfo.bannerClass"
        >
          <div>
            <div class="fw-bold text-dark fs-6">{{ categoryInfo.title }}</div>
            <div class="small text-muted">{{ categoryInfo.description }}</div>
          </div>
          <span class="badge px-3 py-2 fs-7 bg-primary-subtle text-primary border border-primary-subtle">
            {{ categoryInfo.badgeText }}
          </span>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="text-center py-5">
          <div class="spinner-border text-primary" role="status"></div>
          <p class="text-muted mt-2 mb-0">Memuat konfigurasi approval...</p>
        </div>

        <!-- Form Setting Tunggal (Single Form per Tab) -->
        <form v-else @submit.prevent="saveCurrentSetting">
          <div class="card border border-light-subtle shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
              <div>
                <h5 class="mb-0 fw-bold text-dark fs-6">
                  Alur & Tahapan Penandatangan Approval
                </h5>
                <p class="text-muted small mb-0">
                  Tentukan karyawan yang bertanda tangan. Sistem akan otomatis mengisi jabatan pada kolom tanda tangan dokumen cetak / PDF.
                </p>
              </div>
              <button
                type="button"
                class="btn btn-sm btn-outline-primary fw-semibold px-3"
                style="border-radius: 8px;"
                @click="addStage"
              >
                <i class="mdi mdi-plus me-1"></i> Tambah Tahap Penandatangan
              </button>
            </div>

            <div class="card-body p-4">
              <!-- Empty stages state -->
              <div v-if="formData.stages.length === 0" class="text-center py-4 text-muted">
                <i class="mdi mdi-account-multiple-plus fs-1 text-secondary opacity-50"></i>
                <div class="mt-2">Belum ada tahapan penandatangan yang ditentukan.</div>
                <button type="button" class="btn btn-sm btn-primary mt-3" @click="addStage">
                  + Tambah Tahap Pertama
                </button>
              </div>

              <!-- List of Stages -->
              <div v-else class="d-flex flex-column gap-3">
                <div
                  v-for="(st, idx) in formData.stages"
                  :key="idx"
                  class="p-3 bg-light rounded-3 border position-relative"
                >
                  <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                    <div class="d-flex align-items-center gap-2">
                      <span class="badge bg-primary text-white px-2 py-1 font-size-12 rounded-2">
                        Tahap {{ idx + 1 }}
                      </span>
                    </div>

                    <div class="d-flex align-items-center gap-1">
                      <button
                        type="button"
                        class="btn btn-sm btn-light border py-0 px-2"
                        title="Pindah ke atas"
                        :disabled="idx === 0"
                        @click="moveStage(idx, -1)"
                      >
                        ▲
                      </button>
                      <button
                        type="button"
                        class="btn btn-sm btn-light border py-0 px-2"
                        title="Pindah ke bawah"
                        :disabled="idx === formData.stages.length - 1"
                        @click="moveStage(idx, 1)"
                      >
                        ▼
                      </button>
                      <button
                        type="button"
                        class="btn btn-sm btn-outline-danger py-0 px-2 ms-2"
                        title="Hapus tahap ini"
                        v-if="formData.stages.length > 1"
                        @click="removeStage(idx)"
                      >
                        <i class="mdi mdi-trash-can-outline me-1"></i>Hapus
                      </button>
                    </div>
                  </div>

                  <div class="row g-3">
                    <!-- Pilih Karyawan Penandatangan -->
                    <div class="col-md-7">
                      <label class="form-label small fw-semibold text-secondary mb-1">
                        Pilih Karyawan Penandatangan: <span class="text-danger" v-if="idx > 0">*</span>
                      </label>
                      <select
                        v-model="st.employee_id"
                        @change="onStageEmployeeChange(st, idx)"
                        class="form-select form-select-sm rounded-3 bg-white"
                      >
                        <template v-if="idx === 0">
                          <option :value="null">⭐ Atasan Langsung Karyawan Pemohon (Default)</option>
                          <option disabled>──────────────────────────</option>
                        </template>
                        <template v-else>
                          <option :value="null">-- Pilih Karyawan --</option>
                        </template>
                        <option v-for="e in employees" :key="e.id" :value="e.id">
                          {{ e.name }} {{ getEmployeePositionName(e) ? '— (' + getEmployeePositionName(e) + ')' : '' }}
                        </option>
                      </select>
                      <div v-if="idx === 0 && !st.employee_id" class="small text-muted mt-1">
                        <i class="mdi mdi-information-outline text-primary me-1"></i>
                        Otomatis menggunakan atasan langsung (Supervisor / Manager) dari karyawan yang membuat pengajuan.
                      </div>
                    </div>

                    <!-- Jabatan pada Kolom Tanda Tangan PDF -->
                    <div class="col-md-5">
                      <label class="form-label small fw-semibold text-secondary mb-1">
                        Jabatan pada Kolom Tanda Tangan PDF: <span class="text-danger">*</span>
                      </label>
                      <input
                        type="text"
                        v-model="st.role_title"
                        class="form-control form-control-sm rounded-3 bg-white"
                        placeholder="Contoh: Atasan Langsung / Manager Terkait / Direktur"
                        required
                      />
                    </div>
                  </div>
                </div>
              </div>

              <!-- Catatan Tambahan (Opsional) -->
              <div class="mt-4 pt-3 border-top">
                <label class="form-label small fw-semibold text-secondary mb-1">
                  Catatan / Keterangan Pengaturan (Opsional)
                </label>
                <input
                  type="text"
                  v-model="formData.notes"
                  class="form-control form-control-sm rounded-3"
                  placeholder="Catatan internal alur approval..."
                />
              </div>
            </div>

            <!-- Card Footer: Save Button -->
            <div class="card-footer bg-light px-4 py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
              <div class="small">
                <span v-if="saveSuccess" class="text-success fw-bold">
                  <i class="mdi mdi-check-circle me-1"></i> Pengaturan approval berhasil disimpan!
                </span>
                <span v-else class="text-muted">
                  Perubahan akan langsung diterapkan ke formulir pengajuan aset berikutnya.
                </span>
              </div>
              <button
                type="submit"
                class="btn btn-primary px-4 py-2 waves-effect waves-light fw-semibold"
                style="border-radius: 8px;"
                :disabled="saving"
              >
                <span v-if="saving" class="spinner-border spinner-border-sm me-1" role="status"></span>
                <i v-else class="mdi mdi-content-save-outline me-1"></i>
                Simpan Pengaturan Approval
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../api'

const activeCategory = ref('pengajuan_perlengkapan')
const employees = ref([])
const loading = ref(false)
const saving = ref(false)
const saveSuccess = ref(false)

const formData = ref({
  id: null,
  name: '',
  approval_category: 'pengajuan_perlengkapan',
  notes: '',
  stages: []
})

const categoryMeta = {
  pengajuan_perlengkapan: {
    title: '1. Formulir Pengajuan Perlengkapan Kantor (Rutin & ATK)',
    description: 'Menentukan karyawan peninjau & penandatanganan untuk kebutuhan ATK rutin, kebersihan, sarana kantor, atau peralatan karyawan baru.',
    badgeText: 'Dokumen: Form Perlengkapan Kantor',
    bannerClass: 'bg-info-subtle border-info-subtle',
    defaultStages: [
      { employee_id: null, role_title: 'Atasan Langsung' },
      { employee_id: null, role_title: 'Direktur' }
    ]
  },
  permohonan_alat: {
    title: '2. Formulir Permohonan Peralatan Kerja (Pengadaan Baru & Pergantian)',
    description: 'Menentukan karyawan peninjau & penandatanganan untuk pembelian alat kerja baru atau pergantian alat kerja karyawan yang rusak/usang.',
    badgeText: 'Dokumen: Form Peralatan Kerja',
    bannerClass: 'bg-success-subtle border-success-subtle',
    defaultStages: [
      { employee_id: null, role_title: 'Atasan Langsung' },
      { employee_id: null, role_title: 'Direktur' }
    ]
  },
  perbaikan_alat: {
    title: '3. Formulir Permohonan Perbaikan Alat Kerja (Alat Dibawa Karyawan)',
    description: 'Menentukan karyawan peninjau & penandatanganan untuk perbaikan alat kerja milik perusahaan yang dibawa atau digunakan langsung oleh karyawan.',
    badgeText: 'Dokumen: Form Perbaikan Alat',
    bannerClass: 'bg-warning-subtle border-warning-subtle',
    defaultStages: [
      { employee_id: null, role_title: 'Atasan Langsung' },
      { employee_id: null, role_title: 'Manager Finance & Accounting' },
      { employee_id: null, role_title: 'Direktur' }
    ]
  }
}

const categoryInfo = computed(() => categoryMeta[activeCategory.value] || categoryMeta.pengajuan_perlengkapan)

const getEmployeePositionName = (e) => {
  if (!e) return ''
  if (e.assignments && e.assignments.length) {
    const primary = e.assignments.find(a => a.is_primary) || e.assignments[0]
    return primary?.position?.name || ''
  }
  return e.position?.name || ''
}

const onStageEmployeeChange = (st, idx) => {
  if (idx === 0 && !st.employee_id) {
    st.role_title = 'Atasan Langsung'
    return
  }
  if (!st.employee_id) return
  const emp = employees.value.find(e => e.id === st.employee_id)
  if (emp) {
    const pos = getEmployeePositionName(emp)
    if (pos) {
      st.role_title = pos
    }
  }
}

const addStage = () => {
  const currentCount = formData.value.stages.length
  let defaultRole = ''
  if (currentCount === 0) {
    defaultRole = 'Atasan Langsung'
  } else if (currentCount === 1) {
    defaultRole = 'Direktur'
  }
  formData.value.stages.push({
    level: currentCount + 1,
    stage_name: 'Tahap ' + (currentCount + 1),
    employee_id: null,
    role_title: defaultRole,
    notes: ''
  })
}

const removeStage = (idx) => {
  formData.value.stages.splice(idx, 1)
}

const moveStage = (idx, direction) => {
  const newIdx = idx + direction
  if (newIdx < 0 || newIdx >= formData.value.stages.length) return
  const temp = formData.value.stages[idx]
  formData.value.stages[idx] = formData.value.stages[newIdx]
  formData.value.stages[newIdx] = temp
}

const loadEmployees = async () => {
  try {
    const res = await api.get('/employees', { params: { per_page: 500, is_active: true } })
    const raw = res.data?.data
    const list = Array.isArray(raw) ? raw : (raw?.data || res.data || [])
    employees.value = Array.isArray(list) ? list : []
  } catch (e) {
    console.error('Gagal memuat daftar karyawan:', e)
  }
}

const loadCategorySetting = async (category) => {
  loading.value = true
  saveSuccess.value = false
  try {
    const res = await api.get(`/asset-approval-settings/by-category/${category}`)
    const setting = res.data?.data
    if (setting && setting.stages && setting.stages.length > 0) {
      formData.value = {
        id: setting.id,
        name: setting.name || '',
        approval_category: category,
        notes: setting.notes || '',
        stages: setting.stages.map((st, index) => ({
          level: st.level || (index + 1),
          stage_name: 'Tahap ' + (index + 1),
          employee_id: st.employee_id,
          role_title: st.role_title || (index === 0 && !st.employee_id ? 'Atasan Langsung' : ''),
          notes: st.notes || ''
        }))
      }
    } else if (setting && setting.mengetahui_employee_id) {
      // Fallback from legacy columns
      const stages = [
        {
          level: 1,
          stage_name: 'Tahap 1',
          employee_id: setting.mengetahui_employee_id,
          role_title: setting.mengetahui_title || (setting.mengetahui_employee_id ? 'Manager Terkait' : 'Atasan Langsung'),
          notes: ''
        }
      ]
      if (setting.mengetahui_2_employee_id) {
        stages.push({
          level: 2,
          stage_name: 'Tahap 2',
          employee_id: setting.mengetahui_2_employee_id,
          role_title: setting.mengetahui_2_title || 'Manager Finance & Accounting',
          notes: ''
        })
      }
      if (setting.disetujui_employee_id) {
        stages.push({
          level: stages.length + 1,
          stage_name: 'Tahap ' + (stages.length + 1),
          employee_id: setting.disetujui_employee_id,
          role_title: setting.disetujui_title || 'Direktur',
          notes: ''
        })
      }
      formData.value = {
        id: setting.id,
        name: setting.name || '',
        approval_category: category,
        notes: setting.notes || '',
        stages
      }
    } else {
      // Default template for newly created
      const defaults = categoryMeta[category]?.defaultStages || [
        { employee_id: null, role_title: 'Atasan Langsung' },
        { employee_id: null, role_title: 'Direktur' }
      ]
      formData.value = {
        id: null,
        name: categoryMeta[category]?.title || 'Approval Aset',
        approval_category: category,
        notes: '',
        stages: defaults.map((d, i) => ({
          level: i + 1,
          stage_name: 'Tahap ' + (i + 1),
          employee_id: d.employee_id,
          role_title: d.role_title,
          notes: ''
        }))
      }
    }
  } catch (e) {
    console.error('Gagal memuat setting category:', e)
    const defaults = categoryMeta[category]?.defaultStages || [
      { employee_id: null, role_title: 'Atasan Langsung' },
      { employee_id: null, role_title: 'Direktur' }
    ]
    formData.value = {
      id: null,
      name: categoryMeta[category]?.title || 'Approval Aset',
      approval_category: category,
      notes: '',
      stages: defaults.map((d, i) => ({
        level: i + 1,
        stage_name: 'Tahap ' + (i + 1),
        employee_id: d.employee_id,
        role_title: d.role_title,
        notes: ''
      }))
    }
  } finally {
    loading.value = false
  }
}

const switchCategory = (category) => {
  activeCategory.value = category
  loadCategorySetting(category)
}

const saveCurrentSetting = async () => {
  if (formData.value.stages.length === 0) {
    alert('Harap tentukan minimal 1 tahap penandatangan approval.')
    return
  }

  // Tahap 1 boleh null (berarti Atasan Langsung). Tahap 2 dan seterusnya wajib pilih karyawan tertentu.
  for (let i = 0; i < formData.value.stages.length; i++) {
    const st = formData.value.stages[i]
    if (i > 0 && !st.employee_id) {
      alert(`Harap pilih karyawan penandatangan untuk Tahap ${i + 1}.`)
      return
    }
    if (!st.role_title || !st.role_title.trim()) {
      alert(`Harap isi jabatan pada kolom tanda tangan untuk Tahap ${i + 1}.`)
      return
    }
  }

  saving.value = true
  saveSuccess.value = false
  try {
    const payload = {
      name: formData.value.name || categoryInfo.value.title,
      notes: formData.value.notes,
      stages: formData.value.stages.map((st, i) => ({
        level: i + 1,
        stage_name: 'Tahap ' + (i + 1),
        employee_id: st.employee_id,
        role_title: (i === 0 && !st.employee_id && !st.role_title) ? 'Atasan Langsung' : (st.role_title || ''),
        notes: st.notes || ''
      }))
    }

    const res = await api.post(`/asset-approval-settings/by-category/${activeCategory.value}`, payload)
    saveSuccess.value = true
    if (res.data?.data?.id) {
      formData.value.id = res.data.data.id
    }
    setTimeout(() => {
      saveSuccess.value = false
    }, 4000)
  } catch (e) {
    alert(e.response?.data?.message || 'Gagal menyimpan pengaturan approval')
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  await loadEmployees()
  await loadCategorySetting(activeCategory.value)
})
</script>
