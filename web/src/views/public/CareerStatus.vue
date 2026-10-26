<template>
  <div class="max-w-3xl mx-auto px-4 py-10">
    <h1 class="text-2xl font-bold text-slate-800">Cek Status Lamaran</h1>
    <p class="text-slate-500 mt-1">Masukkan NIK Anda untuk melihat status lamaran.</p>

    <div v-if="error" class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-3 mt-4">{{ error }}</div>

    <div class="bg-white border rounded-xl p-6 shadow-sm mt-6 flex gap-3">
      <div class="flex-1">
        <label class="block text-sm font-medium text-slate-600 mb-1">Nomor Induk Kependudukan (NIK)</label>
        <input v-model="candidateNik" type="number" placeholder="Masukkan NIK 16 digit" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" />
        <p class="text-xs text-slate-400 mt-1">Gunakan NIK yang Anda daftarkan saat melamar.</p>
      </div>
      <div class="self-end">
        <button @click="check" :disabled="checking" class="px-6 py-2 bg-[#1e2a3a] hover:bg-[#2a3b4f] text-white rounded-lg text-sm font-medium disabled:opacity-50">
          {{ checking ? 'Memeriksa...' : 'Cek' }}
        </button>
      </div>
    </div>

    <!-- Hasil -->
    <div v-if="c" class="mt-6 space-y-5">
      <div class="bg-white border rounded-xl p-6 shadow-sm">
        <div class="flex items-center gap-4">
          <img v-if="c.photo" :src="c.photo" class="w-16 h-16 rounded-full object-cover" />
          <div v-else class="w-16 h-16 rounded-full bg-slate-200 flex items-center justify-center text-slate-500 text-xl">{{ initials(c.name) }}</div>
          <div class="flex-1">
            <h2 class="text-lg font-semibold text-slate-800">{{ c.name }}</h2>
            <p class="text-sm text-slate-500">{{ c.email }} · {{ c.phone || '-' }}</p>
          </div>
          <div>
            <span class="px-3 py-1.5 text-sm rounded-full font-semibold" :class="statusClass(c.status)">{{ statusLabel(c.status) }}</span>
          </div>
        </div>
      </div>

      <div class="bg-white border rounded-xl shadow-sm overflow-hidden">
        <div class="px-5 py-3 border-b font-semibold text-slate-800 text-sm">Lamaran Saya</div>
        <div v-for="app in c.applications" :key="app.id" class="px-5 py-4 border-b last:border-b-0">
          <div class="flex items-center justify-between">
            <div>
              <p class="font-medium text-slate-800">{{ app.job?.title || 'Lowongan' }}</p>
              <p class="text-xs text-slate-400">Diajukan: {{ fmtDate(app.applied_at) }} · {{ app.job?.code }}</p>
            </div>
            <span class="px-2 py-1 text-xs rounded-full font-semibold" :class="statusClass(app.status)">{{ statusLabel(app.status) }}</span>
          </div>
          <p v-if="app.notes" class="text-xs text-slate-500 mt-2">{{ app.notes }}</p>

          <!-- Tests -->
          <div v-if="app.job?.tests?.length && (app.status === 'test_invited' || app.status === 'test_done' || app.attempts?.length)" class="mt-4 bg-slate-50 rounded-lg p-3 border">
             <div class="text-xs font-semibold text-slate-700 mb-2">Daftar Test</div>
             <div v-for="test in app.job?.tests" :key="test.id" class="flex items-center justify-between mb-2 last:mb-0">
               <div>
                  <div class="text-sm font-medium">{{ test.name }}</div>
                  <div class="text-xs text-slate-500">Durasi: {{ test.duration_minutes }} menit</div>
               </div>
               <div>
                 <router-link v-if="app.status === 'test_invited' && !app.attempts?.find(a => a.test_id === test.id)" :to="{ path: `/karier/tests/${test.id}`, query: { candidate_id: c.id } }" class="px-3 py-1 bg-indigo-600 text-white text-xs rounded-lg hover:bg-indigo-700">Mulai Test</router-link>
                 <span v-else-if="app.attempts?.find(a => a.test_id === test.id)" class="px-3 py-1 bg-slate-200 text-slate-600 text-xs rounded-lg">Selesai (Skor: {{ app.attempts.find(a => a.test_id === test.id).score }})</span>
               </div>
             </div>
          </div>

          <!-- Interviews -->
          <div v-if="app.interviews?.length" class="mt-4 bg-slate-50 rounded-lg p-3 border">
             <div class="text-xs font-semibold text-slate-700 mb-2">Jadwal Interview</div>
             <div v-for="intv in app.interviews" :key="intv.id" class="mb-2 last:mb-0 border-b last:border-0 pb-2 last:pb-0">
               <div class="text-sm font-medium">{{ intv.type === 'online' ? 'Interview Online' : 'Interview Offline' }}</div>
               <div class="text-xs text-slate-500">Jadwal: {{ fmtDate(intv.scheduled_at) }} ({{ new Date(intv.scheduled_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute:'2-digit' }) }})</div>
               <div v-if="intv.location_or_link" class="text-xs text-slate-500 mt-1">
                 <a v-if="intv.type === 'online'" :href="intv.location_or_link" target="_blank" class="text-blue-600 hover:underline">Link Meeting</a>
                 <span v-else>Lokasi: {{ intv.location_or_link }}</span>
               </div>
             </div>
          </div>
        </div>
      </div>

      <div class="bg-white border rounded-xl shadow-sm overflow-hidden">
        <div class="px-5 py-3 border-b font-semibold text-slate-800 text-sm">Dokumen</div>
        <div v-for="doc in c.documents" :key="doc.id" class="px-5 py-3 border-b last:border-b-0 flex items-center justify-between">
          <div>
            <p class="text-sm text-slate-700">{{ doc.original_name || doc.document_type }}</p>
            <p class="text-xs text-slate-400">{{ doc.document_type }}</p>
          </div>
          <span class="text-xs px-2 py-0.5 rounded-full" :class="doc.is_verified ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700'">
            {{ doc.is_verified ? 'Terverifikasi' : 'Menunggu' }}
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import api from '../../api'

const candidateNik = ref('')
const checking = ref(false)
const error = ref('')
const c = ref(null)

const statusLabel = (s) => ({
  applied: 'Applied', document_review: 'Review Dokumen', test_invited: 'Diundang Test',
  test_done: 'Test Selesai', interview_invited: 'Diundang Interview', interview_done: 'Interview Selesai',
  shortlisted: 'Shortlisted', offered: 'Offered', hired: 'Hired', rejected: 'Rejected', on_hold: 'On Hold',
}[s] || s)

const statusClass = (s) => ({
  applied: 'bg-slate-100 text-slate-600', document_review: 'bg-blue-100 text-blue-700',
  test_invited: 'bg-indigo-100 text-indigo-700', test_done: 'bg-teal-100 text-teal-700',
  interview_invited: 'bg-purple-100 text-purple-700', interview_done: 'bg-fuchsia-100 text-fuchsia-700',
  shortlisted: 'bg-cyan-100 text-cyan-700', offered: 'bg-yellow-100 text-yellow-700',
  hired: 'bg-green-100 text-green-700', rejected: 'bg-red-100 text-red-700', on_hold: 'bg-orange-100 text-orange-700',
}[s] || 'bg-slate-100 text-slate-600')

const initials = (name) => (name || '?').split(' ').slice(0, 2).map(w => w[0]).join('').toUpperCase()
const fmtDate = (d) => d ? new Date(d).toLocaleDateString('id-ID', { timeZone: 'Asia/Jakarta' }) : '-'

const check = async () => {
  if (!candidateNik.value) { error.value = 'Masukkan NIK dulu'; return }
  checking.value = true; error.value = ''
  try {
    const { data } = await api.get(`/recruitment/status/${candidateNik.value}`)
    c.value = data.data
  } catch (e) { c.value = null; error.value = e.response?.data?.message || 'Kandidat tidak ditemukan' }
  finally { checking.value = false }
}
</script>
