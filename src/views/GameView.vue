<template>
  <div class="min-h-screen flex flex-col">

    <!-- Navbar -->
    <GameTopNav
      :path-length="game.state.path.length"
      :is-playing="game.isPlaying.value"
      :is-won="game.isWon.value"
      :back-disabled="game.backDisabled.value"
      :can-reroll="canReroll"
      :rerolling="rerolling"
      :current-mode="currentMode"
      :initial-loading="initialLoading"
      :start-title="game.state.startArticle?.title || ''"
      :target-title="game.state.targetArticle?.title || ''"
      :has-target="!!game.state.targetArticle"
      :hints="game.state.hints"
      :show-hint-menu="showHintMenu"
      :clicks="game.state.clicks"
      :effective-click-limit="game.state.effectiveClickLimit"
      :click-limit-class="clickLimitClass"
      :formatted-remaining="game.formattedRemaining.value"
      :formatted-time="game.formattedTime.value"
      :timer-class="timerClass"
      :muted="sound.muted.value"
      :modifiers="game.state.modifiers"
      :game-modifiers="game.MODIFIERS"
      :modifier-svg-path="modifierSvgPath"
      :display-path="displayPath"
      :multiplayer-opponent="multiplayerOpponent"
      @quit="confirmQuit"
      @go-back="handleGoBack"
      @reroll="rerollPair"
      @finish-freeplay="finishFreeplay"
      @toggle-hint-menu="showHintMenu = !showHintMenu"
      @use-hint="useHintAction"
      @toggle-mute="sound.toggleMute()"
    />

    <!-- Hint result banner -->
    <transition name="hint-banner">
      <GameHintBanner
        :hint-result="hintResult"
        :hint-temperature="hintTemperature"
        :hint-banner-text-class="hintBannerTextClass"
        @dismiss="hintResult = null"
      />
    </transition>

    <!-- Content area -->
    <GameMainStage
      :initial-loading="initialLoading"
      :boot-step="bootStep"
      :loading-message="loadingMessage"
      :show-screen-shake="showScreenShake"
      :loading="wiki.loading.value"
      :error="wiki.error.value || ''"
      :article-data="articleData"
      :processed-html="processedHtml"
      :has-fog-modifier="game.state.modifiers.includes('fog')"
      @close-hint-menu="showHintMenu = false"
      @retry="loadArticle(game.state.currentArticle)"
      @link-click="handleLinkClick"
      @link-hover="handleLinkHover"
      @link-hover-end="handleLinkHoverEnd"
    />

    <!-- Article preview tooltip -->
    <div v-if="previewData && previewPos"
         class="wiki-preview-tooltip"
         :style="{ top: previewPos.y + 'px', left: previewPos.x + 'px' }">
      <div class="flex gap-2.5">
        <img v-if="previewData.thumbnail" :src="previewData.thumbnail" class="preview-thumb" alt="">
        <div class="min-w-0">
          <div class="preview-title">{{ previewData.title }}</div>
          <div v-if="previewData.description" class="font-mono text-[9px] text-crt-amber/60 mb-1">{{ previewData.description }}</div>
          <div class="preview-extract">{{ previewData.extract }}</div>
        </div>
      </div>
    </div>

    <!-- Victory / Loss / Freeplay Summary Modal -->
    <GameEndModal
      :show-end-modal="showEndModal"
      :end-modal-style="endModalStyle"
      :is-won="game.isWon.value"
      :freeplay-finished="freeplayFinished"
      :start-title="game.state.startArticle?.title || ''"
      :target-title="game.state.targetArticle?.title || ''"
      :effective-time-limit="game.state.effectiveTimeLimit"
      :path-length="game.state.path.length"
      :clicks="game.state.clicks"
      :formatted-time="game.formattedTime.value"
      :xp-reward-data="xpRewardData"
      :hints-used="game.state.hintsUsed"
      :combo="game.state.combo"
      :modifiers="game.state.modifiers"
      :game-modifiers="game.MODIFIERS"
      :modifier-svg-path="modifierSvgPath"
      :path="game.state.path"
      :daily-mode="props.mode === 'daily'"
      :daily-leaderboard="dailyLeaderboard"
      :format-leaderboard-time="formatLeaderboardTime"
      :match-result="matchResult"
      :lobby-result="lobbyResult"
      :lobby-your-rank="lobbyYourRank"
      :lobby-disconnected-count="lobbyDisconnectedCount"
      :current-user-id="auth.user.value?.id ?? null"
      :show-confetti="showConfetti"
      :confetti-style="confettiStyle"
      @copy-share="copyShareCard"
      @share-challenge="shareChallenge"
      @go-home="goHome"
      @play-again="playAgain"
    />

    <!-- Combo counter -->
    <transition name="fade">
      <div v-if="comboDisplay" :key="comboDisplay.key" class="combo-float"
           style="right: 120px; top: 60px;"
           :style="{ color: comboDisplay.count >= 5 ? '#ff2ecc' : comboDisplay.count >= 3 ? '#ffbf00' : '#00e5ff' }">
        COMBO x{{ comboDisplay.count }}
      </div>
    </transition>

    <!-- Red vignette on loss -->
    <div v-if="showRedVignette" class="red-vignette"></div>

    <!-- Level up overlay -->
    <transition name="fade">
      <div v-if="showLevelUp" class="level-up-overlay">
        <div class="level-up-card text-center animate-scale-in">
          <div class="level-up-icon-wrap">
            <svg class="w-7 h-7 text-crt-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
            </svg>
          </div>
          <div class="font-pixel text-[10px] text-crt-green tracking-[0.3em] mb-2">LEVEL UP!</div>
          <div class="font-pixel text-5xl text-neon-green mb-2 leading-none">{{ progression.level.value }}</div>
          <div class="font-mono text-sm text-retro-light">Your power just increased</div>
          <div class="font-mono text-[11px] text-crt-green/80 mt-1 tracking-wide">Keep going for more unlocks</div>
        </div>
      </div>
    </transition>

    <!-- Achievement unlock toasts -->
    <TransitionGroup name="fade" tag="div" class="fixed top-4 left-1/2 -translate-x-1/2 z-[75] flex flex-col gap-2">
      <div v-for="a in achievementToasts" :key="a.key" class="achievement-toast">
        <div class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0" style="background: rgba(255,215,0,0.1); border: 1px solid rgba(255,215,0,0.25);">
          <svg class="w-5 h-5 text-arcade-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
          </svg>
        </div>
        <div>
          <div class="font-pixel text-[7px] text-arcade-gold tracking-wider">ACHIEVEMENT UNLOCKED</div>
          <div class="font-mono text-[12px] text-crt-white">{{ a.name }}</div>
          <div class="font-mono text-[9px] text-crt-green">+{{ a.xp }} XP</div>
        </div>
      </div>
    </TransitionGroup>

    <GameQuitModals
      :show-quit-confirm="showQuitConfirm"
      :show-opponent-quit-modal="showOpponentQuitModal"
      :opponent-quit-name="opponentQuitName"
      :show-lobby-player-left-modal="showLobbyPlayerLeftModal"
      :lobby-left-players-label="lobbyLeftPlayersLabel"
      @close-quit="showQuitConfirm = false"
      @go-home="goHome"
      @quit-after-opponent-left="quitAfterOpponentLeft"
      @resume-solo-opponent="resumeSoloAfterOpponentQuit"
      @quit-after-lobby-left="quitAfterLobbyPlayerLeft"
      @resume-solo-lobby="resumeSoloAfterLobbyPlayerLeft"
    />

  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useWikipedia } from '../composables/useWikipedia'
import { useGame } from '../composables/useGame'
import { useSound } from '../composables/useSound'
import { useToast } from '../composables/useToast'
import { useAuth } from '../composables/useAuth'
import { useApi } from '../composables/useApi'
import { useTrending } from '../composables/useTrending'
import { useProgression } from '../composables/useProgression'
import { useAchievements } from '../composables/useAchievements'
import { saveMultiplayerSession, loadMultiplayerSession, clearMultiplayerSession } from '../utils/multiplayerSession'
import { saveDailySession, loadDailySession, clearDailySession } from '../utils/dailySession'
import { utcDateKey } from '../utils/dailySeed'
import GameHintBanner from '../components/game/GameHintBanner.vue'
import GameTopNav from '../components/game/GameTopNav.vue'
import GameMainStage from '../components/game/GameMainStage.vue'
import GameEndModal from '../components/game/GameEndModal.vue'
import GameQuitModals from '../components/game/GameQuitModals.vue'

const props = defineProps({
  mode: { type: String, required: true }
})

const router = useRouter()
const route = useRoute()
const wiki = useWikipedia()
const game = useGame()
const sound = useSound()
const toast = useToast()

const genreId = computed(() => route.query.genre || 'random')
const genre = computed(() => game.GENRES[genreId.value] || game.GENRES.random)
const difficulty = computed(() => route.query.difficulty || 'normal')

const customLimitsFromRoute = computed(() => ({
  timeLimit: route.query.timeLimit != null && route.query.timeLimit !== ''
    ? Number(route.query.timeLimit)
    : undefined,
  clickLimit: route.query.clickLimit != null && route.query.clickLimit !== ''
    ? Number(route.query.clickLimit)
    : undefined,
}))

const loadingMessage = computed(() => {
  if (props.mode === 'daily') return 'Loading daily challenge...'
  if (props.mode === 'trending') return 'Fetching trending articles...'
  if (genreId.value !== 'random') return `Scanning ${genre.value.name} topics...`
  return 'Randomizing challenge...'
})

const articleData = ref(null)
const initialLoading = ref(true)
const showQuitConfirm = ref(false)
const freeplayFinished = ref(false)
const validLinks = ref(new Set())
const bootStep = ref(0)

// Preview tooltip state
const previewData = ref(null)
const previewPos = ref(null)
let hoverTimer = null
let hoveredLink = null
let previewCache = {}

const auth = useAuth()
const api = useApi()
const trending = useTrending()
const progression = useProgression()
const achievements = useAchievements()

const rerolling = ref(false)
const showConfetti = ref(false)
const dailyLeaderboard = ref([])
const matchCode = computed(() => route.query.match || null)
const lobbyCode = computed(() => route.query.lobby || null)
const matchResult = ref(null)
const matchPolling = ref(false)
let matchPollTimerId = null
let matchPresencePollTimerId = null
const RESULT_POLL = { min: 800, max: 4000, step: 400 }
const LOBBY_POLL = { min: 800, max: 4000, step: 400 }
const PRESENCE_POLL = { min: 600, max: 2400, step: 300 }
const matchPollDelayMs = ref(RESULT_POLL.min)
const lobbyPollDelayMs = ref(LOBBY_POLL.min)
const presencePollDelayMs = ref(PRESENCE_POLL.min)
const showOpponentQuitModal = ref(false)
const opponentQuitName = ref('')
const resumedSoloAfterQuit = ref(false)
const OPPONENT_QUIT_ACK_KEY = 'wikilink_opponent_quit_ack_v1'
const lobbyResult = ref(null)
const lobbyPolling = ref(false)
let lobbyPollTimerId = null
let multiplayerPersistTimerId = null
let dailyPersistTimerId = null
let multiplayerHeartbeatTimerId = null
let activityHeartbeatTimerId = null
let immediateRefreshTimerId = null
const showLobbyPlayerLeftModal = ref(false)
const lobbyLeftPlayerNames = ref([])
const lobbyLeftPlayersLabel = computed(() => {
  const names = lobbyLeftPlayerNames.value.filter(Boolean)
  if (names.length === 0) return 'A player'
  if (names.length === 1) return names[0]
  if (names.length === 2) return `${names[0]} and ${names[1]}`
  return `${names[0]}, ${names[1]} and ${names.length - 2} others`
})
let lastActivityHeartbeatAt = 0
const ACTIVITY_HEARTBEAT_MIN_GAP_MS = 10000
const IMMEDIATE_REFRESH_THROTTLE_MS = 2000
let lastImmediateRefreshAt = 0

function nextPollDelay(currentDelay, hasFreshChange, config) {
  if (hasFreshChange) return config.min
  return Math.min(currentDelay + config.step, config.max)
}

const lobbyYourRank = computed(() => {
  const lb = lobbyResult.value?.leaderboard
  const uid = auth.user.value?.id
  if (!lb || uid == null) return null
  const row = lb.find(r => r.user_id === uid)
  return row ? row.rank : null
})
const lobbyDisconnectedCount = computed(() => {
  const players = Array.isArray(lobbyResult.value?.players) ? lobbyResult.value.players : []
  return players.filter(p => p?.presence?.is_in_reconnect_grace).length
})
const showScreenShake = ref(false)
const showRedVignette = ref(false)
const comboDisplay = ref(null)
const xpRewardData = ref(null)
const achievementToasts = ref([])
const showLevelUp = ref(false)
const showHintMenu = ref(false)
const hintResult = ref(null)
const targetCategoriesCache = ref(null)
const isLeavingGame = ref(false)
const activeModifiers = computed(() => {
  const modParam = route.query.modifiers
  if (!modParam) return []
  const parsed = modParam.split(',').filter(Boolean)
  const known = parsed.filter(id => game.MODIFIERS[id])
  const isMultiplayerRoute = !!route.query.match || !!route.query.lobby
  const valid = known
    .filter(id => isMultiplayerRoute || id !== 'suddenDeath')
  if (known.length !== parsed.length) {
    toast.error('Invalid modifier detected — removed unknown modifiers')
  }
  return valid
})

const currentMode = computed(() => game.currentMode.value)
const isMultiplayerGame = computed(() => !!matchCode.value || !!lobbyCode.value)
const multiplayerOpponent = computed(() => {
  if (!isMultiplayerGame.value) return null

  if (matchCode.value) {
    const opponent = matchResult.value?.opponent || null
    const name = (matchResult.value?.opponent?.username || '').trim()
    return {
      label: name ? `vs ${name}` : 'vs Opponent',
      profile_icon: opponent?.profile_icon || 'rookie',
      profile_accent: opponent?.profile_accent || 'rank',
    }
  }

  if (lobbyCode.value) {
    const players = Array.isArray(lobbyResult.value?.players) ? lobbyResult.value.players : []
    const uid = auth.user.value?.id
    const others = players.filter(p => p?.user_id != null && p.user_id !== uid)
    if (others.length === 0) {
      return {
        label: 'Lobby',
        profile_icon: 'rookie',
        profile_accent: 'rank',
      }
    }
    if (others.length === 1) {
      const name = (others[0]?.username || '').trim()
      return {
        label: name ? `vs ${name}` : 'vs 1 player',
        profile_icon: others[0]?.profile_icon || 'rookie',
        profile_accent: others[0]?.profile_accent || 'rank',
      }
    }
    return {
      label: `vs ${others.length} players`,
      profile_icon: 'rookie',
      profile_accent: 'rank',
    }
  }

  return null
})
const sessionRouteQuery = computed(() => {
  const out = {}
  for (const [key, value] of Object.entries(route.query || {})) {
    if (typeof value === 'string' && value.length) out[key] = value
  }
  return out
})

function isSuddenDeathMultiplayer(result) {
  const modifiers = Array.isArray(result?.settings?.modifiers) ? result.settings.modifiers : []
  return (result?.settings?.mode || '') === 'sudden' || modifiers.includes('suddenDeath')
}

function applySuddenDeathRemoteFinish(result, source = 'match') {
  if (!isSuddenDeathMultiplayer(result)) return
  if (result?.status !== 'finished') return
  if (!game.isPlaying.value) return

  if (source === 'match') {
    if (result?.winner === 'you') game.endGame('won')
    else game.endGame('lost')
    return
  }

  const uid = auth.user.value?.id
  const leaderboard = Array.isArray(result?.leaderboard) ? result.leaderboard : []
  const me = leaderboard.find(row => row?.user_id === uid)
  if (me && Number(me.rank) === 1) game.endGame('won')
  else game.endGame('lost')
}

function loadOpponentQuitAckMap() {
  try {
    const parsed = JSON.parse(localStorage.getItem(OPPONENT_QUIT_ACK_KEY) || '{}')
    return parsed && typeof parsed === 'object' ? parsed : {}
  } catch {
    return {}
  }
}

function saveOpponentQuitAckMap(map) {
  try {
    localStorage.setItem(OPPONENT_QUIT_ACK_KEY, JSON.stringify(map))
  } catch {
    // ignore storage failures
  }
}

function isOpponentQuitAcked(code) {
  const uid = auth.user.value?.id
  if (!code || uid == null) return false
  const map = loadOpponentQuitAckMap()
  return !!map[`${uid}:${code}`]
}

function markOpponentQuitAcked(code) {
  const uid = auth.user.value?.id
  if (!code || uid == null) return
  const map = loadOpponentQuitAckMap()
  map[`${uid}:${code}`] = Date.now()
  saveOpponentQuitAckMap(map)
}

const MODIFIER_SVG_PATHS = {
  fog: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />',
  blackout: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />',
  noback: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />',
  speedDecay: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />',
}
function modifierSvgPath(modId) {
  return MODIFIER_SVG_PATHS[modId] || MODIFIER_SVG_PATHS.fog
}

const hintTemperature = computed(() => {
  if (!hintResult.value || hintResult.value.type !== 'hotcold') return 0
  const text = hintResult.value.text.toLowerCase()
  if (text.includes('burning')) return 100
  if (text.includes('hot')) return 75
  if (text.includes('warm')) return 55
  if (text.includes('lukewarm')) return 35
  if (text.includes('cold')) return 10
  if (text.includes('checking')) return 50
  return 50
})

const hintBannerTextClass = computed(() => {
  if (!hintResult.value) return ''
  if (hintResult.value.type === 'category') return 'text-crt-cyan'
  const text = hintResult.value.text.toLowerCase()
  if (text.includes('burning')) return 'text-crt-red'
  if (text.includes('hot')) return 'text-crt-amber'
  if (text.includes('warm')) return 'text-crt-amber/80'
  if (text.includes('lukewarm')) return 'text-retro-light'
  if (text.includes('cold')) return 'text-crt-cyan'
  return 'text-retro-light'
})

const canReroll = computed(() => {
  if (isMultiplayerGame.value) return false
  const m = props.mode
  return m !== 'daily' && m !== 'custom' && m !== 'freeplay'
})

const showEndModal = computed(() => {
  if (showOpponentQuitModal.value || showLobbyPlayerLeftModal.value) return false
  return game.isGameOver.value || freeplayFinished.value
})

const endModalStyle = computed(() => {
  if (freeplayFinished.value) return 'background: #0d0e15; border: 2px solid rgba(0,229,255,0.4); box-shadow: 0 0 40px rgba(0,229,255,0.15);'
  return game.isWon.value
    ? 'background: #0d0e15; border: 2px solid rgba(57,255,20,0.4); box-shadow: 0 0 40px rgba(57,255,20,0.15), 0 0 80px rgba(57,255,20,0.05);'
    : 'background: #0d0e15; border: 2px solid rgba(255,68,68,0.4); box-shadow: 0 0 40px rgba(255,68,68,0.15), 0 0 80px rgba(255,68,68,0.05);'
})

const displayPath = computed(() => {
  const path = game.state.path
  if (!path.length) return []

  return path.map((a, i) => ({
    type: 'article',
    label: a.replace(/_/g, ' '),
    active: i === path.length - 1,
  }))
})

const clickLimitClass = computed(() => {
  if (!game.state.effectiveClickLimit) return 'text-crt-white'
  const ratio = game.state.clicks / game.state.effectiveClickLimit
  if (ratio >= 0.8) return 'text-crt-red'
  if (ratio >= 0.5) return 'text-crt-amber'
  return 'text-crt-white'
})

const timerClass = computed(() => {
  if (game.state.timeRemaining === null) return ''
  if (game.state.timeRemaining <= 5) return 'text-crt-red timer-critical'
  if (game.state.timeRemaining <= 15) return 'text-crt-red timer-urgent'
  if (game.state.timeRemaining <= 30) return 'text-crt-amber'
  return 'text-crt-white'
})

// Play sounds on game state changes; submit daily score; show confetti; award XP; check achievements
watch(() => game.state.status, (val) => {
  if (val === 'won') {
    if (props.mode === 'daily') clearDailySession()
    sound.playWin()
    showConfetti.value = true
    setTimeout(() => showConfetti.value = false, 3500)
    submitDailyScore()
    submitMatchResult()
    submitLobbyResult()
    loadDailyLeaderboard()
    processPostGame(true)
  } else if (val === 'lost') {
    if (props.mode === 'daily') clearDailySession()
    sound.playLose()
    showScreenShake.value = true
    showRedVignette.value = true
    setTimeout(() => { showScreenShake.value = false; showRedVignette.value = false }, 600)
    submitMatchResult()
    submitLobbyResult()
    loadDailyLeaderboard()
    processPostGame(false)
  }
})

watch(() => game.state.combo, (val) => {
  if (val >= 2) {
    sound.playCombo(val)
    comboDisplay.value = { count: val, key: Date.now() }
    setTimeout(() => { if (comboDisplay.value?.count === val) comboDisplay.value = null }, 1000)
  }
})

function processPostGame(won) {
  if (isMultiplayerGame.value) {
    // Multiplayer rounds do not grant XP rewards.
    xpRewardData.value = null
    return
  }

  const stats = game.getStats()

  const xpResult = progression.awardXp({
    won,
    mode: game.state.mode,
    difficulty: game.state.difficulty,
    clicks: game.state.clicks,
    elapsed: game.state.elapsed,
    streak: auth.streak?.value || 0,
    hintsUsed: game.state.hintsUsed,
    modifiers: game.state.modifiers,
  })
  xpRewardData.value = xpResult

  if (xpResult.leveledUp) {
    showLevelUp.value = true
    sound.playLevelUp()
    setTimeout(() => showLevelUp.value = false, 2500)
  }

  const dailyCompletions = game.getDailyCompletions()

  const newUnlocks = achievements.checkAchievements({
    won,
    mode: game.state.mode,
    clicks: game.state.clicks,
    elapsed: game.state.elapsed,
    timeRemaining: game.state.timeRemaining,
    stats,
    hintsUsed: game.state.hintsUsed,
    modifiers: game.state.modifiers,
    dailyCompletions,
  })

  if (newUnlocks.length > 0) {
    const unlocked = achievements.consumeRecentUnlocks()
    const achievementXp = unlocked.reduce((sum, a) => sum + (Number(a?.xp) || 0), 0)
    if (achievementXp > 0) {
      const bonusResult = progression.awardBonusXp(achievementXp, 'Achievement XP')
      xpRewardData.value = {
        ...xpRewardData.value,
        rewards: [...(xpRewardData.value?.rewards || []), ...bonusResult.rewards],
        totalReward: (xpRewardData.value?.totalReward || 0) + bonusResult.totalReward,
      }
      if (bonusResult.leveledUp) {
        showLevelUp.value = true
        sound.playLevelUp()
        setTimeout(() => showLevelUp.value = false, 2500)
      }
    }
    unlocked.forEach((a, i) => {
      setTimeout(() => {
        sound.playAchievement()
        achievementToasts.value.push({ ...a, key: Date.now() })
        setTimeout(() => achievementToasts.value.shift(), 3500)
      }, i * 800)
    })
  }
}

function buildProcessedHtml(data) {
  if (!data?.html) return { html: '', allowedTitles: new Set() }
  const parser = new DOMParser()
  const doc = parser.parseFromString(data.html, 'text/html')

  doc.querySelectorAll(
    '.mw-editsection, .reference, .reflist, .navbox, .sistersitebox, ' +
    '.mw-empty-elt, .noprint, .mw-authority-control, .catlinks, ' +
    '#coordinates, .metadata, .ambox, .dmbox, .tmbox, .mbox-small, ' +
    '.portal, .wikitable.collapsible, .sidebar-collapse, .mw-references-wrap'
  ).forEach(el => el.remove())

  const links = doc.querySelectorAll('a[href]')
  const allowedTitles = new Set(data.links || [])

  links.forEach(link => {
    const href = link.getAttribute('href')
    if (href && href.startsWith('/wiki/')) {
      const title = decodeURIComponent(href.replace('/wiki/', '').replace(/_/g, ' '))
      if (allowedTitles.has(title) || allowedTitles.has(title.replace(/ /g, '_'))) {
        link.setAttribute('data-wiki-title', title)
        link.setAttribute('href', 'javascript:void(0)')
        link.removeAttribute('title')
        link.classList.add('wiki-link')
      } else {
        link.removeAttribute('href')
        link.removeAttribute('title')
        link.style.color = '#555770'
        link.style.textDecoration = 'none'
        link.style.cursor = 'default'
      }
    } else if (href && (href.startsWith('http') || href.startsWith('//'))) {
      link.removeAttribute('href')
      link.removeAttribute('title')
      link.style.color = '#555770'
      link.style.textDecoration = 'none'
      link.style.cursor = 'default'
    }
  })

  if (game.state.modifiers.includes('blackout')) {
    const wikiLinks = doc.querySelectorAll('.wiki-link')
    const seed = game.state.clicks + game.state.path.length
    wikiLinks.forEach((link, idx) => {
      const hash = (idx * 2654435761 + seed * 131) >>> 0
      if ((hash % 100) < 30) {
        link.classList.add('blackout-disabled')
        link.removeAttribute('data-wiki-title')
      }
    })
  }

  return { html: doc.body.innerHTML, allowedTitles }
}

const processedResult = computed(() => buildProcessedHtml(articleData.value))
const processedHtml = computed(() => processedResult.value.html)

watch(processedResult, (result) => {
  validLinks.value = result.allowedTitles
})

function normalizeTitleForRestore(title) {
  return (title || '').trim().replace(/_/g, ' ').toLowerCase()
}

async function tryRestoreDailyGame(pair) {
  if (props.mode !== 'daily') return false

  const saved = loadDailySession()
  if (!saved?.snapshot) return false

  const savedDate = saved.date
  if (savedDate && savedDate !== utcDateKey()) return false
  if (!savedDate) return false

  const currentUserId = auth.user.value?.id ?? null
  const savedUserId = saved.userId ?? null
  // Allow restoring across login state changes (guest <-> logged-in), but prevent restoring when
  // two different authenticated users are involved.
  if (savedUserId != null && currentUserId != null && savedUserId !== currentUserId) return false

  const savedSnapshot = saved.snapshot
  if (savedSnapshot.status !== 'playing') return false

  const savedStartTitle = savedSnapshot.startArticle?.title || saved.pair?.startTitle
  const savedTargetTitle = savedSnapshot.targetArticle?.title || saved.pair?.targetTitle
  if (!savedStartTitle || !savedTargetTitle) return false

  if (normalizeTitleForRestore(savedStartTitle) !== normalizeTitleForRestore(pair.start.title)) return false
  if (normalizeTitleForRestore(savedTargetTitle) !== normalizeTitleForRestore(pair.end.title)) return false

  const restored = game.restoreSnapshot(savedSnapshot)
  if (!restored) return false

  await loadArticle(savedSnapshot.currentArticle)
  toast.info('Daily progress restored')
  return true
}

function persistDailySession() {
  if (props.mode !== 'daily') return
  if (!game.isPlaying.value) return

  const snapshot = game.exportSnapshot()
  if (!snapshot) return

  saveDailySession({
    date: utcDateKey(),
    userId: auth.user.value?.id ?? null,
    snapshot,
  })
}

async function initializeGame() {
  isLeavingGame.value = false
  initialLoading.value = true
  freeplayFinished.value = false
  bootStep.value = 0
  previewCache = {}
  targetCategoriesCache.value = null

  setTimeout(() => bootStep.value = 1, 300)
  setTimeout(() => bootStep.value = 2, 800)
  setTimeout(() => bootStep.value = 3, 1300)

  try {
    if (await tryRestoreMultiplayerGame()) {
      return
    }

    const multiplayerFrom = typeof route.query.from === 'string' ? route.query.from.trim() : ''
    const multiplayerTo = typeof route.query.to === 'string' ? route.query.to.trim() : ''
    if (isMultiplayerGame.value && multiplayerFrom && multiplayerTo) {
      const [startArticle, targetArticle] = await Promise.all([
        wiki.getArticleSummary(multiplayerFrom),
        wiki.getArticleSummary(multiplayerTo),
      ])
      if (!startArticle || !targetArticle) {
        toast.error('Could not load multiplayer articles')
        router.push({ name: 'home' })
        return
      }
      game.initGame(
        props.mode,
        startArticle,
        targetArticle,
        genreId.value,
        difficulty.value,
        customLimitsFromRoute.value,
        activeModifiers.value
      )
      await loadArticle(startArticle.title)
      sound.playStart()
      persistMultiplayerSession()
      return
    }

    if (props.mode === 'daily') {
      let pair = null
      try {
        const serverDaily = await api.get('/daily')
        if (serverDaily.start && serverDaily.end) {
          pair = { start: serverDaily.start, end: serverDaily.end }
        }
      } catch { /* fall through */ }
      if (!pair) {
        pair = await wiki.getDailyPair()
        if (pair) {
          try { await api.post('/daily', { start: pair.start, end: pair.end }) } catch { /* ignore */ }
        }
      }
      if (!pair) { toast.error('Failed to load daily challenge'); router.push({ name: 'home' }); return }
      const restored = await tryRestoreDailyGame(pair)
      if (!restored) {
        game.initGame('daily', pair.start, pair.end, 'random', 'normal')
        await loadArticle(pair.start.title)
      }
    } else if (props.mode === 'custom') {
      const codeQ = typeof route.query.code === 'string' ? route.query.code.trim() : ''
      let fromQ = typeof route.query.from === 'string' ? route.query.from.trim() : ''
      let toQ = typeof route.query.to === 'string' ? route.query.to.trim() : ''
      if (codeQ && (!fromQ || !toQ)) {
        try {
          const ch = await api.get(`/challenge/${codeQ}`)
          fromQ = ch.start_title || fromQ
          toQ = ch.end_title || toQ
        } catch {
          toast.error('Challenge not found')
          router.push({ name: 'home' })
          return
        }
      }
      if (!fromQ || !toQ) { toast.error('Custom game needs start and target articles'); router.push({ name: 'home' }); return }
      if (fromQ.toLowerCase() === toQ.toLowerCase()) { toast.error('Start and target must be different articles'); router.push({ name: 'home' }); return }
      const [startArticle, targetArticle] = await Promise.all([
        wiki.getArticleSummary(fromQ),
        wiki.getArticleSummary(toQ),
      ])
      if (!startArticle || !targetArticle) { toast.error('Could not find one or both articles. Check the titles and try again.'); router.push({ name: 'home' }); return }
      game.initGame('custom', startArticle, targetArticle, 'random', 'normal')
      await loadArticle(startArticle.title)
    } else if (props.mode === 'trending') {
      const pair = await trending.getTrendingPair()
      if (!pair) {
        toast.warn('Trending data unavailable, falling back to random')
        const fallback = await wiki.getRandomPairByGenre(genre.value)
        if (!fallback) { toast.error('Could not find article pair'); router.push({ name: 'home' }); return }
        game.initGame('trending', fallback.start, fallback.end, 'random', 'normal', {}, activeModifiers.value)
        await loadArticle(fallback.start.title)
      } else {
        game.initGame('trending', pair.start, pair.end, 'random', 'normal', {}, activeModifiers.value)
        await loadArticle(pair.start.title)
      }
    } else if (game.GAME_MODES[props.mode]?.noTarget) {
      const startArticle = await wiki.getRandomArticleByGenre(genre.value)
      if (!startArticle) { toast.error('Failed to load article'); router.push({ name: 'home' }); return }
      game.initGame(props.mode, startArticle, null, genreId.value, difficulty.value, customLimitsFromRoute.value, activeModifiers.value)
      await loadArticle(startArticle.title)
    } else {
      const pair = await wiki.getRandomPairByGenre(genre.value)
      if (!pair) { toast.error('Could not find article pair'); router.push({ name: 'home' }); return }
      game.initGame(props.mode, pair.start, pair.end, genreId.value, difficulty.value, customLimitsFromRoute.value, activeModifiers.value)
      await loadArticle(pair.start.title)
    }

    sound.playStart()
    persistMultiplayerSession()
  } finally {
    initialLoading.value = false
  }
}

function activeMultiplayerCode() {
  return matchCode.value || lobbyCode.value || null
}

function persistMultiplayerSession() {
  if (!isMultiplayerGame.value) return
  const code = activeMultiplayerCode()
  if (!code) return
  const snapshot = game.exportSnapshot()
  if (!snapshot) return
  saveMultiplayerSession({
    type: matchCode.value ? 'match' : 'lobby',
    code,
    userId: auth.user.value?.id || null,
    route: {
      mode: props.mode,
      query: sessionRouteQuery.value,
    },
    snapshot,
  })
}

async function sendMultiplayerHeartbeat() {
  if (!auth.isLoggedIn() || !isMultiplayerGame.value) return
  if (matchCode.value) {
    await api.post(`/match/ping/${matchCode.value}`).catch(() => {})
    return
  }
  if (lobbyCode.value) {
    await api.post(`/lobby/ping/${lobbyCode.value}`).catch(() => {})
  }
}

function scheduleActivityHeartbeat() {
  if (!auth.isLoggedIn() || !isMultiplayerGame.value) return
  const now = Date.now()
  const delta = now - lastActivityHeartbeatAt

  if (delta >= ACTIVITY_HEARTBEAT_MIN_GAP_MS) {
    lastActivityHeartbeatAt = now
    sendMultiplayerHeartbeat()
    return
  }

  const waitMs = ACTIVITY_HEARTBEAT_MIN_GAP_MS - delta
  if (activityHeartbeatTimerId) return
  activityHeartbeatTimerId = setTimeout(() => {
    activityHeartbeatTimerId = null
    if (!auth.isLoggedIn() || !isMultiplayerGame.value) return
    lastActivityHeartbeatAt = Date.now()
    sendMultiplayerHeartbeat()
  }, waitMs)
}

function onMeaningfulActivity() {
  scheduleActivityHeartbeat()
  if (game.isPlaying.value && !showOpponentQuitModal.value && !showLobbyPlayerLeftModal.value) {
    triggerImmediateRefresh()
  }
}

function onVisibilityOrFocus() {
  if (document.visibilityState === 'visible') {
    scheduleActivityHeartbeat()
    triggerImmediateRefresh()
    if (props.mode === 'daily' && showEndModal.value) loadDailyLeaderboard()
  }
}

async function tryRestoreMultiplayerGame() {
  if (!isMultiplayerGame.value) return false
  const saved = loadMultiplayerSession()
  if (!saved || !saved.snapshot || !saved.route) return false
  const currentUserId = auth.user.value?.id ?? null
  const hasToken = !!localStorage.getItem('wikilink_token')
  if (saved.userId != null && currentUserId != null && saved.userId !== currentUserId) return false
  if (saved.userId != null && currentUserId == null && !hasToken) return false
  if (saved.route.mode !== props.mode) return false
  const code = activeMultiplayerCode()
  if (!code || saved.code !== code) return false
  const titleValue = (value) => {
    if (value && typeof value === 'object') {
      if (typeof value.title === 'string') return value.title
      return ''
    }
    return typeof value === 'string' ? value : ''
  }
  const normalizeTitle = (value) => titleValue(value).trim().replace(/_/g, ' ').toLowerCase()
  const routeFrom = normalizeTitle(route.query.from)
  const routeTo = normalizeTitle(route.query.to)
  const savedFrom = normalizeTitle(saved.route?.query?.from)
  const savedTo = normalizeTitle(saved.route?.query?.to)
  const snapshotFrom = normalizeTitle(saved.snapshot.startArticle)
  const snapshotTo = normalizeTitle(saved.snapshot.targetArticle)
  if (routeFrom && snapshotFrom && routeFrom !== snapshotFrom) return false
  if (routeTo && snapshotTo && routeTo !== snapshotTo) return false
  if (routeFrom && savedFrom && routeFrom !== savedFrom) return false
  if (routeTo && savedTo && routeTo !== savedTo) return false

  const restored = game.restoreSnapshot(saved.snapshot)
  if (!restored) return false
  await loadArticle(saved.snapshot.currentArticle)
  if (saved.snapshot.status === 'playing') {
    toast.info('Restored multiplayer session')
  }
  return true
}

async function loadArticle(title) {
  const data = await wiki.getArticleContent(title)
  if (data) {
    articleData.value = data
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }
}

function dismissPreview() {
  clearTimeout(hoverTimer)
  hoveredLink = null
  previewData.value = null
  previewPos.value = null
}

function handleLinkClick(event) {
  dismissPreview()
  showHintMenu.value = false
  const link = event.target.closest('[data-wiki-title]')
  if (!link || game.isGameOver.value) return

  const title = link.getAttribute('data-wiki-title')
  if (!title) return

  const normalizedTitle = title.replace(/ /g, '_')
  const isValid = validLinks.value.has(title) || validLinks.value.has(normalizedTitle)
    || validLinks.value.has(title.replace(/_/g, ' '))
  if (!isValid) {
    toast.error('Invalid link — this page is not reachable from here')
    return
  }

  sound.playNavigate()
  const gameOver = game.navigateTo(title)
  triggerImmediateRefresh()
  if (!gameOver || game.isWon.value) {
    loadArticle(title)
  }
}

function handleLinkHover(event) {
  const link = event.target.closest('[data-wiki-title]')
  if (!link || link === hoveredLink) return

  hoveredLink = link
  clearTimeout(hoverTimer)
  previewData.value = null
  previewPos.value = null

  const title = link.getAttribute('data-wiki-title')
  if (!title) return

  hoverTimer = setTimeout(async () => {
    if (hoveredLink !== link) return

    const rect = link.getBoundingClientRect()
    const x = Math.min(rect.left, window.innerWidth - 340)
    const y = rect.bottom + 8
    const pos = { x: Math.max(8, x), y: Math.min(y, window.innerHeight - 140) }

    if (previewCache[title]) {
      previewData.value = previewCache[title]
      previewPos.value = pos
      return
    }

    const summary = await wiki.getArticleSummary(title)
    if (summary && hoveredLink === link) {
      previewCache[title] = summary
      previewData.value = summary
      previewPos.value = pos
    }
  }, 500)
}

function handleLinkHoverEnd(event) {
  const related = event.relatedTarget
  if (related && hoveredLink && hoveredLink.contains(related)) return
  dismissPreview()
}

async function handleGoBack() {
  if (game.backDisabled.value) {
    const reason = game.state.modifiers.includes('noback')
      ? 'No Back modifier is active!'
      : `Back is disabled in ${game.GAME_MODES[game.state.mode]?.name || 'this'} mode!`
    toast.warn(reason)
    return
  }
  const title = game.goBack()
  if (title) {
    sound.playBack()
    toast.info('Went back one page')
    triggerImmediateRefresh()
    await loadArticle(title)
  }
}

async function fetchArticleCategories(title) {
  try {
    const params = new URLSearchParams({
      action: 'query', titles: title, prop: 'categories',
      cllimit: '50', clshow: '!hidden',
      format: 'json', origin: '*',
    })
    const res = await fetch(`https://en.wikipedia.org/w/api.php?${params}`)
    if (!res.ok) return []
    const data = await res.json()
    const page = Object.values(data.query?.pages || {})[0]
    return (page?.categories || []).map(c => c.title.replace('Category:', ''))
  } catch {
    return []
  }
}

async function useHintAction(type) {
  if (!game.useHint()) return
  sound.playHint()
  triggerImmediateRefresh()
  showHintMenu.value = false

  if (type === 'category' && game.state.targetArticle) {
    hintResult.value = { type: 'category', text: `Target category: ${game.state.targetArticle.description || 'Unknown'}` }
  } else if (type === 'hotcold') {
    const targetTitle = game.state.targetArticle?.title
    if (!targetTitle) return

    const currentLinks = articleData.value?.links || []
    const targetNorm = targetTitle.replace(/_/g, ' ').toLowerCase()

    if (currentLinks.some(l => l.replace(/_/g, ' ').toLowerCase() === targetNorm)) {
      hintResult.value = { type: 'hotcold', text: 'BURNING HOT! The target is linked on this page!' }
    } else {
      hintResult.value = { type: 'hotcold', text: 'Checking temperature...' }
      try {
        if (!targetCategoriesCache.value) {
          targetCategoriesCache.value = await fetchArticleCategories(targetTitle)
        }
        const currentCats = await fetchArticleCategories(game.state.currentArticle)
        const targetSet = new Set(targetCategoriesCache.value.map(c => c.toLowerCase()))
        const overlap = currentCats.filter(c => targetSet.has(c.toLowerCase())).length

        if (overlap >= 5) hintResult.value = { type: 'hotcold', text: `Hot! You share ${overlap} categories with the target.` }
        else if (overlap >= 3) hintResult.value = { type: 'hotcold', text: `Warm. You share ${overlap} categories with the target.` }
        else if (overlap >= 1) hintResult.value = { type: 'hotcold', text: `Lukewarm. You share ${overlap} categor${overlap === 1 ? 'y' : 'ies'} with the target.` }
        else hintResult.value = { type: 'hotcold', text: 'Cold. No shared categories with the target.' }
      } catch {
        hintResult.value = { type: 'hotcold', text: 'Could not determine temperature.' }
      }
    }
  }
  setTimeout(() => hintResult.value = null, 8000)
}

function generateShareText(challengeUrl) {
  const mode = game.GAME_MODES[game.state.mode]?.name || game.state.mode
  const won = game.isWon.value
  const clicks = game.state.clicks
  const time = game.formattedTime.value
  const path = game.state.path
  const start = game.state.startArticle?.title || path[0]?.replace(/_/g, ' ') || '?'
  const target = game.state.targetArticle?.title || '?'

  const header = won
    ? `[+] WikiLink - ${mode} WIN`
    : `[x] WikiLink - ${mode}`

  const route = `${start} -> ${target}`

  const stats = [
    `${clicks} click${clicks !== 1 ? 's' : ''}`,
    time,
    `${path.length} page${path.length !== 1 ? 's' : ''}`,
  ].join('  |  ')

  const pathSteps = path.map((a, i) => {
    const name = a.replace(/_/g, ' ')
    if (i === 0) return `> ${name}`
    if (i === path.length - 1) return `${won ? '>> ' : 'x '}${name}`
    return `- ${name}`
  })
  const maxPathDisplay = 8
  let pathText
  if (pathSteps.length <= maxPathDisplay) {
    pathText = pathSteps.join(' -> ')
  } else {
    const head = pathSteps.slice(0, 3)
    const tail = pathSteps.slice(-2)
    pathText = [...head, `... ${pathSteps.length - 5} more ...`, ...tail].join(' -> ')
  }

  const link = challengeUrl || 'https://wikilink.fraksis.com'

  return [
    header,
    route,
    '',
    stats,
    '',
    pathText,
    '',
    `Can you beat me? ${link}`,
  ].join('\n')
}

async function copyShareCard() {
  const start = game.state.startArticle?.title
  const end = game.state.targetArticle?.title
  let challengeUrl = null

  if (start && end) {
    try {
      const data = await api.post('/challenge', { startTitle: start, endTitle: end })
      challengeUrl = `${window.location.origin}/play/custom?code=${data.code}`
    } catch {
      challengeUrl = `${window.location.origin}/play/custom?from=${encodeURIComponent(start)}&to=${encodeURIComponent(end)}`
    }
  }

  const text = generateShareText(challengeUrl)
  try {
    await navigator.clipboard.writeText(text)
    toast.success('Result copied to clipboard!')
  } catch {
    toast.warn('Could not copy to clipboard')
  }
}

async function shareChallenge() {
  const start = game.state.startArticle?.title
  const end = game.state.targetArticle?.title
  if (!start || !end) return
  try {
    const data = await api.post('/challenge', { startTitle: start, endTitle: end })
    const url = `${window.location.origin}/play/custom?code=${data.code}`
    await navigator.clipboard.writeText(url)
    toast.success('Challenge link copied!')
  } catch {
    const url = `${window.location.origin}/play/custom?from=${encodeURIComponent(start)}&to=${encodeURIComponent(end)}`
    await navigator.clipboard.writeText(url).catch(() => {})
    toast.success('Challenge link copied!')
  }
}

function finishFreeplay() {
  game.endFreeplay()
  freeplayFinished.value = true
}

const confettiColors = ['#39ff14', '#00e5ff', '#ffbf00', '#ff2ecc', '#4c9fff', '#ff6b2b']
function confettiStyle(i) {
  const color = confettiColors[i % confettiColors.length]
  const left = Math.random() * 100
  const delay = Math.random() * 0.5
  const size = 4 + Math.random() * 6
  const dur = 1.5 + Math.random() * 1.5
  return { '--c-color': color, left: `${left}%`, animationDelay: `${delay}s`, width: `${size}px`, height: `${size}px`, animationDuration: `${dur}s` }
}

function formatLeaderboardTime(seconds) {
  const m = Math.floor(seconds / 60)
  const s = seconds % 60
  return `${m}:${s.toString().padStart(2, '0')}`
}

async function submitMatchResult() {
  if (!matchCode.value || !auth.isLoggedIn() || resumedSoloAfterQuit.value) return
  try {
    const result = await api.post(`/match/submit/${matchCode.value}`, {
      clicks: game.state.clicks,
      time: game.state.elapsed,
      path: [...game.state.path],
    })
    matchResult.value = result
    if (result.status !== 'finished') {
      matchPolling.value = true
      matchPollDelayMs.value = RESULT_POLL.min
      pollMatchResult()
    }
  } catch { /* ignore */ }
}

async function pollMatchResult() {
  if (!matchCode.value || !matchPolling.value) return
  try {
    const prevStatus = matchResult.value?.status || null
    const prevOpponentSubmitted = !!matchResult.value?.opponent?.submitted
    const result = await api.get(`/match/${matchCode.value}`)
    if (isLeavingGame.value) return
    matchResult.value = result
    const hasFreshChange = prevStatus !== result.status
      || prevOpponentSubmitted !== !!result?.opponent?.submitted
    matchPollDelayMs.value = nextPollDelay(matchPollDelayMs.value, hasFreshChange, RESULT_POLL)
    if (result.status === 'finished') {
      matchPolling.value = false
      return
    }
  } catch {
    matchPollDelayMs.value = nextPollDelay(matchPollDelayMs.value, false, RESULT_POLL)
  }
  matchPollTimerId = setTimeout(pollMatchResult, matchPollDelayMs.value)
}

async function notifyMultiplayerLeave() {
  if (!auth.isLoggedIn() || !isMultiplayerGame.value) return
  try {
    await api.post('/multiplayer/leave-current')
  } catch {
    // Fallback: keepalive request with auth header for fast-navigation/browser-close cases.
    try {
      const token = localStorage.getItem('wikilink_token')
      const headers = {}
      if (token) headers.Authorization = `Bearer ${token}`
      await fetch('/api/multiplayer/leave-current', { method: 'POST', headers, keepalive: true })
    } catch {
      // ignore transient leave failures
    }
  }
}

async function pollMatchPresence() {
  if (!matchCode.value || !auth.isLoggedIn() || game.isGameOver.value || showOpponentQuitModal.value) return
  try {
    const result = await api.get(`/match/${matchCode.value}`)
    if (isLeavingGame.value) return
    matchResult.value = result
    if (result?.opponent?.quit) {
      const hasFreshChange = true
      presencePollDelayMs.value = nextPollDelay(presencePollDelayMs.value, hasFreshChange, PRESENCE_POLL)
      if (isOpponentQuitAcked(matchCode.value)) {
        matchPresencePollTimerId = setTimeout(pollMatchPresence, presencePollDelayMs.value)
        return
      }
      opponentQuitName.value = result.opponent?.username || 'Opponent'
      showOpponentQuitModal.value = true
      if (matchPresencePollTimerId) { clearTimeout(matchPresencePollTimerId); matchPresencePollTimerId = null }
      return
    }
    if (result?.status === 'finished') {
      applySuddenDeathRemoteFinish(result, 'match')
      return
    }
    const hasFreshChange = false
    presencePollDelayMs.value = nextPollDelay(presencePollDelayMs.value, hasFreshChange, PRESENCE_POLL)
  } catch {
    presencePollDelayMs.value = nextPollDelay(presencePollDelayMs.value, false, PRESENCE_POLL)
  }
  if (!showOpponentQuitModal.value) {
    matchPresencePollTimerId = setTimeout(pollMatchPresence, presencePollDelayMs.value)
  }
}

async function syncMultiplayerIdentityAndPolling() {
  const uid = auth.user.value?.id
  if (!uid || !isMultiplayerGame.value) return

  if (matchCode.value) {
    if (!matchResult.value?.opponent?.username) {
      try {
        matchResult.value = await api.get(`/match/${matchCode.value}`)
      } catch {
        // ignore transient fetch failures
      }
    }
    if (!game.isGameOver.value && !showOpponentQuitModal.value && !matchPresencePollTimerId) {
      presencePollDelayMs.value = PRESENCE_POLL.min
      pollMatchPresence()
    }
  }

  if (lobbyCode.value) {
    if (!lobbyResult.value?.players?.length) {
      try {
        lobbyResult.value = await api.get(`/lobby/${lobbyCode.value}`)
      } catch {
        // ignore transient fetch failures
      }
    }
    if (!lobbyPolling.value) {
      lobbyPolling.value = true
      lobbyPollDelayMs.value = LOBBY_POLL.min
      pollLobbyResult()
    }
  }
}

async function resumeSoloAfterOpponentQuit(opponentName = 'Opponent') {
  if (matchCode.value) markOpponentQuitAcked(matchCode.value)
  await notifyMultiplayerLeave()
  clearMultiplayerSession()
  resumedSoloAfterQuit.value = true
  showOpponentQuitModal.value = false
  opponentQuitName.value = ''
  matchPolling.value = false
  if (matchPollTimerId) { clearTimeout(matchPollTimerId); matchPollTimerId = null }
  if (matchPresencePollTimerId) { clearTimeout(matchPresencePollTimerId); matchPresencePollTimerId = null }
  const q = { ...route.query }
  delete q.match
  await router.replace({ name: 'game', params: { mode: props.mode }, query: q })
  toast.warn(`${opponentName} quit. Switched to solo mode.`)
}

function quitAfterOpponentLeft() {
  if (matchCode.value) markOpponentQuitAcked(matchCode.value)
  showOpponentQuitModal.value = false
  opponentQuitName.value = ''
  goHome()
}

async function submitLobbyResult() {
  if (!lobbyCode.value || !auth.isLoggedIn()) return
  try {
    const result = await api.post(`/lobby/submit/${lobbyCode.value}`, {
      clicks: game.state.clicks,
      time: game.state.elapsed,
      path: [...game.state.path],
    })
    lobbyResult.value = result
    if (result.status !== 'finished') {
      if (!lobbyPolling.value) {
        lobbyPolling.value = true
        lobbyPollDelayMs.value = LOBBY_POLL.min
        pollLobbyResult()
      }
    }
  } catch { /* ignore */ }
}

async function pollLobbyResult() {
  if (!lobbyCode.value || !lobbyPolling.value) return
  try {
    const prevStatus = lobbyResult.value?.status || null
    const prevPlayers = Array.isArray(lobbyResult.value?.players) ? lobbyResult.value.players : []
    const prevSubmitted = Array.isArray(lobbyResult.value?.players)
      ? lobbyResult.value.players.filter(p => p.submitted).length
      : 0
    const result = await api.get(`/lobby/${lobbyCode.value}`)
    if (isLeavingGame.value) return
    lobbyResult.value = result
    const nextPlayers = Array.isArray(result?.players) ? result.players : []
    const nextIds = new Set(nextPlayers.map(p => p.user_id))
    const uid = auth.user.value?.id
    const removed = prevPlayers.filter(p => p.user_id !== uid && !nextIds.has(p.user_id))
    if (removed.length > 0 && !showLobbyPlayerLeftModal.value) {
      const leftName = removed[0]?.username || 'A player'
      const activeRemaining = nextPlayers.length
      if (activeRemaining >= 2) {
        const extraCount = removed.length - 1
        const suffix = extraCount > 0 ? ` (+${extraCount} more)` : ''
        toast.warn(`${leftName} left the lobby${suffix}.`)
      } else {
        lobbyLeftPlayerNames.value = removed.map(p => p?.username || 'A player')
        showLobbyPlayerLeftModal.value = true
        if (lobbyPollTimerId) { clearTimeout(lobbyPollTimerId); lobbyPollTimerId = null }
      }
    }
    const nextSubmitted = Array.isArray(result?.players)
      ? result.players.filter(p => p.submitted).length
      : 0
    const hasFreshChange = prevStatus !== result.status || prevSubmitted !== nextSubmitted
    lobbyPollDelayMs.value = nextPollDelay(lobbyPollDelayMs.value, hasFreshChange, LOBBY_POLL)
    if (showLobbyPlayerLeftModal.value) return
    if (result.status === 'finished') {
      applySuddenDeathRemoteFinish(result, 'lobby')
      lobbyPolling.value = false
      return
    }
  } catch {
    lobbyPollDelayMs.value = nextPollDelay(lobbyPollDelayMs.value, false, LOBBY_POLL)
  }
  lobbyPollTimerId = setTimeout(pollLobbyResult, lobbyPollDelayMs.value)
}

async function refreshMatchNow() {
  if (!matchCode.value || !auth.isLoggedIn()) return
  try {
    const result = await api.get(`/match/${matchCode.value}`)
    if (isLeavingGame.value) return
    matchResult.value = result
    if (result?.status === 'finished') {
      applySuddenDeathRemoteFinish(result, 'match')
      matchPolling.value = false
    }
  } catch {
    // ignore transient refresh failures
  }
}

async function refreshLobbyNow() {
  if (!lobbyCode.value || !auth.isLoggedIn()) return
  try {
    const result = await api.get(`/lobby/${lobbyCode.value}`)
    if (isLeavingGame.value) return
    lobbyResult.value = result
    if (result?.status === 'finished') {
      applySuddenDeathRemoteFinish(result, 'lobby')
      lobbyPolling.value = false
    }
  } catch {
    // ignore transient refresh failures
  }
}

function triggerImmediateRefresh() {
  if (!isMultiplayerGame.value || !auth.isLoggedIn()) return

  const now = Date.now()
  const elapsed = now - lastImmediateRefreshAt

  const runRefresh = () => {
    lastImmediateRefreshAt = Date.now()
    if (matchCode.value) {
      presencePollDelayMs.value = PRESENCE_POLL.min
      refreshMatchNow()
      return
    }
    if (lobbyCode.value) {
      lobbyPollDelayMs.value = LOBBY_POLL.min
      refreshLobbyNow()
    }
  }

  if (elapsed >= IMMEDIATE_REFRESH_THROTTLE_MS) {
    if (immediateRefreshTimerId) { clearTimeout(immediateRefreshTimerId); immediateRefreshTimerId = null }
    runRefresh()
    return
  }

  if (immediateRefreshTimerId) return
  immediateRefreshTimerId = setTimeout(() => {
    immediateRefreshTimerId = null
    runRefresh()
  }, IMMEDIATE_REFRESH_THROTTLE_MS - elapsed)
}

async function resumeSoloAfterLobbyPlayerLeft(playerName = 'Player') {
  await notifyMultiplayerLeave()
  clearMultiplayerSession()
  showLobbyPlayerLeftModal.value = false
  lobbyLeftPlayerNames.value = []
  lobbyPolling.value = false
  if (lobbyPollTimerId) { clearTimeout(lobbyPollTimerId); lobbyPollTimerId = null }
  const q = { ...route.query }
  delete q.lobby
  await router.replace({ name: 'game', params: { mode: props.mode }, query: q })
  toast.warn(`${playerName} left. Switched to solo mode.`)
}

function quitAfterLobbyPlayerLeft() {
  showLobbyPlayerLeftModal.value = false
  lobbyLeftPlayerNames.value = []
  goHome()
}

async function submitDailyScore() {
  if (props.mode !== 'daily' || !auth.isLoggedIn()) return
  try {
    await api.post('/daily/score', {
      date: new Date().toISOString().slice(0, 10),
      clicks: game.state.clicks,
      time: game.state.elapsed,
      path: [...game.state.path],
    })
    auth.refreshStreak()
  } catch { /* ignore */ }
}

async function loadDailyLeaderboard() {
  if (props.mode !== 'daily') return
  try {
    const data = await api.get('/daily/leaderboard')
    dailyLeaderboard.value = data.scores || []
  } catch { /* ignore */ }
}

async function rerollPair() {
  if (!canReroll.value || rerolling.value || !game.isPlaying.value) return
  rerolling.value = true
  try {
    let pair = null
    if (props.mode === 'trending') {
      pair = await trending.getTrendingPair()
      if (!pair) {
        toast.warn('Trending data unavailable, falling back to random')
        pair = await wiki.getRandomPairByGenre(genre.value)
      }
    } else {
      pair = await wiki.getRandomPairByGenre(genre.value)
    }
    if (!pair) { toast.error('Could not find a new pair'); return }
    game.initGame(props.mode, pair.start, pair.end, genreId.value, difficulty.value, customLimitsFromRoute.value, activeModifiers.value)
    previewCache = {}
    sound.playStart()
    await loadArticle(pair.start.title)
  } finally {
    rerolling.value = false
  }
}

function confirmQuit() {
  if (game.isPlaying.value) {
    showQuitConfirm.value = true
  } else {
    goHome()
  }
}

async function goHome() {
  if (isLeavingGame.value) return
  isLeavingGame.value = true

  persistDailySession()

  const activeMatchCode = matchCode.value
  const activeLobbyCode = lobbyCode.value
  const canNotifyServer = auth.isLoggedIn()
  const shouldLeaveCurrent = isMultiplayerGame.value

  if (canNotifyServer && shouldLeaveCurrent) {
    // Ensure backend receives at least one leave signal before route transition.
    // Keep it snappy with a short timeout so Home remains responsive.
    const leaveCall = notifyMultiplayerLeave().catch(() => {})
    const leaveTimeout = new Promise(resolve => setTimeout(resolve, 1200))
    await Promise.race([leaveCall, leaveTimeout])
  }
  if (canNotifyServer && activeMatchCode) {
    api.post(`/match/quit/${activeMatchCode}`).catch(() => {})
  }
  if (canNotifyServer && activeLobbyCode) {
    api.post(`/lobby/quit/${activeLobbyCode}`).catch(() => {})
  }

  matchPolling.value = false
  if (matchPollTimerId) { clearTimeout(matchPollTimerId); matchPollTimerId = null }
  if (matchPresencePollTimerId) { clearTimeout(matchPresencePollTimerId); matchPresencePollTimerId = null }
  lobbyPolling.value = false
  if (lobbyPollTimerId) { clearTimeout(lobbyPollTimerId); lobbyPollTimerId = null }
  if (multiplayerHeartbeatTimerId) { clearInterval(multiplayerHeartbeatTimerId); multiplayerHeartbeatTimerId = null }
  if (activityHeartbeatTimerId) { clearTimeout(activityHeartbeatTimerId); activityHeartbeatTimerId = null }
  if (immediateRefreshTimerId) { clearTimeout(immediateRefreshTimerId); immediateRefreshTimerId = null }
  showLobbyPlayerLeftModal.value = false
  lobbyLeftPlayerNames.value = []
  clearMultiplayerSession()
  game.resetGame()
  await router.push({ name: 'home' })
}

async function playAgain() {
  if (matchCode.value && auth.isLoggedIn()) {
    const replay = await api.post(`/match/replay/${matchCode.value}`).catch((e) => ({ error: e?.message || 'Replay unavailable' }))
    if (replay?.error) {
      toast.warn(replay.error)
      return
    }
    clearMultiplayerSession()
    await router.push({
      name: 'home',
      query: { replayType: 'match', replayCode: String(matchCode.value) },
    })
    return
  }
  if (lobbyCode.value && auth.isLoggedIn()) {
    const replay = await api.post(`/lobby/replay/${lobbyCode.value}`).catch((e) => ({ error: e?.message || 'Replay unavailable' }))
    if (replay?.error) {
      toast.warn(replay.error)
      return
    }
    clearMultiplayerSession()
    await router.push({
      name: 'home',
      query: { replayType: 'lobby', replayCode: String(lobbyCode.value) },
    })
    return
  }
  clearMultiplayerSession()
  resumedSoloAfterQuit.value = false
  showOpponentQuitModal.value = false
  opponentQuitName.value = ''
  showLobbyPlayerLeftModal.value = false
  lobbyLeftPlayerNames.value = []
  game.resetGame()
  freeplayFinished.value = false
  articleData.value = null
  matchResult.value = null
  matchPolling.value = false
  if (matchPollTimerId) { clearTimeout(matchPollTimerId); matchPollTimerId = null }
  if (matchPresencePollTimerId) { clearTimeout(matchPresencePollTimerId); matchPresencePollTimerId = null }
  lobbyResult.value = null
  lobbyPolling.value = false
  if (lobbyPollTimerId) { clearTimeout(lobbyPollTimerId); lobbyPollTimerId = null }
  const q = { ...route.query }
  if ('match' in q || 'lobby' in q) {
    delete q.match
    delete q.lobby
    await router.replace({ name: 'game', params: { mode: props.mode }, query: q })
  }
  await initializeGame()
}

function handleKeydown(e) {
  if (e.key === 'Escape') {
    if (showQuitConfirm.value) { showQuitConfirm.value = false; return }
    if (showEndModal.value) { goHome(); return }
    confirmQuit()
  }
  if (e.key === 'Backspace' && !showEndModal.value && !showQuitConfirm.value) {
    e.preventDefault()
    handleGoBack()
  }
  if (e.key === 'Enter' && showEndModal.value) playAgain()
  if ((e.key === 'r' || e.key === 'R') && !showEndModal.value && !showQuitConfirm.value) rerollPair()
}

onMounted(() => {
  if (!game.GAME_MODES[props.mode]) {
    toast.warn('Invalid game mode')
    router.push({ name: 'home' })
    return
  }
  window.addEventListener('keydown', handleKeydown)
  window.addEventListener('scroll', onMeaningfulActivity, { passive: true })
  window.addEventListener('mousemove', onMeaningfulActivity, { passive: true })
  window.addEventListener('touchstart', onMeaningfulActivity, { passive: true })
  window.addEventListener('click', onMeaningfulActivity, { passive: true })
  window.addEventListener('keydown', onMeaningfulActivity)
  window.addEventListener('focus', onVisibilityOrFocus)
  document.addEventListener('visibilitychange', onVisibilityOrFocus)
  syncMultiplayerIdentityAndPolling()
  if (!multiplayerPersistTimerId) {
    multiplayerPersistTimerId = setInterval(() => {
      persistMultiplayerSession()
    }, 5000)
  }
  if (props.mode === 'daily' && !dailyPersistTimerId) {
    dailyPersistTimerId = setInterval(() => {
      persistDailySession()
    }, 5000)
  }
  if (isMultiplayerGame.value && auth.isLoggedIn() && !multiplayerHeartbeatTimerId) {
    sendMultiplayerHeartbeat()
    lastActivityHeartbeatAt = Date.now()
    multiplayerHeartbeatTimerId = setInterval(sendMultiplayerHeartbeat, 15000)
  }
  initializeGame()
})

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeydown)
  window.removeEventListener('scroll', onMeaningfulActivity)
  window.removeEventListener('mousemove', onMeaningfulActivity)
  window.removeEventListener('touchstart', onMeaningfulActivity)
  window.removeEventListener('click', onMeaningfulActivity)
  window.removeEventListener('keydown', onMeaningfulActivity)
  window.removeEventListener('focus', onVisibilityOrFocus)
  document.removeEventListener('visibilitychange', onVisibilityOrFocus)
  clearTimeout(hoverTimer)
  matchPolling.value = false
  if (matchPollTimerId) { clearTimeout(matchPollTimerId); matchPollTimerId = null }
  if (matchPresencePollTimerId) { clearTimeout(matchPresencePollTimerId); matchPresencePollTimerId = null }
  lobbyPolling.value = false
  if (lobbyPollTimerId) { clearTimeout(lobbyPollTimerId); lobbyPollTimerId = null }
  if (multiplayerPersistTimerId) { clearInterval(multiplayerPersistTimerId); multiplayerPersistTimerId = null }
  if (dailyPersistTimerId) { clearInterval(dailyPersistTimerId); dailyPersistTimerId = null }
  if (multiplayerHeartbeatTimerId) { clearInterval(multiplayerHeartbeatTimerId); multiplayerHeartbeatTimerId = null }
  if (activityHeartbeatTimerId) { clearTimeout(activityHeartbeatTimerId); activityHeartbeatTimerId = null }
  if (immediateRefreshTimerId) { clearTimeout(immediateRefreshTimerId); immediateRefreshTimerId = null }
  persistMultiplayerSession()
  persistDailySession()
  game.resetGame()
})

watch(
  () => [
    game.state.currentArticle,
    game.state.status,
    game.state.clicks,
    game.state.path.length,
    game.state.hints,
    game.state.hintsUsed,
  ],
  () => {
    persistMultiplayerSession()
  }
)

watch(() => auth.user.value?.id, () => {
  syncMultiplayerIdentityAndPolling()
})
</script>
