<template>
<section id="beranda" class="relative w-full overflow-hidden bg-gradient-to-b from-white via-slate-50 to-slate-100/70 pb-12 pt-6 sm:pt-10 border-b border-slate-200">
    {{ /* Atmospheric Glow */ }}
  <div class="absolute -top-32 left-1/4 w-96 h-96 rounded-full bg-teal-500/10 blur-3xl pointer-events-none" />
      <div class="absolute top-1/2 -right-24 w-[30rem] h-[30rem] rounded-full bg-cyan-500/10 blur-[120px] pointer-events-none" />

      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-center">
    {{ /* Hero Left Column */ }}
  <div class="lg:col-span-7 flex flex-col gap-6">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-teal-50 border border-teal-200 w-fit">
              <span class="material-symbols-outlined text-[16px] text-[#008779]">
                bolt
              </span>
              <span class="font-label-mono-sm text-[11px] text-[#008779] tracking-widest uppercase font-bold">
                RACING SPECIFICATION &amp; STREET DURABILITY
              </span>
            </div>

            <div class="flex flex-col gap-2">
              <h1 class="font-display-hero text-slate-900 tracking-tight uppercase font-extrabold text-4xl sm:text-5xl lg:text-6xl leading-tight">
                RIDE WITH <br />
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#008779] via-teal-600 to-cyan-600">
                  REAL PERFORMANCE
                </span>
              </h1>
              <p class="font-body-lg text-slate-600 leading-relaxed max-w-2xl mt-2 text-base sm:text-lg">
                Dengan memilih produk Jangeby, Anda tidak hanya mendapatkan kualitas terbaik tetapi juga investasi yang mendukung kenyamanan dan keamanan berkendara baik harian, touring, maupun balap. Sangat cocok bagi pengendara yang mengutamakan kualitas dan performa nyata.
              </p>
            </div>
    {{ /* Dual High Impact CTAs */ }}
  <div class="flex flex-wrap items-center gap-4 pt-1">
              <a
                id="hero-catalog-cta"
                class="inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-full bg-[#008779] text-white font-tech-data text-sm sm:text-base shadow-[0_4px_16px_rgba(0,135,121,0.25)] hover:shadow-[0_6px_22px_rgba(0,135,121,0.35)] hover:bg-[#007468] transition-all duration-200"
                href="#katalog-unggulan"
              >
                <span class="material-symbols-outlined text-[20px]">sports_motorsports</span>
                <span>Lihat Produk Unggulan</span>
              </a>
              <a
                id="hero-whatsapp-cta"
                class="inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-full bg-white border border-slate-300 text-slate-800 font-tech-data text-sm sm:text-base hover:bg-slate-50 hover:text-[#008779] hover:border-[#008779]/50 shadow-sm transition-all duration-200"
                href="https://wa.me/6285770201010?text=Halo,%20Saya%20ingin%20bertanya%20terkait%20produk%20Jangeby"
                rel="noopener noreferrer"
                target="_blank"
              >
                <span class="material-symbols-outlined text-[20px] text-[#008779]">chat</span>
                <span>Hubungi via WhatsApp</span>
              </a>
            </div>
    {{ /* Official Verification Notice Badge */ }}
  <div
              @click="onOpenVerifyModal"
              class="cursor-pointer group flex items-center gap-3 p-3.5 rounded-xl bg-white border border-slate-200/90 shadow-sm hover:border-teal-300 hover:shadow-md transition-all duration-200 max-w-xl"
            >
              <div class="w-10 h-10 rounded-lg bg-teal-50 border border-teal-100 flex items-center justify-center text-[#008779] flex-shrink-0 group-hover:scale-105 transition-transform">
                <span class="material-symbols-outlined text-[22px]">verified_user</span>
              </div>
              <div class="flex flex-col">
                <div class="flex items-center gap-2">
                  <span class="font-tech-data text-slate-900 text-sm font-bold">
                    Jaminan Otentik Barcode Terverifikasi
                  </span>
                  <span class="px-1.5 py-0.5 rounded text-[10px] font-mono bg-teal-100 text-[#008779] font-bold">
                    CHECK
                  </span>
                </div>
                <span class="font-body-sm text-slate-600 text-xs">
                  Beli produk original JANGEBY jangan asal beli. Pastikan barcode resmi dari pabrikan.
                </span>
              </div>
            </div>
          </div>
    {{ /* Hero Right Column: Graphic Display Poster */ }}
  <div class="lg:col-span-5 relative">
            <div class="relative rounded-2xl overflow-hidden bg-white border border-slate-200 shadow-[0_12px_36px_rgba(15,23,42,0.12)] group">
              <div class="absolute inset-0 bg-gradient-to-t from-slate-900/60 via-transparent to-transparent z-10 pointer-events-none" />
              <img
                alt="Jangeby Original Performance Network Map and Lineup"
                class="w-full h-auto object-cover transform group-hover:scale-105 transition-transform duration-500"
                src="https://lh3.googleusercontent.com/aida/AEtjO1VnWzfqsttRBQIy0MdhrGoVNbYzToQo632DuGz9eb5bVea9Rv1sVxphsH0oY4mtVqsIrjYJuGB29QINGWomxiYndzX3PxBNyMFbDR84N0E3YiWjux8Jfur5YAWI0j7FZ0xka1jICQAtZINAdnZyJS7wS_tG3uF7bcKLDLXXcEvquMjEPJZU4KW1_IPHo_e9wvGG5TEEfoomA7MFh2JcBfrtfqDy4ALrqjvrT9SHXcMtHaUwqVqvI9acOzI"
               />
              <div class="absolute bottom-4 left-4 right-4 z-20 p-4 rounded-xl bg-white/95 backdrop-blur-md border border-slate-200/80 shadow-lg flex items-center justify-between">
                <div class="flex flex-col">
                  <span class="font-label-mono-sm text-[11px] text-[#008779] uppercase font-bold">
                    Jaringan Bengkel &amp; Toko Resmi
                  </span>
                  <span class="font-tech-data text-slate-900 text-xs sm:text-sm">
                    Tersedia di Seluruh Pelosok Indonesia
                  </span>
                </div>
                <a
                  class="p-2 rounded-full bg-[#008779] hover:bg-[#007468] text-white transition-colors shadow-sm"
                  href="#jaringan-mitra"
                  aria-label="Lihat Jaringan Mitra"
                >
                  <span class="material-symbols-outlined text-[20px]">arrow_forward</span>
                </a>
              </div>
            </div>
          </div>
        </div>
    {{ /* Telemetry Stats Bar Section */ }}
  <div class="mt-10 sm:mt-14 grid grid-cols-2 lg:grid-cols-4 gap-4">
          <div class="p-5 rounded-xl bg-white border border-slate-200 shadow-sm flex flex-col gap-1 hover:border-teal-300 transition-colors">
            <span class="font-label-mono-sm text-[11px] text-[#008779] uppercase tracking-widest font-bold">
              Garansi Keaslian
            </span>
            <span class="font-headline-lg text-slate-900 font-extrabold text-3xl sm:text-4xl">
              100%
            </span>
            <span class="font-body-sm text-slate-600 text-xs">
              Barcode otentik langsung dari pabrikan resmi
            </span>
          </div>

          <div class="p-5 rounded-xl bg-white border border-slate-200 shadow-sm flex flex-col gap-1 hover:border-teal-300 transition-colors">
            <span class="font-label-mono-sm text-[11px] text-teal-600 uppercase tracking-widest font-bold">
              Engineering Parts
            </span>
            <span class="font-headline-lg text-slate-900 font-extrabold text-3xl sm:text-4xl">
              15+
            </span>
            <span class="font-body-sm text-slate-600 text-xs">
              Komponen balap, touring &amp; harian matic
            </span>
          </div>

          <div class="p-5 rounded-xl bg-white border border-slate-200 shadow-sm flex flex-col gap-1 hover:border-teal-300 transition-colors">
            <span class="font-label-mono-sm text-[11px] text-sky-600 uppercase tracking-widest font-bold">
              Cakupan Nasional
            </span>
            <span class="font-headline-lg text-slate-900 font-extrabold text-3xl sm:text-4xl">
              38+
            </span>
            <span class="font-body-sm text-slate-600 text-xs">
              Provinsi didukung ratusan mitra bengkel
            </span>
          </div>

          <div class="p-5 rounded-xl bg-white border border-slate-200 shadow-sm flex flex-col gap-1 hover:border-teal-300 transition-colors">
            <span class="font-label-mono-sm text-[11px] text-[#008779] uppercase tracking-widest font-bold">
              Kepuasan Pelanggan
            </span>
            <span class="font-headline-lg text-slate-900 font-extrabold text-3xl sm:text-4xl">
              4.9 / 5
            </span>
            <span class="font-body-sm text-slate-600 text-xs">
              Ribuan ulasan positif di Shopee, Tokopedia &amp; TikTok
            </span>
          </div>
        </div>
      </div>
    </section>
</template>
<script setup></script>