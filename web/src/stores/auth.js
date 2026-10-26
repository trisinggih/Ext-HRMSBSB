import { defineStore } from 'pinia'
import api from '../api'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    token:    localStorage.getItem('hrms_token') || '',
    admin:    JSON.parse(localStorage.getItem('hrms_admin') || 'null'),
    employee: JSON.parse(localStorage.getItem('hrms_employee') || 'null'),
    userType: localStorage.getItem('hrms_user_type') || 'admin', // 'admin' | 'employee'
  }),
  getters: {
    isLoggedIn:   (s) => !!s.token,
    webRole:      (s) => s.userType === 'employee' ? (s.employee?.web_role || null) : 'admin',
    isSuperAdmin: (s) => s.userType === 'admin' || s.employee?.web_role === 'super_admin',
    isStaffHr:    (s) => s.userType === 'employee' && s.employee?.web_role === 'staff_hr',
    isSpv:        (s) => s.userType === 'employee' && s.employee?.web_role === 'spv',
    // Data yang ditampilkan di topbar (nama, initial, role label)
    currentUser:  (s) => s.userType === 'admin' ? s.admin : s.employee,
    currentUserName: (s) => s.userType === 'admin' ? s.admin?.name : s.employee?.name,
    currentUserRoleLabel: (s) => {
      if (s.userType === 'admin') return s.admin?.role || 'Admin'
      if (s.employee?.position) return s.employee.position
      const roleMap = { super_admin: 'Super Admin HR', staff_hr: 'Staff HR', spv: 'SPV / Kepala' }
      return roleMap[s.employee?.web_role] || s.employee?.web_role || 'Karyawan'
    },
    managedDivisionIds: (s) => s.employee?.managed_division_ids ?? [],
  },
  actions: {
    // Login Unified (Admin, Head/Atasan, & Staff — otomatis dibedakan berdasarkan jabatan)
    async loginUnified(identifier, password, portalType = null) {
      const payload = {
        identifier,
        password,
      }
      if (portalType) {
        payload.portal_type = portalType
      }
      if (identifier.includes('@')) {
        payload.email = identifier
      } else {
        payload.employee_id = identifier
      }

      const { data } = await api.post('/web/auth/login', payload)
      this.token = data.data.token
      localStorage.setItem('hrms_token', this.token)

      if (data.data.user_type === 'admin') {
        this.admin = data.data.admin
        this.employee = null
        this.userType = 'admin'
        localStorage.setItem('hrms_admin', JSON.stringify(this.admin))
        localStorage.setItem('hrms_user_type', 'admin')
        localStorage.removeItem('hrms_employee')
      } else {
        this.employee = data.data.employee
        this.admin = null
        this.userType = 'employee'
        localStorage.setItem('hrms_employee', JSON.stringify(this.employee))
        localStorage.setItem('hrms_user_type', 'employee')
        localStorage.removeItem('hrms_admin')
      }

      return data.data
    },

    // Login Admin (existing)
    async login(email, password, portalType = null) {
      const payload = { email, password }
      if (portalType) payload.portal_type = portalType
      const { data } = await api.post('/auth/login', payload)
      this.token    = data.data.token
      this.admin    = data.data.admin
      this.employee = null
      this.userType = 'admin'
      localStorage.setItem('hrms_token',     this.token)
      localStorage.setItem('hrms_admin',     JSON.stringify(this.admin))
      localStorage.setItem('hrms_user_type', 'admin')
      localStorage.removeItem('hrms_employee')
      return data
    },

    // Login Employee via Web
    async loginEmployee(identifier, password, portalType = null) {
      const payload = identifier.includes('@')
        ? { email: identifier, password }
        : { employee_id: identifier, password }

      if (portalType) {
        payload.portal_type = portalType
      }

      const { data } = await api.post('/web/auth/login', payload)
      this.token    = data.data.token
      this.employee = data.data.employee
      this.admin    = null
      this.userType = 'employee'
      localStorage.setItem('hrms_token',     this.token)
      localStorage.setItem('hrms_employee',  JSON.stringify(this.employee))
      localStorage.setItem('hrms_user_type', 'employee')
      localStorage.removeItem('hrms_admin')
      return data
    },

    // Refresh data user yang sedang login
    async refreshMe() {
      try {
        if (this.userType === 'employee') {
          const { data } = await api.get('/web/auth/me')
          this.employee = data.data.employee
          localStorage.setItem('hrms_employee', JSON.stringify(this.employee))
        } else {
          const { data } = await api.get('/auth/profile')
          this.admin = data.data
          localStorage.setItem('hrms_admin', JSON.stringify(this.admin))
        }
      } catch (e) { /* ignore */ }
    },

    async logout() {
      const isSupervisor = this.userType === 'employee' && this.employee?.web_role === 'spv'
      try {
        if (this.userType === 'employee') {
          await api.post('/web/auth/logout')
        } else {
          await api.post('/auth/logout')
        }
      } catch (e) { /* ignore */ }

      this.token    = ''
      this.admin    = null
      this.employee = null
      this.userType = 'admin'
      localStorage.removeItem('hrms_token')
      localStorage.removeItem('hrms_admin')
      localStorage.removeItem('hrms_employee')
      localStorage.removeItem('hrms_user_type')

      return isSupervisor ? '/login' : '/hrms/login'
    },
  },
})
