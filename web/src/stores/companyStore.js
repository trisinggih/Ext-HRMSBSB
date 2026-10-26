import { defineStore } from 'pinia'
import api from '../api'

const resolveStorageUrl = (rawPath, fallback = null) => {
  if (!rawPath) return fallback
  const baseUrl = (import.meta.env.VITE_API_URL || 'http://127.0.0.1:8011/api/v1').replace('/api/v1', '')
  if (rawPath.startsWith('http')) {
    const storageIndex = rawPath.indexOf('/storage/')
    if (storageIndex !== -1) {
      return baseUrl + rawPath.substring(storageIndex)
    }
    return rawPath
  }
  const cleanPath = rawPath.startsWith('/') ? rawPath : '/' + rawPath
  return baseUrl + (cleanPath.startsWith('/storage') ? '' : '/storage') + cleanPath
}

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
    customLogoUrl: (state) => {
      return resolveStorageUrl(state.company?.logo, null)
    },
    logoUrl: (state) => {
      return resolveStorageUrl(state.company?.logo, '/assets/images/logo-dark.png')
    },
    logoLightUrl: (state) => {
      return resolveStorageUrl(state.company?.logo, '/assets/images/logo-light.png')
    },
    logoSmUrl: (state) => {
      return resolveStorageUrl(state.company?.favicon, resolveStorageUrl(state.company?.logo, '/assets/images/logo-sm-dark.png'))
    },
    companyName: (state) => {
      return state.company?.name || 'BSB Village'
    },
    faviconUrl: (state) => {
      return resolveStorageUrl(state.company?.favicon, '/assets/images/logo-sm-dark.png')
    }
  }
})

