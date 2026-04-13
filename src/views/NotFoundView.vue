<template>
  <div class="min-h-screen flex flex-col items-center justify-center px-4 relative z-10">
    <div class="text-center animate-fade-in max-w-md w-full">
      <!-- Glitch-styled error code -->
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
</template>
