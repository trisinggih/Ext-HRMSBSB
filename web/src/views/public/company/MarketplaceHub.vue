<template>
<section id="marketplace" class="w-full py-16 sm:py-24 bg-white border-b border-slate-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-12">
          <div class="flex flex-col gap-2 max-w-2xl">
            <span class="font-label-mono-sm text-[11px] text-[#008779] uppercase tracking-widest font-bold">
              SALURAN PEMBELIAN RESMI
            </span>
            <h2 class="font-headline-lg text-slate-900 uppercase tracking-tight font-extrabold text-3xl sm:text-4xl">
              Toko Resmi &amp; Marketplace Jangeby
            </h2>
            <p class="font-body-md text-slate-600 text-sm sm:text-base leading-relaxed">
              Dapatkan kepastian jaminan produk 100% original, bebas penipuan, garansi pengiriman aman, dan promo diskon ongkir setiap hari melalui saluran resmi kami.
            </p>
          </div>
          <div class="font-tech-data text-emerald-600 text-xs sm:text-sm tracking-wider font-semibold flex items-center gap-1.5 bg-emerald-50 px-3 py-1.5 rounded-full border border-emerald-200">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping" />
            LIVE ORDER &amp; FAST SHIPPING
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          {marketplaces.map((item) => (
            <div
              key={item.id}
              class="p-6 rounded-2xl bg-white border border-slate-200 hover:border-teal-400 hover:shadow-lg transition-all duration-300 flex flex-col justify-between group"
            >
              <div class="flex flex-col gap-4">
                <div class="flex items-center justify-between">
                  <div class={`w-12 h-12 rounded-xl flex items-center justify-center ${item.iconBg} shadow-sm group-hover:scale-110 transition-transform`}>
                    <span class="material-symbols-outlined text-[24px]">
    {{ item.iconName }}
  </span>
                  </div>
                  <span class={`text-[10px] font-mono font-bold px-2 py-0.5 rounded border ${item.badgeColor}`}>
    {{ item.tag }}
  </span>
                </div>

                <div class="flex flex-col gap-1.5">
                  <h3 class="font-headline-sm text-slate-900 font-bold text-lg group-hover:text-[#008779] transition-colors">
    {{ item.name }}
  </h3>
                  <p class="font-body-sm text-slate-600 text-xs sm:text-sm leading-relaxed">
    {{ item.desc }}
  </p>
                </div>
              </div>

              <div class="mt-6 pt-4 border-t border-slate-100">
                <a
                  href={item.link}
                  target="_blank"
                  rel="noopener noreferrer"
                  class="w-full inline-flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl bg-slate-100 hover:bg-[#008779] text-slate-800 hover:text-white font-tech-data text-xs font-bold transition-all duration-200"
                >
                  <span>
    {{ item.ctaText }}
  </span>
                  <span class="material-symbols-outlined text-[16px]">open_in_new</span>
                </a>
              </div>
            </div>
          ))}
        </div>
      </div>
    </section>
</template>
<script setup></script>