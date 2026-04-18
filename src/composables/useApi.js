const API_BASE = '/api'
const DEFAULT_TIMEOUT_MS = 25_000

function getToken() {
  return localStorage.getItem('wikilink_token') || null
}

function isAbortError(e) {
  return e && (e.name === 'AbortError' || e.code === 20)
}

/**
 * @param {string} path
 * @param {RequestInit & { timeoutMs?: number }} opts
 */
async function request(path, opts = {}) {
  const { timeoutMs = DEFAULT_TIMEOUT_MS, ...fetchOpts } = opts
  const controller = new AbortController()
  const timeoutId = setTimeout(() => controller.abort(), timeoutMs)

  const token = getToken()
  const headers = { 'Content-Type': 'application/json', ...(fetchOpts.headers || {}) }
  if (token) headers['Authorization'] = `Bearer ${token}`

  let res
  try {
    res = await fetch(`${API_BASE}${path}`, {
      ...fetchOpts,
      headers,
      signal: controller.signal,
    })
  } catch (e) {
    clearTimeout(timeoutId)
    if (isAbortError(e)) {
      const err = new Error('Request timed out. Check your connection.')
      err.code = 'timeout'
      throw err
    }
    const err = new Error('Could not reach the server. Check your connection.')
    err.code = 'network'
    throw err
  }
  clearTimeout(timeoutId)

  const text = await res.text()
  let data
  try {
    data = text ? JSON.parse(text) : {}
  } catch {
    if (!res.ok) {
      const err = new Error(
        res.status ? `Server error (${res.status}). Try again in a moment.` : 'Request failed.'
      )
      err.status = res.status
      err.code = 'parse'
      throw err
    }
    return { _raw: text }
  }

  if (!res.ok) {
    const err = new Error(data.error || `Request failed (${res.status})`)
    err.status = res.status
    err.code = 'http'
    throw err
  }
  return data
}

export function useApi() {
  return {
    get: (path, opts = {}) => request(path, { method: 'GET', ...opts }),
    post: (path, body, opts = {}) =>
      request(path, { method: 'POST', body: JSON.stringify(body), ...opts }),
  }
}
