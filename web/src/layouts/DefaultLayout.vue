<template>
  <div id="layout-wrapper">

    <!-- ========== Topbar ========== -->
    <header id="page-topbar">
      <div class="navbar-header">
        <div class="d-flex">
          <!-- LOGO -->
          <div class="navbar-brand-box">
            <router-link to="/hrms" class="logo logo-dark">
              <span class="logo-sm">
                <img :src="companyStore.logoSmUrl" :alt="companyStore.companyName" height="28" style="object-fit: contain;">
              </span>
              <span class="logo-lg">
                <img :src="companyStore.logoUrl" :alt="companyStore.companyName" height="34" style="object-fit: contain; max-height: 38px;">
              </span>
            </router-link>
            <router-link to="/hrms" class="logo logo-light">
              <span class="logo-sm">
                <img :src="companyStore.logoSmUrl" :alt="companyStore.companyName" height="28" style="object-fit: contain;">
              </span>
              <span class="logo-lg">
                <img :src="companyStore.logoLightUrl" :alt="companyStore.companyName" height="34" style="object-fit: contain; max-height: 38px;">
              </span>
            </router-link>
          </div>

          <button type="button" class="btn btn-sm px-3 font-size-24 d-lg-none header-item" data-bs-toggle="collapse" data-bs-target="#topnav-menu-content">
            <i class="ri-menu-2-line align-middle"></i>
          </button>

          <!-- Page Title -->
          <div class="page-title-box align-self-center d-none d-md-block">
            <h4 class="page-title mb-0">{{ $route.meta.title || 'Dashboard' }}</h4>
          </div>
        </div>

        <div class="d-flex">
          <!-- Search -->
          <form class="app-search d-none d-lg-block">
            <div class="position-relative">
              <input type="text" class="form-control" placeholder="Cari...">
              <span class="ri-search-line"></span>
            </div>
          </form>

          <!-- Fullscreen -->
          <div class="dropdown d-none d-lg-inline-block ms-1">
            <button type="button" class="btn header-item noti-icon waves-effect" data-toggle="fullscreen">
              <i class="ri-fullscreen-line"></i>
            </button>
          </div>

          <!-- Notifications -->
          <div class="dropdown d-inline-block">
            <button type="button" class="btn header-item noti-icon waves-effect"
              id="page-header-notifications-dropdown"
              data-bs-toggle="dropdown" aria-expanded="false">
              <i class="ri-notification-3-line"></i>
              <span class="noti-dot" v-if="hasNotifications"></span>
            </button>
            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end p-0"
              aria-labelledby="page-header-notifications-dropdown">
              <div class="p-3">
                <div class="row align-items-center">
                  <div class="col">
                    <h6 class="m-0">Notifikasi</h6>
                  </div>
                  <div class="col-auto">
                    <router-link to="/hrms/notifications" class="small">Lihat Semua</router-link>
                  </div>
                </div>
              </div>
              <div class="p-2 border-top">
                <div class="d-grid">
                  <router-link to="/hrms/notifications" class="btn btn-sm btn-link font-size-14 text-center">
                    <i class="mdi mdi-arrow-right-circle me-1"></i> Lihat Semua Notifikasi
                  </router-link>
                </div>
              </div>
            </div>
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
                <span class="d-none d-xl-inline-block ms-2 fw-medium font-size-15">{{ auth.currentUserName }}</span>
                <i class="uil-angle-down d-none d-xl-inline-block font-size-15"></i>
              </div>
            </button>
            <div class="dropdown-menu dropdown-menu-end">
              <div class="dropdown-header noti-title">
                <h5 class="font-size-13 text-muted fw-normal">
                  <span class="d-block">{{ auth.currentUserName }}</span>
                  <span class="text-muted font-size-12">{{ auth.currentUserRoleLabel }}</span>
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
    <!-- ========== End Topbar ========== -->

    <!-- ========== Topnav (Horizontal Menu) ========== -->
    <div class="topnav">
      <div class="container-fluid">
        <nav class="navbar navbar-light navbar-expand-lg topnav-menu">
          <div class="collapse navbar-collapse" id="topnav-menu-content">
            <ul class="navbar-nav">
              
              <!-- Tombol Kembali ke Portal -->
              <li class="nav-item">
                <router-link class="nav-link fw-bold text-primary" to="/portal">
                  <i class="uim uim-apps"></i> SSO Portal
                </router-link>
              </li>

              <!-- ================= HRMS MENUS ================= -->
              <template v-if="currentModule === 'hrms'">
                <!-- Menu Khusus Kepala / Supervisor (Level Head, SPV, Manager) -->
                <template v-if="auth.isSpv">
                  <li class="nav-item">
                    <router-link class="nav-link" to="/hrms/kpi/periods" :class="{ active: isActive('/hrms/kpi/periods') }">
                      <i class="uim uim-calendar-alt"></i> Periode KPI
                    </router-link>
                  </li>
                  <li class="nav-item">
                    <router-link class="nav-link" to="/hrms/approval" :class="{ active: isActive('/hrms/approval') }">
                      <i class="uim uim-check-circle"></i> Approval Bawahan
                    </router-link>
                  </li>
                  <li class="nav-item">
                    <router-link class="nav-link" to="/hrms/kpi/indicators" :class="{ active: isActive('/hrms/kpi/indicators') }">
                      <i class="uim uim-document-layout-left"></i> Indikator KPI
                    </router-link>
                  </li>
                </template>

                <!-- Menu Lengkap Tim HRD & Administrator -->
                <template v-else>
                <li class="nav-item">
                  <router-link class="nav-link" to="/hrms" :class="{ active: isActive('/hrms') }">
                    <i class="uim uim-airplay"></i> Dashboard HR
                  </router-link>
                </li>

                <li class="nav-item dropdown">
                  <a class="nav-link dropdown-toggle arrow-none" href="#" id="topnav-master" role="button">
                    <i class="uim uim-layer-group"></i> Master Data <div class="arrow-down"></div>
                  </a>
                  <div class="dropdown-menu" aria-labelledby="topnav-master">
                    <div class="dropdown">
                      <a class="dropdown-item dropdown-toggle arrow-none" href="#" id="topnav-company" role="button">
                        Perusahaan <div class="arrow-down"></div>
                      </a>
                      <div class="dropdown-menu" aria-labelledby="topnav-company">
                        <router-link to="/hrms/group-profile" class="dropdown-item">Grup Perusahaan</router-link>
                        <router-link to="/hrms/companies" class="dropdown-item">List Perusahaan</router-link>
                        <router-link to="/hrms/branches" class="dropdown-item">Cabang</router-link>
                      </div>
                    </div>
                    <div class="dropdown">
                      <a class="dropdown-item dropdown-toggle arrow-none" href="#" id="topnav-sdm" role="button">
                        Struktur SDM <div class="arrow-down"></div>
                      </a>
                      <div class="dropdown-menu" aria-labelledby="topnav-sdm">
                        <router-link to="/hrms/departments" class="dropdown-item">Departemen</router-link>
                        <router-link to="/hrms/divisions" class="dropdown-item">Divisi</router-link>
                        <router-link to="/hrms/positions" class="dropdown-item">Jabatan</router-link>
                      </div>
                    </div>
                    <router-link to="/hrms/employees" class="dropdown-item">Karyawan</router-link>
                    <router-link to="/hrms/shifts" class="dropdown-item">Shift Kerja</router-link>
                    <router-link to="/hrms/leave-types" class="dropdown-item">Tipe Cuti</router-link>
                  </div>
                </li>

                <!-- Menu Manajemen Aset (Menu Utama Sendiri) -->
                <li class="nav-item dropdown">
                  <a class="nav-link dropdown-toggle arrow-none" href="#" id="topnav-asset-management" role="button">
                    <i class="uim uim-box"></i> Manajemen Aset <div class="arrow-down"></div>
                  </a>
                  <div class="dropdown-menu" aria-labelledby="topnav-asset-management">
                    <router-link to="/hrms/assets" class="dropdown-item">Data Aset & Stok</router-link>
                    <router-link to="/hrms/asset-requests" class="dropdown-item">Pengajuan & Inventaris</router-link>
                    <router-link to="/hrms/asset-repairs" class="dropdown-item">Perbaikan Alat Kerja</router-link>
                    <router-link to="/hrms/asset-approval-settings" class="dropdown-item">Konfigurasi Approval</router-link>
                    <router-link to="/hrms/asset-types" class="dropdown-item">Kategori / Jenis Aset</router-link>
                  </div>
                </li>

                <!-- Menu Payroll — hanya Super Admin / Admin -->
                <li v-if="auth.isSuperAdmin" class="nav-item dropdown">
                  <a class="nav-link dropdown-toggle arrow-none" href="#" id="topnav-payroll" role="button">
                    <i class="uim uim-chart-pie"></i> Payroll <div class="arrow-down"></div>
                  </a>
                  <div class="dropdown-menu" aria-labelledby="topnav-payroll">
                    <div class="dropdown-header">Master Data & Konfigurasi</div>
                    <router-link to="/hrms/payroll-components" class="dropdown-item">Komponen Gaji</router-link>
                    <div class="dropdown-divider"></div>
                    <div class="dropdown-header">Operasional & Control</div>
                    <router-link to="/hrms/payroll-control" class="dropdown-item">Control & Audit Payroll</router-link>
                    <router-link to="/hrms/salary-simulation" class="dropdown-item">Simulasi Gaji</router-link>
                    <router-link to="/hrms/loan-requests" class="dropdown-item">Pengajuan Kasbon</router-link>
                    <router-link :to="{ path: '/hrms/payroll-periods', query: { cycle: 'daily' } }" class="dropdown-item">Gaji Harian</router-link>
                    <router-link :to="{ path: '/hrms/payroll-periods', query: { cycle: 'monthly' } }" class="dropdown-item">Gaji Bulanan</router-link>
                    <router-link to="/hrms/payroll-rewards" class="dropdown-item">Bonus & Insentif</router-link>
                    <router-link to="/hrms/payroll-deductions" class="dropdown-item">Potongan Lain-Lain</router-link>
                    <div class="dropdown-divider"></div>
                    <div class="dropdown-header">Laporan</div>
                    <router-link to="/hrms/payslips" class="dropdown-item">Riwayat & Slip Gaji</router-link>
                  </div>
                </li>

                <li class="nav-item dropdown">
                  <a class="nav-link dropdown-toggle arrow-none" href="#" id="topnav-kehadiran" role="button">
                    <i class="uim uim-clock"></i> Kehadiran & Cuti <div class="arrow-down"></div>
                  </a>
                  <div class="dropdown-menu" aria-labelledby="topnav-kehadiran">
                    <router-link to="/hrms/attendance" class="dropdown-item">Kehadiran (Harian)</router-link>
                    <router-link to="/hrms/check-points" class="dropdown-item">Check Point (Kunjungan Sales)</router-link>
                    <div class="dropdown">
                      <a class="dropdown-item dropdown-toggle arrow-none" href="#" id="topnav-lembur" role="button">
                        Manage Lembur <div class="arrow-down"></div>
                      </a>
                      <div class="dropdown-menu" aria-labelledby="topnav-lembur">
                        <router-link to="/hrms/overtimes" class="dropdown-item">Pengajuan Lembur</router-link>
                        <router-link to="/hrms/overtimes/history" class="dropdown-item">History Lembur</router-link>
                      </div>
                    </div>
                    <div class="dropdown">
                      <a class="dropdown-item dropdown-toggle arrow-none" href="#" id="topnav-cuti" role="button">
                        Cuti & Izin <div class="arrow-down"></div>
                      </a>
                      <div class="dropdown-menu" aria-labelledby="topnav-cuti">
                        <router-link to="/hrms/leave-requests" class="dropdown-item">Permohonan Cuti</router-link>
                        <router-link to="/hrms/leave-balances" class="dropdown-item">Saldo Cuti (Admin)</router-link>
                        <router-link to="/hrms/approval" class="dropdown-item">Persetujuan (Bawahan)</router-link>
                      </div>
                    </div>
                  </div>
                </li>

                <li class="nav-item dropdown">
                  <a class="nav-link dropdown-toggle arrow-none" href="#" id="topnav-operasional" role="button">
                    <i class="uim uim-briefcase"></i> Operasional <div class="arrow-down"></div>
                  </a>
                  <div class="dropdown-menu" aria-labelledby="topnav-operasional">
                    <router-link to="/hrms/live-tracking" class="dropdown-item">Live Location</router-link>
                    <router-link to="/hrms/check-points" class="dropdown-item">Check Point Kunjungan</router-link>
                    <router-link to="/hrms/mutations" class="dropdown-item">Mutasi & Promosi</router-link>
                    <div class="dropdown">
                      <a class="dropdown-item dropdown-toggle arrow-none" href="#" id="topnav-peringatan" role="button">
                        Peringatan & PHK <div class="arrow-down"></div>
                      </a>
                      <div class="dropdown-menu" aria-labelledby="topnav-peringatan">
                        <router-link to="/hrms/warnings" class="dropdown-item">Peringatan (SP)</router-link>
                        <router-link to="/hrms/terminations" class="dropdown-item">Terminasi / Resign</router-link>
                      </div>
                    </div>
                    <router-link to="/hrms/reimbursements" class="dropdown-item">Reimbursement</router-link>
                    <router-link to="/hrms/announcements" class="dropdown-item">Pengumuman (Broadcast)</router-link>
                  </div>
                </li>

                <li class="nav-item dropdown">
                  <a class="nav-link dropdown-toggle arrow-none" href="#" id="topnav-laporan" role="button">
                    <i class="uim uim-document-layout-center"></i> Laporan <div class="arrow-down"></div>
                  </a>
                  <div class="dropdown-menu" aria-labelledby="topnav-laporan">
                    <div class="dropdown-header text-uppercase text-muted font-size-11">Kepegawaian</div>
                    <router-link to="/hrms/reports/employees" class="dropdown-item">Laporan Data Karyawan</router-link>
                    <router-link to="/hrms/employees/history" class="dropdown-item">History Karyawan</router-link>
                    <router-link to="/hrms/reports/individual" class="dropdown-item">Laporan Per Individu (360°)</router-link>
                    
                    <div class="dropdown-divider"></div>
                    <div class="dropdown-header text-uppercase text-muted font-size-11">Kehadiran & Jam Kerja</div>
                    <router-link to="/hrms/reports/attendance" class="dropdown-item">Laporan Kehadiran</router-link>
                    <router-link to="/hrms/reports/tardiness" class="dropdown-item">Laporan Keterlambatan</router-link>
                    <router-link to="/hrms/reports/overtime" class="dropdown-item">Laporan Lembur</router-link>
                    <router-link to="/hrms/reports/leaves" class="dropdown-item">Laporan Cuti & Izin</router-link>

                    <div class="dropdown-divider"></div>
                    <div class="dropdown-header text-uppercase text-muted font-size-11">Evaluasi & Pelanggaran</div>
                    <router-link v-if="auth.isSuperAdmin" to="/hrms/reports/kpi-summary" class="dropdown-item">Laporan Rekapitulasi KPI</router-link>
                    <router-link to="/hrms/reports/violations" class="dropdown-item">Laporan SP & Pelanggaran</router-link>

                    <template v-if="auth.isSuperAdmin">
                      <div class="dropdown-divider"></div>
                      <div class="dropdown-header text-uppercase text-muted font-size-11">Payroll & Financial</div>
                      <router-link to="/hrms/reports/payroll" class="dropdown-item">Laporan Penggajian</router-link>
                      <router-link to="/hrms/reports/loans" class="dropdown-item">Laporan Kasbon</router-link>
                    </template>
                  </div>
                </li>

                <!-- Menu KPI — HANYA Manager, Supervisor & Admin (Tidak boleh di level staff) -->
                <li v-if="auth.isSuperAdmin" class="nav-item dropdown">
                  <a class="nav-link dropdown-toggle arrow-none" href="#" id="topnav-kpi" role="button">
                    <i class="uim uim-chart-bar"></i> KPI <div class="arrow-down"></div>
                  </a>
                  <div class="dropdown-menu" aria-labelledby="topnav-kpi">
                    <!-- Master KPI — hanya Super Admin / Admin -->
                    <div class="dropdown">
                      <a class="dropdown-item dropdown-toggle arrow-none" href="#" id="topnav-kpi-master" role="button">
                        Master Data <div class="arrow-down"></div>
                      </a>
                      <div class="dropdown-menu" aria-labelledby="topnav-kpi-master">
                        <router-link to="/hrms/kpi/indicators" class="dropdown-item">Indikator & Poin Penilaian</router-link>
                        <router-link to="/hrms/kpi/rating-scales" class="dropdown-item">Skala Penilaian</router-link>
                        <router-link to="/hrms/kpi/templates" class="dropdown-item">Template KPI</router-link>
                      </div>
                    </div>
                    <router-link to="/hrms/kpi/periods" class="dropdown-item">Periode Penilaian KPI</router-link>
                    <router-link to="/hrms/kpi/assessments" class="dropdown-item">Penilaian KPI Karyawan</router-link>
                    <router-link to="/hrms/kpi/probation" class="dropdown-item">KPI & Kenaikan Gaji Probation</router-link>
                    <router-link to="/hrms/kpi/reports" class="dropdown-item">Rekapitulasi Hasil KPI</router-link>
                  </div>
                </li>

                <!-- Lainnya — hanya Super Admin / Admin -->
                <li v-if="auth.isSuperAdmin" class="nav-item dropdown">
                  <a class="nav-link dropdown-toggle arrow-none" href="#" id="topnav-more" role="button">
                    <i class="uim uim-box"></i> Lainnya <div class="arrow-down"></div>
                  </a>
                  <div class="dropdown-menu" aria-labelledby="topnav-more">
                    <router-link to="/hrms/organization" class="dropdown-item">Struktur Organisasi</router-link>
                    <router-link to="/hrms/approval" class="dropdown-item">Approval</router-link>
                    <router-link to="/hrms/notifications" class="dropdown-item">Notifikasi</router-link>
                  </div>
                </li>
                </template>
              </template>

              <!-- ================= CMS MENUS ================= -->
              <template v-if="currentModule === 'cms'">
                <li class="nav-item">
                  <router-link class="nav-link" to="/hrms/cms" :class="{ active: route.path === '/hrms/cms' }">
                    <i class="uim uim-airplay"></i> Dashboard CMS
                  </router-link>
                </li>
                
                <li class="nav-item dropdown">
                  <a class="nav-link dropdown-toggle arrow-none" href="#" id="topnav-cms-content" role="button">
                    <i class="uim uim-document-layout-left"></i> Konten Website <div class="arrow-down"></div>
                  </a>
                  <div class="dropdown-menu" aria-labelledby="topnav-cms-content">
                    <router-link to="/hrms/cms/hero" class="dropdown-item">Banner Utama</router-link>
                    <router-link to="/hrms/cms/about" class="dropdown-item">Tentang BSB Village</router-link>
                    <router-link to="/hrms/cms/advantages" class="dropdown-item">Keunggulan & Fasilitas</router-link>
                    <router-link to="/hrms/cms/contact" class="dropdown-item">Informasi Kontak & Lokasi</router-link>
                  </div>
                </li>
              </template>


              <!-- ================= RMS MENUS ================= -->
              <template v-if="currentModule === 'rms'">
                <li class="nav-item">
                  <router-link class="nav-link" to="/hrms/recruitment" :class="{ active: route.path === '/hrms/recruitment' }">
                    <i class="uim uim-airplay"></i> Dashboard RMS
                  </router-link>
                </li>

                <li class="nav-item">
                  <router-link class="nav-link" to="/hrms/recruitment/jobs" :class="{ active: isActive('/hrms/recruitment/jobs') }">
                    <i class="uim uim-briefcase"></i> Lowongan
                  </router-link>
                </li>

                <li class="nav-item">
                  <router-link class="nav-link" to="/hrms/recruitment/candidates" :class="{ active: isActive('/hrms/recruitment/candidates') }">
                    <i class="uim uim-users-alt"></i> Kandidat
                  </router-link>
                </li>

                <li class="nav-item">
                  <router-link class="nav-link" to="/hrms/recruitment/tests" :class="{ active: isActive('/hrms/recruitment/tests') }">
                    <i class="uim uim-document-layout-left"></i> Kelola Test
                  </router-link>
                </li>
              </template>
            </ul>
          </div>
        </nav>
      </div>
    </div>
    <!-- ========== End Topnav ========== -->

    <!-- ========== Main Content ========== -->
    <div class="main-content">
      <div class="page-content">
        <div :class="['org-chart', 'live-tracking'].includes(route.name) ? 'px-0' : 'container-fluid'">
          <router-view />
        </div>
      </div>

      <!-- Footer -->
      <footer class="footer">
        <div class="container-fluid">
          <div class="row">
            <div class="col-sm-6">
              {{ new Date().getFullYear() }} © {{ companyStore.companyName }}
            </div>
            <div class="col-sm-6">
              <div class="text-sm-end d-none d-sm-block">
                Human Resource Management System
              </div>
            </div>
          </div>
        </div>
      </footer>
    </div>
    <!-- ========== End Main Content ========== -->

  </div>
</template>

<script setup>
import { computed, onMounted, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useCompanyStore } from '../stores/companyStore'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const companyStore = useCompanyStore()

const hasNotifications = computed(() => false)

const userInitial = computed(() => {
  const name = auth.currentUserName || ''
  return name.charAt(0).toUpperCase() || 'U'
})

const currentModule = computed(() => {
  if (route.path.startsWith('/hrms/cms')) return 'cms'
  if (route.path.startsWith('/hrms/recruitment')) return 'rms'
  return 'hrms'
})

function isActive(to) {
  return to === '/hrms' ? route.path === '/hrms' || route.path === '/hrms/' : route.path.startsWith(to)
}

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

<style scoped>
/* Optional styling to ensure proper spacing if needed */
.topnav {
    margin-top: 70px; /* Offset for topbar */
}
</style>


