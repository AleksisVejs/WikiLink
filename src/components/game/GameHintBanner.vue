<template>
  <div v-if="hintResult" class="sticky top-12 z-30 mx-auto max-w-4xl px-3 sm:px-4">
    <div class="hint-banner mt-2" :class="'hint-banner--' + hintResult.type">
      <div class="hint-banner-glow"></div>
      <div class="relative flex items-center gap-3 px-4 py-3">
        <div class="hint-banner-icon shrink-0" :class="'hint-banner-icon--' + hintResult.type">
          <svg v-if="hintResult.type === 'category'" class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
          </svg>
          <svg v-else class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z" />
          </svg>
        </div>

        <div class="flex-1 min-w-0">
          <div class="font-pixel text-[7px] tracking-[0.15em] mb-0.5" :class="hintResult.type === 'category' ? 'text-crt-cyan/50' : 'text-crt-amber/50'">
            {{ hintResult.type === 'category' ? 'CATEGORY HINT' : 'TEMPERATURE CHECK' }}
          </div>
          <div class="font-mono text-[12px] sm:text-[13px] leading-snug" :class="hintBannerTextClass">
            {{ hintResult.text }}
          </div>
        </div>

        <button @click="$emit('dismiss')" class="shrink-0 p-1 rounded-md transition-colors hover:bg-white/5">
          <svg class="w-3.5 h-3.5 text-retro-muted/40 hover:text-retro-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <div v-if="hintResult.type === 'hotcold'" class="hint-temp-bar">
        <div class="hint-temp-fill" :style="{ width: hintTemperature + '%' }"></div>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  hintResult: { type: Object, default: null },
  hintTemperature: { type: Number, required: true },
  hintBannerTextClass: { type: String, required: true },
})

defineEmits(['dismiss'])
</script>
