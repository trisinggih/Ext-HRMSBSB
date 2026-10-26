<template>
  <div id="layout-wrapper">
    <!-- ========== Topbar ========== -->
    <header id="page-topbar" style="left: 0;">
      <div class="navbar-header">
        <div class="d-flex">
          <!-- LOGO -->
          <div class="navbar-brand-box d-flex align-items-center" style="padding: 0 1.5rem; width: auto; background: transparent;">
            <router-link to="/portal" class="logo logo-dark d-flex align-items-center">
              <span class="logo-lg">
                <img :src="companyStore.logoUrl || '/assets/images/logo-dark.png'" alt="BSB Village" height="34" style="object-fit: contain; max-height: 38px;">
              </span>
            </router-link>
            <router-link to="/portal" class="logo logo-light d-flex align-items-center">
              <span class="logo-lg">
                <img src="/assets/images/logo-light.png" alt="BSB Village" height="34" style="object-fit: contain; max-height: 38px;">
              </span>
            </router-link>
          </div>
          <div class="page-title-box align-self-center ms-3 d-none d-md-block">
            <h4 class="page-title mb-0">BSB Village Portal</h4>
          </div>
        </div>

        <div class="d-flex">
          <!-- Fullscreen -->
          <div class="dropdown d-none d-lg-inline-block ms-1">
            <button type="button" class="btn header-item noti-icon waves-effect" data-toggle="fullscreen">
              <i class="ri-fullscreen-line"></i>
            </button>
          </div>

          <!-- User Profile Dropdown -->
          <div class="dropdown d-inline-block user-dropdown">
            <button type="button" class="btn header-item waves-effect"
              id="page-header-user-dropdown"
              data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
              <div class="d-flex align-items-center">
                <div class="avatar-xs rounded-circle flex-shrink-0">
                  <div class="avatar-title border bg-light text-primary rounded-circle text-uppercase">
                    {{ userInitial }}
                  </div>
                </div>
                <span class="d-none d-xl-inline-block ms-2 fw-medium font-size-15">{{ auth.admin?.name }}</span>
                <i class="uil-angle-down d-none d-xl-inline-block font-size-15"></i>
              </div>
            </button>
            <div class="dropdown-menu dropdown-menu-end">
              <div class="dropdown-header noti-title">
                <h5 class="font-size-13 text-muted fw-normal">
                  <span class="d-block">{{ auth.admin?.name }}</span>
                  <span class="text-muted font-size-12">{{ auth.admin?.role }}</span>
                </h5>
              </div>
              <div class="dropdown-divider"></div>
              <a class="dropdown-item" href="#" @click.prevent="logout">
                <i class="mdi mdi-logout text-muted font-size-16 align-middle me-1"></i>
                <span class="align-middle">Keluar</span>
              </a>
            </div>
          </div>
        </div>
      </div>
    </header>

    <!-- ========== Main Content ========== -->
    <div class="main-content" style="margin-left: 0; padding-top: 0px;">
      <div class="page-content bg-light" style="min-height: calc(80vh - 50px); display: flex; align-items: center; justify-content: center; padding-bottom: 0px;margin-top: 0px;">
        <div class="container-fluid">
          <router-view />
        </div>
      </div>

      <footer class="footer" style="left: 0;">
        <div class="container-fluid">
          <div class="row">
            <div class="col-sm-6">
              {{ new Date().getFullYear() }} © {{ companyStore.companyName }}
            </div>
            <div class="col-sm-6">
              <div class="text-sm-end d-none d-sm-block">
                SSO Application Portal
              </div>
            </div>
          </div>
        </div>
      </footer>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useCompanyStore } from '../stores/companyStore'

const router = useRouter()
const auth = useAuthStore()
const companyStore = useCompanyStore()

const userInitial = computed(() => {
  const name = auth.admin?.name || ''
  return name.charAt(0).toUpperCase() || 'U'
})

async function logout() {
  const target = await auth.logout()
  router.push(target || '/hrms/login')
}

onMounted(async () => {
  await nextTick()
  if (!companyStore.company) {
    companyStore.fetchCompany()
  }
})
</script>
