<template>
  <Teleport to="body">
    <transition name="fade">
      <div v-if="open" class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/85 backdrop-blur-sm" @click="$emit('close')"></div>
        <div class="relative rounded-xl p-6 max-w-sm w-full animate-scale-in" style="background: #0d0e15; border: 2px solid rgba(180,76,255,0.35); box-shadow: 0 0 60px rgba(0,0,0,0.8);">
          <div class="flex items-center justify-between mb-5">
            <div class="flex items-center gap-2.5">
              <div class="w-1 h-4 rounded-full bg-arcade-purple"></div>
              <h2 class="font-pixel text-[9px] text-arcade-purple tracking-[0.2em]">1v1 INVITE</h2>
            </div>
            <button @click="$emit('close')" class="btn-ghost p-1.5">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <template v-if="step === 'creating'">
            <div class="text-center py-6">
              <div class="w-8 h-8 border-2 border-arcade-purple/30 border-t-arcade-purple rounded-full animate-spin mx-auto mb-3"></div>
              <span class="font-mono text-xs text-retro-muted">Creating match...</span>
            </div>
          </template>

          <template v-else-if="step === 'pending'">
            <div class="text-center py-2">
              <p class="font-mono text-xs text-retro-muted mb-3">Invite sent to <span class="text-crt-cyan">{{ targetUser }}</span>.</p>
              <p class="font-mono text-[11px] text-retro-muted/70 mb-4">Waiting for response...</p>
              <button @click="$emit('close')" class="btn-retro-ghost w-full !py-2 !text-[9px]">CLOSE</button>
            </div>
          </template>

          <template v-else-if="step === 'accepted'">
            <div class="text-center py-2">
              <p class="font-mono text-xs text-crt-green mb-3">{{ targetUser }} accepted your invite.</p>
              <button @click="$emit('start-match')" class="btn-retro-primary w-full !py-2 !text-[9px]">START PLAYING</button>
            </div>
          </template>

          <template v-else-if="step === 'declined'">
            <div class="text-center py-2">
              <p class="font-mono text-xs text-crt-red mb-3">{{ targetUser }} declined your invite.</p>
              <button @click="$emit('close')" class="btn-retro-ghost w-full !py-2 !text-[9px]">CLOSE</button>
            </div>
          </template>

          <template v-else-if="step === 'error'">
            <div class="text-center py-4">
              <div class="font-mono text-[11px] text-crt-red mb-3">{{ error }}</div>
              <button @click="$emit('close')" class="btn-retro-ghost px-4 py-2">CLOSE</button>
            </div>
          </template>
        </div>
      </div>
    </transition>
  </Teleport>
</template>

<script setup>
defineProps({
  open: { type: Boolean, required: true },
  step: { type: String, required: true },
  targetUser: { type: String, required: true },
  error: { type: String, required: true },
})

defineEmits(['close', 'start-match'])
</script>
