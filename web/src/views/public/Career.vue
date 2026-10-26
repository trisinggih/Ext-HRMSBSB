<template>
  <div class="min-h-screen bg-[#f8fafc] font-['Inter']">
    
    <!-- Hero Section (Aligned with company_profile) -->
    <section class="relative w-full overflow-hidden bg-gradient-to-b from-white via-slate-50 to-slate-100/70 pb-12 pt-6 sm:pt-10 border-b border-slate-200">
      <!-- Atmospheric Glow -->
      <div class="absolute -top-32 left-1/4 w-96 h-96 rounded-full bg-teal-500/10 blur-3xl pointer-events-none"></div>
      <div class="absolute top-1/2 -right-24 w-[30rem] h-[30rem] rounded-full bg-cyan-500/10 blur-[120px] pointer-events-none"></div>

      <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="flex flex-col items-center text-center gap-6 py-8">
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-teal-50 border border-teal-200 w-fit">
            <span class="font-mono text-[11px] text-[#008779] tracking-widest uppercase font-bold">
              JOIN OUR TEAM & IMPACT THE FUTURE
            </span>
          </div>

          <div class="flex flex-col gap-3 max-w-3xl">
            <h1 class="tracking-tight uppercase font-extrabold text-4xl sm:text-5xl lg:text-6xl leading-tight text-slate-900">
              BUILD YOUR CAREER <br />
              <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#124d32] via-emerald-700 to-green-600">
                WITH BSB VILLAGE
              </span>
            </h1>
            <p class="text-slate-600 leading-relaxed text-base sm:text-lg">
              {{ heroTagline }} Temukan posisi yang sesuai dengan passion Anda dan jadilah bagian dari pengembangan kawasan hunian dan real estate terdepan di BSB Village.
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- Main Content -->
    <div class="max-w-6xl mx-auto px-4 py-12 relative z-10">
      <!-- Search Bar -->
      <div class="bg-white/80 backdrop-blur-xl border border-slate-200 rounded-2xl p-4 mb-10 shadow-[0_8px_30px_rgb(0,0,0,0.04)] flex flex-wrap gap-3">
        <div class="relative flex-1 min-w-[250px]">
          <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
            <i class="mdi mdi-magnify text-slate-400 text-xl"></i>
          </div>
          <input v-model="search" @input="debouncedLoad" placeholder="Cari posisi atau kata kunci lowongan..."
            class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-11 pr-4 py-3 text-sm text-slate-700 focus:outline-none focus:border-[#008779] focus:ring-1 focus:ring-[#008779] transition-shadow" />
        </div>
        <button @click="showTracker = true" class="px-6 py-3 bg-teal-50 text-[#008779] hover:bg-teal-100 border border-teal-200 rounded-xl text-sm font-bold tracking-wide transition-colors flex items-center gap-2 shrink-0">
          <i class="mdi mdi-radar text-lg"></i> Cek Status Lamaran
        </button>
      </div>

      <div v-if="error" class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl p-4 mb-6 flex items-center gap-2">
        <i class="mdi mdi-alert-circle outline"></i> {{ error }}
      </div>

      <!-- Jobs grid -->
      <div v-if="loading" class="flex flex-col items-center justify-center py-20">
        <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-[#008779] mb-4"></div>
        <p class="text-slate-500 font-medium tracking-wide">Memuat lowongan...</p>
      </div>
      
      <div v-else-if="!jobs.length" class="text-center bg-white border border-slate-200 rounded-2xl py-20 px-4 shadow-sm">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-100 mb-4">
          <i class="mdi mdi-briefcase-outline text-2xl text-slate-400"></i>
        </div>
        <h3 class="text-lg font-bold text-slate-800 mb-1">Belum ada lowongan</h3>
        <p class="text-slate-500">Tidak ada lowongan yang sedang dibuka saat ini atau pencarian tidak ditemukan.</p>
      </div>
      
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div v-for="j in jobs" :key="j.id" class="group bg-white border border-slate-200 rounded-2xl p-6 shadow-sm hover:shadow-[0_20px_40px_-15px_rgba(0,135,121,0.15)] hover:border-teal-200 transition-all duration-300 cursor-pointer flex flex-col h-full relative overflow-hidden"
          @click="$router.push(`/karier/jobs/${j.id}`)">
          
          <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-[#008779] to-teal-400 transform origin-left scale-x-0 group-hover:scale-x-100 transition-transform duration-300"></div>

          <div class="flex items-start justify-between mb-4">
            <h3 class="font-bold text-lg text-slate-800 group-hover:text-[#008779] transition-colors line-clamp-2 pr-2">{{ j.title }}</h3>
            <span v-if="j.status === 'open'" class="px-2.5 py-1 text-[10px] uppercase tracking-wider rounded-md bg-emerald-50 text-emerald-600 font-bold border border-emerald-100 shrink-0">Dibuka</span>
          </div>
          
          <div class="flex flex-wrap gap-2 mb-4">
            <span v-if="j.department" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-slate-50 text-slate-600 text-xs font-medium border border-slate-100 capitalize">
              <i class="mdi mdi-domain"></i> {{ j.department.name }}
            </span>
            <span v-if="j.position" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-slate-50 text-slate-600 text-xs font-medium border border-slate-100 capitalize">
              <i class="mdi mdi-badge-account-outline"></i> {{ j.position.name }}
            </span>
          </div>
          
          <p class="text-sm text-slate-500 mb-6 line-clamp-3 leading-relaxed flex-grow">{{ j.description || 'Lihat detail lowongan untuk informasi lebih lanjut mengenai posisi ini.' }}</p>
          
          <div class="mt-auto pt-4 border-t border-slate-100 space-y-3">
            <div class="flex items-center justify-between text-xs font-medium text-slate-500">
              <span class="flex items-center gap-1"><i class="mdi mdi-account-multiple-outline text-sm"></i> {{ j.vacancy_quota || 1 }} Posisi</span>
              <span v-if="j.salary_min != null" class="flex items-center gap-1 text-[#008779]"><i class="mdi mdi-cash text-sm"></i> {{ fmtSalary(j) }}</span>
            </div>
            <div class="flex justify-between items-center text-xs">
              <span class="text-slate-400 flex items-center gap-1"><i class="mdi mdi-clock-outline"></i> {{ j.application_deadline ? `S.d ${j.application_deadline}` : 'Buka terus' }}</span>
              <span class="text-[#008779] font-bold group-hover:translate-x-1 transition-transform flex items-center gap-1">Lamar <i class="mdi mdi-arrow-right"></i></span>
            </div>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="meta && meta.last_page > 1" class="flex items-center justify-between mt-10 p-4 bg-white border border-slate-200 rounded-xl shadow-sm text-sm text-slate-600 font-medium">
        <span>Halaman <span class="text-slate-800 font-bold">{{ meta.current_page }}</span> dari {{ meta.last_page }}</span>
        <div class="flex gap-2">
          <button :disabled="meta.current_page <= 1" @click="page--" class="px-4 py-2 border border-slate-200 rounded-lg hover:bg-slate-50 disabled:opacity-40 disabled:hover:bg-transparent transition-colors flex items-center gap-1">
            <i class="mdi mdi-chevron-left text-lg leading-none"></i> Prev
          </button>
          <button :disabled="meta.current_page >= meta.last_page" @click="page++" class="px-4 py-2 border border-slate-200 rounded-lg hover:bg-slate-50 disabled:opacity-40 disabled:hover:bg-transparent transition-colors flex items-center gap-1">
            Next <i class="mdi mdi-chevron-right text-lg leading-none"></i>
          </button>
        </div>
      </div>
    </div>

    <!-- Tracker Modal -->
    <div v-if="showTracker" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-50 flex items-center justify-center p-4" @click.self="closeTracker">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-hidden flex flex-col max-h-[90vh]">
        <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
          <div>
            <h3 class="text-lg font-bold text-slate-800">Cek Status Lamaran</h3>
            <p class="text-xs text-slate-500 mt-1">Masukkan Nomor Induk Kependudukan (NIK) Anda</p>
          </div>
          <button @click="closeTracker" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition-colors">
            <i class="mdi mdi-close"></i>
          </button>
        </div>
        
        <div class="p-6 overflow-y-auto">
          <form @submit.prevent="checkStatus" class="flex gap-2 mb-6" v-if="!trackResult">
            <input v-model="trackId" type="text" required placeholder="Masukkan NIK 16 digit" class="flex-1 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-[#008779] focus:ring-1 focus:ring-[#008779]" />
            <button type="submit" :disabled="trackLoading" class="px-5 py-3 bg-[#008779] hover:bg-teal-700 text-white font-medium rounded-xl text-sm transition-colors disabled:opacity-70 flex items-center gap-2">
              <i v-if="trackLoading" class="mdi mdi-loading mdi-spin"></i>
              <span v-else>Cek</span>
            </button>
          </form>

          <div v-if="trackError" class="bg-red-50 border border-red-100 text-red-600 text-sm rounded-xl p-4 mb-4 flex items-start gap-2">
            <i class="mdi mdi-alert-circle text-lg shrink-0 mt-0.5"></i>
            <p>{{ trackError }}</p>
          </div>

          <div v-if="trackResult" class="space-y-5">
            <div class="flex justify-between items-start border-b border-slate-100 pb-4">
              <div>
                <h4 class="font-bold text-slate-800">{{ trackResult.name }}</h4>
                <p class="text-sm text-slate-500">{{ trackResult.email }}</p>
              </div>
              <span class="px-2.5 py-1 text-xs font-bold rounded-lg border uppercase tracking-wide" :class="statusClass(trackResult.status)">
                {{ statusLabel(trackResult.status) }}
              </span>
            </div>
            
            <div>
              <h5 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Riwayat Lamaran</h5>
              <div v-if="!trackResult.applications?.length" class="text-sm text-slate-500 italic">Belum ada lamaran.</div>
              <div class="space-y-3">
                <div v-for="app in trackResult.applications" :key="app.id" class="p-4 bg-slate-50 rounded-xl border border-slate-100">
                  <div class="flex justify-between items-start mb-2">
                    <p class="font-semibold text-slate-700">{{ app.job?.title || 'Lowongan' }}</p>
                    <span class="px-2 py-0.5 text-[10px] rounded border uppercase font-bold" :class="statusClass(app.status)">
                      {{ statusLabel(app.status) }}
                    </span>
                  </div>
                  <p class="text-xs text-slate-500">Tanggal: {{ app.applied_date || app.applied_at?.substring(0,10) || '-' }}</p>
                  
                  <div v-if="app.interviews?.length" class="mt-3 pt-3 border-t border-slate-200">
                    <p class="text-[10px] font-bold text-slate-400 uppercase mb-2">Jadwal Interview</p>
                    <div v-for="itv in app.interviews" :key="itv.id" class="text-xs flex items-start gap-2 mb-1.5">
                      <i class="mdi mdi-calendar-clock text-teal-600"></i>
                      <div>
                        <p class="font-medium text-slate-700">{{ new Date(itv.scheduled_at).toLocaleString('id-ID') }}</p>
                        <p class="text-slate-500">{{ itv.mode === 'online' ? 'Online: ' + itv.meeting_link : 'Offline: ' + itv.location }}</p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <button @click="trackResult = null; trackId = ''" class="w-full py-2.5 text-sm font-medium text-slate-500 hover:text-slate-700 hover:bg-slate-50 rounded-lg transition-colors border border-transparent hover:border-slate-200">
              Cek Nomor Lain
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../../api'

const jobs = ref([])
const meta = ref(null)
const page = ref(1)
const loading = ref(false)
const error = ref('')
const search = ref('')
const heroTagline = ref('Kami mencari talenta terbaik untuk bersama tumbuh dan berinovasi.')

// Tracker State
const showTracker = ref(false)
const trackId = ref('')
const trackLoading = ref(false)
const trackError = ref('')
const trackResult = ref(null)

let timer = null
const debouncedLoad = () => { clearTimeout(timer); timer = setTimeout(() => { page.value = 1; load() }, 400) }

const fmtSalary = (j) => {
  const f = (n) => n == null ? '' : new Intl.NumberFormat('id-ID').format(n)
  if (j.salary_min != null && j.salary_max != null) return `Rp ${f(j.salary_min)} - ${f(j.salary_max)}`
  return `Rp ${f(j.salary_min ?? j.salary_max)}`
}

const statusLabel = (s) => ({
  applied: 'Applied', document_review: 'Review Dokumen', test_invited: 'Diundang Test',
  test_done: 'Test Selesai', interview_invited: 'Diundang Interview', interview_done: 'Interview Selesai',
  shortlisted: 'Shortlisted', offered: 'Offered', hired: 'Hired', rejected: 'Rejected', on_hold: 'On Hold',
}[s] || s)

const statusClass = (s) => ({
  applied: 'bg-slate-100 text-slate-600 border-slate-200', document_review: 'bg-blue-50 text-blue-600 border-blue-200',
  test_invited: 'bg-indigo-50 text-indigo-600 border-indigo-200', test_done: 'bg-teal-50 text-teal-600 border-teal-200',
  interview_invited: 'bg-purple-50 text-purple-600 border-purple-200', interview_done: 'bg-fuchsia-50 text-fuchsia-600 border-fuchsia-200',
  shortlisted: 'bg-cyan-50 text-cyan-600 border-cyan-200', offered: 'bg-yellow-50 text-yellow-600 border-yellow-200',
  hired: 'bg-emerald-50 text-emerald-600 border-emerald-200', rejected: 'bg-red-50 text-red-600 border-red-200', on_hold: 'bg-orange-50 text-orange-600 border-orange-200',
}[s] || 'bg-slate-100 text-slate-600 border-slate-200')

const load = async () => {
  loading.value = true; error.value = ''
  try {
    const { data } = await api.get('/recruitment/jobs', { params: { page: page.value, search: search.value } })
    jobs.value = data.data?.data ?? data.data ?? []
    meta.value = data.data?.meta ?? data.meta ?? null
  } catch (e) { error.value = e.response?.data?.message || 'Gagal memuat lowongan' }
  finally { loading.value = false }
}

const loadSite = async () => {
  try {
    const { data } = await api.get('/cms/site')
    const settings = data.data?.settings || {}
    if (settings.hero_tagline) heroTagline.value = settings.hero_tagline
  } catch (e) { /* default */ }
}

const closeTracker = () => {
  showTracker.value = false
  trackId.value = ''
  trackError.value = ''
  trackResult.value = null
}

const checkStatus = async () => {
  if (!trackId.value) return
  trackLoading.value = true
  trackError.value = ''
  trackResult.value = null
  try {
    const { data } = await api.get(`/recruitment/status/${trackId.value}`)
    trackResult.value = data.data
  } catch (e) {
    trackError.value = e.response?.status === 404 ? 'Kandidat tidak ditemukan dengan nomor tersebut.' : (e.response?.data?.message || 'Gagal mengecek status')
  } finally {
    trackLoading.value = false
  }
}

onMounted(() => { load(); loadSite() })
</script>
