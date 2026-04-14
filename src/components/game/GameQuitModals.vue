<template>
  <Teleport to="body">
    <transition name="fade">
      <div v-if="showQuitConfirm" class="fixed inset-0 z-[60] flex items-center justify-center p-3 sm:p-4">
        <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" @click="$emit('close-quit')"></div>
        <div class="relative rounded-2xl p-6 max-w-sm w-full animate-scale-in text-center" style="background: #0d0e15; border: 1.5px solid rgba(255,191,0,0.25); box-shadow: 0 0 40px rgba(255,191,0,0.08), 0 12px 40px rgba(0,0,0,0.6);">
          <div class="inline-flex items-center gap-2 mb-3 px-3 py-1.5 rounded-lg" style="background: rgba(255,191,0,0.06); border: 1px solid rgba(255,191,0,0.12);">
            <svg class="w-4 h-4 text-crt-amber" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
            </svg>
            <span class="font-pixel text-[8px] text-crt-amber tracking-[0.2em]">WARNING</span>
          </div>
          <p class="font-mono text-sm text-retro-light mb-6">Abort current mission?<br><span class="text-retro-muted text-xs mt-1 inline-block">Your progress will be lost.</span></p>
          <div class="flex gap-3 pb-[env(safe-area-inset-bottom)]">
            <button @click="$emit('close-quit')" class="btn-secondary flex-1 touch-manipulation">CANCEL</button>
            <button @click="$emit('go-home')" class="btn-primary flex-1 touch-manipulation" style="background: linear-gradient(180deg, #ff6644, #ff4444); border-color: #ff4444; box-shadow: 0 0 15px rgba(255,68,68,0.3), 0 3px 0 #aa2211;">
              QUIT
            </button>
          </div>
        </div>
      </div>
    </transition>
  </Teleport>

  <Teleport to="body">
    <transition name="fade">
      <div v-if="showOpponentQuitModal" class="fixed inset-0 z-[65] flex items-center justify-center p-3 sm:p-4">
        <div class="absolute inset-0 bg-black/85 backdrop-blur-sm"></div>
        <div class="relative rounded-2xl p-6 max-w-sm w-full animate-scale-in text-center" style="background: #0d0e15; border: 1.5px solid rgba(255,68,68,0.25); box-shadow: 0 0 40px rgba(255,68,68,0.08), 0 12px 40px rgba(0,0,0,0.6);">
          <div class="inline-flex items-center gap-2 mb-3 px-3 py-1.5 rounded-lg" style="background: rgba(255,68,68,0.06); border: 1px solid rgba(255,68,68,0.12);">
            <svg class="w-4 h-4 text-crt-red" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-12.728 12.728M5.636 5.636l12.728 12.728" />
            </svg>
            <span class="font-pixel text-[8px] text-crt-red tracking-[0.2em]">PLAYER LEFT</span>
          </div>
          <p class="font-mono text-sm text-retro-light mb-6">
            {{ opponentQuitName || 'The other player' }} quit the match.<br>
            <span class="text-retro-muted text-xs mt-1 inline-block">Continue as solo or quit?</span>
          </p>
          <div class="flex gap-3 pb-[env(safe-area-inset-bottom)]">
            <button @click="$emit('quit-after-opponent-left')" class="btn-secondary flex-1 touch-manipulation">QUIT</button>
            <button @click="$emit('resume-solo-opponent', opponentQuitName || 'Opponent')" class="btn-primary flex-1 touch-manipulation">CONTINUE SOLO</button>
          </div>
        </div>
      </div>
    </transition>
  </Teleport>

  <Teleport to="body">
    <transition name="fade">
      <div v-if="showLobbyPlayerLeftModal" class="fixed inset-0 z-[65] flex items-center justify-center p-3 sm:p-4">
        <div class="absolute inset-0 bg-black/85 backdrop-blur-sm"></div>
        <div class="relative rounded-2xl p-6 max-w-sm w-full animate-scale-in text-center" style="background: #0d0e15; border: 1.5px solid rgba(255,68,68,0.25); box-shadow: 0 0 40px rgba(255,68,68,0.08), 0 12px 40px rgba(0,0,0,0.6);">
          <div class="inline-flex items-center gap-2 mb-3 px-3 py-1.5 rounded-lg" style="background: rgba(255,68,68,0.06); border: 1px solid rgba(255,68,68,0.12);">
            <svg class="w-4 h-4 text-crt-red" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-12.728 12.728M5.636 5.636l12.728 12.728" />
            </svg>
            <span class="font-pixel text-[8px] text-crt-red tracking-[0.2em]">PLAYER LEFT</span>
          </div>
          <p class="font-mono text-sm text-retro-light mb-6">
            {{ lobbyLeftPlayersLabel }} left the lobby.<br>
            <span class="text-retro-muted text-xs mt-1 inline-block">Continue solo or quit?</span>
          </p>
          <div class="flex gap-3 pb-[env(safe-area-inset-bottom)]">
            <button @click="$emit('quit-after-lobby-left')" class="btn-secondary flex-1 touch-manipulation">QUIT</button>
            <button @click="$emit('resume-solo-lobby', lobbyLeftPlayersLabel)" class="btn-primary flex-1 touch-manipulation">CONTINUE SOLO</button>
          </div>
        </div>
      </div>
    </transition>
  </Teleport>
</template>

<script setup>
defineProps({
  showQuitConfirm: { type: Boolean, required: true },
  showOpponentQuitModal: { type: Boolean, required: true },
  opponentQuitName: { type: String, default: '' },
  showLobbyPlayerLeftModal: { type: Boolean, required: true },
  lobbyLeftPlayersLabel: { type: String, required: true },
})

defineEmits([
  'close-quit',
  'go-home',
  'quit-after-opponent-left',
  'resume-solo-opponent',
  'quit-after-lobby-left',
  'resume-solo-lobby',
])
</script>
