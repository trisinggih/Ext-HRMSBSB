<template>
  <CrudPage
    title="Shift"
    singular="Shift"
    endpoint="/shifts"
    :columns="[
      { key: 'name', label: 'Nama' },
      { key: 'company_name', label: 'Perusahaan' },
      { key: 'code', label: 'Kode' },
      { key: 'clock_in', label: 'Masuk' },
      { key: 'clock_out', label: 'Pulang' },
      { key: 'late_tolerance_minutes', label: 'Toleransi (mnt)' },
      { key: 'is_active', label: 'Aktif' },
    ]"
    :form-fields="[
      { key: 'name', label: 'Nama', required: true },
      { key: 'company_id', label: 'Perusahaan', type: 'relation', relationEndpoint: '/companies' },
      { key: 'code', label: 'Kode', required: true },
      { key: 'clock_in', label: 'Jam Masuk', type: 'time' },
      { key: 'clock_out', label: 'Jam Pulang', type: 'time' },
      { key: 'break_start', label: 'Istirahat Mulai', type: 'time' },
      { key: 'break_end', label: 'Istirahat Selesai', type: 'time' },
      { key: 'late_tolerance_minutes', label: 'Toleransi Keterlambatan (mnt)', type: 'number' },
      { key: 'max_overtime_request_time', label: 'Waktu Maks. Pengajuan Lembur', type: 'time' },
      { key: 'working_hours', label: 'Jam Kerja', type: 'number' },
      { key: 'cross_midnight', label: 'Lintas Hari', type: 'select', options: [{ value: true, label: 'Ya' }, { value: false, label: 'Tidak' }] },
      { key: 'is_active', label: 'Aktif', type: 'select', options: [{ value: true, label: 'Ya' }, { value: false, label: 'Tidak' }] },
    ]"
    :filters="[
      { key: 'company_id', label: 'Filter Perusahaan', relationEndpoint: '/companies' }
    ]"
    :initial-form="() => ({ is_active: true, cross_midnight: false, late_tolerance_minutes: 0 })"
    @formChange="onFormChange"
  >
    <template #header-actions>
      <button class="btn btn-warning" @click="showBulkModal = true">
        <i class="mdi mdi-calendar-multiselect"></i> Generate Shift Massal
      </button>
    </template>
    
    <template #cell-is_active="{ value }">
      <span class="badge" :class="value ? 'bg-success' : 'bg-danger'">
        <i class="mdi" :class="value ? 'mdi-check-circle' : 'mdi-close-circle'"></i> {{ value ? 'Ya' : 'Tidak' }}
      </span>
    </template>
  </CrudPage>

  <!-- Modal Generate Massal -->
  <div class="modal fade show" tabindex="-1" v-if="showBulkModal" style="display: block; background: rgba(0,0,0,0.5)">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Generate Shift Semua Karyawan</h5>
          <button type="button" class="btn-close" @click="showBulkModal = false"></button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Tahun</label>
              <input type="number" class="form-control" v-model="bulkForm.year">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Bulan</label>
              <select class="form-select" v-model="bulkForm.month">
                <option v-for="m in 12" :key="m" :value="m">{{ m }}</option>
              </select>
            </div>
          </div>
          <hr>
          <h6>Pola Shift Default</h6>
          <div v-for="(dayName, index) in ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu']" :key="index" class="mb-2 row align-items-center">
            <label class="col-sm-3 col-form-label">{{ dayName }}</label>
            <div class="col-sm-9">
              <select class="form-select" v-model="bulkForm.patterns[index]">
                <option :value="null">Libur / Off</option>
                <option v-for="s in shiftOptions" :key="s.id" :value="s.id">{{ s.name }} ({{ s.clock_in }} - {{ s.clock_out }})</option>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" @click="showBulkModal = false">Batal</button>
          <button type="button" class="btn btn-primary" @click="generateBulk" :disabled="bulkLoading">
            <span v-if="bulkLoading" class="spinner-border spinner-border-sm me-1"></span>
            Generate
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import CrudPage from '../components/CrudPage.vue'
import api from '../api'

const showBulkModal = ref(false)
const bulkLoading = ref(false)
const shiftOptions = ref([])

const bulkForm = ref({
  year: new Date().getFullYear(),
  month: new Date().getMonth() + 1,
  patterns: [null, null, null, null, null, null, null]
})

onMounted(async () => {
  try {
    const { data } = await api.get('/shifts', { params: { per_page: 100 } })
    if (data && data.data) {
      shiftOptions.value = data.data.data || data.data
    }
  } catch (e) {
    console.error(e)
  }
})

const generateBulk = async () => {
  if (!confirm('Anda yakin ingin mengubah pola shift dan meng-generate shift untuk seluruh karyawan aktif pada bulan ini? Aksi ini akan menimpa data sebelumnya.')) return

  bulkLoading.value = true
  try {
    const patternsPayload = bulkForm.value.patterns.map((shift_id, i) => ({
      day_of_week: i,
      shift_id
    }))

    const res = await api.post('/employee-shifts/generate-all', {
      year: bulkForm.value.year,
      month: bulkForm.value.month,
      patterns: patternsPayload
    })

    if (res.data.success) {
      alert('Berhasil generate shift massal')
      showBulkModal.value = false
    } else {
      alert(res.data.message || 'Gagal generate shift massal')
    }
  } catch (e) {
    alert(e.response?.data?.message || 'Terjadi kesalahan sistem')
  } finally {
    bulkLoading.value = false
  }
}

const onFormChange = async (form) => {
  if (form.clock_in && form.clock_out) {
    const start = new Date(`2000-01-01T${form.clock_in}`)
    const end = new Date(`2000-01-01T${form.clock_out}`)
    let diff = (end - start) / (1000 * 60 * 60)
    if (diff < 0) {
      diff += 24
    }
    // Only update if it's different to prevent infinite loop
    if (form.working_hours !== diff) {
      form.working_hours = diff
    }
  }

  // Handle auto-generate code (Temporarily disabled for manual input)
  /*
  if (form.company_id && form.company_id !== form._lastCompanyId) {
    if (!form.id) {
      try {
        const { data } = await api.get(`/shifts/generate-code`, { params: { company_id: form.company_id } })
        if (data.status === 'success' && data.data) {
          form.code = data.data
        }
      } catch (e) {
        console.error('Failed to generate shift code', e)
      }
    }
    form._lastCompanyId = form.company_id
  }
  */
}
</script>
