import { utcDateKey } from './dailySeed'

export const DAILY_IN_PROGRESS_SESSION_KEY = 'wikilink_daily_in_progress_v1'

export function saveDailySession({ date, userId = null, snapshot }) {
  try {
    localStorage.setItem(
      DAILY_IN_PROGRESS_SESSION_KEY,
      JSON.stringify({
        date: date ?? utcDateKey(),
        userId: userId ?? null,
        snapshot,
        updatedAt: Date.now(),
      }),
    )
  } catch {
    // ignore storage failures
  }
}

export function loadDailySession() {
  try {
    const raw = localStorage.getItem(DAILY_IN_PROGRESS_SESSION_KEY)
    if (!raw) return null
    const parsed = JSON.parse(raw)
    if (!parsed || typeof parsed !== 'object') return null
    return parsed
  } catch {
    return null
  }
}

export function clearDailySession() {
  try {
    localStorage.removeItem(DAILY_IN_PROGRESS_SESSION_KEY)
  } catch {
    // ignore storage failures
  }
}

