<template>
  <CrudPage
    title="Pengumuman (Whistleblower)"
    singular="Pengumuman"
    endpoint="/announcements"
    :columns="[
      { key: 'created_at', label: 'Tanggal' },
      { key: 'title', label: 'Judul' },
      { key: 'body', label: 'Isi' },
      { key: 'attachment_type', label: 'Lampiran' },
    ]"
    :form-fields="[
      { key: 'title', label: 'Judul Pengumuman', required: true },
      { key: 'body', label: 'Isi Pesan (HTML)' },
      { key: 'attachment', label: 'Lampiran', type: 'file' }
    ]"
    modalSize="modal-lg"
  >
    <template #cell-created_at="{ row }">
      {{ formatDate(row.created_at) }}
    </template>
    
    <template #cell-body="{ row }">
      <div v-html="row.body || '-'" class="text-truncate" style="max-width: 250px;"></div>
    </template>
    
    <template #cell-attachment_type="{ row }">
      <div v-if="row.attachment_type === 'pdf'">
        <a :href="row.attachment_url" target="_blank" class="badge bg-soft-danger text-danger text-decoration-none">Buka PDF</a>
      </div>
      <div v-else-if="row.attachment_type === 'image'">
        <a :href="row.attachment_url" target="_blank">
          <img :src="row.attachment_url" alt="Lampiran" class="img-thumbnail" style="max-height: 60px;" />
        </a>
      </div>
      <span v-else>-</span>
    </template>

    <template #form="{ form, handleFileUpload }">
      <div class="row g-3">
        <div class="col-md-12">
          <label class="form-label fw-semibold text-secondary small mb-1">Judul Pengumuman <span class="text-danger">*</span></label>
          <input type="text" v-model="form.title" required class="form-control bg-light border-0 px-3 py-2 shadow-none rounded-3" placeholder="Masukkan judul pengumuman" />
        </div>
        
        <div class="col-md-12">
          <label class="form-label fw-semibold text-secondary small mb-1">Isi Pesan (HTML)</label>
          <div style="height: 250px; margin-bottom: 50px;">
            <QuillEditor theme="snow" v-model:content="form.body" contentType="html" />
          </div>
        </div>
        
        <div class="col-md-12">
          <label class="form-label fw-semibold text-secondary small mb-1">Lampiran (Opsional, PDF/Gambar)</label>
          <input type="file" @change="e => handleFileUpload('attachment', e)" class="form-control bg-light border-0 px-3 py-2 shadow-none rounded-3" accept=".pdf,image/*" />
        </div>
      </div>
    </template>
  </CrudPage>
</template>

<script setup>
import CrudPage from '../components/CrudPage.vue'
import { QuillEditor } from '@vueup/vue-quill'
import '@vueup/vue-quill/dist/vue-quill.snow.css'

const formatDate = (dateStr) => {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('id-ID', {
    day: '2-digit', month: 'short', year: 'numeric',
    hour: '2-digit', minute: '2-digit'
  })
}
</script>
