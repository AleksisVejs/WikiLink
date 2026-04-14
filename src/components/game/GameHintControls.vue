<template>
  <div class="relative">
    <button
      @click="$emit('toggle-menu')"
      :disabled="hints <= 0"
      class="hint-trigger-btn flex items-center gap-1 px-1.5 sm:px-2 py-1 sm:py-1.5 rounded-lg transition-all duration-200 disabled:opacity-25 disabled:pointer-events-none touch-manipulation group"
      title="Use a hint"
    >
      <svg class="w-3.5 h-3.5 text-crt-amber group-hover:drop-shadow-[0_0_6px_rgba(255,191,0,0.5)] transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
      </svg>
      <span class="font-terminal text-sm text-crt-amber leading-none">{{ hints }}</span>
    </button>

    <transition name="hint-menu">
      <div v-if="showMenu" class="hint-dropdown absolute right-0 top-full mt-2 z-50">
        <div class="hint-dropdown-header">
          <svg class="w-3 h-3 text-crt-amber/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
          </svg>
          <span class="font-pixel text-[7px] text-crt-amber/60 tracking-[0.15em]">USE HINT</span>
          <span class="ml-auto font-terminal text-[11px] text-crt-amber/40">{{ hints }} left</span>
        </div>
        <div class="p-1.5 space-y-1">
          <button @click="$emit('use-hint', 'category')" class="hint-option group/opt">
            <div class="hint-option-icon" style="background: rgba(0,229,255,0.06); border-color: rgba(0,229,255,0.15);">
              <svg class="w-4 h-4 text-crt-cyan" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <div class="font-mono text-[11px] text-retro-light group-hover/opt:text-crt-cyan transition-colors">Category Reveal</div>
              <div class="font-mono text-[9px] text-retro-muted/50 mt-0.5">Shows the target's topic category</div>
            </div>
            <svg class="w-3 h-3 text-retro-border/40 group-hover/opt:text-crt-cyan/50 transition-colors shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </button>
          <button @click="$emit('use-hint', 'hotcold')" class="hint-option group/opt">
            <div class="hint-option-icon" style="background: rgba(255,68,68,0.06); border-color: rgba(255,68,68,0.15);">
              <svg class="w-4 h-4 text-crt-red" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.879 16.121A3 3 0 1012.015 11L11 14H9c0 .768.293 1.536.879 2.121z" />
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <div class="font-mono text-[11px] text-retro-light group-hover/opt:text-crt-red transition-colors">Hot / Cold</div>
              <div class="font-mono text-[9px] text-retro-muted/50 mt-0.5">How close you are to the target</div>
            </div>
            <svg class="w-3 h-3 text-retro-border/40 group-hover/opt:text-crt-red/50 transition-colors shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </button>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
defineProps({
  hints: { type: Number, required: true },
  showMenu: { type: Boolean, required: true },
})

defineEmits(['toggle-menu', 'use-hint'])
</script>
