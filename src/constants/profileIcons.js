export const PROFILE_ICON_OPTIONS = [
  { id: 'rookie', name: 'ROOKIE SHIELD', minLevel: 1, svgPath: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3l7 3v5c0 5-3.5 8.5-7 10-3.5-1.5-7-5-7-10V6l7-3z" />' },
  { id: 'compass', name: 'PATH COMPASS', minLevel: 5, svgPath: '<circle cx="12" cy="12" r="8" stroke-width="1.8" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14.5 9.5l-2 5-5 2 2-5 5-2z" />' },
  { id: 'spark', name: 'ARC SPARK', minLevel: 10, svgPath: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13 3L5 14h6l-1 7 9-12h-6l0-6z" />' },
  { id: 'crown', name: 'PIXEL CROWN', minLevel: 15, svgPath: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 18h16l-1-8-4 3-3-5-3 5-4-3-1 8z" />' },
  { id: 'star', name: 'WIKI STAR', minLevel: 20, svgPath: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3l2.6 5.3 5.9.9-4.2 4.1 1 5.8L12 16.8 6.7 19l1-5.8-4.2-4.1 5.9-.9L12 3z" />' },
]

export const PROFILE_ACCENT_OPTIONS = [
  { id: 'rank', name: 'RANK COLOR', minLevel: 1, color: null },
  { id: 'neon', name: 'NEON GREEN', minLevel: 1, color: '#39ff14' },
  { id: 'cyan', name: 'CRT CYAN', minLevel: 5, color: '#00e5ff' },
  { id: 'amber', name: 'ARCADE AMBER', minLevel: 10, color: '#ffbf00' },
  { id: 'purple', name: 'RETRO PURPLE', minLevel: 15, color: '#b44cff' },
]

export const PROFILE_TITLE_OPTIONS = [
  { id: 'newcomer', name: 'NEWCOMER', minLevel: 1 },
  { id: 'pathfinder', name: 'PATHFINDER', minLevel: 5 },
  { id: 'specialist', name: 'SPECIALIST', minLevel: 10 },
  { id: 'veteran', name: 'VETERAN', minLevel: 15 },
  { id: 'expert', name: 'EXPERT', minLevel: 20 },
  { id: 'master', name: 'MASTER', minLevel: 30 },
  { id: 'grandmaster', name: 'GRANDMASTER', minLevel: 40 },
  { id: 'legend', name: 'WIKI LEGEND', minLevel: 50 },
]

export const PROFILE_BANNER_OPTIONS = [
  { id: 'default', name: 'DEFAULT GLOW', minLevel: 1, css: 'linear-gradient(135deg, var(--accent)26, transparent)' },
  { id: 'matrix', name: 'MATRIX', minLevel: 8, css: 'linear-gradient(135deg, #39ff1426, #00e5ff14)' },
  { id: 'sunset', name: 'SUNSET', minLevel: 12, css: 'linear-gradient(135deg, #ff6b2b30, #ffbf001f)' },
  { id: 'royal', name: 'ROYAL', minLevel: 18, css: 'linear-gradient(135deg, #b44cff33, #4c9fff1f)' },
]

export const PROFILE_NAMEPLATE_BORDER_OPTIONS = [
  { id: 'default', name: 'DEFAULT', minLevel: 1, css: '2px solid var(--accent)' },
  { id: 'dashed', name: 'DASHED', minLevel: 10, css: '2px dashed var(--accent)' },
  { id: 'double', name: 'DOUBLE', minLevel: 18, css: '3px double var(--accent)' },
  { id: 'glow', name: 'NEON GLOW', minLevel: 25, css: '2px solid var(--accent)' },
]

export function getProfileIconById(iconId) {
  return PROFILE_ICON_OPTIONS.find(icon => icon.id === iconId) || PROFILE_ICON_OPTIONS[0]
}

export function getProfileAccentById(accentId) {
  return PROFILE_ACCENT_OPTIONS.find(accent => accent.id === accentId) || PROFILE_ACCENT_OPTIONS[0]
}

export function getProfileTitleById(titleId) {
  return PROFILE_TITLE_OPTIONS.find(title => title.id === titleId) || PROFILE_TITLE_OPTIONS[0]
}

export function getProfileBannerById(bannerId) {
  return PROFILE_BANNER_OPTIONS.find(banner => banner.id === bannerId) || PROFILE_BANNER_OPTIONS[0]
}

export function getProfileNameplateBorderById(borderId) {
  return PROFILE_NAMEPLATE_BORDER_OPTIONS.find(border => border.id === borderId) || PROFILE_NAMEPLATE_BORDER_OPTIONS[0]
}
