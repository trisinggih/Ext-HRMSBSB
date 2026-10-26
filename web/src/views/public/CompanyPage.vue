<template>
  <div class="max-w-3xl mx-auto px-4 py-10">
    <button @click="$router.back()" class="text-sm text-blue-600 hover:text-blue-800 mb-4">← Kembali</button>

    <div v-if="loading" class="text-center text-slate-400 py-16">Memuat...</div>
    <div v-else-if="!page" class="text-center text-slate-400 py-16 border border-dashed rounded-xl">Halaman tidak ditemukan.</div>

    <template v-else>
      <h1 class="text-3xl font-bold text-slate-800">{{ page.title }}</h1>
      <p v-if="page.meta_description" class="text-slate-500 mt-2">{{ page.meta_description }}</p>

      <div class="mt-6 bg-white border rounded-xl p-6 shadow-sm">
        <p v-if="page.content" class="text-slate-600 whitespace-pre-line">{{ page.content }}</p>
        <p v-else class="text-slate-400">Belum ada konten.</p>
      </div>

      <!-- Sections dari halaman -->
      <div v-if="page.sections?.length" class="mt-6 space-y-4">
        <div v-for="s in page.sections" :key="s.id" class="bg-white border rounded-xl p-6 shadow-sm">
          <h3 class="font-semibold text-slate-800">{{ s.title || s.key }}</h3>
          <p v-if="s.content" class="text-slate-600 mt-2 whitespace-pre-line">{{ s.content }}</p>
          <img v-if="s.image_url" :src="s.image_url" class="mt-3 rounded-lg max-h-64 object-cover" />
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import api from '../../api'

const route = useRoute()
const page = ref(null)
const loading = ref(true)

onMounted(async () => {
  try {
    const { data } = await api.get(`/cms/pages/${route.params.slug}`)
    page.value = data.data
    if (data.data?.meta_title) document.title = `${data.data.meta_title} · ${data.data.title}`
  } catch (e) { /* not found */ }
  finally { loading.value = false }
})
</script>
