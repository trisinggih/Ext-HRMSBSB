<template>
<section id="jaringan-mitra" class="w-full py-16 sm:py-24 bg-slate-50 border-b border-slate-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    {{ /* Section Header */ }}
  <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-8 sm:mb-12">
          <div class="flex flex-col gap-2 max-w-2xl">
            <span class="font-label-mono-sm text-[11px] text-[#008779] uppercase tracking-widest font-bold">
              JARINGAN DISTRIBUSI NASIONAL
            </span>
            <h2 class="font-headline-lg text-slate-900 uppercase tracking-tight font-extrabold text-3xl sm:text-4xl">
              Bengkel &amp; Toko Resmi Jangeby
            </h2>
            <p class="font-body-md text-slate-600 text-sm sm:text-base leading-relaxed">
              Kunjungi mitra bengkel resmi terdekat untuk pemasangan presisi, konsultasi kirian, dan jaminan produk 100% original di seluruh pelosok Indonesia.
            </p>
          </div>
    {{ /* Search Box */ }}
  <div class="relative min-w-[280px]">
            <input
              type="text"
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              placeholder="Cari kota, nama bengkel, daerah..."
              class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-white border border-slate-300 text-sm text-slate-800 placeholder:text-slate-400 focus:outline-none focus:border-[#008779] shadow-xs"
            />
            <span class="material-symbols-outlined absolute left-3 top-3 text-[18px] text-slate-400">
              search
            </span>
    {{ searchQuery && (
              <button
                @click="() => setSearchQuery('')"
                class="absolute right-3 top-3 text-slate-400 hover:text-slate-700 text-xs"
              >
                ✕
              </button>
            ) }}
  </div>
        </div>
    {{ /* Region Filters */ }}
  <div class="flex flex-wrap items-center gap-2 mb-8">
          <button
            @click="() => setSelectedRegion('all')"
            class={`px-4 py-2 rounded-full font-tech-data text-xs transition-all ${
              selectedRegion === 'all'
                ? 'bg-[#008779] text-white font-bold shadow-xs'
                : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-100'
            }`}
          >
            Semua Wilayah ({MITRA_DATA.length})
          </button>
          <button
            @click="() => setSelectedRegion('jawa-bali')"
            class={`px-4 py-2 rounded-full font-tech-data text-xs transition-all ${
              selectedRegion === 'jawa-bali'
                ? 'bg-[#008779] text-white font-bold shadow-xs'
                : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-100'
            }`}
          >
            Jawa &amp; Bali (4)
          </button>
          <button
            @click="() => setSelectedRegion('sumatera')"
            class={`px-4 py-2 rounded-full font-tech-data text-xs transition-all ${
              selectedRegion === 'sumatera'
                ? 'bg-[#008779] text-white font-bold shadow-xs'
                : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-100'
            }`}
          >
            Sumatera (2)
          </button>
          <button
            @click="() => setSelectedRegion('kal-sul-papua')"
            class={`px-4 py-2 rounded-full font-tech-data text-xs transition-all ${
              selectedRegion === 'kal-sul-papua'
                ? 'bg-[#008779] text-white font-bold shadow-xs'
                : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-100'
            }`}
          >
            Kal-Sul-Papua (2)
          </button>
        </div>
    {{ /* Mitra Cards Grid */ }}
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          {filteredMitra.map((mitra) => (
            <div
              key={mitra.id}
              class="p-6 rounded-2xl bg-white border border-slate-200 hover:border-teal-400 hover:shadow-md transition-all duration-300 flex flex-col justify-between"
            >
              <div class="flex flex-col gap-3">
                <div class="flex items-center justify-between">
                  <span class="font-label-mono-sm text-[10px] text-[#008779] bg-teal-50 px-2.5 py-0.5 rounded border border-teal-100 font-bold uppercase">
    {{ mitra.regionLabel }}
  </span>
    {{ mitra.verified && (
                    <span class="flex items-center gap-1 text-[11px] font-tech-data text-teal-700 font-semibold">
                      <span class="material-symbols-outlined text-[15px] text-[#008779]">verified</span>
                      VERIFIED
                    </span>
                  ) }}
  </div>

                <h3 class="font-headline-sm text-slate-900 font-bold text-lg leading-snug">
    {{ mitra.name }}
  </h3>

                <div class="flex items-start gap-1.5 text-xs text-slate-600">
                  <span class="material-symbols-outlined text-[16px] text-slate-400 mt-0.5 flex-shrink-0">
                    location_on
                  </span>
                  <span>
    {{ mitra.address }}
  </span>
                </div>

                <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100">
                  <span class="font-label-mono-sm text-[10px] text-slate-400 uppercase block mb-1">
                    Spesialisasi:
                  </span>
                  <span class="text-xs font-medium text-slate-800">
    {{ mitra.specialization }}
  </span>
                </div>
              </div>

              <div class="mt-5 pt-4 border-t border-slate-100 flex items-center justify-between">
                <span class="font-mono text-xs text-slate-500">
    {{ mitra.city }}
  </span>
                <a
                  href={`https://wa.me/${mitra.phone.replace(/[^0-9]/g, '')}?text=Halo%20${encodeURIComponent(mitra.name)},%20saya%20ingin%20jadwalkan%20servis%20dan%20pasang%20part%20Jangeby`}
                  target="_blank"
                  rel="noopener noreferrer"
                  class="inline-flex items-center gap-1 text-xs font-tech-data font-bold text-[#008779] hover:text-[#007468]"
                >
                  <span>Hubungi</span>
                  <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                </a>
              </div>
            </div>
          ))}
        </div>
    {{ /* Mitra Registration Callout Banner */ }}
  <div class="mt-12 p-8 rounded-3xl bg-white border border-teal-200/80 shadow-sm flex flex-col lg:flex-row items-center justify-between gap-6">
          <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-2xl bg-teal-50 border border-teal-200 flex items-center justify-center text-[#008779] flex-shrink-0">
              <span class="material-symbols-outlined text-[32px]">storefront</span>
            </div>
            <div class="flex flex-col">
              <span class="font-label-mono-sm text-[11px] text-[#008779] uppercase font-bold tracking-wider">
                PROGRAM KEMITRAAN RESMI 2026
              </span>
              <h3 class="font-headline-sm text-xl sm:text-2xl text-slate-900 font-bold">
                Buka Peluang Bisnis Bengkel Bersama Jangeby Official
              </h3>
              <p class="font-body-sm text-slate-600 text-xs sm:text-sm mt-1">
                Dapatkan harga grosir distributor, dukungan materi promosi display toko, garansi retur resmi, dan promosi nama bengkel Anda di direktori resmi.
              </p>
            </div>
          </div>

          <div class="flex flex-wrap items-center gap-3">
            <button
              @click="onOpenPartnerModal"
              class="px-6 py-3 rounded-full bg-[#008779] hover:bg-[#007468] text-white font-tech-data text-xs sm:text-sm font-bold shadow-sm transition-all"
            >
              Daftar Jadi Mitra Bengkel
            </button>
            <a
              href="https://wa.me/6285770201010?text=Halo%20Tim%20Kemitraan%20Jangeby,%20saya%20ingin%20mendaftar%20sebagai%20agen/bengkel%20resmi"
              target="_blank"
              rel="noopener noreferrer"
              class="px-6 py-3 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-800 font-tech-data text-xs sm:text-sm font-bold transition-all"
            >
              Hubungi Sales Grosir
            </a>
          </div>
        </div>
      </div>
    </section>
</template>
<script setup></script>