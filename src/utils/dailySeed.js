/** UTC calendar date YYYY-MM-DD (same "day" worldwide for the daily puzzle). */
export function utcDateKey() {
  return new Date().toISOString().slice(0, 10)
}

export function seedHash(str) {
  let h = 0
  for (let i = 0; i < str.length; i++) h = ((h << 5) - h + str.charCodeAt(i)) | 0
  return h
}

export function seededRandom(seed) {
  let s = Math.abs(seed)
  return () => {
    s = (s * 1103515245 + 12345) & 0x7fffffff
    return s / 0x7fffffff
  }
}
