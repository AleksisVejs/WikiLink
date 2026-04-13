<template>
  <div class="relative min-h-screen flex flex-col">

    <!-- Top bar -->
    <header class="relative z-20 border-b border-retro-border/40 shrink-0">
      <div class="max-w-6xl mx-auto px-4 sm:px-5 py-2 sm:py-3 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <img src="/favicon.svg" alt="WikiLink" class="w-9 h-9" />
          <span class="font-pixel text-[9px] text-crt-white/80 tracking-wider hidden sm:block">WIKILINK</span>
        </div>
        <div class="flex items-center gap-2">
          <!-- Auth -->
          <template v-if="auth.user.value">
            <router-link to="/profile" class="btn-retro-ghost flex items-center gap-1.5 px-2.5" title="Profile">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
              <span class="font-mono text-[10px] text-crt-green hidden sm:inline">{{ auth.user.value.username }}</span>
            </router-link>
            <button @click="auth.logout()" class="btn-retro-ghost flex items-center gap-1.5 px-2.5" title="Logout">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
              </svg>
            </button>
          </template>
          <button v-else @click="showAuthModal = true" class="btn-retro-ghost flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            <span class="hidden sm:inline">LOGIN</span>
          </button>
          <!-- Mute toggle -->
          <button @click="sound.toggleMute()" class="btn-retro-ghost flex items-center gap-1.5 px-2.5"
                  :title="sound.muted.value ? 'Unmute' : 'Mute'">
            <svg v-if="!sound.muted.value" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072M12 6l-4 4H4v4h4l4 4V6z" />
            </svg>
            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707A1 1 0 0112 5v14a1 1 0 01-1.707.707L5.586 15zM17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2" />
            </svg>
          </button>
          <!-- Level badge -->
          <div class="hidden sm:flex items-center gap-1.5 px-2 py-1 rounded-lg" style="border: 1px solid rgba(57,255,20,0.2); background: rgba(57,255,20,0.04);" title="Your level">
            <span class="font-pixel text-[8px] text-crt-green">LV</span>
            <span class="font-terminal text-sm text-crt-green">{{ progression.level.value }}</span>
          </div>
          <button @click="showStats = true" class="btn-retro-ghost flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            <span class="hidden sm:inline">STATS</span>
          </button>
          <button @click="showHowTo = true" class="btn-retro-ghost flex items-center gap-2" title="How to play">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.065 2.05-1.822 3.772-1.822 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </button>
        </div>
      </div>
    </header>

    <!-- XP Progress Bar -->
    <div class="relative z-20 px-4 sm:px-5 max-w-6xl mx-auto w-full">
      <div class="xp-bar mt-1">
        <div class="xp-bar-fill" :style="{ width: (progression.progress.value * 100) + '%' }"></div>
      </div>
      <div class="flex items-center justify-between mt-0.5 mb-0">
        <span class="font-mono text-[8px] text-retro-muted">{{ progression.currentXp.value }} / {{ progression.nextLevelXp.value }} XP</span>
        <span class="font-mono text-[8px] text-crt-green">Level {{ progression.level.value }}</span>
      </div>
    </div>

    <!-- Main -->
    <main class="flex-1 flex flex-col items-center justify-center min-h-0 px-3 sm:px-4 py-3 sm:py-5 md:py-6 relative z-10">
      <div class="max-w-4xl w-full">

        <!-- Title area -->
        <div class="text-center mb-4 sm:mb-5 animate-fade-in">
          <div class="retro-divider max-w-[200px] sm:max-w-xs mx-auto mb-2 sm:mb-3"></div>
          <h1 class="font-pixel text-2xl sm:text-4xl md:text-5xl text-neon-green mb-1 sm:mb-1.5 leading-tight sm:leading-relaxed tracking-wide">
            WIKI<span class="text-neon-cyan">LINK</span>
          </h1>
          <p class="font-terminal text-base sm:text-lg text-crt-white/60 tracking-[0.15em] mb-1.5 sm:mb-2">The Wiki Game</p>
          <div class="retro-divider max-w-[200px] sm:max-w-xs mx-auto mt-2 sm:mt-3"></div>
        </div>

        <!-- Global stats bar -->
        <div v-if="globalStats" class="flex items-center justify-center gap-4 sm:gap-6 mb-3 sm:mb-4 animate-fade-in">
          <div class="text-center">
            <div class="font-terminal text-sm text-crt-green">{{ globalStats.totalGames }}</div>
            <div class="font-mono text-[8px] text-retro-muted">GAMES</div>
          </div>
          <div class="w-px h-5 bg-retro-border/30"></div>
          <div class="text-center">
            <div class="font-terminal text-sm text-crt-cyan">{{ globalStats.totalPlayers }}</div>
            <div class="font-mono text-[8px] text-retro-muted">PLAYERS</div>
          </div>
          <div class="w-px h-5 bg-retro-border/30"></div>
          <div class="text-center">
            <div class="font-terminal text-sm text-crt-amber">{{ globalStats.totalClicks }}</div>
            <div class="font-mono text-[8px] text-retro-muted">CLICKS</div>
          </div>
        </div>

        <!-- Trending ticker -->
        <div v-if="trending.trendingArticles.value.length > 0" class="mb-3 sm:mb-4 animate-fade-in">
          <div class="flex items-center gap-2 mb-1">
            <span class="font-pixel text-[7px] text-arcade-orange tracking-[0.15em]" v-html="'&#128293; TRENDING NOW'"></span>
          </div>
          <div class="trending-ticker rounded-lg py-1.5" style="background: rgba(255,92,58,0.04); border: 1px solid rgba(255,92,58,0.15);">
            <div class="trending-ticker-track">
              <div class="trending-ticker-set">
                <span v-for="(article, idx) in trending.trendingArticles.value.slice(0, 20)" :key="`a-${idx}`"
                      class="trending-ticker-item font-mono text-[11px] text-retro-light/70">
                  {{ article.title }}
                  <span class="text-arcade-orange/60 ml-1">{{ trending.formatViews(article.views) }}</span>
                </span>
              </div>
              <div class="trending-ticker-set">
                <span v-for="(article, idx) in trending.trendingArticles.value.slice(0, 20)" :key="`b-${idx}`"
                      class="trending-ticker-item font-mono text-[11px] text-retro-light/70">
                  {{ article.title }}
                  <span class="text-arcade-orange/60 ml-1">{{ trending.formatViews(article.views) }}</span>
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Daily challenge banner -->
        <div v-if="!dailyCompleted" class="mb-3 sm:mb-4 animate-slide-up">
          <button @click="startDaily"
                  class="w-full flex items-center justify-between gap-3 px-4 py-3 rounded-xl transition-all duration-200 group touch-manipulation"
                  style="background: linear-gradient(135deg, rgba(255,191,0,0.06), rgba(255,107,43,0.04)); border: 1.5px solid rgba(255,191,0,0.25);"
                  @mouseenter="$event.currentTarget.style.borderColor='rgba(255,191,0,0.45)'; $event.currentTarget.style.boxShadow='0 0 20px rgba(255,191,0,0.1)'"
                  @mouseleave="$event.currentTarget.style.borderColor='rgba(255,191,0,0.25)'; $event.currentTarget.style.boxShadow='none'">
            <div class="flex items-center gap-3 min-w-0">
              <span class="text-xl shrink-0">&#128197;</span>
              <div class="text-left min-w-0">
                <div class="font-pixel text-[8px] text-crt-amber tracking-[0.15em]">DAILY CHALLENGE</div>
                <p class="font-mono text-[10px] sm:text-[11px] text-retro-muted truncate">Same pair for everyone today. Can you beat it?</p>
              </div>
            </div>
            <svg class="w-4 h-4 text-crt-amber/50 group-hover:text-crt-amber transition-colors shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </button>
        </div>
        <div v-else class="mb-3 sm:mb-4 animate-slide-up">
          <div class="w-full flex items-center justify-between gap-3 px-4 py-3 rounded-xl"
               style="background: rgba(57,255,20,0.04); border: 1.5px solid rgba(57,255,20,0.2);">
            <div class="flex items-center gap-3">
              <span class="text-xl shrink-0">&#9989;</span>
              <div>
                <div class="font-pixel text-[8px] text-crt-green tracking-[0.15em]">DAILY COMPLETE</div>
                <p class="font-mono text-[10px] text-retro-muted">
                  {{ dailyStatus.clicks }} clicks &middot; {{ formatTime(dailyStatus.time) }}
                  <span v-if="auth.streak.value > 1" class="text-crt-amber ml-1">&#x1F525; {{ auth.streak.value }}-day streak</span>
                </p>
              </div>
            </div>
            <button @click="shareDailyResult" class="btn-retro-ghost flex items-center gap-1.5 px-2 py-1 text-xs shrink-0" title="Share result">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
              </svg>
              <span class="hidden sm:inline">SHARE</span>
            </button>
          </div>
        </div>

        <!-- Daily Leaderboard -->
        <div v-if="dailyLeaderboard.length > 0" class="mb-3 sm:mb-4 animate-slide-up">
          <button @click="showDailyLeaderboard = !showDailyLeaderboard"
                  class="w-full flex items-center justify-between gap-3 px-4 py-2.5 rounded-t-xl transition-all duration-200"
                  :class="showDailyLeaderboard ? 'rounded-b-none' : 'rounded-b-xl'"
                  style="background: rgba(255,191,0,0.04); border: 1.5px solid rgba(255,191,0,0.2);"
                  :style="showDailyLeaderboard ? 'border-bottom: none;' : ''">
            <div class="flex items-center gap-2.5">
              <svg class="w-4 h-4 text-crt-amber" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
              </svg>
              <span class="font-pixel text-[8px] text-crt-amber tracking-[0.15em]">DAILY LEADERBOARD</span>
              <span class="font-mono text-[10px] text-retro-muted">{{ dailyLeaderboard.length }} player{{ dailyLeaderboard.length !== 1 ? 's' : '' }}</span>
            </div>
            <svg class="w-4 h-4 text-crt-amber/50 transition-transform duration-200" :class="{ 'rotate-180': showDailyLeaderboard }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
          </button>
          <transition name="scale">
            <div v-if="showDailyLeaderboard" class="rounded-b-xl overflow-hidden"
                 style="background: rgba(255,191,0,0.02); border: 1.5px solid rgba(255,191,0,0.2); border-top: none;">
              <div class="px-4 py-1.5 flex items-center gap-2 font-mono text-[9px] text-retro-muted/60 uppercase tracking-wider border-b border-retro-border/20">
                <span class="w-7 text-right">#</span>
                <span class="flex-1">Player</span>
                <span class="w-14 text-right">Clicks</span>
                <span class="w-14 text-right">Time</span>
              </div>
              <div class="max-h-[280px] overflow-y-auto">
                <div v-for="entry in dailyLeaderboard.slice(0, 20)" :key="entry.rank"
                     class="px-4 py-2 flex items-center gap-2 transition-colors hover:bg-retro-surface/20"
                     :class="{
                       'border-l-2 border-l-crt-green bg-crt-green/[0.02]': auth.user.value && entry.username === auth.user.value.username
                     }">
                  <span class="w-7 text-right font-terminal text-sm shrink-0"
                        :class="entry.rank === 1 ? 'text-arcade-gold' : entry.rank === 2 ? 'text-retro-light' : entry.rank === 3 ? 'text-arcade-orange' : 'text-retro-muted'">
                    <template v-if="entry.rank === 1" v-html="'&#128081;'"></template>
                    <template v-else-if="entry.rank === 2" v-html="'&#129352;'"></template>
                    <template v-else-if="entry.rank === 3" v-html="'&#129353;'"></template>
                    <template v-else>{{ entry.rank }}</template>
                  </span>
                  <span class="flex-1 font-mono text-[12px] truncate"
                        :class="auth.user.value && entry.username === auth.user.value.username ? 'text-crt-green' : 'text-crt-white'">
                    {{ entry.username }}
                    <span v-if="auth.user.value && entry.username === auth.user.value.username" class="text-[9px] text-crt-green/50 ml-1">(you)</span>
                  </span>
                  <span class="w-14 text-right font-terminal text-sm text-crt-green tabular-nums">{{ entry.clicks }}</span>
                  <span class="w-14 text-right font-mono text-[11px] text-retro-muted tabular-nums">{{ formatTime(entry.time) }}</span>
                </div>
              </div>
              <div v-if="!auth.user.value" class="px-4 py-2.5 border-t border-retro-border/20 text-center">
                <button @click="showAuthModal = true" class="font-mono text-[10px] text-crt-cyan hover:text-crt-cyan/80 transition-colors">
                  Login to appear on the leaderboard
                </button>
              </div>
            </div>
          </transition>
        </div>

        <!-- 1v1 Match banner -->
        <div class="mb-3 sm:mb-4 animate-slide-up">
          <button @click="showMatchModal = true"
                  class="w-full flex items-center justify-between gap-3 px-4 py-3 rounded-xl transition-all duration-200 group touch-manipulation"
                  style="background: linear-gradient(135deg, rgba(180,76,255,0.06), rgba(76,159,255,0.04)); border: 1.5px solid rgba(180,76,255,0.25);"
                  @mouseenter="$event.currentTarget.style.borderColor='rgba(180,76,255,0.45)'; $event.currentTarget.style.boxShadow='0 0 20px rgba(180,76,255,0.1)'"
                  @mouseleave="$event.currentTarget.style.borderColor='rgba(180,76,255,0.25)'; $event.currentTarget.style.boxShadow='none'">
            <div class="flex items-center gap-3 min-w-0">
              <span class="text-xl shrink-0" v-html="'&#9876;'"></span>
              <div class="text-left min-w-0">
                <div class="font-pixel text-[8px] text-arcade-purple tracking-[0.15em]">1v1 MATCH</div>
                <p class="font-mono text-[10px] sm:text-[11px] text-retro-muted truncate">Challenge a friend to the same pair. Who wins?</p>
              </div>
            </div>
            <svg class="w-4 h-4 text-arcade-purple/50 group-hover:text-arcade-purple transition-colors shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </button>
        </div>

        <!-- Display frame (game config) -->
        <div class="relative rounded-xl sm:rounded-2xl overflow-hidden animate-slide-up"
             style="background: linear-gradient(180deg, #0d0e15, #0a0b12); border: 2px solid #252738; box-shadow: inset 0 0 60px rgba(0,0,0,0.4), 0 8px 30px rgba(0,0,0,0.5);">

          <!-- Top decorative bar -->
          <div class="px-3 sm:px-5 py-2 sm:py-2.5 border-b border-retro-border/50 flex items-center justify-between gap-2"
               style="background: linear-gradient(180deg, #161724, #12131c);">
            <div class="flex items-center gap-1.5 sm:gap-2 shrink-0">
              <span class="w-2 h-2 sm:w-2.5 sm:h-2.5 rounded-full bg-crt-red/50"></span>
              <span class="w-2 h-2 sm:w-2.5 sm:h-2.5 rounded-full bg-crt-amber/50"></span>
              <span class="w-2 h-2 sm:w-2.5 sm:h-2.5 rounded-full bg-crt-green/50"></span>
            </div>
            <span class="font-pixel text-[6px] sm:text-[7px] text-retro-muted tracking-[0.2em] sm:tracking-[0.3em] truncate text-center flex-1 min-w-0 px-1">GAME CONFIG</span>
            <div class="flex items-center gap-1.5 sm:gap-2 text-retro-muted shrink-0">
              <span class="w-1.5 h-1.5 sm:w-2 sm:h-2 rounded-full bg-crt-green/40 animate-glow-pulse"></span>
              <span class="font-mono text-[9px] sm:text-[10px]">READY</span>
            </div>
          </div>

          <!-- Genre section (hidden for Custom) -->
          <div v-if="selectedModeId !== 'custom'" class="p-3 sm:p-4 md:p-5">
            <div class="flex items-center gap-2.5 mb-2.5 sm:mb-3">
              <div class="w-1 h-4 rounded-full bg-crt-amber"></div>
              <span class="font-pixel text-[8px] text-crt-amber tracking-[0.2em]">SELECT GENRE</span>
            </div>
            <div class="flex flex-wrap gap-1.5 sm:gap-2">
              <button v-for="g in genres" :key="g.id"
                @click="selectedGenreId = g.id; sound.playClick()"
                class="flex items-center gap-1.5 sm:gap-2 px-2.5 sm:px-3 py-1.5 sm:py-2 rounded-lg transition-all duration-200 cursor-pointer touch-manipulation active:scale-95"
                :style="selectedGenreId === g.id
                  ? 'border: 1.5px solid rgba(57,255,20,0.5); background: rgba(57,255,20,0.05); box-shadow: 0 0 10px rgba(57,255,20,0.12), inset 0 0 8px rgba(57,255,20,0.04);'
                  : 'border: 1.5px solid #252738;'">
                <span class="text-sm sm:text-base" :style="selectedGenreId === g.id ? 'filter: drop-shadow(0 0 6px rgba(57,255,20,0.5))' : ''">{{ g.emoji }}</span>
                <span class="font-mono text-[10px] sm:text-[11px] transition-colors whitespace-nowrap"
                      :class="selectedGenreId === g.id ? 'text-crt-green' : 'text-retro-muted'">{{ g.shortName }}</span>
              </button>
            </div>
          </div>

          <div v-if="selectedModeId !== 'custom'" class="mx-3 sm:mx-5"><div class="retro-divider"></div></div>

          <!-- Mode section -->
          <div class="p-3 sm:p-4 md:p-5">
            <div class="flex items-center gap-2.5 mb-2.5 sm:mb-3">
              <div class="w-1 h-4 rounded-full bg-crt-cyan"></div>
              <span class="font-pixel text-[8px] text-crt-cyan tracking-[0.2em]">SELECT MODE</span>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2 sm:gap-3">
              <button v-for="mode in displayModes" :key="mode.id"
                @click="selectedModeId = mode.id; sound.playClick()"
                class="mode-card w-full text-left flex items-center gap-2.5 sm:gap-3.5 p-2.5 sm:p-3.5 touch-manipulation active:opacity-90"
                :class="{ selected: selectedModeId === mode.id }"
                :style="selectedModeId === mode.id ? `--mode-color: ${mode.uiColor}; border-color: ${mode.borderHex}` : ''">
                <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-lg flex items-center justify-center text-lg sm:text-xl shrink-0 transition-all duration-200"
                     :style="selectedModeId === mode.id ? `background: ${mode.bgGlow}; box-shadow: 0 0 12px ${mode.shadowColor};` : 'background: #181a25; border: 1px solid #252738;'">
                  <span>{{ mode.emoji }}</span>
                </div>
                <div class="min-w-0 flex-1">
                  <div class="flex items-center gap-2 mb-0.5">
                    <span class="font-pixel text-[7px] sm:text-[8px] tracking-wider transition-colors"
                          :style="selectedModeId === mode.id ? `color: ${mode.uiColor}` : 'color: #d4d4e8'">{{ mode.name.toUpperCase() }}</span>
                    <span v-if="mode.tag" class="font-mono text-[9px] sm:text-[10px] px-1.5 py-0.5 rounded"
                          :style="`color: ${mode.uiColor}; background: ${mode.tagBg}; border: 1px solid ${mode.tagBorder};`">{{ mode.tag }}</span>
                  </div>
                  <p class="font-mono text-[10px] sm:text-[11px] text-retro-muted leading-snug line-clamp-1 sm:line-clamp-2">{{ mode.description }}</p>
                </div>
                <div v-if="selectedModeId === mode.id" class="font-terminal text-lg sm:text-xl animate-blink shrink-0" :style="`color: ${mode.uiColor}`">&#9654;</div>
              </button>
            </div>
          </div>

          <!-- Difficulty selector (only for sprint / challenge) -->
          <template v-if="showDifficulty">
            <div class="mx-3 sm:mx-5"><div class="retro-divider"></div></div>
            <div class="p-3 sm:p-4 md:p-5">
              <div class="flex items-center gap-2.5 mb-2.5 sm:mb-3">
                <div class="w-1 h-4 rounded-full bg-crt-magenta"></div>
                <span class="font-pixel text-[8px] text-crt-magenta tracking-[0.2em]">LIMIT PRESET</span>
              </div>
              <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 sm:gap-3">
                <button v-for="d in difficulties" :key="d.id"
                  @click="selectedDifficulty = d.id; sound.playClick()"
                  class="py-2 sm:py-2.5 rounded-lg font-pixel text-[7px] sm:text-[8px] tracking-wider transition-all duration-200 touch-manipulation active:scale-95"
                  :style="selectedDifficulty === d.id
                    ? `border: 1.5px solid ${d.color}; background: ${d.bg}; color: ${d.color}; box-shadow: 0 0 12px ${d.shadow};`
                    : 'border: 1.5px solid #252738; color: #555770;'">
                  {{ d.label }}
                  <div class="font-mono text-[9px] mt-0.5 opacity-60">{{ d.detail }}</div>
                </button>
              </div>
              <div v-if="selectedDifficulty === 'custom' && selectedModeId === 'sprint'" class="mt-3 flex flex-col sm:flex-row sm:items-center gap-2">
                <label class="font-mono text-[10px] text-retro-muted shrink-0">Time limit (sec)</label>
                <input v-model.number="customTimeLimit" type="number" :min="LIMIT_BOUNDS.timeMin" :max="LIMIT_BOUNDS.timeMax"
                       class="w-full sm:max-w-[140px] px-2.5 py-1.5 rounded-lg font-mono text-xs bg-[#12131c] border border-retro-border text-crt-white focus:border-crt-cyan focus:outline-none" />
              </div>
              <div v-if="selectedDifficulty === 'custom' && selectedModeId === 'challenge'" class="mt-3 flex flex-col sm:flex-row sm:items-center gap-2">
                <label class="font-mono text-[10px] text-retro-muted shrink-0">Max link clicks</label>
                <input v-model.number="customClickLimit" type="number" :min="LIMIT_BOUNDS.clicksMin" :max="LIMIT_BOUNDS.clicksMax"
                       class="w-full sm:max-w-[140px] px-2.5 py-1.5 rounded-lg font-mono text-xs bg-[#12131c] border border-retro-border text-crt-white focus:border-crt-cyan focus:outline-none" />
              </div>
            </div>
          </template>

          <!-- Modifiers section -->
          <template v-if="showModifiers">
            <div class="mx-3 sm:mx-5"><div class="retro-divider"></div></div>
            <div class="p-3 sm:p-4 md:p-5">
              <div class="flex items-center gap-2.5 mb-2.5 sm:mb-3">
                <div class="w-1 h-4 rounded-full bg-crt-amber"></div>
                <span class="font-pixel text-[8px] text-crt-amber tracking-[0.2em]">MODIFIERS</span>
                <span v-if="activeModifiers.length > 0" class="font-mono text-[9px] text-crt-green ml-1">{{ (Math.pow(1.25, activeModifiers.length)).toFixed(2) }}x XP</span>
              </div>
              <div class="flex flex-wrap gap-1.5 sm:gap-2">
                <button v-for="mod in filteredModifiers" :key="mod.id"
                  @click="toggleModifier(mod.id)"
                  class="modifier-chip"
                  :class="{ active: activeModifiers.includes(mod.id) }"
                  :title="mod.description">
                  <span v-html="mod.icon"></span>
                  <span>{{ mod.name }}</span>
                </button>
              </div>
              <p class="font-mono text-[9px] text-retro-muted/50 mt-2">Each modifier adds a 1.25x XP multiplier</p>
            </div>
          </template>

          <!-- Custom route: start / target articles -->
          <template v-if="selectedModeId === 'custom'">
            <div class="mx-3 sm:mx-5"><div class="retro-divider"></div></div>
            <div class="p-3 sm:p-4 md:p-5">
              <div class="flex items-center gap-2.5 mb-2.5 sm:mb-3">
                <div class="w-1 h-4 rounded-full bg-crt-amber"></div>
                <span class="font-pixel text-[8px] text-crt-amber tracking-[0.2em]">ARTICLES</span>
              </div>
              <p class="font-mono text-[10px] text-retro-muted mb-3">Type to search Wikipedia; pick a suggestion or enter a title yourself.</p>
              <div class="space-y-2.5">
                <WikiTitleInput v-model="customStartTitle" label="Start" placeholder="Starting article" />
                <WikiTitleInput v-model="customEndTitle" label="Target" placeholder="Target article" />
              </div>
            </div>
          </template>

          <div class="mx-3 sm:mx-5"><div class="retro-divider"></div></div>

          <!-- Start button area -->
          <div class="p-3 sm:p-4 md:p-5 flex flex-col items-center">
            <button type="button" @click="startGame" class="btn-retro-primary animate-glow-pulse w-full sm:w-auto sm:min-w-[240px] min-h-[48px] sm:min-h-0 touch-manipulation">
              START GAME
            </button>
            <div class="mt-2.5 sm:mt-3 flex items-center justify-center gap-2">
              <span class="w-1.5 h-1.5 rounded-full bg-crt-green/50 animate-blink"></span>
              <p class="font-pixel text-[6px] sm:text-[7px] text-retro-muted/50 tracking-[0.25em] animate-blink-slow">
                PRESS START TO BEGIN
              </p>
              <span class="w-1.5 h-1.5 rounded-full bg-crt-green/50 animate-blink"></span>
            </div>
          </div>

          <!-- Bottom status bar -->
          <div class="px-3 sm:px-5 py-2 sm:py-2.5 border-t border-retro-border/30 flex items-center justify-between gap-2"
               style="background: linear-gradient(180deg, #12131c, #0d0e15);">
            <div class="font-mono text-[10px] sm:text-[11px] text-retro-muted truncate min-w-0">
              <span class="text-crt-amber">{{ selectedModeId === 'custom' ? '---' : selectedGenre?.name }}</span>
              <span class="mx-1.5 sm:mx-2 text-retro-border">/</span>
              <span class="text-crt-cyan">{{ selectedMode?.name }}</span>
              <span v-if="showDifficulty" class="text-crt-magenta ml-1.5">[{{ selectedDifficulty.toUpperCase() }}]</span>
            </div>
            <div class="font-mono text-[9px] sm:text-[10px] text-retro-muted/40 shrink-0">
              {{ genres.length }} GENRES &middot; {{ displayModes.length }} MODES
            </div>
          </div>
        </div>

      </div>
    </main>

    <!-- Footer -->
    <footer class="relative z-10 py-2 sm:py-3 md:py-4 border-t border-retro-border/20 shrink-0">
      <div class="max-w-6xl mx-auto px-4 sm:px-5 flex flex-col-reverse gap-1 sm:flex-row sm:items-center sm:justify-between sm:gap-0">
        <span class="font-mono text-[9px] sm:text-[10px] text-retro-muted/40 tracking-wider break-words">POWERED BY WIKIPEDIA API</span>
        <span class="font-mono text-[9px] sm:text-[10px] text-retro-muted/40 sm:text-right">v{{ APP_VERSION }}</span>
      </div>
    </footer>

    <!-- Stats Modal -->
    <Teleport to="body">
      <transition name="fade">
        <div v-if="showStats" class="fixed inset-0 z-[60] flex items-end sm:items-center justify-center p-0 sm:p-4 max-h-[100dvh]">
          <div class="absolute inset-0 bg-black/85 backdrop-blur-sm" @click="showStats = false"></div>
          <div class="relative rounded-t-2xl sm:rounded-xl p-4 sm:p-6 max-w-lg w-full max-h-[min(92dvh,640px)] sm:max-h-[90vh] overflow-y-auto overscroll-contain animate-scale-in"
               style="background: #0d0e15; border: 2px solid #252738; box-shadow: 0 0 60px rgba(0,0,0,0.8), 0 0 30px rgba(0,229,255,0.04);">

            <div class="flex items-center justify-between mb-4 sm:mb-5 gap-2">
              <div class="flex items-center gap-2.5">
                <div class="w-1 h-4 rounded-full bg-crt-amber"></div>
                <h2 class="font-pixel text-[9px] text-crt-amber tracking-[0.2em]">YOUR STATS</h2>
              </div>
              <div class="flex items-center gap-1">
                <button @click="statsTab = 'modes'" class="font-mono text-[10px] px-2 py-1 rounded transition-all"
                        :class="statsTab === 'modes' ? 'text-crt-cyan bg-crt-cyan/10' : 'text-retro-muted hover:text-crt-white'">Modes</button>
                <button @click="statsTab = 'genres'" class="font-mono text-[10px] px-2 py-1 rounded transition-all"
                        :class="statsTab === 'genres' ? 'text-crt-cyan bg-crt-cyan/10' : 'text-retro-muted hover:text-crt-white'">Genres</button>
                <button @click="statsTab = 'badges'" class="font-mono text-[10px] px-2 py-1 rounded transition-all"
                        :class="statsTab === 'badges' ? 'text-arcade-gold bg-arcade-gold/10' : 'text-retro-muted hover:text-crt-white'">
                  Badges <span class="text-[8px] opacity-60">{{ achievements.unlockedCount.value }}/{{ achievements.totalCount.value }}</span>
                </button>
                <button @click="statsTab = 'history'" class="font-mono text-[10px] px-2 py-1 rounded transition-all"
                        :class="statsTab === 'history' ? 'text-crt-cyan bg-crt-cyan/10' : 'text-retro-muted hover:text-crt-white'">History</button>
                <button @click="showStats = false" class="btn-ghost p-1.5 ml-1">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>
            </div>

            <!-- Modes tab -->
            <div v-if="statsTab === 'modes'">
              <div v-if="hasStats" class="space-y-3">
                <div v-for="(modeStats, modeId) in stats.modes" :key="modeId" class="rounded-lg p-4" style="background: #12131c; border: 1px solid #252738;">
                  <h3 class="font-pixel text-[7px] text-crt-cyan mb-2 sm:mb-3 tracking-wider">{{ GAME_MODES[modeId]?.name?.toUpperCase() || modeId }}</h3>
                  <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div class="text-center">
                      <div class="font-terminal text-xl text-crt-green tabular-nums">{{ modeStats.gamesWon }}</div>
                      <div class="font-mono text-[9px] text-retro-muted">WON</div>
                    </div>
                    <div class="text-center">
                      <div class="font-terminal text-xl text-crt-red tabular-nums">{{ modeStats.gamesLost || 0 }}</div>
                      <div class="font-mono text-[9px] text-retro-muted">LOST</div>
                    </div>
                    <div class="text-center">
                      <div class="font-terminal text-xl text-crt-amber tabular-nums">{{ modeStats.bestClicks ?? '-' }}</div>
                      <div class="font-mono text-[9px] text-retro-muted">BEST CLICKS</div>
                    </div>
                    <div class="text-center">
                      <div class="font-terminal text-xl text-crt-cyan tabular-nums">{{ formatTime(modeStats.bestTime) }}</div>
                      <div class="font-mono text-[9px] text-retro-muted">BEST TIME</div>
                    </div>
                  </div>
                  <div v-if="modeStats.bestStreak" class="mt-2 pt-2 border-t border-retro-border/20 flex items-center justify-center gap-1">
                    <span class="font-mono text-[9px] text-retro-muted">BEST STREAK:</span>
                    <span class="font-terminal text-sm text-arcade-gold">{{ modeStats.bestStreak }}</span>
                  </div>
                </div>
              </div>
              <div v-else class="text-center py-10">
                <div class="font-pixel text-[8px] text-retro-muted/50 mb-2">NO DATA YET</div>
                <p class="font-mono text-xs text-retro-muted/30">Play a game to see your stats here</p>
              </div>
            </div>

            <!-- Genres tab -->
            <div v-if="statsTab === 'genres'">
              <div v-if="hasGenreStats" class="space-y-2">
                <div v-for="(gs, gId) in stats.genres" :key="gId" class="rounded-lg px-4 py-3 flex items-center justify-between" style="background: #12131c; border: 1px solid #252738;">
                  <div class="flex items-center gap-2.5">
                    <span class="text-base">{{ GENRES[gId]?.emoji || '?' }}</span>
                    <span class="font-pixel text-[7px] text-crt-white tracking-wider">{{ GENRES[gId]?.name?.toUpperCase() || gId }}</span>
                  </div>
                  <div class="flex items-center gap-4 text-center">
                    <div>
                      <div class="font-terminal text-sm text-crt-green">{{ gs.gamesWon }}</div>
                      <div class="font-mono text-[8px] text-retro-muted">W</div>
                    </div>
                    <div>
                      <div class="font-terminal text-sm text-crt-red">{{ gs.gamesLost || 0 }}</div>
                      <div class="font-mono text-[8px] text-retro-muted">L</div>
                    </div>
                    <div>
                      <div class="font-terminal text-sm text-crt-cyan">{{ gs.gamesPlayed }}</div>
                      <div class="font-mono text-[8px] text-retro-muted">GP</div>
                    </div>
                  </div>
                </div>
              </div>
              <div v-else class="text-center py-10">
                <div class="font-pixel text-[8px] text-retro-muted/50 mb-2">NO GENRE DATA</div>
                <p class="font-mono text-xs text-retro-muted/30">Play games with different genres to see stats here</p>
              </div>
            </div>

            <!-- Badges tab -->
            <div v-if="statsTab === 'badges'">
              <div class="grid grid-cols-3 sm:grid-cols-4 gap-2">
                <div v-for="badge in achievements.getAllAchievements()" :key="badge.id"
                     class="achievement-badge" :class="badge.unlocked ? 'unlocked' : 'locked'"
                     :title="badge.unlocked ? badge.description : '???'">
                  <span class="badge-icon" v-html="badge.icon"></span>
                  <div class="font-pixel text-[6px] tracking-wider" :class="badge.unlocked ? 'text-arcade-gold' : 'text-retro-muted/40'">{{ badge.unlocked ? badge.name : '???' }}</div>
                  <div v-if="badge.unlocked" class="font-mono text-[8px] text-crt-green mt-0.5">+{{ badge.xp }} XP</div>
                </div>
              </div>
              <div class="mt-3 text-center">
                <div class="font-terminal text-lg text-arcade-gold">{{ achievements.unlockedCount.value }} / {{ achievements.totalCount.value }}</div>
                <div class="font-mono text-[9px] text-retro-muted">achievements unlocked</div>
              </div>
            </div>

            <!-- History tab -->
            <div v-if="statsTab === 'history'">
              <div v-if="history.length" class="space-y-2">
                <div v-for="(entry, idx) in history" :key="idx" class="rounded-lg px-3 py-2.5 flex items-center gap-3" style="background: #12131c; border: 1px solid #252738;">
                  <div class="w-8 h-8 rounded-lg flex items-center justify-center text-sm shrink-0"
                       :style="entry.result === 'won' ? 'background: rgba(57,255,20,0.08); border: 1px solid rgba(57,255,20,0.2);' : entry.result === 'finished' ? 'background: rgba(0,229,255,0.08); border: 1px solid rgba(0,229,255,0.2);' : 'background: rgba(255,68,68,0.08); border: 1px solid rgba(255,68,68,0.2);'">
                    {{ entry.result === 'won' ? '&#10003;' : entry.result === 'finished' ? '&#10148;' : '&#10007;' }}
                  </div>
                  <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2 mb-0.5">
                      <span class="font-pixel text-[6px] tracking-wider"
                            :class="entry.result === 'won' ? 'text-crt-green' : entry.result === 'finished' ? 'text-crt-cyan' : 'text-crt-red'">{{ GAME_MODES[entry.mode]?.name?.toUpperCase() || entry.mode }}</span>
                      <span class="font-mono text-[9px] text-retro-muted/50">{{ formatDate(entry.date) }}</span>
                    </div>
                    <p class="font-mono text-[10px] text-retro-muted truncate">{{ entry.start }}{{ entry.target ? ' -> ' + entry.target : '' }}</p>
                  </div>
                  <div class="text-right shrink-0">
                    <div class="font-terminal text-sm text-crt-white tabular-nums">{{ entry.clicks }} clicks</div>
                    <div class="font-mono text-[9px] text-retro-muted">{{ formatTime(entry.time) }}</div>
                  </div>
                </div>
              </div>
              <div v-else class="text-center py-10">
                <div class="font-pixel text-[8px] text-retro-muted/50 mb-2">NO HISTORY</div>
                <p class="font-mono text-xs text-retro-muted/30">Your recent games will appear here</p>
              </div>
            </div>
          </div>
        </div>
      </transition>
    </Teleport>

    <!-- Auth Modal -->
    <Teleport to="body">
      <transition name="fade">
        <div v-if="showAuthModal" class="fixed inset-0 z-[60] flex items-center justify-center p-4">
          <div class="absolute inset-0 bg-black/85 backdrop-blur-sm" @click="showAuthModal = false"></div>
          <div class="relative rounded-xl p-6 max-w-sm w-full animate-scale-in"
               style="background: #0d0e15; border: 2px solid #252738; box-shadow: 0 0 60px rgba(0,0,0,0.8);">
            <div class="flex items-center justify-between mb-5">
              <div class="flex items-center gap-1">
                <button @click="authMode = 'login'" class="font-pixel text-[8px] px-3 py-1.5 rounded transition-all"
                        :class="authMode === 'login' ? 'text-crt-green bg-crt-green/10' : 'text-retro-muted'">LOGIN</button>
                <button @click="authMode = 'register'" class="font-pixel text-[8px] px-3 py-1.5 rounded transition-all"
                        :class="authMode === 'register' ? 'text-crt-cyan bg-crt-cyan/10' : 'text-retro-muted'">REGISTER</button>
              </div>
              <button @click="showAuthModal = false" class="btn-ghost p-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
            <form @submit.prevent="handleAuth" class="space-y-3">
              <div>
                <label class="font-mono text-[10px] text-retro-muted block mb-1">Username</label>
                <input v-model="authUsername" type="text" autocomplete="username" required minlength="2" maxlength="24"
                       class="w-full px-3 py-2 rounded-lg font-mono text-sm bg-[#12131c] border border-retro-border text-crt-white focus:border-crt-cyan focus:outline-none" />
              </div>
              <div>
                <label class="font-mono text-[10px] text-retro-muted block mb-1">Password</label>
                <div class="relative">
                  <input v-model="authPassword" :type="showPassword ? 'text' : 'password'" :autocomplete="authMode === 'login' ? 'current-password' : 'new-password'" required minlength="6"
                         class="w-full px-3 py-2 pr-10 rounded-lg font-mono text-sm bg-[#12131c] border border-retro-border text-crt-white focus:border-crt-cyan focus:outline-none" />
                  <button type="button" @click="showPassword = !showPassword" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-retro-muted hover:text-crt-white transition-colors" tabindex="-1">
                    <svg v-if="!showPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                    </svg>
                  </button>
                </div>
              </div>
              <div v-if="authMode === 'register'">
                <label class="font-mono text-[10px] text-retro-muted block mb-1">Confirm Password</label>
                <div class="relative">
                  <input v-model="authConfirmPassword" :type="showConfirmPassword ? 'text' : 'password'" autocomplete="new-password" required minlength="6"
                         class="w-full px-3 py-2 pr-10 rounded-lg font-mono text-sm bg-[#12131c] border border-retro-border text-crt-white focus:border-crt-cyan focus:outline-none" />
                  <button type="button" @click="showConfirmPassword = !showConfirmPassword" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-retro-muted hover:text-crt-white transition-colors" tabindex="-1">
                    <svg v-if="!showConfirmPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                    </svg>
                  </button>
                </div>
              </div>
              <div v-if="authError" class="font-mono text-[11px] text-crt-red">{{ authError }}</div>
              <button type="submit" :disabled="auth.loading.value" class="btn-retro-primary w-full !py-2.5">
                {{ auth.loading.value ? 'LOADING...' : authMode === 'login' ? 'LOGIN' : 'CREATE ACCOUNT' }}
              </button>
            </form>
          </div>
        </div>
      </transition>
    </Teleport>

    <!-- 1v1 Match Modal -->
    <Teleport to="body">
      <transition name="fade">
        <div v-if="showMatchModal" class="fixed inset-0 z-[60] flex items-center justify-center p-4">
          <div class="absolute inset-0 bg-black/85 backdrop-blur-sm" @click="showMatchModal = false"></div>
          <div class="relative rounded-xl p-6 max-w-sm w-full animate-scale-in"
               style="background: #0d0e15; border: 2px solid rgba(180,76,255,0.35); box-shadow: 0 0 60px rgba(0,0,0,0.8);">
            <div class="flex items-center justify-between mb-5">
              <div class="flex items-center gap-2.5">
                <div class="w-1 h-4 rounded-full bg-arcade-purple"></div>
                <h2 class="font-pixel text-[9px] text-arcade-purple tracking-[0.2em]">1v1 MATCH</h2>
              </div>
              <button @click="showMatchModal = false" class="btn-ghost p-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <template v-if="matchTab === 'waiting'">
              <div class="text-center py-4">
                <div class="font-pixel text-[8px] mb-3 tracking-wider"
                     :class="matchStatus === 'ready' ? 'text-crt-green' : 'text-crt-amber'">
                  {{ matchStatus === 'ready' ? 'OPPONENT JOINED!' : 'WAITING FOR OPPONENT' }}
                </div>
                <div class="font-terminal text-4xl text-arcade-purple mb-3 tracking-[0.3em]">{{ matchCode }}</div>
                <p class="font-mono text-xs text-retro-muted mb-2">Share this code with your opponent</p>

                <!-- Waiting indicator -->
                <div v-if="matchStatus !== 'ready'" class="flex items-center justify-center gap-2 mb-4 py-2">
                  <span class="w-2 h-2 rounded-full bg-crt-amber animate-blink"></span>
                  <span class="font-mono text-[11px] text-crt-amber/70 animate-blink-slow">Scanning for opponent...</span>
                </div>

                <!-- Ready indicator -->
                <div v-else class="flex items-center justify-center gap-2 mb-4 py-2">
                  <span class="w-2 h-2 rounded-full bg-crt-green" style="box-shadow: 0 0 6px rgba(57,255,20,0.5);"></span>
                  <span class="font-mono text-[11px] text-crt-green">Opponent is ready!</span>
                </div>

                <div class="flex gap-2">
                  <button @click="copyMatchCode" class="btn-retro-ghost flex-1 flex items-center justify-center gap-1.5 !py-2 text-[10px]">
                    COPY CODE
                  </button>
                  <button @click="startCreatedMatch"
                          :disabled="matchStatus !== 'ready'"
                          class="flex-1 !py-2 !px-4 !text-[9px]"
                          :class="matchStatus === 'ready' ? 'btn-retro-primary' : 'btn-retro-ghost opacity-40 cursor-not-allowed'">
                    {{ matchStatus === 'ready' ? 'START' : 'WAITING...' }}
                  </button>
                </div>
              </div>
            </template>

            <template v-else>
              <div class="flex gap-1 mb-4">
                <button @click="matchTab = 'create'" class="font-pixel text-[8px] px-3 py-1.5 rounded transition-all"
                        :class="matchTab === 'create' ? 'text-arcade-purple bg-arcade-purple/10' : 'text-retro-muted'">CREATE</button>
                <button @click="matchTab = 'join'" class="font-pixel text-[8px] px-3 py-1.5 rounded transition-all"
                        :class="matchTab === 'join' ? 'text-crt-cyan bg-crt-cyan/10' : 'text-retro-muted'">JOIN</button>
              </div>

              <div v-if="matchTab === 'create'" class="space-y-3">
                <p class="font-mono text-xs text-retro-muted">Create a match with a random article pair. Share the code with a friend.</p>
                <button @click="createMatch" :disabled="matchLoading" class="btn-retro-primary w-full !py-2.5">
                  {{ matchLoading ? 'CREATING...' : 'CREATE MATCH' }}
                </button>
              </div>

              <div v-if="matchTab === 'join'" class="space-y-3">
                <p class="font-mono text-xs text-retro-muted">Enter a match code from your opponent.</p>
                <input v-model="joinCode" type="text" placeholder="ABCD12" maxlength="6"
                       class="w-full px-3 py-2 rounded-lg font-terminal text-xl text-center tracking-[0.3em] bg-[#12131c] border border-retro-border text-crt-white focus:border-arcade-purple focus:outline-none uppercase" />
                <button @click="joinMatch" :disabled="matchLoading" class="btn-retro-primary w-full !py-2.5">
                  {{ matchLoading ? 'JOINING...' : 'JOIN MATCH' }}
                </button>
              </div>

              <div v-if="matchError" class="font-mono text-[11px] text-crt-red mt-3">{{ matchError }}</div>
            </template>
          </div>
        </div>
      </transition>
    </Teleport>

    <!-- How to Play Modal -->
    <Teleport to="body">
      <transition name="fade">
        <div v-if="showHowTo" class="fixed inset-0 z-[60] flex items-end sm:items-center justify-center p-0 sm:p-4 max-h-[100dvh]">
          <div class="absolute inset-0 bg-black/85 backdrop-blur-sm" @click="showHowTo = false"></div>
          <div class="relative rounded-t-2xl sm:rounded-xl p-5 sm:p-6 max-w-lg w-full max-h-[min(92dvh,600px)] sm:max-h-[90vh] overflow-y-auto overscroll-contain animate-scale-in"
               style="background: #0d0e15; border: 2px solid #252738;">
            <div class="flex items-center justify-between mb-4">
              <div class="flex items-center gap-2.5">
                <div class="w-1 h-4 rounded-full bg-crt-cyan"></div>
                <h2 class="font-pixel text-[9px] text-crt-cyan tracking-[0.2em]">HOW TO PLAY</h2>
              </div>
              <button @click="showHowTo = false" class="btn-ghost p-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
            <div class="space-y-4 font-mono text-sm text-retro-light leading-relaxed">
              <div>
                <h3 class="font-pixel text-[8px] text-crt-green mb-1.5 tracking-wider">THE GOAL</h3>
                <p class="text-[12px]">Navigate from a <span class="text-crt-green">starting Wikipedia article</span> to a <span class="text-crt-magenta">target article</span> by clicking only the links within each page.</p>
              </div>
              <div>
                <h3 class="font-pixel text-[8px] text-crt-amber mb-1.5 tracking-wider">GAME MODES</h3>
                <ul class="space-y-1 text-[12px]">
                  <li><span class="text-crt-blue">Classic</span> -- fewest clicks, no timer</li>
                  <li><span class="text-arcade-orange">Sprint</span> -- beat the clock</li>
                  <li><span class="text-crt-magenta">Click Limit</span> -- limited number of clicks</li>
                  <li><span style="color:#ff5c3a">Trending</span> -- navigate between trending articles</li>
                  <li><span class="text-arcade-gold">Custom</span> -- choose your own articles</li>
                  <li><span class="text-crt-green">Freeplay</span> -- explore with no target</li>
                  <li><span class="text-crt-amber">Daily</span> -- same pair for everyone, once per day</li>
                </ul>
              </div>
              <div>
                <h3 class="font-pixel text-[8px] text-crt-cyan mb-1.5 tracking-wider">CONTROLS</h3>
                <ul class="space-y-1 text-[12px]">
                  <li><span class="text-crt-green">Green links</span> = clickable Wikipedia links</li>
                  <li><span class="text-retro-muted">Gray links</span> = disabled (external or excluded)</li>
                  <li><kbd class="px-1 py-0.5 rounded text-[10px] bg-retro-surface border border-retro-border text-crt-cyan">Backspace</kbd> Go back one page</li>
                  <li><kbd class="px-1 py-0.5 rounded text-[10px] bg-retro-surface border border-retro-border text-crt-amber">R</kbd> Reroll for a new pair</li>
                  <li><kbd class="px-1 py-0.5 rounded text-[10px] bg-retro-surface border border-retro-border text-crt-red">Esc</kbd> Quit current game</li>
                </ul>
              </div>
              <div>
                <h3 class="font-pixel text-[8px] text-crt-magenta mb-1.5 tracking-wider">TIPS</h3>
                <p class="text-[12px]">Hover over green links to preview the article before clicking. Look for broad topics like countries, dates, or people to bridge between unrelated subjects.</p>
              </div>
            </div>
          </div>
        </div>
      </transition>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useGame } from '../composables/useGame'
import { useSound } from '../composables/useSound'
import { useToast } from '../composables/useToast'
import { useAuth } from '../composables/useAuth'
import { useApi } from '../composables/useApi'
import { useTrending } from '../composables/useTrending'
import { useProgression } from '../composables/useProgression'
import { useAchievements } from '../composables/useAchievements'
import { useWikipedia } from '../composables/useWikipedia'
import WikiTitleInput from '../components/WikiTitleInput.vue'
import { APP_VERSION } from '../version.js'

const router = useRouter()
const toast = useToast()
const { GAME_MODES, GENRES, MODIFIERS, DIFFICULTIES, getStats, getHistory, getDailyStatus, LIMIT_BOUNDS } = useGame()
const sound = useSound()
const auth = useAuth()
const api = useApi()
const trending = useTrending()
const progression = useProgression()
const achievements = useAchievements()
const wiki = useWikipedia()

const showStats = ref(false)
const showAuthModal = ref(false)
const showHowTo = ref(false)
const statsTab = ref('modes')
const authMode = ref('login')
const authUsername = ref('')
const authPassword = ref('')
const authConfirmPassword = ref('')
const authError = ref('')
const showPassword = ref(false)
const showConfirmPassword = ref(false)
const globalStats = ref(null)
const dailyLeaderboard = ref([])
const showDailyLeaderboard = ref(false)

const selectedGenreId = ref('random')
const selectedModeId = ref('classic')
const selectedDifficulty = ref('normal')
const customTimeLimit = ref(120)
const customClickLimit = ref(6)
const customStartTitle = ref('')
const customEndTitle = ref('')
const activeModifiers = ref([])
const matchCode = ref('')
const showMatchModal = ref(false)
const matchTab = ref('create')
const joinCode = ref('')
const matchLoading = ref(false)
const matchError = ref('')
const matchStatus = ref(null)
let matchPollTimer = null

const genres = Object.values(GENRES)
const modifierList = Object.values(MODIFIERS)

function formatLimitMss(totalSec) {
  const s = Math.max(0, Math.floor(Number(totalSec) || 0))
  const m = Math.floor(s / 60)
  const r = s % 60
  return `${m}:${r.toString().padStart(2, '0')}`
}

const modePalette = {
  classic:   { uiColor: '#4c9fff', borderHex: 'rgba(76,159,255,0.35)',  bgGlow: 'rgba(76,159,255,0.1)',   shadowColor: 'rgba(76,159,255,0.2)',  tagBg: 'rgba(76,159,255,0.08)',  tagBorder: 'rgba(76,159,255,0.2)' },
  sprint:    { uiColor: '#ff6b2b', borderHex: 'rgba(255,107,43,0.35)',  bgGlow: 'rgba(255,107,43,0.1)',   shadowColor: 'rgba(255,107,43,0.2)',  tagBg: 'rgba(255,107,43,0.08)',  tagBorder: 'rgba(255,107,43,0.2)' },
  challenge: { uiColor: '#ff2ecc', borderHex: 'rgba(255,46,204,0.35)',  bgGlow: 'rgba(255,46,204,0.1)',   shadowColor: 'rgba(255,46,204,0.2)',  tagBg: 'rgba(255,46,204,0.08)',  tagBorder: 'rgba(255,46,204,0.2)' },
  trending:  { uiColor: '#ff5c3a', borderHex: 'rgba(255,92,58,0.35)',   bgGlow: 'rgba(255,92,58,0.1)',    shadowColor: 'rgba(255,92,58,0.2)',   tagBg: 'rgba(255,92,58,0.08)',   tagBorder: 'rgba(255,92,58,0.2)' },
  freeplay:  { uiColor: '#39ff14', borderHex: 'rgba(57,255,20,0.35)',   bgGlow: 'rgba(57,255,20,0.1)',    shadowColor: 'rgba(57,255,20,0.2)',   tagBg: 'rgba(57,255,20,0.08)',   tagBorder: 'rgba(57,255,20,0.2)' },
  custom:    { uiColor: '#c9a227', borderHex: 'rgba(201,162,39,0.35)',  bgGlow: 'rgba(201,162,39,0.1)',   shadowColor: 'rgba(201,162,39,0.2)',  tagBg: 'rgba(201,162,39,0.08)',  tagBorder: 'rgba(201,162,39,0.2)' },
}

const displayModes = Object.values(GAME_MODES)
  .filter(m => m.id !== 'daily')
  .map(m => {
    const p = modePalette[m.id] || modePalette.classic
    const tag = m.id === 'sprint' ? 'TIMER' : m.id === 'challenge' ? 'CLICK CAP' : m.id === 'trending' ? 'HOT' : null
    return { ...m, ...p, tag }
  })

const selectedGenre = computed(() => genres.find(g => g.id === selectedGenreId.value))
const selectedMode = computed(() => displayModes.find(m => m.id === selectedModeId.value))
const showDifficulty = computed(() => ['sprint', 'challenge'].includes(selectedModeId.value))
const showModifiers = computed(() => !['freeplay'].includes(selectedModeId.value))

function toggleModifier(id) {
  const idx = activeModifiers.value.indexOf(id)
  if (idx >= 0) activeModifiers.value.splice(idx, 1)
  else activeModifiers.value.push(id)
  sound.playClick()
}

const noBackModes = ['daily', 'challenge']

const filteredModifiers = computed(() => {
  return modifierList.filter(m => {
    if (m.id === 'speedDecay' && selectedModeId.value !== 'sprint') return false
    if (m.id === 'noback' && noBackModes.includes(selectedModeId.value)) return false
    return true
  })
})

const difficulties = computed(() => {
  const mode = selectedModeId.value
  return Object.values(DIFFICULTIES).map(d => {
    let detail = ''
    if (d.id === 'custom') { detail = mode === 'sprint' ? 'Your time' : mode === 'challenge' ? 'Your cap' : '' }
    else if (mode === 'sprint') { detail = formatLimitMss(d.sprintTime) }
    else if (mode === 'challenge') { detail = `${d.challengeClicks} links max` }
    const isCustom = d.id === 'custom'
    return {
      ...d, detail,
      color: isCustom ? '#c9a227' : d.id === 'easy' ? '#39ff14' : d.id === 'normal' ? '#ffbf00' : '#ff4444',
      bg: isCustom ? 'rgba(201,162,39,0.06)' : d.id === 'easy' ? 'rgba(57,255,20,0.06)' : d.id === 'normal' ? 'rgba(255,191,0,0.06)' : 'rgba(255,68,68,0.06)',
      shadow: isCustom ? 'rgba(201,162,39,0.1)' : d.id === 'easy' ? 'rgba(57,255,20,0.1)' : d.id === 'normal' ? 'rgba(255,191,0,0.1)' : 'rgba(255,68,68,0.1)',
    }
  })
})

const stats = computed(() => getStats())
const hasStats = computed(() => Object.keys(stats.value.modes || {}).length > 0)
const hasGenreStats = computed(() => Object.keys(stats.value.genres || {}).length > 0)
const history = computed(() => getHistory())
const dailyStatus = computed(() => getDailyStatus())
const dailyCompleted = computed(() => !!dailyStatus.value?.completed)

async function handleAuth() {
  authError.value = ''
  if (authMode.value === 'register' && authPassword.value !== authConfirmPassword.value) {
    authError.value = 'Passwords do not match.'
    return
  }
  const fn = authMode.value === 'login' ? auth.login : auth.register
  const err = await fn(authUsername.value, authPassword.value)
  if (err) { authError.value = err }
  else {
    showAuthModal.value = false
    authUsername.value = ''
    authPassword.value = ''
    authConfirmPassword.value = ''
    showPassword.value = false
    showConfirmPassword.value = false
  }
}

function shareDailyResult() {
  const ds = dailyStatus.value
  if (!ds) return
  const text = [
    `WikiLink Daily - ${new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}`,
    `${ds.clicks} clicks | ${formatTime(ds.time)}`,
    '',
    'https://wikilink.fraksis.com',
  ].join('\n')
  navigator.clipboard.writeText(text).then(() => toast.success('Result copied to clipboard!'))
    .catch(() => toast.warn('Could not copy to clipboard'))
}

function startGame() {
  if (selectedModeId.value === 'custom') {
    const a = customStartTitle.value.trim()
    const b = customEndTitle.value.trim()
    if (!a || !b) { toast.warn('Enter both start and target article titles.'); sound.playClick(); return }
    if (a.toLowerCase() === b.toLowerCase()) { toast.warn('Start and target must be different.'); sound.playClick(); return }
  }
  sound.playStart()
  const query = {}
  if (selectedModeId.value !== 'custom' && selectedModeId.value !== 'trending' && selectedGenreId.value !== 'random') query.genre = selectedGenreId.value
  if (showDifficulty.value) {
    if (selectedDifficulty.value !== 'normal') query.difficulty = selectedDifficulty.value
    if (selectedDifficulty.value === 'custom') {
      if (selectedModeId.value === 'sprint') query.timeLimit = String(Math.round(customTimeLimit.value) || LIMIT_BOUNDS.timeMin)
      if (selectedModeId.value === 'challenge') query.clickLimit = String(Math.round(customClickLimit.value) || LIMIT_BOUNDS.clicksMin)
    }
  }
  if (selectedModeId.value === 'custom') { query.from = customStartTitle.value.trim(); query.to = customEndTitle.value.trim() }
  if (activeModifiers.value.length > 0) query.modifiers = activeModifiers.value.join(',')
  router.push({ name: 'game', params: { mode: selectedModeId.value }, query })
}

function startDaily() { sound.playStart(); router.push({ name: 'game', params: { mode: 'daily' } }) }

async function createMatch() {
  if (!auth.user.value) { toast.warn('Login required for 1v1 matches'); showAuthModal.value = true; return }
  matchLoading.value = true
  matchError.value = ''
  try {
    const pair = await wiki.getRandomPairByGenre(genres.find(g => g.id === selectedGenreId.value))
    if (!pair) { matchError.value = 'Could not generate articles'; return }
    const result = await api.post('/match/create', { startTitle: pair.start.title, endTitle: pair.end.title })
    if (result.error) { matchError.value = result.error; return }
    matchCode.value = result.code
    matchStatus.value = 'waiting'
    matchTab.value = 'waiting'
    toast.success(`Match created! Code: ${result.code}`)
    startMatchPolling()
  } catch (e) {
    matchError.value = e.message || 'Failed to create match'
  } finally {
    matchLoading.value = false
  }
}

function startMatchPolling() {
  stopMatchPolling()
  matchPollTimer = setInterval(async () => {
    if (!matchCode.value) { stopMatchPolling(); return }
    try {
      const result = await api.get(`/match/${matchCode.value}`)
      if (result.status === 'active' || result.status === 'finished') {
        matchStatus.value = 'ready'
        stopMatchPolling()
        sound.playStart()
        toast.success('Opponent joined! Ready to start.')
      }
    } catch { /* ignore */ }
  }, 3000)
}

function stopMatchPolling() {
  if (matchPollTimer) { clearInterval(matchPollTimer); matchPollTimer = null }
}

async function joinMatch() {
  if (!auth.user.value) { toast.warn('Login required for 1v1 matches'); showAuthModal.value = true; return }
  const code = joinCode.value.trim().toUpperCase()
  if (!code || code.length < 4) { matchError.value = 'Enter a valid match code'; return }
  matchLoading.value = true
  matchError.value = ''
  try {
    const result = await api.post(`/match/join/${code}`)
    if (result.error) { matchError.value = result.error; return }
    sound.playStart()
    showMatchModal.value = false
    router.push({ name: 'game', params: { mode: 'custom' }, query: { from: result.start_title, to: result.end_title, match: code } })
  } catch (e) {
    matchError.value = e.message || 'Failed to join match'
  } finally {
    matchLoading.value = false
  }
}

function startCreatedMatch() {
  if (!matchCode.value || matchStatus.value !== 'ready') return
  sound.playStart()
  showMatchModal.value = false
  api.get(`/match/${matchCode.value}`).then(match => {
    router.push({ name: 'game', params: { mode: 'custom' }, query: { from: match.start_title, to: match.end_title, match: matchCode.value } })
  }).catch(() => toast.error('Failed to start match'))
}

function copyMatchCode() {
  if (!matchCode.value) return
  navigator.clipboard.writeText(matchCode.value).then(() => toast.success('Code copied!')).catch(() => toast.warn('Could not copy to clipboard'))
}

function formatTime(seconds) {
  if (seconds === null || seconds === undefined) return '-'
  const m = Math.floor(seconds / 60)
  const s = seconds % 60
  return `${m}:${s.toString().padStart(2, '0')}`
}

function formatDate(isoString) {
  if (!isoString) return ''
  const d = new Date(isoString)
  return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
}

function handleKeydown(e) {
  if (e.key === 'Enter' && !showStats.value && !showAuthModal.value && !showHowTo.value && !showMatchModal.value) startGame()
  if (e.key === 'Escape') {
    if (showMatchModal.value) showMatchModal.value = false
    else if (showStats.value) showStats.value = false
    else if (showAuthModal.value) showAuthModal.value = false
    else if (showHowTo.value) showHowTo.value = false
  }
}

watch(selectedModeId, (id) => {
  if (id === 'custom') selectedDifficulty.value = 'normal'
  if (noBackModes.includes(id)) activeModifiers.value = activeModifiers.value.filter(m => m !== 'noback')
  if (id !== 'sprint') activeModifiers.value = activeModifiers.value.filter(m => m !== 'speedDecay')
})
watch(showMatchModal, (val) => {
  if (!val) {
    stopMatchPolling()
    if (matchStatus.value !== 'ready') {
      matchCode.value = ''
      matchStatus.value = null
      matchTab.value = 'create'
      matchError.value = ''
    }
  }
})
watch(authMode, () => { authError.value = ''; authConfirmPassword.value = ''; showPassword.value = false; showConfirmPassword.value = false })

onMounted(() => {
  window.addEventListener('keydown', handleKeydown)
  api.get('/stats').then(d => globalStats.value = d).catch(() => {})
  api.get('/daily/leaderboard').then(d => dailyLeaderboard.value = d.scores || []).catch(() => {})
  trending.fetchTrending()
})
onUnmounted(() => {
  window.removeEventListener('keydown', handleKeydown)
  stopMatchPolling()
})
</script>
