import { ref } from 'vue'
import { useApi } from './useApi.js'
import { useWikipedia } from './useWikipedia.js'

const trendingArticles = ref([])
const trendingLoaded = ref(false)
const trendingLoading = ref(false)

export function useTrending() {
  const api = useApi()
  const wiki = useWikipedia()

  async function fetchTrending() {
    if (trendingLoaded.value || trendingLoading.value) return trendingArticles.value
    trendingLoading.value = true
    try {
      const data = await api.get('/trending')
      if (data.articles && data.articles.length > 0) {
        trendingArticles.value = data.articles
        trendingLoaded.value = true
      }
    } catch {
      /* will fall back to random if trending unavailable */
    } finally {
      trendingLoading.value = false
    }
    return trendingArticles.value
  }

  async function getTrendingPair() {
    const articles = await fetchTrending()
    if (!articles || articles.length < 2) return null

    const shuffled = [...articles].sort(() => Math.random() - 0.5)
    for (let i = 0; i < Math.min(shuffled.length, 10); i++) {
      for (let j = i + 1; j < Math.min(shuffled.length, 10); j++) {
        const [startSummary, endSummary] = await Promise.all([
          wiki.getArticleSummary(shuffled[i].title),
          wiki.getArticleSummary(shuffled[j].title),
        ])
        if (startSummary && endSummary && startSummary.title !== endSummary.title) {
          return {
            start: { ...startSummary, views: shuffled[i].views },
            end: { ...endSummary, views: shuffled[j].views },
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
