<template>
<section id="katalog-unggulan" class="w-full py-16 sm:py-24 bg-white border-b border-slate-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    {{ /* Section Header */ }}
  <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-8 sm:mb-12">
          <div class="flex flex-col gap-2 max-w-2xl">
            <span class="font-label-mono-sm text-[11px] text-[#008779] uppercase tracking-widest font-bold">
              LINEUP PERFORMA MATIC
            </span>
            <h2 class="font-headline-lg text-slate-900 uppercase tracking-tight font-extrabold text-3xl sm:text-4xl">
              Katalog Produk Pilihan Jangeby
            </h2>
            <p class="font-body-md text-slate-600 text-sm sm:text-base leading-relaxed">
              Komponen transmisi CVT, kelistrikan presisi, dan pendingin mesin yang telah melalui uji ketahanan dinamis untuk menjamin akselerasi tanpa getar dan performa puncak.
            </p>
          </div>
          <div class="font-tech-data text-slate-400 text-xs sm:text-sm tracking-wider font-semibold">
            TOTAL 10 KOMPONEN RACING &amp; HARIAN
          </div>
        </div>
    {{ /* Filter Navigation Tabs */ }}
  <div class="flex flex-wrap items-center gap-2 mb-10 pb-2 border-b border-slate-100">
          {categories.map((cat) => (
            <button
              key={cat.id}
              @click="() => setSelectedCategory(cat.id)"
              class={`px-4 py-2 rounded-full font-tech-data text-xs sm:text-sm transition-all duration-200 flex items-center gap-2 ${
                selectedCategory === cat.id
                  ? 'bg-[#008779] text-white shadow-sm font-bold'
                  : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
              }`}
            >
              <span>
    {{ cat.label }}
  </span>
              <span
                class={`text-[10px] px-1.5 py-0.2 rounded-full ${
                  selectedCategory === cat.id ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-600'
                }`}
              >
    {{ cat.count }}
  </span>
            </button>
          ))}
        </div>
    {{ /* Product Grid */ }}
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8">
          {filteredProducts.map((product) => {
            const hasMultipleImages = product.images.length > 1;

            return (
              <div
                key={product.id}
                class="group rounded-2xl bg-white border border-slate-200 hover:border-teal-400 hover:shadow-lg transition-all duration-300 flex flex-col justify-between overflow-hidden relative"
              >
    {{ /* Product Image Area */ }}
  <div
                  @click="() => setActiveModalProduct(product)"
                  class="cursor-pointer relative aspect-[4/3] bg-slate-50 border-b border-slate-100 overflow-hidden flex items-center justify-center p-6"
                >
                  <div class="absolute top-3 left-3 z-20 flex items-center gap-1.5">
                    <span class="font-label-mono-sm text-[10px] bg-white/95 text-[#008779] border border-slate-200 px-2.5 py-1 rounded shadow-xs font-bold tracking-wider">
    {{ product.badge }}
  </span>
                  </div>

                  <div class="absolute top-3 right-3 z-20">
                    <span class="font-tech-data text-[10px] bg-teal-50 text-[#008779] border border-teal-200 px-2 py-0.5 rounded font-semibold">
    {{ product.highlight }}
  </span>
                  </div>

                  {!hasMultipleImages ? (
                    <img
                      src={product.images[0]}
                      alt={product.name}
                      class="w-full h-full object-contain transform group-hover:scale-105 transition-transform duration-500"
                     />
                  ) : (
                    <div class="grid grid-cols-2 gap-3 w-full h-full">
                      <div class="relative flex items-center justify-center bg-white/60 rounded-xl p-2 border border-slate-100">
                        <img
                          src={product.images[0]}
                          alt={`${product.name} part 1`}
                          class="w-full h-full object-contain transform group-hover:scale-105 transition-transform duration-500"
                         />
                      </div>
                      <div class="relative flex items-center justify-center bg-white/60 rounded-xl p-2 border border-slate-100">
                        <img
                          src={product.images[1]}
                          alt={`${product.name} part 2`}
                          class="w-full h-full object-contain transform group-hover:scale-105 transition-transform duration-500"
                         />
                      </div>
                    </div>
                  )}
                </div>
    {{ /* Card Content Area */ }}
  <div class="p-6 flex flex-col flex-1 justify-between gap-5">
                  <div class="flex flex-col gap-2">
                    <div class="flex items-center justify-between text-xs text-slate-500 font-label-mono-sm">
                      <span class="text-[#008779] font-bold">
    {{ product.categoryLabel }}
  </span>
                      <span class="text-slate-700 font-semibold">
    {{ product.specValue }}
  </span>
                    </div>

                    <h3
                      @click="() => setActiveModalProduct(product)"
                      class="font-headline-sm text-slate-900 font-bold text-lg sm:text-xl group-hover:text-[#008779] transition-colors cursor-pointer"
                    >
    {{ product.name }}
  </h3>

                    <p class="font-body-sm text-slate-600 line-clamp-2 text-xs sm:text-sm leading-relaxed">
    {{ product.description }}
  </p>

                    <div class="mt-2 py-1.5 px-2.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-700 font-label-mono-sm text-[11px] flex items-center justify-between">
                      <span class="text-slate-500">
    {{ product.specLabel }}
  </span>
                      <span class="text-teal-700 font-bold">100% ORIGINAL</span>
                    </div>
                  </div>
    {{ /* Card Action CTAs */ }}
  <div class="flex items-center gap-2 pt-2 border-t border-slate-100">
                    <button
                      type="button"
                      @click="() => setActiveModalProduct(product)"
                      class="flex-1 inline-flex items-center justify-center gap-1.5 py-2.5 px-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 font-tech-data text-xs transition-colors"
                    >
                      <span class="material-symbols-outlined text-[16px]">info</span>
                      <span>Spesifikasi</span>
                    </button>
                    <a
                      href="https://shopee.co.id/jangebyofficial"
                      target="_blank"
                      rel="noopener noreferrer"
                      class="flex-1 inline-flex items-center justify-center gap-1.5 py-2.5 px-3 rounded-xl bg-[#008779] hover:bg-[#007468] text-white font-tech-data text-xs shadow-xs transition-colors"
                    >
                      <span class="material-symbols-outlined text-[16px]">shopping_cart</span>
                      <span>Beli Resmi</span>
                    </a>
                  </div>
                </div>
              </div>
            );
          })}
        </div>
    {{ /* Bottom Banner inside Catalog */ }}
  <div class="mt-14 p-6 sm:p-8 rounded-3xl bg-gradient-to-r from-slate-900 via-slate-800 to-teal-950 text-white flex flex-col md:flex-row items-center justify-between gap-6 shadow-xl">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-[#008779]/30 border border-teal-400/40 flex items-center justify-center text-teal-300">
              <span class="material-symbols-outlined text-[28px]">verified_user</span>
            </div>
            <div class="flex flex-col">
              <span class="font-tech-data text-lg font-bold">Bingung Menentukan Gram Roller &amp; RPM Per CVT?</span>
              <span class="text-sm text-slate-300">Konsultasikan kebutuhan spek harian, touring, atau bore-up Anda ke tim teknis resmi Jangeby.</span>
            </div>
          </div>
          <a
            href="https://wa.me/6285770201010?text=Halo%20Jangeby,%20mau%20konsultasi%20spek%20roller%20dan%20per%20CVT%20untuk%20motor%20saya"
            target="_blank"
            rel="noopener noreferrer"
            class="whitespace-nowrap px-6 py-3 rounded-full bg-[#008779] hover:bg-teal-500 text-white font-tech-data text-xs sm:text-sm font-bold shadow-lg transition-all"
          >
            Konsultasi Gratis Sekarang
          </a>
        </div>
      </div>

      {/* Modal Detail */}
      {activeModalProduct && (
        <ProductModal
          product={activeModalProduct}
          onClose={() => setActiveModalProduct(null)}
        />
      )}
    </section>
</template>
<script setup></script>