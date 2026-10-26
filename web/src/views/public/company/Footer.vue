<template>
<footer class="w-full bg-slate-950 text-white pt-16 pb-12 border-t border-slate-800">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-10 pb-12 border-b border-slate-800">
    {{ /* Brand & Address Column */ }}
  <div class="lg:col-span-4 flex flex-col gap-4">
            <div class="flex items-center gap-3">
              <div class="relative">
                <img
                  alt="BSB Village"
                  class="h-9 object-contain"
                  src="/assets/images/logo-light.png"
                 />
              </div>
            </div>

            <p class="font-body-sm text-slate-400 text-xs sm:text-sm leading-relaxed max-w-sm">
              BSB Village - Kawasan hunian dan real estate terpadu berkonsep Green Living dan Modern Comfort di Semarang. Menghadirkan lingkungan asri, harmonis, dan bernilai investasi tinggi.
            </p>

            <div class="space-y-1 text-xs text-slate-400 font-mono pt-2">
              <div class="text-slate-300 font-bold uppercase font-tech-data">Kantor Pemasaran &amp; Pengelola:</div>
              <div>BSB Village, Jl. Raya Semarang - Boja, Kec. Mijen, Kota Semarang, Jawa Tengah 50212</div>
              <div>Hotline / WA: +62 811-270-0000</div>
            </div>
          </div>
    {{ /* Navigasi Cepat */ }}
  <div class="lg:col-span-3 flex flex-col gap-3">
            <span class="font-tech-data text-xs uppercase font-bold text-teal-400 tracking-wider">
              Navigasi Cepat
            </span>
            <ul class="space-y-2 text-xs text-slate-400">
              <li>
                <a href="#beranda" class="hover:text-teal-400 transition-colors">
                  Beranda
                </a>
              </li>
              <li>
                <a href="#tentang-kami" class="hover:text-teal-400 transition-colors">
                  Tentang Jangeby
                </a>
              </li>
              <li>
                <a href="#keunggulan" class="hover:text-teal-400 transition-colors">
                  4 Keunggulan Material
                </a>
              </li>
              <li>
                <a href="#katalog-unggulan" class="hover:text-teal-400 transition-colors">
                  Katalog 10 Komponen
                </a>
              </li>
              <li>
                <a href="#dyno-test" class="hover:text-teal-400 transition-colors">
                  Simulasi Dyno Test
                </a>
              </li>
              <li>
                <a href="#jaringan-mitra" class="hover:text-teal-400 transition-colors">
                  Jaringan Bengkel Resmi
                </a>
              </li>
            </ul>
          </div>
    {{ /* Saluran Resmi Marketplace */ }}
  <div class="lg:col-span-3 flex flex-col gap-3">
            <span class="font-tech-data text-xs uppercase font-bold text-teal-400 tracking-wider">
              Toko Resmi Online
            </span>
            <ul class="space-y-2 text-xs text-slate-400">
              <li>
                <a
                  href="https://shopee.co.id/jangebyofficial"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="hover:text-teal-400 transition-colors flex items-center gap-1.5"
                >
                  <span class="material-symbols-outlined text-[16px] text-orange-400">shopping_bag</span>
                  <span>Shopee Mall Official</span>
                </a>
              </li>
              <li>
                <a
                  href="https://www.tokopedia.com/jangeby-store"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="hover:text-teal-400 transition-colors flex items-center gap-1.5"
                >
                  <span class="material-symbols-outlined text-[16px] text-emerald-400">store</span>
                  <span>Tokopedia Official Store</span>
                </a>
              </li>
              <li>
                <a
                  href="https://vt.tiktok.com/ZSY3sTxM4/?page=Mall"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="hover:text-teal-400 transition-colors flex items-center gap-1.5"
                >
                  <span class="material-symbols-outlined text-[16px] text-rose-400">live_tv</span>
                  <span>TikTok Shop Jangeby</span>
                </a>
              </li>
              <li>
                <a
                  href="https://wa.me/6285770201010"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="hover:text-teal-400 transition-colors flex items-center gap-1.5"
                >
                  <span class="material-symbols-outlined text-[16px] text-teal-400">forum</span>
                  <span>WhatsApp Grosir &amp; CS</span>
                </a>
              </li>
            </ul>
          </div>
    {{ /* Layanan Keaslian & Mitra */ }}
  <div class="lg:col-span-2 flex flex-col gap-3">
            <span class="font-tech-data text-xs uppercase font-bold text-teal-400 tracking-wider">
              Layanan &amp; Mitra
            </span>
            <div class="space-y-2 text-xs text-slate-400 flex flex-col items-start">
              <button
                type="button"
                @click="onOpenVerifyModal"
                class="hover:text-teal-400 transition-colors text-left flex items-center gap-1"
              >
                <span class="material-symbols-outlined text-[15px] text-[#008779]">qr_code</span>
                <span>Cek Barcode Otentik</span>
              </button>
              <button
                type="button"
                @click="onOpenPartnerModal"
                class="hover:text-teal-400 transition-colors text-left flex items-center gap-1"
              >
                <span class="material-symbols-outlined text-[15px] text-[#008779]">handshake</span>
                <span>Daftar Mitra Bengkel</span>
              </button>
              <a
                href="https://wa.me/6285770201010?text=Halo%20Jangeby,%20saya%20ingin%20tanya%20klaim%20garansi"
                target="_blank"
                rel="noopener noreferrer"
                class="hover:text-teal-400 transition-colors flex items-center gap-1"
              >
                <span class="material-symbols-outlined text-[15px] text-[#008779]">verified</span>
                <span>Klaim Garansi Resmi</span>
              </a>
            </div>
          </div>
        </div>
    {{ /* Copyright & Disclaimer */ }}
  <div class="pt-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-500">
          <div>
            © 2026 BSB Village Real Estate. Hak Cipta Dilindungi Undang-Undang.
          </div>
          <div class="text-center sm:text-right font-mono text-[11px]">
            Designed for Real Performance &amp; Extreme Durability.
          </div>
        </div>
      </div>
    </footer>
</template>
<script setup></script>