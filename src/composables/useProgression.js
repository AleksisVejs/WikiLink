import { ref, computed } from 'vue'

const XP_KEY = 'wikilink_progression'

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
    return JSON.parse(localStorage.getItem(XP_KEY)) || { totalXp: 0 }
  } catch {
    return { totalXp: 0 }
  }
}

function saveProgression(data) {
  localStorage.setItem(XP_KEY, JSON.stringify(data))
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
  easy:   1.25,
  normal: 1.5,
  hard:   2.0,
  custom: 1.25,
}

const totalXp = ref(loadProgression().totalXp)

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
    const modMult = modifiers.length > 0 ? (1 + 0.25 * modifiers.length) : 1.0

    if (modeXp.multiplier === 0 || !won) return []

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

    const totalMult = modeXp.multiplier * diffMult * modMult
    if (totalMult !== 1.0) {
      const baseTotal = rewards.reduce((sum, r) => sum + r.xp, 0)
      const bonus = Math.round(baseTotal * (totalMult - 1))
      const parts = []
      if (modeXp.multiplier !== 1.0) {
        const modeLabel = mode.charAt(0).toUpperCase() + mode.slice(1)
        parts.push(`${modeLabel} ${modeXp.multiplier}x`)
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
    saveProgression({ totalXp: totalXp.value })

    const newLevel = level.value
    const leveledUp = newLevel > previousLevel

    return { rewards, totalReward, leveledUp, newLevel }
  }

  function estimateXp(mode, difficulty = 'normal', modifierCount = 0) {
    const modeXp = MODE_XP[mode]
    if (!modeXp || modeXp.multiplier === 0) return { base: 0, total: 0, multiplier: 0 }
    const hasDifficulty = mode === 'sprint' || mode === 'challenge'
    const diffMult = hasDifficulty ? (DIFFICULTY_XP[difficulty] || 1.0) : 1.0
    const combinedMult = modeXp.multiplier * diffMult
    const modMult = 1 + 0.25 * modifierCount
    const finalMult = combinedMult * modMult
    const base = 100
    const total = Math.round(base * finalMult)
    return { base, total, multiplier: Math.round(finalMult * 100) / 100 }
  }

  return {
    totalXp,
    level,
    currentXp,
    nextLevelXp,
    progress,
    awardXp,
    estimateXp,
    MODE_XP,
    DIFFICULTY_XP,
  }
}
