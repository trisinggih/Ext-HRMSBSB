<template>
  <div>
    <!-- Hero / tentang dari settings -->
    <section class="bg-[#1e2a3a] text-white">
      <div class="max-w-6xl mx-auto px-4 py-16">
        <h1 class="text-3xl md:text-4xl font-bold">{{ settings.company_name || 'Tentang Perusahaan' }}</h1>
        <p class="mt-3 text-white/70 max-w-2xl">{{ settings.company_tagline || settings.tagline || 'Profil dan informasi perusahaan kami.' }}</p>
      </div>
    </section>

    <div class="max-w-6xl mx-auto px-4 py-10">
      <div v-if="loading" class="text-center text-slate-400 py-16">Memuat...</div>

      <template v-else>
        <!-- Sections dari CMS -->
        <div v-if="sections.length" class="space-y-10">
          <div v-for="s in sections" :key="s.id" class="grid grid-cols-1 md:grid-cols-3 gap-6 items-start bg-white border rounded-xl p-6 shadow-sm">
            <div>
              <h2 class="text-xl font-semibold text-slate-800">{{ s.title || s.key }}</h2>
              <span class="inline-block text-xs text-slate-400 mt-1 uppercase tracking-wide">{{ s.key }}</span>
            </div>
            <div class="md:col-span-2">
              <p v-if="s.content" class="text-slate-600 whitespace-pre-line">{{ s.content }}</p>
              <img v-if="s.image_url" :src="s.image_url" class="mt-3 rounded-lg max-h-64 object-cover" />
            </div>
          </div>
        </div>

        <!-- Fallback sections jika kosong -->
        <div v-else class="space-y-10">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6 bg-white border rounded-xl p-6 shadow-sm">
            <h2 class="text-xl font-semibold text-slate-800 md:col-span-1">Tentang Kami</h2>
            <p class="md:col-span-2 text-slate-600">{{ settings.about || settings.company_description || 'Informasi tentang perusahaan kami.' }}</p>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-6 bg-white border rounded-xl p-6 shadow-sm">
            <h2 class="text-xl font-semibold text-slate-800 md:col-span-1">Visi</h2>
            <p class="md:col-span-2 text-slate-600">{{ settings.vision || '-' }}</p>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6 bg-white border rounded-xl p-6 shadow-sm">
            <h2 class="text-xl font-semibold text-slate-800 md:col-span-1">Misi</h2>
            <p class="md:col-span-2 text-slate-600 whitespace-pre-line">{{ settings.mission || '-' }}</p>
          </div>
        </div>

        <!-- Kontak -->
        <div class="mt-10 bg-white border rounded-xl p-6 shadow-sm grid grid-cols-1 md:grid-cols-3 gap-6">
          <div>
            <h2 class="text-lg font-semibold text-slate-800 mb-2">Kontak</h2>
            <p class="text-slate-600 text-sm">Hubungi kami untuk informasi lebih lanjut.</p>
          </div>
          <div class="md:col-span-2 grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm text-slate-600">
            <div>
              <p class="font-medium text-slate-800 mb-1">Telepon</p>
              <p>{{ settings.contact_phone || '-' }}</p>
            </div>
            <div>
              <p class="font-medium text-slate-800 mb-1">Email</p>
              <p>{{ settings.contact_email || '-' }}</p>
            </div>
            <div>
              <p class="font-medium text-slate-800 mb-1">Alamat</p>
              <p>{{ settings.company_address || '-' }}</p>
            </div>
          </div>
        </div>

        <div class="mt-8 text-center">
          <router-link to="/karir" class="inline-block bg-[#1e2a3a] hover:bg-[#2a3b4f] text-white text-sm font-medium px-6 py-3 rounded-lg transition">
            Lihat Lowongan Kerja →
          </router-link>
        </div>
      </template>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../../api'

const site = ref({ settings: {}, sections: [] })
const loading = ref(true)
const settings = computed(() => site.value.settings || {})
const sections = computed(() => site.value.sections || [])

onMounted(async () => {
  try {
    const { data } = await api.get('/cms/site')
    site.value = data.data ?? { settings: {}, sections: [] }
  } catch (e) { /* default */ }
  finally { loading.value = false }
})
</script>
