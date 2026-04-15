<template>
  <nav class="sticky top-0 z-[40] glass-header" style="border-bottom: 1px solid rgba(37,39,56,0.5);">
    <div class="max-w-6xl mx-auto px-2 sm:px-4">
      <div class="relative flex items-center justify-between h-12 sm:h-12">
        <div class="flex items-center gap-1 sm:gap-1.5 shrink-0">
          <button @click="$emit('quit')" class="nav-action-btn nav-action-btn--red" title="Exit game (Esc)">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
            <span class="nav-action-label">ESC</span>
          </button>

          <button
            @click="$emit('go-back')"
            :disabled="pathLength <= 1 || !isPlaying || backDisabled"
            class="nav-action-btn nav-action-btn--cyan"
            title="Go back (Backspace)"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            <span class="nav-action-label">BACK</span>
          </button>

          <button
            v-if="canReroll && isPlaying"
            @click="$emit('reroll')"
            :disabled="rerolling"
            class="nav-action-btn nav-action-btn--amber"
            title="New pair (R)"
          >
            <svg class="w-3.5 h-3.5" :class="{ 'animate-spin': rerolling }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            <span class="nav-action-label">NEW</span>
          </button>

          <button
            v-if="currentMode?.noTarget && isPlaying"
            @click="$emit('finish-freeplay')"
            class="nav-action-btn nav-action-btn--green"
            title="Finish session"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <span class="nav-action-label">DONE</span>
          </button>
        </div>

        <div v-if="!currentMode?.noTarget && !initialLoading" class="hidden md:flex items-center absolute left-1/2 -translate-x-1/2 nav-route-pill">
          <div class="flex items-center gap-1.5 min-w-0 wiki-point">
            <span class="w-1.5 h-1.5 rounded-full bg-crt-green shrink-0" style="box-shadow: 0 0 6px rgba(57,255,20,0.5);"></span>
            <span class="truncate font-mono text-[11px] text-crt-green max-w-[180px]">{{ startTitle }}</span>
            <span class="wiki-point-full">{{ startTitle }}</span>
          </div>
          <div class="flex items-center gap-1 mx-2.5 shrink-0">
            <span class="w-4 h-px bg-retro-border/30"></span>
            <svg class="w-3.5 h-3.5 text-retro-muted/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
            </svg>
            <span class="w-4 h-px bg-retro-border/30"></span>
          </div>
          <div class="flex items-center gap-1.5 min-w-0 wiki-point">
            <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="isWon ? 'bg-crt-green' : 'animate-blink'" :style="isWon ? 'box-shadow: 0 0 6px rgba(57,255,20,0.5)' : 'background: #ff2ecc; box-shadow: 0 0 6px rgba(255,46,204,0.5);'"></span>
            <span class="truncate font-mono text-[11px] max-w-[180px]" :class="isWon ? 'text-crt-green' : 'text-crt-magenta'">{{ targetTitle }}</span>
            <span class="wiki-point-full">{{ targetTitle }}</span>
          </div>
        </div>

        <div class="flex items-center gap-1.5 sm:gap-2 shrink-0">
          <div
            v-if="multiplayerOpponent"
            class="hidden sm:flex items-center gap-1.5 max-w-[220px] px-2 py-1 rounded-md border border-crt-cyan/30 bg-crt-cyan/[0.06]"
            title="Current multiplayer opponent"
          >
            <span class="font-mono text-[10px] text-crt-cyan truncate">{{ multiplayerOpponent.label }}</span>
            <div class="w-5 h-5 rounded-md border flex items-center justify-center shrink-0" :style="opponentAccentStyle">
              <svg
                v-if="opponentIcon"
                class="w-3 h-3"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
                v-html="opponentIcon.svgPath"
              ></svg>
              <span v-else class="font-mono text-[9px] text-crt-cyan">{{ opponentInitial }}</span>
            </div>
          </div>

          <GameHintControls
            v-if="hasTarget && isPlaying"
            :hints="hints"
            :show-menu="showHintMenu"
            @toggle-menu="$emit('toggle-hint-menu')"
            @use-hint="$emit('use-hint', $event)"
          />

          <div class="nav-stats-group">
            <div class="flex items-center gap-1">
              <svg class="w-3 h-3 text-retro-muted/40 hidden sm:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122" />
              </svg>
              <span class="font-terminal text-sm sm:text-lg font-bold leading-none" :class="clickLimitClass">
                {{ clicks }}<span v-if="effectiveClickLimit" class="text-retro-muted text-[10px] sm:text-xs">/{{ effectiveClickLimit }}</span>
              </span>
            </div>
            <div class="nav-stats-divider"></div>
            <div class="flex items-center gap-1">
              <svg class="w-3 h-3 text-retro-muted/40 hidden sm:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <span v-if="formattedRemaining !== null" class="font-terminal text-sm sm:text-lg font-bold leading-none" :class="timerClass">{{ formattedRemaining }}</span>
              <span v-else class="font-terminal text-sm sm:text-lg font-bold leading-none text-crt-white">{{ formattedTime }}</span>
            </div>
          </div>

          <button @click="$emit('toggle-mute')" class="game-nav-mute-btn" :title="muted ? 'Unmute' : 'Mute'">
            <svg v-if="!muted" class="w-3.5 h-3.5 game-nav-mute-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072M12 6l-4 4H4v4h4l4 4V6z" />
            </svg>
            <svg v-else class="w-3.5 h-3.5 game-nav-mute-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707A1 1 0 0112 5v14a1 1 0 01-1.707.707L5.586 15zM17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2" />
            </svg>
          </button>

          <span class="w-2 h-2 rounded-full shrink-0" :style="isPlaying ? 'background: #39ff14; box-shadow: 0 0 6px rgba(57,255,20,0.5);' : 'background: #555770;'"></span>

          <div v-if="modifiers.length > 0" class="hidden sm:flex items-center gap-1">
            <span
              v-for="modId in modifiers"
              :key="modId"
              class="w-6 h-6 rounded flex items-center justify-center"
              style="background: rgba(255,191,0,0.08); border: 1px solid rgba(255,191,0,0.2);"
              :title="gameModifiers[modId]?.name"
            >
              <svg class="w-3.5 h-3.5 text-crt-amber" fill="none" stroke="currentColor" viewBox="0 0 24 24" v-html="modifierSvgPath(modId)"></svg>
            </span>
          </div>
        </div>
      </div>

      <div v-if="!currentMode?.noTarget && !initialLoading" class="md:hidden flex items-center justify-center pb-1.5 -mt-0.5">
        <div class="nav-route-pill nav-route-pill--mobile">
          <div class="flex items-center gap-1.5 min-w-0 wiki-point">
            <span class="w-1.5 h-1.5 rounded-full bg-crt-green shrink-0" style="box-shadow: 0 0 5px rgba(57,255,20,0.5);"></span>
            <span class="truncate font-mono text-[10px] text-crt-green max-w-[32vw]">{{ startTitle }}</span>
            <span class="wiki-point-full">{{ startTitle }}</span>
          </div>
          <div class="flex items-center gap-0.5 mx-1.5 shrink-0">
            <span class="w-2.5 h-px bg-retro-border/25"></span>
            <svg class="w-3 h-3 text-retro-muted/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
            </svg>
            <span class="w-2.5 h-px bg-retro-border/25"></span>
          </div>
          <div class="flex items-center gap-1.5 min-w-0 wiki-point">
            <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="isWon ? 'bg-crt-green' : 'animate-blink'" :style="isWon ? 'box-shadow: 0 0 5px rgba(57,255,20,0.5)' : 'background: #ff2ecc; box-shadow: 0 0 5px rgba(255,46,204,0.5);'"></span>
            <span class="truncate font-mono text-[10px] max-w-[32vw]" :class="isWon ? 'text-crt-green' : 'text-crt-magenta'">{{ targetTitle }}</span>
            <span class="wiki-point-full">{{ targetTitle }}</span>
          </div>
        </div>
      </div>

      <div v-if="displayPath.length > 1 && !initialLoading" class="hidden sm:flex nav-breadcrumb">
        <template v-for="(article, idx) in displayPath" :key="idx">
          <span v-if="idx > 0" class="text-retro-border/30 shrink-0 text-[8px] sm:text-[10px] select-none">&rsaquo;</span>
          <span v-if="article.type === 'collapsed'" class="shrink-0 px-1.5 py-0.5 rounded text-retro-muted/40 font-mono text-[9px] sm:text-[10px] whitespace-nowrap">
            {{ article.label }}
          </span>
          <span
            v-else
            class="shrink-0 px-1.5 sm:px-2 py-0.5 rounded-md font-mono text-[9px] sm:text-[10px] whitespace-nowrap transition-colors"
            :class="article.active ? 'text-crt-green' : 'text-retro-muted/60'"
            :style="article.active ? 'background: rgba(57,255,20,0.06); border: 1px solid rgba(57,255,20,0.1);' : ''"
          >
            {{ article.label }}
          </span>
        </template>
      </div>

      <div
        v-if="multiplayerOpponent"
        class="sm:hidden flex items-center justify-center pb-1 -mt-0.5"
      >
        <div class="px-2 py-0.5 rounded-md border border-crt-cyan/30 bg-crt-cyan/[0.06] max-w-[90vw] flex items-center gap-1.5">
          <span class="block font-mono text-[10px] text-crt-cyan truncate text-center">{{ multiplayerOpponent.label }}</span>
          <div class="w-4 h-4 rounded border flex items-center justify-center shrink-0" :style="opponentAccentStyle">
            <svg
              v-if="opponentIcon"
              class="w-2.5 h-2.5"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
              v-html="opponentIcon.svgPath"
            ></svg>
            <span v-else class="font-mono text-[8px] text-crt-cyan">{{ opponentInitial }}</span>
          </div>
        </div>
      </div>
    </div>
  </nav>
</template>

<script setup>
import { computed } from 'vue'
import GameHintControls from './GameHintControls.vue'
import { getProfileIconById, getProfileAccentById } from '../../constants/profileIcons'

const props = defineProps({
  pathLength: { type: Number, required: true },
  isPlaying: { type: Boolean, required: true },
  isWon: { type: Boolean, required: true },
  backDisabled: { type: Boolean, required: true },
  canReroll: { type: Boolean, required: true },
  rerolling: { type: Boolean, required: true },
  currentMode: { type: Object, default: null },
  initialLoading: { type: Boolean, required: true },
  startTitle: { type: String, default: '' },
  targetTitle: { type: String, default: '' },
  hasTarget: { type: Boolean, required: true },
  hints: { type: Number, required: true },
  showHintMenu: { type: Boolean, required: true },
  clicks: { type: Number, required: true },
  effectiveClickLimit: { type: Number, default: null },
  clickLimitClass: { type: String, required: true },
  formattedRemaining: { type: String, default: null },
  formattedTime: { type: String, required: true },
  timerClass: { type: String, required: true },
  muted: { type: Boolean, required: true },
  modifiers: { type: Array, required: true },
  gameModifiers: { type: Object, required: true },
  modifierSvgPath: { type: Function, required: true },
  displayPath: { type: Array, required: true },
  multiplayerOpponent: { type: Object, default: null },
})

const opponentIcon = computed(() => {
  if (!props.multiplayerOpponent) return null
  return getProfileIconById(props.multiplayerOpponent.profile_icon)
})

const opponentAccentStyle = computed(() => {
  if (!props.multiplayerOpponent) return null
  const accent = getProfileAccentById(props.multiplayerOpponent.profile_accent)
  if (!accent?.color) return null
  return {
    color: accent.color,
    borderColor: `${accent.color}66`,
    background: `${accent.color}14`,
  }
})

const opponentInitial = computed(() => {
  const label = String(props.multiplayerOpponent?.label || '').trim()
  if (!label) return '?'
  const clean = label.replace(/^vs\s+/i, '')
  return (clean.charAt(0) || '?').toUpperCase()
})

defineEmits([
  'quit',
  'go-back',
  'reroll',
  'finish-freeplay',
  'toggle-hint-menu',
  'use-hint',
  'toggle-mute',
])
</script>
