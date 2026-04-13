<template>
  <div class="relative min-h-screen flex flex-col">

    <!-- Top bar -->
    <header class="relative z-20 border-b border-retro-border/40 shrink-0">
      <div class="max-w-6xl mx-auto px-4 sm:px-5 py-2 sm:py-3 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <router-link to="/" class="flex items-center gap-3 hover:opacity-80 transition-opacity">
            <img src="/favicon.svg" alt="WikiLink" class="w-9 h-9" />
            <span class="font-pixel text-[9px] text-crt-white/80 tracking-wider hidden sm:block">WIKILINK</span>
          </router-link>
        </div>
        <div class="flex items-center gap-2">
          <router-link to="/" class="btn-retro-ghost flex items-center gap-1.5 px-2.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            <span class="hidden sm:inline font-mono text-[10px]">BACK</span>
          </router-link>
          <button @click="handleLogout" class="btn-retro-ghost flex items-center gap-1.5 px-2.5" title="Logout">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
          </button>
        </div>
      </div>
    </header>

    <main class="flex-1 flex flex-col items-center px-3 sm:px-4 py-6 sm:py-10 relative z-10">
      <div class="max-w-lg w-full space-y-5">

        <!-- Profile header -->
        <div class="rounded-xl p-5 sm:p-6 animate-fade-in" style="background: linear-gradient(180deg, #0d0e15, #0a0b12); border: 2px solid #252738;">
          <div class="flex items-center gap-4 mb-4">
            <div class="w-14 h-14 rounded-xl flex items-center justify-center text-2xl" style="background: rgba(57,255,20,0.08); border: 1.5px solid rgba(57,255,20,0.25);">
              <svg class="w-7 h-7 text-crt-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
            </div>
            <div>
              <h1 class="font-pixel text-sm sm:text-base text-crt-green tracking-wider">{{ auth.user.value?.username }}</h1>
              <p v-if="memberSince" class="font-mono text-[10px] text-retro-muted mt-0.5">Member since {{ memberSince }}</p>
            </div>
          </div>
          <div class="flex items-center gap-6">
            <div class="text-center">
              <div class="font-terminal text-xl text-crt-amber">{{ auth.streak.value }}</div>
              <div class="font-mono text-[8px] text-retro-muted">DAILY STREAK</div>
            </div>
            <div class="w-px h-8 bg-retro-border/30"></div>
            <div class="text-center">
              <div class="font-terminal text-xl text-crt-cyan">{{ totalGamesPlayed }}</div>
              <div class="font-mono text-[8px] text-retro-muted">GAMES PLAYED</div>
            </div>
            <div class="w-px h-8 bg-retro-border/30"></div>
            <div class="text-center">
              <div class="font-terminal text-xl text-crt-green">{{ totalWins }}</div>
              <div class="font-mono text-[8px] text-retro-muted">WINS</div>
            </div>
          </div>
        </div>

        <!-- Change Password -->
        <div class="rounded-xl overflow-hidden animate-slide-up" style="background: linear-gradient(180deg, #0d0e15, #0a0b12); border: 2px solid #252738;">
          <button @click="showChangePassword = !showChangePassword"
                  class="w-full px-5 py-3.5 flex items-center justify-between hover:bg-retro-surface/30 transition-colors">
            <div class="flex items-center gap-2.5">
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
              <div class="relative">
                <input v-model="currentPassword" :type="showCurrentPw ? 'text' : 'password'" required autocomplete="current-password"
                       class="w-full px-3 py-2 pr-10 rounded-lg font-mono text-sm bg-[#12131c] border border-retro-border text-crt-white focus:border-crt-cyan focus:outline-none" />
                <button type="button" @click="showCurrentPw = !showCurrentPw" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-retro-muted hover:text-crt-white transition-colors" tabindex="-1">
                  <svg v-if="!showCurrentPw" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                  <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                  </svg>
                </button>
              </div>
            </div>
            <div>
              <label class="font-mono text-[10px] text-retro-muted block mb-1">New Password</label>
              <div class="relative">
                <input v-model="newPassword" :type="showNewPw ? 'text' : 'password'" required minlength="6" autocomplete="new-password"
                       class="w-full px-3 py-2 pr-10 rounded-lg font-mono text-sm bg-[#12131c] border border-retro-border text-crt-white focus:border-crt-cyan focus:outline-none" />
                <button type="button" @click="showNewPw = !showNewPw" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-retro-muted hover:text-crt-white transition-colors" tabindex="-1">
                  <svg v-if="!showNewPw" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                  <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                  </svg>
                </button>
              </div>
            </div>
            <div>
              <label class="font-mono text-[10px] text-retro-muted block mb-1">Confirm New Password</label>
              <div class="relative">
                <input v-model="confirmNewPassword" :type="showConfirmNewPw ? 'text' : 'password'" required minlength="6" autocomplete="new-password"
                       class="w-full px-3 py-2 pr-10 rounded-lg font-mono text-sm bg-[#12131c] border border-retro-border text-crt-white focus:border-crt-cyan focus:outline-none" />
                <button type="button" @click="showConfirmNewPw = !showConfirmNewPw" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-retro-muted hover:text-crt-white transition-colors" tabindex="-1">
                  <svg v-if="!showConfirmNewPw" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                  <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                  </svg>
                </button>
              </div>
            </div>
            <div v-if="changePasswordError" class="font-mono text-[11px] text-crt-red">{{ changePasswordError }}</div>
            <div v-if="changePasswordSuccess" class="font-mono text-[11px] text-crt-green">{{ changePasswordSuccess }}</div>
            <button type="submit" :disabled="changingPassword" class="btn-retro-primary w-full !py-2.5">
              {{ changingPassword ? 'SAVING...' : 'UPDATE PASSWORD' }}
            </button>
          </form>
        </div>

        <!-- Danger Zone -->
        <div class="rounded-xl overflow-hidden animate-slide-up" style="background: linear-gradient(180deg, #0d0e15, #0a0b12); border: 2px solid rgba(255,68,68,0.2);">
          <button @click="showDeleteSection = !showDeleteSection"
                  class="w-full px-5 py-3.5 flex items-center justify-between hover:bg-crt-red/5 transition-colors">
            <div class="flex items-center gap-2.5">
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
                <div class="relative">
                  <input v-model="deletePassword" :type="showDeletePw ? 'text' : 'password'" required autocomplete="current-password"
                         class="w-full px-3 py-2 pr-10 rounded-lg font-mono text-sm bg-[#12131c] border border-crt-red/20 text-crt-white focus:border-crt-red focus:outline-none" />
                  <button type="button" @click="showDeletePw = !showDeletePw" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-retro-muted hover:text-crt-white transition-colors" tabindex="-1">
                    <svg v-if="!showDeletePw" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                    </svg>
                  </button>
                </div>
              </div>
              <div v-if="deleteError" class="font-mono text-[11px] text-crt-red">{{ deleteError }}</div>
              <button type="submit" :disabled="deleting"
                      class="w-full py-2.5 rounded-lg font-pixel text-[8px] tracking-wider transition-all duration-200"
                      style="background: linear-gradient(180deg, #ff4444, #cc2222); border: 1.5px solid #ff4444; color: white; box-shadow: 0 0 15px rgba(255,68,68,0.2);">
                {{ deleting ? 'DELETING...' : 'PERMANENTLY DELETE ACCOUNT' }}
              </button>
            </form>
          </div>
        </div>

      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '../composables/useAuth'
import { useApi } from '../composables/useApi'
import { useToast } from '../composables/useToast'
import { useGame } from '../composables/useGame'

const router = useRouter()
const auth = useAuth()
const api = useApi()
const toast = useToast()
const { getStats } = useGame()

const showChangePassword = ref(false)
const currentPassword = ref('')
const newPassword = ref('')
const confirmNewPassword = ref('')
const changePasswordError = ref('')
const changePasswordSuccess = ref('')
const changingPassword = ref(false)
const showCurrentPw = ref(false)
const showNewPw = ref(false)
const showConfirmNewPw = ref(false)

const showDeleteSection = ref(false)
const deletePassword = ref('')
const deleteError = ref('')
const deleting = ref(false)
const showDeletePw = ref(false)

const memberSince = computed(() => {
  const createdAt = auth.user.value?.created_at
  if (!createdAt) return null
  const d = new Date(createdAt + 'Z')
  return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
})

const stats = computed(() => getStats())

const totalGamesPlayed = computed(() => {
  const modes = stats.value.modes || {}
  return Object.values(modes).reduce((sum, m) => sum + (m.gamesPlayed || 0), 0)
})

const totalWins = computed(() => {
  const modes = stats.value.modes || {}
  return Object.values(modes).reduce((sum, m) => sum + (m.gamesWon || 0), 0)
})

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

function handleLogout() {
  auth.logout()
  router.push({ name: 'home' })
}

onMounted(() => {
  if (!auth.user.value) {
    router.push({ name: 'home' })
  }
})
</script>
