<template>
  <div class="asset-repair-form-page pb-5">
    <!-- Top Bar Navigation & Actions -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 pt-1">
      <div class="d-flex align-items-center gap-3">
        <router-link
          to="/hrms/asset-repairs"
          class="btn btn-light bg-white border shadow-sm px-3 py-2 rounded-3 text-secondary d-flex align-items-center gap-2"
        >
          <span class="fw-medium">&larr; Kembali</span>
        </router-link>
        <div>
          <h4 class="mb-0 fw-bold text-dark">
            {{ isEdit ? 'Ubah Formulir Perbaikan Alat Kerja' : 'Permohonan Perbaikan Alat Kerja' }}
          </h4>
          <p class="text-muted small mb-0">
            Formulir resmi permohonan perbaikan alat kerja yang dibawa/digunakan karyawan (Hasil cetak PDF 1:1)
          </p>
        </div>
      </div>
      <div class="d-flex align-items-center gap-2">
        <router-link to="/hrms/asset-repairs" class="btn btn-outline-secondary px-4 py-2 rounded-3">
          Batal
        </router-link>
        <button
          type="button"
          class="btn btn-primary px-4 py-2 rounded-3 d-flex align-items-center gap-2 shadow-sm"
          :disabled="submitting || loadingData"
          @click="submitForm"
        >
          <span v-if="submitting" class="spinner-border spinner-border-sm" role="status"></span>
          <span>{{ submitting ? 'Menyimpan...' : (isEdit ? 'Simpan Perubahan' : 'Kirim Permohonan') }}</span>
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
      <p class="text-muted mb-0">Memuat formulir perbaikan alat...</p>
    </div>

    <!-- Form Content -->
    <form v-else @submit.prevent="submitForm">
      <!-- 1. IDENTITAS PEMOHON -->
      <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-0 pt-4 px-4 pb-2">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <h5 class="fw-bold text-dark mb-1">Identitas Pemohon</h5>
              <p class="text-muted small mb-0">Pilih karyawan pemohon perbaikan alat kerja.</p>
            </div>
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-3">
              Informasi Pemohon
            </span>
          </div>
        </div>
        <div class="card-body p-4 pt-2">
          <div class="row g-3">
            <!-- Perusahaan -->
            <div class="col-md-3">
              <label class="form-label fw-semibold text-secondary small">Perusahaan</label>
              <select
                v-model="form.company_id"
                @change="onCompanyChange"
                class="form-select rounded-3"
              >
                <option :value="null">-- Pilih Perusahaan --</option>
                <option v-for="c in companies" :key="c.id" :value="c.id">{{ c.name }}</option>
              </select>
            </div>

            <!-- Cabang -->
            <div class="col-md-3">
              <label class="form-label fw-semibold text-secondary small">Cabang</label>
              <select
                v-model="form.branch_id"
                @change="onBranchChange"
                class="form-select rounded-3"
              >
                <option :value="null">-- Pilih Cabang --</option>
                <option v-for="b in filteredBranches" :key="b.id" :value="b.id">{{ b.name }}</option>
              </select>
            </div>

            <!-- Departemen -->
            <div class="col-md-3">
              <label class="form-label fw-semibold text-secondary small">Departemen / Divisi</label>
              <select
                v-model="form.department_id"
                @change="onDepartmentChange"
                class="form-select rounded-3"
              >
                <option :value="null">-- Pilih Departemen --</option>
                <option v-for="d in filteredDepartments" :key="d.id" :value="d.id">{{ d.name }}</option>
              </select>
            </div>

            <!-- Karyawan Pemohon -->
            <div class="col-md-3">
              <label class="form-label fw-semibold text-secondary small">Nama Karyawan Pemohon</label>
              <select
                v-model="form.employee_id"
                @change="onEmployeeChange"
                class="form-select rounded-3"
              >
                <option :value="null">-- Pilih Karyawan --</option>
                <option v-for="e in filteredEmployees" :key="e.id" :value="e.id">
                  {{ e.name }} {{ e.employee_id ? '(' + e.employee_id + ')' : '' }}
                </option>
              </select>
            </div>

            <!-- Tanggal Pengajuan -->
            <div class="col-md-4">
              <label class="form-label fw-semibold text-secondary small">Tanggal Pengajuan <span class="text-danger">*</span></label>
              <input
                type="date"
                v-model="form.submission_date"
                class="form-control rounded-3"
                required
              />
            </div>

            <!-- Fast-Select: Aset yang Dipegang Karyawan -->
            <div class="col-md-8" v-if="employeeAssetsList.length > 0">
              <label class="form-label fw-semibold text-primary small">
                Pilih dari Aset yang Sedang Dipegang Karyawan (Auto-Fill)
              </label>
              <select
                v-model="selectedEmployeeAssetId"
                @change="onSelectEmployeeAsset"
                class="form-select border-primary rounded-3 bg-primary-subtle bg-opacity-10"
              >
                <option :value="null">-- Pilih Aset Karyawan untuk Mengisi Otomatis --</option>
                <option v-for="a in employeeAssetsList" :key="a.id" :value="a.id">
                  {{ a.name }} - {{ a.inventory_number || a.serial_number || 'Tanpa Kode' }} ({{ a.brand || '-' }})
                </option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <!-- 2. BAGIAN A: DETAIL ALAT KERJA -->
      <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-0 pt-4 px-4 pb-2">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <h5 class="fw-bold text-dark mb-1">A. Detail Alat Kerja</h5>
              <p class="text-muted small mb-0">Rincian alat kerja atau inventaris yang diajukan untuk perbaikan.</p>
            </div>
            <span class="badge bg-secondary-subtle text-secondary px-3 py-2 rounded-3">Bagian A</span>
          </div>
        </div>
        <div class="card-body p-4 pt-2">
          <div class="row g-3">
            <!-- Nama Alat -->
            <div class="col-md-6">
              <label class="form-label fw-semibold text-secondary small">Nama Alat <span class="text-danger">*</span></label>
              <input
                type="text"
                v-model="form.item_name"
                class="form-control rounded-3"
                placeholder="Contoh: Laptop Dell Latitude 5420 / Bor Tangan Listrik / Printer"
                required
              />
            </div>

            <!-- Merek / Tipe -->
            <div class="col-md-6">
              <label class="form-label fw-semibold text-secondary small">Merek / Tipe</label>
              <input
                type="text"
                v-model="form.brand_model"
                class="form-control rounded-3"
                placeholder="Contoh: Dell Latitude 5420 Core i7 / Makita HP1630"
              />
            </div>

            <!-- Kode Aset -->
            <div class="col-md-4">
              <label class="form-label fw-semibold text-secondary small">Kode Aset (jika ada)</label>
              <input
                type="text"
                v-model="form.asset_code"
                class="form-control rounded-3"
                placeholder="Contoh: AST-JKT-2024-0012"
              />
            </div>

            <!-- Jumlah -->
            <div class="col-md-3">
              <label class="form-label fw-semibold text-secondary small">Jumlah Unit</label>
              <div class="input-group">
                <input
                  type="number"
                  v-model="form.quantity"
                  min="1"
                  class="form-control rounded-start-3"
                />
                <span class="input-group-text rounded-end-3 bg-light text-muted">unit</span>
              </div>
            </div>

            <!-- Lokasi Alat -->
            <div class="col-md-5">
              <label class="form-label fw-semibold text-secondary small">Lokasi Alat</label>
              <input
                type="text"
                v-model="form.location"
                class="form-control rounded-3"
                placeholder="Contoh: Kantor Cabang Bandung / Ruang Operasional / Dibawa Karyawan"
              />
            </div>

            <!-- Kondisi Alat -->
            <div class="col-12 mt-3">
              <label class="form-label fw-semibold text-secondary small d-block">Kondisi Alat Saat Ini <span class="text-danger">*</span></label>
              <div class="d-flex align-items-center flex-wrap gap-4 pt-1">
                <div class="form-check">
                  <input
                    class="form-check-input"
                    type="radio"
                    name="conditionRadio"
                    id="condRusak"
                    value="rusak"
                    v-model="form.condition"
                  />
                  <label class="form-check-label fw-medium cursor-pointer" for="condRusak">
                    Rusak Fisik / Komponen
                  </label>
                </div>
                <div class="form-check">
                  <input
                    class="form-check-input"
                    type="radio"
                    name="conditionRadio"
                    id="condTidakBerfungsi"
                    value="tidak_berfungsi"
                    v-model="form.condition"
                  />
                  <label class="form-check-label fw-medium cursor-pointer" for="condTidakBerfungsi">
                    Tidak Berfungsi / Mati Total
                  </label>
                </div>
                <div class="form-check">
                  <input
                    class="form-check-input"
                    type="radio"
                    name="conditionRadio"
                    id="condLainnya"
                    value="lainnya"
                    v-model="form.condition"
                  />
                  <label class="form-check-label fw-medium cursor-pointer" for="condLainnya">
                    Lainnya
                  </label>
                </div>

                <div v-if="form.condition === 'lainnya'" class="flex-grow-1" style="min-width: 200px;">
                  <input
                    type="text"
                    v-model="form.condition_other"
                    class="form-control form-control-sm rounded-3"
                    placeholder="Sebutkan kondisi lainnya..."
                  />
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- 3. BAGIAN B: URAIAN KERUSAKAN DAN PERMOHONAN -->
      <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-0 pt-4 px-4 pb-2">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <h5 class="fw-bold text-dark mb-1">B. Uraian Kerusakan dan Permohonan</h5>
              <p class="text-muted small mb-0">Jelaskan kendala, perbaikan yang dibutuhkan, dan dampak ke operasional.</p>
            </div>
            <span class="badge bg-secondary-subtle text-secondary px-3 py-2 rounded-3">Bagian B</span>
          </div>
        </div>
        <div class="card-body p-4 pt-2">
          <div class="row g-3">
            <!-- Uraian Kerusakan / Kendala -->
            <div class="col-12">
              <label class="form-label fw-semibold text-secondary small">
                Uraian Kerusakan / Kendala <span class="text-danger">*</span>
              </label>
              <textarea
                v-model="form.damage_description"
                rows="3"
                class="form-control rounded-3"
                placeholder="Tuliskan secara lengkap detail kerusakan alat, gejala, atau masalah yang terjadi..."
                required
              ></textarea>
            </div>

            <!-- Perbaikan yang Dibutuhkan / Keterangan Tambahan -->
            <div class="col-12">
              <label class="form-label fw-semibold text-secondary small">
                Perbaikan yang Dibutuhkan / Keterangan Tambahan
              </label>
              <textarea
                v-model="form.repair_description"
                rows="3"
                class="form-control rounded-3"
                placeholder="Tuliskan komponen yang perlu diganti, servis yang diajukan, atau rekomendasi teknis..."
              ></textarea>
            </div>

            <!-- PIC / Pengguna Alat -->
            <div class="col-md-6">
              <label class="form-label fw-semibold text-secondary small">PIC / Pengguna Alat</label>
              <input
                type="text"
                v-model="form.pic_name"
                class="form-control rounded-3"
                placeholder="Nama PIC atau pemegang alat saat ini"
              />
            </div>

            <!-- Operasional Terganggu -->
            <div class="col-md-6">
              <label class="form-label fw-semibold text-secondary small d-block">Dampak Terhadap Operasional Kerja</label>
              <div class="d-flex align-items-center gap-4 pt-2">
                <div class="form-check">
                  <input
                    class="form-check-input"
                    type="radio"
                    name="disruptedRadio"
                    id="disruptedTrue"
                    :value="true"
                    v-model="form.operational_disrupted"
                  />
                  <label class="form-check-label fw-semibold text-danger cursor-pointer" for="disruptedTrue">
                    Operasional Terganggu
                  </label>
                </div>
                <div class="form-check">
                  <input
                    class="form-check-input"
                    type="radio"
                    name="disruptedRadio"
                    id="disruptedFalse"
                    :value="false"
                    v-model="form.operational_disrupted"
                  />
                  <label class="form-check-label fw-medium text-secondary cursor-pointer" for="disruptedFalse">
                    Tidak Terganggu
                  </label>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- 4. BAGIAN C: ESTIMASI DAN TINDAK LANJUT -->
      <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-0 pt-4 px-4 pb-2">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <h5 class="fw-bold text-dark mb-1">C. Estimasi dan Tindak Lanjut</h5>
              <p class="text-muted small mb-0">Estimasi biaya servis, prioritas pengerjaan, dan rekening pembayaran.</p>
            </div>
            <span class="badge bg-secondary-subtle text-secondary px-3 py-2 rounded-3">Bagian C</span>
          </div>
        </div>
        <div class="card-body p-4 pt-2">
          <div class="row g-3">
            <!-- Estimasi Biaya -->
            <div class="col-md-4">
              <label class="form-label fw-semibold text-secondary small">Estimasi Biaya (jika ada)</label>
              <div class="input-group">
                <span class="input-group-text rounded-start-3 bg-light text-muted">Rp</span>
                <input
                  type="number"
                  v-model="form.estimated_cost"
                  min="0"
                  class="form-control rounded-end-3"
                  placeholder="0"
                />
              </div>
            </div>

            <!-- Prioritas -->
            <div class="col-md-8">
              <label class="form-label fw-semibold text-secondary small d-block">Tingkat Prioritas</label>
              <div class="d-flex align-items-center gap-4 pt-2">
                <div class="form-check">
                  <input
                    class="form-check-input"
                    type="radio"
                    name="priorityRadio"
                    id="prioTinggi"
                    value="tinggi"
                    v-model="form.priority"
                  />
                  <label class="form-check-label fw-semibold text-danger cursor-pointer" for="prioTinggi">
                    Tinggi (Urgent)
                  </label>
                </div>
                <div class="form-check">
                  <input
                    class="form-check-input"
                    type="radio"
                    name="priorityRadio"
                    id="prioSedang"
                    value="sedang"
                    v-model="form.priority"
                  />
                  <label class="form-check-label fw-semibold text-warning-emphasis cursor-pointer" for="prioSedang">
                    Sedang (Normal)
                  </label>
                </div>
                <div class="form-check">
                  <input
                    class="form-check-input"
                    type="radio"
                    name="priorityRadio"
                    id="prioRendah"
                    value="rendah"
                    v-model="form.priority"
                  />
                  <label class="form-check-label fw-medium text-secondary cursor-pointer" for="prioRendah">
                    Rendah
                  </label>
                </div>
              </div>
            </div>

            <!-- Nama Bank & No Rekening -->
            <div class="col-md-6">
              <label class="form-label fw-semibold text-secondary small">Nama Bank & No Rekening (untuk reimburse / transfer)</label>
              <input
                type="text"
                v-model="form.bank_account_info"
                class="form-control rounded-3"
                placeholder="Contoh: BCA 1234567890 a.n. John Doe / Vendor Service Center"
              />
            </div>

            <!-- Tanggal Perbaikan -->
            <div class="col-md-6">
              <label class="form-label fw-semibold text-secondary small">Estimasi Tanggal Perbaikan</label>
              <input
                type="date"
                v-model="form.repair_date"
                class="form-control rounded-3"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- Action Bottom Bar -->
      <div class="d-flex justify-content-end align-items-center gap-2 pt-2">
        <router-link to="/hrms/asset-repairs" class="btn btn-light px-4 py-2 rounded-3 border">
          Batal
        </router-link>
        <button
          type="submit"
          class="btn btn-primary px-5 py-2 rounded-3 shadow-sm"
          :disabled="submitting"
        >
          <span v-if="submitting" class="spinner-border spinner-border-sm me-1" role="status"></span>
          <span>{{ submitting ? 'Menyimpan...' : (isEdit ? 'Simpan Perubahan' : 'Kirim Permohonan Perbaikan') }}</span>
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../api'
import Swal from 'sweetalert2'

const route = useRoute()
const router = useRouter()

const repairId = computed(() => route.params.id)
const isEdit = computed(() => !!repairId.value)

const loadingData = ref(false)
const submitting = ref(false)
const errorMessage = ref('')

const companies = ref([])
const branches = ref([])
const departments = ref([])
const employees = ref([])
const employeeAssetsList = ref([])
const selectedEmployeeAssetId = ref(null)

const form = ref({
  id: null,
  company_id: null,
  branch_id: null,
  department_id: null,
  employee_id: null,
  asset_id: null,
  submission_date: new Date().toISOString().split('T')[0],
  item_name: '',
  brand_model: '',
  asset_code: '',
  quantity: 1,
  location: '',
  condition: 'rusak',
  condition_other: '',
  damage_description: '',
  repair_description: '',
  pic_name: '',
  operational_disrupted: false,
  estimated_cost: null,
  priority: 'sedang',
  bank_account_info: '',
  repair_date: '',
})

// Helper untuk mengurai response data array dari API Laravel
const extractArray = (res) => {
  if (!res) return []
  const d = res.data !== undefined ? res.data : res
  if (Array.isArray(d)) return d
  if (d && Array.isArray(d.data)) return d.data
  if (d && d.data && Array.isArray(d.data.data)) return d.data.data
  return []
}

// Cascading: Cabang
const filteredBranches = computed(() => {
  if (!form.value.company_id) return branches.value
  return branches.value.filter(b => b.company_id == form.value.company_id)
})

// Cascading: Departemen
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

// Cascading: Karyawan
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
}

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
}

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
}

// Saat Karyawan Dipilih
const onEmployeeChange = async () => {
  if (!form.value.employee_id) {
    employeeAssetsList.value = []
    selectedEmployeeAssetId.value = null
    return
  }
  const emp = employees.value.find(e => e.id == form.value.employee_id)
  if (emp) {
    form.value.pic_name = emp.name
    const assignment = emp.primary_assignment || (emp.assignments && emp.assignments.find(a => a.is_primary)) || (emp.assignments && emp.assignments[0])
    if (assignment) {
      if (assignment.company_id) form.value.company_id = assignment.company_id
      if (assignment.branch_id) form.value.branch_id = assignment.branch_id
      if (assignment.department_id) form.value.department_id = assignment.department_id
    }

    // Ambil daftar aset yang dipegang karyawan
    fetchEmployeeAssets(emp.id)
  }
}

const fetchEmployeeAssets = async (employeeId) => {
  try {
    const res = await api.get(`/employees/${employeeId}/assets`)
    employeeAssetsList.value = extractArray(res)
  } catch (err) {
    // Fallback via /assets?employee_id=...
    try {
      const resFallback = await api.get('/assets', { params: { employee_id: employeeId } })
      employeeAssetsList.value = extractArray(resFallback)
    } catch (e) {
      employeeAssetsList.value = []
    }
  }
}

const onSelectEmployeeAsset = () => {
  if (!selectedEmployeeAssetId.value) return
  const asset = employeeAssetsList.value.find(a => a.id == selectedEmployeeAssetId.value)
  if (asset) {
    form.value.asset_id = asset.id
    form.value.item_name = asset.name || form.value.item_name
    form.value.brand_model = [asset.brand, asset.model].filter(Boolean).join(' - ') || form.value.brand_model
    form.value.asset_code = asset.inventory_number || asset.serial_number || form.value.asset_code
    form.value.location = asset.location || form.value.location
  }
}

const loadDropdowns = async () => {
  try {
    const [compRes, branchRes, deptRes, empRes] = await Promise.all([
      api.get('/companies', { params: { per_page: 200 } }),
      api.get('/branches', { params: { per_page: 200 } }),
      api.get('/departments', { params: { per_page: 200 } }),
      api.get('/employees', { params: { per_page: 500 } }),
    ])
    companies.value = extractArray(compRes)
    branches.value = extractArray(branchRes)
    departments.value = extractArray(deptRes)
    employees.value = extractArray(empRes)
  } catch (err) {
    console.error('Gagal memuat opsi dropdown:', err)
  }
}

const loadDetailData = async () => {
  if (!isEdit.value) return
  loadingData.value = true
  try {
    const { data } = await api.get(`/asset-repairs/${repairId.value}`)
    const item = data.data || data
    form.value = {
      id: item.id,
      company_id: item.company_id,
      branch_id: item.branch_id,
      department_id: item.department_id,
      employee_id: item.employee_id,
      asset_id: item.asset_id,
      submission_date: item.submission_date ? item.submission_date.split('T')[0] : '',
      item_name: item.item_name || '',
      brand_model: item.brand_model || '',
      asset_code: item.asset_code || '',
      quantity: item.quantity || 1,
      location: item.location || '',
      condition: item.condition || 'rusak',
      condition_other: item.condition_other || '',
      damage_description: item.damage_description || '',
      repair_description: item.repair_description || '',
      pic_name: item.pic_name || '',
      operational_disrupted: !!item.operational_disrupted,
      estimated_cost: item.estimated_cost,
      priority: item.priority || 'sedang',
      bank_account_info: item.bank_account_info || '',
      repair_date: item.repair_date ? item.repair_date.split('T')[0] : '',
    }

    if (item.employee_id) {
      fetchEmployeeAssets(item.employee_id)
    }
  } catch (err) {
    errorMessage.value = 'Gagal memuat detail data perbaikan aset.'
  } finally {
    loadingData.value = false
  }
}

const submitForm = async () => {
  errorMessage.value = ''
  if (!form.value.item_name) {
    errorMessage.value = 'Nama alat kerja wajib diisi.'
    return
  }
  if (!form.value.damage_description) {
    errorMessage.value = 'Uraian kerusakan / kendala wajib diisi.'
    return
  }

  submitting.value = true
  try {
    let savedId = form.value.id
    if (isEdit.value) {
      await api.put(`/asset-repairs/${form.value.id}`, form.value)
    } else {
      const res = await api.post('/asset-repairs', form.value)
      savedId = res.data?.data?.id
    }

    const result = await Swal.fire({
      icon: 'success',
      title: 'Permohonan Berhasil Disimpan',
      text: 'Formulir permohonan perbaikan alat kerja berhasil disimpan.',
      showCancelButton: true,
      confirmButtonText: 'Cetak Form PDF Sekarang',
      cancelButtonText: 'Kembali ke Daftar',
      confirmButtonColor: '#008779',
    })

    if (result.isConfirmed && savedId) {
      const baseUrl = api.defaults.baseURL || '/api/v1'
      const token = localStorage.getItem('token') || localStorage.getItem('auth_token')
      const url = `${baseUrl}/asset-repairs/${savedId}/pdf${token ? '?token=' + encodeURIComponent(token) : ''}`
      window.open(url, '_blank')
    }

    router.push('/hrms/asset-repairs')
  } catch (err) {
    const errorData = err.response?.data
    if (errorData?.errors) {
      const messages = Object.values(errorData.errors).flat()
      errorMessage.value = messages.join(', ')
    } else {
      errorMessage.value = errorData?.message || 'Gagal menyimpan permohonan perbaikan alat.'
    }
  } finally {
    submitting.value = false
  }
}

onMounted(async () => {
  await loadDropdowns()
  if (isEdit.value) {
    await loadDetailData()
  }
})
</script>

<style scoped>
.cursor-pointer {
  cursor: pointer;
}
</style>
