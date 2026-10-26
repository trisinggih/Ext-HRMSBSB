<template>
  <header
    id="main-header"
    :class="[
      'fixed top-0 left-0 w-full z-50 transition-all duration-300',
      isScrolled
        ? 'bg-white/95 backdrop-blur-xl border-b border-slate-200 shadow-sm py-2'
        : 'bg-white/90 backdrop-blur-xl border-b border-slate-200/80 shadow-xs py-3'
    ]"
  >
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between gap-4">
      <!-- Brand Logo -->
      <a href="#" class="flex items-center gap-3 group" id="brand-logo-link">
        <div class="relative flex items-center justify-center">
          <img
            alt="BSB Village"
            class="h-9 object-contain"
            src="/assets/images/logo-dark.png"
          />
        </div>
      </a>

      <!-- Desktop Navigation -->
      <nav class="hidden lg:flex items-center gap-6" id="desktop-nav">
        <a
          v-for="item in navLinks"
          :key="item.id"
          :href="item.href"
          :class="[
            'font-body-sm text-[12px] uppercase tracking-wider transition-colors font-semibold',
            activeSection === item.id
              ? 'text-[#008779] font-bold underline underline-offset-8 decoration-2 decoration-[#008779]'
              : 'text-slate-600 hover:text-[#008779]'
          ]"
        >
          {{ item.label }}
        </a>
      </nav>

      <!-- Header Right Actions -->
      <div class="flex items-center gap-3">
        <a
          id="whatsapp-header-btn"
          class="hidden sm:inline-flex items-center gap-1.5 px-4 py-2 rounded-full bg-[#008779] hover:bg-[#007468] text-white font-tech-data text-xs shadow-[0_2px_12px_rgba(0,135,121,0.25)] hover:shadow-[0_4px_16px_rgba(0,135,121,0.35)] transition-all duration-200"
          href="https://wa.me/6285770201010?text=Halo%20Jangeby%20Official,%20saya%20ingin%20konsultasi%20spek%20performa%20CVT"
          rel="noopener noreferrer"
          target="_blank"
        >
          <span class="material-symbols-outlined text-[18px]">chat</span>
          <span>Konsultasi WhatsApp</span>
        </a>

        <!-- Mobile menu button -->
        <button
          id="mobile-menu-toggle"
          type="button"
          @click="mobileMenuOpen = !mobileMenuOpen"
          class="lg:hidden p-2 rounded-lg text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition-colors"
          aria-label="Buka Menu"
        >
          <span class="material-symbols-outlined text-[26px]">
            {{ mobileMenuOpen ? 'close' : 'menu' }}
          </span>
        </button>
      </div>
    </div>

    <!-- Mobile Menu Dropdown -->
    <div
      v-if="mobileMenuOpen"
      class="lg:hidden bg-white/98 backdrop-blur-xl border-b border-slate-200 px-4 pt-3 pb-6 shadow-xl"
    >
      <div class="flex flex-col space-y-3">
        <a
          v-for="item in navLinks"
          :key="item.id"
          :href="item.href"
          @click="mobileMenuOpen = false"
          class="font-tech-data text-sm uppercase py-2 px-3 rounded-lg text-slate-700 hover:bg-teal-50 hover:text-[#008779] transition-colors"
        >
          {{ item.label }}
        </a>
        <div class="pt-2 border-t border-slate-100 flex flex-col gap-2">
          <a
            class="inline-flex items-center justify-center gap-2 w-full py-2.5 rounded-full bg-[#008779] text-white font-tech-data text-xs shadow-sm"
            href="https://wa.me/6285770201010?text=Halo%20Jangeby%20Official"
            target="_blank"
            rel="noopener noreferrer"
          >
            <span class="material-symbols-outlined text-[18px]">chat</span>
            <span>Konsultasi WhatsApp Langsung</span>
          </a>
        </div>
      </div>
    </div>
  </header>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const isScrolled = ref(false);
const mobileMenuOpen = ref(false);
const activeSection = ref('');

const navLinks = [
  { label: 'Tentang Kami', href: '#tentang-kami', id: 'tentang-kami' },
  { label: 'Keunggulan', href: '#keunggulan', id: 'keunggulan' },
  { label: 'Katalog Produk', href: '#katalog-unggulan', id: 'katalog-unggulan' },
  { label: 'Dyno Test', href: '#dyno-test', id: 'dyno-test' },
  { label: 'Jaringan Mitra', href: '#jaringan-mitra', id: 'jaringan-mitra' },
  { label: 'Marketplace', href: '#marketplace', id: 'marketplace' },
  { label: 'Kontak', href: '#kontak', id: 'kontak' },
];

const handleScroll = () => {
  isScrolled.value = window.scrollY > 10;
};

onMounted(() => {
  window.addEventListener('scroll', handleScroll);
});

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll);
});
</script>