<template>
  <Teleport to="body">
    <transition name="fade">
      <div v-if="open" class="fixed inset-0 z-[60] flex items-center justify-center p-3 sm:p-4">
        <div class="absolute inset-0 bg-black/85 backdrop-blur-sm" @click="$emit('close')"></div>
        <div class="relative rounded-2xl p-6 max-w-sm w-full animate-scale-in" style="background: #0d0e15; border: 1.5px solid rgba(37,39,56,0.7); box-shadow: 0 0 60px rgba(0,0,0,0.8), 0 0 30px rgba(0,229,255,0.04);">
          <div class="flex items-center justify-between mb-5">
            <div class="flex items-center gap-1 rounded-lg p-0.5" style="background: rgba(18,19,28,0.6); border: 1px solid rgba(37,39,56,0.4);">
              <button @click="$emit('set-mode', 'login')" class="font-pixel text-[8px] px-3 py-1.5 rounded-md transition-all" :class="mode === 'login' ? 'text-crt-green bg-crt-green/10' : 'text-retro-muted hover:text-crt-white'">LOGIN</button>
              <button @click="$emit('set-mode', 'register')" class="font-pixel text-[8px] px-3 py-1.5 rounded-md transition-all" :class="mode === 'register' ? 'text-crt-cyan bg-crt-cyan/10' : 'text-retro-muted hover:text-crt-white'">REGISTER</button>
            </div>
            <button @click="$emit('close')" class="btn-ghost p-1.5">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          <form @submit.prevent="$emit('submit')" class="space-y-3">
            <div>
              <label class="font-mono text-[10px] text-retro-muted block mb-1">Username</label>
              <input :value="username" @input="$emit('update:username', $event.target.value)" type="text" autocomplete="username" required minlength="2" maxlength="24" class="w-full px-3 py-2 rounded-lg font-mono text-sm bg-[#12131c] border border-retro-border text-crt-white focus:border-crt-cyan focus:outline-none" />
            </div>
            <div>
              <label class="font-mono text-[10px] text-retro-muted block mb-1">Password</label>
              <div class="relative">
                <input :value="password" @input="$emit('update:password', $event.target.value)" :type="showPassword ? 'text' : 'password'" :autocomplete="mode === 'login' ? 'current-password' : 'new-password'" required minlength="6" class="w-full px-3 py-2 pr-10 rounded-lg font-mono text-sm bg-[#12131c] border border-retro-border text-crt-white focus:border-crt-cyan focus:outline-none" />
                <button type="button" @click="$emit('toggle-password')" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-retro-muted hover:text-crt-white transition-colors" tabindex="-1">
                  <svg v-if="!showPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                  <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                  </svg>
                </button>
              </div>
            </div>
            <div v-if="mode === 'register'">
              <label class="font-mono text-[10px] text-retro-muted block mb-1">Confirm Password</label>
              <div class="relative">
                <input :value="confirmPassword" @input="$emit('update:confirm-password', $event.target.value)" :type="showConfirmPassword ? 'text' : 'password'" autocomplete="new-password" required minlength="6" class="w-full px-3 py-2 pr-10 rounded-lg font-mono text-sm bg-[#12131c] border border-retro-border text-crt-white focus:border-crt-cyan focus:outline-none" />
                <button type="button" @click="$emit('toggle-confirm-password')" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-retro-muted hover:text-crt-white transition-colors" tabindex="-1">
                  <svg v-if="!showConfirmPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                  <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                  </svg>
                </button>
              </div>
            </div>
            <div v-if="error" class="font-mono text-[11px] text-crt-red">{{ error }}</div>
            <button type="submit" :disabled="loading" class="btn-retro-primary w-full !py-2.5">
              {{ loading ? 'LOADING...' : mode === 'login' ? 'LOGIN' : 'CREATE ACCOUNT' }}
            </button>
          </form>
        </div>
      </div>
    </transition>
  </Teleport>
</template>

<script setup>
defineProps({
  open: { type: Boolean, required: true },
  mode: { type: String, required: true },
  username: { type: String, required: true },
  password: { type: String, required: true },
  confirmPassword: { type: String, required: true },
  showPassword: { type: Boolean, required: true },
  showConfirmPassword: { type: Boolean, required: true },
  error: { type: String, required: true },
  loading: { type: Boolean, required: true },
})

defineEmits([
  'close',
  'set-mode',
  'submit',
  'update:username',
  'update:password',
  'update:confirm-password',
  'toggle-password',
  'toggle-confirm-password',
])
</script>
