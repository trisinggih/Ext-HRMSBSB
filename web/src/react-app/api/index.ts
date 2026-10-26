import axios from 'axios';

const api = axios.create({
  baseURL: 'http://localhost:8011/api', // Pointing to Laravel backend
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
});

api.interceptors.request.use((config) => {
  // Check URL parameters for token (SSO pass)
  const urlParams = new URLSearchParams(window.location.search);
  const tokenFromUrl = urlParams.get('token');
  
  if (tokenFromUrl) {
    localStorage.setItem('hrms_token', tokenFromUrl);
    // Optionally clean up URL
    window.history.replaceState({}, document.title, window.location.pathname);
  }

  const token = localStorage.getItem('hrms_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

export default api;
