<template>
  <div class="max-w-3xl mx-auto px-4 py-10">
    <button @click="$router.back()" class="text-sm text-blue-600 hover:text-blue-800 mb-4">← Kembali</button>
    <h1 class="text-2xl font-bold text-slate-800">Ajukan Lamaran</h1>
    <p class="text-slate-500 mt-1">Isi data diri dan lampirkan dokumen pendukung Anda.</p>

    <div v-if="success" class="mt-6 bg-green-50 border border-green-200 text-green-700 rounded-xl p-6 text-center">
      <p class="text-xl mb-2">✅</p>
      <p class="font-semibold">Lamaran Anda berhasil dikirim!</p>
      <p class="text-sm mt-2">Cek status lamaran menggunakan NIK Anda di halaman <router-link to="/karir/status" class="text-green-800 underline">Cek Status</router-link>.</p>
    </div>

    <template v-else>
      <div v-if="error" class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-3 my-4">{{ error }}</div>

      <div class="bg-white border rounded-xl p-6 shadow-sm mt-6 space-y-5">
        <div>
          <label class="block text-sm font-medium text-slate-600 mb-1">Lowongan *</label>
          <select v-model="form.job_id" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
            <option value="">-- Pilih lowongan --</option>
            <option v-for="j in jobs" :key="j.id" :value="j.id">{{ j.title }}</option>
          </select>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div><label class="block text-sm font-medium text-slate-600 mb-1">Nama Lengkap *</label>
            <input v-model="form.name" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" /></div>
          <div><label class="block text-sm font-medium text-slate-600 mb-1">Email *</label>
            <input v-model="form.email" type="email" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" /></div>
          <div><label class="block text-sm font-medium text-slate-600 mb-1">No. HP</label>
            <input v-model="form.phone" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" /></div>
          <div><label class="block text-sm font-medium text-slate-600 mb-1">Tanggal Lahir</label>
            <input v-model="form.birth_date" type="date" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" /></div>
          <div><label class="block text-sm font-medium text-slate-600 mb-1">Jenis Kelamin</label>
            <select v-model="form.gender" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
              <option value="">-- Pilih --</option>
              <option value="male">Laki-laki</option>
              <option value="female">Perempuan</option>
            </select></div>
          <div><label class="block text-sm font-medium text-slate-600 mb-1">No. KTP / NIK *</label>
            <input v-model="form.ktp_number" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" /></div>
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-600 mb-1">Alamat</label>
          <textarea v-model="form.address" rows="2" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"></textarea>
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-600 mb-1">Sumber Info (opsional)</label>
          <input v-model="form.source" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" placeholder="LinkedIn, Jobstreet, Instagram..." />
        </div>
      </div>

      <!-- Dokumen -->
      <div class="bg-white border rounded-xl p-6 shadow-sm mt-5">
        <h2 class="font-semibold text-slate-800 mb-3">Dokumen Pendukung</h2>
        <p class="text-sm text-slate-500 mb-4">Format: PDF, JPG, PNG, DOC/DOCX (maks 4MB per file).</p>

        <div class="space-y-3">
          <div v-for="(d, i) in form.documents" :key="i" class="flex items-center gap-3 border border-slate-200 rounded-lg p-2">
            <select v-model="d.type" class="border border-slate-300 rounded-lg px-2 py-1.5 text-sm w-36">
              <option value="cv">CV</option>
              <option value="kk">KK</option>
              <option value="ktp">KTP</option>
              <option value="foto">Foto</option>
              <option value="skck">SKCK</option>
              <option value="ijazah">Ijazah</option>
              <option value="lainnya">Lainnya</option>
            </select>
            <input type="file" class="text-sm flex-1" @change="onFile($event, i)" />
            <span v-if="d.file" class="text-xs text-green-700">✔ {{ d.file.name }}</span>
            <button @click="removeDoc(i)" class="text-red-500 text-sm">✕</button>
          </div>
        </div>
        <button @click="addDoc" class="mt-3 text-sm text-blue-600 hover:text-blue-800">+ Tambah dokumen</button>
      </div>

      <div class="flex justify-end gap-3 mt-6">
        <button @click="$router.back()" class="px-4 py-2 border rounded-lg text-sm text-slate-600">Batal</button>
        <button @click="submit" :disabled="submitting" class="px-6 py-2.5 bg-[#1e2a3a] hover:bg-[#2a3b4f] text-white rounded-lg text-sm font-medium disabled:opacity-50">
          {{ submitting ? 'Mengirim...' : 'Kirim Lamaran' }}
        </button>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import api from '../../api'

const route = useRoute()
const jobs = ref([])
const submitting = ref(false)
const error = ref('')
const success = ref(false)
const candidateId = ref('')

const form = ref({ job_id: '', name: '', email: '', phone: '', birth_date: '', gender: '', ktp_number: '', address: '', source: '', documents: [] })

const addDoc = () => form.value.documents.push({ type: 'cv', file: null })
const removeDoc = (i) => form.value.documents.splice(i, 1)
const onFile = (e, i) => { form.value.documents[i].file = e.target.files[0] }

onMounted(async () => {
  if (route.query.job_id) form.value.job_id = route.query.job_id
  // jika format array multi, handle
  // query bisa berupa string
  try {
    const { data } = await api.get('/recruitment/jobs', { params: { per_page: 50 } })
    jobs.value = data.data?.data ?? data.data ?? []
  } catch (e) { /* non-fatal */ }
})

const submit = async () => {
  submitting.value = true; error.value = ''
  const fd = new FormData()
  fd.append('name', form.value.name)
  fd.append('email', form.value.email)
  fd.append('job_id', form.value.job_id)
  if (form.value.phone) fd.append('phone', form.value.phone)
  if (form.value.birth_date) fd.append('birth_date', form.value.birth_date)
  if (form.value.gender) fd.append('gender', form.value.gender)
  if (form.value.ktp_number) fd.append('ktp_number', form.value.ktp_number)
  if (form.value.address) fd.append('address', form.value.address)
  if (form.value.source) fd.append('source', form.value.source)

  form.value.documents.filter(d => d.file).forEach((d, i) => {
    fd.append(`documents[${i}][document_type]`, d.type)
    fd.append(`documents[${i}][file]`, d.file)
  })

  try {
    const { data } = await api.post('/recruitment/apply', fd)
    success.value = true
    candidateId.value = data.data?.candidate?.id || ''
    // simpan email + id kandidat utk cek status
    localStorage.setItem('karir_email', form.value.email)
    if (data.data?.candidate?.id) localStorage.setItem('karir_candidate', data.data.candidate.id)
  } catch (e) {
    const errs = e.response?.data?.errors
    error.value = errs ? Object.values(errs)[0][0] : (e.response?.data?.message || 'Gagal mengirim lamaran')
  } finally { submitting.value = false }
}
</script>
