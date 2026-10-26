import { createRouter, createWebHistory } from 'vue-router'
import { canRoleAccessRoute, ROLE_DEFAULT_ROUTE } from '../config/roleAccess.js'

import Reimbursements from '../components/Reimbursements.vue'
import Warnings from '../components/Warnings.vue'
import Terminations from '../components/Terminations.vue'
import LiveAttendance from '../components/LiveAttendance.vue'
import CanvassingMonitor from '../components/CanvassingMonitor.vue'

const routes = [
  // ---- ROOT: Company Profile (Publik) ----
  {
    path: '/',
    component: () => import('../layouts/PublicHomeLayout.vue'),
    meta: { public: true },
    children: [
      { path: '', name: 'home', component: () => import('../views/public/CompanyHome.vue'), meta: { title: 'Beranda' } },
      { path: 'pages/:slug', name: 'cms-public-page', component: () => import('../views/public/CompanyPage.vue'), meta: { title: 'Halaman' } },
    ]
  },

  // ---- Karier / Front RMS (Publik) ----
  {
    path: '/karier',
    component: () => import('../layouts/PublicLayout.vue'),
    meta: { public: true },
    children: [
      { path: '', name: 'karir', component: () => import('../views/public/Career.vue'), meta: { title: 'Karir' } },
      { path: 'jobs/:id', name: 'karir-job', component: () => import('../views/public/CareerJob.vue'), meta: { title: 'Detail Lowongan' } },
      { path: 'apply', name: 'karir-apply', component: () => import('../views/public/CareerApply.vue'), meta: { title: 'Ajukan Lamaran' } },
      { path: 'status', name: 'karir-status', component: () => import('../views/public/CareerStatus.vue'), meta: { title: 'Cek Status' } },
      { path: 'tests/:id', name: 'karir-test', component: () => import('../views/public/CareerTest.vue'), meta: { title: 'Tes Online' } },
    ],
  },

  // ---- Auth ----
  { path: '/login', name: 'login', component: () => import('../views/Login.vue'), meta: { title: 'Login' } },
  { path: '/hrms/login', name: 'hrms-login', component: () => import('../views/HrmsLogin.vue'), meta: { title: 'Login HRMS' } },

  // ---- PORTAL SSO ----
  {
    path: '/portal',
    component: () => import('../layouts/PortalLayout.vue'),
    meta: { requiresAuth: true, title: 'Portal Aplikasi' },
    children: [
      { path: '', name: 'sso-portal', component: () => import('../views/SsoPortal.vue') }
    ]
  },

  // ---- ROUTE KIOSK ABSENSI ----
  {
    path: '/hrms/kiosk-attendance',
    name: 'kiosk-attendance',
    component: () => import('../views/KioskAttendance.vue'),
    meta: { requiresAuth: true, title: 'Kiosk Absensi' }
  },

  // ---- ROUTE ADMIN HRMS (prefix /hrms) ----
  {
    path: '/hrms',
    component: () => import('../layouts/DefaultLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      { path: '', name: 'dashboard', component: () => import('../views/Dashboard.vue'), meta: { title: 'Dashboard' } },
      { path: 'organization', name: 'org-chart', component: () => import('../views/OrganizationChart.vue'), meta: { title: 'Organization Chart' } },
      { path: 'group-profile', name: 'group-profile', component: () => import('../views/GroupProfile.vue'), meta: { title: 'Profil Grup' } },
      { path: 'companies', name: 'companies', component: () => import('../views/Companies.vue'), meta: { title: 'Perusahaan' } },
      { path: 'companies/:id', name: 'company-detail', component: () => import('../views/CompanyDetail.vue'), meta: { title: 'Detail Perusahaan' } },
      { path: 'branches', name: 'branches', component: () => import('../views/Branches.vue'), meta: { title: 'Cabang' } },
      { path: 'departments', name: 'departments', component: () => import('../views/Departments.vue'), meta: { title: 'Departemen' } },
      { path: 'divisions', name: 'divisions', component: () => import('../views/Divisions.vue'), meta: { title: 'Divisi' } },
      { path: 'positions', name: 'positions', component: () => import('../views/Positions.vue'), meta: { title: 'Jabatan' } },
      { path: 'employees', name: 'employees', component: () => import('../views/Employees.vue'), meta: { title: 'Karyawan' } },
      { path: 'employees/history', name: 'employee-history', component: () => import('../views/EmployeeHistory.vue'), meta: { title: 'History Karyawan' } },
      { path: 'employees/create', name: 'employee-create', component: () => import('../views/EmployeeCreate.vue'), meta: { title: 'Tambah Karyawan' } },
      { path: 'mutations', name: 'EmployeeMutations', component: () => import('../views/EmployeeMutations.vue'), meta: { title: 'Mutasi Karyawan' } },
      { path: 'mutations/create', name: 'mutation-create', component: () => import('../views/EmployeeMutationCreate.vue'), meta: { title: 'Buat Surat Mutasi' } },
      { path: 'employees/:id', name: 'employee-detail', component: () => import('../views/EmployeeDetail.vue'), meta: { title: 'Profil Karyawan' } },
      { path: 'announcements', name: 'announcements', component: () => import('../views/Announcements.vue'), meta: { title: 'Pengumuman / Whistleblower' } },
      { path: 'shifts', name: 'shifts', component: () => import('../views/Shifts.vue'), meta: { title: 'Shift' } },
      { path: 'payroll-components', name: 'payroll-components', component: () => import('../views/PayrollComponents.vue'), meta: { title: 'Komponen Gaji' } },
      { path: 'payroll-inputs', name: 'payroll-inputs', component: () => import('../views/PayrollInputs.vue'), meta: { title: 'Input Gaji' } },
      { path: 'salary-structures', name: 'salary-structures', component: () => import('../views/SalaryStructures.vue'), meta: { title: 'Struktur Gaji' } },
      { path: 'payroll-rewards', name: 'payroll-rewards', component: () => import('../views/PayrollRewards.vue'), meta: { title: 'Bonus & Insentif' } },
      { path: 'payroll-deductions', name: 'payroll-deductions', component: () => import('../views/PayrollDeductions.vue'), meta: { title: 'Potongan Lain-Lain' } },
      { path: 'payroll-periods', name: 'payroll-periods', component: () => import('../views/PayrollPeriods.vue'), meta: { title: 'Periode Payroll' } },
      { path: 'payroll-control', name: 'payroll-control', component: () => import('../views/PayrollControl.vue'), meta: { title: 'Monitoring & Control Payroll' } },
      { path: 'salary-simulation', name: 'salary-simulation', component: () => import('../views/SalarySimulation.vue'), meta: { title: 'Simulasi Gaji' } },
      { path: 'payslips', name: 'payslips', component: () => import('../views/Payslips.vue'), meta: { title: 'Slip Gaji' } },
      { path: 'leave-types', name: 'leave-types', component: () => import('../views/LeaveTypes.vue'), meta: { title: 'Tipe Cuti' } },
      { path: 'attendance', name: 'attendance', component: () => import('../views/Attendance.vue'), meta: { title: 'Kehadiran' } },
      { path: 'overtimes', name: 'overtimes', component: () => import('../views/Overtimes.vue'), meta: { title: 'Pengajuan Lembur' } },
      { path: 'overtimes/history', name: 'overtime-history', component: () => import('../views/OvertimeHistory.vue'), meta: { title: 'History Lembur' } },
      { path: 'leave', name: 'leave', component: () => import('../views/Leave.vue'), meta: { title: 'Cuti & Izin' } },
      { path: 'leave-requests', name: 'leave-requests', component: () => import('../views/LeaveRequests.vue'), meta: { title: 'Permohonan Cuti' } },
      { path: 'leave-balances', name: 'leave-balances', component: () => import('../views/LeaveBalances.vue'), meta: { title: 'Saldo Cuti' } },
      { path: 'loan-requests', name: 'loan-requests', component: () => import('../views/LoanRequests.vue'), meta: { title: 'Pengajuan Kasbon' } },
      { path: 'approval', name: 'approval', component: () => import('../views/Approval.vue'), meta: { title: 'Approval' } },
      { path: 'notifications', name: 'notifications', component: () => import('../views/Notifications.vue'), meta: { title: 'Notifikasi' } },

      // ==== LAPORAN (REPORTS) ====
      { path: 'reports/employees', name: 'report-employees', component: () => import('../views/reports/ReportEmployee.vue'), meta: { title: 'Laporan Data Karyawan' } },
      { path: 'reports/individual', name: 'report-individual', component: () => import('../views/reports/ReportIndividual.vue'), meta: { title: 'Laporan Per Individu' } },
      { path: 'reports/attendance', name: 'report-attendance', component: () => import('../views/reports/ReportAttendance.vue'), meta: { title: 'Laporan Kehadiran' } },
      { path: 'reports/tardiness', name: 'report-tardiness', component: () => import('../views/reports/ReportTardiness.vue'), meta: { title: 'Laporan Keterlambatan' } },
      { path: 'reports/overtime', name: 'report-overtime', component: () => import('../views/reports/ReportOvertime.vue'), meta: { title: 'Laporan Lembur' } },
      { path: 'reports/leaves', name: 'report-leaves', component: () => import('../views/reports/ReportLeave.vue'), meta: { title: 'Laporan Cuti' } },
      { path: 'reports/payroll', name: 'report-payroll', component: () => import('../views/reports/ReportPayroll.vue'), meta: { title: 'Laporan Penggajian' } },
      { path: 'reports/loans', name: 'report-loans', component: () => import('../views/reports/ReportLoan.vue'), meta: { title: 'Laporan Kasbon' } },
      { path: 'reports/kpi-summary', name: 'report-kpi-summary', component: () => import('../views/reports/ReportKpiSummary.vue'), meta: { title: 'Laporan Rekapitulasi KPI' } },
      { path: 'reports/violations', name: 'report-violations', component: () => import('../views/reports/ReportViolations.vue'), meta: { title: 'Laporan SP & Pelanggaran' } },

      // ==== RECRUITMENT (RMS) — ADMIN ====
      { path: 'recruitment', name: 'recruit-dashboard', component: () => import('../views/rms/RmsDashboard.vue'), meta: { title: 'Dashboard (RMS)' } },
      { path: 'recruitment/jobs', name: 'recruit-jobs', component: () => import('../views/rms/Jobs.vue'), meta: { title: 'Lowongan (RMS)' } },
      { path: 'recruitment/tests', name: 'recruit-tests', component: () => import('../views/rms/Tests.vue'), meta: { title: 'Kelola Test (RMS)' } },
      { path: 'recruitment/tests/:id', name: 'recruit-test-questions', component: () => import('../views/rms/TestQuestions.vue'), meta: { title: 'Soal Test (RMS)' } },
      { path: 'recruitment/candidates', name: 'recruit-candidates', component: () => import('../views/rms/Candidates.vue'), meta: { title: 'Kandidat (RMS)' } },
      { path: 'recruitment/candidates/:id', name: 'recruit-candidate-detail', component: () => import('../views/rms/CandidateDetail.vue'), meta: { title: 'Detail Kandidat (RMS)' } },

      // ==== CMS Company Profile — ADMIN ====
      { path: 'cms', name: 'cms-dashboard', component: () => import('../views/cms/CmsDashboard.vue'), meta: { title: 'Dashboard (CMS)' } },
      
      // CMS Custom (Tentang Kami, Keunggulan, dll)
      { path: 'cms/hero', name: 'cms-hero', component: () => import('../views/cms/CmsHero.vue'), meta: { title: 'Banner Utama (CMS)' } },
      { path: 'cms/about', name: 'cms-about', component: () => import('../views/cms/CmsAbout.vue'), meta: { title: 'Tentang Kami (CMS)' } },
      { path: 'cms/advantages', name: 'cms-advantages', component: () => import('../views/cms/CmsAdvantages.vue'), meta: { title: 'Keunggulan (CMS)' } },
      { path: 'cms/marketplaces', name: 'cms-marketplaces', component: () => import('../views/cms/CmsMarketplaces.vue'), meta: { title: 'Marketplace (CMS)' } },
      { path: 'cms/contact', name: 'cms-contact', component: () => import('../views/cms/CmsContact.vue'), meta: { title: 'Kontak (CMS)' } },
      
      // CMS Advanced (Hidden from sidebar)
      { path: 'cms/settings', name: 'cms-settings', component: () => import('../views/cms/CmsSettings.vue'), meta: { title: 'Setting CMS' } },
      { path: 'cms/pages', name: 'cms-pages', component: () => import('../views/cms/CmsPages.vue'), meta: { title: 'Halaman CMS' } },
      { path: 'cms/sections', name: 'cms-sections', component: () => import('../views/cms/CmsSections.vue'), meta: { title: 'Seksi CMS' } },
      { path: 'cms/media', name: 'cms-media', component: () => import('../views/cms/CmsMedia.vue'), meta: { title: 'Media CMS' } },
      
      { path: 'cms/products', name: 'cms-products', component: () => import('../views/cms/CmsProducts.vue'), meta: { title: 'Katalog Produk CMS' } },

      // ==== NEW FEATURES (PHASE 1.7) ====
      { path: 'reimbursements', name: 'reimbursements', component: Reimbursements, meta: { title: 'Reimbursement' } },
      { path: 'warnings', name: 'warnings', component: Warnings, meta: { title: 'Peringatan' } },
      { path: 'terminations', name: 'terminations', component: Terminations, meta: { title: 'Terminasi' } },
      { path: 'live-attendance', name: 'live-attendance', component: LiveAttendance, meta: { title: 'Live Attendance' } },
      { path: 'live-tracking', name: 'live-tracking', component: () => import('../views/LiveTracking.vue'), meta: { title: 'Live Location Tracking' } },
      { path: 'check-points', name: 'check-points', component: () => import('../views/CheckPoints.vue'), meta: { title: 'Check Point Kunjungan' } },
      { path: 'asset-types', name: 'asset-types', component: () => import('../views/AssetTypes.vue'), meta: { title: 'Master Tipe Aset' } },
      { path: 'assets', name: 'assets', component: () => import('../views/Assets.vue'), meta: { title: 'Manajemen Aset' } },
      { path: 'asset-requests', name: 'asset-requests', component: () => import('../views/AssetRequests.vue'), meta: { title: 'Permintaan Pembelian Aset' } },
      { path: 'asset-requests/create', name: 'asset-request-create', component: () => import('../views/AssetRequestForm.vue'), meta: { title: 'Tambah Pengajuan Aset' } },
      { path: 'asset-requests/:id/edit', name: 'asset-request-edit', component: () => import('../views/AssetRequestForm.vue'), meta: { title: 'Ubah Pengajuan Aset' } },
      { path: 'asset-repairs', name: 'asset-repairs', component: () => import('../views/AssetRepairs.vue'), meta: { title: 'Perbaikan Alat Kerja' } },
      { path: 'asset-repairs/create', name: 'asset-repair-create', component: () => import('../views/AssetRepairForm.vue'), meta: { title: 'Pengajuan Perbaikan Alat' } },
      { path: 'asset-repairs/:id/edit', name: 'asset-repair-edit', component: () => import('../views/AssetRepairForm.vue'), meta: { title: 'Ubah Perbaikan Alat' } },
      { path: 'asset-approval-settings', name: 'asset-approval-settings', component: () => import('../views/AssetApprovalSettings.vue'), meta: { title: 'Konfigurasi Approval Aset' } },

      // ==== KPI ====

      { path: 'kpi/indicators', name: 'kpi-indicators', component: () => import('../views/kpi/KpiIndicators.vue'), meta: { title: 'Master Indikator KPI' } },
      { path: 'kpi/rating-scales', name: 'kpi-rating-scales', component: () => import('../views/kpi/KpiRatingScales.vue'), meta: { title: 'Skala Penilaian KPI' } },
      { path: 'kpi/templates', name: 'kpi-templates', component: () => import('../views/kpi/KpiTemplates.vue'), meta: { title: 'Template KPI' } },
      { path: 'kpi/periods', name: 'kpi-periods', component: () => import('../views/kpi/KpiPeriods.vue'), meta: { title: 'Periode Penilaian KPI' } },
      { path: 'kpi/periods/:id/assessments', name: 'kpi-period-assessments', component: () => import('../views/kpi/KpiPeriodDetail.vue'), meta: { title: 'Detail Penilaian KPI Periode' } },
      { path: 'kpi/periods/:id', redirect: to => `/hrms/kpi/periods/${to.params.id}/assessments` },
      { path: 'kpi/assessments', name: 'kpi-assessments', component: () => import('../views/kpi/KpiAssessments.vue'), meta: { title: 'Penilaian KPI' } },
      { path: 'kpi/assessments/:id', name: 'kpi-assessment-form', component: () => import('../views/kpi/KpiAssessmentForm.vue'), meta: { title: 'Form Penilaian KPI' } },
      { path: 'kpi/probation', name: 'kpi-probation', component: () => import('../views/kpi/ProbationEvaluations.vue'), meta: { title: 'KPI & Kenaikan Gaji Probation' } },
      { path: 'kpi/reports', name: 'kpi-reports', component: () => import('../views/kpi/KpiReports.vue'), meta: { title: 'Laporan KPI' } },
    ],
  },

  // fallback
  { path: '/:pathMatch(.*)*', redirect: '/' },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach((to) => {
  const token    = localStorage.getItem('hrms_token')
  const userType = localStorage.getItem('hrms_user_type') || 'admin'
  const webRole  = userType === 'employee'
    ? JSON.parse(localStorage.getItem('hrms_employee') || '{}')?.web_role
    : 'admin'

  // Belum login → redirect ke login sesuai path
  if (to.meta.requiresAuth && !token) {
    if (to.path.startsWith('/hrms')) {
      return { name: 'hrms-login' }
    }
    return { name: 'login' }
  }

  // Sudah login → jangan akses halaman login lagi
  if ((to.name === 'login' || to.name === 'hrms-login') && token) {
    const defaultRoute = ROLE_DEFAULT_ROUTE[webRole] || 'sso-portal'
    return { name: defaultRoute }
  }

  // Employee web login: cek akses berdasarkan role
  if (token && userType === 'employee' && to.meta.requiresAuth) {
    if (!canRoleAccessRoute(webRole, to.name)) {
      // Redirect ke halaman default mereka
      const fallback = ROLE_DEFAULT_ROUTE[webRole] || 'dashboard'
      if (to.name !== fallback) return { name: fallback }
    }
  }
})

export default router
