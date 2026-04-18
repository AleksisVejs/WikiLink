/**
 * Wikipedia API titles for display (underscores → spaces).
 */
export function formatWikiTitle(raw) {
  if (raw == null) return ''
  return String(raw).replace(/_/g, ' ').trim()
}
