<template>
  <div class="auth-wrapper d-flex align-items-center justify-content-center min-vh-100 py-4">
    <!-- Ambient Glow Orb -->
    <div class="auth-glow-orb"></div>

    <div class="auth-card-container">
      <div class="card auth-card border-0 overflow-hidden">
        <!-- Top Accent Hairline -->
        <div class="auth-accent-line"></div>

        <div class="card-body p-4 p-sm-5">
          <!-- Logo & Header -->
          <div class="text-center mb-4">
            <div class="logo-wrapper mb-3">
              <div class="brand-badge">
                <img
                  :src="effectiveLogo || '/assets/images/logo-dark.png'"
                  :alt="companyName || 'BSB Village'"
                  class="company-logo"
                  @error="handleLogoError"
                />
              </div>
            </div>

            <h4 class="auth-title mb-1">Portal Karyawan</h4>
            <p class="auth-subtitle mb-0 text-muted font-size-13">BSB Village Real Estate Management</p>
          </div>

          <!-- Error Alert -->
          <div v-if="error" class="alert alert-danger border-0 rounded-3 py-2 px-3 mb-3 font-size-13" role="alert">
            {{ error }}
          </div>

          <!-- Form Login Unified -->
          <form @submit.prevent="onLogin" class="auth-form">
            <div class="mb-3">
              <label for="emp-identifier-input" class="form-label auth-label">
                Email atau ID Karyawan
              </label>
              <input
                id="emp-identifier-input"
                v-model.trim="empIdentifier"
                type="text"
                class="form-control auth-input-field w-100"
                placeholder="Email atau ID Karyawan"
                required
                autocomplete="username"
                :disabled="loading"
              />
            </div>

            <div class="mb-4">
              <label for="emp-password-input" class="form-label auth-label">
                Password
              </label>
              <div class="position-relative">
                <input
                  id="emp-password-input"
                  v-model="empPassword"
                  :type="showEmpPassword ? 'text' : 'password'"
                  class="form-control auth-input-field w-100 pe-5"
                  placeholder="Password"
                  required
                  autocomplete="current-password"
                  :disabled="loading"
                />
                <button
                  type="button"
                  class="btn-toggle-password"
                  @click="showEmpPassword = !showEmpPassword"
                  tabindex="-1"
                  aria-label="Toggle password"
                >
                  {{ showEmpPassword ? 'Sembunyi' : 'Lihat' }}
                </button>
              </div>
            </div>

            <button
              id="btn-login-submit"
              type="submit"
              class="btn btn-submit w-100"
              :disabled="loading"
            >
              <span v-if="loading" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
              {{ loading ? 'Memverifikasi...' : 'Masuk' }}
            </button>
          </form>

        </div>
      </div>
      
      <!-- Minimalist Footer -->
      <div class="text-center mt-3 text-muted auth-footer">
        &copy; {{ new Date().getFullYear() }} {{ companyName || 'HRMS' }}
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useCompanyStore } from '../stores/companyStore'
import { ROLE_DEFAULT_ROUTE } from '../config/roleAccess.js'

const router       = useRouter()
const auth         = useAuthStore()
const companyStore = useCompanyStore()

const error           = ref('')
const loading         = ref(false)
const showEmpPassword = ref(false)
const logoFailed      = ref(false)

const empIdentifier   = ref('')
const empPassword     = ref('')

const companyName  = computed(() => companyStore.companyName)
const effectiveLogo = computed(() => {
  if (logoFailed.value) return null
  return companyStore.logoUrl
})

onMounted(async () => {
  try {
    await companyStore.fetchCompany()
  } catch (e) {
    console.error('Failed to load company logo', e)
  }
})

function handleLogoError() {
  logoFailed.value = true
}

async function onLogin() {
  if (!empIdentifier.value || !empPassword.value) return
  loading.value = true
  error.value   = ''
  try {
    const data = await auth.loginUnified(empIdentifier.value, empPassword.value, 'leadership')

    if (data.user_type === 'admin') {
      router.push('/portal')
    } else {
      const emp = data.employee
      if (data.is_head || emp?.is_head || emp?.web_role === 'spv') {
        const defaultRoute = ROLE_DEFAULT_ROUTE['spv'] || 'kpi-assessments'
        router.push({ name: defaultRoute })
      } else {
        const role = emp?.web_role || 'staff_hr'
        const defaultRoute = ROLE_DEFAULT_ROUTE[role] || 'dashboard'
        router.push({ name: defaultRoute })
      }
    }
  } catch (e) {
    error.value = e.response?.data?.errors?.email?.[0]
      || e.response?.data?.message
      || 'Kredensial tidak valid. Silakan periksa kembali email/ID dan password.'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
/* Base Wrapper & Ambient Backdrop */
.auth-wrapper {
  position: relative;
  background-color: #f8fafc;
  min-height: 100vh;
  overflow: hidden;
}

.auth-wrapper::before {
  content: '';
  position: absolute;
  inset: 0;
  background-image: radial-gradient(rgba(148, 163, 184, 0.12) 1px, transparent 1px);
  background-size: 24px 24px;
  pointer-events: none;
}

.auth-glow-orb {
  position: absolute;
  width: 500px;
  height: 500px;
  border-radius: 50%;
  filter: blur(110px);
  pointer-events: none;
  opacity: 0.65;
  top: -150px;
  left: 50%;
  transform: translateX(-50%);
  background: radial-gradient(circle, rgba(18, 77, 50, 0.22) 0%, rgba(122, 149, 68, 0.1) 50%, transparent 72%);
}

.auth-card-container {
  position: relative;
  z-index: 2;
  width: 100%;
  max-width: 410px;
  padding: 16px;
}

/* Premium Card */
.auth-card {
  background: #ffffff;
  border: 1px solid rgba(18, 77, 50, 0.14) !important;
  border-radius: 16px !important;
  box-shadow: 
    0 1px 3px rgba(15, 23, 42, 0.03),
    0 12px 28px -4px rgba(18, 77, 50, 0.08),
    0 24px 36px -8px rgba(18, 77, 50, 0.04) !important;
}

/* Accent Hairline */
.auth-accent-line {
  height: 4px;
  width: 100%;
  background: linear-gradient(90deg, #0e4026 0%, #7a9544 100%);
}

/* Logo Presentation */
.logo-wrapper {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 48px;
}

.brand-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: #ffffff;
  padding: 8px 22px;
  border-radius: 12px;
  border: 1px solid rgba(18, 77, 50, 0.15);
  box-shadow: 0 3px 12px rgba(18, 77, 50, 0.06);
  transition: all 0.2s ease;
}

.brand-badge:hover {
  border-color: rgba(18, 77, 50, 0.3);
  box-shadow: 0 6px 18px rgba(18, 77, 50, 0.1);
}

.company-logo {
  max-height: 42px;
  max-width: 190px;
  width: auto;
  height: auto;
  object-fit: contain;
  display: block;
}

.brand-fallback-text {
  color: #0b3820;
  font-family: 'Tenor Sans', serif;
  font-size: 16px;
  font-weight: 700;
  letter-spacing: 0.04em;
}

/* Typography */
.auth-title {
  font-size: 19px;
  font-weight: 700;
  color: #0f172a;
  letter-spacing: -0.02em;
}

/* Form Controls */
.auth-label {
  display: block;
  font-size: 12.5px;
  font-weight: 600;
  color: #334155;
  margin-bottom: 6px;
  letter-spacing: 0.01em;
}

.auth-input-field {
  background-color: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  font-size: 13.5px;
  padding: 10px 14px;
  color: #0f172a;
  transition: all 0.2s ease;
}

.auth-input-field:focus {
  background-color: #ffffff;
  border-color: #124d32;
  box-shadow: 0 0 0 3px rgba(18, 77, 50, 0.14);
}

.auth-input-field::placeholder {
  color: #94a3b8;
  font-size: 13px;
}

/* Password Toggle Text Button */
.btn-toggle-password {
  position: absolute;
  top: 50%;
  right: 12px;
  transform: translateY(-50%);
  background: transparent;
  border: none;
  padding: 3px 6px;
  font-size: 11.5px;
  font-weight: 600;
  color: #64748b;
  cursor: pointer;
  border-radius: 4px;
  line-height: 1;
  transition: all 0.15s ease;
}

.btn-toggle-password:hover {
  color: #124d32;
  background-color: rgba(18, 77, 50, 0.08);
}

/* Submit Button */
.btn-submit {
  border-radius: 10px;
  padding: 11px 16px;
  font-size: 14px;
  font-weight: 600;
  letter-spacing: 0.01em;
  background: linear-gradient(135deg, #124d32 0%, #1e6b47 100%);
  color: #ffffff;
  border: none;
  box-shadow: 0 3px 8px rgba(18, 77, 50, 0.22);
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.btn-submit:hover:not(:disabled) {
  background: linear-gradient(135deg, #0e3d27 0%, #155537 100%);
  color: #ffffff;
  box-shadow: 0 5px 16px rgba(18, 77, 50, 0.32);
  transform: translateY(-1px);
}

.btn-submit:active:not(:disabled) {
  background-color: #0b3320;
  color: #ffffff;
  transform: translateY(0);
}

/* Footer */
.auth-footer {
  font-size: 11.5px;
  color: #94a3b8;
  letter-spacing: 0.01em;
}
</style>
