<template>
  <div>
    <div class="d-flex align-items-center mb-4">
      <router-link to="/hrms/companies" class="btn-back me-3">
        <i class="mdi mdi-arrow-left"></i> Kembali
      </router-link>
      <h4 class="mb-0 fw-bold text-dark">Detail Perusahaan</h4>
    </div>

    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status"></div>
    </div>
    <div v-else-if="company" class="row">
      <!-- Header -->
      <div class="col-12 mb-2">
        <div class="card border-0 shadow-sm rounded-4">
          <div class="card-body p-4">
            <div class="row align-items-center">
              <div class="col-auto">
                <div class="text-center bg-light rounded d-flex align-items-center justify-content-center border overflow-hidden" style="width: 80px; height: 80px;">
                  <i class="uim uim-building text-primary" style="font-size: 2.5rem;"></i>
                </div>
              </div>
              <div class="col">
                <h4 class="mb-1 fw-bold text-dark">{{ company.name }}</h4>
                <p class="text-secondary mb-2">
                  {{ company.legal_name || 'Tidak ada nama legal' }} &bull; Grup: <span class="fw-semibold">{{ company.group?.name || '-' }}</span>
                  <span v-if="company.parent_company" class="ms-2 badge bg-info-subtle text-info border">
                    <i class="mdi mdi-source-branch me-1"></i>Anak Perusahaan dari: {{ company.parent_company.name }}
                  </span>
                </p>
                <div v-if="company.child_companies && company.child_companies.length" class="mb-2">
                  <span class="text-muted small me-1"><i class="mdi mdi-domain me-1"></i>Membawahi Anak Perusahaan:</span>
                  <span v-for="child in company.child_companies" :key="child.id" class="badge bg-light text-primary border me-1">
                    {{ child.name }}
                  </span>
                </div>
                <span class="badge px-3 py-2" :class="company.is_active ? 'bg-success text-white' : 'bg-danger-subtle text-danger'">
                  {{ company.is_active ? 'Perusahaan Aktif' : 'Nonaktif' }}
                </span>
              </div>
              <div class="col-auto ms-auto d-none d-lg-block">
                <div class="d-flex gap-4">
                  <div>
                    <label class="text-muted mb-1 d-block small fw-semibold"><i class="uim uim-envelope me-1"></i> Email</label>
                    <span class="text-dark fw-medium">{{ company.email || '-' }}</span>
                  </div>
                  <div>
                    <label class="text-muted mb-1 d-block small fw-semibold"><i class="uim uim-phone-alt me-1"></i> Telepon</label>
                    <span class="text-dark fw-medium">{{ company.phone || '-' }}</span>
                  </div>
                  <div>
                    <label class="text-muted mb-1 d-block small fw-semibold"><i class="uim uim-map-marker me-1"></i> Kota</label>
                    <span class="text-dark fw-medium">{{ company.city || '-' }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Content Tabs -->
      <div class="col-12">
        <div class="card">
          <div class="card-body p-0">
            <div class="p-4">
              <!-- INFORMASI DASAR TAB -->
              <div class="tab-pane active" id="info" role="tabpanel">
                <form @submit.prevent="saveCompanyInfo">
                  <div class="row">
                    <div class="col-md-6 mb-3">
                      <label class="form-label fw-semibold">Nama Perusahaan <span class="text-danger">*</span></label>
                      <input type="text" class="form-control" v-model="form.name" required />
                    </div>
                    <div class="col-md-6 mb-3">
                      <label class="form-label fw-semibold">Nama Legal (PT/CV)</label>
                      <input type="text" class="form-control" v-model="form.legal_name" />
                    </div>
                    <div class="col-md-6 mb-3">
                      <label class="form-label fw-semibold">Perusahaan Induk (Holding)</label>
                      <select class="form-select" v-model="form.parent_company_id">
                        <option :value="null">-- Tidak Ada (Perusahaan Utama) --</option>
                        <option v-for="c in otherCompanies" :key="c.id" :value="c.id" :disabled="c.id == companyId">
                          {{ c.name }} {{ c.legal_name ? `(${c.legal_name})` : '' }}
                        </option>
                      </select>
                    </div>
                    <div class="col-md-6 mb-3">
                      <label class="form-label fw-semibold">NPWP</label>
                      <input type="text" class="form-control" v-model="form.npwp" @input="form.npwp = form.npwp ? form.npwp.replace(/[^0-9]/g, '') : ''" placeholder="Hanya Angka" />
                    </div>
                    <div class="col-md-6 mb-3">
                      <label class="form-label fw-semibold">Email</label>
                      <input type="email" class="form-control" v-model="form.email" />
                    </div>
                    <!-- <div class="col-md-6 mb-3">
                      <label class="form-label fw-semibold">PIN Kiosk (Absensi)</label>
                      <input type="password" class="form-control" v-model="form.kiosk_pin" />
                    </div> -->
                    <div class="col-md-6 mb-3">
                      <label class="form-label fw-semibold">Telepon</label>
                      <input type="text" class="form-control" v-model="form.phone" @input="onPhoneInput(form)" placeholder="628xxxxxxxxxx" />
                    </div>
                    <div class="col-12 mb-3">
                      <label class="form-label fw-semibold">Alamat</label>
                      <textarea class="form-control" v-model="form.address" rows="2"></textarea>
                    </div>
                    <div class="col-md-4 mb-3">
                      <label class="form-label fw-semibold">Provinsi</label>
                      <select class="form-select" v-model="form.province" @change="onProvinceChange">
                        <option value="">Pilih Provinsi</option>
                        <option v-for="prov in provinces" :key="prov.code" :value="prov.name">{{ prov.name }}</option>
                      </select>
                    </div>
                    <div class="col-md-4 mb-3">
                      <label class="form-label fw-semibold">Kota</label>
                      <select class="form-select" v-model="form.city" :disabled="!form.province">
                        <option value="">Pilih Kota</option>
                        <option v-for="c in cities" :key="c.code" :value="c.name">{{ c.name }}</option>
                      </select>
                    </div>
                    <div class="col-md-4 mb-3">
                      <label class="form-label fw-semibold">Kode Pos</label>
                      <input type="text" class="form-control" v-model="form.postal_code" @input="form.postal_code = form.postal_code ? form.postal_code.replace(/[^0-9]/g, '') : ''" placeholder="Hanya Angka" />
                    </div>

                    <div class="col-12 mb-3">
                      <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" id="isActive" v-model="form.is_active" />
                        <label class="form-check-label" for="isActive">Perusahaan Aktif</label>
                      </div>
                    </div>
                  </div>
                  <hr>
                  <div class="text-end">
                    <button type="submit" class="btn btn-action-add px-4 py-2 waves-effect waves-light shadow-sm" :disabled="savingInfo">
                      <i v-if="savingInfo" class="mdi mdi-spin mdi-loading me-1"></i>
                      <i v-else class="mdi mdi-content-save-outline me-1"></i> Simpan Perubahan
                    </button>
                  </div>
                </form>
              </div>



            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { toast } from 'vue3-toastify'
import api from '../api'

const route = useRoute()
const companyId = route.params.id

const loading = ref(true)
const company = ref(null)

const provinces = ref([])
const cities = ref([])

// Company Info
const groups = ref([])
const otherCompanies = ref([])
const savingInfo = ref(false)
const form = ref({
  group_id: '', parent_company_id: null, name: '', legal_name: '', npwp: '', email: '', phone: '', address: '', city: '', province: '', postal_code: '', is_active: true, kiosk_pin: ''
})
const onPhoneInput = (formObj) => {
  if (!formObj.phone) return
  let val = formObj.phone.replace(/[^0-9]/g, '')
  if (val.startsWith('0')) val = '62' + val.substring(1)
  else if (val.startsWith('8')) val = '628' + val.substring(1)
  formObj.phone = val
}

onMounted(async () => {
  await Promise.all([
    loadProvinces(),
    loadCompany(),
    loadGroups(),
    loadOtherCompanies()
  ])
})

const loadOtherCompanies = async () => {
  try {
    const { data } = await api.get('/companies', { params: { per_page: 100 } })
    otherCompanies.value = data.data?.data || data.data || []
  } catch (e) {
    console.error(e)
  }
}

const loadCompany = async () => {
  loading.value = true
  try {
    const { data } = await api.get(`/companies/${companyId}`)
    company.value = data.data
    Object.keys(form.value).forEach(key => {
      if (company.value[key] !== undefined) form.value[key] = company.value[key]
    })
    if (form.value.province) {
      const selectedProv = provinces.value.find(p => p.name === form.value.province)
      if (selectedProv) {
        await loadCities(selectedProv.code)
      }
    }
  } catch (error) {
    toast.error('Gagal memuat detail perusahaan')
  } finally {
    loading.value = false
  }
}

const loadProvinces = async () => {
  try {
    const { data } = await api.get('/regions/provinces')
    provinces.value = data.data
  } catch (e) {
    console.error(e)
  }
}

const loadCities = async (provinceCode) => {
  try {
    const { data } = await api.get(`/regions/cities/${provinceCode}`)
    cities.value = data.data
  } catch (e) {
    console.error(e)
  }
}

const onProvinceChange = async () => {
  form.value.city = ''
  cities.value = []
  if (form.value.province) {
    const selectedProv = provinces.value.find(p => p.name === form.value.province)
    if (selectedProv) {
      await loadCities(selectedProv.code)
    }
  }
}

const loadGroups = async () => {
  try {
    const { data } = await api.get('/company-groups')
    groups.value = data.data?.data || data.data || []
  } catch (e) {
    console.error(e)
  }
}

const saveCompanyInfo = async () => {
  savingInfo.value = true
  try {
    const formData = new FormData()
    Object.keys(form.value).forEach(key => {
      if (form.value[key] !== null && form.value[key] !== undefined && form.value[key] !== '') {
        // Boolean values must be cast to 1/0 or true/false strings for FormData
        formData.append(key, form.value[key] === true ? 1 : (form.value[key] === false ? 0 : form.value[key]))
      } else if (key === 'parent_company_id') {
        formData.append('parent_company_id', '')
      }
    })
    
    formData.append('_method', 'PUT')

    const { data } = await api.post(`/companies/${companyId}`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    
    company.value = data.data
    Object.keys(form.value).forEach(key => {
      if (company.value[key] !== undefined) form.value[key] = company.value[key]
    })
    toast.success('Informasi perusahaan berhasil diperbarui')
  } catch (error) {
    toast.error(error.response?.data?.message || 'Gagal menyimpan perusahaan')
  } finally {
    savingInfo.value = false
  }
}



</script>
