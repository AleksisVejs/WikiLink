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

const totalXp = ref(loadProgression().totalXp)

export function useProgression() {
  const levelInfo = computed(() => levelFromXp(totalXp.value))
  const level = computed(() => levelInfo.value.level)
  const currentXp = computed(() => levelInfo.value.currentXp)
  const nextLevelXp = computed(() => levelInfo.value.nextLevelXp)
  const progress = computed(() => currentXp.value / nextLevelXp.value)

  function calculateXpReward(gameResult) {
    const rewards = []
    const { won, mode, clicks, elapsed, streak, hintsUsed = 0, modifiers = [] } = gameResult

    if (!won) {
      rewards.push({ label: 'Game completed', xp: 15 })
      return applyModifierBonus(rewards, modifiers)
    }

    rewards.push({ label: 'Victory', xp: 100 })

    if (mode === 'daily') {
      rewards.push({ label: 'Daily bonus', xp: 50 })
    }

    if (mode === 'trending') {
      rewards.push({ label: 'Trending bonus', xp: 25 })
    }

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

    if (mode === 'sprint') {
      rewards.push({ label: 'Sprint mode', xp: 25 })
    }
    if (mode === 'challenge') {
      rewards.push({ label: 'Click limit mode', xp: 25 })
    }

    return applyModifierBonus(rewards, modifiers)
  }

  function applyModifierBonus(rewards, modifiers) {
    if (modifiers.length > 0) {
      const multiplier = 1 + 0.25 * modifiers.length
      const baseTotal = rewards.reduce((sum, r) => sum + r.xp, 0)
      const bonus = Math.round(baseTotal * (multiplier - 1))
      if (bonus > 0) {
        rewards.push({ label: `${modifiers.length}x modifier bonus`, xp: bonus })
      }
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

  return {
    totalXp,
    level,
    currentXp,
    nextLevelXp,
    progress,
    awardXp,
  }
}
