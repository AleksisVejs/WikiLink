import { ref } from 'vue'

const muted = ref(localStorage.getItem('wikilink_muted') === 'true')
let audioCtx = null

function getCtx() {
  if (!audioCtx) audioCtx = new (window.AudioContext || window.webkitAudioContext)()
  if (audioCtx.state === 'suspended') audioCtx.resume()
  return audioCtx
}

function tone(freq, duration, type = 'square', volume = 0.07) {
  if (muted.value) return
  try {
    const ctx = getCtx()
    const osc = ctx.createOscillator()
    const gain = ctx.createGain()
    osc.type = type
    osc.frequency.value = freq
    gain.gain.value = volume
    osc.connect(gain)
    gain.connect(ctx.destination)
    osc.start(ctx.currentTime)
    gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + duration)
    osc.stop(ctx.currentTime + duration)
  } catch { /* audio not supported */ }
}

export function useSound() {
  function toggleMute() {
    muted.value = !muted.value
    localStorage.setItem('wikilink_muted', String(muted.value))
  }

  const playClick = () => tone(800, 0.04)
  const playNavigate = () => { tone(500, 0.06); setTimeout(() => tone(720, 0.07), 50) }
  const playBack = () => tone(350, 0.06, 'triangle')
  const playWin = () => {
    tone(523, 0.12, 'square', 0.05)
    setTimeout(() => tone(659, 0.12, 'square', 0.05), 120)
    setTimeout(() => tone(784, 0.25, 'square', 0.05), 240)
  }
  const playLose = () => {
    tone(400, 0.18, 'sawtooth', 0.04)
    setTimeout(() => tone(280, 0.3, 'sawtooth', 0.04), 180)
  }
  const playStart = () => {
    tone(440, 0.08, 'square', 0.04)
    setTimeout(() => tone(660, 0.12, 'square', 0.04), 80)
  }
  const playCombo = (level) => {
    const base = 600 + Math.min(level, 10) * 50
    tone(base, 0.06, 'square', 0.03)
    setTimeout(() => tone(base + 100, 0.06, 'square', 0.03), 40)
  }
  const playHint = () => {
    tone(880, 0.06, 'sine', 0.04)
    setTimeout(() => tone(660, 0.1, 'sine', 0.04), 60)
  }
  const playAchievement = () => {
    tone(523, 0.1, 'square', 0.04)
    setTimeout(() => tone(659, 0.1, 'square', 0.04), 100)
    setTimeout(() => tone(784, 0.1, 'square', 0.04), 200)
    setTimeout(() => tone(1047, 0.2, 'square', 0.04), 300)
  }
  const playLevelUp = () => {
    const notes = [523, 587, 659, 784, 880, 1047]
    notes.forEach((freq, i) => {
      setTimeout(() => tone(freq, 0.12, 'square', 0.04), i * 80)
    })
  }
  return {
    muted, toggleMute, playClick, playNavigate, playBack, playWin, playLose, playStart,
    playCombo, playHint, playAchievement, playLevelUp,
  }
}
