<template>
  <div class="portal-page">
    <!-- Header -->
    <div class="portal-header">
      <h1 class="portal-title">Portal Aplikasi</h1>
      <p class="portal-subtitle">Pilih modul aplikasi yang ingin Anda akses</p>
    </div>

    <!-- Grid Aplikasi -->
    <div class="portal-grid">

      <!-- 1. CMS Web -->
      <router-link to="/hrms/cms" class="portal-card">
        <div class="card-number">01</div>
        <div class="portal-card-body">
          <h3 class="portal-card-title">CMS Web</h3>
          <p class="portal-card-desc">Manajemen konten website perusahaan</p>
        </div>
        <div class="portal-card-footer">
          <span class="portal-badge portal-badge-active">Aktif</span>
          <span class="arrow">&rarr;</span>
        </div>
      </router-link>

      <!-- 2. Human Resources (HRMS) -->
      <router-link to="/hrms" class="portal-card">
        <div class="card-number">02</div>
        <div class="portal-card-body">
          <h3 class="portal-card-title">Human Resources</h3>
          <p class="portal-card-desc">Manajemen SDM, absensi, payroll, dan organisasi</p>
        </div>
        <div class="portal-card-footer">
          <span class="portal-badge portal-badge-active">Aktif</span>
          <span class="arrow">&rarr;</span>
        </div>
      </router-link>

      <!-- 3. Rekrutmen RMS -->
      <router-link to="/hrms/recruitment" class="portal-card">
        <div class="card-number">03</div>
        <div class="portal-card-body">
          <h3 class="portal-card-title">Rekrutmen RMS</h3>
          <p class="portal-card-desc">Manajemen lowongan dan pendaftaran kandidat</p>
        </div>
        <div class="portal-card-footer">
          <span class="portal-badge portal-badge-active">Aktif</span>
          <span class="arrow">&rarr;</span>
        </div>
      </router-link>

      <!-- 4. POS (Disabled) -->
      <div class="portal-card portal-card-disabled">
        <div class="card-number">04</div>
        <div class="portal-card-body">
          <h3 class="portal-card-title">POS System</h3>
          <p class="portal-card-desc">Sistem kasir, inventaris, dan point of sale</p>
        </div>
        <div class="portal-card-footer">
          <span class="portal-badge portal-badge-soon">Segera</span>
        </div>
      </div>

      <!-- 5. ERP (Disabled) -->
      <div class="portal-card portal-card-disabled">
        <div class="card-number">05</div>
        <div class="portal-card-body">
          <h3 class="portal-card-title">ERP System</h3>
          <p class="portal-card-desc">Enterprise Resource Planning terintegrasi</p>
        </div>
        <div class="portal-card-footer">
          <span class="portal-badge portal-badge-soon">Segera</span>
        </div>
      </div>

    </div>

    <!-- Footer info -->
    <div class="portal-footer">
      <span>Masuk sebagai <strong class="user-name">{{ auth.admin?.name }}</strong></span>
      <button @click="logout" class="portal-logout">Keluar Sesi</button>
    </div>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const auth = useAuthStore()

async function logout() {
  const target = await auth.logout()
  router.push(target || '/hrms/login')
}
</script>

<style scoped>
.portal-page {
  min-height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 1rem;
  font-family: 'Inter', system-ui, sans-serif;
}

.portal-header {
  text-align: center;
  margin-bottom: 3.5rem;
  margin-top: 2rem;
}
.portal-title {
  font-size: 2.2rem;
  font-weight: 800;
  color: #0f172a;
  letter-spacing: -0.02em;
  margin-bottom: 0.5rem;
}
.portal-subtitle { 
  color: #64748b; 
  font-size: 1rem; 
  font-weight: 400;
}

.portal-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 1.5rem;
  width: 100%;
  max-width: 1000px;
}

/* ---- Card Modern Typography Style ---- */
.portal-card {
  position: relative;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  padding: 2rem;
  border-radius: 16px;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  text-decoration: none;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  overflow: hidden;
  cursor: pointer;
  min-height: 200px;
}

.portal-card::before {
  content: '';
  position: absolute;
  top: 0; left: 0;
  width: 100%; height: 4px;
  background: var(--bs-primary, #124d32);
  transform: scaleX(0);
  transform-origin: left;
  transition: transform 0.4s ease;
}

.portal-card:not(.portal-card-disabled):hover {
  transform: translateY(-4px);
  box-shadow: 0 20px 40px -15px rgba(18, 77, 50, 0.15);
  border-color: #cbd5e1;
}

.portal-card:not(.portal-card-disabled):hover::before {
  transform: scaleX(1);
}

.card-number {
  position: absolute;
  top: 1rem;
  right: 1.5rem;
  font-size: 4.5rem;
  font-weight: 900;
  color: #f1f5f9;
  line-height: 1;
  z-index: 0;
  transition: color 0.3s ease, transform 0.3s ease;
}

.portal-card:not(.portal-card-disabled):hover .card-number {
  color: #e2e8f0;
  transform: scale(1.05) translate(5px, -5px);
}

.portal-card-body { 
  position: relative;
  z-index: 1;
  margin-bottom: 2rem;
}
.portal-card-title { 
  font-size: 1.25rem; 
  font-weight: 700; 
  color: #1e293b; 
  margin-bottom: 0.5rem; 
  letter-spacing: -0.01em;
}
.portal-card-desc { 
  font-size: 0.875rem; 
  color: #64748b; 
  line-height: 1.6; 
}

.portal-card-footer {
  position: relative;
  z-index: 1;
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: auto;
}

.arrow {
  color: var(--bs-primary, #124d32);
  font-size: 1.2rem;
  font-weight: bold;
  opacity: 0;
  transform: translateX(-10px);
  transition: all 0.3s ease;
}

.portal-card:not(.portal-card-disabled):hover .arrow {
  opacity: 1;
  transform: translateX(0);
}

.portal-card-disabled {
  background: #f8fafc;
  border-color: #f1f5f9;
  cursor: not-allowed;
}
.portal-card-disabled .portal-card-title { color: #94a3b8; }
.portal-card-disabled .card-number { color: #f8fafc; }

/* ---- Badge ---- */
.portal-badge {
  font-size: 0.65rem;
  font-weight: 700;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  padding: 0.35rem 0.75rem;
  border-radius: 6px;
}
.portal-badge-active { background: rgba(18, 77, 50, 0.1); color: var(--bs-primary, #124d32); }
.portal-badge-soon   { background: #e2e8f0; color: #64748b; }

/* ---- Footer ---- */
.portal-footer {
  margin-top: 4rem;
  display: flex;
  align-items: center;
  gap: 1.5rem;
  font-size: 0.9rem;
  color: #64748b;
  padding: 1rem 2rem;
  background: #ffffff;
  border-radius: 50px;
  border: 1px solid #e2e8f0;
  box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
}
.user-name {
  color: #0f172a;
  font-weight: 600;
}
.portal-logout {
  background: none;
  border: none;
  font-size: 0.85rem;
  font-weight: 600;
  color: #ef4444;
  cursor: pointer;
  padding: 0;
  transition: color 0.2s;
}
.portal-logout:hover { 
  color: #b91c1c; 
  text-decoration: underline;
}
</style>
