import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import { useAuth } from './composables/useAuth.js'
import './style.css'

useAuth().init()
createApp(App).use(router).mount('#app')
