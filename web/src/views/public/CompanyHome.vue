<template>
  <div class="min-h-screen bg-slate-50 text-slate-800 flex flex-col font-['Inter',sans-serif] selection:bg-[#124d32] selection:text-white">
    
    <!-- 1. TOP TELEMETRY / ANNOUNCEMENT BAR -->
    <div class="bg-[#0c3823] text-emerald-100 py-2 px-4 sm:px-6 text-xs border-b border-emerald-900/60">
      <div class="max-w-7xl mx-auto flex flex-wrap items-center justify-between gap-2">
        <div class="flex items-center gap-2">
          <span class="inline-flex items-center gap-1.5 font-semibold text-emerald-300">
            <span class="w-2 h-2 rounded-full bg-[#8ea854] animate-pulse"></span>
            BSB VILLAGE SEMARANG
          </span>
          <span class="hidden sm:inline text-emerald-600">|</span>
          <span class="hidden sm:inline text-emerald-200">Alami, Asri, & Tenang — Green Living in Harmony</span>
        </div>
        <div class="flex items-center gap-4 text-[11px] font-mono">
          <span class="hidden md:inline text-emerald-300/80">Senin - Minggu: 08.30 - 17.00 WIB</span>
          <a
            href="https://wa.me/6281225566675?text=Halo%20Marketing%20BSB%20Village,%20saya%20ingin%20informasi%20perumahan"
            target="_blank"
            rel="noopener noreferrer"
            class="inline-flex items-center gap-1.5 text-white hover:text-emerald-300 transition-colors font-sans font-semibold"
          >
            <i class="mdi mdi-whatsapp text-emerald-400 text-sm"></i>
            <span>Sales: 0812-2556-6675 (Ali Maskun)</span>
          </a>
        </div>
      </div>
    </div>

    <!-- 2. STICKY HEADER & NAVBAR -->
    <header class="sticky top-0 z-50 bg-[#124d32]/95 backdrop-blur-md shadow-md border-b border-emerald-900/40">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between gap-4">
        
        <!-- Logo BSB Village -->
        <a href="#beranda" class="flex items-center gap-3">
          <img
            :src="companyStore.logoLightUrl"
            :alt="companyStore.companyName"
            style="height: 44px; max-height: 44px; width: auto; object-fit: contain;"
          />
        </a>

        <!-- Desktop Navigation -->
        <nav class="hidden lg:flex items-center gap-7 text-sm font-medium text-slate-100">
          <a href="#beranda" class="hover:text-emerald-300 transition-colors">Beranda</a>
          <a href="#tentang" class="hover:text-emerald-300 transition-colors">Tentang Kami</a>
          <a href="#cluster" class="hover:text-emerald-300 transition-colors">Pilihan Cluster</a>
          <a href="#fasilitas" class="hover:text-emerald-300 transition-colors">Fasilitas</a>
          <a href="#partner" class="hover:text-emerald-300 transition-colors">Bank Rekanan</a>
          <a href="#kontak" class="hover:text-emerald-300 transition-colors">Kontak & Lokasi</a>
          <router-link to="/karier" class="hover:text-emerald-300 transition-colors flex items-center gap-1">
            <span>Karier</span>
            <span class="text-[10px] bg-[#8ea854] text-[#0c3823] px-1.5 py-0.5 rounded font-bold uppercase">RMS</span>
          </router-link>
        </nav>

        <!-- Right Action Buttons -->
        <div class="flex items-center gap-3">
          <!-- Tombol Portal HRMS / Karyawan -->
          <div class="hidden sm:flex items-center gap-2">
            <router-link
              to="/hrms/login"
              class="px-3.5 py-1.5 text-xs font-semibold rounded-lg text-emerald-100 hover:text-white hover:bg-emerald-800/60 border border-emerald-700/60 transition-all"
            >
              Portal HRMS
            </router-link>
            <router-link
              to="/login"
              class="px-3.5 py-1.5 text-xs font-semibold rounded-lg text-emerald-100 hover:text-white hover:bg-emerald-800/60 border border-emerald-700/60 transition-all"
            >
              Portal Karyawan
            </router-link>
          </div>

          <!-- Tombol WhatsApp Sales -->
          <a
            href="https://wa.me/6281225566675?text=Halo%20Marketing%20BSB%20Village,%20saya%20ingin%20tanya%20informasi%20hunian%20dan%20pricelist"
            target="_blank"
            rel="noopener noreferrer"
            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full bg-[#8ea854] hover:bg-[#7a9544] text-[#0c3823] font-bold text-xs shadow-md transition-all duration-200"
          >
            <i class="mdi mdi-whatsapp text-base"></i>
            <span>Hubungi Sales</span>
          </a>

          <!-- Mobile Hamburger Button -->
          <button
            type="button"
            @click="mobileNavOpen = !mobileNavOpen"
            class="lg:hidden p-2 rounded-lg text-white hover:bg-emerald-800/60 transition-colors"
            aria-label="Toggle menu"
          >
            <i :class="mobileNavOpen ? 'mdi mdi-close' : 'mdi mdi-menu'" class="text-2xl"></i>
          </button>
        </div>
      </div>

      <!-- Mobile Nav Dropdown -->
      <div v-if="mobileNavOpen" class="lg:hidden bg-[#0c3823] border-b border-emerald-800 px-4 pt-3 pb-6 space-y-3 animate-in slide-in-from-top-2">
        <a href="#beranda" @click="mobileNavOpen = false" class="block py-2 text-slate-100 font-semibold hover:text-emerald-300">Beranda</a>
        <a href="#tentang" @click="mobileNavOpen = false" class="block py-2 text-slate-100 font-semibold hover:text-emerald-300">Tentang Kami</a>
        <a href="#cluster" @click="mobileNavOpen = false" class="block py-2 text-slate-100 font-semibold hover:text-emerald-300">Pilihan Cluster</a>
        <a href="#fasilitas" @click="mobileNavOpen = false" class="block py-2 text-slate-100 font-semibold hover:text-emerald-300">Fasilitas</a>
        <a href="#partner" @click="mobileNavOpen = false" class="block py-2 text-slate-100 font-semibold hover:text-emerald-300">Bank Rekanan</a>
        <a href="#kontak" @click="mobileNavOpen = false" class="block py-2 text-slate-100 font-semibold hover:text-emerald-300">Kontak & Lokasi</a>
        <router-link to="/karier" @click="mobileNavOpen = false" class="block py-2 text-emerald-300 font-semibold">Karier & Rekrutmen</router-link>
        
        <div class="pt-3 border-t border-emerald-800/80 flex flex-col gap-2">
          <router-link to="/hrms/login" class="text-center py-2 rounded-lg bg-emerald-800 text-white font-medium text-xs">
            Portal HRMS Internal
          </router-link>
          <router-link to="/login" class="text-center py-2 rounded-lg bg-emerald-900 text-white font-medium text-xs">
            Portal Mandiri Karyawan
          </router-link>
        </div>
      </div>
    </header>

    <main class="flex-1">
      
      <!-- 3. HERO SECTION (NATURE HOME BACKGROUND WITH ANIMATED SVG & VIDEO TOUR) -->
      <section id="beranda" class="relative overflow-hidden bg-[#0c3823] text-white py-24 lg:py-32">
        
        <!-- Layer 1: Photographic Nature Home Backdrop with Ken Burns Zoom -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
          <img
            src="/assets/images/hero-nature-home.webp"
            alt="BSB Village Nature Home"
            class="w-full h-full object-cover object-center kenburns-bg opacity-45 scale-105"
          />
        </div>

        <!-- Layer 2: Deep Emerald Atmospheric & Nature Light Gradient Overlay -->
        <div class="absolute inset-0 bg-gradient-to-r from-[#072416]/95 via-[#0c3823]/80 to-[#124d32]/50 pointer-events-none"></div>
        <div class="absolute inset-0 bg-gradient-to-t from-[#0c3823] via-transparent to-transparent pointer-events-none"></div>

        <!-- Layer 3: Animated SVG Sunbeams radiating from Top-Right -->
        <div class="absolute -top-10 -right-10 w-[38rem] h-[38rem] pointer-events-none opacity-40 sunbeam-glow">
          <svg viewBox="0 0 500 500" class="w-full h-full fill-none">
            <defs>
              <radialGradient id="sun-glow" cx="80%" cy="20%" r="70%">
                <stop offset="0%" stop-color="#fff8db" stop-opacity="0.8" />
                <stop offset="30%" stop-color="#8ea854" stop-opacity="0.4" />
                <stop offset="70%" stop-color="#124d32" stop-opacity="0" />
              </radialGradient>
            </defs>
            <circle cx="400" cy="100" r="300" fill="url(#sun-glow)" />
            <!-- Radiant light rays -->
            <path d="M400,100 L-100,500 L-50,500 Z" fill="#fff8db" opacity="0.08" />
            <path d="M400,100 L100,600 L180,600 Z" fill="#fff8db" opacity="0.06" />
            <path d="M400,100 L-200,300 L-200,380 Z" fill="#8ea854" opacity="0.07" />
          </svg>
        </div>

        <!-- Layer 4: Floating Organic SVG Leaves (Nature Breeze Effect) -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none z-10">
          <!-- Leaf 1 -->
          <div class="floating-leaf leaf-1">
            <svg width="34" height="34" viewBox="0 0 100 100" class="fill-[#8ea854] drop-shadow-md">
              <path d="M50 0 C65 20, 95 35, 95 65 C95 85, 75 100, 50 100 C25 100, 5 85, 5 65 C5 35, 35 20, 50 0 Z" />
            </svg>
          </div>
          <!-- Leaf 2 -->
          <div class="floating-leaf leaf-2">
            <svg width="26" height="26" viewBox="0 0 100 100" class="fill-[#a3c267] drop-shadow-md">
              <path d="M50 0 C65 20, 95 35, 95 65 C95 85, 75 100, 50 100 C25 100, 5 85, 5 65 C5 35, 35 20, 50 0 Z" />
            </svg>
          </div>
          <!-- Leaf 3 -->
          <div class="floating-leaf leaf-3">
            <svg width="22" height="22" viewBox="0 0 100 100" class="fill-[#7a9544] drop-shadow-md">
              <path d="M50 0 C65 20, 95 35, 95 65 C95 85, 75 100, 50 100 C25 100, 5 85, 5 65 C5 35, 35 20, 50 0 Z" />
            </svg>
          </div>
          <!-- Leaf 4 (Twin blade sprout like logo) -->
          <div class="floating-leaf leaf-4">
            <svg width="36" height="36" viewBox="0 0 100 100" class="drop-shadow-lg">
              <path d="M50,90 Q40,40 10,10 Q35,30 50,75 Z" fill="#8ea854" opacity="0.85" />
              <path d="M50,90 Q60,40 90,10 Q65,30 50,75 Z" fill="#7a9544" opacity="0.9" />
            </svg>
          </div>
          <!-- Leaf 5 -->
          <div class="floating-leaf leaf-5">
            <svg width="28" height="28" viewBox="0 0 100 100" class="fill-[#b5d57f] drop-shadow-md">
              <path d="M50 0 C65 20, 95 35, 95 65 C95 85, 75 100, 50 100 C25 100, 5 85, 5 65 C5 35, 35 20, 50 0 Z" />
            </svg>
          </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-20">
          <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
            
            <div class="lg:col-span-7 space-y-6">
              <!-- Pill badge -->
              <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-emerald-950/80 border border-[#8ea854]/50 text-emerald-200 text-xs font-semibold uppercase tracking-wider backdrop-blur-md shadow-sm">
                <i class="mdi mdi-leaf text-[#8ea854]"></i>
                <span>Bukit Semarang Baru • PT. Hijau Cipta Harmoni</span>
              </div>

              <!-- Main Heading -->
              <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-white leading-tight tracking-tight drop-shadow-md" style="color: #ffffff !important;">
                Alami, Asri, & Tenang <br/>
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-emerald-200 via-[#8ea854] to-yellow-200">
                  Green Living in Harmony
                </span>
              </h1>

              <!-- Description -->
              <p class="text-base sm:text-lg text-emerald-100 leading-relaxed font-normal max-w-2xl drop-shadow-sm">
                Perumahan BSB Village Semarang mempersembahkan kawasan hunian modern bernuansa alam di area perbukitan Semarang Barat. Hamparan panorama hijau membentang dan semburat cahaya mentari pagi menciptakan oase baru yang melebur dalam hunian nyaman untuk kualitas hidup sehat seluruh keluarga.
              </p>

              <!-- CTA Group with Video Tour Button -->
              <div class="pt-2 flex flex-wrap items-center gap-4">
                <a
                  href="https://wa.me/6281225566675?text=Halo%20Marketing%20BSB%20Village,%20saya%20ingin%20konsultasi%20dan%20jadwalkan%20kunjungan%20ke%20lokasi"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="inline-flex items-center gap-2 px-6 py-3.5 rounded-xl bg-[#8ea854] hover:bg-[#7a9544] text-[#0c3823] font-bold text-sm shadow-xl hover:shadow-emerald-900/50 transition-all transform hover:-translate-y-0.5"
                >
                  <i class="mdi mdi-whatsapp text-lg"></i>
                  <span>Hubungi Marketing (0812-2556-6675)</span>
                </a>

                <!-- Video Tour Trigger Button -->
                <button
                  type="button"
                  @click="showVideoModal = true"
                  class="inline-flex items-center gap-2 px-5 py-3.5 rounded-xl bg-white/15 hover:bg-white/25 text-white font-semibold text-sm border border-white/30 backdrop-blur-md shadow-lg transition-all transform hover:-translate-y-0.5 group"
                >
                  <i class="mdi mdi-play-circle text-2xl text-[#8ea854] group-hover:scale-110 transition-transform"></i>
                  <span>Tonton Video Kawasan</span>
                </button>
                
                <a
                  href="#cluster"
                  class="inline-flex items-center gap-2 px-5 py-3.5 rounded-xl bg-black/20 hover:bg-black/30 text-emerald-200 hover:text-white font-semibold text-sm border border-emerald-700/50 backdrop-blur-sm transition-all"
                >
                  <span>Pilihan Cluster</span>
                  <i class="mdi mdi-arrow-down text-base"></i>
                </a>
              </div>

              <!-- Key Trust Badges -->
              <div class="pt-6 border-t border-emerald-800/80 grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
                <div class="p-2.5 rounded-lg bg-black/20 backdrop-blur-xs border border-white/5">
                  <div class="text-[#8ea854] font-bold text-lg">Perbukitan</div>
                  <div class="text-emerald-100/80">Bebas Banjir & Asri</div>
                </div>
                <div class="p-2.5 rounded-lg bg-black/20 backdrop-blur-xs border border-white/5">
                  <div class="text-[#8ea854] font-bold text-lg">One Gate</div>
                  <div class="text-emerald-100/80">Keamanan 24 Jam</div>
                </div>
                <div class="p-2.5 rounded-lg bg-black/20 backdrop-blur-xs border border-white/5">
                  <div class="text-[#8ea854] font-bold text-lg">Rumah Tumbuh</div>
                  <div class="text-emerald-100/80">Desain Fleksibel</div>
                </div>
                <div class="p-2.5 rounded-lg bg-black/20 backdrop-blur-xs border border-white/5">
                  <div class="text-[#8ea854] font-bold text-lg">Investasi</div>
                  <div class="text-emerald-100/80">Nilai Berkembang</div>
                </div>
              </div>
            </div>

            <!-- Hero Feature Visual Card with Ambient Nature Glassmorphism -->
            <div class="lg:col-span-5">
              <div class="relative rounded-2xl bg-gradient-to-b from-[#0a2f1c]/90 to-[#072416]/95 p-6 backdrop-blur-xl border border-emerald-600/40 shadow-2xl space-y-5">
                <div class="flex items-center justify-between pb-3 border-b border-white/10">
                  <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-[#8ea854] animate-ping"></span>
                    <span class="text-xs uppercase font-bold tracking-widest text-emerald-200">Kawasan BSB Village</span>
                  </div>
                  <span class="text-xs text-[#8ea854] font-mono font-bold">Semarang Barat</span>
                </div>

                <div class="space-y-3 text-sm">
                  <div class="p-3.5 rounded-xl bg-white/5 border border-white/10 hover:bg-white/10 transition-colors">
                    <div class="font-bold text-white flex items-center gap-2">
                      <i class="mdi mdi-home-heart text-[#8ea854] text-lg"></i>
                      <span>Pilihan Rumah Tumbuh Idaman</span>
                    </div>
                    <p class="text-xs text-emerald-100/80 mt-1">
                      Dirancang dengan bahan bangunan pilihan bermutu tinggi, siap berkembang mengikuti pertambahan anggota keluarga.
                    </p>
                  </div>

                  <div class="p-3.5 rounded-xl bg-white/5 border border-white/10 hover:bg-white/10 transition-colors">
                    <div class="font-bold text-white flex items-center gap-2">
                      <i class="mdi mdi-tree text-[#8ea854] text-lg"></i>
                      <span>Harmoni Alam & Ruang Terbuka Hijau</span>
                    </div>
                    <p class="text-xs text-emerald-100/80 mt-1">
                      Keseimbangan lingkungan hidup yang sehat dengan udara sejuk pegunungan untuk tumbuh kembang anak yang optimal.
                    </p>
                  </div>

                  <div class="p-3.5 rounded-xl bg-white/5 border border-white/10 hover:bg-white/10 transition-colors">
                    <div class="font-bold text-white flex items-center gap-2">
                      <i class="mdi mdi-shield-check text-[#8ea854] text-lg"></i>
                      <span>Akses Strategis & Legalitas Terjamin</span>
                    </div>
                    <p class="text-xs text-emerald-100/80 mt-1">
                      Dikelola oleh pengembang Bukit Semarang Baru terpercaya dengan kemudahan fasilitas KPR semua bank rekanan.
                    </p>
                  </div>
                </div>

                <!-- Sales Info Banner inside Card -->
                <div class="pt-2 text-center text-xs text-emerald-200">
                  <span class="opacity-80">Konsultasikan kebutuhan hunian Anda bersama Inhouse Sales resmi:</span>
                  <div class="font-bold text-sm text-[#8ea854] mt-0.5">Ali Maskun — 0812-2556-6675</div>
                </div>
              </div>
            </div>

          </div>
        </div>

        <!-- Layer 5: Seamless Organic Mountain / Ridge SVG Wave Divider -->
        <div class="absolute bottom-0 left-0 right-0 overflow-hidden leading-none z-10 pointer-events-none">
          <svg class="relative block w-full h-10 sm:h-14 text-white" viewBox="0 0 1200 120" preserveAspectRatio="none">
            <path d="M0,0 C200,80 400,-20 600,50 C800,120 1000,20 1200,40 L1200,120 L0,120 Z" fill="currentColor"></path>
          </svg>
        </div>
      </section>

      <!-- 4. SECTION TENTANG BSB VILLAGE -->
      <section id="tentang" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div class="text-center max-w-3xl mx-auto space-y-4">
            <span class="text-xs font-bold uppercase tracking-widest text-[#124d32] bg-emerald-50 px-3 py-1 rounded-full border border-emerald-200">
              Tentang Pengembang & Kawasan
            </span>
            <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
              Hunian Modern Berwawasan Lingkungan di Semarang Barat
            </h2>
            <p class="text-slate-600 text-sm sm:text-base leading-relaxed">
              BSB Village merupakan mahakarya perumahan bernuansa asri yang dikembangkan oleh PT. Hijau Cipta Harmoni di koridor Bukit Semarang Baru (BSB). Kami menghadirkan hunian asri berkualitas yang nyaman dihuni dan bernilai investasi tinggi.
            </p>
          </div>

          <div class="mt-14 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            <div class="p-6 rounded-2xl bg-slate-50 border border-slate-200 hover:border-emerald-500 hover:shadow-lg transition-all duration-300">
              <div class="w-12 h-12 rounded-xl bg-[#124d32] text-[#8ea854] flex items-center justify-center text-2xl mb-4">
                <i class="mdi mdi-pine-tree"></i>
              </div>
              <h3 class="font-bold text-slate-900 text-base mb-2">Green Living Concept</h3>
              <p class="text-xs text-slate-600 leading-relaxed">
                Menyatu dengan alam perbukitan, memadukan pepohonan rindang dan sirkulasi udara bersih untuk hidup sehat seimbang.
              </p>
            </div>

            <div class="p-6 rounded-2xl bg-slate-50 border border-slate-200 hover:border-emerald-500 hover:shadow-lg transition-all duration-300">
              <div class="w-12 h-12 rounded-xl bg-[#124d32] text-[#8ea854] flex items-center justify-center text-2xl mb-4">
                <i class="mdi mdi-home-plus"></i>
              </div>
              <h3 class="font-bold text-slate-900 text-base mb-2">Konsep Rumah Tumbuh</h3>
              <p class="text-xs text-slate-600 leading-relaxed">
                Penataan denah fleksibel yang memudahkan renovasi dan penambahan ruang seiring berkembangnya kebutuhan keluarga Anda.
              </p>
            </div>

            <div class="p-6 rounded-2xl bg-slate-50 border border-slate-200 hover:border-emerald-500 hover:shadow-lg transition-all duration-300">
              <div class="w-12 h-12 rounded-xl bg-[#124d32] text-[#8ea854] flex items-center justify-center text-2xl mb-4">
                <i class="mdi mdi-medal"></i>
              </div>
              <h3 class="font-bold text-slate-900 text-base mb-2">Material Berkualitas</h3>
              <p class="text-xs text-slate-600 leading-relaxed">
                Dibangun dengan spesifikasi material terpilih, struktur tahan cuaca, serta pengawasan mutu konstruksi yang ketat.
              </p>
            </div>

            <div class="p-6 rounded-2xl bg-slate-50 border border-slate-200 hover:border-emerald-500 hover:shadow-lg transition-all duration-300">
              <div class="w-12 h-12 rounded-xl bg-[#124d32] text-[#8ea854] flex items-center justify-center text-2xl mb-4">
                <i class="mdi mdi-trending-up"></i>
              </div>
              <h3 class="font-bold text-slate-900 text-base mb-2">Investasi Menjanjikan</h3>
              <p class="text-xs text-slate-600 leading-relaxed">
                Pertumbuhan nilai capital gain yang konsisten seiring perkembangan pesat kawasan kota mandiri Bukit Semarang Baru.
              </p>
            </div>
          </div>
        </div>
      </section>

      <!-- 5. SECTION PILIHAN CLUSTER (INFORMASI RESIDENSIAL - ACUAN BSBVILLAGESEMARANG.COM) -->
      <section id="cluster" class="py-20 bg-slate-100 border-y border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div class="text-center max-w-3xl mx-auto space-y-3">
            <span class="text-xs font-bold uppercase tracking-widest text-[#124d32] bg-emerald-50 px-3 py-1 rounded-full border border-emerald-200">
              Residential Area
            </span>
            <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
              Pilihan Cluster Hunian BSB Village
            </h2>
            <p class="text-slate-600 text-sm leading-relaxed">
              Temukan hunian yang selaras dengan gaya hidup Anda dan keluarga, dirancang dengan estetika modern di lingkungan alami yang tenang.
            </p>
          </div>

          <div class="mt-12 grid grid-cols-1 md:grid-cols-3 gap-8">
            
            <!-- CLUSTER 1: AYRA -->
            <div class="bg-white rounded-2xl overflow-hidden border border-slate-200 shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col">
              <div class="h-48 bg-gradient-to-tr from-[#0c3823] to-[#1b5e3f] p-6 relative flex flex-col justify-between text-white">
                <span class="self-start px-2.5 py-1 rounded-full bg-[#8ea854] text-[#0c3823] text-[11px] font-extrabold uppercase tracking-wider">
                  New Cluster
                </span>
                <div>
                  <h3 class="text-2xl font-bold tracking-tight text-white" style="color: #ffffff !important;">Cluster AYRA</h3>
                  <p class="text-xs text-emerald-200">Modern Contemporary Living</p>
                </div>
              </div>
              <div class="p-6 flex-1 flex flex-col justify-between space-y-5">
                <div class="space-y-3 text-xs text-slate-600">
                  <p class="leading-relaxed">
                    Dirancang dengan konsep hunian kekinian untuk keluarga muda, mengutamakan efisiensi tata ruang, pencahayaan alami, dan sirkulasi udara silang.
                  </p>
                  <ul class="space-y-2 pt-2 border-t border-slate-100">
                    <li class="flex items-center gap-2">
                      <i class="mdi mdi-check-circle text-emerald-600 text-sm"></i>
                      <span>Tata Ruang Kompak & Fungsional</span>
                    </li>
                    <li class="flex items-center gap-2">
                      <i class="mdi mdi-check-circle text-emerald-600 text-sm"></i>
                      <span>2 - 3 Kamar Tidur & Ruang Tamu Lapang</span>
                    </li>
                    <li class="flex items-center gap-2">
                      <i class="mdi mdi-check-circle text-emerald-600 text-sm"></i>
                      <span>Carport Mobil & Taman Depan Asri</span>
                    </li>
                    <li class="flex items-center gap-2">
                      <i class="mdi mdi-check-circle text-emerald-600 text-sm"></i>
                      <span>Sistem Sanitasi & Drainase Terpadu</span>
                    </li>
                  </ul>
                </div>

                <a
                  href="https://wa.me/6281225566675?text=Halo%20Marketing%20BSB%20Village,%20saya%20tertarik%20dengan%20informasi%20Cluster%20AYRA"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="w-full text-center py-2.5 px-4 rounded-xl bg-[#124d32] hover:bg-[#0c3823] text-white font-semibold text-xs transition-colors flex items-center justify-center gap-1.5"
                >
                  <i class="mdi mdi-whatsapp text-sm"></i>
                  <span>Tanya Unit Cluster AYRA</span>
                </a>
              </div>
            </div>

            <!-- CLUSTER 2: LOKKA -->
            <div class="bg-white rounded-2xl overflow-hidden border border-emerald-500/40 shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col relative">
              <div class="h-48 bg-gradient-to-tr from-[#124d32] to-[#26734d] p-6 relative flex flex-col justify-between text-white">
                <span class="self-start px-2.5 py-1 rounded-full bg-emerald-900/80 text-emerald-200 border border-emerald-700 text-[11px] font-extrabold uppercase tracking-wider">
                  Favorite Cluster
                </span>
                <div>
                  <h3 class="text-2xl font-bold tracking-tight text-white" style="color: #ffffff !important;">Cluster Lokka</h3>
                  <p class="text-xs text-emerald-200">Tropical Nature Residence</p>
                </div>
              </div>
              <div class="p-6 flex-1 flex flex-col justify-between space-y-5">
                <div class="space-y-3 text-xs text-slate-600">
                  <p class="leading-relaxed">
                    Menghadirkan harmoni arsitektur tropis dengan halaman terbuka yang lebih luas. Sangat cocok bagi Anda yang menginginkan ketenangan dan relaksasi maksimal di rumah.
                  </p>
                  <ul class="space-y-2 pt-2 border-t border-slate-100">
                    <li class="flex items-center gap-2">
                      <i class="mdi mdi-check-circle text-emerald-600 text-sm"></i>
                      <span>Halaman Hijau Terbuka Lebih Luas</span>
                    </li>
                    <li class="flex items-center gap-2">
                      <i class="mdi mdi-check-circle text-emerald-600 text-sm"></i>
                      <span>Desain High-Ceiling (Plafon Tinggi & Sejuk)</span>
                    </li>
                    <li class="flex items-center gap-2">
                      <i class="mdi mdi-check-circle text-emerald-600 text-sm"></i>
                      <span>Kamar Utama Luas dengan Bukaan Jendela Lebar</span>
                    </li>
                    <li class="flex items-center gap-2">
                      <i class="mdi mdi-check-circle text-emerald-600 text-sm"></i>
                      <span>Lingkungan Tetangga Aman & Homie</span>
                    </li>
                  </ul>
                </div>

                <a
                  href="https://wa.me/6281225566675?text=Halo%20Marketing%20BSB%20Village,%20saya%20tertarik%20dengan%20informasi%20Cluster%20Lokka"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="w-full text-center py-2.5 px-4 rounded-xl bg-[#124d32] hover:bg-[#0c3823] text-white font-semibold text-xs transition-colors flex items-center justify-center gap-1.5"
                >
                  <i class="mdi mdi-whatsapp text-sm"></i>
                  <span>Tanya Unit Cluster Lokka</span>
                </a>
              </div>
            </div>

            <!-- CLUSTER 3: AURORA -->
            <div class="bg-white rounded-2xl overflow-hidden border border-slate-200 shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col">
              <div class="h-48 bg-gradient-to-tr from-[#0a2d1d] to-[#124d32] p-6 relative flex flex-col justify-between text-white">
                <span class="self-start px-2.5 py-1 rounded-full bg-yellow-400 text-[#0c3823] text-[11px] font-extrabold uppercase tracking-wider">
                  Exclusive Unit
                </span>
                <div>
                  <h3 class="text-2xl font-bold tracking-tight text-white" style="color: #ffffff !important;">Cluster Aurora</h3>
                  <p class="text-xs text-emerald-200">Scenic Hillside Living</p>
                </div>
              </div>
              <div class="p-6 flex-1 flex flex-col justify-between space-y-5">
                <div class="space-y-3 text-xs text-slate-600">
                  <p class="leading-relaxed">
                    Hunian prestisius dengan kemewahan view perbukitan asri Semarang Barat. Menawarkan suasana eksklusif dengan privasi tinggi dan pemandangan menawan.
                  </p>
                  <ul class="space-y-2 pt-2 border-t border-slate-100">
                    <li class="flex items-center gap-2">
                      <i class="mdi mdi-check-circle text-emerald-600 text-sm"></i>
                      <span>Fasad Elegan Modern & Megah</span>
                    </li>
                    <li class="flex items-center gap-2">
                      <i class="mdi mdi-check-circle text-emerald-600 text-sm"></i>
                      <span>Panorama View Bukit Asri & Hijau</span>
                    </li>
                    <li class="flex items-center gap-2">
                      <i class="mdi mdi-check-circle text-emerald-600 text-sm"></i>
                      <span>Row Jalan Cluster Lebar & Nyaman</span>
                    </li>
                    <li class="flex items-center gap-2">
                      <i class="mdi mdi-check-circle text-emerald-600 text-sm"></i>
                      <span>Akses Dekat ke Taman Rekreasi Kawasan</span>
                    </li>
                  </ul>
                </div>

                <a
                  href="https://wa.me/6281225566675?text=Halo%20Marketing%20BSB%20Village,%20saya%20tertarik%20dengan%20informasi%20Cluster%20Aurora"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="w-full text-center py-2.5 px-4 rounded-xl bg-[#124d32] hover:bg-[#0c3823] text-white font-semibold text-xs transition-colors flex items-center justify-center gap-1.5"
                >
                  <i class="mdi mdi-whatsapp text-sm"></i>
                  <span>Tanya Unit Cluster Aurora</span>
                </a>
              </div>
            </div>

          </div>
        </div>
      </section>

      <!-- 6. SECTION FASILITAS KAWASAN -->
      <section id="fasilitas" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div class="text-center max-w-3xl mx-auto space-y-3">
            <span class="text-xs font-bold uppercase tracking-widest text-[#124d32] bg-emerald-50 px-3 py-1 rounded-full border border-emerald-200">
              Township Facilities
            </span>
            <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
              Fasilitas Lengkap untuk Kualitas Hidup Terbaik
            </h2>
            <p class="text-slate-600 text-sm leading-relaxed">
              Nikmati kenyamanan hidup modern dengan fasilitas penunjang lengkap yang didesain untuk kemudahan dan kebahagiaan seluruh anggota keluarga.
            </p>
          </div>

          <div class="mt-14 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            
            <div class="p-6 rounded-2xl bg-slate-50 border border-slate-200 flex items-start gap-4">
              <div class="w-12 h-12 rounded-xl bg-emerald-100 text-[#124d32] flex items-center justify-center text-2xl shrink-0">
                <i class="mdi mdi-shield-account"></i>
              </div>
              <div>
                <h4 class="font-bold text-slate-900 text-sm mb-1">One Gate System & Security 24 Jam</h4>
                <p class="text-xs text-slate-600 leading-relaxed">
                  Gerbang masuk cluster terintegrasi pos keamanan dan pantauan CCTV 24 jam untuk ketenangan keluarga.
                </p>
              </div>
            </div>

            <div class="p-6 rounded-2xl bg-slate-50 border border-slate-200 flex items-start gap-4">
              <div class="w-12 h-12 rounded-xl bg-emerald-100 text-[#124d32] flex items-center justify-center text-2xl shrink-0">
                <i class="mdi mdi-tree-outline"></i>
              </div>
              <div>
                <h4 class="font-bold text-slate-900 text-sm mb-1">Taman Tematik & Ruang Terbuka Hijau</h4>
                <p class="text-xs text-slate-600 leading-relaxed">
                  Lanskap asri dengan taman hijau untuk tempat bersantai, refreshing, dan berinteraksi antarwarga.
                </p>
              </div>
            </div>

            <div class="p-6 rounded-2xl bg-slate-50 border border-slate-200 flex items-start gap-4">
              <div class="w-12 h-12 rounded-xl bg-emerald-100 text-[#124d32] flex items-center justify-center text-2xl shrink-0">
                <i class="mdi mdi-run-fast"></i>
              </div>
              <div>
                <h4 class="font-bold text-slate-900 text-sm mb-1">Jogging Track & Pedestrian</h4>
                <p class="text-xs text-slate-600 leading-relaxed">
                  Jalur lari dan jalan santai yang teduh dan aman dari lalu lintas kendaraan untuk olahraga pagi dan sore.
                </p>
              </div>
            </div>

            <div class="p-6 rounded-2xl bg-slate-50 border border-slate-200 flex items-start gap-4">
              <div class="w-12 h-12 rounded-xl bg-emerald-100 text-[#124d32] flex items-center justify-center text-2xl shrink-0">
                <i class="mdi mdi-slide"></i>
              </div>
              <div>
                <h4 class="font-bold text-slate-900 text-sm mb-1">Children Playground</h4>
                <p class="text-xs text-slate-600 leading-relaxed">
                  Area bermain anak yang ramah dan aman, mendukung aktivitas fisik dan sosial buah hati Anda.
                </p>
              </div>
            </div>

            <div class="p-6 rounded-2xl bg-slate-50 border border-slate-200 flex items-start gap-4">
              <div class="w-12 h-12 rounded-xl bg-emerald-100 text-[#124d32] flex items-center justify-center text-2xl shrink-0">
                <i class="mdi mdi-store"></i>
              </div>
              <div>
                <h4 class="font-bold text-slate-900 text-sm mb-1">Commercial Area & Shophouse</h4>
                <p class="text-xs text-slate-600 leading-relaxed">
                  Ruko dan kawasan perniagaan di sekitar perumahan untuk memenuhi kebutuhan belanja sehari-hari.
                </p>
              </div>
            </div>

            <div class="p-6 rounded-2xl bg-slate-50 border border-slate-200 flex items-start gap-4">
              <div class="w-12 h-12 rounded-xl bg-emerald-100 text-[#124d32] flex items-center justify-center text-2xl shrink-0">
                <i class="mdi mdi-road"></i>
              </div>
              <div>
                <h4 class="font-bold text-slate-900 text-sm mb-1">Main Boulevard Lebar & Bebas Banjir</h4>
                <p class="text-xs text-slate-600 leading-relaxed">
                  Jalan boulevard utama dengan aspal mulus, drainase bawah tanah tertutup, serta pencahayaan jalan modern.
                </p>
              </div>
            </div>

          </div>
        </div>
      </section>

      <!-- 7. SECTION BANK REKANAN (PAYMENT PARTNERS) -->
      <section id="partner" class="py-16 bg-slate-50 border-t border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center space-y-6">
          <span class="text-xs font-bold uppercase tracking-widest text-[#124d32] bg-emerald-50 px-3 py-1 rounded-full border border-emerald-200">
            Payment Partner
          </span>
          <h2 class="text-2xl font-extrabold text-slate-900">
            Bekerja Sama dengan Bank Terkemuka
          </h2>
          <p class="text-slate-600 text-xs sm:text-sm max-w-2xl mx-auto leading-relaxed">
            Kemudahan kepemilikan rumah impian melalui program KPR dengan suku bunga kompetitif dan proses pengajuan yang didampingi langsung oleh tim inhouse sales BSB Village.
          </p>

          <div class="pt-4 flex flex-wrap items-center justify-center gap-6 sm:gap-10">
            <div class="px-6 py-3 rounded-xl bg-white border border-slate-200 font-bold text-sm text-slate-700 shadow-sm">
              Bank Mandiri
            </div>
            <div class="px-6 py-3 rounded-xl bg-white border border-slate-200 font-bold text-sm text-slate-700 shadow-sm">
              Bank BCA
            </div>
            <div class="px-6 py-3 rounded-xl bg-white border border-slate-200 font-bold text-sm text-slate-700 shadow-sm">
              Bank BTN
            </div>
            <div class="px-6 py-3 rounded-xl bg-white border border-slate-200 font-bold text-sm text-slate-700 shadow-sm">
              Bank BNI
            </div>
            <div class="px-6 py-3 rounded-xl bg-white border border-slate-200 font-bold text-sm text-slate-700 shadow-sm">
              Bank BRI
            </div>
            <div class="px-6 py-3 rounded-xl bg-white border border-slate-200 font-bold text-sm text-slate-700 shadow-sm">
              Bank Syariah Indonesia (BSI)
            </div>
          </div>
        </div>
      </section>

      <!-- 8. SECTION KONTAK & MARKETING GALLERY -->
      <section id="kontak" class="py-20 bg-gradient-to-br from-[#0c3823] to-[#124d32] text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
            
            <div class="lg:col-span-6 space-y-6">
              <span class="text-xs font-bold uppercase tracking-widest text-[#8ea854] bg-emerald-950 px-3 py-1 rounded-full border border-emerald-800">
                Marketing Gallery
              </span>
              <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-white" style="color: #ffffff !important;">
                Kunjungi Galeri Pemasaran & Lokasi BSB Village
              </h2>
              <p class="text-emerald-100/90 text-sm leading-relaxed">
                Kami mengundang Anda dan keluarga untuk melihat langsung keindahan lingkungan perumahan BSB Village Semarang serta melihat unit rumah contoh. Tim Marketing kami siap mendampingi Anda setiap hari.
              </p>

              <div class="space-y-4 pt-2 text-sm text-emerald-100">
                <div class="flex items-start gap-3">
                  <i class="mdi mdi-map-marker-radius text-[#8ea854] text-xl shrink-0 mt-0.5"></i>
                  <div>
                    <div class="font-bold text-white">Marketing Gallery BSB Village (PT. Hijau Cipta Harmoni)</div>
                    <div class="text-xs text-emerald-200 mt-0.5">
                      Jl. Boulevard Raya BSB Village, Kelurahan Bubakan, Kecamatan Mijen, Kota Semarang, Jawa Tengah 50218
                    </div>
                  </div>
                </div>

                <div class="flex items-start gap-3">
                  <i class="mdi mdi-account-tie text-[#8ea854] text-xl shrink-0 mt-0.5"></i>
                  <div>
                    <div class="font-bold text-white">Inhouse Sales Executive</div>
                    <div class="text-xs text-emerald-200 mt-0.5">
                      Ali Maskun — WhatsApp / Call: 0812-2556-6675
                    </div>
                  </div>
                </div>

                <div class="flex items-start gap-3">
                  <i class="mdi mdi-clock-outline text-[#8ea854] text-xl shrink-0 mt-0.5"></i>
                  <div>
                    <div class="font-bold text-white">Jam Operasional Layanan</div>
                    <div class="text-xs text-emerald-200 mt-0.5">
                      Buka Setiap Hari (Senin s/d Minggu: 08.30 - 17.00 WIB)
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Quick WhatsApp Action Card -->
            <div class="lg:col-span-6">
              <div class="bg-white rounded-2xl p-8 text-slate-800 shadow-2xl space-y-5">
                <div class="border-b border-slate-100 pb-3">
                  <h3 class="font-extrabold text-slate-900 text-lg">Hubungi Sales Resmi</h3>
                  <p class="text-xs text-slate-500 mt-0.5">
                    Dapatkan e-brochure lengkap, pricelist terkini, simulasi angsuran KPR, dan promo unit terbaru.
                  </p>
                </div>

                <div class="space-y-3">
                  <a
                    href="https://wa.me/6281225566675?text=Halo%20Pak%20Ali%20Maskun,%20saya%20tertarik%20dengan%20informasi%20Cluster%20AYRA%20BSB%20Village"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="p-4 rounded-xl border border-slate-200 hover:border-emerald-600 hover:bg-emerald-50/50 transition-all flex items-center justify-between group"
                  >
                    <div>
                      <div class="font-bold text-xs text-slate-900 group-hover:text-emerald-800">Tanya Brosur Cluster AYRA</div>
                      <div class="text-[11px] text-slate-500">Hunian 1 & 2 Lantai Minimalis Modern</div>
                    </div>
                    <i class="mdi mdi-whatsapp text-emerald-600 text-2xl"></i>
                  </a>

                  <a
                    href="https://wa.me/6281225566675?text=Halo%20Pak%20Ali%20Maskun,%20saya%20tertarik%20dengan%20informasi%20Cluster%20Lokka%20BSB%20Village"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="p-4 rounded-xl border border-slate-200 hover:border-emerald-600 hover:bg-emerald-50/50 transition-all flex items-center justify-between group"
                  >
                    <div>
                      <div class="font-bold text-xs text-slate-900 group-hover:text-emerald-800">Tanya Brosur Cluster Lokka</div>
                      <div class="text-[11px] text-slate-500">Hunian Tropis Nyaman dengan Halaman Luas</div>
                    </div>
                    <i class="mdi mdi-whatsapp text-emerald-600 text-2xl"></i>
                  </a>

                  <a
                    href="https://wa.me/6281225566675?text=Halo%20Pak%20Ali%20Maskun,%20saya%20tertarik%20dengan%20informasi%20Cluster%20Aurora%20BSB%20Village"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="p-4 rounded-xl border border-slate-200 hover:border-emerald-600 hover:bg-emerald-50/50 transition-all flex items-center justify-between group"
                  >
                    <div>
                      <div class="font-bold text-xs text-slate-900 group-hover:text-emerald-800">Tanya Brosur Cluster Aurora</div>
                      <div class="text-[11px] text-slate-500">Klaster Eksklusif dengan View Perbukitan</div>
                    </div>
                    <i class="mdi mdi-whatsapp text-emerald-600 text-2xl"></i>
                  </a>
                </div>

                <div class="pt-2">
                  <a
                    href="https://wa.me/6281225566675?text=Halo%20Marketing%20BSB%20Village,%20saya%20ingin%20konsultasi%20umum%20dan%20simulasi%20KPR"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="w-full py-3 rounded-xl bg-[#8ea854] hover:bg-[#7a9544] text-[#0c3823] font-extrabold text-sm shadow-md transition-colors flex items-center justify-center gap-2"
                  >
                    <i class="mdi mdi-whatsapp text-lg"></i>
                    <span>Chat WhatsApp Langsung (0812-2556-6675)</span>
                  </a>
                </div>
              </div>
            </div>

          </div>
        </div>
      </section>

    </main>

    <!-- 9. FOOTER -->
    <footer class="bg-[#082215] text-slate-300 pt-16 pb-12 border-t border-emerald-950 text-xs">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-8">
          
          <!-- Column 1: Brand & Bio -->
          <div class="lg:col-span-5 space-y-4">
            <img
              :src="companyStore.logoLightUrl"
              :alt="companyStore.companyName"
              style="height: 44px; max-height: 44px; width: auto; object-fit: contain;"
            />
            <p class="text-slate-400 text-xs leading-relaxed max-w-sm">
              Perumahan BSB Village Semarang menghadirkan hunian modern bernuansa alam di area perbukitan yang mengusung konsep “Green Living in Harmony”. Oase baru untuk kualitas hidup sehat keluarga Anda.
            </p>
            <div class="text-emerald-400 font-mono text-[11px]">
              PT. Hijau Cipta Harmoni • Bukit Semarang Baru
            </div>
          </div>

          <!-- Column 2: Navigasi Halaman -->
          <div class="lg:col-span-3 space-y-3">
            <div class="font-bold text-white uppercase tracking-wider text-xs">
              Kawasan & Cluster
            </div>
            <ul class="space-y-2 text-slate-400">
              <li><a href="#beranda" class="hover:text-emerald-400 transition-colors">Beranda</a></li>
              <li><a href="#tentang" class="hover:text-emerald-400 transition-colors">Tentang Kami</a></li>
              <li><a href="#cluster" class="hover:text-emerald-400 transition-colors">Cluster AYRA</a></li>
              <li><a href="#cluster" class="hover:text-emerald-400 transition-colors">Cluster Lokka</a></li>
              <li><a href="#cluster" class="hover:text-emerald-400 transition-colors">Cluster Aurora</a></li>
              <li><a href="#fasilitas" class="hover:text-emerald-400 transition-colors">Fasilitas Kawasan</a></li>
            </ul>
          </div>

          <!-- Column 3: Akses Sistem HRMS & Karier -->
          <div class="lg:col-span-4 space-y-3">
            <div class="font-bold text-white uppercase tracking-wider text-xs">
              Portal Layanan & Internal
            </div>
            <ul class="space-y-2 text-slate-400">
              <li>
                <router-link to="/karier" class="hover:text-emerald-400 transition-colors flex items-center gap-1.5">
                  <i class="mdi mdi-briefcase-outline text-emerald-400"></i>
                  <span>Portal Karier & Rekrutmen</span>
                </router-link>
              </li>
              <li>
                <router-link to="/hrms/login" class="hover:text-emerald-400 transition-colors flex items-center gap-1.5">
                  <i class="mdi mdi-shield-account-outline text-emerald-400"></i>
                  <span>Portal HRMS Internal</span>
                </router-link>
              </li>
              <li>
                <router-link to="/login" class="hover:text-emerald-400 transition-colors flex items-center gap-1.5">
                  <i class="mdi mdi-account-circle-outline text-emerald-400"></i>
                  <span>Portal Mandiri Karyawan</span>
                </router-link>
              </li>
              <li class="pt-2 text-slate-400 text-[11px] leading-relaxed">
                Marketing Gallery: Jl. Boulevard Raya BSB Village, Kel. Bubakan, Kec. Mijen, Semarang.
              </li>
            </ul>
          </div>

        </div>

        <div class="pt-8 border-t border-emerald-950/80 flex flex-col sm:flex-row items-center justify-between gap-4 text-slate-500">
          <div>
            © {{ new Date().getFullYear() }} BSB Village Semarang. PT. Hijau Cipta Harmoni. Hak Cipta Dilindungi Undang-Undang.
          </div>
          <div class="text-emerald-500 font-mono text-[11px]">
            Alami, Asri, & Tenang — Green Living in Harmony
          </div>
        </div>

      </div>
    </footer>

    <!-- 10. VIDEO TOUR MODAL -->
    <div
      v-if="showVideoModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/85 backdrop-blur-md animate-in fade-in duration-200"
      @click.self="showVideoModal = false"
    >
      <div class="relative w-full max-w-4xl bg-[#0c3823] rounded-2xl overflow-hidden border border-emerald-600/50 shadow-2xl">
        <div class="flex items-center justify-between px-6 py-4 border-b border-emerald-800/80 bg-emerald-950/70">
          <div class="flex items-center gap-2">
            <i class="mdi mdi-play-circle text-[#8ea854] text-xl"></i>
            <span class="font-bold text-white text-sm">Video Tour Kawasan BSB Village Semarang</span>
          </div>
          <button
            type="button"
            @click="showVideoModal = false"
            class="text-emerald-300 hover:text-white p-1 rounded-lg transition-colors"
          >
            <i class="mdi mdi-close text-2xl"></i>
          </button>
        </div>

        <div class="aspect-video w-full bg-black relative flex items-center justify-center">
          <!-- Video Preview with Fallback and Direct Play -->
          <iframe
            class="w-full h-full"
            src="https://www.youtube-nocookie.com/embed?listType=search&list=BSB+Village+Semarang+drone"
            title="Video Kawasan BSB Village Semarang"
            frameborder="0"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
            allowfullscreen
          ></iframe>
        </div>

        <div class="p-5 bg-gradient-to-r from-[#072416] to-[#0c3823] flex flex-wrap items-center justify-between gap-3 text-xs">
          <div>
            <div class="font-bold text-white">Ingin Kunjungan Langsung ke Lokasi?</div>
            <div class="text-emerald-300">Tim Sales kami siap mendampingi Anda berkeliling kawasan dan melihat unit rumah contoh.</div>
          </div>
          <a
            href="https://wa.me/6281225566675?text=Halo%20Marketing%20BSB%20Village,%20setelah%20melihat%20video%20saya%20tertarik%20untuk%20survei%20lokasi"
            target="_blank"
            rel="noopener noreferrer"
            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-[#8ea854] hover:bg-[#7a9544] text-[#0c3823] font-bold shadow-md transition-colors"
          >
            <i class="mdi mdi-whatsapp text-base"></i>
            <span>Jadwalkan Survei Lokasi</span>
          </a>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useCompanyStore } from '../../stores/companyStore'
import '../../react-app/index.css'

const companyStore = useCompanyStore()
const mobileNavOpen = ref(false)
const showVideoModal = ref(false)

onMounted(() => {
  if (!companyStore.company) {
    companyStore.fetchCompany()
  }
})
</script>

<style scoped>
/* Ken Burns Slow Zoom Motion */
.kenburns-bg {
  animation: kenburns 26s ease-in-out infinite alternate;
  transform-origin: center center;
}

@keyframes kenburns {
  0% {
    transform: scale(1.04) translate(0, 0);
  }
  50% {
    transform: scale(1.12) translate(-1%, -1%);
  }
  100% {
    transform: scale(1.04) translate(0, 0);
  }
}

/* Sunbeam glow pulse */
.sunbeam-glow {
  animation: sunbeamPulse 8s ease-in-out infinite alternate;
}

@keyframes sunbeamPulse {
  0% {
    opacity: 0.25;
    transform: scale(1);
  }
  100% {
    opacity: 0.5;
    transform: scale(1.05);
  }
}

/* Floating Nature Leaves */
.floating-leaf {
  position: absolute;
  pointer-events: none;
  z-index: 10;
}

.leaf-1 {
  top: -10%;
  left: 18%;
  animation: floatDownLeft 15s cubic-bezier(0.4, 0, 0.2, 1) infinite;
  animation-delay: 0s;
}

.leaf-2 {
  top: -10%;
  left: 65%;
  animation: floatDownRight 18s cubic-bezier(0.4, 0, 0.2, 1) infinite;
  animation-delay: 2.5s;
}

.leaf-3 {
  top: -10%;
  left: 42%;
  animation: floatDownLeft 20s cubic-bezier(0.4, 0, 0.2, 1) infinite;
  animation-delay: 6s;
}

.leaf-4 {
  top: -10%;
  left: 82%;
  animation: floatDownRight 22s cubic-bezier(0.4, 0, 0.2, 1) infinite;
  animation-delay: 1.5s;
}

.leaf-5 {
  top: -10%;
  left: 10%;
  animation: floatDownRight 17s cubic-bezier(0.4, 0, 0.2, 1) infinite;
  animation-delay: 8s;
}

@keyframes floatDownLeft {
  0% {
    transform: translate(0, 0) rotate(0deg);
    opacity: 0;
  }
  15% {
    opacity: 0.85;
  }
  85% {
    opacity: 0.85;
  }
  100% {
    transform: translate(-150px, 750px) rotate(260deg);
    opacity: 0;
  }
}

@keyframes floatDownRight {
  0% {
    transform: translate(0, 0) rotate(0deg);
    opacity: 0;
  }
  15% {
    opacity: 0.85;
  }
  85% {
    opacity: 0.85;
  }
  100% {
    transform: translate(160px, 750px) rotate(-220deg);
    opacity: 0;
  }
}
</style>
