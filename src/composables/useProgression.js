import { ref, computed } from 'vue'
import { useApi } from './useApi.js'

const XP_KEY_BASE = 'wikilink_progression'

function xpForLevel(level) {
  return level * 200
}

function levelFromXp(totalXp) {
  let level = 1
  let remaining = totalXp
  while (remaining >= xpForLevel(level)) {
    remaining -= xpForLevel(level)
    level++
  }
  return { level, currentXp: remaining, nextLevelXp: xpForLevel(level) }
}

function loadProgression() {
  try {
    return JSON.parse(localStorage.getItem(XP_KEY_BASE)) || { totalXp: 0 }
  } catch {
    return { totalXp: 0 }
  }
}

function loadProgressionByKey(key) {
  try {
    return JSON.parse(localStorage.getItem(key)) || { totalXp: 0 }
  } catch {
    return { totalXp: 0 }
  }
}

function saveProgressionByKey(key, data) {
  localStorage.setItem(key, JSON.stringify(data))
}

function totalXpFromStats(stats) {
  const value = Number(stats?.progression?.totalXp)
  if (!Number.isFinite(value) || value < 0) return null
  return Math.round(value)
}

const MODE_XP = {
  classic:   { multiplier: 1.0  },
  sprint:    { multiplier: 1.5  },
  challenge: { multiplier: 1.5  },
  trending:  { multiplier: 1.25 },
  custom:    { multiplier: 0    },
  freeplay:  { multiplier: 0    },
  daily:     { multiplier: 2.0  },
}

const DIFFICULTY_XP = {
  easy:   2.0,
  normal: 2.5,
  hard:   3.0,
  custom: 1.0,
}

function modifierMultiplier(modifierCount) {
  const count = Math.max(0, Number(modifierCount) || 0)
  return Math.pow(1.25, count)
}

const totalXp = ref(loadProgression().totalXp)
let xpCheatRegistered = false
const api = useApi()
let lastSyncedXp = totalXp.value
let activeProgressionKey = XP_KEY_BASE

export function hydrateProgressionFromStats(stats, userId = null) {
  if (!userId) {
    activeProgressionKey = XP_KEY_BASE
    const guestTotalXp = Math.max(0, Number(loadProgressionByKey(activeProgressionKey).totalXp) || 0)
    totalXp.value = guestTotalXp
    lastSyncedXp = guestTotalXp
    return
  }

  activeProgressionKey = `${XP_KEY_BASE}_${userId}`
  const serverTotalXp = totalXpFromStats(stats) ?? 0
  totalXp.value = serverTotalXp
  saveProgressionByKey(activeProgressionKey, { totalXp: serverTotalXp })
  lastSyncedXp = serverTotalXp
}

function syncXpToServer() {
  const token = localStorage.getItem('wikilink_token')
  if (!token) return
  if (totalXp.value === lastSyncedXp) return
  const payloadXp = totalXp.value
  lastSyncedXp = payloadXp
  api.post('/progression/sync', { totalXp: payloadXp }).catch(() => {})
}

export function useProgression() {
  const levelInfo = computed(() => levelFromXp(totalXp.value))
  const level = computed(() => levelInfo.value.level)
  const currentXp = computed(() => levelInfo.value.currentXp)
  const nextLevelXp = computed(() => levelInfo.value.nextLevelXp)
  const progress = computed(() => currentXp.value / nextLevelXp.value)

  function calculateXpReward(gameResult) {
    const rewards = []
    const { won, mode, difficulty = 'normal', clicks, elapsed, streak, hintsUsed = 0, modifiers = [] } = gameResult
    const modeXp = MODE_XP[mode] || MODE_XP.classic
    const hasDifficulty = mode === 'sprint' || mode === 'challenge'
    const diffMult = hasDifficulty ? (DIFFICULTY_XP[difficulty] || 1.0) : 1.0
    const modeMult = (hasDifficulty && difficulty === 'custom') ? 1.0 : modeXp.multiplier
    const modMult = modifierMultiplier(modifiers.length)

    if (modeMult === 0 || !won) return []

    rewards.push({ label: 'Victory', xp: 100 })

    if (streak && streak > 1) {
      rewards.push({ label: `${streak}-day streak`, xp: Math.min(streak * 25, 200) })
    }

    if (clicks <= 3) {
      rewards.push({ label: 'Efficiency master', xp: 75 })
    } else if (clicks <= 5) {
      rewards.push({ label: 'Efficient path', xp: 50 })
    }

    if (elapsed <= 15) {
      rewards.push({ label: 'Lightning fast', xp: 75 })
    } else if (elapsed <= 30) {
      rewards.push({ label: 'Speed bonus', xp: 50 })
    } else if (elapsed <= 60) {
      rewards.push({ label: 'Quick finish', xp: 25 })
    }

    if (hintsUsed === 0) {
      rewards.push({ label: 'No hints', xp: 25 })
    }

    const totalMult = modeMult * diffMult * modMult
    if (totalMult !== 1.0) {
      const baseTotal = rewards.reduce((sum, r) => sum + r.xp, 0)
      const bonus = Math.round(baseTotal * (totalMult - 1))
      const parts = []
      if (modeMult !== 1.0) {
        const modeLabel = mode.charAt(0).toUpperCase() + mode.slice(1)
        parts.push(`${modeLabel} ${modeMult}x`)
      }
      if (diffMult !== 1.0) {
        const diffLabel = difficulty.charAt(0).toUpperCase() + difficulty.slice(1)
        parts.push(`${diffLabel} ${diffMult}x`)
      }
      if (modMult !== 1.0) {
        parts.push(`${modifiers.length} mod ${modMult}x`)
      }
      const roundedMult = Math.round(totalMult * 100) / 100
      rewards.push({ label: `${parts.join(' + ')} = ${roundedMult}x`, xp: bonus })
    }

    return rewards
  }

  function awardXp(gameResult) {
    const previousLevel = level.value
    const rewards = calculateXpReward(gameResult)
    const totalReward = rewards.reduce((sum, r) => sum + r.xp, 0)

    totalXp.value += totalReward
    saveProgressionByKey(activeProgressionKey, { totalXp: totalXp.value })
    syncXpToServer()

    const newLevel = level.value
    const leveledUp = newLevel > previousLevel

    return { rewards, totalReward, leveledUp, newLevel }
  }

  function awardBonusXp(amount, label = 'Bonus XP') {
    const parsed = Math.max(0, Math.round(Number(amount) || 0))
    if (parsed === 0) {
      return { rewards: [], totalReward: 0, leveledUp: false, newLevel: level.value }
    }

    const previousLevel = level.value
    totalXp.value += parsed
    saveProgressionByKey(activeProgressionKey, { totalXp: totalXp.value })
    syncXpToServer()

    const newLevel = level.value
    const leveledUp = newLevel > previousLevel
    return {
      rewards: [{ label, xp: parsed }],
      totalReward: parsed,
      leveledUp,
      newLevel,
    }
  }

  function estimateXp(mode, difficulty = 'normal', modifierCount = 0) {
    const modeXp = MODE_XP[mode]
    if (!modeXp || modeXp.multiplier === 0) return { base: 0, total: 0, multiplier: 0 }
    const hasDifficulty = mode === 'sprint' || mode === 'challenge'
    const diffMult = hasDifficulty ? (DIFFICULTY_XP[difficulty] || 1.0) : 1.0
    const modeMult = (hasDifficulty && difficulty === 'custom') ? 1.0 : modeXp.multiplier
    const combinedMult = modeMult * diffMult
    const modMult = modifierMultiplier(modifierCount)
    const finalMult = combinedMult * modMult
    const base = 100
    const total = Math.round(base * finalMult)
    return { base, total, multiplier: Math.round(finalMult * 100) / 100 }
  }

  if (!xpCheatRegistered && typeof window !== 'undefined' && import.meta.env.DEV) {
    window.__wikilinkAddXp = (amount = 1000, label = 'Cheat XP') => awardBonusXp(amount, label)
    xpCheatRegistered = true
  }

  return {
    totalXp,
    level,
    currentXp,
    nextLevelXp,
    progress,
    awardXp,
    awardBonusXp,
    estimateXp,
    MODE_XP,
    DIFFICULTY_XP,
  }
}
