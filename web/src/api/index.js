import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://127.0.0.1:8011/api/v1',
  headers: { Accept: 'application/json' },
})

// interceptor: sisipkan token
api.interceptors.request.use((config) => {
  const isPublic = config.url?.includes('/company/logo') || config.url?.includes('/cms/') || config.url?.includes('/recruitment/')
  const token = localStorage.getItem('hrms_token')
  if (token && !isPublic) config.headers.Authorization = `Bearer ${token}`
  return config
})

// interceptor: handle 401
api.interceptors.response.use(
  (res) => res,
  (err) => {
    if (err.response?.status === 401) {
      localStorage.removeItem('hrms_token')
      localStorage.removeItem('hrms_admin')
      localStorage.removeItem('hrms_employee')
      localStorage.removeItem('hrms_user_type')
      const target = location.pathname.startsWith('/hrms') ? '/hrms/login' : '/login'
      if (location.pathname !== '/login' && location.pathname !== '/hrms/login') {
        location.href = target
      }
    }
    return Promise.reject(err)
  },
)

export default api
