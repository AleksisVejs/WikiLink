import { ref, reactive, computed } from 'vue'
import { utcDateKey } from '../utils/dailySeed.js'
import { useApi } from './useApi.js'

const GENRES = {
  random:     { id: 'random',     name: 'Random',      emoji: '🎲', shortName: 'Random',  description: 'Anything goes',                searchTerms: null },
  science:    { id: 'science',    name: 'Science',      emoji: '🔬', shortName: 'Science', description: 'Physics, chemistry, biology',   searchTerms: ['physics theory', 'chemical element', 'biology organism', 'astronomy star', 'geology mineral', 'quantum mechanics', 'evolution species', 'genetics DNA', 'scientific discovery', 'space exploration mission'] },
  history:    { id: 'history',    name: 'History',      emoji: '⏳', shortName: 'History', description: 'Wars, empires, revolutions',    searchTerms: ['ancient civilization', 'world war battle', 'medieval kingdom', 'roman empire', 'revolution independence', 'dynasty emperor', 'colonial era', 'renaissance period', 'cold war', 'historical treaty'] },
  geography:  { id: 'geography',  name: 'Geography',    emoji: '🌍', shortName: 'Geo',     description: 'Countries, cities, landmarks',  searchTerms: ['country republic', 'capital city', 'mountain range peak', 'river delta', 'island archipelago', 'national park', 'desert landscape', 'ocean sea', 'continent region', 'volcano eruption'] },
  sports:     { id: 'sports',     name: 'Sports',       emoji: '⚽', shortName: 'Sports',  description: 'Football, Olympics, athletics',  searchTerms: ['olympic games athlete', 'football championship', 'basketball player NBA', 'tennis grand slam', 'cricket world cup', 'athletics record', 'swimming olympian', 'Formula One racing', 'boxing champion', 'soccer league'] },
  nature:     { id: 'nature',     name: 'Nature',       emoji: '🌿', shortName: 'Nature',  description: 'Animals, plants, ecosystems',   searchTerms: ['mammal species habitat', 'bird species migration', 'reptile amphibian', 'marine animal ocean', 'insect butterfly', 'flowering plant', 'tree species forest', 'endangered species', 'ecosystem biodiversity', 'tropical rainforest'] },
  music:      { id: 'music',      name: 'Music',        emoji: '🎵', shortName: 'Music',   description: 'Artists, genres, instruments',   searchTerms: ['music genre history', 'musical instrument', 'rock band album', 'classical composer symphony', 'jazz musician', 'pop singer', 'hip hop artist', 'opera composer', 'electronic music', 'music festival'] },
  movies:     { id: 'movies',     name: 'Movies & TV',  emoji: '🎬', shortName: 'Film',    description: 'Films, shows, directors',        searchTerms: ['film director filmography', 'television series season', 'animated film studio', 'documentary film', 'Academy Award winning', 'Hollywood movie', 'science fiction film', 'comedy film', 'horror film', 'television network'] },
  technology: { id: 'technology', name: 'Technology',   emoji: '💻', shortName: 'Tech',    description: 'Computers, AI, inventions',      searchTerms: ['computer science algorithm', 'artificial intelligence', 'programming language', 'internet protocol', 'robotics automation', 'spacecraft satellite', 'invention patent', 'electronics semiconductor', 'software engineering', 'mobile technology'] },
  food:       { id: 'food',       name: 'Food & Drink', emoji: '🍜', shortName: 'Food',    description: 'Cuisine, dishes, beverages',     searchTerms: ['cuisine traditional dish', 'cooking technique recipe', 'beverage drink history', 'fruit tropical', 'spice herb', 'dessert pastry', 'wine region vineyard', 'bread baking', 'cheese dairy', 'coffee tea'] },
}

const GAME_MODES = {
  classic: {
    id: 'classic',
    emoji: '🧭',
    name: 'Classic',
    description: 'Navigate from start to target in the fewest clicks.',
    rules: 'Find the target article using only Wikipedia links. No time limit.',
  },
  sprint: {
    id: 'sprint',
    emoji: '⏱️',
    name: 'Sprint',
    description: 'Reach the target before the timer runs out. Limit is set by difficulty or your own value.',
    rules: 'Reach the target before time runs out.',
    timeLimit: 120,
  },
  challenge: {
    id: 'challenge',
    emoji: '🎯',
    name: 'Click limit',
    description: 'Reach the target within a maximum number of link clicks. Limit is set by difficulty or custom.',
    rules: 'Reach the target within your click budget. Each link counts.',
    clickLimit: 6,
  },
  custom: {
    id: 'custom',
    emoji: '✏️',
    name: 'Custom',
    description: 'Pick the start and target articles yourself. Same rules as Classic.',
    rules: 'Navigate from your start article to your target using only Wikipedia links.',
  },
  freeplay: {
    id: 'freeplay',
    emoji: '🔮',
    name: 'Freeplay',
    description: 'Explore Wikipedia freely. No target, no pressure.',
    rules: 'Just explore! See how far the rabbit hole goes.',
    noTarget: true,
  },
  daily: {
    id: 'daily',
    emoji: '📅',
    name: 'Daily',
    description: "Today's challenge. Same pair for everyone.",
    rules: 'Complete the daily challenge. One pair per day!',
  },
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
const HISTORY_KEY = 'wikilink_history'
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

function loadHistory() {
  try { return JSON.parse(localStorage.getItem(HISTORY_KEY)) || [] }
  catch { return [] }
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
  })

  const timerInterval = ref(null)

  const currentMode = computed(() => state.mode ? GAME_MODES[state.mode] : null)
  const isPlaying = computed(() => state.status === 'playing')
  const isWon = computed(() => state.status === 'won')
  const isLost = computed(() => state.status === 'lost')
  const isGameOver = computed(() => state.status === 'won' || state.status === 'lost')

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
        state.timeRemaining = Math.max(0, state.effectiveTimeLimit - state.elapsed)
        if (state.timeRemaining <= 0) endGame('lost')
      }
    }, 100)
  }

  function stopTimer() {
    if (timerInterval.value) { clearInterval(timerInterval.value); timerInterval.value = null }
  }

  function initGame(mode, startArticle, targetArticle, genre = 'random', difficulty = 'normal', customLimits = {}) {
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
    startTimer()
  }

  function navigateTo(title) {
    if (!isPlaying.value) return false

    state.clicks++
    state.currentArticle = title
    state.path.push(title)

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

  function endGame(result) {
    state.status = result
    state.elapsed = Math.floor((Date.now() - state.startTime) / 1000)
    stopTimer()
    saveStats(result)
    saveHistory(result)
    if (state.mode === 'daily' && result === 'won') saveDailyResult()
    try {
      const api = useApi()
      const isLoggedIn = !!localStorage.getItem('wikilink_token')
      if (isLoggedIn) {
        api.post('/stats/game', {
          mode: state.mode,
          genre: state.genre || 'random',
          clicks: state.clicks,
          time: state.elapsed,
          won: result === 'won',
        }).catch(() => {})
      } else {
        api.post('/stats/increment', { clicks: state.clicks, won: result === 'won' }).catch(() => {})
      }
    } catch { /* ignore */ }
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
    else stats.genres[genre].gamesLost++

    localStorage.setItem(STATS_KEY, JSON.stringify(stats))
  }

  function saveHistory(result) {
    const history = loadHistory()
    history.unshift({
      date: new Date().toISOString(),
      mode: state.mode,
      genre: state.genre || 'random',
      difficulty: state.difficulty,
      result,
      clicks: state.clicks,
      time: state.elapsed,
      start: state.startArticle?.title,
      target: state.targetArticle?.title || null,
      path: [...state.path],
    })
    // Keep last 30 games
    if (history.length > 30) history.length = 30
    localStorage.setItem(HISTORY_KEY, JSON.stringify(history))
  }

  function saveDailyResult() {
    const daily = loadDaily()
    daily[utcDateKey()] = { completed: true, clicks: state.clicks, time: state.elapsed }
    localStorage.setItem(DAILY_KEY, JSON.stringify(daily))
  }

  function getStats() { return loadStats() }
  function getHistory() { return loadHistory() }
  function getDailyStatus() { return loadDaily()[utcDateKey()] || null }

  function goBack() {
    if (!isPlaying.value || state.path.length <= 1) return null
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
      saveHistory('finished')
    }
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
    state.timeRemaining = null
    state.effectiveTimeLimit = null
    state.effectiveClickLimit = null
  }

  return {
    state,
    GAME_MODES,
    GENRES,
    DIFFICULTIES,
    currentMode,
    isPlaying,
    isWon,
    isLost,
    isGameOver,
    formattedTime,
    formattedRemaining,
    initGame,
    navigateTo,
    goBack,
    endGame,
    endFreeplay,
    resetGame,
    getStats,
    getHistory,
    getDailyStatus,
    getEffectiveLimits,
    LIMIT_BOUNDS,
  }
}
