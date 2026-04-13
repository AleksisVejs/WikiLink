<template>
  <div class="min-h-screen flex flex-col items-center justify-center px-4 relative z-10">
    <div class="text-center animate-fade-in">
      <div class="font-pixel text-[10px] text-crt-red mb-3 tracking-[0.3em]">ERROR 404</div>
      <h1 class="font-pixel text-3xl sm:text-5xl mb-4"
          style="color: #ff4444; text-shadow: 0 0 15px rgba(255,68,68,0.5), 0 0 40px rgba(255,68,68,0.2);">
        PAGE NOT FOUND
      </h1>
      <p class="font-mono text-sm text-retro-muted mb-8 max-w-sm mx-auto">
        This article doesn't exist in our wiki. You may have followed a broken link.
      </p>
      <div class="retro-divider max-w-xs mx-auto mb-8"></div>
      <router-link to="/" class="btn-retro-primary inline-block">RETURN HOME</router-link>
    </div>
  </div>
</template>
