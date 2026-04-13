import { ref } from 'vue'
import { useApi } from './useApi.js'
import { loadStats, STATS_KEY } from './useGame.js'

const user = ref(null)
const streak = ref(0)
const loading = ref(false)
const TOKEN_KEY = 'wikilink_token'
const api = useApi()

function hydrateLocalStats(serverStats) {
  if (!serverStats || typeof serverStats !== 'object') return
  const hasData = (serverStats.modes && Object.keys(serverStats.modes).length > 0)
              || (serverStats.genres && Object.keys(serverStats.genres).length > 0)
  if (hasData) {
    localStorage.setItem(STATS_KEY, JSON.stringify(serverStats))
  }
}

async function init() {
  const token = localStorage.getItem(TOKEN_KEY)
  if (!token) return
  try {
    const data = await api.get('/me')
    user.value = data.user
    streak.value = data.streak || 0
    hydrateLocalStats(data.stats)
  } catch (e) {
    if (e.status === 401 || e.status === 403) {
      localStorage.removeItem(TOKEN_KEY)
      user.value = null
    }
  }
}

export function useAuth() {
  async function register(username, password) {
    loading.value = true
    try {
      const localStats = loadStats()
      const data = await api.post('/register', { username, password, stats: localStats })
      localStorage.setItem(TOKEN_KEY, data.token)
      user.value = data.user
      streak.value = 0
      hydrateLocalStats(data.stats)
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
      hydrateLocalStats(data.stats)
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
      hydrateLocalStats(data.stats)
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
