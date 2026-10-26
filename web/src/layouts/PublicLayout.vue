<template>
  <div class="min-h-screen bg-[#f8fafc] text-slate-900 flex flex-col selection:bg-[#124d32] selection:text-white font-['Inter']">
    <!-- Top Telemetry & Certification Ticker -->
    <section id="telemetry-ticker" class="w-full bg-slate-100 text-[#124d32] py-2 px-4 sm:px-6 lg:px-8 border-b border-slate-200">
      <div class="max-w-7xl mx-auto flex flex-wrap items-center justify-between gap-3 font-mono text-[11px] uppercase tracking-wider">
        <div class="flex items-center gap-3">
          <span class="inline-flex items-center gap-1.5 text-[#124d32] font-bold">
            <span class="w-2 h-2 rounded-full bg-[#124d32] animate-pulse"></span>
            BSB VILLAGE REAL ESTATE & TOWNSHIP
          </span>
          <span class="text-slate-300">/</span>
          <span class="text-slate-600 font-semibold">MODERN & GREEN LIVING ENVIRONMENT</span>
        </div>
        <div class="hidden sm:flex items-center gap-6 text-slate-600">
          <span>SEMARANG, JAWA TENGAH</span>
          <span class="text-[#124d32] font-bold">BSB VILLAGE HR & CAREER</span>
        </div>
      </div>
    </section>

    <!-- Main Sticky Header -->
    <header :class="['fixed top-0 left-0 w-full z-50 transition-all duration-300', isScrolled ? 'bg-[#124d32]/95 backdrop-blur-xl border-b border-[#0c3823] shadow-sm py-2' : 'bg-[#124d32]/90 backdrop-blur-xl border-b border-[#0c3823]/80 shadow-sm py-3']">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between gap-4">
        <!-- Brand Logo -->
        <a href="/" class="flex items-center gap-3 group" id="brand-logo-link">
          <div class="relative flex items-center justify-center">
            <div class="flex flex-col">
              <span class="font-headline-sm tracking-tight text-white transition-colors font-bold text-xl leading-none">
                <img :src="companyStore.logoLightUrl" :alt="brandName" style="max-height: 38px; object-fit: contain;" />
              </span>
            </div>
          </div>
        </a>

        <!-- Desktop Navigation -->
        <nav class="hidden lg:flex items-center gap-6" id="desktop-nav">
          <router-link to="/karier" class="font-body-sm text-[12px] uppercase tracking-wider transition-colors font-bold text-yellow-300 underline underline-offset-8 decoration-2 decoration-yellow-300">Karier</router-link>
          <router-link to="/hrms/login" class="font-body-sm text-[12px] uppercase tracking-wider transition-colors font-semibold text-slate-100 hover:text-white">Portal HRMS</router-link>
          <router-link to="/login" class="font-body-sm text-[12px] uppercase tracking-wider transition-colors font-semibold text-slate-100 hover:text-white">Portal Karyawan</router-link>
        </nav>

        <!-- Header Right Actions -->
        <div class="flex items-center gap-3">
          <a class="hidden sm:inline-flex items-center gap-1.5 px-4 py-2 rounded-full bg-white hover:bg-slate-100 text-[#124d32] font-tech-data text-xs shadow-[0_2px_12px_rgba(255,255,255,0.1)] hover:shadow-[0_4px_16px_rgba(255,255,255,0.2)] transition-all duration-200" href="https://wa.me/628112700000?text=Halo%20BSB%20Village,%20saya%20ingin%20informasi%20karir%20dan%20rekrutmen" target="_blank" rel="noopener noreferrer">
            <img src="/whatsapp.svg" alt="WhatsApp" class="w-[18px] h-[18px]" />
            <span>KONTAK KAMI</span>
          </a>

          <!-- Mobile menu button -->
          <button type="button" @click="mobileMenuOpen = !mobileMenuOpen" class="lg:hidden p-2 rounded-lg text-white hover:text-slate-100 hover:bg-[#0c3823] transition-colors" aria-label="Buka Menu">
            <span class="material-symbols-outlined text-[26px]">
              {{ mobileMenuOpen ? 'close' : 'menu' }}
            </span>
          </button>
        </div>
      </div>

      <!-- Mobile Menu Dropdown -->
      <div v-if="mobileMenuOpen" class="lg:hidden bg-[#124d32]/98 backdrop-blur-xl border-b border-[#0c3823] px-4 pt-3 pb-6 shadow-xl animate-in slide-in-from-top-2 duration-200">
        <div class="flex flex-col space-y-3">
          <router-link to="/karier" @click="mobileMenuOpen = false" class="font-tech-data text-sm uppercase py-2 px-3 rounded-lg transition-colors bg-white text-[#124d32] font-bold shadow-sm">Karier & Lowongan</router-link>
          <router-link to="/hrms/login" @click="mobileMenuOpen = false" class="font-tech-data text-sm uppercase py-2 px-3 rounded-lg transition-colors text-slate-100 hover:bg-[#0c3823] hover:text-white font-semibold">Portal HRMS</router-link>
          <router-link to="/login" @click="mobileMenuOpen = false" class="font-tech-data text-sm uppercase py-2 px-3 rounded-lg transition-colors text-slate-100 hover:bg-[#0c3823] hover:text-white font-semibold">Portal Karyawan</router-link>
          <div class="pt-2 border-t border-[#0c3823] flex flex-col gap-2">
            <a class="inline-flex items-center justify-center gap-2 w-full py-2.5 rounded-full bg-white text-[#124d32] font-tech-data text-xs shadow-sm hover:bg-slate-100 transition-colors" href="https://wa.me/628112700000?text=Halo%20BSB%20Village,%20saya%20ingin%20informasi%20karir%20dan%20rekrutmen" target="_blank" rel="noopener noreferrer">
              <span class="material-symbols-outlined text-[18px]">chat</span>
              <span>Hubungi BSB Village via WhatsApp</span>
            </a>
          </div>
        </div>
      </div>
    </header>

    <main class="flex-1 flex flex-col pt-16">
      <router-view />
    </main>

    <footer class="w-full bg-[#0a2618] text-white pt-16 pb-12 border-t border-[#124d32]/40 mt-auto">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-10 pb-12 border-b border-emerald-950">
          
          <!-- Brand & Address Column -->
          <div class="lg:col-span-5 flex flex-col gap-4">
            <div class="flex items-center gap-3">
              <div class="relative">
                <img
                  :alt="brandName"
                  class="h-10 object-contain"
                  :src="companyStore.logoLightUrl"
                />
              </div>
            </div>

            <p class="text-slate-300 text-xs sm:text-sm leading-relaxed max-w-sm">
              Kawasan hunian mandiri modern dan asri di Semarang Barat. Menghadirkan harmoni lingkungan hijau alami dengan aksesibilitas dan fasilitas terbaik untuk kualitas hidup keluarga.
            </p>

            <div class="space-y-1 text-xs text-slate-400 font-mono pt-2">
              <div class="text-emerald-400 font-bold uppercase tracking-widest">Kantor Pengelola & Pemasaran:</div>
              <div>Jl. Raya Semarang - Boja, Mijen, Kota Semarang, Jawa Tengah 50218</div>
              <div>Hotline / CS: (024) 7667 0555 | info@bsbvillage.com</div>
            </div>
          </div>

          <!-- Navigasi Cepat -->
          <div class="lg:col-span-3 flex flex-col gap-3">
            <span class="text-xs uppercase font-bold text-[#8ea854] tracking-wider">
              Portal & Layanan
            </span>
            <ul class="space-y-2 text-xs text-slate-300">
              <li><router-link to="/karier" class="hover:text-emerald-400 transition-colors">Karir & Lowongan Kerja</router-link></li>
              <li><router-link to="/hrms/login" class="hover:text-emerald-400 transition-colors">Portal HRMS Internal</router-link></li>
              <li><router-link to="/login" class="hover:text-emerald-400 transition-colors">Portal Mandiri Karyawan</router-link></li>
            </ul>
          </div>

          <!-- Informasi Kawasan -->
          <div class="lg:col-span-4 flex flex-col gap-3">
            <span class="text-xs uppercase font-bold text-[#8ea854] tracking-wider">
              Kawasan BSB Village
            </span>
            <p class="text-xs text-slate-400 leading-relaxed">
              Dikembangkan dengan konsep eco-green living yang terpadu dengan pusat bisnis, fasilitas rekreasi keluarga, dan sistem keamanan 24 jam di koridor pengembangan utama Kota Semarang.
            </p>
          </div>
        </div>

        <!-- Copyright & Disclaimer -->
        <div class="pt-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-400">
          <div>
            © {{ new Date().getFullYear() }} BSB Village. PT BSB Village Estate. Hak Cipta Dilindungi Undang-Undang.
          </div>
          <div class="text-center sm:text-right font-mono text-[11px] text-emerald-400">
            Real Estate Management & Township System.
          </div>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, onUnmounted } from 'vue'
import { useRoute } from 'vue-router'
import api from '../api'

import { useCompanyStore } from '../stores/companyStore'
import '../react-app/index.css'

const route = useRoute()
const companyStore = useCompanyStore()
const brandName = computed(() => site.value.settings?.company_name || companyStore.companyName)
const site = ref({ settings: {}, sections: [] })

const isScrolled = ref(false)
const mobileMenuOpen = ref(false)

const handleScroll = () => {
  isScrolled.value = window.scrollY > 20
}

onMounted(async () => {
  window.addEventListener('scroll', handleScroll)
  if (!companyStore.company) {
    companyStore.fetchCompany()
  }
  try {
    const { data } = await api.get('/cms/site')
    site.value = data.data ?? { settings: {}, sections: [] }
  } catch (e) { /* tampil default */ }
})

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll)
})
</script>
