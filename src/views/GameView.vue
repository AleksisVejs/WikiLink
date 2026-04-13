<template>
  <div class="min-h-screen flex flex-col">

    <!-- Navbar -->
    <nav class="sticky top-0 z-[40] border-b border-retro-border/50"
         style="background: linear-gradient(180deg, #111118ee, #0d0e15ee); backdrop-filter: blur(12px);">
      <div class="max-w-6xl mx-auto px-4">
        <div class="flex items-center justify-between gap-4 h-12">

          <!-- Left: exit + back -->
          <div class="flex items-center gap-2.5 min-w-0">
            <button @click="confirmQuit"
                    class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg transition-all duration-200 group"
                    title="Exit game (Esc)"
                    style="border: 1px solid rgba(255,68,68,0.15);"
                    @mouseenter="$event.currentTarget.style.background='rgba(255,68,68,0.08)'; $event.currentTarget.style.borderColor='rgba(255,68,68,0.35)'"
                    @mouseleave="$event.currentTarget.style.background='transparent'; $event.currentTarget.style.borderColor='rgba(255,68,68,0.15)'">
              <svg class="w-3.5 h-3.5 text-crt-red/50 group-hover:text-crt-red transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
              <span class="font-mono text-[10px] text-crt-red/40 group-hover:text-crt-red transition-colors hidden sm:inline">EXIT</span>
            </button>

            <button @click="handleGoBack"
                    :disabled="game.state.path.length <= 1 || !game.isPlaying.value"
                    class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg transition-all duration-200 group disabled:opacity-25 disabled:cursor-not-allowed"
                    title="Go back (Backspace)"
                    style="border: 1px solid rgba(0,229,255,0.15);"
                    @mouseenter="$event.currentTarget.style.background='rgba(0,229,255,0.06)'; $event.currentTarget.style.borderColor='rgba(0,229,255,0.35)'"
                    @mouseleave="$event.currentTarget.style.background='transparent'; $event.currentTarget.style.borderColor='rgba(0,229,255,0.15)'">
              <svg class="w-3.5 h-3.5 text-crt-cyan/50 group-hover:text-crt-cyan transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
              </svg>
              <span class="font-mono text-[10px] text-crt-cyan/40 group-hover:text-crt-cyan transition-colors hidden sm:inline">BACK</span>
            </button>

            <!-- Reroll pair -->
            <button v-if="canReroll && game.isPlaying.value"
                    @click="rerollPair"
                    :disabled="rerolling"
                    class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg transition-all duration-200 group disabled:opacity-40 disabled:cursor-wait"
                    title="New pair (R)"
                    style="border: 1px solid rgba(255,191,0,0.15);"
                    @mouseenter="$event.currentTarget.style.background='rgba(255,191,0,0.08)'; $event.currentTarget.style.borderColor='rgba(255,191,0,0.35)'"
                    @mouseleave="$event.currentTarget.style.background='transparent'; $event.currentTarget.style.borderColor='rgba(255,191,0,0.15)'">
              <svg class="w-3.5 h-3.5 text-crt-amber/50 group-hover:text-crt-amber transition-colors" :class="{ 'animate-spin': rerolling }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
              <span class="font-mono text-[10px] text-crt-amber/40 group-hover:text-crt-amber transition-colors hidden sm:inline">REROLL</span>
            </button>

            <!-- Freeplay finish button -->
            <button v-if="currentMode?.noTarget && game.isPlaying.value"
                    @click="finishFreeplay"
                    class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg transition-all duration-200 group"
                    title="Finish session"
                    style="border: 1px solid rgba(57,255,20,0.15);"
                    @mouseenter="$event.currentTarget.style.background='rgba(57,255,20,0.06)'; $event.currentTarget.style.borderColor='rgba(57,255,20,0.35)'"
                    @mouseleave="$event.currentTarget.style.background='transparent'; $event.currentTarget.style.borderColor='rgba(57,255,20,0.15)'">
              <svg class="w-3.5 h-3.5 text-crt-green/50 group-hover:text-crt-green transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
              <span class="font-mono text-[10px] text-crt-green/40 group-hover:text-crt-green transition-colors hidden sm:inline">FINISH</span>
            </button>

            <div class="w-px h-5 bg-retro-border/40 hidden sm:block"></div>

            <!-- Start -> Target (desktop) -->
            <div v-if="!currentMode?.noTarget && !initialLoading" class="hidden md:flex items-center gap-2 min-w-0 overflow-hidden">
              <div class="flex items-center gap-1.5 min-w-0">
                <span class="w-1.5 h-1.5 rounded-full bg-crt-green shrink-0" style="box-shadow: 0 0 4px rgba(57,255,20,0.4);"></span>
                <span class="truncate font-mono text-[11px] text-crt-green">
                  {{ game.state.startArticle?.title }}
                </span>
              </div>
              <svg class="w-3 h-3 text-retro-muted shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
              <div class="flex items-center gap-1.5 min-w-0">
                <span class="w-1.5 h-1.5 rounded-full shrink-0"
                      :class="game.isWon.value ? 'bg-crt-green' : 'animate-blink'"
                      :style="game.isWon.value ? 'box-shadow: 0 0 4px rgba(57,255,20,0.4)' : 'background: #ff2ecc; box-shadow: 0 0 4px rgba(255,46,204,0.4);'"></span>
                <span class="truncate font-mono text-[11px]"
                      :class="game.isWon.value ? 'text-crt-green' : 'text-crt-magenta'">
                  {{ game.state.targetArticle?.title }}
                </span>
              </div>
            </div>
          </div>

          <!-- Right: stats + mute -->
          <div class="flex items-center gap-3 sm:gap-5 shrink-0">
            <div class="flex items-center gap-1.5">
              <span class="font-mono text-[10px] text-retro-muted uppercase tracking-wider hidden sm:inline">Clicks</span>
              <span class="font-terminal text-lg font-bold" :class="clickLimitClass">
                {{ game.state.clicks }}
                <span v-if="game.state.effectiveClickLimit" class="text-retro-muted text-sm">/{{ game.state.effectiveClickLimit }}</span>
              </span>
            </div>
            <div class="w-px h-5 bg-retro-border/40"></div>
            <div class="flex items-center gap-1.5">
              <span class="font-mono text-[10px] text-retro-muted uppercase tracking-wider hidden sm:inline">
                {{ game.formattedRemaining.value !== null ? 'Left' : 'Time' }}
              </span>
              <span v-if="game.formattedRemaining.value !== null"
                    class="font-terminal text-lg font-bold" :class="timerClass">
                {{ game.formattedRemaining.value }}
              </span>
              <span v-else class="font-terminal text-lg font-bold text-crt-white">{{ game.formattedTime.value }}</span>
            </div>
            <button @click="sound.toggleMute()" class="p-1.5 rounded-lg transition-all hover:bg-retro-surface/50" :title="sound.muted.value ? 'Unmute' : 'Mute'">
              <svg v-if="!sound.muted.value" class="w-3.5 h-3.5 text-retro-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072M12 6l-4 4H4v4h4l4 4V6z" />
              </svg>
              <svg v-else class="w-3.5 h-3.5 text-retro-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707A1 1 0 0112 5v14a1 1 0 01-1.707.707L5.586 15zM17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2" />
              </svg>
            </button>
            <div class="flex items-center gap-1.5">
              <span class="w-2 h-2 rounded-full"
                    :style="game.isPlaying.value
                      ? 'background: #39ff14; box-shadow: 0 0 6px rgba(57,255,20,0.5);'
                      : 'background: #555770;'"></span>
            </div>
          </div>
        </div>

        <!-- Start -> Target (mobile) -->
        <div v-if="!currentMode?.noTarget && !initialLoading" class="flex md:hidden items-center justify-center gap-2 pb-2 -mt-0.5 px-2 overflow-hidden">
          <div class="flex items-center gap-1.5 min-w-0">
            <span class="w-1.5 h-1.5 rounded-full bg-crt-green shrink-0" style="box-shadow: 0 0 4px rgba(57,255,20,0.4);"></span>
            <span class="truncate font-mono text-[10px] text-crt-green">{{ game.state.startArticle?.title }}</span>
          </div>
          <svg class="w-3 h-3 text-retro-muted shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
          </svg>
          <div class="flex items-center gap-1.5 min-w-0">
            <span class="w-1.5 h-1.5 rounded-full shrink-0"
                  :class="game.isWon.value ? 'bg-crt-green' : 'animate-blink'"
                  :style="game.isWon.value ? 'box-shadow: 0 0 4px rgba(57,255,20,0.4)' : 'background: #ff2ecc; box-shadow: 0 0 4px rgba(255,46,204,0.4);'"></span>
            <span class="truncate font-mono text-[10px]"
                  :class="game.isWon.value ? 'text-crt-green' : 'text-crt-magenta'">{{ game.state.targetArticle?.title }}</span>
          </div>
        </div>

        <!-- Breadcrumb -->
        <div v-if="game.state.path.length > 1 && !initialLoading"
             class="flex items-center gap-1.5 overflow-x-auto pb-2 -mt-0.5 font-mono text-[10px]">
          <template v-for="(article, idx) in game.state.path" :key="idx">
            <span v-if="idx > 0" class="text-retro-border shrink-0">/</span>
            <span class="px-1.5 py-0.5 rounded shrink-0 whitespace-nowrap"
                  :class="idx === game.state.path.length - 1 ? 'text-crt-green' : 'text-retro-muted'"
                  :style="idx === game.state.path.length - 1 ? 'background: rgba(57,255,20,0.06);' : ''">
              {{ article.replace(/_/g, ' ') }}
            </span>
          </template>
        </div>
      </div>
    </nav>

    <!-- Content area -->
    <main class="flex-1">

      <!-- Boot sequence -->
      <div v-if="initialLoading" class="flex items-center justify-center" style="min-height: calc(100vh - 3rem);">
        <div class="text-center max-w-sm">
          <div class="font-pixel text-[10px] text-crt-green mb-6 animate-blink tracking-widest">INITIALIZING</div>
          <div class="font-mono text-sm text-retro-muted space-y-2 text-left">
            <p v-if="bootStep >= 1" class="animate-fade-in flex items-center gap-2">
              <span class="text-crt-green">></span> Connecting to Wikipedia API...
            </p>
            <p v-if="bootStep >= 2" class="animate-fade-in flex items-center gap-2">
              <span class="text-crt-cyan">></span> Fetching articles...
            </p>
            <p v-if="bootStep >= 3" class="animate-fade-in flex items-center gap-2">
              <span class="text-crt-amber">></span>
              {{ props.mode === 'daily' ? 'Loading daily challenge' : genreId !== 'random' ? `Scanning ${genre.name} topics` : 'Randomizing challenge' }}...
            </p>
          </div>
          <div class="mt-6 retro-divider"></div>
          <p class="font-terminal text-lg text-crt-green animate-blink mt-4">_</p>
        </div>
      </div>

      <!-- Article content -->
      <div v-else class="max-w-4xl mx-auto px-5 py-6 sm:py-8">

        <!-- Loading skeleton -->
        <div v-if="wiki.loading.value" class="space-y-4 animate-fade-in">
          <div class="skeleton h-10 w-3/4 rounded-lg"></div>
          <div class="skeleton h-px w-full"></div>
          <div class="space-y-3">
            <div class="skeleton h-4 w-full"></div>
            <div class="skeleton h-4 w-11/12"></div>
            <div class="skeleton h-4 w-4/5"></div>
            <div class="skeleton h-4 w-full"></div>
            <div class="skeleton h-4 w-3/4"></div>
          </div>
          <div class="skeleton h-4 w-1/3 mt-6"></div>
          <div class="space-y-3">
            <div class="skeleton h-4 w-full"></div>
            <div class="skeleton h-4 w-5/6"></div>
            <div class="skeleton h-4 w-full"></div>
          </div>
        </div>

        <!-- Error -->
        <div v-else-if="wiki.error.value" class="text-center py-24">
          <div class="font-pixel text-[9px] text-crt-red mb-3 tracking-widest">ERROR</div>
          <p class="font-mono text-sm text-retro-light mb-5">{{ wiki.error.value }}</p>
          <button @click="loadArticle(game.state.currentArticle)" class="btn-retro-secondary">RETRY</button>
        </div>

        <!-- Article -->
        <article v-else-if="articleData" class="animate-fade-in">
          <h1 class="font-terminal text-3xl sm:text-4xl text-crt-cyan mb-5 pb-4"
              style="text-shadow: 0 0 12px rgba(0,229,255,0.3); border-bottom: 1px solid rgba(0,229,255,0.12);"
              v-html="articleData.displayTitle"></h1>
          <div class="wiki-content"
               ref="contentRef"
               @click="handleLinkClick"
               @mouseover="handleLinkHover"
               @mouseout="handleLinkHoverEnd"
               v-html="processedHtml"></div>
        </article>
      </div>
    </main>

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
    <Teleport to="body">
      <transition name="fade">
        <div v-if="showEndModal" class="fixed inset-0 z-[60] flex items-center justify-center p-4">
          <div class="absolute inset-0 bg-black/85 backdrop-blur-sm"></div>
          <div class="relative rounded-xl max-w-md w-full animate-scale-in text-center p-7"
               :style="endModalStyle">

            <!-- Win -->
            <template v-if="game.isWon.value && !freeplayFinished">
              <div class="font-pixel text-[9px] text-crt-green mb-1 tracking-[0.3em]">MISSION</div>
              <h2 class="font-pixel text-xl sm:text-2xl text-neon-green mb-5">COMPLETE!</h2>
              <p class="font-mono text-sm text-retro-light mb-6">
                <span class="text-crt-white">{{ game.state.startArticle?.title }}</span>
                <span class="inline-block mx-2">
                  <svg class="w-4 h-4 text-crt-green inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                  </svg>
                </span>
                <span class="text-crt-cyan">{{ game.state.targetArticle?.title }}</span>
              </p>
            </template>

            <!-- Freeplay finish -->
            <template v-else-if="freeplayFinished">
              <div class="font-pixel text-[9px] text-crt-cyan mb-1 tracking-[0.3em]">SESSION</div>
              <h2 class="font-pixel text-xl sm:text-2xl text-neon-cyan mb-5">SUMMARY</h2>
              <p class="font-mono text-sm text-retro-light mb-6">
                You explored <span class="text-crt-green">{{ game.state.path.length }}</span> articles freely.
              </p>
            </template>

            <!-- Loss -->
            <template v-else>
              <div class="font-pixel text-[9px] text-crt-red mb-1 tracking-[0.3em]">GAME</div>
              <h2 class="font-pixel text-xl sm:text-2xl mb-5"
                  style="color: #ff4444; text-shadow: 0 0 15px rgba(255,68,68,0.5), 0 0 40px rgba(255,68,68,0.2);">OVER</h2>
              <p class="font-mono text-sm text-retro-light mb-6">
                {{ game.state.effectiveTimeLimit ? 'Time ran out!' : 'No more clicks!' }}
                <br><span class="text-crt-magenta mt-1 inline-block">Target: {{ game.state.targetArticle?.title }}</span>
              </p>
            </template>

            <!-- Stats grid -->
            <div class="grid grid-cols-3 gap-3 mb-5">
              <div class="rounded-lg p-3" style="background: #12131c; border: 1px solid #252738;">
                <div class="font-terminal text-2xl text-crt-green">{{ game.state.clicks }}</div>
                <div class="font-mono text-[10px] text-retro-muted mt-0.5">CLICKS</div>
              </div>
              <div class="rounded-lg p-3" style="background: #12131c; border: 1px solid #252738;">
                <div class="font-terminal text-2xl text-crt-amber">{{ game.formattedTime.value }}</div>
                <div class="font-mono text-[10px] text-retro-muted mt-0.5">TIME</div>
              </div>
              <div class="rounded-lg p-3" style="background: #12131c; border: 1px solid #252738;">
                <div class="font-terminal text-2xl text-crt-magenta">{{ game.state.path.length }}</div>
                <div class="font-mono text-[10px] text-retro-muted mt-0.5">PAGES</div>
              </div>
            </div>

            <!-- Path log -->
            <div class="mb-6 rounded-lg p-3 max-h-40 overflow-y-auto text-left"
                 style="background: #12131c; border: 1px solid #252738;">
              <div class="font-pixel text-[7px] text-retro-muted tracking-[0.15em] mb-2">PATH LOG</div>
              <div class="space-y-0.5 font-mono text-xs">
                <div v-for="(article, idx) in game.state.path" :key="idx" class="flex items-center gap-2">
                  <span class="text-retro-muted w-5 text-right shrink-0 tabular-nums">{{ String(idx + 1).padStart(2, '0') }}</span>
                  <span style="color: #252738;">|</span>
                  <span :class="idx === game.state.path.length - 1 ? 'text-crt-green' : 'text-retro-light'" class="truncate">
                    {{ article.replace(/_/g, ' ') }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Share / challenge buttons -->
            <div v-if="!freeplayFinished && game.state.targetArticle" class="flex gap-2 mb-4">
              <button @click="shareChallenge" class="btn-retro-ghost flex-1 flex items-center justify-center gap-1.5 !py-2 text-[10px]">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                </svg>
                SHARE CHALLENGE
              </button>
            </div>

            <!-- Daily leaderboard preview -->
            <div v-if="props.mode === 'daily' && dailyLeaderboard.length" class="mb-4 rounded-lg p-3 text-left" style="background: #12131c; border: 1px solid #252738;">
              <div class="font-pixel text-[7px] text-crt-amber tracking-[0.15em] mb-2">DAILY LEADERBOARD</div>
              <div class="space-y-1 font-mono text-xs">
                <div v-for="entry in dailyLeaderboard.slice(0, 5)" :key="entry.rank" class="flex items-center gap-2">
                  <span class="text-retro-muted w-4 text-right shrink-0">#{{ entry.rank }}</span>
                  <span class="text-crt-white flex-1 truncate">{{ entry.username }}</span>
                  <span class="text-crt-green shrink-0">{{ entry.clicks }}</span>
                  <span class="text-retro-muted shrink-0">{{ formatLeaderboardTime(entry.time) }}</span>
                </div>
              </div>
            </div>

            <div class="flex gap-3">
              <button @click="goHome" class="btn-secondary flex-1">HOME</button>
              <button @click="playAgain" class="btn-primary flex-1">PLAY AGAIN</button>
            </div>
          </div>

          <!-- Confetti -->
          <div v-if="game.isWon.value && !freeplayFinished && showConfetti" class="confetti-container" aria-hidden="true">
            <div v-for="i in 40" :key="i" class="confetti-piece" :style="confettiStyle(i)"></div>
          </div>
        </div>
      </transition>
    </Teleport>

    <!-- Quit Confirm Modal -->
    <Teleport to="body">
      <transition name="fade">
        <div v-if="showQuitConfirm" class="fixed inset-0 z-[60] flex items-center justify-center p-4">
          <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" @click="showQuitConfirm = false"></div>
          <div class="relative rounded-xl p-6 max-w-sm w-full animate-scale-in text-center"
               style="background: #0d0e15; border: 2px solid rgba(255,191,0,0.35); box-shadow: 0 0 30px rgba(255,191,0,0.1);">
            <div class="font-pixel text-[9px] text-crt-amber mb-2 tracking-[0.3em]">WARNING</div>
            <p class="font-mono text-sm text-retro-light mb-6">Abort current mission?<br><span class="text-retro-muted text-xs">Your progress will be lost.</span></p>
            <div class="flex gap-3">
              <button @click="showQuitConfirm = false" class="btn-secondary flex-1">CANCEL</button>
              <button @click="goHome" class="btn-primary flex-1" style="background: linear-gradient(180deg, #ff6644, #ff4444); border-color: #ff4444; box-shadow: 0 0 15px rgba(255,68,68,0.3), 0 3px 0 #aa2211;">
                QUIT
              </button>
            </div>
          </div>
        </div>
      </transition>
    </Teleport>
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

const articleData = ref(null)
const contentRef = ref(null)
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

const rerolling = ref(false)
const showConfetti = ref(false)
const dailyLeaderboard = ref([])

const currentMode = computed(() => game.currentMode.value)

const canReroll = computed(() => {
  const m = props.mode
  return m !== 'daily' && m !== 'custom' && m !== 'freeplay'
})

const showEndModal = computed(() => game.isGameOver.value || freeplayFinished.value)

const endModalStyle = computed(() => {
  if (freeplayFinished.value) return 'background: #0d0e15; border: 2px solid rgba(0,229,255,0.4); box-shadow: 0 0 40px rgba(0,229,255,0.15);'
  return game.isWon.value
    ? 'background: #0d0e15; border: 2px solid rgba(57,255,20,0.4); box-shadow: 0 0 40px rgba(57,255,20,0.15), 0 0 80px rgba(57,255,20,0.05);'
    : 'background: #0d0e15; border: 2px solid rgba(255,68,68,0.4); box-shadow: 0 0 40px rgba(255,68,68,0.15), 0 0 80px rgba(255,68,68,0.05);'
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
  if (game.state.timeRemaining <= 10) return 'text-crt-red animate-blink'
  if (game.state.timeRemaining <= 30) return 'text-crt-amber'
  return 'text-crt-white'
})

// Play sounds on game state changes; submit daily score; show confetti
watch(() => game.state.status, (val) => {
  if (val === 'won') {
    sound.playWin()
    showConfetti.value = true
    setTimeout(() => showConfetti.value = false, 3500)
    submitDailyScore()
    loadDailyLeaderboard()
  } else if (val === 'lost') {
    sound.playLose()
    loadDailyLeaderboard()
  }
})

const processedHtml = computed(() => {
  if (!articleData.value?.html) return ''
  const parser = new DOMParser()
  const doc = parser.parseFromString(articleData.value.html, 'text/html')

  doc.querySelectorAll(
    '.mw-editsection, .reference, .reflist, .navbox, .sistersitebox, ' +
    '.mw-empty-elt, .noprint, .mw-authority-control, .catlinks, ' +
    '#coordinates, .metadata, .ambox, .dmbox, .tmbox, .mbox-small, ' +
    '.portal, .wikitable.collapsible, .sidebar-collapse, .mw-references-wrap'
  ).forEach(el => el.remove())

  const links = doc.querySelectorAll('a[href]')
  const allowedTitles = new Set(articleData.value.links || [])
  validLinks.value = allowedTitles

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

  return doc.body.innerHTML
})

async function initializeGame() {
  initialLoading.value = true
  freeplayFinished.value = false
  bootStep.value = 0
  previewCache = {}

  setTimeout(() => bootStep.value = 1, 300)
  setTimeout(() => bootStep.value = 2, 800)
  setTimeout(() => bootStep.value = 3, 1300)

  if (props.mode === 'daily') {
    let pair = null
    // Try server-side daily pair first
    try {
      const serverDaily = await api.get('/daily')
      if (serverDaily.start && serverDaily.end) {
        pair = { start: serverDaily.start, end: serverDaily.end }
      }
    } catch { /* fall through */ }
    // Fall back to client-side generation, then save to server
    if (!pair) {
      pair = await wiki.getDailyPair()
      if (pair) {
        try { await api.post('/daily', { start: pair.start, end: pair.end }) } catch { /* ignore */ }
      }
    }
    if (!pair) { toast.error('Failed to load daily challenge'); router.push({ name: 'home' }); return }
    game.initGame('daily', pair.start, pair.end, 'random', 'normal')
    await loadArticle(pair.start.title)
  } else if (props.mode === 'custom') {
    // Check for shared challenge code
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
    if (!fromQ || !toQ) {
      toast.error('Custom game needs start and target articles')
      router.push({ name: 'home' })
      return
    }
    if (fromQ.toLowerCase() === toQ.toLowerCase()) {
      toast.error('Start and target must be different articles')
      router.push({ name: 'home' })
      return
    }
    const [startArticle, targetArticle] = await Promise.all([
      wiki.getArticleSummary(fromQ),
      wiki.getArticleSummary(toQ),
    ])
    if (!startArticle || !targetArticle) {
      toast.error('Could not find one or both articles. Check the titles and try again.')
      router.push({ name: 'home' })
      return
    }
    game.initGame('custom', startArticle, targetArticle, 'random', 'normal')
    await loadArticle(startArticle.title)
  } else if (game.GAME_MODES[props.mode]?.noTarget) {
    const startArticle = await wiki.getRandomArticleByGenre(genre.value)
    if (!startArticle) { toast.error('Failed to load article'); router.push({ name: 'home' }); return }
    game.initGame(props.mode, startArticle, null, genreId.value, difficulty.value, customLimitsFromRoute.value)
    await loadArticle(startArticle.title)
  } else {
    const pair = await wiki.getRandomPairByGenre(genre.value)
    if (!pair) { toast.error('Could not find article pair'); router.push({ name: 'home' }); return }
    game.initGame(props.mode, pair.start, pair.end, genreId.value, difficulty.value, customLimitsFromRoute.value)
    await loadArticle(pair.start.title)
  }

  initialLoading.value = false
  sound.playStart()
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
  const link = event.target.closest('[data-wiki-title]')
  if (!link || game.isGameOver.value) return

  const title = link.getAttribute('data-wiki-title')
  if (!title) return

  sound.playNavigate()
  const gameOver = game.navigateTo(title)
  if (!gameOver || game.isWon.value) {
    loadArticle(title)
  }
}

function handleLinkHover(event) {
  const link = event.target.closest('[data-wiki-title]')
  if (!link || link === hoveredLink) return
  if (!link) { dismissPreview(); return }

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
  const title = game.goBack()
  if (title) {
    sound.playBack()
    toast.info('Went back one page')
    await loadArticle(title)
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
    const pair = await wiki.getRandomPairByGenre(genre.value)
    if (!pair) { toast.error('Could not find a new pair'); return }
    game.initGame(props.mode, pair.start, pair.end, genreId.value, difficulty.value, customLimitsFromRoute.value)
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

function goHome() {
  game.resetGame()
  router.push({ name: 'home' })
}

async function playAgain() {
  game.resetGame()
  freeplayFinished.value = false
  articleData.value = null
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
  initializeGame()
})

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeydown)
  clearTimeout(hoverTimer)
  game.resetGame()
})
</script>
