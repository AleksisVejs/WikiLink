<template>
  <div class="min-h-screen bg-retro-black relative">
    <!-- Layered atmospheric background -->
    <div class="fixed inset-0 pointer-events-none">
      <div class="absolute -top-32 -left-32 w-[600px] h-[600px] rounded-full blur-[160px] animate-glow-breathe"
           style="background: radial-gradient(circle, rgba(57,255,20,0.04), transparent 70%)"></div>
      <div class="absolute top-1/2 -right-40 w-[500px] h-[500px] rounded-full blur-[140px] animate-glow-breathe"
           style="background: radial-gradient(circle, rgba(0,229,255,0.035), transparent 70%); animation-delay: 2s;"></div>
      <div class="absolute -bottom-32 left-1/3 w-[500px] h-[500px] rounded-full blur-[140px] animate-glow-breathe"
           style="background: radial-gradient(circle, rgba(255,46,204,0.025), transparent 70%); animation-delay: 4s;"></div>
      <div class="absolute inset-0 opacity-[0.025]"
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
