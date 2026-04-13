import { ref } from 'vue'
import { useApi } from './useApi.js'

const user = ref(null)
const streak = ref(0)
const loading = ref(false)
const TOKEN_KEY = 'wikilink_token'
const api = useApi()

async function init() {
  const token = localStorage.getItem(TOKEN_KEY)
  if (!token) return
  try {
    const data = await api.get('/me')
    user.value = data.user
    streak.value = data.streak || 0
  } catch {
    localStorage.removeItem(TOKEN_KEY)
    user.value = null
  }
}

export function useAuth() {
  async function register(username, password) {
    loading.value = true
    try {
      const data = await api.post('/register', { username, password })
      localStorage.setItem(TOKEN_KEY, data.token)
      user.value = data.user
      streak.value = 0
      return null
    } catch (e) {
      return e.message
    } finally {
      loading.value = false
    }
  }

  async function login(username, password) {
    loading.value = true
    try {
      const data = await api.post('/login', { username, password })
      localStorage.setItem(TOKEN_KEY, data.token)
      user.value = data.user
      streak.value = data.streak || 0
      return null
    } catch (e) {
      return e.message
    } finally {
      loading.value = false
    }
  }

  async function logout() {
    try { await api.post('/logout') } catch { /* ignore */ }
    localStorage.removeItem(TOKEN_KEY)
    user.value = null
    streak.value = 0
  }

  async function refreshStreak() {
    if (!user.value) return
    try {
      const data = await api.get('/me')
      streak.value = data.streak || 0
    } catch { /* ignore */ }
  }

  return {
    user,
    streak,
    loading,
    isLoggedIn: () => !!user.value,
    register,
    login,
    logout,
    refreshStreak,
    init,
  }
}
