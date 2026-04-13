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
  </div>
</template>

<script setup>
import { useToast } from './composables/useToast'
const { toasts } = useToast()
</script>
