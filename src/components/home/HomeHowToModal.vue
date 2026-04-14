<template>
  <Teleport to="body">
    <transition name="fade">
      <div v-if="open" class="fixed inset-0 z-[60] flex items-center justify-center p-3 sm:p-4 max-h-[100dvh]">
        <div class="absolute inset-0 bg-black/85 backdrop-blur-sm" @click="$emit('close')"></div>
        <div class="relative rounded-2xl p-5 sm:p-6 max-w-lg w-full max-h-[min(92dvh,600px)] overflow-y-auto overscroll-contain animate-scale-in" style="background: #0d0e15; border: 1.5px solid rgba(37,39,56,0.7); box-shadow: 0 0 60px rgba(0,0,0,0.8);">
          <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2.5">
              <div class="w-1 h-4 rounded-full bg-crt-cyan"></div>
              <h2 class="font-pixel text-[9px] text-crt-cyan tracking-[0.2em]">HOW TO PLAY</h2>
            </div>
            <button @click="$emit('close')" class="btn-ghost p-1.5">
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
</template>

<script setup>
defineProps({
  open: { type: Boolean, required: true },
})

defineEmits(['close'])
</script>
