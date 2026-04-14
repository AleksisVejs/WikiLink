<template>
  <div class="min-h-screen bg-retro-black relative">
    <!-- Top accent line -->
    <div class="fixed top-0 left-0 right-0 h-[1px] z-50 pointer-events-none"
         style="background: linear-gradient(90deg, transparent, rgba(57,255,20,0.4) 20%, rgba(0,229,255,0.5) 50%, rgba(180,76,255,0.4) 80%, transparent);"></div>

    <!-- Layered atmospheric background -->
    <div class="fixed inset-0 pointer-events-none">
      <div class="absolute -top-32 -left-32 w-[600px] h-[600px] rounded-full blur-[160px] animate-glow-breathe"
           style="background: radial-gradient(circle, rgba(57,255,20,0.045), transparent 70%)"></div>
      <div class="absolute top-1/4 -right-40 w-[550px] h-[550px] rounded-full blur-[150px] animate-glow-breathe"
           style="background: radial-gradient(circle, rgba(0,229,255,0.04), transparent 70%); animation-delay: 2s;"></div>
      <div class="absolute -bottom-32 left-1/3 w-[500px] h-[500px] rounded-full blur-[140px] animate-glow-breathe"
           style="background: radial-gradient(circle, rgba(255,46,204,0.03), transparent 70%); animation-delay: 4s;"></div>
      <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[700px] h-[700px] rounded-full blur-[200px] animate-glow-breathe"
           style="background: radial-gradient(circle, rgba(180,76,255,0.02), transparent 70%); animation-delay: 3s;"></div>
      <div class="absolute inset-0 opacity-[0.02]"
           style="background-image: radial-gradient(circle, rgba(255,255,255,0.5) 1px, transparent 1px); background-size: 32px 32px;"></div>
    </div>

    <!-- Global scanlines -->
    <div class="scanlines"></div>
    <!-- Film grain -->
    <div class="grain-overlay"></div>

    <router-view v-slot="{ Component }">
      <transition name="crt" mode="out-in">
        <component :is="Component" />
      </transition>
    </router-view>

    <!-- Toast container -->
    <div class="toast-container">
      <transition-group name="fade">
        <div v-for="t in toasts" :key="t.id" :class="['toast', `toast-${t.type}`]">
          {{ t.message }}
        </div>
      </transition-group>
    </div>

    <Teleport to="body">
      <transition name="fade">
        <div v-if="incomingInvite" class="fixed inset-0 z-[80] flex items-center justify-center p-4">
          <div class="absolute inset-0 bg-black/85 backdrop-blur-sm"></div>
          <div class="relative rounded-xl p-6 max-w-sm w-full animate-scale-in" style="background: #0d0e15; border: 2px solid rgba(0,229,255,0.35); box-shadow: 0 0 60px rgba(0,0,0,0.8);">
            <div class="flex items-center gap-2.5 mb-4">
              <div class="w-1 h-4 rounded-full bg-crt-cyan"></div>
              <h2 class="font-pixel text-[9px] text-crt-cyan tracking-[0.2em]">GAME INVITE</h2>
            </div>
            <p class="font-mono text-xs text-retro-muted mb-4">
              <span class="text-crt-cyan">{{ incomingInvite.sender_username }}</span> invited you to a 1v1 match.
            </p>
            <div class="flex gap-2">
              <button :disabled="inviteActionLoading" @click="respondToInvite('deny')" class="btn-retro-ghost flex-1 !py-2 !text-[9px]">DENY</button>
              <button :disabled="inviteActionLoading" @click="respondToInvite('accept')" class="btn-retro-primary flex-1 !py-2 !text-[9px]">
                {{ inviteActionLoading ? '...' : 'ACCEPT' }}
              </button>
            </div>
          </div>
        </div>
      </transition>
    </Teleport>
  </div>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useApi } from './composables/useApi'
import { useAuth } from './composables/useAuth'
import { useToast } from './composables/useToast'

const router = useRouter()
const api = useApi()
const auth = useAuth()
const { toasts } = useToast()
const toast = useToast()

const incomingInvite = ref(null)
const inviteActionLoading = ref(false)
let invitePollInterval = null

async function pollIncomingInvites() {
  if (!auth.user.value) {
    incomingInvite.value = null
    return
  }
  if (inviteActionLoading.value) return
  try {
    const result = await api.get('/friends/game-invites')
    incomingInvite.value = result.incoming?.[0] || null
  } catch {
    incomingInvite.value = null
  }
}

async function respondToInvite(action) {
  if (!incomingInvite.value || inviteActionLoading.value) return
  inviteActionLoading.value = true
  try {
    const result = await api.post('/friends/game-invite/respond', {
      inviteId: incomingInvite.value.id,
      action,
    })
    const acceptedInvite = incomingInvite.value
    incomingInvite.value = null
    if (action === 'accept' && result.match) {
      toast.success(`Joining ${acceptedInvite.sender_username}'s 1v1...`)
      router.push({
        name: 'home',
        query: { inviteMatch: result.match.code },
      })
    } else {
      toast.info(`Invite from ${acceptedInvite.sender_username} declined.`)
    }
  } catch (e) {
    toast.error(e.message || 'Failed to respond to invite')
  } finally {
    inviteActionLoading.value = false
    pollIncomingInvites()
  }
}

onMounted(() => {
  pollIncomingInvites()
  invitePollInterval = setInterval(pollIncomingInvites, 3000)
})

onBeforeUnmount(() => {
  if (invitePollInterval) {
    clearInterval(invitePollInterval)
    invitePollInterval = null
  }
})
</script>
