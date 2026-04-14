export const MULTIPLAYER_SESSION_KEY = 'wikilink_multiplayer_session_v1'

export function buildSessionRoute(session) {
  if (!session?.route?.mode) return null
  return {
    name: 'game',
    params: { mode: session.route.mode },
    query: { ...(session.route.query || {}) },
  }
}

export function saveMultiplayerSession(session) {
  try {
    localStorage.setItem(MULTIPLAYER_SESSION_KEY, JSON.stringify({
      ...session,
      updatedAt: Date.now(),
    }))
  } catch {
    // ignore storage failures
  }
}

export function loadMultiplayerSession() {
  try {
    const raw = localStorage.getItem(MULTIPLAYER_SESSION_KEY)
    if (!raw) return null
    const parsed = JSON.parse(raw)
    if (!parsed || typeof parsed !== 'object') return null
    return parsed
  } catch {
    return null
  }
}

export function clearMultiplayerSession() {
  try {
    localStorage.removeItem(MULTIPLAYER_SESSION_KEY)
  } catch {
    // ignore storage failures
  }
}
