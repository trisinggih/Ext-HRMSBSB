<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/70 backdrop-blur-sm animate-in fade-in duration-200">
    <div class="relative w-full max-w-2xl bg-white rounded-3xl border border-slate-200 shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">
      <!-- Header -->
      <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50/70">
        <div class="flex items-center gap-2">
          <span class="px-2.5 py-1 rounded-full bg-[#008779]/10 text-[#008779] font-label-mono-sm text-[11px] font-bold uppercase">
            {{ product.categoryLabel || product.category }}
          </span>
          <span class="text-xs font-mono text-slate-500">// {{ product.badge }}</span>
        </div>
        <button
          @click="$emit('close')"
          class="w-8 h-8 rounded-full bg-white border border-slate-200 flex items-center justify-center text-slate-500 hover:text-slate-900 hover:bg-slate-100 transition-colors"
          aria-label="Tutup"
        >
          <span class="material-symbols-outlined text-[20px]">close</span>
        </button>
      </div>

      <!-- Modal Body -->
      <div class="p-6 overflow-y-auto space-y-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 items-center">
          
          <!-- Image Preview -->
          <div class="relative bg-slate-50 border border-slate-200 rounded-2xl aspect-square flex items-center justify-center p-4 overflow-hidden">
            <template v-if="product.images && product.images.length === 1">
              <img
                :src="product.images[0]"
                :alt="product.name"
                class="w-full h-full object-contain"
              />
            </template>
            <template v-else-if="product.images && product.images.length > 1">
              <div class="grid grid-cols-2 gap-2 w-full h-full">
                <img
                  v-for="(img, i) in product.images"
                  :key="i"
                  :src="img"
                  :alt="`${product.name} ${i + 1}`"
                  class="w-full h-full object-contain"
                />
              </div>
            </template>
            <span v-if="product.badge" class="absolute top-3 left-3 px-2 py-0.5 rounded bg-white/95 border border-slate-200 font-label-mono-sm text-[10px] text-[#008779] font-bold shadow-xs">
              {{ product.badge }}
            </span>
          </div>

          <!-- Info -->
          <div class="flex flex-col gap-3">
            <div class="flex items-center justify-between">
              <span v-if="product.highlight" class="font-tech-data text-xs text-[#008779] bg-teal-50 px-2 py-0.5 rounded border border-teal-100">
                {{ product.highlight }}
              </span>
              <span class="font-label-mono-sm text-xs font-bold text-slate-900">
                {{ product.specValue }}
              </span>
            </div>
            
            <h3 class="font-headline-sm text-2xl font-bold text-slate-900 leading-tight">
              {{ product.name }}
            </h3>
            
            <p class="font-body-sm text-slate-600 text-xs sm:text-sm leading-relaxed">
              {{ product.description }}
            </p>

            <!-- Quick Spec List -->
            <div v-if="product.specs && Object.keys(product.specs).length > 0" class="p-3 rounded-xl bg-slate-50 border border-slate-200 space-y-1.5 text-xs">
              <div v-for="(val, key) in product.specs" :key="key" class="flex justify-between">
                <span class="text-slate-500 capitalize">{{ key }}:</span>
                <span class="font-semibold text-slate-800 text-right">{{ val }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Motor Compatibility Checker -->
        <div v-if="product.compatibility && product.compatibility.length > 0" class="border-t border-slate-200 pt-5">
          <h4 class="font-tech-data text-xs uppercase tracking-wider text-slate-900 mb-2 flex items-center gap-1.5">
            <span class="material-symbols-outlined text-[16px] text-[#008779]">two_wheeler</span>
            Cek Kompatibilitas Motor
          </h4>
          <div class="flex flex-wrap gap-2">
            <button
              v-for="(motor, idx) in product.compatibility"
              :key="idx"
              type="button"
              @click="selectedMotor = motor"
              :class="[
                'text-xs px-3 py-1.5 rounded-lg border transition-all',
                selectedMotor === motor
                  ? 'bg-[#008779] text-white border-[#008779] shadow-xs'
                  : 'bg-slate-50 border-slate-200 text-slate-700 hover:bg-slate-100'
              ]"
            >
              {{ motor }}
            </button>
          </div>
          <p v-if="selectedMotor" class="mt-2 text-xs text-teal-700 font-medium flex items-center gap-1 bg-teal-50 p-2 rounded-lg border border-teal-100">
            <span class="material-symbols-outlined text-[16px]">check_circle</span>
            Terkonfirmasi Presisi Plug &amp; Play untuk <strong>{{ selectedMotor }}</strong>.
          </p>
        </div>

        <!-- Direct Purchase Actions -->
        <div v-if="product.marketplaceLinks" class="border-t border-slate-200 pt-5 flex flex-col sm:flex-row items-center gap-3">
          <a
            v-if="product.marketplaceLinks.shopee"
            :href="product.marketplaceLinks.shopee"
            target="_blank"
            rel="noopener noreferrer"
            class="w-full sm:flex-1 inline-flex items-center justify-center gap-2 py-3 rounded-full bg-[#008779] text-white font-tech-data text-xs shadow-sm hover:bg-[#007468] transition-colors"
          >
            <span class="material-symbols-outlined text-[18px]">shopping_bag</span>
            <span>Beli di Shopee</span>
          </a>
          <a
            v-if="product.marketplaceLinks.tokopedia"
            :href="product.marketplaceLinks.tokopedia"
            target="_blank"
            rel="noopener noreferrer"
            class="w-full sm:flex-1 inline-flex items-center justify-center gap-2 py-3 rounded-full bg-white border border-slate-300 text-slate-800 font-tech-data text-xs shadow-xs hover:border-[#008779] hover:text-[#008779] transition-colors"
          >
            <span class="material-symbols-outlined text-[18px] text-teal-600">store</span>
            <span>Beli di Tokopedia</span>
          </a>
          <a
            v-if="product.marketplaceLinks.whatsapp || true"
            :href="product.marketplaceLinks.whatsapp || `https://wa.me/6285770201010?text=Halo%20Jangeby,%20saya%20ingin%20tanya%20stok%20${encodeURIComponent(product.name)}`"
            target="_blank"
            rel="noopener noreferrer"
            class="w-full sm:w-auto p-3 rounded-full bg-slate-100 hover:bg-teal-50 text-slate-700 hover:text-[#008779] border border-slate-200 transition-colors flex items-center justify-center"
            title="Konsultasi WhatsApp"
          >
            <span class="material-symbols-outlined text-[20px]">chat</span>
          </a>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const props = defineProps({
  product: {
    type: Object,
    required: true
  }
})

defineEmits(['close'])

const selectedMotor = ref(null)
</script>