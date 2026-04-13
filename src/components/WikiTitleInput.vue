<template>
  <div class="relative" ref="rootEl">
    <label v-if="label" class="font-mono text-[9px] text-retro-muted block mb-1">{{ label }}</label>
    <input
      ref="inputEl"
      :value="modelValue"
      type="text"
      :placeholder="placeholder"
      autocomplete="off"
      autocapitalize="off"
      spellcheck="false"
      role="combobox"
      :aria-expanded="open"
      :aria-activedescendant="activeId"
      class="w-full px-2.5 py-2 rounded-lg font-mono text-xs bg-[#12131c] border border-retro-border text-crt-white placeholder:text-retro-muted/40 focus:border-crt-cyan focus:outline-none"
      @input="onInput"
      @focus="onFocus"
      @blur="scheduleBlurClose"
      @keydown="onKeydown"
    />
    <ul
      v-if="open && (suggestions.length || searching)"
      :id="listId"
      role="listbox"
      class="absolute z-30 left-0 right-0 mt-1 max-h-48 overflow-y-auto rounded-lg border border-retro-border shadow-xl"
      style="background: #0d0e15;"
      @mousedown.prevent
    >
      <li
        v-if="searching && !suggestions.length"
        class="px-2.5 py-2 font-mono text-[10px] text-retro-muted"
      >
        Searching…
      </li>
      <li
        v-for="(title, i) in suggestions"
        :id="`${listId}-opt-${i}`"
        :key="title"
        role="option"
        :aria-selected="i === highlightIndex"
        class="px-2.5 py-2 font-mono text-[11px] cursor-pointer border-b border-retro-border/30 last:border-0 transition-colors"
        :class="i === highlightIndex ? 'bg-crt-cyan/15 text-crt-cyan' : 'text-retro-light hover:bg-retro-surface/80'"
        @mousedown.prevent="selectTitle(title)"
      >
        {{ title.replace(/_/g, ' ') }}
      </li>
    </ul>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useWikipedia } from '../composables/useWikipedia'

const props = defineProps({
  modelValue: { type: String, default: '' },
  label: { type: String, default: '' },
  placeholder: { type: String, default: '' },
})

const emit = defineEmits(['update:modelValue'])

const wiki = useWikipedia()
const rootEl = ref(null)
const inputEl = ref(null)
const suggestions = ref([])
const open = ref(false)
const searching = ref(false)
const highlightIndex = ref(-1)
const debounceMs = 280
let debounceTimer = null
let blurTimer = null
let fetchGeneration = 0

const listId = `wiki-ac-${Math.random().toString(36).slice(2, 9)}`
const activeId = ref(undefined)

function scheduleFetch(q) {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => runFetch(q), debounceMs)
}

async function runFetch(q) {
  const trimmed = q.trim()
  if (trimmed.length < 2) {
    suggestions.value = []
    searching.value = false
    return
  }
  const gen = ++fetchGeneration
  searching.value = true
  try {
    const list = await wiki.searchArticleTitles(trimmed, 10)
    if (gen !== fetchGeneration) return
    if (trimmed !== props.modelValue.trim()) return
    suggestions.value = list
    highlightIndex.value = list.length ? 0 : -1
    syncActiveId()
  } catch {
    if (gen === fetchGeneration) suggestions.value = []
  } finally {
    if (gen === fetchGeneration) searching.value = false
  }
}

function onInput(e) {
  const v = e.target.value
  emit('update:modelValue', v)
  open.value = true
  highlightIndex.value = -1
  scheduleFetch(v)
}

function onFocus() {
  clearBlurTimer()
  open.value = true
  if (props.modelValue.trim().length >= 2) {
    scheduleFetch(props.modelValue)
  }
}

function scheduleBlurClose() {
  blurTimer = setTimeout(() => {
    open.value = false
    highlightIndex.value = -1
    suggestions.value = []
    searching.value = false
  }, 150)
}

function clearBlurTimer() {
  if (blurTimer) {
    clearTimeout(blurTimer)
    blurTimer = null
  }
}

function selectTitle(title) {
  clearBlurTimer()
  emit('update:modelValue', title)
  open.value = false
  suggestions.value = []
  highlightIndex.value = -1
  inputEl.value?.focus()
}

function syncActiveId() {
  const i = highlightIndex.value
  activeId.value = i >= 0 ? `${listId}-opt-${i}` : undefined
}

function onKeydown(e) {
  if (!open.value || (!suggestions.value.length && !searching.value)) {
    if (e.key === 'Escape') open.value = false
    return
  }

  if (e.key === 'ArrowDown') {
    e.preventDefault()
    if (!suggestions.value.length) return
    highlightIndex.value = Math.min(suggestions.value.length - 1, highlightIndex.value + 1)
    if (highlightIndex.value < 0) highlightIndex.value = 0
    syncActiveId()
  } else if (e.key === 'ArrowUp') {
    e.preventDefault()
    if (!suggestions.value.length) return
    highlightIndex.value = Math.max(0, highlightIndex.value - 1)
    syncActiveId()
  } else if (e.key === 'Enter') {
    if (highlightIndex.value >= 0 && suggestions.value[highlightIndex.value]) {
      e.preventDefault()
      selectTitle(suggestions.value[highlightIndex.value])
    }
  } else if (e.key === 'Escape') {
    e.preventDefault()
    open.value = false
    highlightIndex.value = -1
  }
}

function onDocPointerDown(ev) {
  if (!rootEl.value?.contains(ev.target)) {
    open.value = false
  }
}

onMounted(() => {
  document.addEventListener('pointerdown', onDocPointerDown, true)
})

onUnmounted(() => {
  document.removeEventListener('pointerdown', onDocPointerDown, true)
  clearTimeout(debounceTimer)
  clearTimeout(blurTimer)
})
</script>
