<template>
  <div>
    <!-- Error Alert -->
    <div v-if="error" class="alert alert-danger" role="alert">
      {{ error }}
    </div>

    <!-- Header (outside card, no background) -->
    <div v-if="!hideHeader" class="d-flex align-items-stretch mb-0" style="min-height: 42px;">
      <!-- Title tab with diagonal cut on right -->
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
        <h4 class="mb-0 font-size-16 fw-semibold text-white text-nowrap" style="letter-spacing: 0.01em;">{{ title }}</h4>
      </div>
      <!-- Buttons aligned to the right -->
      <div class="flex-grow-1 d-flex align-items-center justify-content-end gap-2 ps-3" style="padding-bottom: 10px;">
        <slot name="header-actions"></slot>
        <div v-if="exportable || importable" class="dropdown">
          <button class="btn btn-action-secondary px-3 py-2 waves-effect shadow-none" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="border-radius:7px; font-size: 0.85rem;">
            <i class="mdi mdi-tune-vertical-variant me-1"></i> Opsi
          </button>
          <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 py-2" style="border-radius:10px; min-width:180px;">
            <li v-if="exportable"><a class="dropdown-item py-2 px-3" href="#" @click.prevent="openExportModal"><i class="mdi mdi-file-excel-outline me-2 text-success"></i>Export Excel</a></li>
            <li v-if="importable"><a class="dropdown-item py-2 px-3" href="#" @click.prevent="openImport"><i class="mdi mdi-file-upload-outline me-2 text-primary"></i>Import Data</a></li>
          </ul>
        </div>
        <button v-if="creatable" @click="openCreate" class="btn btn-action-add px-3 py-2 waves-effect waves-light shadow-sm" style="border-radius:7px; font-size: 0.85rem;">
          <i class="mdi mdi-plus-circle-outline font-size-15 me-1"></i> Tambah {{ singular }}
        </button>
      </div>
    </div>

    <!-- Main Card -->
    <div :class="hideCard ? '' : 'card'">
      <div :class="hideCard ? '' : 'card-body'">
        
        <!-- Search and Filter -->
        <div v-if="!hideControls" class="row mb-4 align-items-center">
          <div class="col-sm-12 col-xl-auto mb-3 mb-xl-0">
            <div class="d-flex align-items-center gap-2 flex-wrap">
              <!-- Pagination -->
              <div class="d-flex align-items-center text-muted">
                <span class="me-2 text-nowrap">Tampilkan</span>
                <select v-model="perPage" @change="doSearch" class="form-select form-select-sm" style="width: 70px; border-radius: 8px; font-weight: 500;">
                  <option :value="10">10</option>
                  <option :value="25">25</option>
                  <option :value="50">50</option>
                  <option :value="100">100</option>
                </select>
                <span class="ms-2 text-nowrap">data</span>
              </div>

              <!-- Custom actions for embed -->
              <div v-if="hideHeader && (creatable || exportable || !!$slots['header-actions'])" class="d-flex gap-2 ms-2 align-items-center">
                <slot name="header-actions"></slot>
                <div v-if="exportable || importable" class="dropdown">
                  <button class="btn btn-action-secondary btn-sm px-2 waves-effect shadow-none" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="border-radius:7px;">
                    <i class="mdi mdi-tune-vertical-variant me-1"></i> Opsi
                  </button>
                  <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 py-2" style="border-radius:10px; min-width:180px;">
                    <li v-if="exportable"><a class="dropdown-item py-2 px-3" href="#" @click.prevent="openExportModal"><i class="mdi mdi-file-excel-outline me-2 text-success"></i>Export Excel</a></li>
                    <li v-if="importable"><a class="dropdown-item py-2 px-3" href="#" @click.prevent="openImport"><i class="mdi mdi-file-upload-outline me-2 text-primary"></i>Import Data</a></li>
                  </ul>
                </div>
                <button v-if="creatable" @click="openCreate" class="btn btn-action-add btn-sm px-3 waves-effect waves-light shadow-sm" style="border-radius:7px;">
                  <i class="mdi mdi-plus-circle-outline font-size-14 me-1"></i> Tambah {{ singular }}
                </button>
              </div>
            </div>
          </div>
          <div class="col-sm-12 col-xl">
            <div class="d-flex justify-content-xl-end justify-content-start gap-2 flex-wrap align-items-center">
              
              <!-- Filters -->
              <div v-for="f in filters" :key="f.key">
                <div v-if="f.type === 'date'" class="d-flex align-items-center bg-light px-2 py-1 rounded-2" style="box-shadow: 0 2px 4px rgba(0,0,0,0.03); min-width: 150px;">
                  <span class="text-secondary small me-1 font-size-11 text-nowrap">{{ f.label.replace('Filter ', '') }}:</span>
                  <input type="date" v-model="filterValues[f.key]" @change="handleFilterChange(f)" class="form-control form-control-sm border-0 bg-transparent text-secondary p-0 fw-medium" style="font-size: 0.82rem; box-shadow: none;" />
                </div>
                <div v-else-if="f.type === 'month'" class="d-flex align-items-center bg-light px-2 py-1 rounded-2" style="box-shadow: 0 2px 4px rgba(0,0,0,0.03); min-width: 150px;">
                  <span class="text-secondary small me-1 font-size-11 text-nowrap">{{ f.label.replace('Filter ', '') }}:</span>
                  <input type="month" v-model="filterValues[f.key]" @change="handleFilterChange(f)" class="form-control form-control-sm border-0 bg-transparent text-secondary p-0 fw-medium" style="font-size: 0.82rem; box-shadow: none;" />
                </div>
                <select 
                  v-else 
                  v-model="filterValues[f.key]" 
                  @change="handleFilterChange(f)" 
                  :disabled="f.dependsOnFilter && !filterValues[f.dependsOnFilter]"
                  class="form-select form-select-sm border-0 bg-light text-secondary" 
                  style="border-radius: 8px; font-weight: 500; min-width: 140px; box-shadow: 0 2px 4px rgba(0,0,0,0.03);"
                  :style="(f.dependsOnFilter && !filterValues[f.dependsOnFilter]) ? { opacity: 0.6, cursor: 'not-allowed' } : {}"
                >
                  <option value="">
                    {{ (f.dependsOnFilter && !filterValues[f.dependsOnFilter]) ? `Pilih ${getFilterLabel(f.dependsOnFilter)} dahulu` : `Semua ${f.label.replace('Filter ', '')}` }}
                  </option>
                  <option v-for="opt in (f.options || filterOptions[f.key])" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                </select>
              </div>

              <!-- Search -->
              <div class="position-relative ms-md-2" style="width: 240px;">
                <div class="input-group input-group-sm bg-light" style="border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.03); overflow: hidden;">
                  <span class="input-group-text bg-transparent border-0 text-muted ps-3 pe-2">
                    <i class="mdi mdi-magnify font-size-16"></i>
                  </span>
                  <input type="search" v-model="searchQuery" @keyup.enter="doSearch" @search="e => { if (!searchQuery) doSearch() }" class="form-control border-0 bg-transparent fw-medium ps-0" style="box-shadow: none;" placeholder="Pencarian cepat...">
                </div>
              </div>

            </div>
          </div>
        </div>

        <!-- Table -->
        <div class="table-responsive">
          <table class="table table-striped table-bordered table-hover mb-0" :class="tableClass">
            <thead class="custom-thead-gradient">
              <tr>
                <th style="width: 50px;" class="text-center align-middle text-white border-0">No.</th>
                <template v-for="col in columns" :key="col.key">
                  <th 
                    v-if="!col.hidden"
                    class="text-white border-0"
                    :style="{ width: col.width, cursor: col.sortable !== false ? 'pointer' : 'default' }"
                    @click="col.sortable !== false ? toggleSort(col.key) : null">
                    <div class="d-flex justify-content-between align-items-center">
                      <span>{{ col.label }}</span>
                      <span v-if="col.sortable !== false && sortCol === col.key">
                        <i :class="sortDir === 'asc' ? 'mdi mdi-arrow-up' : 'mdi mdi-arrow-down'"></i>
                      </span>
                      <span v-else-if="col.sortable !== false" class="text-white-50">
                        <i class="mdi mdi-swap-vertical"></i>
                      </span>
                    </div>
                  </th>
                </template>
                <th v-if="!disableActions" class="text-end text-white border-0" style="width: 150px;">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(row, index) in rows" :key="row.id">
                <td class="text-center align-middle text-muted">
                  {{ ((meta?.current_page || page) - 1) * Number(perPage) + index + 1 }}
                </td>
                <template v-for="col in columns" :key="col.key">
                  <td v-if="!col.hidden">
                    <slot :name="`cell-${col.key}`" :row="row" :col="col" :value="cell(row, col)" :index="index">
                      <img v-if="col.type === 'image'" :src="cell(row, col) || 'https://ui-avatars.com/api/?name=' + (row.name || 'User')" class="rounded-circle img-thumbnail" style="width: 40px; height: 40px; object-fit: cover;" alt="avatar">
                      <component v-else :is="col.render || 'span'">{{ cell(row, col) }}</component>
                    </slot>
                  </td>
                </template>
                <td v-if="!disableActions" class="text-end text-nowrap">
                  <div class="d-flex justify-content-end gap-1">
                    <slot name="actions" :row="row" :open-edit="openEdit" :remove="remove">
                      <button @click="openEdit(row)" class="btn-action-edit" title="Edit">
                        <i class="mdi mdi-pencil-outline"></i> Edit
                      </button>
                      <button @click="remove(row)" class="btn-action-delete" title="Hapus">
                        <i class="mdi mdi-trash-can-outline"></i> Hapus
                      </button>
                    </slot>
                  </div>
                </td>
              </tr>
              <tr v-if="!rows.length">
                <td :colspan="columns.filter(c => !c.hidden).length + (disableActions ? 1 : 2)" class="text-center text-muted py-4">
                  {{ loading ? 'Memuat data...' : 'Data tidak ditemukan' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="!hideControls" class="row align-items-center mt-3">
          <div class="col-sm-6">
            <div class="text-muted text-sm-start mt-2 mt-sm-0">
              Menampilkan halaman {{ meta?.current_page || page }} dari {{ meta?.last_page || 1 }}
            </div>
          </div>
          <div class="col-sm-6">
            <div class="float-sm-end">
              <ul class="pagination pagination-rounded mb-0">
                <li class="page-item" :class="{ disabled: (meta?.current_page || page) <= 1 }">
                  <a class="page-link" href="#" @click.prevent="page = (meta?.current_page || page) - 1"><i class="mdi mdi-chevron-left"></i></a>
                </li>
                <li v-for="p in (meta?.last_page || 1)" :key="p" class="page-item" :class="{ active: (meta?.current_page || page) === p }">
                  <a class="page-link" href="#" @click.prevent="page = p">{{ p }}</a>
                </li>
                <li class="page-item" :class="{ disabled: (meta?.current_page || page) >= (meta?.last_page || 1) }">
                  <a class="page-link" href="#" @click.prevent="page = (meta?.current_page || page) + 1"><i class="mdi mdi-chevron-right"></i></a>
                </li>
              </ul>
            </div>
          </div>
        </div>

        <!-- Footer Slot -->
        <slot name="footer" :rows="rows"></slot>

      </div>
    </div>

    <!-- Modal Form -->
  <div v-if="modal" class="modal fade show d-block" tabindex="-1" style="background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px);">
      <div class="modal-dialog modal-dialog-centered" :class="modalSize">
        <div class="modal-content border-0 shadow-lg rounded-4" style="overflow: visible;">
          <div class="modal-header border-bottom-0 pt-4 px-4 pb-2">
            <h5 class="modal-title fw-bold fs-4 text-dark">{{ editingId ? 'Edit' : 'Tambah' }} {{ singular }}</h5>
            <button type="button" class="btn-close shadow-none" @click="modal = false" aria-label="Close"></button>
          </div>
          <div class="modal-body px-4 pb-4 pt-2" style="overflow: visible;">
            <form @submit.prevent="save">
              <slot name="form" :form="form" :saving="saving" :editing-id="editingId" :handle-file-upload="handleFileUpload" :form-fields="formFields">
                <div class="row g-3">
                  <div :class="f.colClass || 'col-md-6'" v-for="f in formFields" :key="f.key" v-show="f.showIf ? f.showIf(form) : true">
                    <label class="form-label fw-semibold text-secondary small mb-1">{{ getLabel(f, form) }} <span v-if="f.required" class="text-danger">*</span></label>
                    
                    <slot :name="'field-' + f.key" :form="form" :field="f" :editing-id="editingId" :format-money="formatMoney" :handle-file-upload="handleFileUpload">
                      <input v-if="f.type !== 'select' && f.type !== 'multiselect' && f.type !== 'relation' && f.type !== 'file' && f.type !== 'currency'" v-model="form[f.key]" :type="f.type || 'text'" :required="f.required" :readonly="f.readonly" :disabled="f.disabled" class="form-control bg-light border-0 px-3 py-2 shadow-none rounded-3" :placeholder="'Masukkan ' + getLabel(f, form).toLowerCase()" />
                    
                    <div v-else-if="f.type === 'currency'" class="input-group">
                      <span class="input-group-text bg-light border-0 border-end border-secondary border-opacity-10 text-muted fw-medium px-3">Rp</span>
                      <input 
                        type="text" 
                        class="form-control bg-light border-0 px-3 py-2 shadow-none rounded-end-3" 
                        :value="formatMoney(form[f.key])" 
                        @input="e => { form[f.key] = e.target.value.replace(/\D/g, ''); e.target.value = formatMoney(form[f.key]) }"
                        :required="f.required"
                        :placeholder="'Masukkan ' + getLabel(f, form).toLowerCase()"
                      />
                    </div>
                    
                    <input v-else-if="f.type === 'file'" type="file" @change="e => handleFileUpload(f.key, e)" class="form-control bg-light border-0 px-3 py-2 shadow-none rounded-3" :accept="f.accept || '*/*'" :required="!editingId && f.required" />
                    
                    <textarea v-else-if="f.type === 'textarea'" v-model="form[f.key]" class="form-control bg-light border-0 px-3 py-2 shadow-none rounded-3" rows="3" :placeholder="'Masukkan ' + getLabel(f, form).toLowerCase()"></textarea>

                    <select v-else-if="f.type === 'select'" v-model="form[f.key]" class="form-select bg-light border-0 px-3 py-2 shadow-none rounded-3" :required="f.required" :disabled="f.disabled">
                      <option value="">-- Pilih --</option>
                      <option v-for="opt in f.options" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                    </select>
                    
                    <Multiselect
                      v-else-if="f.type === 'multiselect'"
                      v-model="form[f.key]"
                      mode="tags"
                      :options="f.options"
                      :searchable="true"
                      :close-on-select="false"
                      :placeholder="'Pilih ' + getLabel(f, form)"
                      class="rounded-3 border-0 bg-light shadow-none custom-multiselect"
                    />

                    <Multiselect
                      v-else-if="f.type === 'relation'"
                      :key="f.key + (f.dependsOn ? '_' + form[f.dependsOn] : '')"
                      v-model="form[f.key]"
                      :options="async (query) => await fetchRelationOptions(f, query)"
                      :searchable="true"
                      :clear-on-select="false"
                      :placeholder="'Pilih ' + getLabel(f, form)"
                      :resolve-on-load="true"
                      :delay="300"
                      :min-chars="0"
                      class="rounded-3 border-0 bg-light shadow-none custom-multiselect"
                    />

                    <!-- HINT -->
                    <div v-if="f.hint" class="form-text text-muted small mt-1" v-html="f.hint"></div>
                    </slot>
                  </div>
                </div>
              </slot>
              <div class="text-end mt-4 pt-3 border-top">
                <button type="button" @click="modal = false" class="btn btn-action-secondary me-2 waves-effect px-4 py-2" style="border-radius: 7px;">Batal</button>
                <button type="submit" :disabled="saving" class="btn btn-action-add waves-effect waves-light px-4 py-2 shadow-sm" style="border-radius: 7px;">
                  <i v-if="saving" class="mdi mdi-spin mdi-loading me-1"></i>
                  {{ saving ? 'Menyimpan...' : 'Simpan Data' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Import Modal -->
    <div v-if="importModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px);">
      <div class="modal-dialog modal-dialog-centered" :class="modalSize">
        <div class="modal-content border-0 shadow-lg rounded-4">
          <div class="modal-header border-bottom-0 pt-4 px-4 pb-2">
            <h5 class="modal-title fw-bold fs-4 text-dark">Import {{ singular }}</h5>
            <button type="button" class="btn-close shadow-none" @click="importModal = false" aria-label="Close"></button>
          </div>
          <div class="modal-body px-4 pb-4 pt-2" style="overflow: visible;">
            <form @submit.prevent="saveImport">
                <div class="row g-3">
                  <div class="col-md-12" v-for="f in importFields" :key="f.key">
                    <label class="form-label fw-semibold text-secondary small mb-1">{{ f.label }}</label>
                    
                    <select v-if="f.type === 'select'" v-model="importForm[f.key]" class="form-select bg-light border-0 px-3 py-2 shadow-none rounded-3" :required="f.required">
                      <option value="">-- Pilih --</option>
                      <option v-for="opt in f.options" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                    </select>

                    <Multiselect
                      v-else-if="f.type === 'relation'"
                      :key="f.key + (f.dependsOn ? '_' + importForm[f.dependsOn] : '')"
                      v-model="importForm[f.key]"
                      :options="async (query) => {
                          const api = (await import('../api')).default
                          try {
                            const params = { search: query, per_page: 50 }
                            if (f.dependsOn) params[f.dependsOn] = importForm[f.dependsOn]
                            const { data } = await api.get(f.relationEndpoint, { params })
                            const list = data.data?.data || data.data || []
                            return list.map(item => ({ value: item.id, label: f.optionLabel ? f.optionLabel(item) : (item.name || item.title || item.code || item.employee_code || item.id) }))
                          } catch (e) { return [] }
                      }"
                      :searchable="true"
                      :placeholder="'Pilih ' + f.label"
                      :resolve-on-load="true"
                      :delay="300"
                      class="rounded-3 border-0 bg-light shadow-none custom-multiselect"
                    />
                  </div>
                  
                  <div class="col-md-12">
                    <label class="form-label fw-semibold text-secondary small mb-1">File Excel (.xlsx) <span class="text-danger">*</span></label>
                    <input type="file" @change="handleImportFileUpload" class="form-control bg-light border-0 px-3 py-2 shadow-none rounded-3" accept=".xlsx, .xls" required />
                  </div>
                </div>

              <div class="text-end mt-4 pt-3 border-top">
                <button type="button" @click="importModal = false" class="btn btn-light me-2 waves-effect fw-medium px-4 rounded-3">Batal</button>
                <button type="submit" :disabled="saving" class="btn btn-warning waves-effect waves-light fw-medium px-4 rounded-3 shadow-sm">
                  <i v-if="saving" class="mdi mdi-spin mdi-loading me-1"></i>
                  {{ saving ? 'Mengimpor...' : 'Import Data' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Export Modal -->
    <div v-if="exportModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px); z-index: 1055;">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
          
          <!-- Modal Header -->
          <div class="modal-header border-bottom px-4 pt-4 pb-3" style="background: #f8fafc;">
            <div class="d-flex align-items-center gap-3">
              <div class="rounded-3 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; background-color: #ecfdf5; color: #10b981;">
                <i class="mdi mdi-file-excel-box font-size-24"></i>
              </div>
              <div>
                <h5 class="modal-title fw-bold text-dark mb-0 font-size-17">Pilih Kolom Export Excel</h5>
                <p class="text-muted small mb-0 font-size-12">Tentukan field / kolom data yang ingin disertakan ke dalam file Excel</p>
              </div>
            </div>
            <button type="button" class="btn-close shadow-none" @click="exportModal = false" aria-label="Close"></button>
          </div>

          <!-- Modal Body -->
          <div class="modal-body px-4 py-3" style="max-height: 65vh; overflow-y: auto;">
            
            <!-- Quick Selection Toolbar -->
            <div class="d-flex align-items-center justify-content-between p-2.5 rounded-3 mb-3 border" style="background-color: #f1f5f9;">
              <div class="d-flex align-items-center gap-2">
                <button type="button" @click="selectAllExportFields" class="btn btn-sm btn-white border bg-white shadow-none font-size-12 fw-semibold px-2.5 py-1">
                  <i class="mdi mdi-checkbox-marked-outline text-success me-1"></i> Pilih Semua
                </button>
                <button type="button" @click="deselectAllExportFields" class="btn btn-sm btn-white border bg-white shadow-none font-size-12 fw-semibold px-2.5 py-1">
                  <i class="mdi mdi-checkbox-blank-outline text-danger me-1"></i> Hapus Semua
                </button>
              </div>
              <div class="font-size-12 fw-bold" :class="selectedExportKeys.length > 0 ? 'text-primary' : 'text-danger'">
                Terpilih: {{ selectedExportKeys.length }} dari {{ availableExportFields.length }} kolom
              </div>
            </div>

            <!-- Fields by Category -->
            <div class="d-flex flex-column gap-3">
              <div v-for="(fields, catName) in groupedExportFields" :key="catName" class="border rounded-3 p-3 bg-white">
                <div class="d-flex align-items-center justify-content-between mb-2 pb-1 border-bottom">
                  <div class="fw-bold font-size-13 text-dark text-uppercase tracking-wider">
                    <i class="mdi mdi-folder-outline me-1 text-primary"></i> {{ catName }}
                  </div>
                  <span class="badge bg-light text-secondary border font-size-11">{{ fields.length }} opsi</span>
                </div>
                <div class="row g-2">
                  <div class="col-md-6 col-lg-4" v-for="f in fields" :key="f.key">
                    <label 
                      class="d-flex align-items-center gap-2 p-2 rounded-2 border cursor-pointer transition-all mb-0 w-100 user-select-none"
                      :style="selectedExportKeys.includes(f.key) ? { backgroundColor: '#f0fdfa', borderColor: '#99f6e4' } : { backgroundColor: '#ffffff', borderColor: '#e2e8f0' }"
                    >
                      <input 
                        type="checkbox" 
                        :value="f.key" 
                        v-model="selectedExportKeys" 
                        class="form-check-input mt-0"
                        style="cursor: pointer;"
                      />
                      <span class="font-size-12 fw-medium text-dark text-truncate" :title="f.label">{{ f.label }}</span>
                    </label>
                  </div>
                </div>
              </div>
            </div>

          </div>

          <!-- Modal Footer -->
          <div class="modal-footer border-top px-4 py-3 bg-light d-flex align-items-center justify-content-between">
            <span class="text-muted font-size-12">
              <i class="mdi mdi-filter-variant me-1"></i> Filter aktif pada tabel akan otomatis diterapkan
            </span>
            <div class="d-flex align-items-center gap-2">
              <button type="button" @click="exportModal = false" class="btn btn-light px-3 py-2 fw-semibold rounded-3 font-size-13">
                Batal
              </button>
              <button 
                type="button" 
                @click="runExport" 
                :disabled="selectedExportKeys.length === 0 || exporting" 
                class="btn btn-success px-4 py-2 fw-bold rounded-3 shadow-sm font-size-13 d-flex align-items-center gap-1.5"
              >
                <span v-if="exporting" class="spinner-border spinner-border-sm me-1" role="status"></span>
                <i v-else class="mdi mdi-download me-1"></i>
                <span>{{ exporting ? 'Menyiapkan Excel...' : 'Download File Excel' }}</span>
              </button>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted, computed } from 'vue'
import { toast } from 'vue3-toastify'
import Multiselect from '@vueform/multiselect'
import '@vueform/multiselect/themes/default.css'

const props = defineProps({
  title: String,
  singular: String,
  endpoint: String,
  columns: { type: Array, default: () => [] },
  formFields: { type: Array, default: () => [] },
  filters: { type: Array, default: () => [] },
  creatable: { type: Boolean, default: true },
  exportable: { type: Boolean, default: true },
  exportFilename: { type: String, default: '' },
  exportFields: { type: Array, default: () => [] },
  importable: { type: Boolean, default: true },
  disableActions: { type: Boolean, default: false },
  hideHeader: { type: Boolean, default: false },
  hideCard: { type: Boolean, default: false },
  hideControls: { type: Boolean, default: false },
  modalSize: { type: String, default: 'modal-lg' },
  baseFilters: { type: Object, default: () => ({}) },
  initialForm: { type: Function, default: () => ({}) },
  mapDataToForm: { type: Function, default: (row) => ({ ...row }) },
  transformPayload: { type: Function, default: (form) => form },
  defaultPerPage: { type: Number, default: 10 },
  tableClass: { type: String, default: 'table-sm font-size-13' },
})

const emit = defineEmits(['loaded', 'formChange'])

const getLabel = (f, formObj = form.value) => {
  if (!f) return ''
  const lbl = typeof f.label === 'function' ? f.label(formObj) : f.label
  return String(lbl || '')
}

const rows = ref([])
const perPage = ref(props.defaultPerPage)
const filterValues = ref({})
const meta = ref(null)
const page = ref(1)
const searchQuery = ref('')
const loading = ref(false)
const saving = ref(false)
const error = ref('')
const modal = ref(false)
const editingId = ref(null)
const form = ref({})
const filterOptions = ref({})

watch(form, (newVal) => {
  if (modal.value) {
    emit('formChange', newVal)
  }
}, { deep: true })

const sortCol = ref('')
const sortDir = ref('asc')

const importModal = ref(false)
const importForm = ref({})
const importFile = ref(null)

const importFields = computed(() => {
  return props.formFields.filter(f => f.type === 'relation' || f.type === 'select')
})

const openImport = () => {
  importForm.value = {}
  importFile.value = null
  importModal.value = true
}

const handleImportFileUpload = (e) => {
  if (e.target.files && e.target.files.length > 0) {
    importFile.value = e.target.files[0]
  } else {
    importFile.value = null
  }
}

const saveImport = async () => {
  if (!importFile.value) {
    toast.error('Pilih file Excel terlebih dahulu')
    return
  }
  saving.value = true
  error.value = ''
  const api = (await import('../api')).default
  try {
    const formData = new FormData()
    formData.append('file', importFile.value)
    Object.keys(importForm.value).forEach(k => {
      if (importForm.value[k]) formData.append(k, importForm.value[k])
    })
    await api.post(`${props.endpoint}/import`, formData, { headers: { 'Content-Type': 'multipart/form-data' } })
    toast.success('Import berhasil')
    importModal.value = false
    load()
  } catch (e) {
    const msg = e.response?.data?.message || 'Gagal import'
    error.value = msg
    toast.error(msg)
  } finally {
    saving.value = false
  }
}

const toggleSort = (key) => {
  if (sortCol.value === key) {
    sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortCol.value = key
    sortDir.value = 'asc'
  }
  doSearch()
}

const formatMoney = (val) => {
  if (!val) return ''
  return Number(val).toLocaleString('id-ID')
}

const doSearch = () => {
  page.value = 1
  load()
}

const getFilterLabel = (key) => {
  const f = props.filters.find(x => x.key === key)
  return f ? f.label.replace('Filter ', '') : 'sebelumnya'
}

const fetchFilterRelationOptions = async (field) => {
  if (!field.relationEndpoint) return []
  if (field.dependsOnFilter && !filterValues.value[field.dependsOnFilter]) return []
  
  const api = (await import('../api')).default
  try {
    const params = { per_page: 1000 }
    if (field.dependsOnFilter) params[field.dependsOnFilter] = filterValues.value[field.dependsOnFilter]
    
    // Only include active ancestor filters if strictCascade is explicitly enabled on the field
    if (field.strictCascade) {
      if (filterValues.value.company_id && field.key !== 'company_id') {
        params.company_id = filterValues.value.company_id
      }
      if (filterValues.value.branch_id && field.key !== 'company_id' && field.key !== 'branch_id') {
        params.branch_id = filterValues.value.branch_id
      }
      if (filterValues.value.department_id && (field.key === 'division_id' || field.key === 'position_id')) {
        params.department_id = filterValues.value.department_id
      }
      if (filterValues.value.division_id && field.key === 'position_id') {
        params.division_id = filterValues.value.division_id
      }
    }
    
    const { data } = await api.get(field.relationEndpoint, { params })
    const list = data.data?.data || data.data || []
    return list.map(item => {
      let label = ''
      if (field.optionLabel) {
        label = field.optionLabel(item)
      } else if (field.relationEndpoint === '/branches' || field.key === 'branch_id') {
        const comp = item.company?.name || item.company?.legal_name || ''
        label = comp ? `${item.name} (${comp})` : (item.name || item.id)
      } else if (field.relationEndpoint === '/departments' || field.key === 'department_id') {
        label = item.name || item.id
      } else if (field.relationEndpoint === '/divisions' || field.key === 'division_id') {
        label = item.name || item.id
      } else if (field.relationEndpoint === '/positions' || field.key === 'position_id') {
        label = item.name || item.id
      } else {
        label = item.name || item.title || item.code || item.employee_code || item.id
      }
      return {
        value: item.id,
        label
      }
    })
  } catch (e) {
    return []
  }
}

const handleFilterChange = (changedFilter) => {
  // Cascading reset and refetch
  let dependents = props.filters.filter(x => x.dependsOnFilter === changedFilter.key)
  while (dependents.length > 0) {
    const nextDeps = []
    for (const d of dependents) {
      filterValues.value[d.key] = "" // Reset dependent filter value
      // Only fetch options if direct parent has a selected value
      if (filterValues.value[d.dependsOnFilter]) {
        fetchFilterRelationOptions(d).then(opts => {
          filterOptions.value[d.key] = opts
        })
      } else {
        filterOptions.value[d.key] = []
      }
      nextDeps.push(...props.filters.filter(x => x.dependsOnFilter === d.key))
    }
    dependents = nextDeps
  }
  
  doSearch()
}

const load = async () => {
  loading.value = true
  error.value = ''
  try {
    const api = (await import('../api')).default
    const params = { page: page.value, search: searchQuery.value, per_page: perPage.value, ...filterValues.value, ...props.baseFilters }
    if (sortCol.value) {
      params.sort_by = sortCol.value
      params.sort_dir = sortDir.value
    }
    const { data } = await api.get(props.endpoint, { params })
    rows.value = data.data?.data ?? data.data?.items ?? data.data ?? []
    meta.value = data.data?.meta ?? data.data?.pagination ?? data.meta ?? null
    emit('loaded', rows.value, data)
  } catch (e) {
    error.value = e.response?.data?.message || 'Gagal memuat data'
  } finally { loading.value = false }
}

const exportModal = ref(false)
const exporting = ref(false)
const selectedExportKeys = ref([])

const availableExportFields = computed(() => {
  if (props.exportFields && props.exportFields.length > 0) {
    return props.exportFields
  }
  return props.columns
    .filter(col => col.type !== 'image' && col.type !== 'action' && col.key !== 'actions')
    .map(col => ({
      key: col.sub ? `${col.key}.${col.sub}` : col.key,
      label: col.label,
      category: 'Data Tabel',
      default: true
    }))
})

const groupedExportFields = computed(() => {
  const groups = {}
  for (const f of availableExportFields.value) {
    const cat = f.category || 'Data Utama'
    if (!groups[cat]) groups[cat] = []
    groups[cat].push(f)
  }
  return groups
})

const openExportModal = () => {
  if (selectedExportKeys.value.length === 0) {
    const defaults = availableExportFields.value
      .filter(f => f.default !== false)
      .map(f => f.key)
    selectedExportKeys.value = defaults.length > 0 ? defaults : availableExportFields.value.map(f => f.key)
  }
  exportModal.value = true
}

const selectAllExportFields = () => {
  selectedExportKeys.value = availableExportFields.value.map(f => f.key)
}

const deselectAllExportFields = () => {
  selectedExportKeys.value = []
}

const runExport = async () => {
  if (selectedExportKeys.value.length === 0) {
    toast.error('Pilih minimal 1 kolom data untuk diekspor.')
    return
  }

  exporting.value = true
  const api = (await import('../api')).default
  try {
    toast.info('Menyiapkan file Excel...')
    
    // Prepare column mapping: key -> label
    const exportColumns = {}
    selectedExportKeys.value.forEach(key => {
      const field = availableExportFields.value.find(f => f.key === key)
      if (field) {
        exportColumns[key] = field.label
      }
    })

    const params = { 
      search: searchQuery.value, 
      ...filterValues.value, 
      ...props.baseFilters, 
      export: 1,
      export_columns: JSON.stringify(exportColumns)
    }
    
    const response = await api.get(props.endpoint, { params, responseType: 'blob' })
    
    // Detect content type to use correct extension
    const contentType = response.headers['content-type'] || ''
    const isCsv = contentType.includes('csv') || contentType.includes('text/')
    const ext = isCsv ? 'csv' : 'xlsx'
    
    // Use custom filename if provided, else fallback
    const filename = props.exportFilename
      ? `${props.exportFilename}.${ext}`
      : `${props.singular || 'Export'}_${new Date().toISOString().slice(0, 10)}.${ext}`
    
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', filename)
    document.body.appendChild(link)
    link.click()
    link.parentNode.removeChild(link)
    window.URL.revokeObjectURL(url)
    toast.success('Download Excel berhasil')
    exportModal.value = false
  } catch (e) {
    console.error('Export error', e)
    toast.error('Gagal mengekspor data')
  } finally {
    exporting.value = false
  }
}

const exportData = openExportModal

const cell = (row, col) => {
  const val = col.sub ? (row[col.key]?.[col.sub]) : row[col.key]
  if (col.format) return col.format(val, row)
  
  if (typeof val === 'string' && /^\d{4}-\d{2}-\d{2}(T\d{2}:\d{2}:\d{2}(\.\d{3})?Z?)?$/.test(val)) {
    const d = new Date(val)
    if (!isNaN(d.getTime())) {
      const pad = n => n.toString().padStart(2, '0')
      const m = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des']
      return `${pad(d.getDate())}-${m[d.getMonth()]}-${d.getFullYear()}`
    }
  }

  return val ?? '-'
}

const openCreate = () => {
  editingId.value = null
  form.value = props.initialForm()
  modal.value = true
}
const openEdit = (row) => {
  editingId.value = row.id
  form.value = props.mapDataToForm(row)
  modal.value = true
}

const handleFileUpload = (key, event) => {
  if (event.target.files && event.target.files.length > 0) {
    form.value[key] = event.target.files[0]
  } else {
    delete form.value[key]
  }
}

const fetchRelationOptions = async (field, query) => {
  if (!field.relationEndpoint) return []
  if (field.dependsOn && !form.value[field.dependsOn]) return []
  const api = (await import('../api')).default
  try {
    const params = { search: query, per_page: 1000 }
    if (field.dependsOn) params[field.dependsOn] = form.value[field.dependsOn]
    const { data } = await api.get(field.relationEndpoint, { params })
    const list = data.data?.data || data.data || []
    return list.map(item => {
      let label = ''
      if (field.optionLabel) {
        label = field.optionLabel(item)
      } else if (field.relationEndpoint === '/branches' || field.key === 'branch_id') {
        const comp = item.company?.name || item.company?.legal_name || ''
        label = comp ? `${item.name} (${comp})` : (item.name || item.id)
      } else if (field.relationEndpoint === '/departments' || field.key === 'department_id') {
        label = item.name || item.id
      } else if (field.relationEndpoint === '/divisions' || field.key === 'division_id') {
        label = item.name || item.id
      } else if (field.relationEndpoint === '/positions' || field.key === 'position_id') {
        label = item.name || item.id
      } else {
        label = item.name || item.title || item.code || item.employee_code || item.id
      }
      return {
        value: item.id,
        label
      }
    })
  } catch (e) {
    return []
  }
}

const save = async () => {
  saving.value = true
  error.value = ''
  const api = (await import('../api')).default
  try {
    let isFormData = false
    const formData = new FormData()
    
    // Transform payload first
    const payloadData = props.transformPayload({ ...form.value })
    
    Object.keys(payloadData).forEach(key => {
      if (payloadData[key] instanceof File) {
        isFormData = true
      }
      if (payloadData[key] !== null && payloadData[key] !== undefined && payloadData[key] !== '') {
        let val = payloadData[key]
        if (typeof val === 'boolean') {
          val = val ? '1' : '0'
        } else if (typeof val === 'object' && !(val instanceof File)) {
          val = JSON.stringify(val)
        }
        formData.append(key, val)
      }
    })

    // If there are complex objects (like items) and NO files, send as JSON.
    const hasComplexObjects = Object.values(payloadData).some(val => typeof val === 'object' && val !== null && !(val instanceof File))
    
    if (editingId.value) {
      if (isFormData) {
        formData.append('_method', 'PUT')
        await api.post(`${props.endpoint}/${editingId.value}`, formData, { headers: { 'Content-Type': 'multipart/form-data' } })
      } else {
        await api.put(`${props.endpoint}/${editingId.value}`, payloadData)
      }
    } else {
      if (isFormData) {
        await api.post(props.endpoint, formData, { headers: { 'Content-Type': 'multipart/form-data' } })
      } else {
        await api.post(props.endpoint, payloadData)
      }
    }
    modal.value = false
    toast.success('Data berhasil disimpan')
    load()
  } catch (e) {
    const msg = e.response?.data?.message || e.response?.data?.errors ? Object.values(e.response.data.errors)[0][0] : 'Gagal menyimpan'
    error.value = msg
    toast.error(msg)
  } finally { saving.value = false }
}

const remove = async (row) => {
  if (!confirm(`Hapus ${props.singular} "${row.name || row.code || row.id}"?`)) return
  try {
    const api = (await import('../api')).default
    await api.delete(`${props.endpoint}/${row.id}`)
    toast.success('Data berhasil dihapus')
    load()
  } catch (e) { 
    const msg = e.response?.data?.message || 'Gagal menghapus'
    error.value = msg
    toast.error(msg)
  }
}

watch(page, load)
watch(() => props.baseFilters, () => {
  page.value = 1
  load()
}, { deep: true })
watch(() => form.value, (newVal) => {
  emit('formChange', newVal)
}, { deep: true })
onMounted(() => {
  for (const f of props.filters) {
    if (filterValues.value[f.key] === undefined) {
      filterValues.value[f.key] = f.default !== undefined ? f.default : ""
    }
    if (f.relationEndpoint && (!f.dependsOnFilter || filterValues.value[f.dependsOnFilter])) {
      fetchFilterRelationOptions(f).then(opts => {
        filterOptions.value[f.key] = opts
      })
    }
  }
  load()
})

defineExpose({ load, doSearch })
</script>

<style scoped>
.custom-multiselect {
  --ms-bg: #f8f9fa;
  --ms-border-color: transparent;
  --ms-radius: 0.5rem;
  --ms-font-size: 1rem;
  --ms-line-height: 1.5;
  --ms-py: 0.5rem;
  --ms-px: 0.75rem;
  --ms-ring-width: 0px;
  --ms-placeholder-color: #6c757d;
  --ms-dropdown-bg: #ffffff;
  --ms-dropdown-border-color: #e9ecef;
  --ms-dropdown-radius: 0.5rem;
  --ms-option-bg-pointed: #f1f5f9;
  --ms-option-color-pointed: #1e293b;
  --ms-option-bg-selected: #3b82f6;
  --ms-option-color-selected: #ffffff;
}

.modal.fade.show {
  animation: fadeIn 0.2s ease-out;
}

.modal-content {
  animation: slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes slideUp {
  from { opacity: 0; transform: translateY(20px) scale(0.98); }
  to { opacity: 1; transform: translateY(0) scale(1); }
}

:deep(thead th) {
  background: var(--bs-primary, #124d32) !important;
  color: white !important;
  border-bottom: 0 !important;
}

:deep(thead th[style*="sticky"]) {
  background: var(--bs-primary, #124d32) !important;
  color: white !important;
}
</style>
