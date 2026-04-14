<template>
  <main class="flex-1" :class="{ 'screen-shake': showScreenShake }" @click.self="$emit('close-hint-menu')">
    <div v-if="initialLoading" class="flex items-center justify-center" style="min-height: calc(100vh - 3rem);">
      <div class="text-center max-w-sm px-4">
        <div class="inline-flex items-center gap-2 mb-6 px-4 py-2 rounded-lg" style="background: rgba(57,255,20,0.04); border: 1px solid rgba(57,255,20,0.12);">
          <span class="w-2 h-2 rounded-full bg-crt-green/60 animate-glow-pulse" style="box-shadow: 0 0 6px rgba(57,255,20,0.4);"></span>
          <span class="font-pixel text-[9px] text-crt-green tracking-[0.2em]">INITIALIZING</span>
        </div>
        <div class="font-mono text-sm text-retro-muted space-y-3 text-left rounded-xl p-5" style="background: rgba(18,19,28,0.5); border: 1px solid rgba(37,39,56,0.4);">
          <p v-if="bootStep >= 1" class="animate-fade-in flex items-center gap-3">
            <span class="w-6 h-6 rounded-md flex items-center justify-center shrink-0" style="background: rgba(57,255,20,0.08); border: 1px solid rgba(57,255,20,0.15);">
              <svg class="w-3.5 h-3.5 text-crt-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
              </svg>
            </span>
            <span>Connecting to Wikipedia API...</span>
          </p>
          <p v-if="bootStep >= 2" class="animate-fade-in flex items-center gap-3">
            <span class="w-6 h-6 rounded-md flex items-center justify-center shrink-0" style="background: rgba(0,229,255,0.08); border: 1px solid rgba(0,229,255,0.15);">
              <svg class="w-3.5 h-3.5 text-crt-cyan" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
              </svg>
            </span>
            <span>Fetching articles...</span>
          </p>
          <p v-if="bootStep >= 3" class="animate-fade-in flex items-center gap-3">
            <span class="w-6 h-6 rounded-md flex items-center justify-center shrink-0" style="background: rgba(255,191,0,0.08); border: 1px solid rgba(255,191,0,0.15);">
              <svg class="w-3.5 h-3.5 text-crt-amber" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
              </svg>
            </span>
            <span>{{ loadingMessage }}</span>
          </p>
        </div>
        <div class="mt-6 retro-divider max-w-[200px] mx-auto"></div>
        <p class="font-terminal text-xl text-crt-green animate-blink mt-4">_</p>
      </div>
    </div>

    <div v-else class="max-w-4xl mx-auto px-2.5 sm:px-5 py-3 sm:py-8">
      <div v-if="loading" class="space-y-4 animate-fade-in">
        <div class="skeleton h-10 w-3/4 rounded-lg"></div>
        <div class="skeleton h-px w-full"></div>
        <div class="space-y-3">
          <div class="skeleton h-4 w-full"></div>
          <div class="skeleton h-4 w-11/12"></div>
          <div class="skeleton h-4 w-4/5"></div>
          <div class="skeleton h-4 w-full"></div>
          <div class="skeleton h-4 w-3/4"></div>
        </div>
        <div class="skeleton h-4 w-1/3 mt-6"></div>
        <div class="space-y-3">
          <div class="skeleton h-4 w-full"></div>
          <div class="skeleton h-4 w-5/6"></div>
          <div class="skeleton h-4 w-full"></div>
        </div>
      </div>

      <div v-else-if="error" class="text-center py-24">
        <div class="font-pixel text-[9px] text-crt-red mb-3 tracking-widest">ERROR</div>
        <p class="font-mono text-sm text-retro-light mb-5">{{ error }}</p>
        <button @click="$emit('retry')" class="btn-retro-secondary">RETRY</button>
      </div>

      <article v-else-if="articleData" class="animate-fade-in rounded-xl sm:rounded-2xl p-3 sm:p-5"
               style="background: linear-gradient(180deg, #0f111a, #0b0c13); border: 1px solid rgba(37,39,56,0.75); box-shadow: 0 8px 24px rgba(0,0,0,0.35);">
        <h1 class="font-terminal text-xl sm:text-4xl text-crt-cyan mb-3 sm:mb-5 pb-2.5 sm:pb-4 relative leading-tight" style="text-shadow: 0 0 12px rgba(0,229,255,0.25);" v-html="articleData.displayTitle"></h1>
        <div class="retro-divider mb-4 sm:mb-5 -mt-1"></div>
        <div
          class="wiki-content"
          :class="{ 'fog-mode': hasFogModifier }"
          @click="$emit('link-click', $event)"
          @mouseover="$emit('link-hover', $event)"
          @mouseout="$emit('link-hover-end', $event)"
          v-html="processedHtml"
        ></div>
      </article>
    </div>
  </main>
</template>

<script setup>
defineProps({
  initialLoading: { type: Boolean, required: true },
  bootStep: { type: Number, required: true },
  loadingMessage: { type: String, required: true },
  showScreenShake: { type: Boolean, required: true },
  loading: { type: Boolean, required: true },
  error: { type: String, default: '' },
  articleData: { type: Object, default: null },
  processedHtml: { type: String, required: true },
  hasFogModifier: { type: Boolean, required: true },
})

defineEmits(['close-hint-menu', 'retry', 'link-click', 'link-hover', 'link-hover-end'])
</script>
