<template>
  <div class="relative min-h-screen flex flex-col">

    <SiteTopNav
      :user="auth.user.value"
      :level="progression.level.value"
      :current-xp="progression.currentXp.value"
      :next-level-xp="progression.nextLevelXp.value"
      :xp-percent="progression.progress.value * 100"
      :show-xp-bar="true"
      :show-utility-controls="true"
      :muted="sound.muted.value"
      @open-howto="showHowTo = true"
      @toggle-mute="sound.toggleMute()"
      @login="showAuthModal = true"
      @logout="auth.logout()"
    />

    <!-- Main -->
    <main class="flex-1 flex flex-col items-center justify-center min-h-0 px-3 sm:px-4 py-3 sm:py-5 md:py-6 relative z-10">
      <div class="max-w-5xl w-full">

        <!-- Hero -->
        <section class="home-hero-shell mb-4 sm:mb-5 md:mb-6 animate-fade-in">
          <div class="home-hero-glow"></div>
          <div>
            <div class="home-hero-panel">
              <div class="inline-flex items-center gap-2 self-start rounded-full border border-crt-cyan/20 bg-crt-cyan/[0.05] px-3 py-1">
                <span class="h-1.5 w-1.5 rounded-full bg-crt-green animate-glow-pulse"></span>
                <span class="font-mono text-[9px] uppercase tracking-[0.24em] text-crt-cyan/80">Arcade Wiki Run</span>
              </div>

              <div class="home-hero-layout">
                <div class="home-hero-copy">
                  <div class="retro-divider max-w-[220px] sm:max-w-xs mb-4"></div>
                  <h1 class="font-pixel text-3xl sm:text-5xl md:text-6xl leading-tight tracking-wide">
                    <span class="text-neon-green">WIKI</span><span class="text-neon-cyan">LINK</span>
                  </h1>
                  <p class="mt-2 font-terminal text-sm sm:text-lg text-crt-white/55 tracking-[0.22em] uppercase">The Wiki Game</p>
                  <p class="mt-4 max-w-2xl font-mono text-[12px] sm:text-sm leading-6 text-retro-light/72">
                    Race from one Wikipedia page to another, test your routing instincts, and climb the boards with faster, smarter runs.
                  </p>

                  <div v-if="trending.trendingArticles.value.length > 0" class="home-strip-card mt-5">
                    <div class="flex items-center gap-2 mb-2">
                      <svg class="w-3.5 h-3.5 text-arcade-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z" />
                      </svg>
                      <span class="font-pixel text-[7px] text-arcade-orange tracking-[0.15em]">TRENDING NOW</span>
                    </div>
                    <div class="trending-ticker rounded-lg py-1.5" style="background: rgba(255,92,58,0.04); border: 1px solid rgba(255,92,58,0.15);">
                      <div class="trending-ticker-track">
                        <div class="trending-ticker-set">
                          <span v-for="(article, idx) in trending.trendingArticles.value.slice(0, 20)" :key="`hero-a-${idx}`"
                                class="trending-ticker-item font-mono text-[11px] text-retro-light/70">
                            {{ article.title }}
                            <span class="text-arcade-orange/60 ml-1">{{ trending.formatViews(article.views) }}</span>
                          </span>
                        </div>
                        <div class="trending-ticker-set">
                          <span v-for="(article, idx) in trending.trendingArticles.value.slice(0, 20)" :key="`hero-b-${idx}`"
                                class="trending-ticker-item font-mono text-[11px] text-retro-light/70">
                            {{ article.title }}
                            <span class="text-arcade-orange/60 ml-1">{{ trending.formatViews(article.views) }}</span>
                          </span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="home-hero-side">
                  <div class="flex flex-wrap gap-2.5">
                    <button
                      type="button"
                      @click="showHowTo = true"
                      class="home-hero-action"
                    >
                      <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8.228 9c.55-1.165 1.85-2 3.272-2 1.933 0 3.5 1.567 3.5 3.5 0 1.283-.69 2.404-1.718 3.014-.658.39-1.282.86-1.282 1.736V16m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                      <span>How To Play</span>
                    </button>
                    <router-link
                      v-if="auth.user.value"
                      :to="`/profile/${auth.user.value.username}`"
                      class="home-hero-action"
                    >
                      <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5.121 17.804A9 9 0 1118.88 17.8M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                      </svg>
                      <span>My Profile</span>
                    </router-link>
                    <button
                      v-else
                      type="button"
                      @click="showAuthModal = true"
                      class="home-hero-action"
                    >
                      <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12H3m0 0l4-4m-4 4l4 4m6 3h5a2 2 0 002-2V7a2 2 0 00-2-2h-5" />
                      </svg>
                      <span>Login</span>
                    </button>
                  </div>

                  <div v-if="globalStats" class="grid grid-cols-1 gap-2.5 sm:grid-cols-3 lg:grid-cols-1">
                    <div class="home-hero-stat">
                      <div class="font-terminal text-lg sm:text-xl text-crt-green tabular-nums">{{ globalStats.totalGames }}</div>
                      <div class="font-mono text-[9px] tracking-[0.24em] text-retro-muted uppercase">Games Played</div>
                    </div>
                    <div class="home-hero-stat">
                      <div class="font-terminal text-lg sm:text-xl text-crt-cyan tabular-nums">{{ globalStats.totalPlayers }}</div>
                      <div class="font-mono text-[9px] tracking-[0.24em] text-retro-muted uppercase">Players Joined</div>
                    </div>
                    <div class="home-hero-stat">
                      <div class="font-terminal text-lg sm:text-xl text-crt-amber tabular-nums">{{ globalStats.totalClicks }}</div>
                      <div class="font-mono text-[9px] tracking-[0.24em] text-retro-muted uppercase">Links Clicked</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>

        <section class="mb-4 sm:mb-5 animate-fade-in">
          <div class="home-section-header">
            <h2 class="home-section-title">Jump into the action</h2>
          </div>

          <div class="grid gap-3 sm:gap-4 lg:grid-cols-2">
            <section v-if="!dailyCompleted" class="animate-slide-up flex">
              <button @click="startDaily"
                      :disabled="hasActiveMultiplayerSession"
                      class="home-action-card home-action-card--amber w-full h-full text-left"
                      :class="{ 'opacity-50 cursor-not-allowed': hasActiveMultiplayerSession }">
                <div class="home-action-card__bar"></div>
                <div class="flex items-start justify-between gap-3">
                  <div class="flex items-start gap-3 min-w-0">
                    <div class="home-action-card__icon home-action-card__icon--amber">
                      <svg class="w-5 h-5 text-crt-amber" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                      </svg>
                    </div>
                    <div class="min-w-0">
                      <div class="flex flex-wrap items-center gap-2">
                        <span class="font-pixel text-[8px] text-crt-amber tracking-[0.15em]">DAILY CHALLENGE</span>
                        <span class="home-feature-chip">2x XP</span>
                      </div>
                      <p class="mt-1 font-terminal text-lg text-crt-white">One route for everyone today</p>
                      <p class="mt-1 font-mono text-[11px] text-retro-muted break-words">Same pair for every player. Fewer clicks win, and faster time breaks ties.</p>
                    </div>
                  </div>
                  <svg class="w-5 h-5 text-crt-amber/30 group-hover:text-crt-amber transition-all shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                  </svg>
                </div>
              </button>
            </section>
            <section v-else class="animate-slide-up flex">
              <div class="home-action-card home-action-card--green w-full h-full">
                <div class="home-action-card__bar"></div>
                <div class="flex items-start justify-between gap-3">
                  <div class="flex items-start gap-3 min-w-0">
                    <div class="home-action-card__icon home-action-card__icon--green">
                      <svg class="w-5 h-5 text-crt-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                    </div>
                    <div class="min-w-0">
                      <div class="font-pixel text-[8px] text-crt-green tracking-[0.15em]">DAILY COMPLETE</div>
                      <p class="mt-1 font-terminal text-lg text-crt-white">Your result is locked in</p>
                      <p class="mt-1 font-mono text-[11px] text-retro-muted break-words">
                        {{ dailyStatus.clicks }} clicks in {{ formatTime(dailyStatus.time) }}
                        <span v-if="auth.streak.value > 1" class="text-crt-amber ml-1 inline-flex items-center gap-0.5">
                          <svg class="w-3 h-3 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z" /></svg>
                          {{ auth.streak.value }}-day streak
                        </span>
                      </p>
                    </div>
                  </div>
                  <button @click.stop="shareDailyResult" class="btn-retro-ghost flex items-center gap-1.5 px-2.5 py-1.5 text-xs shrink-0" title="Share result">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                    </svg>
                    <span class="hidden sm:inline">SHARE</span>
                  </button>
                </div>
              </div>
            </section>

            <section class="animate-slide-up flex">
              <button @click="showMatchModal = true" class="home-action-card home-action-card--purple w-full h-full text-left">
                <div class="home-action-card__bar"></div>
                <div class="flex items-start justify-between gap-3">
                  <div class="flex items-start gap-3 min-w-0">
                    <div class="home-action-card__icon home-action-card__icon--purple">
                      <svg class="w-5 h-5 text-arcade-purple" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                      </svg>
                    </div>
                    <div class="min-w-0">
                      <div class="flex flex-wrap items-center gap-2">
                        <span class="font-pixel text-[8px] text-arcade-purple tracking-[0.15em]">PVP</span>
                        <span class="font-mono text-[9px] text-retro-muted uppercase tracking-[0.18em]">1v1 or group</span>
                      </div>
                      <p class="mt-1 font-terminal text-lg text-crt-white">Play against real people</p>
                      <p class="mt-1 font-mono text-[11px] text-retro-muted break-words">Challenge a friend, make a room, or host a lobby for up to 8 players.</p>
                    </div>
                  </div>
                  <svg class="w-5 h-5 text-arcade-purple/30 group-hover:text-arcade-purple transition-all shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                  </svg>
                </div>
              </button>
            </section>
          </div>
        </section>

        <!-- Daily Leaderboard -->
        <section v-if="dailyLeaderboard.length > 0" class="mb-3 sm:mb-4 animate-slide-up">
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
              <div class="px-4 py-2 border-b border-retro-border/20 space-y-2">
                <div class="font-mono text-[10px] text-retro-muted/80">
                  Ranking rule: fewer clicks rank higher; if tied, faster time wins.
                </div>
                <div class="flex flex-wrap items-center gap-2">
                  <div v-if="dailyTopEntry" class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md font-mono text-[10px]"
                       style="background: rgba(255,191,0,0.08); border: 1px solid rgba(255,191,0,0.25);">
                    <span class="text-arcade-gold">Top</span>
                    <span class="text-crt-white truncate max-w-[140px]">{{ dailyTopEntry.username }}</span>
                    <span class="text-crt-green tabular-nums">{{ dailyTopEntry.clicks }}</span>
                    <span class="text-retro-muted tabular-nums">{{ formatTime(dailyTopEntry.time) }}</span>
                  </div>
                  <div v-if="dailyMyEntry" class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md font-mono text-[10px]"
                       style="background: rgba(57,255,20,0.08); border: 1px solid rgba(57,255,20,0.25);">
                    <span class="text-crt-green">You</span>
                    <span class="text-retro-muted">#{{ dailyMyEntry.rank }}</span>
                    <span class="text-crt-green tabular-nums">{{ dailyMyEntry.clicks }}</span>
                    <span class="text-retro-muted tabular-nums">{{ formatTime(dailyMyEntry.time) }}</span>
                  </div>
                </div>
              </div>
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
                  <span class="w-7 text-right shrink-0 flex items-center justify-end">
                    <template v-if="entry.rank === 1">
                      <span class="inline-flex items-center gap-0.5">
                        <svg class="w-4 h-4 text-arcade-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M4 18h16l-1-8-4 3-3-5-3 5-4-3-1 8z" />
                        </svg>
                        <span class="font-mono text-[10px] text-arcade-gold">1</span>
                      </span>
                    </template>
                    <template v-else-if="entry.rank === 2">
                      <span class="font-mono text-[10px] text-retro-light">2</span>
                    </template>
                    <template v-else-if="entry.rank === 3">
                      <span class="font-mono text-[10px] text-arcade-orange">3</span>
                    </template>
                    <span v-else class="font-terminal text-sm text-retro-muted">{{ entry.rank }}</span>
                  </span>
                  <router-link :to="`/profile/${entry.username}`" class="flex-1 min-w-0">
                    <div class="rounded-md px-2 py-1" :style="leaderboardNameplateStyle(entry.profile_nameplate_border, entry.profile_accent)">
                    <div class="font-mono text-[12px] truncate"
                         :class="auth.user.value && entry.username === auth.user.value.username ? 'text-crt-green' : 'text-crt-white'">
                      {{ entry.username }}
                      <span v-if="auth.user.value && entry.username === auth.user.value.username" class="text-[9px] text-crt-green/50 ml-1">(you)</span>
                      <span v-if="entry.profile_pinned_badge" class="inline-flex items-center gap-1 ml-1.5 px-1 py-0.5 rounded text-[8px] align-middle"
                            style="background: rgba(255,191,0,0.08); border: 1px solid rgba(255,191,0,0.25); color: #ffbf00;">
                        <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" v-html="pinnedBadgeSvgPath(entry.profile_pinned_badge)"></svg>
                        <span class="max-w-[80px] truncate">{{ formatPinnedBadge(entry.profile_pinned_badge) }}</span>
                      </span>
                    </div>
                    <div class="font-pixel text-[6px] tracking-wider text-retro-muted truncate">{{ formatProfileTitle(entry.profile_title) }}</div>
                    </div>
                  </router-link>
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
        </section>

        <section v-if="resumeMultiplayerSession" class="mb-3 sm:mb-4 animate-slide-up">
          <button @click="resumeMultiplayer"
                  class="w-full flex items-center justify-between gap-3 px-4 py-3 rounded-xl transition-all duration-300 group touch-manipulation relative overflow-hidden"
                  style="background: linear-gradient(135deg, rgba(0,229,255,0.06), rgba(57,255,20,0.04)); border: 1.5px solid rgba(0,229,255,0.22);">
            <div class="absolute top-0 left-0 right-0 h-[1px]" style="background: linear-gradient(90deg, transparent, rgba(0,229,255,0.3), transparent);"></div>
            <div class="flex items-center gap-3 min-w-0">
              <div class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0"
                   style="background: rgba(0,229,255,0.08); border: 1px solid rgba(0,229,255,0.18);">
                <svg class="w-5 h-5 text-crt-cyan" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6l4 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <div class="text-left min-w-0">
                <div class="flex items-center gap-2">
                  <span class="font-pixel text-[8px] text-crt-cyan tracking-[0.15em]">RESUME MULTIPLAYER</span>
                  <span class="font-mono text-[9px] px-1.5 py-0.5 rounded" style="color: #39ff14; background: rgba(57,255,20,0.08); border: 1px solid rgba(57,255,20,0.2);">
                    {{ resumeMultiplayerSession.type === 'lobby' ? 'GROUP' : 'DUEL' }}
                  </span>
                </div>
                <p class="font-mono text-[10px] sm:text-[11px] text-retro-muted mt-0.5 break-words">
                  Code: {{ resumeMultiplayerSession.code }}
                </p>
                <p v-if="resumeSprintRemaining !== null" class="font-mono text-[10px] sm:text-[11px] text-crt-amber mt-0.5">
                  Sprint left: {{ formatTime(resumeSprintRemaining) }}
                </p>
              </div>
            </div>
            <svg class="w-5 h-5 text-crt-cyan/30 group-hover:text-crt-cyan group-hover:translate-x-0.5 transition-all shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </button>
        </section>

        <!-- Display frame (game config) -->
        <section class="mb-4 sm:mb-5 animate-slide-up">
          <div class="home-section-header">
            <h2 class="home-section-title">Build your next round</h2>
          </div>
        </section>

        <!-- Display frame (game config) -->
        <section class="relative rounded-xl sm:rounded-2xl overflow-hidden animate-slide-up"
             style="background: linear-gradient(180deg, #0d0e15, #0a0b11); border: 1.5px solid rgba(37,39,56,0.7); box-shadow: 0 1px 0 0 rgba(0,229,255,0.04), inset 0 0 60px rgba(0,0,0,0.4), 0 12px 40px rgba(0,0,0,0.5);">

          <!-- Top decorative bar -->
          <header class="relative px-3 sm:px-5 py-2.5 sm:py-3 border-b border-retro-border/40 flex items-center justify-between gap-2"
               style="background: linear-gradient(180deg, rgba(22,23,36,0.9), rgba(18,19,28,0.9));">
            <div class="flex items-center gap-1.5 sm:gap-2 shrink-0">
              <span class="w-2.5 h-2.5 sm:w-3 sm:h-3 rounded-full transition-colors" style="background: rgba(255,68,68,0.4); box-shadow: inset 0 -1px 2px rgba(0,0,0,0.3);"></span>
              <span class="w-2.5 h-2.5 sm:w-3 sm:h-3 rounded-full transition-colors" style="background: rgba(255,191,0,0.4); box-shadow: inset 0 -1px 2px rgba(0,0,0,0.3);"></span>
              <span class="w-2.5 h-2.5 sm:w-3 sm:h-3 rounded-full transition-colors" style="background: rgba(57,255,20,0.4); box-shadow: inset 0 -1px 2px rgba(0,0,0,0.3);"></span>
            </div>
            <span class="font-pixel text-[6px] sm:text-[7px] text-retro-muted/70 tracking-[0.2em] sm:tracking-[0.3em] truncate text-center flex-1 min-w-0 px-1">CREATE ROUND</span>
            <div class="flex items-center gap-1.5 sm:gap-2 text-retro-muted shrink-0">
              <span class="w-2 h-2 rounded-full bg-crt-green/50 animate-glow-pulse" style="box-shadow: 0 0 6px rgba(57,255,20,0.3);"></span>
              <span class="font-mono text-[9px] sm:text-[10px] text-crt-green/60">READY</span>
            </div>
          </header>

          <!-- Genre section (hidden for Custom) -->
          <div v-if="selectedModeId !== 'custom'" class="p-3 sm:p-4 md:p-5">
            <div class="flex items-center gap-2.5 mb-2.5 sm:mb-3">
              <div class="w-1 h-4 rounded-full bg-crt-amber"></div>
              <span class="font-pixel text-[8px] text-crt-amber tracking-[0.2em]">PICK CATEGORY</span>
            </div>
            <div class="flex flex-wrap gap-1.5 sm:gap-2">
              <button v-for="g in genres" :key="g.id"
                @click="selectedGenreId = g.id; sound.playClick()"
                class="flex items-center gap-1.5 sm:gap-2 px-2.5 sm:px-3 py-1.5 sm:py-2 rounded-lg transition-all duration-200 cursor-pointer touch-manipulation active:scale-95"
                :style="selectedGenreId === g.id
                  ? 'border: 1.5px solid rgba(57,255,20,0.5); background: rgba(57,255,20,0.05); box-shadow: 0 0 10px rgba(57,255,20,0.12), inset 0 0 8px rgba(57,255,20,0.04);'
                  : 'border: 1.5px solid #252738;'">
                <svg class="w-4 h-4 sm:w-4.5 sm:h-4.5 shrink-0" :class="selectedGenreId === g.id ? 'text-crt-green' : 'text-retro-muted/60'" :style="selectedGenreId === g.id ? 'filter: drop-shadow(0 0 6px rgba(57,255,20,0.5))' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" v-html="g.svgIcon"></svg>
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
              <span class="font-pixel text-[8px] text-crt-cyan tracking-[0.2em]">PICK GAME TYPE</span>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2 sm:gap-3">
              <button v-for="mode in displayModes" :key="mode.id"
                @click="selectedModeId = mode.id; sound.playClick()"
                class="mode-card w-full text-left flex items-center gap-2.5 sm:gap-3.5 p-2.5 sm:p-3.5 touch-manipulation active:opacity-90"
                :class="{ selected: selectedModeId === mode.id }"
                :style="selectedModeId === mode.id ? `--mode-color: ${mode.uiColor}; border-color: ${mode.borderHex}` : ''">
                <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-lg flex items-center justify-center shrink-0 transition-all duration-200"
                     :style="selectedModeId === mode.id ? `background: ${mode.bgGlow}; box-shadow: 0 0 12px ${mode.shadowColor};` : 'background: #181a25; border: 1px solid #252738;'">
                  <svg class="w-5 h-5" :style="selectedModeId === mode.id ? `color: ${mode.uiColor}` : 'color: #555770'" fill="none" stroke="currentColor" viewBox="0 0 24 24" v-html="mode.svgIcon"></svg>
                </div>
                <div class="min-w-0 flex-1">
                  <div class="flex items-center gap-2 mb-0.5">
                    <span class="font-pixel text-[7px] sm:text-[8px] tracking-wider transition-colors"
                          :style="selectedModeId === mode.id ? `color: ${mode.uiColor}` : 'color: #d4d4e8'">{{ mode.name.toUpperCase() }}</span>
                    <span v-if="mode.tag" class="font-mono text-[9px] sm:text-[10px] px-1.5 py-0.5 rounded"
                          :style="`color: ${mode.uiColor}; background: ${mode.tagBg}; border: 1px solid ${mode.tagBorder};`">{{ mode.tag }}</span>
                  </div>
                  <p class="font-mono text-[10px] sm:text-[11px] text-retro-muted leading-snug break-words">{{ mode.description }}</p>
                </div>
                <div v-if="selectedModeId === mode.id" class="animate-blink shrink-0" :style="`color: ${mode.uiColor}`">
                  <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M8 5v14l11-7z" />
                  </svg>
                </div>
              </button>
            </div>
          </div>

          <!-- Difficulty selector (only for sprint / challenge) -->
          <template v-if="showDifficulty">
            <div class="mx-3 sm:mx-5"><div class="retro-divider"></div></div>
            <div class="p-3 sm:p-4 md:p-5">
              <div class="flex items-center gap-2.5 mb-2.5 sm:mb-3">
                <div class="w-1 h-4 rounded-full bg-crt-magenta"></div>
                <span class="font-pixel text-[8px] text-crt-magenta tracking-[0.2em]">SET DIFFICULTY</span>
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
                  <div class="font-mono text-[8px] mt-0.5" style="color: #39ff14;">~{{ d.estimatedXp }} XP ({{ d.totalMult }}x)</div>
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
                <span class="font-pixel text-[8px] text-crt-amber tracking-[0.2em]">OPTIONAL MODIFIERS</span>
                <span v-if="activeModifiers.length > 0" class="font-mono text-[9px] text-crt-green ml-1">{{ (1.25 ** activeModifiers.length).toFixed(2) }}x XP</span>
              </div>
              <div class="flex flex-wrap gap-1.5 sm:gap-2">
                <button v-for="mod in soloModifiers" :key="mod.id"
                  @click="toggleModifier(mod.id)"
                  class="modifier-chip"
                  :class="{ active: activeModifiers.includes(mod.id) }"
                  :title="mod.description">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" v-html="modifierSvgPath(mod.id)"></svg>
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
                <span class="font-pixel text-[8px] text-crt-amber tracking-[0.2em]">CHOOSE ARTICLES</span>
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
          <div class="p-4 sm:p-5 md:p-6">
            <div class="setup-summary-grid">
              <div class="setup-summary-panel">
                <div class="font-pixel text-[6px] sm:text-[7px] text-crt-cyan tracking-[0.15em] mb-1.5">ROUND SUMMARY</div>
                <div class="setup-summary-items">
                  <div class="setup-summary-item">
                    <span class="setup-summary-label">Mode</span>
                    <span class="setup-summary-value text-crt-cyan">{{ selectedMode?.name || 'Unknown' }}</span>
                  </div>
                  <div class="setup-summary-item">
                    <span class="setup-summary-label">Objective</span>
                    <span class="setup-summary-value text-crt-white">{{ modeGoalText }}</span>
                  </div>
                  <div class="setup-summary-item">
                    <span class="setup-summary-label">Limit</span>
                    <span class="setup-summary-value text-crt-amber">{{ selectedLimitText }}</span>
                  </div>
                  <div class="setup-summary-item">
                    <span class="setup-summary-label">Modifiers</span>
                    <span class="setup-summary-value text-crt-green">{{ modifierSummaryText }}</span>
                  </div>
                </div>
              </div>

              <div class="setup-launch-panel">
                <div class="setup-launch-head">
                  <div>
                    <div class="font-pixel text-[6px] sm:text-[7px] text-crt-green tracking-[0.15em]">LAUNCH READY</div>
                    <p class="mt-1.5 font-terminal text-lg sm:text-xl text-crt-white">Start when the setup looks right</p>
                  </div>
                  <div v-if="xpEstimate.multiplier > 0" class="setup-xp-chip">
                    <span class="font-terminal text-xs sm:text-sm text-crt-green">~{{ xpEstimate.total }} XP</span>
                    <span v-if="xpEstimate.multiplier !== 1" class="setup-xp-multiplier">{{ xpEstimate.multiplier }}x</span>
                  </div>
                </div>

                <p class="font-mono text-[10px] sm:text-[11px] leading-4 sm:leading-5 text-retro-muted">
                  {{ hasActiveMultiplayerSession ? 'Finish or leave the current multiplayer session before starting a solo round.' : 'Your selected mode, limits, and modifiers are locked in when you press start.' }}
                </p>

                <div v-if="xpEstimate.multiplier <= 0" class="rounded-lg px-3 py-1.5 flex items-center justify-center gap-2"
                     style="background: rgba(85,87,112,0.04); border: 1px solid rgba(85,87,112,0.15);">
                  <span class="font-mono text-[9px] sm:text-[10px] text-retro-muted/50">No XP in this mode</span>
                </div>

                <button
                  type="button"
                  @click="startGame"
                  :disabled="hasActiveMultiplayerSession"
                  class="btn-retro-primary w-full min-h-[44px] sm:min-h-[48px] touch-manipulation text-[10px] sm:text-[11px] disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  {{ hasActiveMultiplayerSession ? 'LEAVE MULTIPLAYER TO START' : 'START GAME' }}
                </button>

                <div class="mt-1 flex items-center justify-center gap-2.5">
                  <span class="w-1.5 h-1.5 rounded-full bg-crt-green/40 animate-blink"></span>
                  <p class="font-pixel text-[6px] sm:text-[7px] text-retro-muted/40 tracking-[0.3em] animate-blink-slow">
                    PRESS START TO BEGIN
                  </p>
                  <span class="w-1.5 h-1.5 rounded-full bg-crt-green/40 animate-blink"></span>
                </div>
              </div>
            </div>
          </div>

          <!-- Bottom status bar -->
          <div class="px-3 sm:px-5 py-2.5 sm:py-3 border-t border-retro-border/30 flex items-center justify-between gap-2"
               style="background: linear-gradient(180deg, rgba(18,19,28,0.8), rgba(13,14,21,0.9));">
            <div class="font-mono text-[10px] sm:text-[11px] text-retro-muted truncate min-w-0 flex items-center gap-1.5">
              <span class="w-1 h-1 rounded-full bg-crt-amber/50"></span>
              <span class="text-crt-amber">{{ selectedModeId === 'custom' ? '---' : selectedGenre?.name }}</span>
              <span class="text-retro-border/50">/</span>
              <span class="text-crt-cyan">{{ selectedMode?.name }}</span>
              <span v-if="showDifficulty" class="text-crt-magenta ml-0.5">[{{ selectedDifficulty.toUpperCase() }}]</span>
            </div>
            <div class="font-mono text-[9px] sm:text-[10px] text-retro-muted/30 shrink-0">
              {{ genres.length }} GENRES &middot; {{ displayModes.length }} MODES
            </div>
          </div>
        </section>

      </div>
    </main>

    <!-- Footer -->
    <footer class="relative z-10 py-3 sm:py-4 border-t border-retro-border/15 shrink-0">
      <div class="max-w-6xl mx-auto px-4 sm:px-5 flex flex-col-reverse gap-1.5 sm:flex-row sm:items-center sm:justify-between sm:gap-0">
        <span class="font-mono text-[9px] sm:text-[10px] text-retro-muted/30 tracking-wider break-words">POWERED BY WIKIPEDIA API</span>
        <span class="font-mono text-[9px] sm:text-[10px] text-retro-muted/30 sm:text-right">v{{ APP_VERSION }}</span>
      </div>
    </footer>

    <HomeStatsModal
      :open="showStats"
      :tab="statsTab"
      :has-stats="hasStats"
      :has-genre-stats="hasGenreStats"
      :stats="stats"
      :game-modes="GAME_MODES"
      :genres="GENRES"
      :achievements="achievements"
      :achievement-svg-path="achievementSvgPath"
      :format-time="formatTime"
      @close="showStats = false"
      @set-tab="statsTab = $event"
    />

    <HomeAuthModal
      :open="showAuthModal"
      :mode="authMode"
      :username="authUsername"
      :password="authPassword"
      :confirm-password="authConfirmPassword"
      :show-password="showPassword"
      :show-confirm-password="showConfirmPassword"
      :error="authError"
      :loading="auth.loading.value"
      @close="showAuthModal = false"
      @set-mode="authMode = $event"
      @submit="handleAuth"
      @update:username="authUsername = $event"
      @update:password="authPassword = $event"
      @update:confirm-password="authConfirmPassword = $event"
      @toggle-password="showPassword = !showPassword"
      @toggle-confirm-password="showConfirmPassword = !showConfirmPassword"
    />

    <HomePvpModal
      :open="showMatchModal"
      :match-tab="matchTab"
      :group-view="groupView"
      :group-lobby-code="groupLobbyCode"
      :group-lobby-data="displayGroupLobbyData"
      :group-can-start="groupCanStart"
      :current-user-id="auth.user.value?.id ?? null"
      :group-start-loading="groupStartLoading"
      :group-error="groupError"
      :match-status="matchStatus"
      :match-code="matchCode"
      :joined-match-code="joinedMatchCode"
      :match-opponent-username="matchOpponentUsername"
      :match-loading="matchLoading"
      :group-max-players="groupMaxPlayers"
      :group-loading="groupLoading"
      :join-room-code="joinRoomCode"
      :join-busy="joinBusy"
      :unified-join-error="unifiedJoinError"
      :show-leave-current-room-button="showLeaveCurrentRoomButton"
      :room-settings="editableRoomSettings"
      :can-edit-room-settings="canEditRoomSettings"
      :room-settings-saving="roomSettingsSaving"
      :room-genres="genres.map(g => ({ id: g.id, name: g.shortName }))"
      :room-modes="displayModes.filter(m => ['custom','classic','sprint','challenge'].includes(m.id)).map(m => ({ id: m.id, name: m.name }))"
      :room-modifiers="filteredModifiers.map(m => ({ id: m.id, name: m.name, description: m.description || '' }))"
      :friends="friendsComposable.friends.value"
      :invite-busy="multiplayerInviteLoading"
      :invite-error="multiplayerInviteError"
      :can-invite-friends="canInviteFriends"
      @close="closeMatchModal"
      @copy-group-code="copyGroupLobbyCode"
      @start-group="postStartGroupLobby"
      @copy-match-code="copyMatchCode"
      @start-created-match="startCreatedMatch"
      @switch-hub="switchPvpHub"
      @create-match="createMatch"
      @create-group-lobby="createGroupLobby"
      @update:group-max-players="groupMaxPlayers = $event"
      @update:join-room-code="joinRoomCode = $event"
      @join-by-code="joinByCode"
      @leave-current-room="leaveCurrentMultiplayerRoom"
      @update-room-settings="updateRoomSettingsDraft"
      @toggle-room-modifier="toggleRoomModifier"
      @save-room-settings="saveRoomSettings"
      @invite-friend-to-match="inviteFriendToCurrentMatch"
      @invite-friend-to-group="inviteFriendToCurrentLobby"
    />

    <HomeHowToModal :open="showHowTo" @close="showHowTo = false" />
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useGame } from '../composables/useGame'
import { useSound } from '../composables/useSound'
import { useToast } from '../composables/useToast'
import { useAuth } from '../composables/useAuth'
import { useApi } from '../composables/useApi'
import { useTrending } from '../composables/useTrending'
import { useProgression } from '../composables/useProgression'
import { useAchievements } from '../composables/useAchievements'
import { useWikipedia } from '../composables/useWikipedia'
import { useFriends } from '../composables/useFriends'
import WikiTitleInput from '../components/WikiTitleInput.vue'
import SiteTopNav from '../components/layout/SiteTopNav.vue'
import HomeAuthModal from '../components/home/HomeAuthModal.vue'
import HomeHowToModal from '../components/home/HomeHowToModal.vue'
import HomeStatsModal from '../components/home/HomeStatsModal.vue'
import HomePvpModal from '../components/home/HomePvpModal.vue'
import { APP_VERSION } from '../version.js'
import { loadMultiplayerSession, buildSessionRoute, clearMultiplayerSession } from '../utils/multiplayerSession'

const router = useRouter()
const route = useRoute()
const toast = useToast()
const { GAME_MODES, GENRES, MODIFIERS, DIFFICULTIES, getStats, getDailyStatus, LIMIT_BOUNDS } = useGame()
const sound = useSound()
const auth = useAuth()
const api = useApi()
const trending = useTrending()
const progression = useProgression()
const achievements = useAchievements()
const wiki = useWikipedia()
const friendsComposable = useFriends()

// --------------------
// UI / Modal State
// --------------------
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

// --------------------
// Home: Stats + Daily
// --------------------
const globalStats = ref(null)
const dailyLeaderboard = ref([])
const showDailyLeaderboard = ref(false)
const dailyTopEntry = computed(() => dailyLeaderboard.value[0] || null)
const dailyMyEntry = computed(() => {
  const username = auth.user.value?.username
  if (!username) return null
  return dailyLeaderboard.value.find(entry => entry.username === username) || null
})

// --------------------
// Multiplayer: Resume
// --------------------
const resumeMultiplayerSession = ref(null)
const resumeNowMs = ref(Date.now())
let resumeTicker = null

// --------------------
// Solo Round Configuration
// --------------------
const selectedGenreId = ref('random')
const selectedModeId = ref('classic')
const selectedDifficulty = ref('normal')
const customTimeLimit = ref(120)
const customClickLimit = ref(6)
const customStartTitle = ref('')
const customEndTitle = ref('')
const activeModifiers = ref([])

// --------------------
// Multiplayer: 1v1 (duel)
// --------------------
const matchCode = ref('')
const showMatchModal = ref(false)
const matchTab = ref('duel')
const joinRoomCode = ref('')
const matchLoading = ref(false)
const matchError = ref('')
const matchStatus = ref(null)
const matchOpponentUsername = ref('')
const replayMatchWaitingMode = ref(false)
const replayLobbyWaitingMode = ref(false)
const replayMatchSawOpponentMissing = ref(false)
const joinedMatchCode = ref('')
const matchOpponentLeftNotified = ref(false)
let matchPollTimer = null
let handlingInviteJoin = false
let handlingHostMatch = false
const hostInviteId = ref(null)
const hostInviteUser = ref('')

// --------------------
// Multiplayer: Group Lobby (up to 8)
// --------------------
const groupView = ref('menu')
const groupLobbyCode = ref('')
const groupLobbyData = ref(null)
const groupMaxPlayers = ref(8)
const groupLoading = ref(false)
const groupStartLoading = ref(false)
const groupError = ref('')
let groupPollTimer = null

// Room settings draft (used by host editing before saving)
function createDefaultRoomSettings() {
  return {
    mode: 'classic',
    genre: 'random',
    modifiers: [],
    timeLimit: 120,
    clickLimit: 6,
    customStartTitle: '',
    customEndTitle: '',
  }
}

const editableRoomSettings = ref(createDefaultRoomSettings())
const roomSettingsSaving = ref(false)
const roomSettingsDirty = ref(false)
const lastSeenMatchSettingsSignature = ref('')
const lastSeenLobbySettingsSignature = ref('')
let roomSettingsAutosaveTimer = null
const roomSettingsAutosaveRetry = ref(false)
const multiplayerInviteLoading = ref(false)
const multiplayerInviteError = ref('')
const suppressModalFriendInvites = ref(false)
const MULTIPLAYER_WAITING_POLL_MS = 300
const ROOM_SETTINGS_AUTOSAVE_DELAY_MS = 250

const genres = Object.values(GENRES)
const modifierList = Object.values(MODIFIERS)

const MODIFIER_SVG_PATHS = {
  fog: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />',
  blackout: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />',
  noback: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />',
  speedDecay: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />',
}
function modifierSvgPath(modId) {
  return MODIFIER_SVG_PATHS[modId] || MODIFIER_SVG_PATHS.fog
}

const ACHIEVEMENT_CATEGORY_SVG = {
  speed: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z" />',
  efficiency: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />',
  milestone: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />',
  dedication: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z" />',
  explorer: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />',
  mode: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />',
  special: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />',
}
function achievementSvgPath(category) {
  return ACHIEVEMENT_CATEGORY_SVG[category] || ACHIEVEMENT_CATEGORY_SVG.milestone
}

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
    const mx = progression.MODE_XP[m.id]
    let xpTag = null
    let xpTagStyle = ''
    if (mx) {
      if (mx.multiplier === 0) {
        xpTag = 'NO XP'
        xpTagStyle = 'color: #555770; background: rgba(85,87,112,0.08); border: 1px solid rgba(85,87,112,0.2);'
      } else if (mx.multiplier > 1) {
        xpTag = `${mx.multiplier}x XP`
        xpTagStyle = 'color: #39ff14; background: rgba(57,255,20,0.08); border: 1px solid rgba(57,255,20,0.2);'
      } else {
        xpTag = `${mx.multiplier}x XP`
        xpTagStyle = 'color: #00e5ff; background: rgba(0,229,255,0.06); border: 1px solid rgba(0,229,255,0.15);'
      }
    }
    return { ...m, ...p, tag, xpTag, xpTagStyle }
  })

const selectedGenre = computed(() => genres.find(g => g.id === selectedGenreId.value))
const selectedMode = computed(() => displayModes.find(m => m.id === selectedModeId.value))
const showDifficulty = computed(() => ['sprint', 'challenge'].includes(selectedModeId.value))
const showModifiers = computed(() => !['freeplay'].includes(selectedModeId.value))
const modeGoalText = computed(() => {
  if (selectedModeId.value === 'freeplay') return 'Explore freely'
  if (selectedModeId.value === 'custom') return 'Reach your custom target'
  if (selectedModeId.value === 'sprint') return 'Reach target before time runs out'
  if (selectedModeId.value === 'challenge') return 'Reach target within click cap'
  return 'Reach target in few clicks'
})
const selectedLimitText = computed(() => {
  if (selectedModeId.value === 'sprint') {
    const sec = selectedDifficulty.value === 'custom'
      ? Math.round(customTimeLimit.value) || LIMIT_BOUNDS.timeMin
      : (DIFFICULTIES[selectedDifficulty.value]?.sprintTime || DIFFICULTIES.normal.sprintTime)
    return `${formatLimitMss(sec)} timer`
  }
  if (selectedModeId.value === 'challenge') {
    const clicks = selectedDifficulty.value === 'custom'
      ? Math.round(customClickLimit.value) || LIMIT_BOUNDS.clicksMin
      : (DIFFICULTIES[selectedDifficulty.value]?.challengeClicks || DIFFICULTIES.normal.challengeClicks)
    return `${clicks} clicks max`
  }
  return 'No hard limit'
})
const modifierSummaryText = computed(() => {
  if (!showModifiers.value) return 'Not available'
  if (activeModifiers.value.length === 0) return 'None'
  return `${activeModifiers.value.length} active`
})

const xpEstimate = computed(() => {
  const diff = showDifficulty.value ? selectedDifficulty.value : 'normal'
  return progression.estimateXp(selectedModeId.value, diff, activeModifiers.value.length)
})
const joinBusy = computed(() => matchLoading.value || groupLoading.value)
const unifiedJoinError = computed(() => matchError.value || groupError.value || '')
const showLeaveCurrentRoomButton = computed(() =>
  unifiedJoinError.value.toLowerCase().includes('already in another')
)
const hasActiveMultiplayerSession = computed(() => !!resumeMultiplayerSession.value)
const resumeSprintRemaining = computed(() => {
  const saved = resumeMultiplayerSession.value
  const snap = saved?.snapshot
  if (!saved || !snap) return null
  if (snap.mode !== 'sprint') return null
  if (!Number.isFinite(snap.effectiveTimeLimit)) return null
  const baseElapsed = Number.isFinite(snap.elapsed) ? snap.elapsed : 0
  const savedAt = Number.isFinite(snap.savedAt)
    ? snap.savedAt
    : (Number.isFinite(saved.updatedAt) ? saved.updatedAt : null)
  const ageSec = (snap.status === 'playing' && savedAt != null)
    ? Math.max(0, Math.floor((resumeNowMs.value - savedAt) / 1000))
    : 0
  const speedPenalty = Number.isFinite(snap.speedDecayPenalty) ? snap.speedDecayPenalty : 0
  const elapsed = baseElapsed + ageSec
  return Math.max(0, snap.effectiveTimeLimit - elapsed - speedPenalty)
})
const canEditRoomSettings = computed(() => {
  if (matchTab.value === 'group') return !!groupLobbyData.value?.can_edit_settings
  const isHostRoom = !!matchCode.value
  const isStillWaiting = matchStatus.value === 'waiting' || matchStatus.value === 'ready'
  return isHostRoom && isStillWaiting
})
const replayReadyPlayers = computed(() => {
  const players = Array.isArray(groupLobbyData.value?.players) ? groupLobbyData.value.players : []
  return players.filter(p => !!p?.replay_ready)
})
const displayGroupLobbyData = computed(() => {
  const lobby = groupLobbyData.value
  if (!lobby) return null
  if (!replayLobbyWaitingMode.value) return lobby
  return {
    ...lobby,
    players: replayReadyPlayers.value,
  }
})
const groupCanStart = computed(() => {
  const lobby = groupLobbyData.value
  if (!lobby || !lobby.is_host || lobby.status !== 'waiting') return false
  if (replayLobbyWaitingMode.value) return replayReadyPlayers.value.length >= 2
  const totalPlayers = Array.isArray(lobby.players) ? lobby.players.length : 0
  return totalPlayers >= 2
})
const canInviteFriends = computed(() =>
  !suppressModalFriendInvites.value &&
  !!auth.user.value &&
  friendsComposable.friends.value.length > 0
)

function toggleModifier(id) {
  const idx = activeModifiers.value.indexOf(id)
  if (idx >= 0) activeModifiers.value.splice(idx, 1)
  else activeModifiers.value.push(id)
  sound.playClick()
}

function toMultiplayerSettingsPayload() {
  return {
    mode: editableRoomSettings.value.mode || selectedModeId.value,
    genre: editableRoomSettings.value.genre || selectedGenreId.value,
    modifiers: Array.isArray(editableRoomSettings.value.modifiers)
      ? [...editableRoomSettings.value.modifiers]
      : [...activeModifiers.value],
    timeLimit: Number(editableRoomSettings.value.timeLimit) || 120,
    clickLimit: Number(editableRoomSettings.value.clickLimit) || 6,
    customStartTitle: (editableRoomSettings.value.customStartTitle || '').trim(),
    customEndTitle: (editableRoomSettings.value.customEndTitle || '').trim(),
  }
}

function hydrateRoomSettingsFromResult(result) {
  const raw = result?.settings || {}
  editableRoomSettings.value = {
    mode: raw.mode || 'classic',
    genre: raw.genre || 'random',
    modifiers: Array.isArray(raw.modifiers) ? [...raw.modifiers] : [],
    timeLimit: raw.timeLimit ?? 120,
    clickLimit: raw.clickLimit ?? 6,
    customStartTitle: raw.customStartTitle || result?.start_title || '',
    customEndTitle: raw.customEndTitle || result?.end_title || '',
  }
  roomSettingsDirty.value = false
}

function updateRoomSettingsDraft(patch) {
  editableRoomSettings.value = {
    ...editableRoomSettings.value,
    ...patch,
  }
  roomSettingsDirty.value = true
  const isCustomMode = editableRoomSettings.value.mode === 'custom'
  if (isCustomMode) {
    const start = String(editableRoomSettings.value.customStartTitle || '').trim()
    const end = String(editableRoomSettings.value.customEndTitle || '').trim()
    // While user is editing custom titles, avoid autosaving partial/empty values
    // that would be normalized back to the previous server value and feel "sticky".
    if (!start || !end || start.toLowerCase() === end.toLowerCase()) return
  }
  scheduleRoomSettingsAutosave()
}

function toggleRoomModifier(modId) {
  if (!canEditRoomSettings.value) return
  const current = Array.isArray(editableRoomSettings.value.modifiers) ? [...editableRoomSettings.value.modifiers] : []
  const idx = current.indexOf(modId)
  if (idx >= 0) {
    current.splice(idx, 1)
  } else {
    // Backend persists max 4 modifiers; block extra locally to prevent "auto-disable" after save.
    if (current.length >= 4) {
      toast.warn('You can enable up to 4 modifiers at once.')
      return
    }
    current.push(modId)
  }
  editableRoomSettings.value = {
    ...editableRoomSettings.value,
    modifiers: current,
  }
  roomSettingsDirty.value = true
  scheduleRoomSettingsAutosave()
}

function buildMultiplayerRoute(result, type, code) {
  const settings = result?.settings || {}
  const mode = settings.mode || 'custom'
  const query = {
    from: result.start_title,
    to: result.end_title,
    [type]: code,
  }
  if (settings.genre) query.genre = settings.genre
  if (Array.isArray(settings.modifiers) && settings.modifiers.length > 0) {
    query.modifiers = settings.modifiers.join(',')
  }
  if (settings.timeLimit != null) {
    query.timeLimit = String(settings.timeLimit)
    query.difficulty = 'custom'
  }
  if (settings.clickLimit != null) {
    query.clickLimit = String(settings.clickLimit)
    query.difficulty = 'custom'
  }
  return { name: 'game', params: { mode }, query }
}

function roomSettingsSignature(settings) {
  const s = settings || {}
  return JSON.stringify({
    mode: s.mode || null,
    genre: s.genre || null,
    modifiers: Array.isArray(s.modifiers) ? [...s.modifiers].sort() : [],
    timeLimit: s.timeLimit ?? null,
    clickLimit: s.clickLimit ?? null,
    customStartTitle: s.customStartTitle || null,
    customEndTitle: s.customEndTitle || null,
  })
}

const noBackModes = ['daily', 'challenge']

const filteredModifiers = computed(() => {
  return modifierList.filter(m => {
    if (m.id === 'speedDecay' && selectedModeId.value !== 'sprint') return false
    if (m.id === 'noback' && noBackModes.includes(selectedModeId.value)) return false
    return true
  })
})
const soloModifiers = computed(() => filteredModifiers.value.filter(m => m.id !== 'suddenDeath'))

const difficulties = computed(() => {
  const mode = selectedModeId.value
  return Object.values(DIFFICULTIES).map(d => {
    let detail = ''
    if (d.id === 'custom') { detail = mode === 'sprint' ? 'Your time' : mode === 'challenge' ? 'Your cap' : '' }
    else if (mode === 'sprint') { detail = formatLimitMss(d.sprintTime) }
    else if (mode === 'challenge') { detail = `${d.challengeClicks} links max` }
    const isCustom = d.id === 'custom'
    const diffMult = progression.DIFFICULTY_XP[d.id] || 1.0
    const estimate = progression.estimateXp(mode, d.id, activeModifiers.value.length)
    return {
      ...d,
      detail,
      diffMult,
      estimatedXp: estimate.total,
      totalMult: estimate.multiplier,
      color: isCustom ? '#c9a227' : d.id === 'easy' ? '#39ff14' : d.id === 'normal' ? '#ffbf00' : '#ff4444',
      bg: isCustom ? 'rgba(201,162,39,0.06)' : d.id === 'easy' ? 'rgba(57,255,20,0.06)' : d.id === 'normal' ? 'rgba(255,191,0,0.06)' : 'rgba(255,68,68,0.06)',
      shadow: isCustom ? 'rgba(201,162,39,0.1)' : d.id === 'easy' ? 'rgba(57,255,20,0.1)' : d.id === 'normal' ? 'rgba(255,191,0,0.1)' : 'rgba(255,68,68,0.1)',
    }
  })
})

const stats = computed(() => getStats())
const hasStats = computed(() => Object.keys(stats.value.modes || {}).length > 0)
const hasGenreStats = computed(() => Object.keys(stats.value.genres || {}).length > 0)
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
  if (hasActiveMultiplayerSession.value) {
    toast.warn('Leave your multiplayer room before starting a solo game.')
    return
  }
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
  const soloModifiers = activeModifiers.value.filter(id => id !== 'suddenDeath')
  if (soloModifiers.length > 0) query.modifiers = soloModifiers.join(',')
  router.push({ name: 'game', params: { mode: selectedModeId.value }, query })
}

function startDaily() {
  if (hasActiveMultiplayerSession.value) {
    toast.warn('Leave your multiplayer room before starting a solo game.')
    return
  }
  sound.playStart()
  router.push({ name: 'game', params: { mode: 'daily' } })
}

async function refreshResumeSession() {
  const saved = loadMultiplayerSession()
  if (!saved || !saved.route || !saved.code) {
    resumeMultiplayerSession.value = null
    return
  }
  const hasMultiplayerQuery = !!(saved.route?.query?.match || saved.route?.query?.lobby)
  if (!hasMultiplayerQuery) {
    clearMultiplayerSession()
    resumeMultiplayerSession.value = null
    return
  }
  if (saved.userId !== (auth.user.value?.id || null)) {
    resumeMultiplayerSession.value = null
    return
  }

  try {
    if (saved.route?.query?.match) {
      const m = await api.get(`/match/${saved.code}`)
      const isEffectivelyFinished = m?.status === 'finished'
        || m?.winner_user_id != null
        || (m?.you?.submitted && m?.opponent?.submitted)
      if (!m || m.error || isEffectivelyFinished || m.you?.quit) {
        clearMultiplayerSession()
        resumeMultiplayerSession.value = null
        return
      }
    } else if (saved.route?.query?.lobby) {
      const l = await api.get(`/lobby/${saved.code}`)
      if (!l || l.error || l.status === 'finished') {
        clearMultiplayerSession()
        resumeMultiplayerSession.value = null
        return
      }
    }
  } catch {
    clearMultiplayerSession()
    resumeMultiplayerSession.value = null
    return
  }

  resumeMultiplayerSession.value = saved
}

function resumeMultiplayer() {
  const routeToGame = buildSessionRoute(resumeMultiplayerSession.value)
  if (!routeToGame) {
    clearMultiplayerSession()
    refreshResumeSession()
    return
  }
  sound.playStart()
  router.push(routeToGame)
}

async function createMatch() {
  if (!auth.user.value) { toast.warn('Login required for 1v1 matches'); showAuthModal.value = true; return }
  suppressModalFriendInvites.value = false
  replayMatchWaitingMode.value = false
  replayLobbyWaitingMode.value = false
  replayMatchSawOpponentMissing.value = false
  matchLoading.value = true
  matchError.value = ''
  try {
    const pair = await wiki.getRandomPairByGenre(genres.find(g => g.id === selectedGenreId.value))
    if (!pair) { matchError.value = 'Could not generate articles'; return }
    const result = await api.post('/match/create', {
      startTitle: pair.start.title,
      endTitle: pair.end.title,
      settings: toMultiplayerSettingsPayload(),
    })
    if (result.error) { matchError.value = result.error; return }
    matchCode.value = result.code
    joinedMatchCode.value = ''
    matchOpponentUsername.value = ''
    matchStatus.value = 'waiting'
    hydrateRoomSettingsFromResult(result)
    lastSeenMatchSettingsSignature.value = roomSettingsSignature(result?.settings)
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
    const code = matchCode.value || joinedMatchCode.value
    if (!code) { stopMatchPolling(); return }
    try {
      const result = await api.get(`/match/${code}`)
      const signature = roomSettingsSignature(result?.settings)
      if (!canEditRoomSettings.value && lastSeenMatchSettingsSignature.value && signature !== lastSeenMatchSettingsSignature.value) {
        toast.info('Host updated match settings.')
      }
      lastSeenMatchSettingsSignature.value = signature
      if (result?.settings && (!canEditRoomSettings.value || !roomSettingsDirty.value)) hydrateRoomSettingsFromResult(result)
      const opponentPresent = !!result?.opponent?.username && !result?.opponent?.quit
      if (opponentPresent) {
        matchOpponentUsername.value = result.opponent.username
      } else {
        matchOpponentUsername.value = ''
      }
      if (result.status === 'active') {
        stopMatchPolling()
        sound.playStart()
        showMatchModal.value = false
        router.push(buildMultiplayerRoute(result, 'match', code))
        return
      }
      if (result.status === 'finished') {
        stopMatchPolling()
        showMatchModal.value = false
        matchCode.value = ''
        joinedMatchCode.value = ''
        matchStatus.value = null
        matchOpponentUsername.value = ''
        if (hostInviteId.value) {
          try {
            const inviteStatus = await api.get(`/friends/game-invite/${hostInviteId.value}`)
            if (inviteStatus.status === 'declined') {
              toast.info(`${hostInviteUser.value || 'Invited player'} denied the invite.`)
            } else {
              toast.info('1v1 room closed.')
            }
          } catch {
            toast.info('1v1 room closed.')
          }
        } else {
          toast.info('1v1 room closed.')
        }
        hostInviteId.value = null
        hostInviteUser.value = ''
        return
      }
      if (matchCode.value) {
        const hasOpponent = !!result?.opponent?.username && !result?.opponent?.quit
        if (hasOpponent) {
          matchOpponentLeftNotified.value = false
        } else if (matchStatus.value === 'ready' && !matchOpponentLeftNotified.value) {
          toast.info('Opponent left the room.')
          matchOpponentLeftNotified.value = true
          matchOpponentUsername.value = ''
        }
        if (replayMatchWaitingMode.value) {
          if (!hasOpponent) replayMatchSawOpponentMissing.value = true
          const opponentReplayReady = !!result?.replay?.opponent_ready
          const opponentRejoinedAfterMissing = hasOpponent && replayMatchSawOpponentMissing.value
          matchStatus.value = hasOpponent && (opponentReplayReady || opponentRejoinedAfterMissing)
            ? 'ready'
            : 'waiting'
        } else {
          matchStatus.value = hasOpponent ? 'ready' : 'waiting'
        }
      }
    } catch { /* ignore */ }
  }, MULTIPLAYER_WAITING_POLL_MS)
}

function stopMatchPolling() {
  if (matchPollTimer) { clearInterval(matchPollTimer); matchPollTimer = null }
}

function stopGroupLobbyPolling() {
  if (groupPollTimer) { clearInterval(groupPollTimer); groupPollTimer = null }
}

function resetGroupLobbyUi() {
  stopGroupLobbyPolling()
  groupView.value = 'menu'
  groupLobbyCode.value = ''
  groupLobbyData.value = null
  groupError.value = ''
  groupMaxPlayers.value = 8
  groupLoading.value = false
  groupStartLoading.value = false
  joinRoomCode.value = ''
  editableRoomSettings.value = createDefaultRoomSettings()
  roomSettingsDirty.value = false
  lastSeenMatchSettingsSignature.value = ''
  lastSeenLobbySettingsSignature.value = ''
  roomSettingsAutosaveRetry.value = false
  if (roomSettingsAutosaveTimer) {
    clearTimeout(roomSettingsAutosaveTimer)
    roomSettingsAutosaveTimer = null
  }
  replayMatchSawOpponentMissing.value = false
}

function switchPvpHub(mode) {
  if (mode === 'group') {
    matchTab.value = 'group'
    groupView.value = 'menu'
  } else {
    matchTab.value = 'duel'
  }
  matchError.value = ''
  groupError.value = ''
}

function startGroupLobbyPolling() {
  stopGroupLobbyPolling()
  groupPollTimer = setInterval(async () => {
    if (!groupLobbyCode.value) { stopGroupLobbyPolling(); return }
    try {
      const r = await api.get(`/lobby/${groupLobbyCode.value}`)
      if (r.error) return
      groupLobbyData.value = r
      const signature = roomSettingsSignature(r?.settings)
      if (!canEditRoomSettings.value && lastSeenLobbySettingsSignature.value && signature !== lastSeenLobbySettingsSignature.value) {
        toast.info('Host updated lobby settings.')
      }
      lastSeenLobbySettingsSignature.value = signature
      if (r?.settings && (!canEditRoomSettings.value || !roomSettingsDirty.value)) hydrateRoomSettingsFromResult(r)
      if (r.status === 'active') {
        stopGroupLobbyPolling()
        sound.playStart()
        showMatchModal.value = false
        router.push(buildMultiplayerRoute(r, 'lobby', r.code))
      }
    } catch { /* ignore */ }
  }, MULTIPLAYER_WAITING_POLL_MS)
}

async function createGroupLobby() {
  if (!auth.user.value) { toast.warn('Login required'); showAuthModal.value = true; return }
  replayLobbyWaitingMode.value = false
  replayMatchSawOpponentMissing.value = false
  groupLoading.value = true
  groupError.value = ''
  try {
    const pair = await wiki.getRandomPairByGenre(genres.find(g => g.id === selectedGenreId.value))
    if (!pair) { groupError.value = 'Could not generate articles'; return }
    const result = await api.post('/lobby/create', {
      startTitle: pair.start.title,
      endTitle: pair.end.title,
      maxPlayers: groupMaxPlayers.value,
      settings: toMultiplayerSettingsPayload(),
    })
    if (result.error) { groupError.value = result.error; return }
    groupLobbyCode.value = result.code
    groupLobbyData.value = result
    hydrateRoomSettingsFromResult(result)
    lastSeenLobbySettingsSignature.value = roomSettingsSignature(result?.settings)
    groupView.value = 'waiting'
    matchTab.value = 'group'
    toast.success(`Lobby created: ${result.code}`)
    startGroupLobbyPolling()
  } catch (e) {
    groupError.value = e.message || 'Failed to create lobby'
  } finally {
    groupLoading.value = false
  }
}

async function postStartGroupLobby() {
  if (!groupLobbyCode.value || !groupLobbyData.value?.is_host) return
  const saved = await flushPendingRoomSettings()
  if (!saved) return
  if (editableRoomSettings.value.mode === 'custom') {
    const start = String(editableRoomSettings.value.customStartTitle || '').trim().toLowerCase()
    const end = String(editableRoomSettings.value.customEndTitle || '').trim().toLowerCase()
    if (!start || !end || start === end) {
      groupError.value = 'Custom start and target must be different.'
      return
    }
  }
  groupStartLoading.value = true
  groupError.value = ''
  try {
    if (replayLobbyWaitingMode.value && editableRoomSettings.value.mode !== 'custom') {
      const genre = genres.find(g => g.id === editableRoomSettings.value.genre) || genres.find(g => g.id === 'random')
      const pair = await wiki.getRandomPairByGenre(genre)
      if (!pair) {
        groupError.value = 'Could not generate a new replay pair'
        return
      }
      const reseeded = await api.post(`/lobby/reseed/${groupLobbyCode.value}`, {
        startTitle: pair.start.title,
        endTitle: pair.end.title,
      })
      if (reseeded?.error) {
        groupError.value = reseeded.error
        return
      }
      groupLobbyData.value = reseeded
      hydrateRoomSettingsFromResult(reseeded)
      lastSeenLobbySettingsSignature.value = roomSettingsSignature(reseeded?.settings)
    }
    const r = await api.post(`/lobby/start/${groupLobbyCode.value}`)
    if (r.error) { groupError.value = r.error; return }
    // Do not force host into game before polling catches up for all players.
    // Poll loop will transition both sides as soon as backend status flips to active.
    if (!groupPollTimer) startGroupLobbyPolling()
  } catch (e) {
    groupError.value = e.message || 'Failed to start'
  } finally {
    groupStartLoading.value = false
  }
}

function copyGroupLobbyCode() {
  if (!groupLobbyCode.value) return
  navigator.clipboard.writeText(groupLobbyCode.value).then(() => toast.success('Code copied!')).catch(() => toast.warn('Could not copy'))
}

async function joinByCode(prefer = 'match') {
  if (!auth.user.value) { toast.warn('Login required for multiplayer'); showAuthModal.value = true; return }
  suppressModalFriendInvites.value = false
  replayMatchWaitingMode.value = false
  replayLobbyWaitingMode.value = false
  replayMatchSawOpponentMissing.value = false
  const code = joinRoomCode.value.trim().toUpperCase()
  if (!code || code.length < 4) {
    matchError.value = 'Enter a valid room code'
    groupError.value = 'Enter a valid room code'
    return
  }

  matchLoading.value = true
  groupLoading.value = true
  matchError.value = ''
  groupError.value = ''

  const order = prefer === 'lobby' ? ['lobby', 'match'] : ['match', 'lobby']
  const errors = {}

  try {
    for (const type of order) {
      try {
        if (type === 'match') {
          const result = await api.post(`/match/join/${code}`)
          if (result.error) {
            errors.match = result.error
          } else {
            if (result.status === 'finished') {
              errors.match = 'Match is closed.'
              continue
            }
            if (result.status === 'active') {
              sound.playStart()
              showMatchModal.value = false
              router.push(buildMultiplayerRoute(result, 'match', result.code))
              return
            }
            joinedMatchCode.value = result.code
            matchCode.value = ''
            matchStatus.value = 'joined_waiting'
            matchOpponentUsername.value = ''
            matchOpponentLeftNotified.value = false
            hydrateRoomSettingsFromResult(result)
            lastSeenMatchSettingsSignature.value = roomSettingsSignature(result?.settings)
            matchTab.value = 'waiting'
            toast.success('Joined match. Waiting for host to start...')
            startMatchPolling()
            return
          }
        } else {
          const result = await api.post(`/lobby/join/${code}`)
          if (result.error) {
            errors.lobby = result.error
          } else {
            groupLobbyCode.value = result.code
            groupLobbyData.value = result
            hydrateRoomSettingsFromResult(result)
            lastSeenLobbySettingsSignature.value = roomSettingsSignature(result?.settings)
            matchTab.value = 'group'
            if (result.status === 'active') {
              sound.playStart()
              showMatchModal.value = false
              router.push(buildMultiplayerRoute(result, 'lobby', result.code))
              return
            }
            groupView.value = 'waiting'
            toast.success('Joined lobby')
            startGroupLobbyPolling()
            return
          }
        }
      } catch (e) {
        errors[type] = e?.message || 'Join failed'
      }
    }

    const message = errors.match || errors.lobby || 'Code not found'
    matchError.value = message
    groupError.value = message
  } finally {
    matchLoading.value = false
    groupLoading.value = false
  }
}

async function saveRoomSettings() {
  if (!canEditRoomSettings.value) return
  if (editableRoomSettings.value.mode === 'custom') {
    const start = String(editableRoomSettings.value.customStartTitle || '').trim()
    const end = String(editableRoomSettings.value.customEndTitle || '').trim()
    if (!start || !end) {
      toast.warn('Custom mode requires both start and target articles.')
      return
    }
    if (start.toLowerCase() === end.toLowerCase()) {
      toast.warn('Custom start and target must be different.')
      return
    }
  }
  if (roomSettingsSaving.value) {
    roomSettingsAutosaveRetry.value = true
    return
  }
  roomSettingsSaving.value = true
  try {
    if (matchTab.value === 'group' && groupLobbyCode.value) {
      const updated = await api.post(`/lobby/settings/${groupLobbyCode.value}`, {
        settings: editableRoomSettings.value,
      })
      if (updated.error) throw new Error(updated.error)
      groupLobbyData.value = updated
      hydrateRoomSettingsFromResult(updated)
      roomSettingsDirty.value = false
      lastSeenLobbySettingsSignature.value = roomSettingsSignature(updated?.settings)
      toast.success('Lobby settings saved')
      return
    }
    const code = matchCode.value || joinedMatchCode.value
    if (code) {
      const updated = await api.post(`/match/settings/${code}`, {
        settings: editableRoomSettings.value,
      })
      if (updated.error) throw new Error(updated.error)
      hydrateRoomSettingsFromResult(updated)
      roomSettingsDirty.value = false
      lastSeenMatchSettingsSignature.value = roomSettingsSignature(updated?.settings)
      toast.success('Match settings saved')
    }
  } catch (e) {
    toast.error(e?.message || 'Could not save settings')
  } finally {
    roomSettingsSaving.value = false
    if (roomSettingsAutosaveRetry.value) {
      roomSettingsAutosaveRetry.value = false
      scheduleRoomSettingsAutosave()
    }
  }
}

async function flushPendingRoomSettings() {
  if (!canEditRoomSettings.value) return true

  if (roomSettingsAutosaveTimer) {
    clearTimeout(roomSettingsAutosaveTimer)
    roomSettingsAutosaveTimer = null
  }

  if (roomSettingsSaving.value) {
    roomSettingsAutosaveRetry.value = true
    while (roomSettingsSaving.value) {
      await new Promise((resolve) => setTimeout(resolve, 25))
    }
  }

  if (!roomSettingsDirty.value) return true
  await saveRoomSettings()
  return !roomSettingsDirty.value
}

function scheduleRoomSettingsAutosave() {
  if (!canEditRoomSettings.value) return
  if (roomSettingsAutosaveTimer) clearTimeout(roomSettingsAutosaveTimer)
  roomSettingsAutosaveTimer = setTimeout(() => {
    roomSettingsAutosaveTimer = null
    if (!roomSettingsDirty.value) return
    saveRoomSettings()
  }, ROOM_SETTINGS_AUTOSAVE_DELAY_MS)
}

async function joinInvitedMatchFromRoute() {
  const inviteCodeRaw = typeof route.query.inviteMatch === 'string' ? route.query.inviteMatch : ''
  const inviteCode = inviteCodeRaw.trim().toUpperCase()
  if (!inviteCode || handlingInviteJoin) return
  if (!auth.user.value) {
    showAuthModal.value = true
    toast.warn('Login required to join the invite')
    return
  }

  handlingInviteJoin = true
  showMatchModal.value = true
  matchTab.value = 'duel'
  joinRoomCode.value = inviteCode
  try {
    await joinByCode('match')
  } finally {
    const nextQuery = { ...route.query }
    delete nextQuery.inviteMatch
    router.replace({ name: 'home', query: nextQuery })
    handlingInviteJoin = false
  }
}

async function joinInvitedLobbyFromRoute() {
  const inviteCodeRaw = typeof route.query.inviteLobby === 'string' ? route.query.inviteLobby : ''
  const inviteCode = inviteCodeRaw.trim().toUpperCase()
  if (!inviteCode || handlingInviteJoin) return
  if (!auth.user.value) {
    showAuthModal.value = true
    toast.warn('Login required to join the invite')
    return
  }

  handlingInviteJoin = true
  showMatchModal.value = true
  matchTab.value = 'group'
  joinRoomCode.value = inviteCode
  try {
    await joinByCode('lobby')
  } finally {
    const nextQuery = { ...route.query }
    delete nextQuery.inviteLobby
    router.replace({ name: 'home', query: nextQuery })
    handlingInviteJoin = false
  }
}

async function openHostedMatchFromRoute() {
  const hostCodeRaw = typeof route.query.hostMatch === 'string' ? route.query.hostMatch : ''
  const hostCode = hostCodeRaw.trim().toUpperCase()
  if (!hostCode || handlingHostMatch) return
  if (!auth.user.value) {
    showAuthModal.value = true
    toast.warn('Login required to host the invite match')
    return
  }

  handlingHostMatch = true
  replayMatchWaitingMode.value = false
  replayLobbyWaitingMode.value = false
  replayMatchSawOpponentMissing.value = false
  matchLoading.value = true
  matchError.value = ''
  groupError.value = ''

  let existing = null
  try {
    existing = await api.get(`/match/${hostCode}`)
    if (!existing || existing.error || existing.status === 'finished' || existing.you?.quit) {
      toast.info('That match is already closed. Create a new one.')
      const nextQuery = { ...route.query }
      delete nextQuery.hostMatch
      delete nextQuery.hostInviteId
      delete nextQuery.hostInviteUser
      router.replace({ name: 'home', query: nextQuery })
      return
    }
  } catch {
    toast.info('That match is no longer available. Create a new one.')
    const nextQuery = { ...route.query }
    delete nextQuery.hostMatch
    delete nextQuery.hostInviteId
    delete nextQuery.hostInviteUser
    router.replace({ name: 'home', query: nextQuery })
    return
  } finally {
    matchLoading.value = false
  }

  showMatchModal.value = true
  suppressModalFriendInvites.value = true
  matchTab.value = 'waiting'
  matchCode.value = hostCode
  joinedMatchCode.value = ''
  matchStatus.value = 'waiting'
  matchOpponentUsername.value = ''
  matchError.value = ''
  hydrateRoomSettingsFromResult(existing)
  lastSeenMatchSettingsSignature.value = roomSettingsSignature(existing?.settings)
  hostInviteId.value = Number(route.query.hostInviteId) || null
  hostInviteUser.value = typeof route.query.hostInviteUser === 'string' ? route.query.hostInviteUser : ''
  startMatchPolling()

  const nextQuery = { ...route.query }
  delete nextQuery.hostMatch
  delete nextQuery.hostInviteId
  delete nextQuery.hostInviteUser
  router.replace({ name: 'home', query: nextQuery }).finally(() => {
    handlingHostMatch = false
  })
}

async function openReplayRoomFromRoute() {
  const replayType = typeof route.query.replayType === 'string' ? route.query.replayType.trim().toLowerCase() : ''
  const replayCode = typeof route.query.replayCode === 'string' ? route.query.replayCode.trim().toUpperCase() : ''
  if (!replayType || !replayCode || handlingHostMatch || handlingInviteJoin) return
  if (!auth.user.value) {
    showAuthModal.value = true
    toast.warn('Login required for multiplayer replay')
    return
  }

  showMatchModal.value = true
  suppressModalFriendInvites.value = false
  matchError.value = ''
  groupError.value = ''
  try {
    if (replayType === 'match') {
      const result = await api.get(`/match/${replayCode}`)
      if (result.error || result.status === 'finished') {
        toast.warn('Replay room is closed.')
      } else if (result.status === 'active') {
        replayMatchWaitingMode.value = false
        replayLobbyWaitingMode.value = false
        sound.playStart()
        showMatchModal.value = false
        router.push(buildMultiplayerRoute(result, 'match', result.code))
      } else {
        replayMatchWaitingMode.value = true
        replayLobbyWaitingMode.value = false
        if (result.can_edit_settings) {
          matchCode.value = result.code
          joinedMatchCode.value = ''
          const hasOpponent = !!result?.opponent?.username && !result?.opponent?.quit
          replayMatchSawOpponentMissing.value = !hasOpponent
          const opponentReplayReady = !!result?.replay?.opponent_ready
          matchStatus.value = hasOpponent && (opponentReplayReady || replayMatchSawOpponentMissing.value)
            ? 'ready'
            : 'waiting'
        } else {
          matchCode.value = ''
          joinedMatchCode.value = result.code
          matchStatus.value = 'joined_waiting'
          replayMatchSawOpponentMissing.value = false
        }
        matchOpponentUsername.value = result.opponent?.username || ''
        hydrateRoomSettingsFromResult(result)
        lastSeenMatchSettingsSignature.value = roomSettingsSignature(result?.settings)
        matchTab.value = 'waiting'
        startMatchPolling()
      }
    } else if (replayType === 'lobby') {
      const result = await api.get(`/lobby/${replayCode}`)
      if (result.error || result.status === 'finished') {
        toast.warn('Replay lobby is closed.')
      } else if (result.status === 'active') {
        replayLobbyWaitingMode.value = false
        sound.playStart()
        showMatchModal.value = false
        router.push(buildMultiplayerRoute(result, 'lobby', result.code))
      } else {
        replayMatchWaitingMode.value = false
        replayLobbyWaitingMode.value = true
        replayMatchSawOpponentMissing.value = false
        groupLobbyCode.value = result.code
        groupLobbyData.value = result
        hydrateRoomSettingsFromResult(result)
        lastSeenLobbySettingsSignature.value = roomSettingsSignature(result?.settings)
        matchTab.value = 'group'
        groupView.value = 'waiting'
        startGroupLobbyPolling()
      }
    }
  } catch (e) {
    toast.warn(e?.message || 'Could not open replay room')
  } finally {
    const nextQuery = { ...route.query }
    delete nextQuery.replayType
    delete nextQuery.replayCode
    router.replace({ name: 'home', query: nextQuery })
  }
}

async function leaveCurrentMultiplayerRoom() {
  if (!auth.user.value) return
  matchLoading.value = true
  groupLoading.value = true
  try {
    await api.post('/multiplayer/leave-current')
    clearMultiplayerSession()
    resumeMultiplayerSession.value = null
    matchError.value = ''
    groupError.value = ''
    toast.success('Left current multiplayer room')
  } catch (e) {
    const msg = e?.message || 'Could not leave current room'
    matchError.value = msg
    groupError.value = msg
  } finally {
    matchLoading.value = false
    groupLoading.value = false
  }
}

async function inviteFriendToCurrentMatch(username) {
  const code = (matchCode.value || joinedMatchCode.value || '').trim().toUpperCase()
  const targetUsername = (username || '').trim()
  if (!code || !targetUsername) return
  multiplayerInviteLoading.value = true
  multiplayerInviteError.value = ''
  try {
    const result = await api.post('/friends/room-invite', {
      username: targetUsername,
      roomType: 'match',
      roomCode: code,
    })
    if (result?.error) throw new Error(result.error)
    toast.success(`Invite sent to ${targetUsername}`)
  } catch (e) {
    const message = e?.message || 'Could not send invite'
    multiplayerInviteError.value = message
    toast.error(message)
  } finally {
    multiplayerInviteLoading.value = false
  }
}

async function inviteFriendToCurrentLobby(username) {
  const code = (groupLobbyCode.value || '').trim().toUpperCase()
  const targetUsername = (username || '').trim()
  if (!code || !targetUsername) return
  multiplayerInviteLoading.value = true
  multiplayerInviteError.value = ''
  try {
    const result = await api.post('/friends/room-invite', {
      username: targetUsername,
      roomType: 'lobby',
      roomCode: code,
    })
    if (result?.error) throw new Error(result.error)
    toast.success(`Invite sent to ${targetUsername}`)
  } catch (e) {
    const message = e?.message || 'Could not send invite'
    multiplayerInviteError.value = message
    toast.error(message)
  } finally {
    multiplayerInviteLoading.value = false
  }
}

async function closeMatchModal() {
  const hasOpenRoom = !!(matchCode.value || joinedMatchCode.value || groupLobbyCode.value)
  if (hasOpenRoom && auth.user.value) {
    try {
      await api.post('/multiplayer/leave-current')
      clearMultiplayerSession()
      resumeMultiplayerSession.value = null
      matchError.value = ''
      groupError.value = ''
      toast.info('Left multiplayer room')
    } catch {
      // If leave call fails, still allow closing modal.
    }
  }
  showMatchModal.value = false
  suppressModalFriendInvites.value = false
  replayMatchWaitingMode.value = false
  replayLobbyWaitingMode.value = false
  replayMatchSawOpponentMissing.value = false
  multiplayerInviteError.value = ''
}

async function startCreatedMatch() {
  if (!matchCode.value || matchStatus.value !== 'ready') return
  const saved = await flushPendingRoomSettings()
  if (!saved) return
  if (editableRoomSettings.value.mode === 'custom') {
    const start = String(editableRoomSettings.value.customStartTitle || '').trim().toLowerCase()
    const end = String(editableRoomSettings.value.customEndTitle || '').trim().toLowerCase()
    if (!start || !end || start === end) {
      toast.warn('Custom start and target must be different.')
      return
    }
  }
  const startFlow = async () => {
    if (replayMatchWaitingMode.value && editableRoomSettings.value.mode !== 'custom') {
      const genre = genres.find(g => g.id === editableRoomSettings.value.genre) || genres.find(g => g.id === 'random')
      const pair = await wiki.getRandomPairByGenre(genre)
      if (!pair) throw new Error('Could not generate a new replay pair')
      const reseeded = await api.post(`/match/reseed/${matchCode.value}`, {
        startTitle: pair.start.title,
        endTitle: pair.end.title,
      })
      if (reseeded?.error) throw new Error(reseeded.error)
      hydrateRoomSettingsFromResult(reseeded)
      lastSeenMatchSettingsSignature.value = roomSettingsSignature(reseeded?.settings)
    }
    return api.post(`/match/start/${matchCode.value}`)
  }
  startFlow().then(match => {
    replayMatchWaitingMode.value = false
    replayLobbyWaitingMode.value = false
    // Keep host in waiting flow; poller will route as soon as active state is observed.
    if (!matchPollTimer) startMatchPolling()
  }).catch((e) => toast.error(e?.message || 'Failed to start match'))
}

function copyMatchCode() {
  const code = matchCode.value || joinedMatchCode.value
  if (!code) return
  navigator.clipboard.writeText(code).then(() => toast.success('Code copied!')).catch(() => toast.warn('Could not copy to clipboard'))
}

function formatTime(seconds) {
  if (seconds === null || seconds === undefined) return '-'
  const m = Math.floor(seconds / 60)
  const s = seconds % 60
  return `${m}:${s.toString().padStart(2, '0')}`
}

const NAMEPLATE_BORDER_STYLE = {
  default: 'solid',
  dashed: 'dashed',
  double: 'double',
  glow: 'glow',
}

const ACCENT_COLOR_MAP = {
  rank: '#9ca3af',
  neon: '#39ff14',
  cyan: '#00e5ff',
  amber: '#ffbf00',
  purple: '#b44cff',
}

function leaderboardNameplateStyle(borderId, accentId) {
  const borderStyle = NAMEPLATE_BORDER_STYLE[borderId] || NAMEPLATE_BORDER_STYLE.default
  const color = ACCENT_COLOR_MAP[accentId] || ACCENT_COLOR_MAP.rank
  if (borderStyle === 'dashed') return `border: 1px dashed ${color}AA; background: transparent;`
  if (borderStyle === 'double') return `border: 3px double ${color}AA; background: transparent;`
  if (borderStyle === 'glow') return `border: 1px solid ${color}CC; background: ${color}10; box-shadow: inset 0 0 10px ${color}55;`
  return `border: 1px solid ${color}66; background: transparent;`
}

function formatProfileTitle(value) {
  const raw = String(value || 'newcomer')
  return raw.replace(/_/g, ' ').toUpperCase()
}

function formatPinnedBadge(value) {
  const raw = String(value || '')
  if (!raw) return ''
  return raw.replace(/_/g, ' ').toUpperCase()
}

function pinnedBadgeSvgPath(badgeId) {
  const badge = achievements.getAllAchievements().find(a => a.id === badgeId)
  if (!badge) return '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />'

  const byCategory = {
    speed: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M13 10V3L4 14h7v7l9-11h-7z" />',
    efficiency: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />',
    milestone: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />',
    dedication: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z" />',
    explorer: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />',
    mode: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />',
    special: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />',
  }
  return byCategory[badge.category] || byCategory.special
}


function handleKeydown(e) {
  if (e.key === 'Enter' && !showStats.value && !showAuthModal.value && !showHowTo.value && !showMatchModal.value) startGame()
  if (e.key === 'Escape') {
    if (showMatchModal.value) closeMatchModal()
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
    stopGroupLobbyPolling()
    if (matchStatus.value !== 'ready') {
      matchCode.value = ''
      joinedMatchCode.value = ''
      matchStatus.value = null
      matchOpponentUsername.value = ''
      matchTab.value = 'duel'
      matchError.value = ''
    }
    replayMatchWaitingMode.value = false
    replayLobbyWaitingMode.value = false
    replayMatchSawOpponentMissing.value = false
    multiplayerInviteError.value = ''
    suppressModalFriendInvites.value = false
    resetGroupLobbyUi()
  }
})
watch(authMode, () => { authError.value = ''; authConfirmPassword.value = ''; showPassword.value = false; showConfirmPassword.value = false })
watch(() => auth.user.value?.id, () => {
  refreshResumeSession()
  if (auth.user.value) friendsComposable.fetchFriends()
})

onMounted(() => {
  window.addEventListener('keydown', handleKeydown)
  api.get('/stats').then(d => globalStats.value = d).catch(() => {})
  api.get('/daily/leaderboard').then(d => dailyLeaderboard.value = d.scores || []).catch(() => {})
  trending.fetchTrending()
  refreshResumeSession()
  if (auth.user.value) friendsComposable.fetchFriends()
  openReplayRoomFromRoute()
  openHostedMatchFromRoute()
  joinInvitedMatchFromRoute()
  joinInvitedLobbyFromRoute()
  resumeTicker = setInterval(() => {
    resumeNowMs.value = Date.now()
  }, 1000)
})
onUnmounted(() => {
  window.removeEventListener('keydown', handleKeydown)
  stopMatchPolling()
  stopGroupLobbyPolling()
  if (roomSettingsAutosaveTimer) {
    clearTimeout(roomSettingsAutosaveTimer)
    roomSettingsAutosaveTimer = null
  }
  if (resumeTicker) {
    clearInterval(resumeTicker)
    resumeTicker = null
  }
})

watch(
  () => [route.query.replayType, route.query.replayCode, auth.user.value?.id],
  () => {
    openReplayRoomFromRoute()
  }
)
watch(
  () => [route.query.inviteMatch, auth.user.value?.id],
  () => {
    joinInvitedMatchFromRoute()
  }
)
watch(
  () => [route.query.inviteLobby, auth.user.value?.id],
  () => {
    joinInvitedLobbyFromRoute()
  }
)
watch(
  () => [route.query.hostMatch, auth.user.value?.id],
  () => {
    openHostedMatchFromRoute()
  }
)
</script>
