<template>
  <div class="max-w-6xl mx-auto px-4 py-10">
    <button @click="$router.back()" class="text-sm text-blue-600 hover:text-blue-800 mb-4">← Kembali</button>

    <div v-if="loading" class="text-center text-slate-400 py-16">Memuat...</div>
    <div v-else-if="!job" class="text-center text-slate-400 py-16">Lowongan tidak ditemukan.</div>

    <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Main -->
      <div class="lg:col-span-2 space-y-5">
        <div class="bg-white border rounded-xl p-6 shadow-sm">
          <div class="flex items-start justify-between gap-4">
            <div>
              <h1 class="text-2xl font-bold text-slate-800">{{ job.title }}</h1>
              <p class="text-slate-500 mt-1">
                <span v-if="job.department" class="capitalize">{{ job.department.name }}</span>
                <span v-if="job.position" class="capitalize"> · {{ job.position.name }}</span>
                <span v-if="job.company" class="capitalize"> · {{ job.company.name }}</span>
              </p>
            </div>
            <span v-if="job.status === 'open'" class="px-3 py-1 text-xs rounded-full bg-green-100 text-green-700 font-semibold shrink-0">Dibuka</span>
          </div>
          <div class="mt-4 flex flex-wrap gap-3 text-sm text-slate-600">
            <span class="px-3 py-1 bg-slate-100 rounded-lg">{{ job.vacancy_quota || 1 }} posisi</span>
            <span v-if="job.salary_min != null" class="px-3 py-1 bg-slate-100 rounded-lg">{{ fmtSalary(job) }}</span>
            <span class="px-3 py-1 bg-slate-100 rounded-lg">Deadline: {{ job.application_deadline || 'Buka terus' }}</span>
          </div>
        </div>

        <div class="bg-white border rounded-xl p-6 shadow-sm">
          <h2 class="font-semibold text-slate-800 mb-3">Deskripsi Pekerjaan</h2>
          <p class="text-slate-600 whitespace-pre-line">{{ job.description || 'Belum ada deskripsi.' }}</p>
        </div>

        <div class="bg-white border rounded-xl p-6 shadow-sm">
          <h2 class="font-semibold text-slate-800 mb-3">Persyaratan</h2>
          <p class="text-slate-600 whitespace-pre-line">{{ job.requirements || 'Belum ada persyaratan.' }}</p>
        </div>
      </div>

      <!-- Sidebar CTA -->
      <div class="space-y-4">
        <div class="bg-[#1e2a3a] text-white rounded-xl p-6 shadow-sm">
          <h3 class="font-semibold mb-3">Tertarik?</h3>
          <p class="text-white/70 text-sm mb-4">Ajukan lamaran Anda sekarang dan mulailah karier bersama kami.</p>
          <button v-if="job.status === 'open'" @click="$router.push({ path: '/karier/apply', query: { job_id: job.id } })"
            class="w-full bg-[#4cc2ff] hover:bg-[#3db2ef] text-[#1e2a3a] font-semibold text-sm px-4 py-2.5 rounded-lg transition">
            Lamar Sekarang
          </button>
          <button v-else disabled class="w-full bg-white/10 text-white/50 font-semibold text-sm px-4 py-2.5 rounded-lg cursor-not-allowed">
            Lowongan Ditutup
          </button>
        </div>

        <div class="bg-white border rounded-xl p-6 shadow-sm">
          <h3 class="font-semibold text-slate-800 text-sm mb-3">Dokumen yang Dibutuhkan</h3>
          <ul class="text-sm text-slate-600 space-y-2">
            <li>• CV / Resume</li>
            <li>• Kartu Keluarga (KK)</li>
            <li>• KTP</li>
            <li>• Pas Foto</li>
            <li>• SKCK (jika ada)</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import api from '../../api'

const route = useRoute()
const job = ref(null)
const loading = ref(true)

const fmtSalary = (j) => {
  const f = (n) => n == null ? '' : new Intl.NumberFormat('id-ID').format(n)
  if (j.salary_min != null && j.salary_max != null) return `Rp ${f(j.salary_min)} - ${f(j.salary_max)}`
  return `Rp ${f(j.salary_min ?? j.salary_max)}`
}

onMounted(async () => {
  try {
    const { data } = await api.get(`/recruitment/jobs/${route.params.id}`)
    job.value = data.data
  } catch (e) { /* not found */ }
  finally { loading.value = false }
})
</script>
