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
              <p class="text-[12px]">Navigate from a <span class="text-crt-green">starting Wikipedia article</span> to a <span class="text-crt-magenta">target article</span> using only the links inside each Wikipedia page. Reach the target in as few clicks and as little time as possible.</p>
            </div>
            <div>
              <h3 class="font-pixel text-[8px] text-crt-amber mb-1.5 tracking-wider">MODES</h3>
              <ul class="space-y-1 text-[12px]">
                <li><span class="text-crt-blue">Classic</span> -- no timer, lowest click count wins</li>
                <li><span class="text-arcade-orange">Sprint</span> -- reach the target before the timer runs out</li>
                <li><span class="text-crt-magenta">Click Limit</span> -- finish within a fixed click budget, with no going back</li>
                <li><span style="color:#ff5c3a">Trending</span> -- classic rules, but the route uses trending Wikipedia pages</li>
                <li><span class="text-arcade-gold">Custom</span> -- choose your own start and target articles</li>
                <li><span class="text-crt-green">Freeplay</span> -- explore freely with no target article</li>
                <li><span class="text-crt-amber">Daily</span> -- one shared route per day for everyone, with no going back</li>
                <li><span class="text-arcade-purple">Multiplayer</span> -- race friends in 1v1 duels or group lobbies</li>
              </ul>
            </div>
            <div>
              <h3 class="font-pixel text-[8px] text-crt-cyan mb-1.5 tracking-wider">RULES &amp; CONTROLS</h3>
              <ul class="space-y-1 text-[12px]">
                <li><span class="text-crt-green">Green links</span> = valid article links you can click</li>
                <li><span class="text-retro-muted">Gray links</span> = disabled, excluded, or non-playable links</li>
                <li><kbd class="px-1 py-0.5 rounded text-[10px] bg-retro-surface border border-retro-border text-crt-cyan">Backspace</kbd> go back one page when the mode allows it</li>
                <li><kbd class="px-1 py-0.5 rounded text-[10px] bg-retro-surface border border-retro-border text-crt-amber">R</kbd> reroll for a new pair in supported solo modes</li>
                <li><kbd class="px-1 py-0.5 rounded text-[10px] bg-retro-surface border border-retro-border text-crt-red">Esc</kbd> quit the current run</li>
                <li><kbd class="px-1 py-0.5 rounded text-[10px] bg-retro-surface border border-retro-border text-crt-magenta">Ctrl/Cmd + F</kbd> is disabled during play</li>
                <li>Daily and Click Limit runs disable backtracking by default</li>
                <li>Daily can only be completed once per day; after completion it is locked until the next day</li>
                <li>Sprint fails when the timer reaches zero; Click Limit fails when you run out of allowed clicks</li>
                <li>In multiplayer, fewer clicks ranks higher; time breaks ties</li>
              </ul>
            </div>
            <div>
              <h3 class="font-pixel text-[8px] text-crt-magenta mb-1.5 tracking-wider">TIPS</h3>
              <p class="text-[12px]">Use broad hub pages like countries, historical events, famous people, or major topics to bridge unrelated articles. If you get stuck, zoom out and route through categories that connect lots of pages.</p>
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
