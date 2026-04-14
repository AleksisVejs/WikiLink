<template>
  <div class="min-h-screen flex flex-col relative z-10">
    <SiteTopNav
      :user="auth.user.value"
      :level="progression.level.value"
      :current-xp="progression.currentXp.value"
      :next-level-xp="progression.nextLevelXp.value"
      :xp-percent="progression.progress.value * 100"
      :show-xp-bar="!!auth.user.value"
      @login="showAuthModal = true"
      @logout="auth.logout()"
    />

    <div class="flex-1 flex flex-col items-center justify-center px-4">
      <div class="text-center animate-fade-in max-w-md w-full">
        <div class="mb-6">
          <div class="font-terminal text-[80px] sm:text-[100px] leading-none font-bold"
               style="color: rgba(255,68,68,0.15); text-shadow: 0 0 40px rgba(255,68,68,0.1);">
            404
          </div>
        </div>

        <div class="inline-flex items-center gap-2 mb-4 px-3 py-1.5 rounded-lg"
             style="background: rgba(255,68,68,0.06); border: 1px solid rgba(255,68,68,0.15);">
          <span class="w-2 h-2 rounded-full bg-crt-red/50 animate-blink"></span>
          <span class="font-pixel text-[8px] text-crt-red tracking-[0.2em]">PAGE NOT FOUND</span>
        </div>

        <p class="font-mono text-sm text-retro-muted mb-3 max-w-sm mx-auto leading-relaxed">
          This article doesn't exist in our wiki.
        </p>
        <p class="font-mono text-xs text-retro-muted/50 mb-8 max-w-sm mx-auto">
          You may have followed a broken link or typed the URL incorrectly.
        </p>

        <div class="retro-divider max-w-[200px] mx-auto mb-8"></div>

        <router-link to="/" class="btn-retro-primary inline-block">
          RETURN HOME
        </router-link>
      </div>
    </div>

    <HomeAuthModal
      :open="showAuthModal"
      :mode="authMode"
      :username="authUsername"
      :password="authPassword"
      :confirm-password="authConfirmPassword"
      :show-password="authShowPassword"
      :show-confirm-password="authShowConfirmPassword"
      :error="authError"
      :loading="auth.loading.value"
      @close="closeAuthModal"
      @set-mode="authMode = $event"
      @submit="submitAuth"
      @update:username="authUsername = $event"
      @update:password="authPassword = $event"
      @update:confirm-password="authConfirmPassword = $event"
      @toggle-password="authShowPassword = !authShowPassword"
      @toggle-confirm-password="authShowConfirmPassword = !authShowConfirmPassword"
    />
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { useAuth } from '../composables/useAuth'
import { useProgression } from '../composables/useProgression'
import SiteTopNav from '../components/layout/SiteTopNav.vue'
import HomeAuthModal from '../components/home/HomeAuthModal.vue'

const auth = useAuth()
const progression = useProgression()
const showAuthModal = ref(false)
const authMode = ref('login')
const authUsername = ref('')
const authPassword = ref('')
const authConfirmPassword = ref('')
const authError = ref('')
const authShowPassword = ref(false)
const authShowConfirmPassword = ref(false)

watch(authMode, () => {
  authError.value = ''
  authConfirmPassword.value = ''
  authShowPassword.value = false
  authShowConfirmPassword.value = false
})

function closeAuthModal() {
  showAuthModal.value = false
  authError.value = ''
  authPassword.value = ''
  authConfirmPassword.value = ''
  authShowPassword.value = false
  authShowConfirmPassword.value = false
}

async function submitAuth() {
  authError.value = ''
  if (authMode.value === 'register' && authPassword.value !== authConfirmPassword.value) {
    authError.value = 'Passwords do not match.'
    return
  }
  const fn = authMode.value === 'login' ? auth.login : auth.register
  const err = await fn(authUsername.value, authPassword.value)
  if (err) {
    authError.value = err
    return
  }
  closeAuthModal()
  authUsername.value = ''
}
</script>
