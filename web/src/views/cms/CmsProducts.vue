<template>
  <div>
    <!-- Header -->
    <div class="row mb-4 align-items-center">
      <div class="col-sm-6">
        <h4 class="mb-0 font-size-18">Katalog Produk CMS</h4>
        <p class="text-muted mt-1 mb-0">Kelola produk yang ditampilkan di halaman utama company profile.</p>
      </div>
      <div class="col-sm-6 text-sm-end mt-3 mt-sm-0">
        <button @click="openCreate" class="btn btn-primary waves-effect waves-light">
          <i class="mdi mdi-plus me-1"></i> Tambah Produk
        </button>
      </div>
    </div>

    <!-- Filter Bar -->
    <div class="card mb-4">
      <div class="card-body p-3">
        <div class="row align-items-center gap-3 gap-md-0">
          <div class="col-md-6">
            <select v-model="filters.category" @change="() => { page = 1; load() }" class="form-select">
              <option value="">Semua Kategori</option>
              <option value="cvt">Transmisi CVT</option>
              <option value="engine">Sensor &amp; Mesin</option>
              <option value="handling">Handling &amp; Sasis</option>
            </select>
          </div>
          <div class="col-md-6 mt-3 mt-md-0">
            <div class="search-box">
              <div class="position-relative">
                <input type="text" v-model="filters.search" @input="debouncedLoad" class="form-control rounded bg-light border-light" placeholder="Cari nama produk...">
                <i class="mdi mdi-magnify search-icon"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Error Alert -->
    <div v-if="error" class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
      {{ error }}
      <button type="button" class="btn-close" @click="error = ''" aria-label="Close"></button>
    </div>

    <!-- Product Table -->
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-bordered table-nowrap table-hover mb-0 align-middle">
                <thead class="table-light">
                  <tr>
                    <th>Gambar</th>
                    <th>Nama Produk</th>
                    <th>Kategori</th>
                    <th>Badge</th>
                    <th>Status</th>
                    <th class="text-end">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="p in products" :key="p.id">
                    <td>
                      <img v-if="getImages(p).length > 0" :src="resolveImage(getImages(p)[0])" :alt="p.name" class="rounded avatar-md object-fit-cover border" style="object-fit: cover; background: #fff;" />
                      <div v-else class="avatar-md rounded bg-light border d-flex align-items-center justify-content-center text-muted">
                        <span class="font-size-12">No img</span>
                      </div>
                    </td>
                    <td>
                      <div class="fw-medium text-dark font-size-14">{{ p.name }}</div>
                      <div class="text-muted font-size-12 mt-1">{{ p.specLabel }}</div>
                    </td>
                    <td class="text-muted">{{ p.categoryLabel || p.category }}</td>
                    <td><code class="font-size-12 text-primary">{{ p.badge }}</code></td>
                    <td>
                      <span :class="['badge font-size-12', p.is_active ? 'bg-success' : 'bg-secondary']">
                        {{ p.is_active ? 'Aktif' : 'Nonaktif' }}
                      </span>
                    </td>
                    <td class="text-end">
                      <button @click="openEdit(p)" class="btn btn-outline-primary btn-sm me-1" title="Edit">
                        <i class="mdi mdi-pencil"></i>
                      </button>
                      <button @click="remove(p)" class="btn btn-outline-danger btn-sm" title="Hapus">
                        <i class="mdi mdi-delete"></i>
                      </button>
                    </td>
                  </tr>
                  <tr v-if="!products.length">
                    <td colspan="6" class="text-center py-4 text-muted">
                      {{ loading ? 'Memuat data...' : 'Belum ada produk' }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Pagination -->
            <div v-if="meta && meta.last_page > 1" class="row align-items-center mt-4">
              <div class="col-sm-6">
                <p class="text-muted mb-0">Halaman {{ meta.current_page }} dari {{ meta.last_page }} ({{ meta.total }} produk)</p>
              </div>
              <div class="col-sm-6">
                <ul class="pagination pagination-sm m-0 float-sm-end">
                  <li class="page-item" :class="{ disabled: meta.current_page <= 1 }">
                    <button class="page-link" @click="page--; load()">Prev</button>
                  </li>
                  <li class="page-item" :class="{ disabled: meta.current_page >= meta.last_page }">
                    <button class="page-link" @click="page++; load()">Next</button>
                  </li>
                </ul>
              </div>
            </div>

          </div>
        </div>
      </div>
    </div>

    <!-- Modal Form -->
    <div v-if="modal" class="modal fade show" tabindex="-1" style="display: block; background: rgba(0,0,0,0.5);" @click.self="modal = false">
      <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">{{ editingId ? 'Edit' : 'Tambah' }} Produk</h5>
            <button type="button" class="btn-close" @click="modal = false" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            
            <div class="mb-3">
              <label class="form-label">Nama Produk <span class="text-danger">*</span></label>
              <input type="text" v-model="form.name" class="form-control" placeholder="CVT Spring Jangeby">
            </div>

            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">Kategori <span class="text-danger">*</span></label>
                <select v-model="form.category" class="form-select">
                  <option value="cvt">CVT</option>
                  <option value="engine">Engine</option>
                  <option value="handling">Handling</option>
                </select>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Label Kategori</label>
                <input type="text" v-model="form.categoryLabel" class="form-control" placeholder="TRANSMISI CVT">
              </div>
            </div>

            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">Badge</label>
                <input type="text" v-model="form.badge" class="form-control" placeholder="BEST SELLER">
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Highlight</label>
                <input type="text" v-model="form.highlight" class="form-control" placeholder="Anti-Fatigue">
              </div>
            </div>

            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">Spec Label</label>
                <input type="text" v-model="form.specLabel" class="form-control" placeholder="Spek: 1000 / 1500 RPM">
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Spec Value</label>
                <input type="text" v-model="form.specValue" class="form-control" placeholder="100% PRESISI">
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label">Deskripsi</label>
              <textarea v-model="form.description" rows="3" class="form-control"></textarea>
            </div>

            <div class="mb-3">
              <label class="form-label">URL Gambar</label>
              <p class="text-muted font-size-12 mb-2">Masukkan satu URL per baris. Baris pertama = gambar utama.</p>
              <textarea v-model="imagesText" rows="3" class="form-control font-family-monospace font-size-12" placeholder="https://example.com/image1.jpg&#10;https://example.com/image2.jpg"></textarea>
            </div>

            <div class="mb-3">
              <label class="form-label">Spesifikasi Tambahan</label>
              <div v-for="(spec, index) in specsList" :key="'spec'+index" class="d-flex gap-2 mb-2">
                <input type="text" v-model="spec.key" class="form-control form-control-sm w-50" placeholder="Kunci (Misal: Material)">
                <input type="text" v-model="spec.value" class="form-control form-control-sm w-50" placeholder="Nilai (Misal: Besi Baja)">
                <button type="button" class="btn btn-sm btn-outline-danger px-2 py-0" @click="specsList.splice(index, 1)"><i class="ri-delete-bin-line"></i></button>
              </div>
              <button type="button" class="btn btn-sm btn-outline-primary mt-1" @click="specsList.push({key:'', value:''})">+ Tambah Spesifikasi</button>
            </div>

            <div class="mb-3">
              <label class="form-label">Kompatibilitas Motor</label>
              <div v-for="(motor, index) in compatibilityList" :key="'compat'+index" class="d-flex gap-2 mb-2">
                <input type="text" v-model="compatibilityList[index]" class="form-control form-control-sm w-100" placeholder="Misal: Honda Beat FI">
                <button type="button" class="btn btn-sm btn-outline-danger px-2 py-0" @click="compatibilityList.splice(index, 1)"><i class="ri-delete-bin-line"></i></button>
              </div>
              <button type="button" class="btn btn-sm btn-outline-primary mt-1" @click="compatibilityList.push('')">+ Tambah Motor</button>
            </div>

            <div class="mb-3">
              <label class="form-label">Marketplace Links</label>
              <div class="input-group input-group-sm mb-2">
                <span class="input-group-text w-25 bg-light">Shopee</span>
                <input type="text" v-model="marketplaceLinksList.shopee" class="form-control" placeholder="URL Shopee">
              </div>
              <div class="input-group input-group-sm mb-2">
                <span class="input-group-text w-25 bg-light">Tokopedia</span>
                <input type="text" v-model="marketplaceLinksList.tokopedia" class="form-control" placeholder="URL Tokopedia">
              </div>
              <div class="input-group input-group-sm mb-2">
                <span class="input-group-text w-25 bg-light">TikTok</span>
                <input type="text" v-model="marketplaceLinksList.tiktok" class="form-control" placeholder="URL TikTok">
              </div>
              <div class="input-group input-group-sm mb-2">
                <span class="input-group-text w-25 bg-light">WhatsApp</span>
                <input type="text" v-model="marketplaceLinksList.whatsapp" class="form-control" placeholder="URL WhatsApp">
              </div>
            </div>

            <div class="form-check form-switch mb-0">
              <input class="form-check-input" type="checkbox" id="productStatus" v-model="form.is_active">
              <label class="form-check-label" for="productStatus">Aktif (tampilkan di website)</label>
            </div>
            
          </div>
          <div class="modal-footer">
            <button type="button" @click="modal = false" class="btn btn-secondary waves-effect">Batal</button>
            <button type="button" @click="save" :disabled="saving" class="btn btn-primary waves-effect waves-light">
              {{ saving ? 'Menyimpan...' : 'Simpan Produk' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import api from '../../api'

const products = ref([])
const meta = ref(null)
const page = ref(1)
const loading = ref(false)
const saving = ref(false)
const error = ref('')
const modal = ref(false)
const editingId = ref(null)
const specsList = ref([])
const compatibilityList = ref([])
const marketplaceLinksList = reactive({ shopee: '', tokopedia: '', tiktok: '', whatsapp: '' })
const filters = reactive({ search: '', category: '' })

// Form state
const form = ref({})
const imagesText = ref('')

const getImages = (p) => {
  if (Array.isArray(p.images)) return p.images;
  if (typeof p.images === 'string') {
    try {
      const parsed = JSON.parse(p.images);
      return Array.isArray(parsed) ? parsed : [];
    } catch(e) {
      return [];
    }
  }
  return [];
}

const resolveImage = (url) => {
  if (!url) return '';
  if (url.startsWith('http') || url.startsWith('data:') || url.startsWith('/')) {
    return url;
  }
  return `/assets/images/company/${url}`;
}

let searchTimer = null
const debouncedLoad = () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => { page.value = 1; load() }, 400)
}

const load = async () => {
  loading.value = true; error.value = ''
  try {
    const { data } = await api.get('/cms/products', {
      params: { page: page.value, search: filters.search || undefined, category: filters.category || undefined }
    })
    products.value = data.data?.data ?? data.data ?? []
    meta.value = data.data?.meta ?? null
  } catch (e) {
    error.value = e.response?.data?.message || 'Gagal memuat data produk'
  } finally {
    loading.value = false }
}

const defaultForm = () => ({
  name: '', category: 'cvt', categoryLabel: '', badge: '', highlight: '',
  specLabel: '', specValue: '', description: '', is_active: true
})

const openCreate = () => {
  editingId.value = null
  form.value = defaultForm()
  imagesText.value = ''
  specsList.value = []
  compatibilityList.value = []
  Object.assign(marketplaceLinksList, { shopee: '', tokopedia: '', tiktok: '', whatsapp: '' })
  modal.value = true
}

const openEdit = (p) => {
  editingId.value = p.id
  form.value = { ...p }
  imagesText.value = getImages(p).join('\n')
  
  const parseJSONField = (val, defaultVal) => {
    if (!val) return defaultVal;
    if (typeof val === 'string') {
      try { return JSON.parse(val); } catch (e) { return defaultVal; }
    }
    return val;
  };

  const pSpecs = parseJSONField(p.specs, {});
  specsList.value = Object.entries(pSpecs).map(([key, value]) => ({ key, value: String(value) }))
  
  const pCompat = parseJSONField(p.compatibility, []);
  compatibilityList.value = [...pCompat]
  
  const pLinks = parseJSONField(p.marketplaceLinks, {});
  Object.assign(marketplaceLinksList, { shopee: '', tokopedia: '', tiktok: '', whatsapp: '', ...pLinks })
  
  modal.value = true
}



const save = async () => {
  const specs = specsList.value.reduce((acc, curr) => {
    if (curr.key && curr.value) acc[curr.key] = curr.value;
    return acc;
  }, {});
  const compatibility = compatibilityList.value.filter(m => m.trim());
  const marketplaceLinks = Object.fromEntries(
    Object.entries(marketplaceLinksList).filter(([_, v]) => v.trim())
  );

  const images = imagesText.value.split('\n').map(u => u.trim()).filter(Boolean)

  saving.value = true; error.value = ''
  try {
    const payload = { ...form.value, images, specs, compatibility, marketplaceLinks }
    if (editingId.value) {
      await api.put(`/cms/products/${editingId.value}`, payload)
    } else {
      await api.post('/cms/products', payload)
    }
    modal.value = false; load()
  } catch (e) {
    const errs = e.response?.data?.errors
    error.value = errs ? Object.values(errs).flat()[0] : (e.response?.data?.message || 'Gagal menyimpan')
  } finally {
    saving.value = false
  }
}

const remove = async (p) => {
  if (!confirm(`Hapus produk "${p.name}"? Tindakan ini tidak dapat dibatalkan.`)) return
  try {
    await api.delete(`/cms/products/${p.id}`)
    load()
  } catch (e) {
    error.value = e.response?.data?.message || 'Gagal menghapus'
  }
}

onMounted(load)
</script>
