import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from './router'
import App from './App.vue'
import './style.css'
import Vue3Toastify, { toast } from 'vue3-toastify'
import 'vue3-toastify/dist/index.css'

createApp(App)
  .use(createPinia())
  .use(router)
  .use(Vue3Toastify, {
    autoClose: 3000,
    position: toast.POSITION.TOP_RIGHT,
  })
  .mount('#app')
