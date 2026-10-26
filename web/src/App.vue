<template>
  <router-view />
</template>

<script setup>
import { watch, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useCompanyStore } from './stores/companyStore'

const route = useRoute()
const companyStore = useCompanyStore()

// ── Title page ──────────────────────────────────────────────
watch(
  () => [route.meta?.title, companyStore.companyName],
  ([title, companyName]) => {
    const name = companyName || 'HRMS'
    document.title = title ? `${title} · ${name}` : name
  },
  { immediate: true }
)

// ── Favicon dinamis ─────────────────────────────────────────
// Watch langsung pada faviconUrl; dieksekusi setiap kali store berubah
watch(
  () => companyStore.faviconUrl,
  (faviconUrl) => {
    if (!faviconUrl) return
    // Hapus semua favicon yang ada
    document.querySelectorAll("link[rel~='icon'], link[rel='shortcut icon']").forEach(e => e.remove())
    // Buat link favicon baru dengan cache-buster
    const link = document.createElement('link')
    link.id = 'dynamic-favicon'
    link.rel = 'icon'
    link.href = faviconUrl + (faviconUrl.includes('?') ? '&' : '?') + '_t=' + Date.now()
    document.head.appendChild(link)
  },
  { immediate: true }
)

// ── Pastikan data company selalu di-load di semua layout ────
onMounted(() => {
  if (!companyStore.company) {
    companyStore.fetchCompany()
  }
})
</script>
<style>
body[data-layout=horizontal] .page-content {
    margin-top: 57px;
}
@media (min-width: 1200px) {
    body[data-layout=horizontal] .container-fluid, body[data-layout=horizontal] .navbar-header {
        max-width: 100%;
    }
}
</style>
