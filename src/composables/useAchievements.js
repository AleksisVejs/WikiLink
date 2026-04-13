import { ref, computed } from 'vue'

const ACHIEVEMENTS_KEY = 'wikilink_achievements'

const ACHIEVEMENTS = {
  speed_demon:      { id: 'speed_demon',      name: 'Speed Demon',      description: 'Win a game in under 30 seconds',     xp: 50,  category: 'speed' },
  lightning:        { id: 'lightning',         name: 'Lightning',        description: 'Win a game in under 15 seconds',     xp: 100, category: 'speed' },
  minimalist:       { id: 'minimalist',        name: 'Minimalist',       description: 'Win a game in 2 clicks or fewer',    xp: 100, category: 'efficiency' },
  straight_line:    { id: 'straight_line',     name: 'Straight Line',    description: 'Win a game in 3 clicks',             xp: 50,  category: 'efficiency' },
  pathfinder:       { id: 'pathfinder',        name: 'Pathfinder',       description: 'Win a game in 5 clicks or fewer',    xp: 30,  category: 'efficiency' },
  first_blood:      { id: 'first_blood',       name: 'First Blood',      description: 'Win your very first game',            xp: 25,  category: 'milestone' },
  ten_wins:         { id: 'ten_wins',          name: 'Getting Good',     description: 'Win 10 games',                       xp: 50,  category: 'milestone' },
  fifty_wins:       { id: 'fifty_wins',        name: 'Veteran',          description: 'Win 50 games',                       xp: 100, category: 'milestone' },
  century:          { id: 'century',           name: 'Century',          description: 'Win 100 games',                      xp: 200, category: 'milestone' },
  wiki_wanderer:    { id: 'wiki_wanderer',     name: 'Wiki Wanderer',    description: 'Play 50 games total',                xp: 50,  category: 'milestone' },
  daily_devotee:    { id: 'daily_devotee',     name: 'Daily Devotee',    description: 'Complete 7 daily challenges',         xp: 75,  category: 'dedication' },
  ironman:          { id: 'ironman',           name: 'Ironman',          description: 'Complete 30 daily challenges',        xp: 200, category: 'dedication' },
  streak_3:         { id: 'streak_3',          name: 'On a Roll',        description: 'Get a 3-win streak in any mode',     xp: 30,  category: 'dedication' },
  streak_10:        { id: 'streak_10',         name: 'Unstoppable',      description: 'Get a 10-win streak in any mode',    xp: 100, category: 'dedication' },
  globe_trotter:    { id: 'globe_trotter',     name: 'Globe Trotter',    description: 'Win a game in 5 different genres',   xp: 75,  category: 'explorer' },
  sprinter:         { id: 'sprinter',          name: 'Sprinter',         description: 'Win 10 sprint games',                xp: 50,  category: 'mode' },
  sharpshooter:     { id: 'sharpshooter',      name: 'Sharpshooter',     description: 'Win 10 click-limit games',           xp: 50,  category: 'mode' },
  trendsetter:      { id: 'trendsetter',       name: 'Trendsetter',      description: 'Win 5 trending games',               xp: 50,  category: 'mode' },
  close_call:       { id: 'close_call',        name: 'Close Call',       description: 'Win a sprint with under 5 seconds left', xp: 75, category: 'special' },
  no_hints:         { id: 'no_hints',          name: 'Pure Skill',       description: 'Win 10 games without using any hints', xp: 75, category: 'special' },
  modifier_master:  { id: 'modifier_master',   name: 'Modifier Master',  description: 'Win a game with 3+ modifiers active', xp: 100, category: 'special' },
}

function loadAchievements() {
  try {
    return JSON.parse(localStorage.getItem(ACHIEVEMENTS_KEY)) || {}
  } catch {
    return {}
  }
}

function saveAchievements(data) {
  localStorage.setItem(ACHIEVEMENTS_KEY, JSON.stringify(data))
}

const unlockedMap = ref(loadAchievements())
const recentUnlocks = ref([])

export function useAchievements() {
  const unlockedCount = computed(() => Object.keys(unlockedMap.value).length)
  const totalCount = computed(() => Object.keys(ACHIEVEMENTS).length)

  function unlock(id) {
    if (unlockedMap.value[id]) return false
    if (!ACHIEVEMENTS[id]) return false

    unlockedMap.value[id] = { unlockedAt: new Date().toISOString() }
    saveAchievements(unlockedMap.value)
    recentUnlocks.value.push(ACHIEVEMENTS[id])
    return true
  }

  function consumeRecentUnlocks() {
    const unlocks = [...recentUnlocks.value]
    recentUnlocks.value = []
    return unlocks
  }

  function checkAchievements(gameContext) {
    const { won, mode, clicks, elapsed, timeRemaining, stats, hintsUsed = 0, modifiers = [], dailyCompletions = 0 } = gameContext
    const newUnlocks = []

    if (won) {
      const totalWins = Object.values(stats?.modes || {}).reduce((s, m) => s + (m.gamesWon || 0), 0)
      const totalPlayed = Object.values(stats?.modes || {}).reduce((s, m) => s + (m.gamesPlayed || 0), 0)

      if (totalWins === 1 && unlock('first_blood')) newUnlocks.push('first_blood')
      if (totalWins >= 10 && unlock('ten_wins')) newUnlocks.push('ten_wins')
      if (totalWins >= 50 && unlock('fifty_wins')) newUnlocks.push('fifty_wins')
      if (totalWins >= 100 && unlock('century')) newUnlocks.push('century')
      if (totalPlayed >= 50 && unlock('wiki_wanderer')) newUnlocks.push('wiki_wanderer')

      if (elapsed <= 15 && unlock('lightning')) newUnlocks.push('lightning')
      else if (elapsed <= 30 && unlock('speed_demon')) newUnlocks.push('speed_demon')

      if (clicks <= 2 && unlock('minimalist')) newUnlocks.push('minimalist')
      else if (clicks <= 3 && unlock('straight_line')) newUnlocks.push('straight_line')
      else if (clicks <= 5 && unlock('pathfinder')) newUnlocks.push('pathfinder')

      const modeStats = stats?.modes || {}
      for (const ms of Object.values(modeStats)) {
        if ((ms.currentStreak || 0) >= 3 && unlock('streak_3')) newUnlocks.push('streak_3')
        if ((ms.currentStreak || 0) >= 10 && unlock('streak_10')) newUnlocks.push('streak_10')
      }

      const genresWon = Object.values(stats?.genres || {}).filter(g => (g.gamesWon || 0) > 0).length
      if (genresWon >= 5 && unlock('globe_trotter')) newUnlocks.push('globe_trotter')

      if ((modeStats.sprint?.gamesWon || 0) >= 10 && unlock('sprinter')) newUnlocks.push('sprinter')
      if ((modeStats.challenge?.gamesWon || 0) >= 10 && unlock('sharpshooter')) newUnlocks.push('sharpshooter')
      if ((modeStats.trending?.gamesWon || 0) >= 5 && unlock('trendsetter')) newUnlocks.push('trendsetter')

      if (mode === 'sprint' && timeRemaining !== null && timeRemaining < 5) {
        if (unlock('close_call')) newUnlocks.push('close_call')
      }

      if (dailyCompletions >= 7 && unlock('daily_devotee')) newUnlocks.push('daily_devotee')
      if (dailyCompletions >= 30 && unlock('ironman')) newUnlocks.push('ironman')

      if (modifiers.length >= 3 && unlock('modifier_master')) newUnlocks.push('modifier_master')

      const noHintWins = parseInt(localStorage.getItem('wikilink_nohint_wins') || '0')
      if (hintsUsed === 0) {
        const newCount = noHintWins + 1
        localStorage.setItem('wikilink_nohint_wins', String(newCount))
        if (newCount >= 10 && unlock('no_hints')) newUnlocks.push('no_hints')
      }
    }

    return newUnlocks
  }

  function getAllAchievements() {
    return Object.values(ACHIEVEMENTS).map(a => ({
      ...a,
      unlocked: !!unlockedMap.value[a.id],
      unlockedAt: unlockedMap.value[a.id]?.unlockedAt || null,
    }))
  }

  return {
    unlockedCount,
    totalCount,
    checkAchievements,
    consumeRecentUnlocks,
    getAllAchievements,
  }
}
