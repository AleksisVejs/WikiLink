import { ref } from 'vue'
import { useApi } from './useApi.js'
import { useWikipedia } from './useWikipedia.js'

const trendingArticles = ref([])
const trendingLoaded = ref(false)
const trendingLoading = ref(false)
let trendingFailedAt = 0
const TRENDING_RETRY_MS = 60_000
const FALLBACK_TRENDING_ARTICLES = [
  { title: 'World War II', views: 1900000, rank: 1 },
  { title: 'United States', views: 1750000, rank: 2 },
  { title: 'India', views: 1600000, rank: 3 },
  { title: 'YouTube', views: 1450000, rank: 4 },
  { title: 'Artificial intelligence', views: 1320000, rank: 5 },
  { title: 'Lionel Messi', views: 1180000, rank: 6 },
  { title: 'Taylor Swift', views: 1090000, rank: 7 },
  { title: 'Cristiano Ronaldo', views: 980000, rank: 8 },
  { title: 'Python (programming language)', views: 910000, rank: 9 },
  { title: 'Minecraft', views: 860000, rank: 10 },
]

function fisherYatesShuffle(arr) {
  for (let i = arr.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1));
    [arr[i], arr[j]] = [arr[j], arr[i]]
  }
  return arr
}

export function useTrending() {
  const api = useApi()
  const wiki = useWikipedia()

  function ensureFallbackArticles() {
    if (trendingArticles.value.length > 0) return
    trendingArticles.value = [...FALLBACK_TRENDING_ARTICLES]
  }

  async function fetchTrending() {
    if (trendingLoaded.value || trendingLoading.value) return trendingArticles.value
    if (trendingFailedAt && (Date.now() - trendingFailedAt) < TRENDING_RETRY_MS) return trendingArticles.value
    trendingLoading.value = true
    try {
      const data = await api.get('/trending')
      if (data.articles && data.articles.length > 0) {
        trendingArticles.value = data.articles
        trendingLoaded.value = true
        trendingFailedAt = 0
      } else {
        trendingFailedAt = Date.now()
        ensureFallbackArticles()
      }
    } catch {
      trendingFailedAt = Date.now()
      ensureFallbackArticles()
    } finally {
      trendingLoading.value = false
    }
    return trendingArticles.value
  }

  async function getTrendingPair() {
    const articles = await fetchTrending()
    if (!articles || articles.length < 2) return null

    const shuffled = fisherYatesShuffle([...articles])
    const candidates = shuffled.slice(0, 10)

    const summaryCache = new Map()
    async function getSummary(article) {
      if (summaryCache.has(article.title)) return summaryCache.get(article.title)
      const summary = await wiki.getArticleSummary(article.title)
      summaryCache.set(article.title, summary)
      return summary
    }

    for (let i = 0; i < candidates.length; i++) {
      for (let j = i + 1; j < candidates.length; j++) {
        const [startSummary, endSummary] = await Promise.all([
          getSummary(candidates[i]),
          getSummary(candidates[j]),
        ])
        if (startSummary && endSummary && startSummary.title !== endSummary.title) {
          return {
            start: { ...startSummary, views: candidates[i].views },
            end: { ...endSummary, views: candidates[j].views },
          }
        }
      }
    }
    return null
  }

  function formatViews(views) {
    if (views == null) return ''
    if (views >= 1000000) return `${(views / 1000000).toFixed(1)}M`
    if (views >= 1000) return `${(views / 1000).toFixed(0)}K`
    return String(views)
  }

  return {
    trendingArticles,
    fetchTrending,
    getTrendingPair,
    formatViews,
  }
}
