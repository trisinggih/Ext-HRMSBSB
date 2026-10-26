<template>
  <div>
    <CrudPage
      title="Template KPI"
      singular="Template KPI"
      endpoint="/kpi/templates"
      :columns="columns"
      :filters="filters"
      :form-fields="[]"
      :initial-form="initialForm"
      :map-data-to-form="mapDataToForm"
      :transform-payload="transformPayload"
    >
      <template #cell-scope="{ row }">
        <span class="badge mb-1" :class="row.scope === 'position' ? 'bg-primary' : 'bg-success'">
          {{ row.scope === 'position' ? 'Jabatan' : 'Divisi' }}
        </span>
      </template>
      <template #cell-target="{ row }">
        {{ row.scope === 'position' ? (row.position?.name || '—') : (row.division?.name || '—') }}
      </template>
      <template #cell-rating_scale="{ row }">
        <span class="text-muted small">
          {{ row.rating_scale?.name || 'Skala default' }}
        </span>
      </template>
      <template #cell-indicators="{ row }">
        <div class="d-flex flex-column gap-1">
          <div v-for="ti in (row.template_indicators || []).slice(0, 3)" :key="ti.id"
                class="d-flex justify-content-between align-items-center bg-light rounded-2 px-2 py-1 mb-1">
            <span class="small text-truncate me-2">{{ ti.indicator?.name }}</span>
            <span class="badge bg-secondary fw-normal" style="white-space:nowrap">Bobot {{ parseFloat(ti.weight) }}</span>
          </div>
          <div v-if="(row.template_indicators || []).length > 3" class="text-muted" style="font-size:11px;">
            +{{ row.template_indicators.length - 3 }} indikator lainnya
          </div>
          <div v-if="(row.template_indicators || []).length === 0" class="text-muted fst-italic small">Belum ada indikator</div>
        </div>
      </template>

      <!-- Custom Form -->
      <template #form="{ form, saving }">
        <div class="row g-3 mb-4 border-bottom pb-4">
          <div class="col-md-6">
            <label class="form-label fw-semibold small mb-1">Nama Template <span class="text-danger">*</span></label>
            <input v-model="form.name" type="text" class="form-control" required placeholder="Contoh: KPI Staff Sales" />
          </div>
          <div class="col-md-3">
            <label class="form-label fw-semibold small mb-1">Tipe <span class="text-danger">*</span></label>
            <select v-model="form.scope" class="form-select" @change="form.position_id = null; form.division_id = null">
              <option value="position">Per Jabatan</option>
              <option value="division">Per Divisi</option>
            </select>
          </div>
          <div class="col-md-3" v-if="form.scope === 'position'">
            <label class="form-label fw-semibold small mb-1">Jabatan</label>
            <Multiselect v-model="form.position_id" :options="async (q) => fetchOptions('/positions', q)" searchable class="custom-multiselect" placeholder="Pilih jabatan..." />
          </div>
          <div class="col-md-3" v-if="form.scope === 'division'">
            <label class="form-label fw-semibold small mb-1">Divisi</label>
            <Multiselect v-model="form.division_id" :options="async (q) => fetchOptions('/divisions', q)" searchable class="custom-multiselect" placeholder="Pilih divisi..." />
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold small mb-1">Skala Penilaian Default</label>
            <Multiselect v-model="form.rating_scale_id" :options="async (q) => fetchOptions('/kpi/rating-scales', q)" searchable class="custom-multiselect" placeholder="Pilih skala..." />
          </div>
          <div class="col-md-12">
            <label class="form-label fw-semibold small mb-1">Deskripsi</label>
            <input v-model="form.description" type="text" class="form-control" placeholder="Keterangan template..." />
          </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3">
          <h6 class="fw-bold mb-0">Daftar Indikator & Bobot</h6>
          <button type="button" class="btn btn-sm btn-outline-success" @click="form.indicators.push({ indicator_id: null, weight: 0, frequency: '', calculation_method: '', target: '' })">
            <i class="mdi mdi-plus me-1"></i> Tambah Indikator
          </button>
        </div>
        <div class="table-responsive">
          <table class="table table-bordered align-middle">
            <thead class="table-light">
              <tr>
                <th style="min-width: 250px;">Indikator KPI</th>
                <th style="width:100px">Bobot (%)</th>
                <th style="width:130px">Frekuensi</th>
                <th style="min-width: 200px;">Rumus / Cara Ukur</th>
                <th style="min-width: 150px;">Target</th>
                <th style="width:50px"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(row, idx) in form.indicators" :key="idx">
                <td>
                  <Multiselect v-model="row.indicator_id" :options="async (q) => fetchIndicatorOptions(q)" searchable class="custom-multiselect" placeholder="Pilih indikator..." />
                </td>
                <td>
                  <input v-model.number="row.weight" type="number" step="0.01" min="0" class="form-control form-control-sm text-center fw-bold" placeholder="20" />
                </td>
                <td>
                  <select v-model="row.frequency" class="form-select form-select-sm">
                    <option value="">(Default Indikator)</option>
                    <option value="H">Harian (H)</option>
                    <option value="M">Mingguan (M)</option>
                    <option value="B">Bulanan (B)</option>
                    <option value="K">Kuartalan (K)</option>
                    <option value="I">Insidental (I)</option>
                  </select>
                </td>
                <td>
                  <input v-model="row.calculation_method" type="text" class="form-control form-control-sm" placeholder="Contoh: Realisasi ÷ Target x 100%" />
                </td>
                <td>
                  <input v-model="row.target" type="text" class="form-control form-control-sm" placeholder="Contoh: ≥ 95%" />
                </td>
                <td class="text-center">
                  <button type="button" class="btn btn-sm btn-outline-danger p-1" @click="form.indicators.splice(idx, 1)">
                    <i class="mdi mdi-close"></i>
                  </button>
                </td>
              </tr>
              <tr v-if="form.indicators.length === 0">
                <td colspan="6" class="text-center text-muted fst-italic py-3">Belum ada indikator. Klik "Tambah Indikator".</td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-if="form.indicators.length > 0" class="d-flex justify-content-between align-items-center text-muted small mt-2">
          <span>*Pastikan total akumulasi bobot indikator berjumlah <strong>100%</strong>.</span>
          <span>Total Bobot Saat Ini: <strong :class="form.indicators.reduce((s, r) => s + (parseFloat(r.weight) || 0), 0) === 100 ? 'text-success' : 'text-danger'">{{ form.indicators.reduce((s, r) => s + (parseFloat(r.weight) || 0), 0) }}%</strong></span>
        </div>
      </template>
    </CrudPage>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import CrudPage from '../../components/CrudPage.vue'
import Multiselect from '@vueform/multiselect'
import '@vueform/multiselect/themes/default.css'
import api from '../../api'

const columns = [
  { key: 'name', label: 'Nama Template', class: 'fw-bold' },
  { key: 'scope', label: 'Tipe' },
  { key: 'target', label: 'Jabatan / Divisi' },
  { key: 'rating_scale', label: 'Skala Penilaian' },
  { key: 'indicators', label: 'Indikator & Bobot', style: 'min-width:300px;' },
]

const filters = ref([
  { key: 'scope', label: 'Tipe', options: [
    { value: 'position', label: 'Per Jabatan' },
    { value: 'division', label: 'Per Divisi' }
  ]}
])

const fetchOptions = async (endpoint, query) => {
  try {
    const { data } = await api.get(endpoint, { params: { search: query, per_page: 50 } })
    return (data.data?.data || data.data || []).map(item => ({ value: item.id, label: item.name }))
  } catch { return [] }
}

const fetchIndicatorOptions = async (query) => {
  try {
    const { data } = await api.get('/kpi/indicators', { params: { search: query, per_page: 50, is_active: 1 } })
    return (data.data?.data || data.data || []).map(item => ({
      value: item.id,
      label: `[${item.type?.toUpperCase()}] ${item.name}${item.category ? ' · ' + item.category : ''}`
    }))
  } catch { return [] }
}

const initialForm = () => ({
  name: '',
  description: '',
  scope: 'position',
  position_id: null,
  division_id: null,
  rating_scale_id: null,
  is_active: true,
  indicators: []
})

const mapDataToForm = (tmpl) => ({
  name: tmpl.name,
  description: tmpl.description || '',
  scope: tmpl.scope,
  position_id: tmpl.position_id,
  division_id: tmpl.division_id,
  rating_scale_id: tmpl.rating_scale_id,
  is_active: tmpl.is_active,
  indicators: (tmpl.template_indicators || []).map(ti => ({
    indicator_id: ti.indicator_id,
    weight: ti.weight,
    frequency: ti.frequency || ti.indicator?.frequency || '',
    calculation_method: ti.calculation_method || ti.indicator?.calculation_method || '',
    target: ti.target || '',
  })),
})

const transformPayload = (f) => ({
  ...f,
  // ensure no undefined values are sent
  description: f.description || '',
})
</script>

<style scoped>
.custom-multiselect {
  --ms-bg: #f8f9fa;
  --ms-border-color: transparent;
  --ms-radius: 0.5rem;
  --ms-py: 0.3rem;
  --ms-px: 0.75rem;
  --ms-ring-width: 0px;
  --ms-placeholder-color: #6c757d;
  --ms-font-size: 0.875rem;
}
</style>
