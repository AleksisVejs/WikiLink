import { ref } from 'vue'

const toasts = ref([])
let nextId = 0

export function useToast() {
  function addToast(message, type = 'info', duration = 3000) {
    const id = nextId++
    toasts.value.push({ id, message, type })
    setTimeout(() => {
      toasts.value = toasts.value.filter(t => t.id !== id)
    }, duration)
  }

  return {
    toasts,
    info: (msg) => addToast(msg, 'info'),
    success: (msg) => addToast(msg, 'success'),
    warn: (msg) => addToast(msg, 'warning'),
    error: (msg) => addToast(msg, 'error', 4000),
  }
}
