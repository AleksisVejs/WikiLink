<template>
  <div class="relative min-h-screen flex flex-col">

    <!-- Top bar -->
    <header class="relative z-20 border-b border-retro-border/40 shrink-0">
      <div class="max-w-6xl mx-auto px-4 sm:px-5 py-2 sm:py-3 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="w-9 h-9 rounded-lg flex items-center justify-center font-pixel text-[9px]"
               style="background: linear-gradient(135deg, rgba(57,255,20,0.15), rgba(0,229,255,0.1)); border: 1px solid rgba(57,255,20,0.25); box-shadow: 0 0 12px rgba(57,255,20,0.1);">
            <span class="text-crt-green">W</span>
          </div>
          <span class="font-pixel text-[9px] text-crt-white/80 tracking-wider hidden sm:block">WIKILINK</span>
        </div>
        <div class="flex items-center gap-2">
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
          <button @click="showStats = true" class="btn-retro-ghost flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            <span class="hidden sm:inline">STATS</span>
          </button>
        </div>
      </div>
    </header>

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
          <p class="font-mono text-[11px] sm:text-xs text-retro-muted max-w-sm mx-auto leading-snug px-1">
            Navigate the labyrinth of Wikipedia. Reach the target. Beat the game.
          </p>
          <div class="retro-divider max-w-[200px] sm:max-w-xs mx-auto mt-2 sm:mt-3"></div>
        </div>

        <!-- Daily challenge banner -->
        <div v-if="!dailyCompleted" class="mb-3 sm:mb-4 animate-slide-up">
          <button @click="startDaily"
                  class="w-full flex items-center justify-between gap-3 px-4 py-3 rounded-xl transition-all duration-200 group touch-manipulation"
                  style="background: linear-gradient(135deg, rgba(255,191,0,0.06), rgba(255,107,43,0.04)); border: 1.5px solid rgba(255,191,0,0.25);"
                  @mouseenter="$event.currentTarget.style.borderColor='rgba(255,191,0,0.45)'; $event.currentTarget.style.boxShadow='0 0 20px rgba(255,191,0,0.1)'"
                  @mouseleave="$event.currentTarget.style.borderColor='rgba(255,191,0,0.25)'; $event.currentTarget.style.boxShadow='none'">
            <div class="flex items-center gap-3 min-w-0">
              <span class="text-xl">📅</span>
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
              <span class="text-xl">✅</span>
              <div>
                <div class="font-pixel text-[8px] text-crt-green tracking-[0.15em]">DAILY COMPLETE</div>
                <p class="font-mono text-[10px] text-retro-muted">
                  {{ dailyStatus.clicks }} clicks &middot; {{ formatTime(dailyStatus.time) }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Display frame -->
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

          <!-- Genre section (hidden for Custom — articles are chosen manually) -->
          <div v-if="selectedModeId !== 'custom'" class="p-3 sm:p-4 md:p-5">
            <div class="flex items-center gap-2.5 mb-2.5 sm:mb-3">
              <div class="w-1 h-4 rounded-full bg-crt-amber"></div>
              <span class="font-pixel text-[8px] text-crt-amber tracking-[0.2em]">SELECT GENRE</span>
            </div>
            <div class="flex flex-wrap gap-1.5 sm:gap-2">
              <button
                v-for="g in genres"
                :key="g.id"
                @click="selectedGenreId = g.id; sound.playClick()"
                class="flex items-center gap-1.5 sm:gap-2 px-2.5 sm:px-3 py-1.5 sm:py-2 rounded-lg transition-all duration-200 cursor-pointer touch-manipulation active:scale-95"
                :style="selectedGenreId === g.id
                  ? 'border: 1.5px solid rgba(57,255,20,0.5); background: rgba(57,255,20,0.05); box-shadow: 0 0 10px rgba(57,255,20,0.12), inset 0 0 8px rgba(57,255,20,0.04);'
                  : 'border: 1.5px solid #252738;'"
              >
                <span class="text-sm sm:text-base"
                      :style="selectedGenreId === g.id ? 'filter: drop-shadow(0 0 6px rgba(57,255,20,0.5))' : ''">
                  {{ g.emoji }}
                </span>
                <span class="font-mono text-[10px] sm:text-[11px] transition-colors whitespace-nowrap"
                      :class="selectedGenreId === g.id ? 'text-crt-green' : 'text-retro-muted'">
                  {{ g.shortName }}
                </span>
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
              <button
                v-for="mode in displayModes"
                :key="mode.id"
                @click="selectedModeId = mode.id; sound.playClick()"
                class="mode-card w-full text-left flex items-center gap-2.5 sm:gap-3.5 p-2.5 sm:p-3.5 touch-manipulation active:opacity-90"
                :class="{ selected: selectedModeId === mode.id }"
                :style="selectedModeId === mode.id ? `--mode-color: ${mode.uiColor}; border-color: ${mode.borderHex}` : ''"
              >
                <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-lg flex items-center justify-center text-lg sm:text-xl shrink-0 transition-all duration-200"
                     :style="selectedModeId === mode.id
                       ? `background: ${mode.bgGlow}; box-shadow: 0 0 12px ${mode.shadowColor};`
                       : 'background: #181a25; border: 1px solid #252738;'">
                  <span>{{ mode.emoji }}</span>
                </div>
                <div class="min-w-0 flex-1">
                  <div class="flex items-center gap-2 mb-0.5">
                    <span class="font-pixel text-[7px] sm:text-[8px] tracking-wider transition-colors"
                          :style="selectedModeId === mode.id ? `color: ${mode.uiColor}` : 'color: #d4d4e8'">
                      {{ mode.name.toUpperCase() }}
                    </span>
                    <span v-if="mode.tag"
                          class="font-mono text-[9px] sm:text-[10px] px-1.5 py-0.5 rounded"
                          :style="`color: ${mode.uiColor}; background: ${mode.tagBg}; border: 1px solid ${mode.tagBorder};`">
                      {{ mode.tag }}
                    </span>
                  </div>
                  <p class="font-mono text-[10px] sm:text-[11px] text-retro-muted leading-snug line-clamp-1 sm:line-clamp-2">{{ mode.description }}</p>
                </div>
                <div v-if="selectedModeId === mode.id"
                     class="font-terminal text-lg sm:text-xl animate-blink shrink-0"
                     :style="`color: ${mode.uiColor}`">&#9654;</div>
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
                <button
                  v-for="d in difficulties"
                  :key="d.id"
                  @click="selectedDifficulty = d.id; sound.playClick()"
                  class="py-2 sm:py-2.5 rounded-lg font-pixel text-[7px] sm:text-[8px] tracking-wider transition-all duration-200 touch-manipulation active:scale-95"
                  :style="selectedDifficulty === d.id
                    ? `border: 1.5px solid ${d.color}; background: ${d.bg}; color: ${d.color}; box-shadow: 0 0 12px ${d.shadow};`
                    : 'border: 1.5px solid #252738; color: #555770;'"
                >
                  {{ d.label }}
                  <div class="font-mono text-[9px] mt-0.5 opacity-60">{{ d.detail }}</div>
                </button>
              </div>
              <div v-if="selectedDifficulty === 'custom' && selectedModeId === 'sprint'" class="mt-3 flex flex-col sm:flex-row sm:items-center gap-2">
                <label class="font-mono text-[10px] text-retro-muted shrink-0">Time limit (sec)</label>
                <input v-model.number="customTimeLimit"
                       type="number"
                       :min="LIMIT_BOUNDS.timeMin"
                       :max="LIMIT_BOUNDS.timeMax"
                       class="w-full sm:max-w-[140px] px-2.5 py-1.5 rounded-lg font-mono text-xs bg-[#12131c] border border-retro-border text-crt-white focus:border-crt-cyan focus:outline-none" />
              </div>
              <div v-if="selectedDifficulty === 'custom' && selectedModeId === 'challenge'" class="mt-3 flex flex-col sm:flex-row sm:items-center gap-2">
                <label class="font-mono text-[10px] text-retro-muted shrink-0">Max link clicks</label>
                <input v-model.number="customClickLimit"
                       type="number"
                       :min="LIMIT_BOUNDS.clicksMin"
                       :max="LIMIT_BOUNDS.clicksMax"
                       class="w-full sm:max-w-[140px] px-2.5 py-1.5 rounded-lg font-mono text-xs bg-[#12131c] border border-retro-border text-crt-white focus:border-crt-cyan focus:outline-none" />
              </div>
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
              <span class="text-crt-amber">{{ selectedModeId === 'custom' ? '—' : selectedGenre?.name }}</span>
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
        <span class="font-mono text-[9px] sm:text-[10px] text-retro-muted/40 sm:text-right">v1.1</span>
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
              <!-- Tab switcher -->
              <div class="flex items-center gap-1">
                <button @click="statsTab = 'modes'" class="font-mono text-[10px] px-2 py-1 rounded transition-all"
                        :class="statsTab === 'modes' ? 'text-crt-cyan bg-crt-cyan/10' : 'text-retro-muted hover:text-crt-white'">Modes</button>
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
                <div v-for="(modeStats, modeId) in stats.modes" :key="modeId"
                     class="rounded-lg p-4" style="background: #12131c; border: 1px solid #252738;">
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

            <!-- History tab -->
            <div v-if="statsTab === 'history'">
              <div v-if="history.length" class="space-y-2">
                <div v-for="(entry, idx) in history" :key="idx"
                     class="rounded-lg px-3 py-2.5 flex items-center gap-3"
                     style="background: #12131c; border: 1px solid #252738;">
                  <div class="w-8 h-8 rounded-lg flex items-center justify-center text-sm shrink-0"
                       :style="entry.result === 'won' ? 'background: rgba(57,255,20,0.08); border: 1px solid rgba(57,255,20,0.2);' : entry.result === 'finished' ? 'background: rgba(0,229,255,0.08); border: 1px solid rgba(0,229,255,0.2);' : 'background: rgba(255,68,68,0.08); border: 1px solid rgba(255,68,68,0.2);'">
                    {{ entry.result === 'won' ? '&#10003;' : entry.result === 'finished' ? '&#10148;' : '&#10007;' }}
                  </div>
                  <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2 mb-0.5">
                      <span class="font-pixel text-[6px] tracking-wider"
                            :class="entry.result === 'won' ? 'text-crt-green' : entry.result === 'finished' ? 'text-crt-cyan' : 'text-crt-red'">
                        {{ GAME_MODES[entry.mode]?.name?.toUpperCase() || entry.mode }}
                      </span>
                      <span class="font-mono text-[9px] text-retro-muted/50">{{ formatDate(entry.date) }}</span>
                    </div>
                    <p class="font-mono text-[10px] text-retro-muted truncate">
                      {{ entry.start }}{{ entry.target ? ' -> ' + entry.target : '' }}
                    </p>
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
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useGame } from '../composables/useGame'
import { useSound } from '../composables/useSound'
import { useToast } from '../composables/useToast'
import WikiTitleInput from '../components/WikiTitleInput.vue'

const router = useRouter()
const toast = useToast()
const { GAME_MODES, GENRES, DIFFICULTIES, getStats, getHistory, getDailyStatus, LIMIT_BOUNDS } = useGame()
const sound = useSound()

const showStats = ref(false)
const statsTab = ref('modes')
const selectedGenreId = ref('random')
const selectedModeId = ref('classic')
const selectedDifficulty = ref('normal')
const customTimeLimit = ref(120)
const customClickLimit = ref(6)
const customStartTitle = ref('')
const customEndTitle = ref('')

const genres = Object.values(GENRES)

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
  freeplay:  { uiColor: '#39ff14', borderHex: 'rgba(57,255,20,0.35)',   bgGlow: 'rgba(57,255,20,0.1)',    shadowColor: 'rgba(57,255,20,0.2)',   tagBg: 'rgba(57,255,20,0.08)',   tagBorder: 'rgba(57,255,20,0.2)' },
  custom:    { uiColor: '#c9a227', borderHex: 'rgba(201,162,39,0.35)',  bgGlow: 'rgba(201,162,39,0.1)',   shadowColor: 'rgba(201,162,39,0.2)',  tagBg: 'rgba(201,162,39,0.08)',  tagBorder: 'rgba(201,162,39,0.2)' },
}

const displayModes = Object.values(GAME_MODES)
  .filter(m => m.id !== 'daily')
  .map(m => {
    const p = modePalette[m.id] || modePalette.classic
    const tag = m.id === 'sprint' ? 'TIMER' : m.id === 'challenge' ? 'CLICK CAP' : null
    return { ...m, ...p, tag }
  })

const selectedGenre = computed(() => genres.find(g => g.id === selectedGenreId.value))
const selectedMode = computed(() => displayModes.find(m => m.id === selectedModeId.value))
const showDifficulty = computed(() => ['sprint', 'challenge'].includes(selectedModeId.value))

const difficulties = computed(() => {
  const mode = selectedModeId.value
  return Object.values(DIFFICULTIES).map(d => {
    let detail = ''
    if (d.id === 'custom') {
      detail = mode === 'sprint' ? 'Your time' : mode === 'challenge' ? 'Your cap' : ''
    } else if (mode === 'sprint') {
      detail = formatLimitMss(d.sprintTime)
    } else if (mode === 'challenge') {
      detail = `${d.challengeClicks} links max`
    }
    const isCustom = d.id === 'custom'
    return {
      ...d,
      detail,
      color: isCustom ? '#c9a227' : d.id === 'easy' ? '#39ff14' : d.id === 'normal' ? '#ffbf00' : '#ff4444',
      bg: isCustom ? 'rgba(201,162,39,0.06)' : d.id === 'easy' ? 'rgba(57,255,20,0.06)' : d.id === 'normal' ? 'rgba(255,191,0,0.06)' : 'rgba(255,68,68,0.06)',
      shadow: isCustom ? 'rgba(201,162,39,0.1)' : d.id === 'easy' ? 'rgba(57,255,20,0.1)' : d.id === 'normal' ? 'rgba(255,191,0,0.1)' : 'rgba(255,68,68,0.1)',
    }
  })
})

const stats = computed(() => getStats())
const hasStats = computed(() => Object.keys(stats.value.modes || {}).length > 0)
const history = computed(() => getHistory())
const dailyStatus = computed(() => getDailyStatus())
const dailyCompleted = computed(() => !!dailyStatus.value?.completed)

function startGame() {
  if (selectedModeId.value === 'custom') {
    const a = customStartTitle.value.trim()
    const b = customEndTitle.value.trim()
    if (!a || !b) {
      toast.warn('Enter both start and target article titles.')
      sound.playClick()
      return
    }
    if (a.toLowerCase() === b.toLowerCase()) {
      toast.warn('Start and target must be different.')
      sound.playClick()
      return
    }
  }

  sound.playStart()
  const query = {}
  if (selectedModeId.value !== 'custom' && selectedGenreId.value !== 'random') {
    query.genre = selectedGenreId.value
  }
  if (showDifficulty.value) {
    if (selectedDifficulty.value !== 'normal') {
      query.difficulty = selectedDifficulty.value
    }
    if (selectedDifficulty.value === 'custom') {
      if (selectedModeId.value === 'sprint') {
        query.timeLimit = String(Math.round(customTimeLimit.value) || LIMIT_BOUNDS.timeMin)
      }
      if (selectedModeId.value === 'challenge') {
        query.clickLimit = String(Math.round(customClickLimit.value) || LIMIT_BOUNDS.clicksMin)
      }
    }
  }
  if (selectedModeId.value === 'custom') {
    query.from = customStartTitle.value.trim()
    query.to = customEndTitle.value.trim()
  }
  router.push({ name: 'game', params: { mode: selectedModeId.value }, query })
}

function startDaily() {
  sound.playStart()
  router.push({ name: 'game', params: { mode: 'daily' } })
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
  if (e.key === 'Enter' && !showStats.value) startGame()
  if (e.key === 'Escape' && showStats.value) showStats.value = false
}

watch(selectedModeId, (id) => {
  if (id === 'custom') {
    selectedDifficulty.value = 'normal'
  }
})

onMounted(() => window.addEventListener('keydown', handleKeydown))
onUnmounted(() => window.removeEventListener('keydown', handleKeydown))
</script>
