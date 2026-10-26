import { defineStore } from 'pinia'
import api from '../api'

export const useCompanyStore = defineStore('company', {
  state: () => ({
    company: null,
    loading: false,
    error: null,
  }),
  actions: {
    async fetchCompany() {
      this.loading = true
      try {
        const { data } = await api.get('/company/logo')
        if (data.success && data.data) {
          this.company = data.data
        } else {
          this.company = null
        }
      } catch (e) {
        this.error = e.response?.data?.message || 'Failed to fetch company data'
      } finally {
        this.loading = false
      }
    }
  },
  getters: {
    logoUrl: (state) => {
      const baseUrl = (import.meta.env.VITE_API_URL || 'http://127.0.0.1:8011/api/v1').replace('/api/v1', '')
      const rawLogo = state.company?.logo
      if (rawLogo) {
        if (rawLogo.startsWith('http')) {
          const storageIndex = rawLogo.indexOf('/storage/')
          if (storageIndex !== -1) {
            return baseUrl + rawLogo.substring(storageIndex)
          }
          return rawLogo
        }
        const cleanPath = rawLogo.startsWith('/') ? rawLogo : '/' + rawLogo
        return baseUrl + (cleanPath.startsWith('/storage') ? '' : '/storage') + cleanPath
      }
      return '/assets/images/logo-dark.png'
    },
    companyName: (state) => {
      return state.company?.name || 'BSB Village'
    },
    faviconUrl: (state) => {
      const baseUrl = (import.meta.env.VITE_API_URL || 'http://127.0.0.1:8011/api/v1').replace('/api/v1', '')
      if (state.company?.favicon) {
        if (state.company.favicon.startsWith('http')) {
          const storageIndex = state.company.favicon.indexOf('/storage/')
          if (storageIndex !== -1) {
            return baseUrl + state.company.favicon.substring(storageIndex)
          }
          return state.company.favicon
        }
        const cleanPath = state.company.favicon.startsWith('/') ? state.company.favicon : '/' + state.company.favicon
        return baseUrl + (cleanPath.startsWith('/storage') ? '' : '/storage') + cleanPath
      }
      return '/assets/images/logo-sm-dark.png'
    }
  }
})
