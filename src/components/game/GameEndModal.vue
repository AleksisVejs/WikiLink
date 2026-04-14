<template>
  <Teleport to="body">
    <transition name="fade">
      <div v-if="showEndModal" class="fixed inset-0 z-[60] flex items-center justify-center p-3 sm:p-4">
        <div class="absolute inset-0 bg-black/85 backdrop-blur-sm"></div>
        <div class="relative rounded-2xl max-w-[420px] w-full max-h-[90dvh] overflow-y-auto overscroll-contain animate-scale-in" :style="endModalStyle">
          <div class="px-5 pt-5 pb-3 text-center">
            <template v-if="isWon && !freeplayFinished">
              <div class="font-pixel text-[8px] text-crt-green/60 mb-1 tracking-[0.3em]">MISSION</div>
              <h2 class="font-pixel text-lg sm:text-xl text-neon-green mb-2.5">COMPLETE!</h2>
            </template>
            <template v-else-if="freeplayFinished">
              <div class="font-pixel text-[8px] text-crt-cyan/60 mb-1 tracking-[0.3em]">SESSION</div>
              <h2 class="font-pixel text-lg sm:text-xl text-neon-cyan mb-2.5">SUMMARY</h2>
            </template>
            <template v-else>
              <div class="font-pixel text-[8px] text-crt-red/60 mb-1 tracking-[0.3em]">GAME</div>
              <h2 class="font-pixel text-lg sm:text-xl mb-2.5" style="color: #ff4444; text-shadow: 0 0 15px rgba(255,68,68,0.5), 0 0 40px rgba(255,68,68,0.2);">OVER</h2>
            </template>

            <div v-if="!freeplayFinished" class="flex items-center justify-center gap-2 font-mono text-[11px] mb-1">
              <span class="text-crt-white truncate max-w-[140px] wiki-point">
                {{ startTitle }}
                <span class="wiki-point-full">{{ startTitle }}</span>
              </span>
              <svg class="w-3.5 h-3.5 shrink-0" :class="isWon ? 'text-crt-green' : 'text-crt-red'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
              </svg>
              <span class="truncate max-w-[140px] wiki-point" :class="isWon ? 'text-crt-green' : 'text-crt-magenta'">
                {{ targetTitle }}
                <span class="wiki-point-full">{{ targetTitle }}</span>
              </span>
            </div>
            <p v-if="!isWon && !freeplayFinished" class="font-mono text-[10px] text-retro-muted/60">
              {{ effectiveTimeLimit ? 'Time ran out!' : 'No more clicks!' }}
            </p>
            <p v-if="freeplayFinished" class="font-mono text-[11px] text-retro-muted">
              You explored <span class="text-crt-green">{{ pathLength }}</span> articles freely.
            </p>
          </div>

          <div class="px-5 pb-3">
            <div class="grid grid-cols-3 gap-2">
              <div class="rounded-lg py-2.5 px-1 text-center" style="background: #12131c; border: 1px solid rgba(57,255,20,0.1);">
                <svg class="w-4 h-4 text-crt-green/40 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122" />
                </svg>
                <div class="font-terminal text-xl text-crt-green leading-none">{{ clicks }}</div>
                <div class="font-mono text-[8px] text-retro-muted mt-1 tracking-wider">CLICKS</div>
              </div>
              <div class="rounded-lg py-2.5 px-1 text-center" style="background: #12131c; border: 1px solid rgba(255,191,0,0.1);">
                <svg class="w-4 h-4 text-crt-amber/40 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="font-terminal text-xl text-crt-amber leading-none">{{ formattedTime }}</div>
                <div class="font-mono text-[8px] text-retro-muted mt-1 tracking-wider">TIME</div>
              </div>
              <div class="rounded-lg py-2.5 px-1 text-center" style="background: #12131c; border: 1px solid rgba(255,46,204,0.1);">
                <svg class="w-4 h-4 text-crt-magenta/40 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <div class="font-terminal text-xl text-crt-magenta leading-none">{{ pathLength }}</div>
                <div class="font-mono text-[8px] text-retro-muted mt-1 tracking-wider">PAGES</div>
              </div>
            </div>
          </div>

          <div class="px-5 pb-3 space-y-2">
            <div v-if="xpRewardData && xpRewardData.totalReward > 0" class="flex items-center justify-between rounded-lg px-3 py-2.5" style="background: rgba(57,255,20,0.03); border: 1px solid rgba(57,255,20,0.1);">
              <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-crt-green/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
                <span class="font-pixel text-[7px] text-crt-green/70 tracking-wider">XP EARNED</span>
              </div>
              <span class="font-terminal text-base text-crt-green">+{{ xpRewardData.totalReward }}</span>
            </div>

            <div class="flex flex-wrap items-center gap-1.5">
              <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md font-mono text-[10px]" style="background: #12131c; border: 1px solid #252738;">
                <svg class="w-3 h-3 text-crt-amber/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                </svg>
                <span class="text-retro-muted">Hints</span>
                <span :class="hintsUsed > 0 ? 'text-crt-amber' : 'text-crt-green'">{{ hintsUsed }}</span>
              </span>
              <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md font-mono text-[10px]" style="background: #12131c; border: 1px solid #252738;">
                <svg class="w-3 h-3 text-crt-cyan/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
                <span class="text-retro-muted">Combo</span>
                <span class="text-crt-cyan">{{ combo }}x</span>
              </span>
              <span v-for="modId in modifiers" :key="modId" class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md font-mono text-[10px]" style="background: rgba(255,191,0,0.04); border: 1px solid rgba(255,191,0,0.15);">
                <svg class="w-3 h-3 text-crt-amber" fill="none" stroke="currentColor" viewBox="0 0 24 24" v-html="modifierSvgPath(modId)"></svg>
                <span class="text-crt-amber">{{ gameModifiers[modId]?.name }}</span>
              </span>
            </div>

            <div class="rounded-lg px-3 py-2" style="background: #12131c; border: 1px solid #252738;">
              <div class="flex items-center gap-1 overflow-x-auto pb-0.5 font-mono text-[10px]">
                <template v-for="(article, idx) in path" :key="idx">
                  <span v-if="idx > 0" class="text-retro-border/40 shrink-0 text-[8px]">&rsaquo;</span>
                  <span class="shrink-0 whitespace-nowrap px-1 py-0.5 rounded" :class="idx === 0 ? 'text-crt-white' : idx === path.length - 1 ? (isWon ? 'text-crt-green' : 'text-crt-red') : 'text-retro-muted'" :style="idx === path.length - 1 && isWon ? 'background: rgba(57,255,20,0.06);' : ''">
                    {{ article.replace(/_/g, ' ') }}
                  </span>
                </template>
              </div>
            </div>

            <div v-if="dailyMode && dailyLeaderboard.length" class="rounded-lg px-3 py-2" style="background: #12131c; border: 1px solid #252738;">
              <div class="font-pixel text-[6px] text-crt-amber tracking-[0.15em] mb-1.5">DAILY LEADERBOARD</div>
              <div class="space-y-0.5 font-mono text-[11px]">
                <div v-for="entry in dailyLeaderboard.slice(0, 3)" :key="entry.rank" class="flex items-center gap-2">
                  <span class="text-retro-muted w-3 text-right shrink-0">#{{ entry.rank }}</span>
                  <span class="text-crt-white flex-1 truncate">{{ entry.username }}</span>
                  <span class="text-crt-green shrink-0">{{ entry.clicks }}</span>
                  <span class="text-retro-muted shrink-0">{{ formatLeaderboardTime(entry.time) }}</span>
                </div>
              </div>
            </div>

            <div v-if="matchResult" class="rounded-lg px-3 py-2" style="background: #12131c; border: 1px solid rgba(180,76,255,0.2);">
              <div v-if="matchResult.status === 'finished'">
                <div class="text-center mb-1.5">
                  <span class="font-pixel text-[8px]" :class="matchResult.winner === 'you' ? 'text-crt-green' : matchResult.winner === 'tie' ? 'text-crt-amber' : 'text-crt-red'">
                    {{ matchResult.winner === 'you' ? 'YOU WIN!' : matchResult.winner === 'tie' ? 'TIE!' : 'OPPONENT WINS' }}
                  </span>
                </div>
                <div class="grid grid-cols-2 gap-2 text-center">
                  <div class="rounded px-2 py-1.5" style="background: rgba(57,255,20,0.03); border: 1px solid rgba(57,255,20,0.1);">
                    <div class="font-pixel text-[6px] text-crt-green/60">YOU</div>
                    <div v-if="matchResult.you?.username" class="font-mono text-[9px] text-retro-muted truncate mb-0.5">{{ matchResult.you.username }}</div>
                    <div class="font-terminal text-base text-crt-white">{{ matchResult.you?.clicks }} <span class="text-retro-muted text-xs">/ {{ formatLeaderboardTime(matchResult.you?.time || 0) }}</span></div>
                  </div>
                  <div class="rounded px-2 py-1.5" style="background: rgba(180,76,255,0.03); border: 1px solid rgba(180,76,255,0.1);">
                    <div class="font-pixel text-[6px] text-arcade-purple/60">OPP</div>
                    <div v-if="matchResult.opponent?.username" class="font-mono text-[9px] text-arcade-purple/70 truncate mb-0.5">{{ matchResult.opponent.username }}</div>
                    <div class="font-terminal text-base text-crt-white">{{ matchResult.opponent?.clicks }} <span class="text-retro-muted text-xs">/ {{ formatLeaderboardTime(matchResult.opponent?.time || 0) }}</span></div>
                  </div>
                </div>
              </div>
              <div v-else class="text-center py-1">
                <span class="font-mono text-[11px] text-retro-muted animate-blink">Waiting for {{ matchResult.opponent?.username || 'opponent' }}...</span>
                <span class="font-terminal text-sm text-arcade-purple ml-2">{{ matchResult.code }}</span>
              </div>
            </div>

            <div v-if="lobbyResult" class="rounded-lg px-3 py-2" style="background: #12131c; border: 1px solid rgba(0,229,255,0.2);">
              <template v-if="lobbyResult.status === 'finished' && lobbyResult.leaderboard?.length">
                <div class="flex items-center justify-between gap-2 mb-1.5">
                  <div class="font-pixel text-[6px] text-crt-cyan tracking-[0.15em]">GROUP LEADERBOARD</div>
                  <span v-if="lobbyYourRank != null" class="font-mono text-[9px] text-crt-green">You: #{{ lobbyYourRank }}</span>
                </div>
                <div v-if="lobbyResult.leaderboard.every(row => !row.reached_target)" class="mb-2 rounded px-2 py-1 font-mono text-[10px] text-crt-amber text-center" style="background: rgba(255,191,0,0.06); border: 1px solid rgba(255,191,0,0.2);">
                  Nobody reached the target. Result is a tie.
                </div>
                <div class="mb-1 px-1 py-1 rounded font-mono text-[9px] text-retro-muted/70" style="background: rgba(37,39,56,0.35); border: 1px solid rgba(37,39,56,0.55);">
                  <div class="flex items-center gap-2">
                    <span class="w-7 text-right shrink-0">Rank</span>
                    <span class="flex-1">Player</span>
                    <span class="shrink-0 tabular-nums">Clicks</span>
                    <span class="shrink-0 tabular-nums w-12 text-right">Time</span>
                  </div>
                </div>
                <div class="space-y-0.5 font-mono text-[11px] max-h-[220px] overflow-y-auto">
                  <div v-for="row in lobbyResult.leaderboard" :key="row.user_id" class="flex items-center gap-2 py-1 rounded px-1" :class="{ 'bg-crt-green/[0.04] border-l-2 border-l-crt-green': currentUserId != null && row.user_id === currentUserId }">
                    <span class="text-retro-muted w-7 text-right shrink-0 flex items-center justify-end">
                      <template v-if="!row.reached_target">
                        <span class="font-pixel text-[7px] text-crt-red/85">DNF</span>
                      </template>
                      <template v-else-if="row.rank === 1">
                        <svg class="w-4 h-4 text-arcade-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                        </svg>
                      </template>
                      <template v-else-if="row.rank === 2">
                        <svg class="w-4 h-4 text-retro-light" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                        </svg>
                      </template>
                      <template v-else-if="row.rank === 3">
                        <svg class="w-4 h-4 text-arcade-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                        </svg>
                      </template>
                      <span v-else class="font-terminal text-sm text-retro-muted">#{{ row.rank }}</span>
                    </span>
                    <span class="flex-1 truncate" :class="currentUserId != null && row.user_id === currentUserId ? 'text-crt-green' : 'text-crt-white'">{{ row.username }}</span>
                    <span class="text-crt-green shrink-0 tabular-nums">{{ row.clicks }}</span>
                    <span class="text-retro-muted shrink-0 tabular-nums w-12 text-right">{{ formatLeaderboardTime(row.time) }}</span>
                  </div>
                </div>
              </template>
              <div v-else class="text-center py-1">
                <span class="font-mono text-[11px] text-retro-muted animate-blink">Waiting for other players...</span>
                <span class="font-terminal text-sm text-crt-cyan ml-2">{{ lobbyResult.code }}</span>
                <div v-if="lobbyDisconnectedCount > 0" class="mt-1 font-mono text-[10px] text-crt-amber/80">
                  {{ lobbyDisconnectedCount }} player{{ lobbyDisconnectedCount === 1 ? '' : 's' }} reconnecting...
                </div>
              </div>
            </div>
          </div>

          <div class="px-5 pb-5 pt-2">
            <div v-if="!freeplayFinished && targetTitle" class="flex gap-2 mb-2.5">
              <button @click="$emit('copy-share')" class="btn-retro-ghost flex-1 flex items-center justify-center gap-1.5 !py-1.5 !text-[9px] !px-2">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                </svg>
                SHARE
              </button>
              <button @click="$emit('share-challenge')" class="btn-retro-ghost flex-1 flex items-center justify-center gap-1.5 !py-1.5 !text-[9px] !px-2">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                </svg>
                CHALLENGE
              </button>
            </div>
            <div class="flex gap-2.5 pb-[env(safe-area-inset-bottom)]">
              <button @click="$emit('go-home')" class="btn-secondary flex-1 !py-3 sm:!py-2.5 !text-sm touch-manipulation">HOME</button>
              <button @click="$emit('play-again')" class="btn-primary flex-1 !py-3 sm:!py-2.5 !text-sm touch-manipulation">PLAY AGAIN</button>
            </div>
          </div>
        </div>

        <div v-if="isWon && !freeplayFinished && showConfetti" class="confetti-container" aria-hidden="true">
          <div v-for="i in 40" :key="i" class="confetti-piece" :style="confettiStyle(i)"></div>
        </div>
      </div>
    </transition>
  </Teleport>
</template>

<script setup>
defineProps({
  showEndModal: { type: Boolean, required: true },
  endModalStyle: { type: String, required: true },
  isWon: { type: Boolean, required: true },
  freeplayFinished: { type: Boolean, required: true },
  startTitle: { type: String, default: '' },
  targetTitle: { type: String, default: '' },
  effectiveTimeLimit: { type: Number, default: null },
  pathLength: { type: Number, required: true },
  clicks: { type: Number, required: true },
  formattedTime: { type: String, required: true },
  xpRewardData: { type: Object, default: null },
  hintsUsed: { type: Number, required: true },
  combo: { type: Number, required: true },
  modifiers: { type: Array, required: true },
  gameModifiers: { type: Object, required: true },
  modifierSvgPath: { type: Function, required: true },
  path: { type: Array, required: true },
  dailyMode: { type: Boolean, required: true },
  dailyLeaderboard: { type: Array, required: true },
  formatLeaderboardTime: { type: Function, required: true },
  matchResult: { type: Object, default: null },
  lobbyResult: { type: Object, default: null },
  lobbyYourRank: { type: Number, default: null },
  lobbyDisconnectedCount: { type: Number, required: true },
  currentUserId: { type: Number, default: null },
  showConfetti: { type: Boolean, required: true },
  confettiStyle: { type: Function, required: true },
})

defineEmits(['copy-share', 'share-challenge', 'go-home', 'play-again'])
</script>
