import { ref } from 'vue'
import { useApi } from './useApi.js'
import { useWikipedia } from './useWikipedia.js'

const trendingArticles = ref([])
const trendingLoaded = ref(false)
const trendingLoading = ref(false)
let trendingFailedAt = 0
const TRENDING_RETRY_MS = 60_000

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
      }
    } catch {
      trendingFailedAt = Date.now()
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
    if (!views) return ''
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
