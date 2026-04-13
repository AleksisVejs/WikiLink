import { ref, reactive, computed } from 'vue'
import { utcDateKey } from '../utils/dailySeed.js'
import { useApi } from './useApi.js'

const GENRES = {
  random:     { id: 'random',     name: 'Random',      svgIcon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />', shortName: 'Random',  description: 'Anything goes',                searchTerms: null },
  science:    { id: 'science',    name: 'Science',      svgIcon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />', shortName: 'Science', description: 'Physics, chemistry, biology',   searchTerms: ['physics theory', 'chemical element', 'biology organism', 'astronomy star', 'geology mineral', 'quantum mechanics', 'evolution species', 'genetics DNA', 'scientific discovery', 'space exploration mission'] },
  history:    { id: 'history',    name: 'History',      svgIcon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />', shortName: 'History', description: 'Wars, empires, revolutions',    searchTerms: ['ancient civilization', 'world war battle', 'medieval kingdom', 'roman empire', 'revolution independence', 'dynasty emperor', 'colonial era', 'renaissance period', 'cold war', 'historical treaty'] },
  geography:  { id: 'geography',  name: 'Geography',    svgIcon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />', shortName: 'Geo',     description: 'Countries, cities, landmarks',  searchTerms: ['country republic', 'capital city', 'mountain range peak', 'river delta', 'island archipelago', 'national park', 'desert landscape', 'ocean sea', 'continent region', 'volcano eruption'] },
  sports:     { id: 'sports',     name: 'Sports',       svgIcon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />', shortName: 'Sports',  description: 'Football, Olympics, athletics',  searchTerms: ['olympic games athlete', 'football championship', 'basketball player NBA', 'tennis grand slam', 'cricket world cup', 'athletics record', 'swimming olympian', 'Formula One racing', 'boxing champion', 'soccer league'] },
  nature:     { id: 'nature',     name: 'Nature',       svgIcon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />', shortName: 'Nature',  description: 'Animals, plants, ecosystems',   searchTerms: ['mammal species habitat', 'bird species migration', 'reptile amphibian', 'marine animal ocean', 'insect butterfly', 'flowering plant', 'tree species forest', 'endangered species', 'ecosystem biodiversity', 'tropical rainforest'] },
  music:      { id: 'music',      name: 'Music',        svgIcon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" />', shortName: 'Music',   description: 'Artists, genres, instruments',   searchTerms: ['music genre history', 'musical instrument', 'rock band album', 'classical composer symphony', 'jazz musician', 'pop singer', 'hip hop artist', 'opera composer', 'electronic music', 'music festival'] },
  movies:     { id: 'movies',     name: 'Movies & TV',  svgIcon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" />', shortName: 'Film',    description: 'Films, shows, directors',        searchTerms: ['film director filmography', 'television series season', 'animated film studio', 'documentary film', 'Academy Award winning', 'Hollywood movie', 'science fiction film', 'comedy film', 'horror film', 'television network'] },
  technology: { id: 'technology', name: 'Technology',   svgIcon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />', shortName: 'Tech',    description: 'Computers, AI, inventions',      searchTerms: ['computer science algorithm', 'artificial intelligence', 'programming language', 'internet protocol', 'robotics automation', 'spacecraft satellite', 'invention patent', 'electronics semiconductor', 'software engineering', 'mobile technology'] },
  food:       { id: 'food',       name: 'Food & Drink', svgIcon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />', shortName: 'Food',    description: 'Cuisine, dishes, beverages',     searchTerms: ['cuisine traditional dish', 'cooking technique recipe', 'beverage drink history', 'fruit tropical', 'spice herb', 'dessert pastry', 'wine region vineyard', 'bread baking', 'cheese dairy', 'coffee tea'] },
}

const GAME_MODES = {
  classic: {
    id: 'classic',
    svgIcon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l5.447 2.724A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />',
    name: 'Classic',
    description: 'Navigate from start to target in the fewest clicks.',
    rules: 'Find the target article using only Wikipedia links. No time limit.',
  },
  sprint: {
    id: 'sprint',
    svgIcon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />',
    name: 'Sprint',
    description: 'Reach the target before the timer runs out. Limit is set by difficulty or your own value.',
    rules: 'Reach the target before time runs out.',
    timeLimit: 120,
  },
  challenge: {
    id: 'challenge',
    svgIcon: '<circle cx="12" cy="12" r="9" stroke-width="1.5" /><circle cx="12" cy="12" r="5" stroke-width="1.5" /><circle cx="12" cy="12" r="1" stroke-width="1.5" />',
    name: 'Click limit',
    description: 'Reach the target within a limited number of clicks. No going back!',
    rules: 'Reach the target within your click budget. Each click counts. No undo!',
    clickLimit: 6,
  },
  trending: {
    id: 'trending',
    svgIcon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.879 16.121A3 3 0 1012.015 11L11 14H9c0 .768.293 1.536.879 2.121z" />',
    name: 'Trending',
    description: "Navigate between today's hottest Wikipedia articles. What's the world reading?",
    rules: 'Same as Classic, but both articles are trending on Wikipedia right now.',
  },
  custom: {
    id: 'custom',
    svgIcon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />',
    name: 'Custom',
    description: 'Pick the start and target articles yourself. Same rules as Classic.',
    rules: 'Navigate from your start article to your target using only Wikipedia links.',
  },
  freeplay: {
    id: 'freeplay',
    svgIcon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />',
    name: 'Freeplay',
    description: 'Explore Wikipedia freely. No target, no pressure.',
    rules: 'Just explore! See how far the rabbit hole goes.',
    noTarget: true,
  },
  daily: {
    id: 'daily',
    svgIcon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />',
    name: 'Daily',
    description: "Today's challenge. Same pair for everyone. No going back!",
    rules: 'Complete the daily challenge. One pair per day! Going back is disabled.',
  },
}

const MODIFIERS = {
  fog:       { id: 'fog',       name: 'Fog of War',   description: 'Links hidden until hovered' },
  blackout:  { id: 'blackout',  name: 'Blackout',      description: '30% of links randomly disabled' },
  noback:    { id: 'noback',    name: 'No Back',       description: 'Back button is disabled' },
  speedDecay:{ id: 'speedDecay',name: 'Speed Decay',   description: 'Timer speeds up with each click (Sprint only)' },
}

const DIFFICULTIES = {
  easy:   { id: 'easy',   label: 'EASY',   sprintTime: 180, challengeClicks: 10 },
  normal: { id: 'normal', label: 'NORMAL', sprintTime: 120, challengeClicks: 6 },
  hard:   { id: 'hard',   label: 'HARD',   sprintTime: 60,  challengeClicks: 3 },
  custom: { id: 'custom', label: 'CUSTOM', sprintTime: 120, challengeClicks: 6 },
}

const LIMIT_BOUNDS = {
  timeMin: 1,
  timeMax: 3600,
  clicksMin: 1,
  clicksMax: 99,
}

function clamp(n, min, max) {
  const x = Number(n)
  if (!Number.isFinite(x)) return min
  return Math.min(max, Math.max(min, Math.round(x)))
}

function getEffectiveLimits(mode, difficulty = 'normal', custom = {}) {
  const m = GAME_MODES[mode]
  if (!m) return {}
  const d = DIFFICULTIES[difficulty] || DIFFICULTIES.normal

  if (difficulty === 'custom') {
    const time = clamp(custom.timeLimit ?? d.sprintTime, LIMIT_BOUNDS.timeMin, LIMIT_BOUNDS.timeMax)
    const clicks = clamp(custom.clickLimit ?? d.challengeClicks, LIMIT_BOUNDS.clicksMin, LIMIT_BOUNDS.clicksMax)
    return {
      timeLimit: m.timeLimit ? time : null,
      clickLimit: m.clickLimit ? clicks : null,
    }
  }

  return {
    timeLimit: m.timeLimit ? d.sprintTime : null,
    clickLimit: m.clickLimit ? d.challengeClicks : null,
  }
}

// --- Stats persistence ---

export const STATS_KEY = 'wikilink_stats_v2'
const DAILY_KEY = 'wikilink_daily'

function migrateOldStats() {
  const old = localStorage.getItem('wikilink_stats')
  if (!old) return
  try {
    const parsed = JSON.parse(old)
    if (parsed && !parsed._migrated) {
      const modes = {}
      for (const [modeId, data] of Object.entries(parsed)) {
        modes[modeId] = {
          gamesPlayed: data.gamesPlayed || 0,
          gamesWon: data.gamesWon || 0,
          gamesLost: 0,
          bestClicks: data.bestClicks ?? null,
          bestTime: data.bestTime ?? null,
          totalClicks: data.totalClicks || 0,
          currentStreak: data.gamesWon || 0,
          bestStreak: data.gamesWon || 0,
        }
      }
      localStorage.setItem(STATS_KEY, JSON.stringify({ modes, genres: {} }))
      localStorage.setItem('wikilink_stats', JSON.stringify({ _migrated: true }))
    }
  } catch { /* ignore corrupt data */ }
}

migrateOldStats()

export function loadStats() {
  try { return JSON.parse(localStorage.getItem(STATS_KEY)) || { modes: {}, genres: {} } }
  catch { return { modes: {}, genres: {} } }
}

function loadDaily() {
  try { return JSON.parse(localStorage.getItem(DAILY_KEY)) || {} }
  catch { return {} }
}

// --- Game composable ---

export function useGame() {
  const state = reactive({
    mode: null,
    genre: null,
    difficulty: 'normal',
    status: 'idle',
    startArticle: null,
    targetArticle: null,
    currentArticle: null,
    path: [],
    clicks: 0,
    startTime: null,
    elapsed: 0,
    timeRemaining: null,
    effectiveTimeLimit: null,
    effectiveClickLimit: null,
    speedDecayPenalty: 0,
    modifiers: [],
    hints: 3,
    hintsUsed: 0,
    combo: 0,
    lastClickTime: null,
  })

  const timerInterval = ref(null)

  const NO_BACK_MODES = ['daily', 'challenge']

  const currentMode = computed(() => state.mode ? GAME_MODES[state.mode] : null)
  const isPlaying = computed(() => state.status === 'playing')
  const isWon = computed(() => state.status === 'won')
  const isLost = computed(() => state.status === 'lost')
  const isGameOver = computed(() => state.status === 'won' || state.status === 'lost')
  const backDisabled = computed(() =>
    state.modifiers.includes('noback') || NO_BACK_MODES.includes(state.mode)
  )

  const formattedTime = computed(() => {
    const s = state.elapsed
    return `${Math.floor(s / 60)}:${(s % 60).toString().padStart(2, '0')}`
  })

  const formattedRemaining = computed(() => {
    if (state.timeRemaining === null) return null
    const s = state.timeRemaining
    return `${Math.floor(s / 60)}:${(s % 60).toString().padStart(2, '0')}`
  })

  function startTimer() {
    state.startTime = Date.now()
    timerInterval.value = setInterval(() => {
      state.elapsed = Math.floor((Date.now() - state.startTime) / 1000)
      if (state.effectiveTimeLimit) {
        state.timeRemaining = Math.max(0, state.effectiveTimeLimit - state.elapsed - state.speedDecayPenalty)
        if (state.timeRemaining <= 0) endGame('lost')
      }
    }, 100)
  }

  function stopTimer() {
    if (timerInterval.value) { clearInterval(timerInterval.value); timerInterval.value = null }
  }

  function initGame(mode, startArticle, targetArticle, genre = 'random', difficulty = 'normal', customLimits = {}, modifiers = []) {
    stopTimer()
    const limits = getEffectiveLimits(mode, difficulty, customLimits)

    state.mode = mode
    state.genre = genre
    state.difficulty = difficulty
    state.status = 'playing'
    state.startArticle = startArticle
    state.targetArticle = targetArticle
    state.currentArticle = startArticle.title
    state.path = [startArticle.title]
    state.clicks = 0
    state.elapsed = 0
    state.effectiveTimeLimit = limits.timeLimit || null
    state.effectiveClickLimit = limits.clickLimit || null
    state.timeRemaining = limits.timeLimit || null
    state.speedDecayPenalty = 0
    state.modifiers = modifiers
    state.hints = modifiers.includes('noback') ? 2 : 3
    state.hintsUsed = 0
    state.combo = 0
    state.lastClickTime = null
    startTimer()
  }

  function navigateTo(title) {
    if (!isPlaying.value) return false

    const now = Date.now()
    if (state.lastClickTime && (now - state.lastClickTime) < 3000) {
      state.combo++
    } else {
      state.combo = 1
    }
    state.lastClickTime = now

    state.clicks++
    state.currentArticle = title
    state.path.push(title)

    if (state.modifiers.includes('speedDecay') && state.effectiveTimeLimit) {
      state.speedDecayPenalty += Math.floor(state.clicks * 2)
    }

    if (state.effectiveClickLimit && state.clicks >= state.effectiveClickLimit) {
      if (state.targetArticle && title === state.targetArticle.title) {
        endGame('won')
      } else {
        endGame('lost')
      }
      return true
    }

    if (state.targetArticle && title === state.targetArticle.title) {
      endGame('won')
      return true
    }

    return false
  }

  function syncStatsToServer(won) {
    try {
      const api = useApi()
      const isLoggedIn = !!localStorage.getItem('wikilink_token')
      if (isLoggedIn) {
        api.post('/stats/game', {
          mode: state.mode,
          genre: state.genre || 'random',
          clicks: state.clicks,
          time: state.elapsed,
          won,
        }).catch(() => {})
      } else {
        api.post('/stats/increment', { clicks: state.clicks, won }).catch(() => {})
      }
    } catch { /* ignore */ }
  }

  function endGame(result) {
    state.status = result
    state.elapsed = Math.floor((Date.now() - state.startTime) / 1000)
    stopTimer()
    saveStats(result)
    if (state.mode === 'daily' && result === 'won') saveDailyResult()
    syncStatsToServer(result === 'won')
  }

  function saveStats(result) {
    const stats = loadStats()
    const mode = state.mode
    const genre = state.genre || 'random'

    if (!stats.modes[mode]) {
      stats.modes[mode] = { gamesPlayed: 0, gamesWon: 0, gamesLost: 0, bestClicks: null, bestTime: null, totalClicks: 0, currentStreak: 0, bestStreak: 0 }
    }
    const ms = stats.modes[mode]
    ms.gamesPlayed++
    ms.totalClicks += state.clicks

    if (result === 'won') {
      ms.gamesWon++
      ms.currentStreak++
      if (ms.currentStreak > ms.bestStreak) ms.bestStreak = ms.currentStreak
      if (ms.bestClicks === null || state.clicks < ms.bestClicks) ms.bestClicks = state.clicks
      if (ms.bestTime === null || state.elapsed < ms.bestTime) ms.bestTime = state.elapsed
    } else if (result === 'finished') {
      // Freeplay: count as played but not won or lost; don't break streak
    } else {
      ms.gamesLost++
      ms.currentStreak = 0
    }

    if (!stats.genres) stats.genres = {}
    if (!stats.genres[genre]) {
      stats.genres[genre] = { gamesPlayed: 0, gamesWon: 0, gamesLost: 0 }
    }
    stats.genres[genre].gamesPlayed++
    if (result === 'won') stats.genres[genre].gamesWon++
    else if (result !== 'finished') stats.genres[genre].gamesLost++

    localStorage.setItem(STATS_KEY, JSON.stringify(stats))
  }

  function saveDailyResult() {
    const daily = loadDaily()
    daily[utcDateKey()] = { completed: true, clicks: state.clicks, time: state.elapsed }
    localStorage.setItem(DAILY_KEY, JSON.stringify(daily))
  }

  function getStats() { return loadStats() }
  function getDailyStatus() { return loadDaily()[utcDateKey()] || null }

  function goBack() {
    if (!isPlaying.value || state.path.length <= 1 || backDisabled.value) return null
    state.path.pop()
    state.clicks = Math.max(0, state.clicks - 1)
    state.currentArticle = state.path[state.path.length - 1]
    return state.currentArticle
  }

  function endFreeplay() {
    if (state.mode === 'freeplay' && isPlaying.value) {
      state.status = 'won'
      state.elapsed = Math.floor((Date.now() - state.startTime) / 1000)
      stopTimer()
      saveStats('finished')
      syncStatsToServer(false)
    }
  }

  function useHint() {
    if (state.hints <= 0 || !isPlaying.value) return false
    state.hints--
    state.hintsUsed++
    return true
  }

  function resetGame() {
    stopTimer()
    state.status = 'idle'
    state.genre = null
    state.difficulty = 'normal'
    state.startArticle = null
    state.targetArticle = null
    state.currentArticle = null
    state.path = []
    state.clicks = 0
    state.elapsed = 0
    state.startTime = null
    state.timeRemaining = null
    state.effectiveTimeLimit = null
    state.effectiveClickLimit = null
    state.speedDecayPenalty = 0
    state.modifiers = []
    state.hints = 3
    state.hintsUsed = 0
    state.combo = 0
    state.lastClickTime = null
  }

  return {
    state,
    GAME_MODES,
    GENRES,
    MODIFIERS,
    DIFFICULTIES,
    currentMode,
    isPlaying,
    isWon,
    isLost,
    isGameOver,
    backDisabled,
    formattedTime,
    formattedRemaining,
    initGame,
    navigateTo,
    goBack,
    useHint,
    endGame,
    endFreeplay,
    resetGame,
    getStats,
    getDailyStatus,
    getEffectiveLimits,
    LIMIT_BOUNDS,
  }
}
