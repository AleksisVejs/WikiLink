import { ref } from 'vue'
import { utcDateKey, seedHash, seededRandom } from '../utils/dailySeed.js'

const WIKI_API = 'https://en.wikipedia.org/api/rest_v1'
const WIKI_ACTION_API = 'https://en.wikipedia.org/w/api.php'
const MAX_PAIR_RETRIES = 5
const DAILY_CACHE_KEY = 'wikilink_daily_pair'
const DAILY_SEARCH_LIMIT = 5

/** Terms for deterministic daily fallback (no random API). */
const DAILY_FALLBACK_TERMS = [
  'physics theory', 'ancient civilization', 'capital city', 'olympic games athlete',
  'mammal species habitat', 'music genre history', 'film director filmography',
  'computer science algorithm', 'cuisine traditional dish', 'chemical element',
  'world war battle', 'country republic', 'football championship', 'bird species migration',
  'classical composer symphony', 'television series season', 'artificial intelligence',
  'mountain range peak', 'invention patent', 'desert landscape', 'jazz musician',
]

const EXCLUDED_PREFIXES = [
  'Wikipedia:', 'Help:', 'Template:', 'Category:', 'Portal:',
  'File:', 'Talk:', 'User:', 'Special:', 'MediaWiki:', 'Draft:',
  'Module:', 'TimedText:', 'Book:'
]

function isValidArticleTitle(title) {
  return !EXCLUDED_PREFIXES.some(prefix => title.startsWith(prefix))
    && !title.includes('#')
    && title.length > 0
}

export function useWikipedia() {
  const loading = ref(false)
  const error = ref(null)

  async function getRandomArticle() {
    loading.value = true
    error.value = null
    try {
      const res = await fetch(`${WIKI_API}/page/random/summary`, { redirect: 'follow' })
      if (!res.ok) throw new Error('Failed to fetch random article')
      const data = await res.json()
      return {
        title: data.title,
        displayTitle: data.displaytitle || data.title,
        description: data.description || '',
        extract: data.extract || '',
        thumbnail: data.thumbnail?.source || null,
      }
    } catch (e) {
      error.value = e.message
      return null
    } finally {
      loading.value = false
    }
  }

  async function getRandomArticleByGenre(genre) {
    if (!genre || !genre.searchTerms) return getRandomArticle()

    loading.value = true
    error.value = null
    try {
      const searchTerm = genre.searchTerms[Math.floor(Math.random() * genre.searchTerms.length)]
      const offset = Math.floor(Math.random() * 80)

      const params = new URLSearchParams({
        action: 'query', list: 'search', srsearch: searchTerm,
        srnamespace: '0', srlimit: '20', sroffset: String(offset),
        format: 'json', origin: '*',
      })

      const res = await fetch(`${WIKI_ACTION_API}?${params}`)
      if (!res.ok) throw new Error('Search failed')
      const data = await res.json()
      const results = data.query?.search
      if (!results?.length) return getRandomArticle()

      const pick = results[Math.floor(Math.random() * results.length)]
      const summary = await getArticleSummary(pick.title)
      return summary || { title: pick.title, displayTitle: pick.title, description: '', extract: '', thumbnail: null }
    } catch (e) {
      error.value = e.message
      return getRandomArticle()
    } finally {
      loading.value = false
    }
  }

  async function getRandomPairByGenre(genre, retries = 0) {
    if (retries >= MAX_PAIR_RETRIES) {
      error.value = 'Could not find a valid article pair. Please try again.'
      return null
    }
    const fetcher = genre?.searchTerms ? () => getRandomArticleByGenre(genre) : getRandomArticle
    const [start, end] = await Promise.all([fetcher(), fetcher()])
    if (start && end && start.title !== end.title) return { start, end }
    return getRandomPairByGenre(genre, retries + 1)
  }

  async function searchByTerm(term, offset = 0, resultPickIndex = null) {
    const params = new URLSearchParams({
      action: 'query', list: 'search', srsearch: term,
      srnamespace: '0', srlimit: String(DAILY_SEARCH_LIMIT), sroffset: String(offset),
      format: 'json', origin: '*',
    })
    const res = await fetch(`${WIKI_ACTION_API}?${params}`)
    if (!res.ok) return null
    const data = await res.json()
    const results = data.query?.search
    if (!results?.length) return null
    const n = results.length
    const idx =
      resultPickIndex != null
        ? ((Number(resultPickIndex) % n) + n) % n
        : Math.floor(Math.random() * n)
    const pick = results[idx]
    const summary = await getArticleSummary(pick.title)
    return summary || { title: pick.title, displayTitle: pick.title, description: '', extract: '', thumbnail: null }
  }

  async function getDeterministicPairForUtcDate(utcDateStr) {
    const rng = seededRandom(seedHash(`wikilink-daily-fallback-${utcDateStr}`))
    for (let attempt = 0; attempt < 24; attempt++) {
      const term1 = DAILY_FALLBACK_TERMS[Math.floor(rng() * DAILY_FALLBACK_TERMS.length)]
      const offset1 = Math.floor(rng() * 40)
      const pick1 = Math.floor(rng() * DAILY_SEARCH_LIMIT)
      const term2 = DAILY_FALLBACK_TERMS[Math.floor(rng() * DAILY_FALLBACK_TERMS.length)]
      const offset2 = Math.floor(rng() * 40)
      const pick2 = Math.floor(rng() * DAILY_SEARCH_LIMIT)
      const [start, end] = await Promise.all([
        searchByTerm(term1, offset1, pick1),
        searchByTerm(term2, offset2, pick2),
      ])
      if (start && end && start.title !== end.title) return { start, end }
    }
    return null
  }

  async function getDailyPair(searchParams) {
    const today = utcDateKey()
    try {
      const cached = JSON.parse(localStorage.getItem(DAILY_CACHE_KEY) || '{}')
      if (cached.date === today && cached.start && cached.end) {
        return { start: cached.start, end: cached.end }
      }
    } catch { /* ignore */ }

    loading.value = true
    error.value = null
    try {
      const pick1 = searchParams.pick1 ?? 0
      const pick2 = searchParams.pick2 ?? 0
      const [start, end] = await Promise.all([
        searchByTerm(searchParams.term1, searchParams.offset1, pick1),
        searchByTerm(searchParams.term2, searchParams.offset2, pick2),
      ])

      if (!start || !end || start.title === end.title) {
        const pair = await getDeterministicPairForUtcDate(today)
        if (!pair) return null
        localStorage.setItem(DAILY_CACHE_KEY, JSON.stringify({ date: today, start: pair.start, end: pair.end }))
        return pair
      }

      localStorage.setItem(DAILY_CACHE_KEY, JSON.stringify({ date: today, start, end }))
      return { start, end }
    } catch (e) {
      error.value = e.message
      return null
    } finally {
      loading.value = false
    }
  }

  async function getArticleContent(title) {
    loading.value = true
    error.value = null
    try {
      const params = new URLSearchParams({
        action: 'parse', page: title, format: 'json', origin: '*',
        prop: 'text|displaytitle|links', disableeditsection: 'true', disabletoc: 'true',
      })

      const res = await fetch(`${WIKI_ACTION_API}?${params}`)
      if (!res.ok) throw new Error('Failed to fetch article')
      const data = await res.json()
      if (data.error) throw new Error(data.error.info)

      const links = (data.parse.links || [])
        .filter(l => l.ns === 0 && l.exists !== undefined)
        .map(l => l['*'])
        .filter(isValidArticleTitle)

      return {
        title: data.parse.title,
        displayTitle: data.parse.displaytitle,
        html: data.parse.text['*'],
        links,
      }
    } catch (e) {
      error.value = e.message
      return null
    } finally {
      loading.value = false
    }
  }

  async function getArticleSummary(title) {
    try {
      const res = await fetch(`${WIKI_API}/page/summary/${encodeURIComponent(title)}`)
      if (!res.ok) return null
      const data = await res.json()
      return {
        title: data.title,
        displayTitle: data.displaytitle || data.title,
        description: data.description || '',
        extract: data.extract || '',
        thumbnail: data.thumbnail?.source || null,
      }
    } catch {
      return null
    }
  }

  /**
   * Lightweight title suggestions for autocomplete (main namespace only).
   * @param {string} query
   * @param {number} limit
   * @returns {Promise<string[]>}
   */
  async function searchArticleTitles(query, limit = 10) {
    const q = String(query || '').trim()
    if (q.length < 2) return []
    try {
      const params = new URLSearchParams({
        action: 'opensearch',
        search: q,
        limit: String(Math.min(25, Math.max(1, limit))),
        namespace: '0',
        format: 'json',
        origin: '*',
      })
      const res = await fetch(`${WIKI_ACTION_API}?${params}`)
      if (!res.ok) return []
      const data = await res.json()
      const titles = Array.isArray(data[1]) ? data[1] : []
      return titles.filter(isValidArticleTitle)
    } catch {
      return []
    }
  }

  return {
    loading,
    error,
    getRandomArticle,
    getRandomArticleByGenre,
    getRandomPairByGenre,
    getArticleContent,
    getArticleSummary,
    getDailyPair,
    searchArticleTitles,
  }
}
