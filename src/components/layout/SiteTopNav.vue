<template>
  <header class="relative z-20 glass-header shrink-0">
    <div class="max-w-6xl mx-auto px-3 sm:px-5 py-2 sm:py-2.5 flex items-center justify-between gap-3">
      <router-link to="/" class="flex items-center gap-2.5 shrink-0 group">
        <img src="/favicon.svg" alt="WikiLink" class="w-8 h-8 sm:w-9 sm:h-9 drop-shadow-[0_0_8px_rgba(57,255,20,0.3)] group-hover:drop-shadow-[0_0_12px_rgba(57,255,20,0.5)] transition-all" />
        <span class="font-pixel text-[8px] sm:text-[9px] text-crt-white/80 tracking-wider hidden sm:block">WIKILINK</span>
      </router-link>

      <div class="flex items-center gap-1 sm:gap-2.5 min-w-0">
        <router-link v-if="showHomeShortcut" to="/" class="site-nav-group" style="text-decoration: none;">
          <div class="site-nav-icon-btn">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1" />
            </svg>
          </div>
          <span class="font-mono text-[10px] text-retro-muted hidden sm:inline pr-1">HOME</span>
        </router-link>

        <div v-if="showUtilityControls" class="site-nav-group shrink-0">
          <button @click="$emit('open-howto')" class="site-nav-icon-btn" title="How to play">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.065 2.05-1.822 3.772-1.822 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </button>
          <button @click="$emit('toggle-mute')" class="site-nav-icon-btn" :title="muted ? 'Unmute' : 'Mute'">
            <svg v-if="!muted" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072M12 6l-4 4H4v4h4l4 4V6z" />
            </svg>
            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707A1 1 0 0112 5v14a1 1 0 01-1.707.707L5.586 15zM17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2" />
            </svg>
          </button>
        </div>

        <template v-if="user">
          <div class="site-nav-user">
            <div class="flex items-center gap-1.5 px-1 sm:px-1.5" title="Your level">
              <span class="font-pixel text-[7px] sm:text-[8px] text-crt-green/70">LV</span>
              <span class="font-terminal text-sm text-crt-green leading-none">{{ level }}</span>
            </div>
            <div class="w-px h-4 bg-retro-border/30"></div>
            <router-link to="/profile" class="site-nav-profile-link min-w-0" title="Profile">
              <div class="site-nav-avatar" :style="avatarStyle">
                <svg
                  v-if="userProfileIcon"
                  class="w-3.5 h-3.5"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                  v-html="userProfileIcon.svgPath"
                ></svg>
                <span v-else>{{ user.username?.charAt(0).toUpperCase() }}</span>
              </div>
              <span class="font-mono text-[10px] sm:text-[11px] text-retro-light truncate max-w-[82px] sm:max-w-[100px]">{{ user.username }}</span>
            </router-link>
            <div class="w-px h-4 bg-retro-border/30 hidden sm:block"></div>
            <button @click="$emit('logout')" class="site-nav-icon-btn site-nav-icon-btn--dim" title="Logout">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
              </svg>
            </button>
          </div>
        </template>
        <button v-else @click="$emit('login')" class="site-nav-login-btn">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
          </svg>
          <span class="font-mono text-[10px] sm:text-[11px] tracking-wider">SIGN IN</span>
        </button>
      </div>
    </div>
  </header>

  <div v-if="showXpBar" class="relative z-20 px-4 sm:px-5 max-w-6xl mx-auto w-full">
    <div class="xp-bar mt-1">
      <div class="xp-bar-fill" :style="{ width: xpPercent + '%' }"></div>
    </div>
    <div class="flex items-center justify-between mt-0.5 mb-0">
      <span class="font-mono text-[8px] text-retro-muted">{{ currentXp }} / {{ nextLevelXp }} XP</span>
      <span class="font-mono text-[8px] text-crt-green">Level {{ level }}</span>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { getProfileIconById, getProfileAccentById } from '../../constants/profileIcons'

const props = defineProps({
  user: { type: Object, default: null },
  level: { type: Number, default: 1 },
  currentXp: { type: Number, default: 0 },
  nextLevelXp: { type: Number, default: 1000 },
  xpPercent: { type: Number, default: 0 },
  showXpBar: { type: Boolean, default: false },
  showUtilityControls: { type: Boolean, default: false },
  showHomeShortcut: { type: Boolean, default: false },
  muted: { type: Boolean, default: false },
})

const userProfileIcon = computed(() => {
  if (!props.user) return null
  return getProfileIconById(props.user.profile_icon)
})

const avatarStyle = computed(() => {
  if (!props.user) return null
  const accent = getProfileAccentById(props.user.profile_accent)
  if (!accent.color) return null
  return {
    color: accent.color,
    borderColor: `${accent.color}66`,
  }
})

defineEmits([
  'open-howto',
  'toggle-mute',
  'login',
  'logout',
])
</script>
