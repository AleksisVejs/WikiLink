<template>
  <div class="relative min-h-screen flex flex-col">
    <SiteTopNav
      :user="auth.user.value"
      :level="progression.level.value"
      :current-xp="progression.currentXp.value"
      :next-level-xp="progression.nextLevelXp.value"
      :xp-percent="progression.progress.value * 100"
      :show-xp-bar="!!auth.user.value"
      @login="showAuthModal = true"
      @logout="auth.logout()"
    />

    <div v-if="pageLoading" class="flex-1 flex items-center justify-center">
      <div class="text-center">
        <div class="w-8 h-8 border-2 border-crt-green/30 border-t-crt-green rounded-full animate-spin mx-auto mb-3"></div>
        <span class="font-mono text-xs text-retro-muted">Loading profile...</span>
      </div>
    </div>

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

    <main v-else class="flex-1 px-3 sm:px-5 py-4 sm:py-8 relative z-10">
      <div class="max-w-6xl mx-auto grid grid-cols-1 lg:grid-cols-[280px,1fr] gap-4 sm:gap-5">
        <aside class="space-y-3 sm:space-y-4">
          <div class="rounded-2xl overflow-hidden"
               style="background: linear-gradient(180deg, #0d0e15, #0a0b11); border: 1.5px solid rgba(37,39,56,0.7); box-shadow: 0 12px 30px rgba(0,0,0,0.35);">
            <div class="h-16 sm:h-20" :style="profileBannerStyle"></div>
            <div class="p-4 sm:p-5 -mt-8">
              <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-xl flex items-center justify-center mb-3"
                   :style="nameplateBoxStyle">
                <svg
                  v-if="selectedProfileIcon"
                  class="w-8 h-8 sm:w-10 sm:h-10"
                  :style="`color:${profileAccentColor}`"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                  v-html="selectedProfileIcon.svgPath"
                ></svg>
                <span v-else class="font-pixel text-2xl sm:text-3xl" :style="`color:${profileAccentColor}`">{{ profileInitial }}</span>
              </div>
              <h1 class="font-pixel text-[10px] text-crt-white tracking-wider break-all">{{ profileData.username }}</h1>
              <p v-if="profileMemberSince" class="font-mono text-[10px] text-retro-muted mt-1">Member since {{ profileMemberSince }}</p>
              <div class="mt-2 font-pixel text-[8px] tracking-[0.14em]" :style="`color:${profileAccentColor}`">{{ profileTitleLabel }}</div>
              <div class="mt-1 font-mono text-[9px] text-retro-muted">{{ rankTitle }}</div>
              <div v-if="pinnedBadge" class="mt-1.5 inline-flex items-center gap-1.5 px-2 py-1 rounded-lg font-mono text-[8px] text-arcade-gold"
                   style="background: rgba(255,191,0,0.08); border: 1px solid rgba(255,191,0,0.3);">
                <svg class="w-3.5 h-3.5 text-arcade-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24" v-html="achievementSvgPath(pinnedBadge.category)"></svg>
                <span>PINNED</span>
                <span class="text-crt-white/85">{{ pinnedBadge.name }}</span>
              </div>

              <div v-if="isOwnProfile" class="mt-3">
                <div class="flex items-center justify-between mb-1">
                  <span class="font-mono text-[9px] text-retro-muted">Level {{ progression.level.value }}</span>
                  <span class="font-mono text-[9px] text-retro-muted">{{ progression.currentXp.value }}/{{ progression.nextLevelXp.value }}</span>
                </div>
                <div class="h-2 rounded-full overflow-hidden" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08);">
                  <div class="h-full rounded-full transition-all duration-500"
                       :style="`width:${progression.progress.value * 100}%;background:linear-gradient(90deg, ${profileAccentColor}85, ${profileAccentColor});`"></div>
                </div>
              </div>
            </div>
          </div>

          <div class="grid grid-cols-2 lg:grid-cols-1 gap-2">
            <div class="rounded-xl p-3 text-center" style="background:#10121b;border:1px solid #252738;">
              <div class="font-terminal text-xl text-crt-amber tabular-nums">{{ profileStats.streak }}</div>
              <div class="font-mono text-[8px] text-retro-muted tracking-wider">STREAK</div>
            </div>
            <div class="rounded-xl p-3 text-center" style="background:#10121b;border:1px solid #252738;">
              <div class="font-terminal text-xl text-crt-cyan tabular-nums">{{ profileStats.totalGames }}</div>
              <div class="font-mono text-[8px] text-retro-muted tracking-wider">GAMES</div>
            </div>
            <div class="rounded-xl p-3 text-center" style="background:#10121b;border:1px solid #252738;">
              <div class="font-terminal text-xl text-crt-green tabular-nums">{{ profileStats.totalWins }}</div>
              <div class="font-mono text-[8px] text-retro-muted tracking-wider">WINS</div>
            </div>
            <div class="rounded-xl p-3 text-center" style="background:#10121b;border:1px solid #252738;">
              <div class="font-terminal text-xl tabular-nums" :class="winRate > 60 ? 'text-crt-green' : winRate > 40 ? 'text-crt-amber' : 'text-crt-red'">{{ winRate }}%</div>
              <div class="font-mono text-[8px] text-retro-muted tracking-wider">WIN RATE</div>
            </div>
          </div>
        </aside>

        <section class="space-y-3 sm:space-y-4 min-w-0">
          <div v-if="!isOwnProfile && auth.user.value" class="rounded-xl p-3 sm:p-4"
               style="background:#10121b;border:1.5px solid #252738;">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
              <div class="min-w-0">
                <div class="font-pixel text-[8px] tracking-[0.15em]"
                     :class="friendshipStatus === 'accepted' ? 'text-crt-green' : friendshipStatus === 'pending' ? 'text-crt-amber' : 'text-crt-cyan'">
                  {{
                    friendshipStatus === 'accepted'
                      ? 'FRIEND CONNECTED'
                      : friendshipStatus === 'pending' && friendshipDirection === 'sent'
                        ? 'REQUEST PENDING'
                        : friendshipStatus === 'pending' && friendshipDirection === 'received'
                          ? 'REQUEST RECEIVED'
                          : 'NOT FRIENDS YET'
                  }}
                </div>
                <p class="font-mono text-[10px] text-retro-muted mt-1">
                  {{
                    friendshipStatus === 'accepted'
                      ? 'You can invite this player to a direct 1v1.'
                      : friendshipStatus === 'pending' && friendshipDirection === 'sent'
                        ? 'Waiting for this player to accept your friend request.'
                        : friendshipStatus === 'pending' && friendshipDirection === 'received'
                          ? 'This player sent you a friend request.'
                          : 'Add this player to compare stats and send direct invites.'
                  }}
                </p>
              </div>

              <div class="flex flex-wrap items-center gap-2">
                <template v-if="friendshipStatus === 'accepted'">
                  <button @click="inviteTo1v1"
                          class="flex items-center gap-1 px-3 py-1.5 rounded-lg font-pixel text-[7px] tracking-wider transition-all duration-200"
                          style="border: 1.5px solid rgba(180,76,255,0.4); color: #b44cff; background: rgba(180,76,255,0.06);">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                    INVITE 1v1
                  </button>
                  <button @click="handleRemoveFriend" class="btn-retro-ghost px-3 py-1.5 font-mono text-[9px] text-crt-red/70 hover:text-crt-red">REMOVE</button>
                </template>
                <template v-else-if="friendshipStatus === 'pending' && friendshipDirection === 'sent'">
                  <span class="font-mono text-[10px] text-crt-amber px-2.5 py-1 rounded-lg"
                        style="background: rgba(255,191,0,0.08); border: 1px solid rgba(255,191,0,0.25);">
                    REQUEST SENT
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
              </div>
            </div>
          </div>

          <div class="rounded-xl p-1 flex items-center gap-1 overflow-x-auto"
               style="background:#10121b;border:1px solid #252738;">
            <button v-for="tab in availableTabs" :key="tab.id"
                    @click="activeTab = tab.id"
                    class="flex items-center gap-1.5 px-3 py-2 rounded-lg font-pixel text-[8px] tracking-wider transition-all duration-200 shrink-0 touch-manipulation"
                    :class="activeTab === tab.id
                      ? 'text-crt-green bg-crt-green/8 border border-crt-green/25'
                      : 'text-retro-muted hover:text-crt-white border border-transparent'">
              <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" v-html="tab.svgIcon"></svg>
              <span>{{ tab.label }}</span>
              <span v-if="tab.badge" class="font-mono text-[8px] px-1 py-0.5 rounded bg-crt-red/20 text-crt-red">{{ tab.badge }}</span>
            </button>
          </div>

          <div v-if="activeTab === 'stats'" class="space-y-3 animate-fade-in">
            <div v-if="hasDetailedStats" class="grid grid-cols-1 xl:grid-cols-2 gap-3">
              <div v-for="(modeStats, modeId) in profileDetailedStats.modes" :key="modeId"
                   class="rounded-xl overflow-hidden" style="background: linear-gradient(180deg, #0f111a, #0b0c13); border: 1.5px solid #252738;">
                <div class="px-4 py-3 border-b border-retro-border/20 flex items-center gap-2">
                  <div class="w-1 h-4 rounded-full bg-crt-cyan"></div>
                  <h3 class="font-pixel text-[8px] text-crt-cyan tracking-[0.2em]">{{ getModeLabel(modeId) }}</h3>
                  <span class="font-mono text-[9px] text-retro-muted ml-auto">{{ modeStats.gamesPlayed || 0 }} played</span>
                </div>
                <div class="p-4">
                  <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-lg p-2 text-center" style="background:#12131c;border:1px solid #252738;">
                      <div class="font-terminal text-lg text-crt-green">{{ modeStats.gamesWon }}</div>
                      <div class="font-mono text-[8px] text-retro-muted">WON</div>
                    </div>
                    <div class="rounded-lg p-2 text-center" style="background:#12131c;border:1px solid #252738;">
                      <div class="font-terminal text-lg text-crt-red">{{ modeStats.gamesLost || 0 }}</div>
                      <div class="font-mono text-[8px] text-retro-muted">LOST</div>
                    </div>
                    <div class="rounded-lg p-2 text-center" style="background:#12131c;border:1px solid #252738;">
                      <div class="font-terminal text-lg text-crt-amber">{{ modeStats.bestClicks ?? '-' }}</div>
                      <div class="font-mono text-[8px] text-retro-muted">BEST CLICKS</div>
                    </div>
                    <div class="rounded-lg p-2 text-center" style="background:#12131c;border:1px solid #252738;">
                      <div class="font-terminal text-lg text-crt-cyan">{{ formatTime(modeStats.bestTime) }}</div>
                      <div class="font-mono text-[8px] text-retro-muted">BEST TIME</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div v-if="hasGenreStats" class="rounded-xl overflow-hidden" style="background: linear-gradient(180deg, #0f111a, #0b0c13); border: 1.5px solid #252738;">
              <div class="px-4 py-3 border-b border-retro-border/20 flex items-center gap-2">
                <div class="w-1 h-4 rounded-full bg-crt-amber"></div>
                <h3 class="font-pixel text-[8px] text-crt-amber tracking-[0.2em]">GENRE BREAKDOWN</h3>
              </div>
              <div class="p-3 sm:p-4 space-y-2.5">
                <div v-for="row in genreBreakdownRows" :key="row.id"
                     class="rounded-lg p-3 border transition-all hover:bg-retro-surface/10"
                     style="background:#12131c;border-color:rgba(37,39,56,0.85);">
                  <div class="flex items-center justify-between gap-2 mb-2">
                    <div class="flex items-center gap-2 min-w-0">
                      <div class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0"
                           style="background:rgba(0,229,255,0.08);border:1px solid rgba(0,229,255,0.2);">
                        <svg class="w-4 h-4 text-crt-cyan/70" fill="none" stroke="currentColor" viewBox="0 0 24 24" v-html="row.svgIcon"></svg>
                      </div>
                      <div class="min-w-0">
                        <div class="font-mono text-[11px] text-crt-white truncate">{{ row.label }}</div>
                        <div class="font-mono text-[9px] text-retro-muted">
                          {{ row.played }} played · {{ row.wins }}W / {{ row.losses }}L
                        </div>
                      </div>
                    </div>
                    <span class="font-terminal text-sm shrink-0"
                          :class="row.winRate >= 60 ? 'text-crt-green' : row.winRate >= 40 ? 'text-crt-amber' : 'text-crt-red'">
                      {{ row.winRate }}%
                    </span>
                  </div>
                  <div class="h-1.5 rounded-full overflow-hidden" style="background: rgba(255,255,255,0.05);">
                    <div class="h-full rounded-full transition-all duration-500"
                         :class="row.winRate >= 60 ? 'bg-crt-green/70' : row.winRate >= 40 ? 'bg-crt-amber/70' : 'bg-crt-red/70'"
                         :style="`width:${row.winRate}%`"></div>
                  </div>
                </div>
              </div>
            </div>

            <div class="rounded-xl overflow-hidden" style="background: linear-gradient(180deg, #0f111a, #0b0c13); border: 1.5px solid #252738;">
              <div class="px-4 py-3 border-b border-retro-border/20 flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <div class="w-1 h-4 rounded-full bg-arcade-gold"></div>
                  <h3 class="font-pixel text-[8px] text-arcade-gold tracking-[0.2em]">ACHIEVEMENTS</h3>
                </div>
                <span class="font-terminal text-sm text-arcade-gold">{{ visibleAchievementsUnlockedCount }} / {{ visibleAchievementsTotalCount }}</span>
              </div>
              <div class="p-4">
                <div class="mb-3">
                  <div class="flex items-center justify-between mb-1">
                    <span class="font-mono text-[10px] text-retro-muted">Completion</span>
                    <span class="font-mono text-[10px] text-arcade-gold">{{ achievementProgressPercent }}%</span>
                  </div>
                  <div class="h-2 rounded-full overflow-hidden" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08);">
                    <div class="h-full rounded-full transition-all duration-500"
                         :style="`width:${achievementProgressPercent}%; background: linear-gradient(90deg, rgba(255,191,0,0.75), rgba(255,215,0,0.95));`"></div>
                  </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                  <div v-for="badge in visibleAchievements" :key="badge.id"
                       class="rounded-lg p-3 border transition-all duration-200"
                       :class="badge.unlocked ? 'bg-arcade-gold/[0.08] border-arcade-gold/30' : 'bg-[#12131c] border-retro-border/30'"
                       :title="badge.unlocked ? badge.description : '???'">
                    <div class="flex items-start gap-2.5">
                      <div class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0"
                           :style="badge.unlocked ? 'background: rgba(255,215,0,0.14); border: 1px solid rgba(255,215,0,0.3);' : 'background: rgba(85,87,112,0.18); border: 1px solid rgba(85,87,112,0.35);'">
                        <svg class="w-5 h-5" :class="badge.unlocked ? 'text-arcade-gold' : 'text-retro-muted/40'" fill="none" stroke="currentColor" viewBox="0 0 24 24" v-html="achievementSvgPath(badge.category)"></svg>
                      </div>
                      <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-2">
                          <div class="font-pixel text-[7px] tracking-wider truncate" :class="badge.unlocked ? 'text-arcade-gold' : 'text-retro-muted/55'">
                            {{ badge.unlocked ? badge.name : 'LOCKED ACHIEVEMENT' }}
                          </div>
                          <span class="font-mono text-[8px] shrink-0"
                                :class="badge.unlocked ? 'text-crt-green' : 'text-retro-muted/50'">
                            {{ badge.unlocked ? `+${badge.xp} XP` : 'LOCKED' }}
                          </span>
                        </div>
                        <p class="font-mono text-[10px] leading-snug mt-1"
                           :class="badge.unlocked ? 'text-retro-light/80' : 'text-retro-muted/50'">
                          {{ badge.unlocked ? badge.description : 'Unlock by improving your game stats and exploring more modes.' }}
                        </p>
                        <div class="mt-1.5 font-mono text-[8px] uppercase tracking-wider"
                             :class="badge.unlocked ? 'text-arcade-gold/80' : 'text-retro-muted/45'">
                          {{ achievementCategoryLabel(badge.category) }}
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div v-if="!hasDetailedStats && !hasGenreStats" class="rounded-xl p-8 text-center" style="background:#10121b;border:1.5px solid #252738;">
              <div class="font-pixel text-[8px] text-retro-muted/50 mb-2">NO STATS YET</div>
              <p class="font-mono text-xs text-retro-muted/30">{{ isOwnProfile ? 'Play some games to see your stats here' : 'This player has not played any games yet' }}</p>
            </div>
          </div>

          <div v-if="activeTab === 'friends'" class="space-y-3 animate-fade-in">
            <div v-if="isOwnProfile" class="rounded-2xl p-4 sm:p-5" style="background:linear-gradient(180deg,#10121b,#0d0f17);border:1.5px solid #252738;box-shadow:0 10px 24px rgba(0,0,0,0.25);">
              <div class="flex items-center gap-2 mb-3">
                <div class="w-1 h-4 rounded-full bg-crt-cyan"></div>
                <span class="font-pixel text-[8px] text-crt-cyan tracking-[0.2em]">ADD FRIEND</span>
              </div>
              <div class="flex flex-col sm:flex-row gap-2">
                <div class="flex-1 relative">
                  <svg class="w-4 h-4 text-retro-muted/50 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M10.5 18a7.5 7.5 0 110-15 7.5 7.5 0 010 15z" />
                  </svg>
                  <input v-model="friendSearchQuery" type="text" placeholder="Search by username..."
                         @input="debouncedSearch" @focus="showSearchResults = true"
                         class="w-full pl-9 pr-3 py-2 rounded-lg font-mono text-sm bg-[#12131c] border border-retro-border text-crt-white focus:border-crt-cyan focus:outline-none" />
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
                        class="btn-retro-primary px-4 py-2 font-pixel text-[7px] w-full sm:w-auto">
                  {{ friendsComposable.loading.value ? '...' : 'ADD' }}
                </button>
              </div>
              <div v-if="friendError" class="font-mono text-[11px] text-crt-red mt-2">{{ friendError }}</div>
              <div v-if="friendSuccess" class="font-mono text-[11px] text-crt-green mt-2">{{ friendSuccess }}</div>
            </div>

            <div v-if="isOwnProfile && friendsComposable.incomingRequests.value.length > 0"
                 class="rounded-2xl overflow-hidden" style="background:linear-gradient(180deg,#10121b,#0d0f17);border:1.5px solid rgba(255,191,0,0.25);box-shadow:0 10px 24px rgba(0,0,0,0.22);">
              <div class="px-4 py-3 border-b border-retro-border/20 flex items-center gap-2">
                <div class="w-1 h-4 rounded-full bg-crt-amber"></div>
                <span class="font-pixel text-[8px] text-crt-amber tracking-[0.2em]">PENDING REQUESTS</span>
                <span class="font-mono text-[9px] text-crt-amber/60">{{ friendsComposable.incomingRequests.value.length }}</span>
              </div>
              <div class="divide-y divide-retro-border/10">
                <div v-for="req in friendsComposable.incomingRequests.value" :key="req.request_id"
                     class="px-4 py-3.5 flex items-center justify-between gap-2 hover:bg-retro-surface/10 transition-colors">
                  <router-link :to="`/profile/${req.username}`" class="font-mono text-[11px] text-crt-white hover:text-crt-cyan transition-colors truncate">{{ req.username }}</router-link>
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

            <div class="rounded-2xl overflow-hidden" style="background:linear-gradient(180deg,#10121b,#0d0f17);border:1.5px solid #252738;box-shadow:0 12px 28px rgba(0,0,0,0.24);">
              <div class="px-4 py-3 border-b border-retro-border/20 flex items-center gap-2">
                <div class="w-1 h-4 rounded-full bg-crt-green"></div>
                <span class="font-pixel text-[8px] text-crt-green tracking-[0.2em]">FRIENDS</span>
                <span class="font-mono text-[9px] text-retro-muted">{{ visibleFriends.length }}</span>
                <span class="ml-auto font-mono text-[9px] text-retro-muted/70">{{ isOwnProfile ? 'Manage your squad' : 'Player connections' }}</span>
              </div>
              <div v-if="visibleFriends.length > 0" class="p-2 sm:p-3 space-y-2">
                <div v-for="friend in visibleFriends" :key="friend.friendship_id"
                     class="px-3 py-2.5 rounded-xl flex items-center justify-between gap-2 hover:bg-retro-surface/10 transition-colors border border-retro-border/20">
                  <router-link :to="`/profile/${friend.username}`" class="min-w-0 flex items-center gap-2.5 flex-1">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0"
                         style="background:#0d0e15;border:1px solid rgba(57,255,20,0.2);box-shadow:inset 0 0 12px rgba(0,0,0,0.35);">
                      <svg class="w-4.5 h-4.5 text-crt-cyan" fill="none" stroke="currentColor" viewBox="0 0 24 24" v-html="friendProfileIcon(friend).svgPath"></svg>
                    </div>
                    <div class="min-w-0">
                      <div class="font-mono text-[11px] text-crt-white truncate hover:text-crt-cyan transition-colors">{{ friend.username }}</div>
                      <div class="flex items-center gap-1.5 mt-0.5">
                        <span class="font-mono text-[9px] px-1.5 py-0.5 rounded text-crt-green" style="background: rgba(57,255,20,0.08); border: 1px solid rgba(57,255,20,0.2);">
                          {{ Number(friend.vs_wins || 0) }}W
                        </span>
                        <span class="font-mono text-[9px] px-1.5 py-0.5 rounded text-crt-red/80" style="background: rgba(255,68,68,0.08); border: 1px solid rgba(255,68,68,0.2);">
                          {{ Number(friend.vs_losses || 0) }}L
                        </span>
                      </div>
                    </div>
                  </router-link>
                  <div class="flex items-center gap-1.5 shrink-0">
                    <button v-if="isOwnProfile" @click="inviteFriendTo1v1(friend.username)" title="Invite to 1v1"
                            class="px-2 py-1.5 rounded-lg transition-all flex items-center gap-1"
                            style="border: 1px solid rgba(180,76,255,0.3); background: rgba(180,76,255,0.06);">
                      <svg class="w-3.5 h-3.5 text-arcade-purple" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                      </svg>
                      <span class="hidden sm:inline font-pixel text-[7px] tracking-wider text-arcade-purple">1V1</span>
                    </button>
                    <button v-if="isOwnProfile" @click="handleRemoveFriendById(friend.friendship_id)" title="Remove friend"
                            class="p-1.5 rounded-lg transition-all"
                            style="border: 1px solid rgba(255,68,68,0.2); background: rgba(255,68,68,0.04);">
                      <svg class="w-3.5 h-3.5 text-crt-red/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                      </svg>
                    </button>
                  </div>
                </div>
              </div>
              <div v-else class="px-4 py-8 text-center">
                <div class="w-12 h-12 rounded-xl mx-auto mb-3 flex items-center justify-center" style="background: rgba(57,255,20,0.05); border: 1px solid rgba(57,255,20,0.2);">
                  <svg class="w-6 h-6 text-crt-green/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a3 3 0 00-5.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2a5 5 0 0110 0v2M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                </div>
                <div class="font-pixel text-[8px] text-retro-muted/70 mb-2 tracking-wider">NO FRIENDS YET</div>
                <p class="font-mono text-xs text-retro-muted/40">{{ isOwnProfile ? 'Search for players above to add friends' : 'This player has not added any friends yet' }}</p>
              </div>
            </div>
          </div>

          <div v-if="activeTab === 'costumization'" class="space-y-3 animate-fade-in">
            <div class="rounded-xl overflow-hidden" style="background:#10121b;border:1.5px solid #252738;">
              <div class="px-5 py-3.5 border-b border-retro-border/20">
                <div class="flex items-center gap-2">
                  <div class="w-1 h-4 rounded-full bg-crt-green"></div>
                  <span class="font-pixel text-[8px] text-crt-green tracking-[0.2em]">PROFILE ICON</span>
                  <span class="font-mono text-[9px] text-retro-muted ml-auto">LV {{ progression.level.value }}</span>
                </div>
              </div>
              <div class="p-4">
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5">
                  <button
                    v-for="icon in PROFILE_ICON_OPTIONS"
                    :key="icon.id"
                    @click="selectProfileIcon(icon)"
                    :disabled="progression.level.value < icon.minLevel"
                    class="rounded-lg p-2.5 border transition-all text-left"
                    :class="[
                      selectedProfileIconId === icon.id ? 'border-crt-green/45 bg-crt-green/10' : 'border-retro-border/30 bg-[#12131c]',
                      progression.level.value < icon.minLevel ? 'opacity-45 cursor-not-allowed' : 'hover:border-crt-cyan/40 hover:bg-retro-surface/20',
                    ]"
                  >
                    <div class="flex items-center gap-2">
                      <div class="w-9 h-9 rounded-lg flex items-center justify-center" style="background:#0d0e15;border:1px solid rgba(255,255,255,0.08);">
                        <svg class="w-5 h-5 text-crt-cyan" fill="none" stroke="currentColor" viewBox="0 0 24 24" v-html="icon.svgPath"></svg>
                      </div>
                      <div class="min-w-0">
                        <div class="font-pixel text-[7px] tracking-wider text-crt-white truncate">{{ icon.name }}</div>
                        <div class="font-mono text-[9px]" :class="progression.level.value >= icon.minLevel ? 'text-crt-green' : 'text-retro-muted'">
                          {{ progression.level.value >= icon.minLevel ? 'UNLOCKED' : `UNLOCKS AT LV ${icon.minLevel}` }}
                        </div>
                      </div>
                    </div>
                  </button>
                </div>
              </div>
            </div>

            <div class="rounded-xl overflow-hidden" style="background:#10121b;border:1.5px solid #252738;">
              <div class="px-5 py-3.5 border-b border-retro-border/20">
                <div class="flex items-center gap-2">
                  <div class="w-1 h-4 rounded-full bg-crt-cyan"></div>
                  <span class="font-pixel text-[8px] text-crt-cyan tracking-[0.2em]">PROFILE ACCENT</span>
                  <span class="font-mono text-[9px] text-retro-muted ml-auto">LV {{ progression.level.value }}</span>
                </div>
              </div>
              <div class="p-4">
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5">
                  <button
                    v-for="accent in PROFILE_ACCENT_OPTIONS"
                    :key="accent.id"
                    @click="selectProfileAccent(accent)"
                    :disabled="progression.level.value < accent.minLevel"
                    class="rounded-lg p-2.5 border transition-all text-left"
                    :class="[
                      selectedProfileAccentId === accent.id ? 'border-crt-cyan/45 bg-crt-cyan/10' : 'border-retro-border/30 bg-[#12131c]',
                      progression.level.value < accent.minLevel ? 'opacity-45 cursor-not-allowed' : 'hover:border-crt-cyan/40 hover:bg-retro-surface/20',
                    ]"
                  >
                    <div class="flex items-center gap-2">
                      <div class="w-9 h-9 rounded-lg flex items-center justify-center" style="background:#0d0e15;border:1px solid rgba(255,255,255,0.08);">
                        <div v-if="accent.color" class="w-5 h-5 rounded-full border border-white/15" :style="`background:${accent.color};`"></div>
                        <svg v-else class="w-5 h-5 text-retro-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                      </div>
                      <div class="min-w-0">
                        <div class="font-pixel text-[7px] tracking-wider text-crt-white truncate">{{ accent.name }}</div>
                        <div class="font-mono text-[9px]" :class="progression.level.value >= accent.minLevel ? 'text-crt-green' : 'text-retro-muted'">
                          {{ progression.level.value >= accent.minLevel ? 'UNLOCKED' : `UNLOCKS AT LV ${accent.minLevel}` }}
                        </div>
                      </div>
                    </div>
                  </button>
                </div>
              </div>
            </div>

            <div class="rounded-xl overflow-hidden" style="background:#10121b;border:1.5px solid #252738;">
              <div class="px-5 py-3.5 border-b border-retro-border/20">
                <div class="flex items-center gap-2">
                  <div class="w-1 h-4 rounded-full bg-arcade-gold"></div>
                  <span class="font-pixel text-[8px] text-arcade-gold tracking-[0.2em]">PLAYER TITLE</span>
                </div>
              </div>
              <div class="p-4 grid grid-cols-2 sm:grid-cols-3 gap-2.5">
                <button
                  v-for="title in PROFILE_TITLE_OPTIONS"
                  :key="title.id"
                  @click="selectProfileTitle(title)"
                  :disabled="progression.level.value < title.minLevel"
                  class="rounded-lg p-2 border transition-all text-left"
                  :class="[
                    selectedProfileTitleId === title.id ? 'border-arcade-gold/45 bg-arcade-gold/10' : 'border-retro-border/30 bg-[#12131c]',
                    progression.level.value < title.minLevel ? 'opacity-45 cursor-not-allowed' : 'hover:border-arcade-gold/40',
                  ]"
                >
                  <div class="font-pixel text-[7px] tracking-wider text-crt-white truncate">{{ title.name }}</div>
                  <div class="font-mono text-[9px]" :class="progression.level.value >= title.minLevel ? 'text-crt-green' : 'text-retro-muted'">
                    {{ progression.level.value >= title.minLevel ? 'UNLOCKED' : `LV ${title.minLevel}` }}
                  </div>
                </button>
              </div>
            </div>

            <div class="rounded-xl overflow-hidden" style="background:#10121b;border:1.5px solid #252738;">
              <div class="px-5 py-3.5 border-b border-retro-border/20">
                <div class="flex items-center gap-2">
                  <div class="w-1 h-4 rounded-full bg-arcade-purple"></div>
                  <span class="font-pixel text-[8px] text-arcade-purple tracking-[0.2em]">BANNER STYLE</span>
                </div>
              </div>
              <div class="p-4 grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                <button
                  v-for="banner in PROFILE_BANNER_OPTIONS"
                  :key="banner.id"
                  @click="selectProfileBanner(banner)"
                  :disabled="progression.level.value < banner.minLevel"
                  class="rounded-lg p-2 border transition-all text-left"
                  :class="[
                    selectedProfileBannerId === banner.id ? 'border-arcade-purple/50 bg-arcade-purple/10' : 'border-retro-border/30 bg-[#12131c]',
                    progression.level.value < banner.minLevel ? 'opacity-45 cursor-not-allowed' : 'hover:border-arcade-purple/40',
                  ]"
                >
                  <div class="h-7 rounded mb-1.5 border border-white/10" :style="`background:${banner.css.replaceAll('var(--accent)', profileAccentColor)};`"></div>
                  <div class="font-pixel text-[7px] tracking-wider text-crt-white truncate">{{ banner.name }}</div>
                  <div class="font-mono text-[9px]" :class="progression.level.value >= banner.minLevel ? 'text-crt-green' : 'text-retro-muted'">
                    {{ progression.level.value >= banner.minLevel ? 'UNLOCKED' : `LV ${banner.minLevel}` }}
                  </div>
                </button>
              </div>
            </div>

            <div class="rounded-xl overflow-hidden" style="background:#10121b;border:1.5px solid #252738;">
              <div class="px-5 py-3.5 border-b border-retro-border/20">
                <div class="flex items-center gap-2">
                  <div class="w-1 h-4 rounded-full bg-crt-amber"></div>
                  <span class="font-pixel text-[8px] text-crt-amber tracking-[0.2em]">NAMEPLATE BORDER</span>
                </div>
              </div>
              <div class="p-4 grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                <button
                  v-for="border in PROFILE_NAMEPLATE_BORDER_OPTIONS"
                  :key="border.id"
                  @click="selectProfileNameplateBorder(border)"
                  :disabled="progression.level.value < border.minLevel"
                  class="rounded-lg p-2 border transition-all text-left"
                  :class="[
                    selectedProfileNameplateBorderId === border.id ? 'border-crt-amber/50 bg-crt-amber/10' : 'border-retro-border/30 bg-[#12131c]',
                    progression.level.value < border.minLevel ? 'opacity-45 cursor-not-allowed' : 'hover:border-crt-amber/40',
                  ]"
                >
                  <div class="h-7 rounded mb-1.5 flex items-center justify-center font-pixel text-[8px] text-crt-white"
                       :style="`background:#0d0e15;border:${border.css.replaceAll('var(--accent)', profileAccentColor)};`">
                    A
                  </div>
                  <div class="font-pixel text-[7px] tracking-wider text-crt-white truncate">{{ border.name }}</div>
                  <div class="font-mono text-[9px]" :class="progression.level.value >= border.minLevel ? 'text-crt-green' : 'text-retro-muted'">
                    {{ progression.level.value >= border.minLevel ? 'UNLOCKED' : `LV ${border.minLevel}` }}
                  </div>
                </button>
              </div>
            </div>

            <div class="rounded-xl overflow-hidden" style="background:#10121b;border:1.5px solid #252738;">
              <div class="px-5 py-3.5 border-b border-retro-border/20">
                <div class="flex items-center gap-2">
                  <div class="w-1 h-4 rounded-full bg-crt-green"></div>
                  <span class="font-pixel text-[8px] text-crt-green tracking-[0.2em]">ACHIEVEMENT PIN</span>
                </div>
              </div>
              <div class="p-4">
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5">
                  <button
                    @click="selectPinnedBadge('')"
                    class="rounded-lg p-2 border transition-all text-left"
                    :class="selectedPinnedBadgeId === '' ? 'border-crt-green/50 bg-crt-green/10' : 'border-retro-border/30 bg-[#12131c] hover:border-crt-green/35'"
                  >
                    <div class="font-pixel text-[7px] tracking-wider text-crt-white">NO PIN</div>
                  </button>
                  <button
                    v-for="badge in ownUnlockedAchievementOptions"
                    :key="badge.id"
                    @click="selectPinnedBadge(badge.id)"
                    class="rounded-lg p-2 border transition-all text-left"
                    :class="selectedPinnedBadgeId === badge.id ? 'border-crt-green/50 bg-crt-green/10' : 'border-retro-border/30 bg-[#12131c] hover:border-crt-green/35'"
                  >
                    <div class="flex items-center gap-1.5">
                      <svg class="w-3.5 h-3.5 text-arcade-gold shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" v-html="achievementSvgPath(badge.category)"></svg>
                      <div class="font-pixel text-[7px] tracking-wider text-crt-white truncate">{{ badge.name }}</div>
                    </div>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div v-if="activeTab === 'settings'" class="space-y-3 animate-fade-in">
            <div class="rounded-xl overflow-hidden" style="background:#10121b;border:1.5px solid #252738;">
              <button @click="showChangePassword = !showChangePassword"
                      class="w-full px-5 py-3.5 flex items-center justify-between hover:bg-retro-surface/30 transition-colors">
                <div class="flex items-center gap-2">
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

            <div class="rounded-xl overflow-hidden" style="background:#10121b;border:1.5px solid rgba(255,68,68,0.2);">
              <button @click="showDeleteSection = !showDeleteSection"
                      class="w-full px-5 py-3.5 flex items-center justify-between hover:bg-crt-red/5 transition-colors">
                <div class="flex items-center gap-2">
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
                          style="background: linear-gradient(180deg, #ff4444, #cc2222); border: 1.5px solid #ff4444; color: white;">
                    {{ deleting ? 'DELETING...' : 'PERMANENTLY DELETE ACCOUNT' }}
                  </button>
                </form>
              </div>
            </div>
          </div>
        </section>
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

    <HomeAuthModal
      :open="showAuthModal"
      :mode="authMode"
      :username="authUsername"
      :password="authPassword"
      :confirm-password="authConfirmPassword"
      :show-password="authShowPassword"
      :show-confirm-password="authShowConfirmPassword"
      :error="authError"
      :loading="auth.loading.value"
      @close="closeAuthModal"
      @set-mode="authMode = $event"
      @submit="submitAuth"
      @update:username="authUsername = $event"
      @update:password="authPassword = $event"
      @update:confirm-password="authConfirmPassword = $event"
      @toggle-password="authShowPassword = !authShowPassword"
      @toggle-confirm-password="authShowConfirmPassword = !authShowConfirmPassword"
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
import {
  PROFILE_ICON_OPTIONS,
  PROFILE_ACCENT_OPTIONS,
  PROFILE_TITLE_OPTIONS,
  PROFILE_BANNER_OPTIONS,
  PROFILE_NAMEPLATE_BORDER_OPTIONS,
  getProfileIconById,
  getProfileAccentById,
  getProfileTitleById,
  getProfileBannerById,
  getProfileNameplateBorderById,
} from '../constants/profileIcons'
import SiteTopNav from '../components/layout/SiteTopNav.vue'
import HomeAuthModal from '../components/home/HomeAuthModal.vue'
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
const showAuthModal = ref(false)
const authMode = ref('login')
const authUsername = ref('')
const authPassword = ref('')
const authConfirmPassword = ref('')
const authError = ref('')
const authShowPassword = ref(false)
const authShowConfirmPassword = ref(false)

const profileData = ref({
  username: '',
  profile_icon: 'rookie',
  profile_accent: 'rank',
  profile_title: 'newcomer',
  profile_banner: 'default',
  profile_nameplate_border: 'default',
  profile_pinned_badge: '',
  created_at: '',
})
const profileServerStats = ref({ modes: {}, genres: {} })
const profileStreak = ref(0)
const profileFriends = ref([])
const profileAchievementIds = ref([])
const friendshipStatus = ref('none')
const friendshipDirection = ref(null)
const friendshipId = ref(null)
const friendActionLoading = ref(false)

const isOwnProfile = computed(() => {
  if (!props.username) return true
  return auth.user.value && auth.user.value.username.toLowerCase() === props.username.toLowerCase()
})

const profileInitial = computed(() => (profileData.value.username || '?').charAt(0).toUpperCase())
const playerLevel = computed(() => (isOwnProfile.value ? progression.level.value : 1))
const selectedProfileIconId = computed(() => profileData.value.profile_icon || 'rookie')
const unlockedProfileIcons = computed(() => PROFILE_ICON_OPTIONS.filter(icon => playerLevel.value >= icon.minLevel))
const selectedProfileAccentId = computed(() => profileData.value.profile_accent || 'rank')
const unlockedProfileAccents = computed(() => PROFILE_ACCENT_OPTIONS.filter(accent => playerLevel.value >= accent.minLevel))
const selectedProfileTitleId = computed(() => profileData.value.profile_title || 'newcomer')
const selectedProfileBannerId = computed(() => profileData.value.profile_banner || 'default')
const selectedProfileNameplateBorderId = computed(() => profileData.value.profile_nameplate_border || 'default')
const selectedPinnedBadgeId = computed(() => profileData.value.profile_pinned_badge || '')
const unlockedProfileTitles = computed(() => PROFILE_TITLE_OPTIONS.filter(title => playerLevel.value >= title.minLevel))
const unlockedProfileBanners = computed(() => PROFILE_BANNER_OPTIONS.filter(banner => playerLevel.value >= banner.minLevel))
const unlockedProfileNameplateBorders = computed(() => PROFILE_NAMEPLATE_BORDER_OPTIONS.filter(border => playerLevel.value >= border.minLevel))
const selectedProfileIcon = computed(() => {
  const icon = getProfileIconById(selectedProfileIconId.value)
  if (!isOwnProfile.value) return icon
  return playerLevel.value >= icon.minLevel ? icon : unlockedProfileIcons.value[0] || PROFILE_ICON_OPTIONS[0]
})
const selectedProfileTitle = computed(() => {
  const title = getProfileTitleById(selectedProfileTitleId.value)
  return playerLevel.value >= title.minLevel ? title : unlockedProfileTitles.value[0] || PROFILE_TITLE_OPTIONS[0]
})
const selectedProfileBanner = computed(() => {
  const banner = getProfileBannerById(selectedProfileBannerId.value)
  return playerLevel.value >= banner.minLevel ? banner : unlockedProfileBanners.value[0] || PROFILE_BANNER_OPTIONS[0]
})
const selectedProfileNameplateBorder = computed(() => {
  const border = getProfileNameplateBorderById(selectedProfileNameplateBorderId.value)
  return playerLevel.value >= border.minLevel ? border : unlockedProfileNameplateBorders.value[0] || PROFILE_NAMEPLATE_BORDER_OPTIONS[0]
})

async function selectProfileIcon(icon) {
  if (!icon || playerLevel.value < icon.minLevel || !isOwnProfile.value) return
  await updateProfileCustomization({ icon: icon.id }, 'Profile icon updated.')
}

async function selectProfileAccent(accent) {
  if (!accent || playerLevel.value < accent.minLevel || !isOwnProfile.value) return
  await updateProfileCustomization({ accent: accent.id }, 'Profile accent updated.')
}

async function selectProfileTitle(title) {
  if (!title || playerLevel.value < title.minLevel || !isOwnProfile.value) return
  await updateProfileCustomization({ title: title.id }, 'Profile title updated.')
}

async function selectProfileBanner(banner) {
  if (!banner || playerLevel.value < banner.minLevel || !isOwnProfile.value) return
  await updateProfileCustomization({ banner: banner.id }, 'Banner style updated.')
}

async function selectProfileNameplateBorder(border) {
  if (!border || playerLevel.value < border.minLevel || !isOwnProfile.value) return
  await updateProfileCustomization({ nameplateBorder: border.id }, 'Nameplate border updated.')
}

async function selectPinnedBadge(badgeId) {
  if (!isOwnProfile.value) return
  await updateProfileCustomization({ pinnedBadge: badgeId || '' }, badgeId ? 'Pinned achievement updated.' : 'Pinned achievement cleared.')
}

async function updateProfileCustomization(payload, successMessage) {
  try {
    const result = await api.post('/profile/customization', payload)
    const patch = {
      profile_icon: result.profile_icon || profileData.value.profile_icon || 'rookie',
      profile_accent: result.profile_accent || profileData.value.profile_accent || 'rank',
      profile_title: result.profile_title || profileData.value.profile_title || 'newcomer',
      profile_banner: result.profile_banner || profileData.value.profile_banner || 'default',
      profile_nameplate_border: result.profile_nameplate_border || profileData.value.profile_nameplate_border || 'default',
      profile_pinned_badge: typeof result.profile_pinned_badge === 'string' ? result.profile_pinned_badge : (profileData.value.profile_pinned_badge || ''),
    }
    profileData.value = { ...profileData.value, ...patch }
    auth.setProfileCustomization({
      profile_icon: patch.profile_icon,
      profile_accent: patch.profile_accent,
      profile_title: patch.profile_title,
      profile_banner: patch.profile_banner,
      profile_nameplate_border: patch.profile_nameplate_border,
      profile_pinned_badge: patch.profile_pinned_badge,
    })
    toast.success(successMessage)
  } catch (e) {
    toast.error(e.message || 'Failed to update customization.')
  }
}

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
const visibleFriends = computed(() => (isOwnProfile.value ? friendsComposable.friends.value : profileFriends.value))
const visibleAchievements = computed(() => {
  if (isOwnProfile.value) return achievements.getAllAchievements()
  const unlockedSet = new Set(profileAchievementIds.value)
  return achievements.getAllAchievements().map(a => ({
    ...a,
    unlocked: unlockedSet.has(a.id),
    unlockedAt: null,
  }))
})
const ownUnlockedAchievementOptions = computed(() =>
  achievements.getAllAchievements()
    .filter(a => a.unlocked)
    .map(a => ({ id: a.id, name: a.name, category: a.category }))
)
const pinnedBadge = computed(() => {
  if (!selectedPinnedBadgeId.value) return null
  const match = visibleAchievements.value.find(a => a.id === selectedPinnedBadgeId.value)
  if (!match || !match.unlocked) return null
  return match
})
const visibleAchievementsUnlockedCount = computed(() =>
  visibleAchievements.value.reduce((sum, a) => sum + (a.unlocked ? 1 : 0), 0)
)
const visibleAchievementsTotalCount = computed(() => visibleAchievements.value.length)
const achievementProgressPercent = computed(() => {
  if (!visibleAchievementsTotalCount.value) return 0
  return Math.round((visibleAchievementsUnlockedCount.value / visibleAchievementsTotalCount.value) * 100)
})
const genreBreakdownRows = computed(() => {
  const genres = profileDetailedStats.value.genres || {}
  return Object.entries(genres)
    .map(([id, stats]) => {
      const played = stats?.gamesPlayed || 0
      const wins = stats?.gamesWon || 0
      const losses = stats?.gamesLost || 0
      const winRate = played > 0 ? Math.round((wins / played) * 100) : 0
      return {
        id,
        label: getGenreLabel(id),
        svgIcon: getGenreSvgIcon(id),
        played,
        wins,
        losses,
        winRate,
      }
    })
    .sort((a, b) => b.played - a.played)
})

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
const profileTitleLabel = computed(() => selectedProfileTitle.value.name)
const profileAccentColor = computed(() => {
  const accent = getProfileAccentById(profileData.value.profile_accent)
  return accent.color || rankColor.value
})
const profileBannerStyle = computed(() => {
  const banner = selectedProfileBanner.value
  const css = banner.css.replaceAll('var(--accent)', profileAccentColor.value)
  return `background: ${css};`
})
const nameplateBoxStyle = computed(() => {
  const border = selectedProfileNameplateBorder.value
  const css = border.css.replaceAll('var(--accent)', profileAccentColor.value)
  const glow = border.id === 'glow' ? `box-shadow: 0 0 16px ${profileAccentColor.value}55;` : ''
  return `background:#0d0e15;border:${css};${glow}`
})

function modeWinRate(modeStats) {
  const played = modeStats.gamesPlayed || 0
  if (played === 0) return 0
  return Math.round(((modeStats.gamesWon || 0) / played) * 100)
}

const TAB_SVG_ICONS = {
  stats: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />',
  friends: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />',
  costumization: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 3h10a2 2 0 012 2v4a2 2 0 01-2 2h-3l-2 2-2-2H7a2 2 0 01-2-2V5a2 2 0 012-2z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14h10a2 2 0 012 2v3a2 2 0 01-2 2H7a2 2 0 01-2-2v-3a2 2 0 012-2z" />',
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
    tabs.push({ id: 'costumization', label: 'COSTUMIZATION', svgIcon: TAB_SVG_ICONS.costumization })
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

watch(authMode, () => {
  authError.value = ''
  authConfirmPassword.value = ''
  authShowPassword.value = false
  authShowConfirmPassword.value = false
})

function closeAuthModal() {
  showAuthModal.value = false
  authError.value = ''
  authPassword.value = ''
  authConfirmPassword.value = ''
  authShowPassword.value = false
  authShowConfirmPassword.value = false
}

async function submitAuth() {
  authError.value = ''
  if (authMode.value === 'register' && authPassword.value !== authConfirmPassword.value) {
    authError.value = 'Passwords do not match.'
    return
  }
  const fn = authMode.value === 'login' ? auth.login : auth.register
  const err = await fn(authUsername.value, authPassword.value)
  if (err) {
    authError.value = err
    return
  }
  closeAuthModal()
  authUsername.value = ''
}

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

function achievementCategoryLabel(category) {
  if (!category) return 'General'
  return category.replace(/([A-Z])/g, ' $1').trim()
}

function friendProfileIcon(friend) {
  const rawId = String(
    friend?.profile_icon
    || friend?.icon
    || friend?.friend_profile_icon
    || ''
  ).trim().toLowerCase()

  const legacyMap = {
    shield: 'rookie',
    rookie_shield: 'rookie',
    path_compass: 'compass',
    lightning: 'spark',
    bolt: 'spark',
    pixel_crown: 'crown',
    wiki_star: 'star',
  }

  const resolvedId = legacyMap[rawId] || rawId || 'rookie'
  return getProfileIconById(resolvedId)
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
      profile_icon: auth.user.value.profile_icon || 'rookie',
      profile_accent: auth.user.value.profile_accent || 'rank',
      profile_title: auth.user.value.profile_title || 'newcomer',
      profile_banner: auth.user.value.profile_banner || 'default',
      profile_nameplate_border: auth.user.value.profile_nameplate_border || 'default',
      profile_pinned_badge: auth.user.value.profile_pinned_badge || '',
      created_at: auth.user.value.created_at,
    }
    await Promise.all([
      friendsComposable.fetchFriends(),
      friendsComposable.fetchRequests(),
    ])
    profileFriends.value = [...friendsComposable.friends.value]
    profileAchievementIds.value = []
    pageLoading.value = false
    return
  }

  const data = await friendsComposable.getPublicProfile(props.username)
  if (data.error) {
    notFound.value = true
    pageLoading.value = false
    return
  }

  profileData.value = {
    username: data.username,
    profile_icon: data.profile_icon || 'rookie',
    profile_accent: data.profile_accent || 'rank',
    profile_title: data.profile_title || 'newcomer',
    profile_banner: data.profile_banner || 'default',
    profile_nameplate_border: data.profile_nameplate_border || 'default',
    profile_pinned_badge: data.profile_pinned_badge || '',
    created_at: data.created_at,
  }
  profileServerStats.value = data.stats || { modes: {}, genres: {} }
  profileStreak.value = data.streak || 0
  profileFriends.value = Array.isArray(data.friends) ? data.friends : []
  profileAchievementIds.value = Array.isArray(data.achievements?.unlocked_ids) ? data.achievements.unlocked_ids : []

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
