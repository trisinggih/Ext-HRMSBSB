/**
 * Konfigurasi akses menu per web role karyawan.
 * 
 * - super_admin: Akses semua menu (tidak perlu daftar, dianggap sama dengan Admin)
 * - staff_hr:    Menu terbatas — kehadiran, operasional, laporan, master data (tanpa gaji)
 * - spv:         Hanya KPI untuk menilai bawahan di divisi yang dikelola
 */

// ======================================================
// STAFF HR — Route names yang boleh diakses
// ======================================================
export const STAFF_HR_ALLOWED_ROUTES = new Set([
  // ---- Umum ----
  'dashboard',
  'sso-portal',
  'notifications',
  'org-chart',

  // ---- Karyawan (terbatas — tanpa tab gaji di detail) ----
  'employees',
  'employee-history',
  'employee-detail',
  'EmployeeMutations',

  // ---- Kehadiran & Operasional ----
  'attendance',
  'live-attendance',
  'kiosk-attendance',
  'overtimes',
  'overtime-history',
  'leave',
  'leave-requests',
  'leave-balances',
  'approval',
  'announcements',

  // ---- Master Data & Aset (aman) ----
  'group-profile',
  'companies',
  'company-detail',
  'branches',
  'departments',
  'divisions',
  'positions',
  'leave-types',
  'shifts',
  'asset-types',
  'assets',
  'asset-requests',
  'asset-request-create',
  'asset-request-edit',
  'asset-repairs',
  'asset-repair-create',
  'asset-repair-edit',
  'asset-approval-settings',
  'kpi-indicators',

  // ---- Laporan (tanpa payroll, tanpa KPI) ----
  'report-employees',
  'report-individual',
  'report-attendance',
  'report-tardiness',
  'report-overtime',
  'report-leaves',
  'report-violations',
])

// ======================================================
// SPV / Kepala / Manager Divisi — Route names yang boleh diakses
// ======================================================
export const SPV_ALLOWED_ROUTES = new Set([
  // ---- Umum ----
  'dashboard',
  'sso-portal',
  'notifications',
  'approval',

  // ---- KPI — periode, penilaian, laporan, dan informasi indikator ----
  'kpi-periods',
  'kpi-period-assessments',
  'kpi-assessments',
  'kpi-assessment-form',
  'kpi-indicators',
  'kpi-templates',
  'kpi-reports',
])

// ======================================================
// Tab di EmployeeDetail yang DISEMBUNYIKAN dari Staff HR
// ======================================================
export const EMPLOYEE_DETAIL_HIDDEN_TABS_STAFF_HR = new Set([
  'payroll',
  'salary-components',
  'tax',
  'loans',
  'rewards',
  'warnings',
  'terminations',
])

// ======================================================
// Helper: apakah route ini boleh diakses oleh role tertentu?
// ======================================================
export function canRoleAccessRoute(webRole, routeName) {
  if (!webRole || webRole === 'admin' || webRole === 'super_admin') return true
  if (webRole === 'staff_hr') return STAFF_HR_ALLOWED_ROUTES.has(routeName)
  if (webRole === 'spv')      return SPV_ALLOWED_ROUTES.has(routeName)
  return false
}

// ======================================================
// Halaman default setelah login per role
// ======================================================
export const ROLE_DEFAULT_ROUTE = {
  admin:       'sso-portal',
  super_admin: 'sso-portal',
  staff_hr:    'dashboard',
  spv:         'kpi-periods',
}
