<template>
  <Teleport to="body">
    <transition name="fade">
      <div v-if="open" class="fixed inset-0 z-[60] flex items-center justify-center p-3 sm:p-4 max-h-[100dvh]">
        <div class="absolute inset-0 bg-black/85 backdrop-blur-sm" @click="$emit('close')"></div>
        <div class="relative rounded-2xl p-4 sm:p-6 max-w-lg w-full max-h-[min(92dvh,640px)] overflow-y-auto overscroll-contain animate-scale-in" style="background: #0d0e15; border: 1.5px solid rgba(37,39,56,0.7); box-shadow: 0 0 60px rgba(0,0,0,0.8), 0 0 30px rgba(0,229,255,0.04);">
          <div class="flex items-center justify-between mb-3 sm:mb-5 gap-2">
            <div class="flex items-center gap-2.5 shrink-0">
              <div class="w-1 h-4 rounded-full bg-crt-amber"></div>
              <h2 class="font-pixel text-[9px] text-crt-amber tracking-[0.2em]">YOUR STATS</h2>
            </div>
            <button @click="$emit('close')" class="btn-ghost p-1.5 shrink-0">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          <div class="flex items-center gap-1 mb-4 overflow-x-auto pb-1 -mb-1 sm:mb-4 sm:pb-0">
            <button @click="$emit('set-tab', 'modes')" class="font-mono text-[10px] px-2 py-1 rounded transition-all whitespace-nowrap shrink-0" :class="tab === 'modes' ? 'text-crt-cyan bg-crt-cyan/10' : 'text-retro-muted hover:text-crt-white'">Modes</button>
            <button @click="$emit('set-tab', 'genres')" class="font-mono text-[10px] px-2 py-1 rounded transition-all whitespace-nowrap shrink-0" :class="tab === 'genres' ? 'text-crt-cyan bg-crt-cyan/10' : 'text-retro-muted hover:text-crt-white'">Genres</button>
            <button @click="$emit('set-tab', 'badges')" class="font-mono text-[10px] px-2 py-1 rounded transition-all whitespace-nowrap shrink-0" :class="tab === 'badges' ? 'text-arcade-gold bg-arcade-gold/10' : 'text-retro-muted hover:text-crt-white'">
              Badges <span class="text-[8px] opacity-60">{{ achievements.unlockedCount.value }}/{{ achievements.totalCount.value }}</span>
            </button>
          </div>

          <div v-if="tab === 'modes'">
            <div v-if="hasStats" class="space-y-3">
              <div v-for="(modeStats, modeId) in stats.modes" :key="modeId" class="rounded-lg p-4" style="background: #12131c; border: 1px solid #252738;">
                <h3 class="font-pixel text-[7px] text-crt-cyan mb-2 sm:mb-3 tracking-wider">{{ gameModes[modeId]?.name?.toUpperCase() || modeId }}</h3>
                <div class="grid grid-cols-4 gap-2 sm:gap-3">
                  <div class="text-center"><div class="font-terminal text-lg sm:text-xl text-crt-green tabular-nums">{{ modeStats.gamesWon }}</div><div class="font-mono text-[8px] sm:text-[9px] text-retro-muted">WON</div></div>
                  <div class="text-center"><div class="font-terminal text-lg sm:text-xl text-crt-red tabular-nums">{{ modeStats.gamesLost || 0 }}</div><div class="font-mono text-[8px] sm:text-[9px] text-retro-muted">LOST</div></div>
                  <div class="text-center"><div class="font-terminal text-lg sm:text-xl text-crt-amber tabular-nums">{{ modeStats.bestClicks ?? '-' }}</div><div class="font-mono text-[8px] sm:text-[9px] text-retro-muted">BEST</div></div>
                  <div class="text-center"><div class="font-terminal text-lg sm:text-xl text-crt-cyan tabular-nums">{{ formatTime(modeStats.bestTime) }}</div><div class="font-mono text-[8px] sm:text-[9px] text-retro-muted">TIME</div></div>
                </div>
                <div v-if="modeStats.bestStreak" class="mt-2 pt-2 border-t border-retro-border/20 flex items-center justify-center gap-1"><span class="font-mono text-[9px] text-retro-muted">BEST STREAK:</span><span class="font-terminal text-sm text-arcade-gold">{{ modeStats.bestStreak }}</span></div>
              </div>
            </div>
            <div v-else class="text-center py-10">
              <div class="font-pixel text-[8px] text-retro-muted/50 mb-2">NO DATA YET</div>
              <p class="font-mono text-xs text-retro-muted/30">Play a game to see your stats here</p>
            </div>
          </div>

          <div v-if="tab === 'genres'">
            <div v-if="hasGenreStats" class="space-y-2">
              <div v-for="(gs, gId) in stats.genres" :key="gId" class="rounded-lg px-4 py-3 flex items-center justify-between" style="background: #12131c; border: 1px solid #252738;">
                <div class="flex items-center gap-2.5">
                  <svg class="w-4.5 h-4.5 text-crt-cyan/60 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" v-html="genres[gId]?.svgIcon || ''"></svg>
                  <span class="font-pixel text-[7px] text-crt-white tracking-wider">{{ genres[gId]?.name?.toUpperCase() || gId }}</span>
                </div>
                <div class="flex items-center gap-4 text-center">
                  <div><div class="font-terminal text-sm text-crt-green">{{ gs.gamesWon }}</div><div class="font-mono text-[8px] text-retro-muted">W</div></div>
                  <div><div class="font-terminal text-sm text-crt-red">{{ gs.gamesLost || 0 }}</div><div class="font-mono text-[8px] text-retro-muted">L</div></div>
                  <div><div class="font-terminal text-sm text-crt-cyan">{{ gs.gamesPlayed }}</div><div class="font-mono text-[8px] text-retro-muted">GP</div></div>
                </div>
              </div>
            </div>
            <div v-else class="text-center py-10">
              <div class="font-pixel text-[8px] text-retro-muted/50 mb-2">NO GENRE DATA</div>
              <p class="font-mono text-xs text-retro-muted/30">Play games with different genres to see stats here</p>
            </div>
          </div>

          <div v-if="tab === 'badges'">
            <div class="grid grid-cols-3 sm:grid-cols-4 gap-2">
              <div v-for="badge in achievements.getAllAchievements()" :key="badge.id" class="achievement-badge" :class="badge.unlocked ? 'unlocked' : 'locked'" :title="badge.unlocked ? badge.description : '???'">
                <div class="w-8 h-8 rounded-lg mx-auto mb-1.5 flex items-center justify-center" :style="badge.unlocked ? 'background: rgba(255,215,0,0.08); border: 1px solid rgba(255,215,0,0.2);' : 'background: #181a25; border: 1px solid #252738;'">
                  <svg class="w-4 h-4" :class="badge.unlocked ? 'text-arcade-gold' : 'text-retro-muted/30'" fill="none" stroke="currentColor" viewBox="0 0 24 24" v-html="achievementSvgPath(badge.category)"></svg>
                </div>
                <div class="font-pixel text-[6px] tracking-wider" :class="badge.unlocked ? 'text-arcade-gold' : 'text-retro-muted/40'">{{ badge.unlocked ? badge.name : '???' }}</div>
                <div v-if="badge.unlocked" class="font-mono text-[8px] text-crt-green mt-0.5">+{{ badge.xp }} XP</div>
              </div>
            </div>
            <div class="mt-3 text-center">
              <div class="font-terminal text-lg text-arcade-gold">{{ achievements.unlockedCount.value }} / {{ achievements.totalCount.value }}</div>
              <div class="font-mono text-[9px] text-retro-muted">achievements unlocked</div>
            </div>
          </div>
        </div>
      </div>
    </transition>
  </Teleport>
</template>

<script setup>
defineProps({
  open: { type: Boolean, required: true },
  tab: { type: String, required: true },
  hasStats: { type: Boolean, required: true },
  hasGenreStats: { type: Boolean, required: true },
  stats: { type: Object, required: true },
  gameModes: { type: Object, required: true },
  genres: { type: Object, required: true },
  achievements: { type: Object, required: true },
  achievementSvgPath: { type: Function, required: true },
  formatTime: { type: Function, required: true },
})

defineEmits(['close', 'set-tab'])
</script>
