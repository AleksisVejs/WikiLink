import { ref } from 'vue'
import { useApi } from './useApi.js'
import { STATS_KEY } from './useGame.js'
import { hydrateProgressionFromStats } from './useProgression.js'
import { hydrateAchievementsFromStats } from './useAchievements.js'

const user = ref(null)
const streak = ref(0)
const loading = ref(false)
const TOKEN_KEY = 'wikilink_token'
const api = useApi()

function hydrateLocalStats(serverStats, userId = null) {
  if (!serverStats || typeof serverStats !== 'object') return
  const hasData = (serverStats.modes && Object.keys(serverStats.modes).length > 0)
              || (serverStats.genres && Object.keys(serverStats.genres).length > 0)
  if (hasData) {
    localStorage.setItem(STATS_KEY, JSON.stringify(serverStats))
  }
  hydrateProgressionFromStats(serverStats, userId)
  hydrateAchievementsFromStats(serverStats, userId)
}

async function init() {
  const token = localStorage.getItem(TOKEN_KEY)
  if (!token) return
  try {
    const data = await api.get('/me')
    user.value = data.user
    streak.value = data.streak || 0
    hydrateLocalStats(data.stats, data.user?.id || null)
  } catch (e) {
    if (e.status === 401 || e.status === 403) {
      localStorage.removeItem(TOKEN_KEY)
      user.value = null
      hydrateProgressionFromStats(null, null)
      hydrateAchievementsFromStats(null, null)
    }
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
      hydrateLocalStats(data.stats, data.user?.id || null)
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
      hydrateLocalStats(data.stats, data.user?.id || null)
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
    hydrateProgressionFromStats(null, null)
    hydrateAchievementsFromStats(null, null)
  }

  async function refreshStreak() {
    if (!user.value) return
    try {
      const data = await api.get('/me')
      streak.value = data.streak || 0
      hydrateLocalStats(data.stats, data.user?.id || user.value?.id || null)
    } catch { /* ignore */ }
  }

  function setProfileIcon(iconId) {
    if (!user.value) return
    user.value = { ...user.value, profile_icon: iconId }
  }

  function setProfileAccent(accentId) {
    if (!user.value) return
    user.value = { ...user.value, profile_accent: accentId }
  }

  function setProfileCustomization(customization = {}) {
    if (!user.value) return
    user.value = { ...user.value, ...customization }
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
    setProfileIcon,
    setProfileAccent,
    setProfileCustomization,
    init,
  }
}
