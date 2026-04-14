<template>
  <div class="relative min-h-screen flex flex-col">

    <!-- Top bar -->
    <header class="relative z-20 glass-header shrink-0">
      <div class="max-w-6xl mx-auto px-3 sm:px-5 py-2 sm:py-2.5 flex items-center justify-between gap-3">

        <!-- Brand -->
        <router-link to="/" class="flex items-center gap-2.5 shrink-0 group">
          <img src="/favicon.svg" alt="WikiLink" class="w-8 h-8 sm:w-9 sm:h-9 drop-shadow-[0_0_8px_rgba(57,255,20,0.3)] group-hover:drop-shadow-[0_0_12px_rgba(57,255,20,0.5)] transition-all" />
          <span class="font-pixel text-[8px] sm:text-[9px] text-crt-white/80 tracking-wider hidden sm:block">WIKILINK</span>
        </router-link>

        <!-- Right controls -->
        <div class="flex items-center gap-1.5 sm:gap-2.5">
          <router-link to="/" class="site-nav-group" style="text-decoration: none;">
            <div class="site-nav-icon-btn">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1" />
              </svg>
            </div>
            <span class="font-mono text-[10px] text-retro-muted hidden sm:inline pr-1">HOME</span>
          </router-link>
          <template v-if="isOwnProfile && auth.user.value">
            <button @click="handleLogout" class="site-nav-login-btn" style="border-color: rgba(255,68,68,0.2); color: rgba(255,68,68,0.6);" title="Logout">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
              </svg>
              <span class="font-mono text-[10px] tracking-wider">LOGOUT</span>
            </button>
          </template>
        </div>
      </div>
    </header>

    <!-- Loading state -->
    <div v-if="pageLoading" class="flex-1 flex items-center justify-center">
      <div class="text-center">
        <div class="w-8 h-8 border-2 border-crt-green/30 border-t-crt-green rounded-full animate-spin mx-auto mb-3"></div>
        <span class="font-mono text-xs text-retro-muted">Loading profile...</span>
      </div>
    </div>

    <!-- Not found state -->
    <div v-else-if="notFound" class="flex-1 flex items-center justify-center px-4">
      <div class="text-center">
        <div class="font-terminal text-6xl text-retro-muted/20 mb-3">404</div>
        <div class="font-pixel text-[9px] text-retro-muted tracking-wider mb-4">USER NOT FOUND</div>
        <router-link to="/" class="btn-retro-ghost inline-flex items-center gap-1.5 px-4 py-2">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          <span class="font-mono text-[10px]">BACK TO HOME</span>
        </router-link>
      </div>
    </div>

    <!-- Main profile content -->
    <main v-else class="flex-1 flex flex-col items-center px-3 sm:px-4 py-6 sm:py-10 relative z-10">
      <div class="max-w-2xl w-full space-y-4">

        <!-- Profile header card -->
        <div class="rounded-2xl overflow-hidden animate-fade-in"
             style="background: linear-gradient(180deg, #0d0e15, #0a0b11); border: 1.5px solid rgba(37,39,56,0.7); box-shadow: 0 12px 40px rgba(0,0,0,0.4);">

          <!-- Accent header strip -->
          <div class="relative h-20 sm:h-24 overflow-hidden"
               :style="`background: linear-gradient(135deg, ${rankColor}10, ${rankColor}05, transparent);`">
            <div class="absolute inset-0 opacity-[0.03]"
                 style="background-image: radial-gradient(circle, rgba(255,255,255,0.5) 1px, transparent 1px); background-size: 20px 20px;"></div>
            <div class="absolute bottom-0 left-0 right-0 h-[1px]" :style="`background: linear-gradient(90deg, transparent, ${rankColor}30, transparent);`"></div>
          </div>

          <div class="px-5 sm:px-6 pb-5 sm:pb-6 -mt-10 sm:-mt-12 relative">
            <div class="flex items-end gap-4 mb-4">
              <!-- Avatar -->
              <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-xl flex items-center justify-center shrink-0 relative"
                   :style="`background: #0d0e15; border: 2.5px solid ${rankColor}40; box-shadow: 0 0 20px ${rankColor}15;`">
                <span class="font-pixel text-3xl sm:text-4xl" :style="`color: ${rankColor}`">{{ profileInitial }}</span>
                <!-- Level badge -->
                <div v-if="isOwnProfile" class="absolute -bottom-2 -right-2 px-2 py-0.5 rounded-lg font-pixel text-[8px] tracking-wider"
                     :style="`background: #0d0e15; border: 1.5px solid ${rankColor}50; color: ${rankColor};`">
                  LV {{ progression.level.value }}
                </div>
              </div>
              <div class="min-w-0 flex-1 pb-1">
                <div class="flex items-center gap-2 flex-wrap">
                  <h1 class="font-pixel text-sm sm:text-base text-crt-white tracking-wider">{{ profileData.username }}</h1>
                  <span v-if="isOwnProfile" class="font-mono text-[9px] text-crt-cyan/60 px-1.5 py-0.5 rounded border border-crt-cyan/20 bg-crt-cyan/5">YOU</span>
                </div>
                <p v-if="profileMemberSince" class="font-mono text-[10px] text-retro-muted mt-0.5">Member since {{ profileMemberSince }}</p>

                <!-- Rank title -->
                <div class="flex items-center gap-2 mt-1.5">
                  <span class="font-pixel text-[8px] tracking-wider" :style="`color: ${rankColor}`">{{ rankTitle }}</span>
                </div>
              </div>
            </div>

            <!-- XP bar (own profile only) -->
            <div v-if="isOwnProfile" class="mb-4">
              <div class="flex items-center justify-between mb-1.5">
                <span class="font-mono text-[9px] text-retro-muted">{{ progression.currentXp.value }} / {{ progression.nextLevelXp.value }} XP</span>
                <span class="font-mono text-[9px]" :style="`color: ${rankColor}`">Level {{ progression.level.value }}</span>
              </div>
              <div class="h-2 rounded-full overflow-hidden" style="background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.06);">
                <div class="h-full rounded-full transition-all duration-500"
                     :style="`width: ${progression.progress.value * 100}%; background: linear-gradient(90deg, ${rankColor}80, ${rankColor}); box-shadow: 0 0 8px ${rankColor}40;`"></div>
              </div>
            </div>

            <!-- Friend action (other profile) -->
            <div v-if="!isOwnProfile && auth.user.value" class="mb-4 flex items-center gap-2">
              <template v-if="friendshipStatus === 'accepted'">
                <span class="font-mono text-[10px] text-crt-green flex items-center gap-1">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                  Friends
                </span>
                <button @click="handleRemoveFriend" class="btn-retro-ghost px-2 py-1 font-mono text-[9px] text-crt-red/70 hover:text-crt-red">Remove</button>
              </template>
              <template v-else-if="friendshipStatus === 'pending' && friendshipDirection === 'sent'">
                <span class="font-mono text-[10px] text-crt-amber flex items-center gap-1">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                  Request sent
                </span>
              </template>
              <template v-else-if="friendshipStatus === 'pending' && friendshipDirection === 'received'">
                <button @click="handleAcceptFromProfile" class="btn-retro-primary px-3 py-1.5 font-pixel text-[7px]">ACCEPT REQUEST</button>
              </template>
              <template v-else>
                <button @click="handleAddFriend" :disabled="friendActionLoading" class="btn-retro-primary px-3 py-1.5 font-pixel text-[7px]">
                  {{ friendActionLoading ? 'SENDING...' : 'ADD FRIEND' }}
                </button>
              </template>
              <button v-if="friendshipStatus === 'accepted'" @click="inviteTo1v1"
                      class="flex items-center gap-1 px-2.5 py-1.5 rounded-lg font-pixel text-[7px] tracking-wider transition-all duration-200"
                      style="border: 1.5px solid rgba(180,76,255,0.4); color: #b44cff; background: rgba(180,76,255,0.06);"
                      @mouseenter="$event.currentTarget.style.background='rgba(180,76,255,0.12)'"
                      @mouseleave="$event.currentTarget.style.background='rgba(180,76,255,0.06)'">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                1v1
              </button>
            </div>

            <!-- Stats grid -->
            <div class="grid grid-cols-4 gap-2 sm:gap-3">
              <div class="text-center rounded-xl py-3 px-2" style="background: rgba(255,191,0,0.04); border: 1px solid rgba(255,191,0,0.12);">
                <div class="font-terminal text-xl sm:text-2xl text-crt-amber tabular-nums">{{ profileStats.streak }}</div>
                <div class="font-mono text-[7px] sm:text-[8px] text-retro-muted tracking-wider mt-0.5">STREAK</div>
              </div>
              <div class="text-center rounded-xl py-3 px-2" style="background: rgba(0,229,255,0.04); border: 1px solid rgba(0,229,255,0.12);">
                <div class="font-terminal text-xl sm:text-2xl text-crt-cyan tabular-nums">{{ profileStats.totalGames }}</div>
                <div class="font-mono text-[7px] sm:text-[8px] text-retro-muted tracking-wider mt-0.5">GAMES</div>
              </div>
              <div class="text-center rounded-xl py-3 px-2" style="background: rgba(57,255,20,0.04); border: 1px solid rgba(57,255,20,0.12);">
                <div class="font-terminal text-xl sm:text-2xl text-crt-green tabular-nums">{{ profileStats.totalWins }}</div>
                <div class="font-mono text-[7px] sm:text-[8px] text-retro-muted tracking-wider mt-0.5">WINS</div>
              </div>
              <div class="text-center rounded-xl py-3 px-2"
                   :style="winRate > 60
                     ? 'background: rgba(57,255,20,0.04); border: 1px solid rgba(57,255,20,0.12);'
                     : winRate > 40
                       ? 'background: rgba(255,191,0,0.04); border: 1px solid rgba(255,191,0,0.12);'
                       : 'background: rgba(255,68,68,0.04); border: 1px solid rgba(255,68,68,0.12);'">
                <div class="font-terminal text-xl sm:text-2xl tabular-nums" :class="winRate > 60 ? 'text-crt-green' : winRate > 40 ? 'text-crt-amber' : 'text-crt-red'">{{ winRate }}%</div>
                <div class="font-mono text-[7px] sm:text-[8px] text-retro-muted tracking-wider mt-0.5">WIN RATE</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Tab navigation -->
        <div class="flex items-center gap-1 px-1 animate-fade-in overflow-x-auto">
          <button v-for="tab in availableTabs" :key="tab.id"
                  @click="activeTab = tab.id"
                  class="flex items-center gap-1.5 px-2.5 sm:px-3 py-2 rounded-lg font-pixel text-[7px] sm:text-[8px] tracking-wider transition-all duration-200 shrink-0 touch-manipulation"
                  :class="activeTab === tab.id
                    ? 'text-crt-green bg-crt-green/8 border border-crt-green/25'
                    : 'text-retro-muted hover:text-crt-white border border-transparent'">
            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" v-html="tab.svgIcon"></svg>
            <span class="hidden sm:inline">{{ tab.label }}</span>
            <span v-if="tab.badge" class="font-mono text-[8px] px-1 py-0.5 rounded bg-crt-red/20 text-crt-red ml-0.5">{{ tab.badge }}</span>
          </button>
        </div>

        <!-- Tab: Game Stats -->
        <div v-if="activeTab === 'stats'" class="space-y-3 animate-fade-in">
          <!-- Mode stats -->
          <div v-if="hasDetailedStats" class="space-y-3">
            <div v-for="(modeStats, modeId) in profileDetailedStats.modes" :key="modeId"
                 class="rounded-xl overflow-hidden" style="background: linear-gradient(180deg, #0d0e15, #0a0b12); border: 1.5px solid #252738;">
              <div class="px-4 sm:px-5 py-3 border-b border-retro-border/20 flex items-center gap-2.5">
                <div class="w-1 h-4 rounded-full bg-crt-cyan"></div>
                <h3 class="font-pixel text-[8px] text-crt-cyan tracking-[0.2em]">{{ getModeLabel(modeId) }}</h3>
                <span class="font-mono text-[9px] text-retro-muted ml-auto">{{ modeStats.gamesPlayed || 0 }} played</span>
              </div>
              <div class="p-4 sm:p-5">
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
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
                <!-- Win rate bar for this mode -->
                <div v-if="modeStats.gamesPlayed > 0" class="mt-4 pt-3 border-t border-retro-border/15">
                  <div class="flex items-center justify-between mb-1.5">
                    <span class="font-mono text-[9px] text-retro-muted">Win rate</span>
                    <span class="font-terminal text-sm" :class="modeWinRate(modeStats) > 60 ? 'text-crt-green' : modeWinRate(modeStats) > 40 ? 'text-crt-amber' : 'text-crt-red'">{{ modeWinRate(modeStats) }}%</span>
                  </div>
                  <div class="h-1.5 rounded-full overflow-hidden" style="background: rgba(255,255,255,0.04);">
                    <div class="h-full rounded-full transition-all duration-500"
                         :class="modeWinRate(modeStats) > 60 ? 'bg-crt-green/60' : modeWinRate(modeStats) > 40 ? 'bg-crt-amber/60' : 'bg-crt-red/60'"
                         :style="`width: ${modeWinRate(modeStats)}%`"></div>
                  </div>
                </div>
                <div v-if="modeStats.bestStreak" class="mt-3 pt-3 border-t border-retro-border/15 flex items-center justify-center gap-1.5">
                  <span class="font-mono text-[9px] text-retro-muted">BEST STREAK:</span>
                  <span class="font-terminal text-sm text-arcade-gold">{{ modeStats.bestStreak }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Genre stats -->
          <div v-if="hasGenreStats" class="rounded-xl overflow-hidden" style="background: linear-gradient(180deg, #0d0e15, #0a0b12); border: 1.5px solid #252738;">
            <div class="px-4 sm:px-5 py-3 border-b border-retro-border/20 flex items-center gap-2.5">
              <div class="w-1 h-4 rounded-full bg-crt-amber"></div>
              <h3 class="font-pixel text-[8px] text-crt-amber tracking-[0.2em]">GENRE BREAKDOWN</h3>
            </div>
            <div class="divide-y divide-retro-border/10">
              <div v-for="(gs, gId) in profileDetailedStats.genres" :key="gId"
                   class="px-4 sm:px-5 py-3 flex items-center justify-between hover:bg-retro-surface/10 transition-colors">
                <div class="flex items-center gap-2.5">
                  <svg class="w-4.5 h-4.5 text-crt-cyan/60 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" v-html="getGenreSvgIcon(gId)"></svg>
                  <span class="font-mono text-[11px] text-crt-white">{{ getGenreLabel(gId) }}</span>
                </div>
                <div class="flex items-center gap-4 text-center">
                  <div>
                    <div class="font-terminal text-sm text-crt-green tabular-nums">{{ gs.gamesWon }}</div>
                    <div class="font-mono text-[8px] text-retro-muted">W</div>
                  </div>
                  <div>
                    <div class="font-terminal text-sm text-crt-red tabular-nums">{{ gs.gamesLost || 0 }}</div>
                    <div class="font-mono text-[8px] text-retro-muted">L</div>
                  </div>
                  <div>
                    <div class="font-terminal text-sm text-crt-cyan tabular-nums">{{ gs.gamesPlayed }}</div>
                    <div class="font-mono text-[8px] text-retro-muted">GP</div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Achievements (own profile) -->
          <div v-if="isOwnProfile" class="rounded-xl overflow-hidden" style="background: linear-gradient(180deg, #0d0e15, #0a0b12); border: 1.5px solid #252738;">
            <div class="px-4 sm:px-5 py-3 border-b border-retro-border/20 flex items-center justify-between">
              <div class="flex items-center gap-2.5">
                <div class="w-1 h-4 rounded-full bg-arcade-gold"></div>
                <h3 class="font-pixel text-[8px] text-arcade-gold tracking-[0.2em]">ACHIEVEMENTS</h3>
              </div>
              <span class="font-terminal text-sm text-arcade-gold">{{ achievements.unlockedCount.value }} / {{ achievements.totalCount.value }}</span>
            </div>
            <div class="p-4 sm:p-5">
              <!-- Progress bar -->
              <div class="mb-4">
                <div class="h-1.5 rounded-full overflow-hidden" style="background: rgba(255,255,255,0.04);">
                  <div class="h-full rounded-full bg-arcade-gold/60 transition-all duration-500"
                       :style="`width: ${achievements.totalCount.value > 0 ? (achievements.unlockedCount.value / achievements.totalCount.value * 100) : 0}%`"></div>
                </div>
              </div>
              <div class="grid grid-cols-3 sm:grid-cols-4 gap-2">
                <div v-for="badge in achievements.getAllAchievements()" :key="badge.id"
                     class="achievement-badge" :class="badge.unlocked ? 'unlocked' : 'locked'"
                     :title="badge.unlocked ? badge.description : '???'">
                  <svg class="badge-icon w-5 h-5" :class="badge.unlocked ? 'text-arcade-gold' : 'text-retro-muted/30'" fill="none" stroke="currentColor" viewBox="0 0 24 24" v-html="achievementSvgPath(badge.category)"></svg>
                  <div class="font-pixel text-[6px] tracking-wider" :class="badge.unlocked ? 'text-arcade-gold' : 'text-retro-muted/40'">{{ badge.unlocked ? badge.name : '???' }}</div>
                  <div v-if="badge.unlocked" class="font-mono text-[8px] text-crt-green mt-0.5">+{{ badge.xp }} XP</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Empty state -->
          <div v-if="!hasDetailedStats && !hasGenreStats" class="rounded-xl p-10 text-center" style="background: linear-gradient(180deg, #0d0e15, #0a0b12); border: 1.5px solid #252738;">
            <svg class="w-10 h-10 text-retro-muted/20 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            <div class="font-pixel text-[8px] text-retro-muted/50 mb-2">NO STATS YET</div>
            <p class="font-mono text-xs text-retro-muted/30">{{ isOwnProfile ? 'Play some games to see your stats here' : 'This player hasn\'t played any games yet' }}</p>
          </div>
        </div>

        <!-- Tab: Friends -->
        <div v-if="activeTab === 'friends'" class="space-y-3 animate-fade-in">
          <!-- Add friend form (own profile) -->
          <div v-if="isOwnProfile" class="rounded-xl p-4 sm:p-5" style="background: linear-gradient(180deg, #0d0e15, #0a0b12); border: 1.5px solid #252738;">
            <div class="flex items-center gap-2.5 mb-3">
              <div class="w-1 h-4 rounded-full bg-crt-cyan"></div>
              <span class="font-pixel text-[8px] text-crt-cyan tracking-[0.2em]">ADD FRIEND</span>
            </div>
            <div class="flex gap-2">
              <div class="flex-1 relative">
                <input v-model="friendSearchQuery" type="text" placeholder="Search by username..."
                       @input="debouncedSearch" @focus="showSearchResults = true"
                       class="w-full px-3 py-2 rounded-lg font-mono text-sm bg-[#12131c] border border-retro-border text-crt-white focus:border-crt-cyan focus:outline-none" />
                <!-- Search results dropdown -->
                <div v-if="showSearchResults && searchResults.length > 0"
                     class="absolute top-full left-0 right-0 mt-1 rounded-lg overflow-hidden z-30"
                     style="background: #12131c; border: 1.5px solid #252738; box-shadow: 0 8px 24px rgba(0,0,0,0.6);">
                  <button v-for="u in searchResults" :key="u.id"
                          @click="sendFriendRequest(u.username)"
                          class="w-full px-3 py-2.5 flex items-center justify-between hover:bg-retro-surface/20 transition-colors text-left">
                    <div class="flex items-center gap-2">
                      <div class="w-7 h-7 rounded-lg flex items-center justify-center font-pixel text-[9px] text-crt-green"
                           style="background: rgba(57,255,20,0.08); border: 1px solid rgba(57,255,20,0.2);">
                        {{ u.username.charAt(0).toUpperCase() }}
                      </div>
                      <span class="font-mono text-[11px] text-crt-white">{{ u.username }}</span>
                    </div>
                    <svg class="w-4 h-4 text-crt-cyan/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg>
                  </button>
                </div>
              </div>
              <button @click="sendFriendRequest(friendSearchQuery)" :disabled="!friendSearchQuery.trim() || friendsComposable.loading.value"
                      class="btn-retro-primary px-4 py-2 font-pixel text-[7px] shrink-0">
                {{ friendsComposable.loading.value ? '...' : 'ADD' }}
              </button>
            </div>
            <div v-if="friendError" class="font-mono text-[11px] text-crt-red mt-2">{{ friendError }}</div>
            <div v-if="friendSuccess" class="font-mono text-[11px] text-crt-green mt-2">{{ friendSuccess }}</div>
          </div>

          <!-- Pending requests (own profile) -->
          <div v-if="isOwnProfile && friendsComposable.incomingRequests.value.length > 0"
               class="rounded-xl overflow-hidden" style="background: linear-gradient(180deg, #0d0e15, #0a0b12); border: 1.5px solid rgba(255,191,0,0.25);">
            <div class="px-4 sm:px-5 py-3 border-b border-retro-border/20 flex items-center gap-2.5">
              <div class="w-1 h-4 rounded-full bg-crt-amber"></div>
              <span class="font-pixel text-[8px] text-crt-amber tracking-[0.2em]">PENDING REQUESTS</span>
              <span class="font-mono text-[9px] text-crt-amber/60">{{ friendsComposable.incomingRequests.value.length }}</span>
            </div>
            <div class="divide-y divide-retro-border/10">
              <div v-for="req in friendsComposable.incomingRequests.value" :key="req.request_id"
                   class="px-4 sm:px-5 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                  <div class="w-8 h-8 rounded-lg flex items-center justify-center font-pixel text-[9px] text-crt-amber"
                       style="background: rgba(255,191,0,0.08); border: 1px solid rgba(255,191,0,0.2);">
                    {{ req.username.charAt(0).toUpperCase() }}
                  </div>
                  <router-link :to="`/profile/${req.username}`" class="font-mono text-[11px] text-crt-white hover:text-crt-cyan transition-colors">
                    {{ req.username }}
                  </router-link>
                </div>
                <div class="flex items-center gap-1.5">
                  <button @click="handleAcceptRequest(req.request_id)"
                          class="px-2.5 py-1.5 rounded-lg font-pixel text-[7px] text-crt-green tracking-wider transition-all"
                          style="border: 1px solid rgba(57,255,20,0.3); background: rgba(57,255,20,0.06);">ACCEPT</button>
                  <button @click="handleDeclineRequest(req.request_id)"
                          class="px-2.5 py-1.5 rounded-lg font-pixel text-[7px] text-crt-red/70 tracking-wider transition-all"
                          style="border: 1px solid rgba(255,68,68,0.2); background: rgba(255,68,68,0.04);">DECLINE</button>
                </div>
              </div>
            </div>
          </div>

          <!-- Friends list -->
          <div class="rounded-xl overflow-hidden" style="background: linear-gradient(180deg, #0d0e15, #0a0b12); border: 1.5px solid #252738;">
            <div class="px-4 sm:px-5 py-3 border-b border-retro-border/20 flex items-center gap-2.5">
              <div class="w-1 h-4 rounded-full bg-crt-green"></div>
              <span class="font-pixel text-[8px] text-crt-green tracking-[0.2em]">FRIENDS</span>
              <span class="font-mono text-[9px] text-retro-muted">{{ friendsComposable.friends.value.length }}</span>
            </div>
            <div v-if="friendsComposable.friends.value.length > 0" class="divide-y divide-retro-border/10">
              <div v-for="friend in friendsComposable.friends.value" :key="friend.friendship_id"
                   class="px-4 sm:px-5 py-3 flex items-center justify-between hover:bg-retro-surface/10 transition-colors">
                <router-link :to="`/profile/${friend.username}`" class="flex items-center gap-3 min-w-0 flex-1">
                  <div class="w-9 h-9 rounded-lg flex items-center justify-center font-pixel text-[10px] text-crt-green shrink-0"
                       style="background: rgba(57,255,20,0.08); border: 1px solid rgba(57,255,20,0.2);">
                    {{ friend.username.charAt(0).toUpperCase() }}
                  </div>
                  <div class="min-w-0">
                    <div class="font-mono text-[11px] text-crt-white truncate hover:text-crt-cyan transition-colors">{{ friend.username }}</div>
                    <div class="font-mono text-[9px] text-retro-muted">
                      {{ friend.total_wins }}W / {{ friend.total_games }}G
                    </div>
                  </div>
                </router-link>
                <div class="flex items-center gap-1.5 shrink-0">
                  <button @click="inviteFriendTo1v1(friend.username)" title="Invite to 1v1"
                          class="p-1.5 rounded-lg transition-all"
                          style="border: 1px solid rgba(180,76,255,0.3); background: rgba(180,76,255,0.06);"
                          @mouseenter="$event.currentTarget.style.background='rgba(180,76,255,0.15)'"
                          @mouseleave="$event.currentTarget.style.background='rgba(180,76,255,0.06)'">
                    <svg class="w-3.5 h-3.5 text-arcade-purple" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                  </button>
                  <button v-if="isOwnProfile" @click="handleRemoveFriendById(friend.friendship_id)" title="Remove friend"
                          class="p-1.5 rounded-lg transition-all"
                          style="border: 1px solid rgba(255,68,68,0.2); background: rgba(255,68,68,0.04);"
                          @mouseenter="$event.currentTarget.style.background='rgba(255,68,68,0.12)'"
                          @mouseleave="$event.currentTarget.style.background='rgba(255,68,68,0.04)'">
                    <svg class="w-3.5 h-3.5 text-crt-red/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </div>
              </div>
            </div>
            <div v-else class="px-4 sm:px-5 py-8 text-center">
              <svg class="w-8 h-8 text-retro-muted/15 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
              <div class="font-pixel text-[8px] text-retro-muted/50 mb-2">NO FRIENDS YET</div>
              <p class="font-mono text-xs text-retro-muted/30">{{ isOwnProfile ? 'Search for players above to add friends' : 'This player hasn\'t added any friends yet' }}</p>
            </div>
          </div>
        </div>

        <!-- Tab: Settings (own profile only) -->
        <div v-if="activeTab === 'settings'" class="space-y-3 animate-fade-in">
          <!-- Change Password -->
          <div class="rounded-xl overflow-hidden" style="background: linear-gradient(180deg, #0d0e15, #0a0b12); border: 1.5px solid #252738;">
            <button @click="showChangePassword = !showChangePassword"
                    class="w-full px-5 py-3.5 flex items-center justify-between hover:bg-retro-surface/30 transition-colors">
              <div class="flex items-center gap-2.5">
                <div class="w-1 h-4 rounded-full bg-crt-cyan"></div>
                <span class="font-pixel text-[8px] text-crt-cyan tracking-[0.2em]">CHANGE PASSWORD</span>
              </div>
              <svg class="w-4 h-4 text-retro-muted transition-transform" :class="{ 'rotate-180': showChangePassword }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </button>
            <form v-if="showChangePassword" @submit.prevent="handleChangePassword" class="px-5 pb-5 space-y-3 border-t border-retro-border/20 pt-4">
              <div>
                <label class="font-mono text-[10px] text-retro-muted block mb-1">Current Password</label>
                <input v-model="currentPassword" type="password" required autocomplete="current-password"
                       class="w-full px-3 py-2 rounded-lg font-mono text-sm bg-[#12131c] border border-retro-border text-crt-white focus:border-crt-cyan focus:outline-none" />
              </div>
              <div>
                <label class="font-mono text-[10px] text-retro-muted block mb-1">New Password</label>
                <input v-model="newPassword" type="password" required minlength="6" autocomplete="new-password"
                       class="w-full px-3 py-2 rounded-lg font-mono text-sm bg-[#12131c] border border-retro-border text-crt-white focus:border-crt-cyan focus:outline-none" />
              </div>
              <div>
                <label class="font-mono text-[10px] text-retro-muted block mb-1">Confirm New Password</label>
                <input v-model="confirmNewPassword" type="password" required minlength="6" autocomplete="new-password"
                       class="w-full px-3 py-2 rounded-lg font-mono text-sm bg-[#12131c] border border-retro-border text-crt-white focus:border-crt-cyan focus:outline-none" />
              </div>
              <div v-if="changePasswordError" class="font-mono text-[11px] text-crt-red">{{ changePasswordError }}</div>
              <div v-if="changePasswordSuccess" class="font-mono text-[11px] text-crt-green">{{ changePasswordSuccess }}</div>
              <button type="submit" :disabled="changingPassword" class="btn-retro-primary w-full !py-2.5">
                {{ changingPassword ? 'SAVING...' : 'UPDATE PASSWORD' }}
              </button>
            </form>
          </div>

          <!-- Danger Zone -->
          <div class="rounded-xl overflow-hidden" style="background: linear-gradient(180deg, #0d0e15, #0a0b12); border: 1.5px solid rgba(255,68,68,0.2);">
            <button @click="showDeleteSection = !showDeleteSection"
                    class="w-full px-5 py-3.5 flex items-center justify-between hover:bg-crt-red/5 transition-colors">
              <div class="flex items-center gap-2.5">
                <div class="w-1 h-4 rounded-full bg-crt-red"></div>
                <span class="font-pixel text-[8px] text-crt-red tracking-[0.2em]">DELETE ACCOUNT</span>
              </div>
              <svg class="w-4 h-4 text-crt-red/50 transition-transform" :class="{ 'rotate-180': showDeleteSection }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </button>
            <div v-if="showDeleteSection" class="px-5 pb-5 border-t border-crt-red/10 pt-4">
              <p class="font-mono text-[11px] text-retro-muted mb-4 leading-relaxed">
                This will permanently delete your account, all your daily scores, and leaderboard entries. This action cannot be undone.
              </p>
              <form @submit.prevent="handleDeleteAccount" class="space-y-3">
                <div>
                  <label class="font-mono text-[10px] text-retro-muted block mb-1">Enter your password to confirm</label>
                  <input v-model="deletePassword" type="password" required autocomplete="current-password"
                         class="w-full px-3 py-2 rounded-lg font-mono text-sm bg-[#12131c] border border-crt-red/20 text-crt-white focus:border-crt-red focus:outline-none" />
                </div>
                <div v-if="deleteError" class="font-mono text-[11px] text-crt-red">{{ deleteError }}</div>
                <button type="submit" :disabled="deleting"
                        class="w-full py-2.5 rounded-lg font-pixel text-[8px] tracking-wider transition-all duration-200"
                        style="background: linear-gradient(180deg, #ff4444, #cc2222); border: 1.5px solid #ff4444; color: white; box-shadow: 0 0 15px rgba(255,68,68,0.2);">
                  {{ deleting ? 'DELETING...' : 'PERMANENTLY DELETE ACCOUNT' }}
                </button>
              </form>
            </div>
          </div>
        </div>

      </div>
    </main>

    <ProfileInviteModal
      :open="showInviteModal"
      :step="inviteStep"
      :target-user="inviteTargetUser"
      :error="inviteError"
      @close="showInviteModal = false"
      @start-match="startInviteMatch"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuth } from '../composables/useAuth'
import { useApi } from '../composables/useApi'
import { useToast } from '../composables/useToast'
import { useGame } from '../composables/useGame'
import { useProgression } from '../composables/useProgression'
import { useAchievements } from '../composables/useAchievements'
import { useFriends } from '../composables/useFriends'
import { useWikipedia } from '../composables/useWikipedia'
import ProfileInviteModal from '../components/profile/ProfileInviteModal.vue'

const props = defineProps({ username: String })

const router = useRouter()
const route = useRoute()
const auth = useAuth()
const api = useApi()
const toast = useToast()
const { GAME_MODES, GENRES, getStats } = useGame()
const progression = useProgression()
const achievements = useAchievements()
const friendsComposable = useFriends()
const wiki = useWikipedia()

const pageLoading = ref(true)
const notFound = ref(false)
const activeTab = ref('stats')

const profileData = ref({ username: '', created_at: '' })
const profileServerStats = ref({ modes: {}, genres: {} })
const profileStreak = ref(0)
const friendshipStatus = ref('none')
const friendshipDirection = ref(null)
const friendshipId = ref(null)
const friendActionLoading = ref(false)

const isOwnProfile = computed(() => {
  if (!props.username) return true
  return auth.user.value && auth.user.value.username.toLowerCase() === props.username.toLowerCase()
})

const profileInitial = computed(() => (profileData.value.username || '?').charAt(0).toUpperCase())

const profileMemberSince = computed(() => {
  const createdAt = profileData.value.created_at
  if (!createdAt) return null
  const d = new Date(createdAt.endsWith('Z') || createdAt.includes('+') ? createdAt : createdAt + 'Z')
  if (isNaN(d.getTime())) return null
  return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
})

const profileDetailedStats = computed(() => {
  if (isOwnProfile.value) return getStats()
  return profileServerStats.value
})

const profileStats = computed(() => {
  const stats = profileDetailedStats.value
  const modes = stats.modes || {}
  const totalGames = Object.values(modes).reduce((s, m) => s + (m.gamesPlayed || 0), 0)
  const totalWins = Object.values(modes).reduce((s, m) => s + (m.gamesWon || 0), 0)
  return {
    totalGames,
    totalWins,
    streak: isOwnProfile.value ? auth.streak.value : profileStreak.value,
  }
})

const winRate = computed(() => {
  if (profileStats.value.totalGames === 0) return 0
  return Math.round((profileStats.value.totalWins / profileStats.value.totalGames) * 100)
})

const hasDetailedStats = computed(() => Object.keys(profileDetailedStats.value.modes || {}).length > 0)
const hasGenreStats = computed(() => Object.keys(profileDetailedStats.value.genres || {}).length > 0)

const RANK_TIERS = [
  { minLevel: 50, title: 'WIKI LEGEND', color: '#ff2ecc' },
  { minLevel: 40, title: 'GRANDMASTER', color: '#b44cff' },
  { minLevel: 30, title: 'MASTER', color: '#ff6b2b' },
  { minLevel: 20, title: 'EXPERT', color: '#ffbf00' },
  { minLevel: 15, title: 'VETERAN', color: '#00e5ff' },
  { minLevel: 10, title: 'SPECIALIST', color: '#4c9fff' },
  { minLevel: 5,  title: 'PATHFINDER', color: '#39ff14' },
  { minLevel: 1,  title: 'NEWCOMER', color: '#555770' },
]

const currentRank = computed(() => {
  const lvl = isOwnProfile.value ? progression.level.value : 1
  return RANK_TIERS.find(r => lvl >= r.minLevel) || RANK_TIERS[RANK_TIERS.length - 1]
})

const rankTitle = computed(() => currentRank.value.title)
const rankColor = computed(() => currentRank.value.color)

function modeWinRate(modeStats) {
  const played = modeStats.gamesPlayed || 0
  if (played === 0) return 0
  return Math.round(((modeStats.gamesWon || 0) / played) * 100)
}

const TAB_SVG_ICONS = {
  stats: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />',
  friends: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />',
  settings: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />',
}

const availableTabs = computed(() => {
  const tabs = [
    { id: 'stats', label: 'STATS', svgIcon: TAB_SVG_ICONS.stats },
  ]
  tabs.push({
    id: 'friends',
    label: 'FRIENDS',
    svgIcon: TAB_SVG_ICONS.friends,
    badge: isOwnProfile.value ? friendsComposable.incomingRequests.value.length || null : null,
  })
  if (isOwnProfile.value) {
    tabs.push({ id: 'settings', label: 'SETTINGS', svgIcon: TAB_SVG_ICONS.settings })
  }
  return tabs
})

// Password change state
const showChangePassword = ref(false)
const currentPassword = ref('')
const newPassword = ref('')
const confirmNewPassword = ref('')
const changePasswordError = ref('')
const changePasswordSuccess = ref('')
const changingPassword = ref(false)

// Delete account state
const showDeleteSection = ref(false)
const deletePassword = ref('')
const deleteError = ref('')
const deleting = ref(false)

// Friend search state
const friendSearchQuery = ref('')
const searchResults = ref([])
const showSearchResults = ref(false)
const friendError = ref('')
const friendSuccess = ref('')
let searchTimeout = null

// 1v1 invite state
const showInviteModal = ref(false)
const inviteStep = ref('creating')
const inviteTargetUser = ref('')
const inviteId = ref(null)
const inviteMatchData = ref(null)
const inviteError = ref('')
let invitePollInterval = null

function getModeLabel(modeId) {
  return GAME_MODES[modeId]?.name?.toUpperCase() || modeId?.toUpperCase() || 'UNKNOWN'
}

function getGenreLabel(genreId) {
  return GENRES[genreId]?.name || genreId || 'Unknown'
}

function getGenreSvgIcon(genreId) {
  return GENRES[genreId]?.svgIcon || ''
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

function formatTime(seconds) {
  if (seconds === null || seconds === undefined) return '-'
  const m = Math.floor(seconds / 60)
  const s = seconds % 60
  return `${m}:${s.toString().padStart(2, '0')}`
}

async function loadProfile() {
  pageLoading.value = true
  notFound.value = false

  if (isOwnProfile.value) {
    if (!auth.user.value) {
      router.push({ name: 'home' })
      return
    }
    profileData.value = {
      username: auth.user.value.username,
      created_at: auth.user.value.created_at,
    }
    await Promise.all([
      friendsComposable.fetchFriends(),
      friendsComposable.fetchRequests(),
    ])
    pageLoading.value = false
    return
  }

  const data = await friendsComposable.getPublicProfile(props.username)
  if (data.error) {
    notFound.value = true
    pageLoading.value = false
    return
  }

  profileData.value = { username: data.username, created_at: data.created_at }
  profileServerStats.value = data.stats || { modes: {}, genres: {} }
  profileStreak.value = data.streak || 0

  if (data.friendship) {
    friendshipStatus.value = data.friendship.status
    friendshipDirection.value = data.friendship.direction || null
    friendshipId.value = data.friendship.friendship_id || null
  }

  if (auth.user.value) {
    await friendsComposable.fetchFriends()
  }

  pageLoading.value = false
}

async function handleChangePassword() {
  changePasswordError.value = ''
  changePasswordSuccess.value = ''
  if (newPassword.value !== confirmNewPassword.value) {
    changePasswordError.value = 'New passwords do not match.'
    return
  }
  changingPassword.value = true
  try {
    await api.post('/change-password', {
      currentPassword: currentPassword.value,
      newPassword: newPassword.value,
    })
    changePasswordSuccess.value = 'Password updated successfully.'
    currentPassword.value = ''
    newPassword.value = ''
    confirmNewPassword.value = ''
    toast.success('Password changed!')
  } catch (e) {
    changePasswordError.value = e.message
  } finally {
    changingPassword.value = false
  }
}

async function handleDeleteAccount() {
  deleteError.value = ''
  deleting.value = true
  try {
    await api.post('/delete-account', { password: deletePassword.value })
    localStorage.removeItem('wikilink_token')
    localStorage.removeItem('wikilink_stats_v2')
    localStorage.removeItem('wikilink_history')
    localStorage.removeItem('wikilink_daily')
    auth.user.value = null
    auth.streak.value = 0
    toast.success('Account deleted.')
    router.push({ name: 'home' })
  } catch (e) {
    deleteError.value = e.message
  } finally {
    deleting.value = false
  }
}

function handleLogout() {
  auth.logout()
  router.push({ name: 'home' })
}

function debouncedSearch() {
  clearTimeout(searchTimeout)
  friendError.value = ''
  friendSuccess.value = ''
  const q = friendSearchQuery.value.trim()
  if (q.length < 2) { searchResults.value = []; return }
  searchTimeout = setTimeout(async () => {
    searchResults.value = await friendsComposable.searchUsers(q)
    showSearchResults.value = true
  }, 300)
}

async function sendFriendRequest(username) {
  friendError.value = ''
  friendSuccess.value = ''
  showSearchResults.value = false
  if (!username || !username.trim()) return
  const result = await friendsComposable.sendRequest(username.trim())
  if (result.error) {
    friendError.value = result.error
  } else {
    friendSuccess.value = result.message || 'Request sent!'
    friendSearchQuery.value = ''
    searchResults.value = []
    toast.success(result.message || 'Friend request sent!')
  }
}

async function handleAcceptRequest(requestId) {
  const result = await friendsComposable.acceptRequest(requestId)
  if (result.ok) toast.success('Friend request accepted!')
  else toast.error(result.error || 'Failed to accept request')
}

async function handleDeclineRequest(requestId) {
  const result = await friendsComposable.declineRequest(requestId)
  if (result.ok) toast.info('Request declined')
  else toast.error(result.error || 'Failed to decline request')
}

async function handleRemoveFriendById(friendshipIdVal) {
  if (!confirm('Remove this friend?')) return
  const result = await friendsComposable.removeFriend(friendshipIdVal)
  if (result.ok) toast.info('Friend removed')
  else toast.error(result.error || 'Failed to remove friend')
}

async function handleAddFriend() {
  friendActionLoading.value = true
  const result = await friendsComposable.sendRequest(profileData.value.username)
  friendActionLoading.value = false
  if (result.error) {
    toast.error(result.error)
  } else {
    toast.success(result.message || 'Friend request sent!')
    friendshipStatus.value = result.status || 'pending'
    friendshipDirection.value = 'sent'
  }
}

async function handleAcceptFromProfile() {
  if (!friendshipId.value) return
  const result = await friendsComposable.acceptRequest(friendshipId.value)
  if (result.ok) {
    friendshipStatus.value = 'accepted'
    toast.success('Friend request accepted!')
  }
}

async function handleRemoveFriend() {
  if (!friendshipId.value || !confirm('Remove this friend?')) return
  const result = await friendsComposable.removeFriend(friendshipId.value)
  if (result.ok) {
    friendshipStatus.value = 'none'
    friendshipId.value = null
    toast.info('Friend removed')
  }
}

async function inviteTo1v1() {
  inviteFriendTo1v1(profileData.value.username)
}

async function inviteFriendTo1v1(username) {
  try {
    const pair = await wiki.getRandomPairByGenre(null)
    if (!pair) throw new Error('Could not generate articles')
    const result = await api.post('/friends/game-invite', {
      username,
      startTitle: pair.start.title,
      endTitle: pair.end.title,
    })
    if (result.error) throw new Error(result.error)
    const matchCode = result.invite?.match_code || ''
    if (!matchCode) throw new Error('Invite created but missing match code')
    const inviteIdFromResult = result.invite?.id
    toast.success(`Invite sent to ${username}`)
    router.push({
      name: 'home',
      query: {
        hostMatch: matchCode,
        hostInviteId: inviteIdFromResult ? String(inviteIdFromResult) : '',
        hostInviteUser: username,
      },
    })
  } catch (e) {
    toast.error(e.message || 'Failed to create invite')
  }
}

function clearInvitePolling() {
  if (invitePollInterval) {
    clearInterval(invitePollInterval)
    invitePollInterval = null
  }
}

function startInvitePolling() {
  clearInvitePolling()
  if (!inviteId.value) return
  invitePollInterval = setInterval(checkInviteStatus, 2000)
}

async function checkInviteStatus() {
  if (!inviteId.value || inviteStep.value !== 'pending') return
  try {
    const result = await api.get(`/friends/game-invite/${inviteId.value}`)
    inviteMatchData.value = {
      code: result.match_code || inviteMatchData.value?.code || '',
      start_title: result.start_title || inviteMatchData.value?.start_title || '',
      end_title: result.end_title || inviteMatchData.value?.end_title || '',
    }
    if (result.status === 'accepted') {
      inviteStep.value = 'accepted'
      clearInvitePolling()
      toast.success(`${inviteTargetUser.value} accepted your invite!`)
      return
    }
    if (result.status === 'declined') {
      inviteStep.value = 'declined'
      clearInvitePolling()
      toast.info(`${inviteTargetUser.value} declined your invite.`)
      return
    }
  } catch (e) {
    inviteError.value = e.message || 'Failed to check invite status'
    inviteStep.value = 'error'
    clearInvitePolling()
  }
}

function startInviteMatch() {
  if (!inviteMatchData.value?.code) return
  clearInvitePolling()
  showInviteModal.value = false
  router.push({
    name: 'game',
    params: { mode: 'custom' },
    query: {
      from: inviteMatchData.value.start_title,
      to: inviteMatchData.value.end_title,
      match: inviteMatchData.value.code,
    },
  })
}

function handleGlobalClick(e) {
  if (!e.target.closest('.relative')) {
    showSearchResults.value = false
  }
}

onMounted(() => {
  loadProfile()
  document.addEventListener('click', handleGlobalClick)
})

onBeforeUnmount(() => {
  clearInvitePolling()
  document.removeEventListener('click', handleGlobalClick)
})

watch(() => props.username, () => {
  activeTab.value = 'stats'
  loadProfile()
})

watch(() => route.name, () => {
  if (route.name === 'profile' || route.name === 'public-profile') {
    activeTab.value = 'stats'
    loadProfile()
  }
})
</script>
