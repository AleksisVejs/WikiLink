import { ref } from 'vue'
import { useApi } from './useApi.js'

const api = useApi()
const friends = ref([])
const incomingRequests = ref([])
const sentRequests = ref([])
const loading = ref(false)

export function useFriends() {
  async function fetchFriends() {
    try {
      const data = await api.get('/friends')
      friends.value = data.friends || []
    } catch { /* ignore */ }
  }

  async function fetchRequests() {
    try {
      const data = await api.get('/friends/requests')
      incomingRequests.value = data.incoming || []
      sentRequests.value = data.sent || []
    } catch { /* ignore */ }
  }

  async function sendRequest(username) {
    loading.value = true
    try {
      const result = await api.post('/friends/request', { username })
      await fetchFriends()
      await fetchRequests()
      return result
    } catch (e) {
      return { error: e.message }
    } finally {
      loading.value = false
    }
  }

  async function acceptRequest(requestId) {
    try {
      await api.post('/friends/accept', { requestId })
      await fetchFriends()
      await fetchRequests()
      return { ok: true }
    } catch (e) {
      return { error: e.message }
    }
  }

  async function declineRequest(requestId) {
    try {
      await api.post('/friends/decline', { requestId })
      await fetchRequests()
      return { ok: true }
    } catch (e) {
      return { error: e.message }
    }
  }

  async function removeFriend(friendshipId) {
    try {
      await api.post('/friends/remove', { friendshipId })
      await fetchFriends()
      return { ok: true }
    } catch (e) {
      return { error: e.message }
    }
  }

  async function searchUsers(query) {
    if (!query || query.length < 2) return []
    try {
      const data = await api.get(`/user/search?q=${encodeURIComponent(query)}`)
      return data.users || []
    } catch {
      return []
    }
  }

  async function getPublicProfile(username) {
    try {
      return await api.get(`/user/${encodeURIComponent(username)}`)
    } catch (e) {
      return { error: e.message }
    }
  }

  return {
    friends,
    incomingRequests,
    sentRequests,
    loading,
    fetchFriends,
    fetchRequests,
    sendRequest,
    acceptRequest,
    declineRequest,
    removeFriend,
    searchUsers,
    getPublicProfile,
  }
}
