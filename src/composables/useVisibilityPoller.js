/**
 * Polls `fn` on `intervalVisibleMs` while the tab is visible, and on `intervalHiddenMs` when hidden.
 * When the tab becomes visible again, runs `fn` once immediately, then reschedules.
 */
export function createVisibilityAwarePoller(fn, intervalVisibleMs, intervalHiddenMs) {
  let timerId = null

  function clearTimer() {
    if (timerId != null) {
      clearInterval(timerId)
      timerId = null
    }
  }

  function arm() {
    clearTimer()
    const ms = document.hidden ? intervalHiddenMs : intervalVisibleMs
    timerId = setInterval(fn, ms)
  }

  function onVisibilityChange() {
    if (!document.hidden) {
      fn()
    }
    arm()
  }

  return {
    start() {
      document.addEventListener('visibilitychange', onVisibilityChange)
      fn()
      arm()
    },
    stop() {
      document.removeEventListener('visibilitychange', onVisibilityChange)
      clearTimer()
    },
  }
}
