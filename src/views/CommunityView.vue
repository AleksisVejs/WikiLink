<template>
  <div class="community-view relative min-h-screen flex flex-col">
    <SiteTopNav
      :user="auth.user.value"
      :level="progression.level.value"
      :current-xp="progression.currentXp.value"
      :next-level-xp="progression.nextLevelXp.value"
      :xp-percent="progression.progress.value * 100"
      :show-xp-bar="true"
      :show-home-shortcut="true"
      :show-utility-controls="false"
      @login="goHomeLogin"
      @logout="auth.logout()"
    />

    <main class="flex-1 px-3 sm:px-4 py-4 sm:py-6">
      <div class="max-w-6xl mx-auto space-y-4 sm:space-y-5">
        <section class="community-hero rounded-2xl p-4 sm:p-5">
          <div class="community-hero__glow" />
          <div class="relative z-[1]">
            <div class="flex flex-wrap items-center gap-2 mb-2">
              <h1 class="font-pixel text-[10px] sm:text-[11px] text-crt-cyan tracking-[0.2em]">COMMUNITY PAIRS</h1>
              <span class="community-chip">LIVE HUB</span>
            </div>
            <p class="font-mono text-[12px] text-retro-muted max-w-2xl">Create your own pair or curated route packs, vote on the best ideas, and jump straight into runs from the community feed.</p>
            <div class="mt-4 grid grid-cols-3 gap-2 sm:gap-3 max-w-xl">
              <div class="community-stat">
                <div class="community-stat__value text-crt-cyan">{{ hubPairs.length }}</div>
                <div class="community-stat__label">Pairs</div>
              </div>
              <div class="community-stat">
                <div class="community-stat__value text-crt-amber">{{ hubGroups.length }}</div>
                <div class="community-stat__label">Groups</div>
              </div>
              <div class="community-stat">
                <div class="community-stat__value text-crt-green">{{ totalRoutes }}</div>
                <div class="community-stat__label">Routes</div>
              </div>
            </div>
          </div>
        </section>

        <section class="community-panel rounded-xl p-4 sm:p-5">
          <div class="flex items-center justify-between mb-3">
            <h2 class="font-pixel text-[8px] text-crt-green tracking-[0.15em]">CREATE</h2>
            <span class="community-chip">PAIR OR GROUP</span>
          </div>
          <p class="font-mono text-[11px] text-retro-muted mb-3">Use one builder for quick pair challenges or curated multi-route groups.</p>
          <button @click="showCreatorModal = true" class="btn-retro-primary !py-1.5 !px-3 !text-[10px] w-full sm:w-auto">OPEN CREATOR</button>
        </section>

        <section class="grid gap-4 lg:grid-cols-2">
          <div class="community-panel rounded-xl overflow-hidden">
            <div class="px-4 py-2.5 border-b border-retro-border/30 flex items-center justify-between">
              <div class="font-pixel text-[8px] text-crt-green tracking-[0.15em]">HIGHEST RATED PAIRS</div>
              <div class="font-mono text-[10px] text-retro-muted">Top {{ topRatedPairs.length }}</div>
            </div>
            <div class="px-4 py-2.5">
              <div v-if="!topRatedPairs.length" class="font-mono text-[11px] text-retro-muted">No ratings yet.</div>
              <div v-for="pair in topRatedPairs" :key="`best-pair-${pair.id}`" class="py-2 border-b border-retro-border/10 last:border-0">
                <div class="font-mono text-[11px] text-crt-white truncate">{{ pair.startTitle }} -> {{ pair.endTitle }}</div>
                <div class="flex items-center justify-between mt-1 gap-2">
                  <router-link :to="`/profile/${pair.username}`" class="font-mono text-[10px] text-retro-muted hover:text-crt-cyan truncate">by {{ pair.username }}</router-link>
                  <div class="flex items-center gap-2">
                    <div class="font-mono text-[10px] text-crt-green">Score {{ getScore(pair) }}</div>
                    <button @click="playPair(pair)" class="btn-retro-ghost !py-0.5 !px-2 !text-[9px]">PLAY</button>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="community-panel rounded-xl overflow-hidden">
            <div class="px-4 py-2.5 border-b border-retro-border/30 flex items-center justify-between">
              <div class="font-pixel text-[8px] text-crt-amber tracking-[0.15em]">HIGHEST RATED GROUPS</div>
              <div class="font-mono text-[10px] text-retro-muted">Top {{ topRatedGroups.length }}</div>
            </div>
            <div class="px-4 py-2.5">
              <div v-if="!topRatedGroups.length" class="font-mono text-[11px] text-retro-muted">No ratings yet.</div>
              <div v-for="group in topRatedGroups" :key="`best-group-${group.id}`" class="py-2 border-b border-retro-border/10 last:border-0">
                <div class="font-mono text-[11px] text-crt-white truncate">{{ group.name }}</div>
                <div class="flex items-center justify-between mt-1 gap-2">
                  <router-link :to="`/profile/${group.username}`" class="font-mono text-[10px] text-retro-muted hover:text-crt-cyan truncate">by {{ group.username }}</router-link>
                  <div class="flex items-center gap-2">
                    <div class="font-mono text-[10px] text-crt-amber">Score {{ getScore(group) }}</div>
                    <button @click="playGroup(group)" class="btn-retro-ghost !py-0.5 !px-2 !text-[9px]">PLAY</button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>

        <section class="community-panel rounded-xl overflow-hidden">
          <div class="px-4 py-2.5 border-b border-retro-border/30 flex items-center justify-between">
            <div class="font-pixel text-[8px] text-crt-cyan tracking-[0.15em]">COMMUNITY PAIRS</div>
            <div class="font-mono text-[10px] text-retro-muted">{{ hubPairs.length }} available</div>
          </div>
          <div class="max-h-[280px] overflow-y-auto">
            <div v-if="!hubPairs.length" class="community-empty">
              <div class="font-pixel text-[8px] tracking-[0.14em] text-retro-muted/80">NO COMMUNITY PAIRS YET</div>
              <p class="font-mono text-[11px] text-retro-muted mt-1">Be the first to publish one from the create panel.</p>
            </div>
            <div v-for="(pair, idx) in hubPairs" :key="`pair-${idx}`" class="px-4 py-2.5 flex items-center gap-2 border-b border-retro-border/10 community-row">
              <div class="flex-1 min-w-0">
                <div class="font-mono text-[11px] text-crt-white truncate">{{ pair.startTitle }} -> {{ pair.endTitle }}</div>
                <router-link :to="`/profile/${pair.username}`" class="font-mono text-[10px] text-retro-muted hover:text-crt-cyan transition-colors truncate inline-block">by {{ pair.username }}</router-link>
              </div>
              <div class="flex items-center gap-1">
                <button
                  @click="votePair(pair, pair.yourVote === 1 ? 0 : 1)"
                  class="font-mono text-[10px] px-1.5 py-0.5 rounded"
                  :class="pair.yourVote === 1 ? 'text-crt-green bg-crt-green/10' : 'text-retro-muted bg-retro-surface/40'"
                >
                  +{{ pair.likes || 0 }}
                </button>
                <button
                  @click="votePair(pair, pair.yourVote === -1 ? 0 : -1)"
                  class="font-mono text-[10px] px-1.5 py-0.5 rounded"
                  :class="pair.yourVote === -1 ? 'text-crt-red bg-crt-red/10' : 'text-retro-muted bg-retro-surface/40'"
                >
                  -{{ pair.dislikes || 0 }}
                </button>
              </div>
              <button @click="playPair(pair)" class="btn-retro-ghost !py-1 !px-2 !text-[10px]">PLAY</button>
              <button
                v-if="canDeletePair(pair)"
                @click="deletePair(pair)"
                class="btn-retro-ghost !py-1 !px-2 !text-[10px]"
              >
                DELETE
              </button>
            </div>
          </div>
        </section>

        <section class="community-panel rounded-xl overflow-hidden">
          <div class="px-4 py-2.5 border-b border-retro-border/30 flex items-center justify-between">
            <div class="font-pixel text-[8px] text-crt-amber tracking-[0.15em]">PAIR GROUPS</div>
            <div class="font-mono text-[10px] text-retro-muted">{{ hubGroups.length }} listed</div>
          </div>
          <div class="max-h-[340px] overflow-y-auto">
            <div v-if="!hubGroups.length" class="community-empty">
              <div class="font-pixel text-[8px] tracking-[0.14em] text-retro-muted/80">NO GROUPS YET</div>
              <p class="font-mono text-[11px] text-retro-muted mt-1">Bundle several routes and share a mini challenge set.</p>
            </div>
            <div v-for="group in hubGroups" :key="`group-${group.id}`" class="px-4 py-3 border-b border-retro-border/10 community-row">
              <div class="flex items-center gap-2">
                <div class="font-mono text-[11px] text-crt-white flex-1 min-w-0 truncate">
                  {{ group.name }}
                  <router-link :to="`/profile/${group.username}`" class="text-retro-muted hover:text-crt-cyan transition-colors"> by {{ group.username }}</router-link>
                </div>
                <div class="flex items-center gap-1">
                  <button
                    @click="voteGroup(group, group.yourVote === 1 ? 0 : 1)"
                    class="font-mono text-[10px] px-1.5 py-0.5 rounded"
                    :class="group.yourVote === 1 ? 'text-crt-green bg-crt-green/10' : 'text-retro-muted bg-retro-surface/40'"
                  >
                    +{{ group.likes || 0 }}
                  </button>
                  <button
                    @click="voteGroup(group, group.yourVote === -1 ? 0 : -1)"
                    class="font-mono text-[10px] px-1.5 py-0.5 rounded"
                    :class="group.yourVote === -1 ? 'text-crt-red bg-crt-red/10' : 'text-retro-muted bg-retro-surface/40'"
                  >
                    -{{ group.dislikes || 0 }}
                  </button>
                </div>
                <button @click="playGroup(group)" class="btn-retro-ghost !py-0.5 !px-2 !text-[9px]">PLAY GROUP</button>
                <button v-if="auth.user.value && Number(auth.user.value.id) === Number(group.userId)" @click="deleteGroup(group)" class="btn-retro-ghost !py-0.5 !px-2 !text-[9px]">DELETE</button>
              </div>
              <div class="mt-1 space-y-1">
                <div v-for="item in group.items" :key="`item-${group.id}-${item.position}`" class="flex items-center gap-2">
                  <div class="font-mono text-[10px] text-retro-muted truncate flex-1">{{ item.position }}. {{ item.startTitle }} -> {{ item.endTitle }}</div>
                </div>
              </div>
            </div>
          </div>
        </section>
      </div>

      <div v-if="showCreatorModal" class="community-modal-backdrop" @click="closeModals" />

      <section v-if="showCreatorModal" class="community-modal community-panel rounded-xl p-4 sm:p-5">
        <div class="flex items-center justify-between mb-3">
          <h3 class="font-pixel text-[8px] text-crt-cyan tracking-[0.15em]">COMMUNITY CREATOR</h3>
          <button @click="showCreatorModal = false" class="btn-retro-ghost !py-1 !px-2 !text-[10px]">CLOSE</button>
        </div>
        <div class="flex items-center gap-2 mb-3">
          <button @click="creatorMode = 'pair'" class="btn-retro-ghost !py-1 !px-2 !text-[10px]" :class="creatorMode === 'pair' ? '!text-crt-green !border-crt-green/40 !bg-crt-green/10' : ''">PAIR</button>
          <button @click="creatorMode = 'group'" class="btn-retro-ghost !py-1 !px-2 !text-[10px]" :class="creatorMode === 'group' ? '!text-crt-amber !border-crt-amber/40 !bg-crt-amber/10' : ''">GROUP</button>
        </div>
        <div v-if="creatorMode === 'pair'" class="space-y-2">
          <WikiTitleInput v-model="pairStart" label="Start" placeholder="Starting article" />
          <WikiTitleInput v-model="pairEnd" label="Target" placeholder="Target article" />
          <button @click="createPair" class="btn-retro-primary !py-1.5 !px-3 !text-[10px] w-full sm:w-auto">CREATE PAIR</button>
        </div>
        <div v-else class="space-y-2">
          <input v-model="groupName" class="community-input w-full rounded-md px-2.5 py-1.5 text-crt-white font-mono text-xs focus:outline-none focus:border-crt-cyan" placeholder="Group name" />
          <div v-for="(pair, idx) in groupPairs" :key="`modal-gpair-${idx}`" class="grid grid-cols-1 sm:grid-cols-[1fr_1fr_auto] gap-1.5 rounded-lg p-2 bg-[#10111a]/70 border border-retro-border/30">
            <WikiTitleInput v-model="pair.startTitle" placeholder="Start article" />
            <WikiTitleInput v-model="pair.endTitle" placeholder="Target article" />
            <button @click="removeGroupPair(idx)" class="btn-retro-ghost !py-1 !px-2 !text-[10px]">X</button>
          </div>
          <div class="flex gap-2">
            <button @click="addGroupPair" class="btn-retro-ghost !py-1 !px-2 !text-[10px]">ADD PAIR</button>
            <button @click="createGroup" class="btn-retro-primary !py-1 !px-2 !text-[10px]">CREATE GROUP</button>
          </div>
        </div>
      </section>
    </main>
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import SiteTopNav from '../components/layout/SiteTopNav.vue'
import WikiTitleInput from '../components/WikiTitleInput.vue'
import { useApi } from '../composables/useApi'
import { useAuth } from '../composables/useAuth'
import { useToast } from '../composables/useToast'
import { useProgression } from '../composables/useProgression'

const api = useApi()
const auth = useAuth()
const toast = useToast()
const progression = useProgression()
const router = useRouter()

const pairStart = ref('')
const pairEnd = ref('')
const groupName = ref('')
const groupPairs = ref([{ startTitle: '', endTitle: '' }, { startTitle: '', endTitle: '' }])
const hubPairs = ref([])
const hubGroups = ref([])
const showCreatorModal = ref(false)
const creatorMode = ref('pair')
const totalRoutes = computed(() => {
  const groupRoutes = hubGroups.value.reduce((acc, group) => acc + (Array.isArray(group.items) ? group.items.length : 0), 0)
  return hubPairs.value.length + groupRoutes
})
const topRatedPairs = computed(() => [...hubPairs.value].sort((a, b) => getScore(b) - getScore(a)).slice(0, 3))
const topRatedGroups = computed(() => [...hubGroups.value].sort((a, b) => getScore(b) - getScore(a)).slice(0, 3))

function goHomeLogin() {
  router.push({ name: 'home' })
}

function addGroupPair() {
  if (groupPairs.value.length >= 12) return
  groupPairs.value.push({ startTitle: '', endTitle: '' })
}

function removeGroupPair(idx) {
  if (groupPairs.value.length <= 2) return
  groupPairs.value.splice(idx, 1)
}

function closeModals() {
  showCreatorModal.value = false
}

function getScore(item) {
  const likes = Number(item?.likes || 0)
  const dislikes = Number(item?.dislikes || 0)
  return likes - dislikes
}

async function loadHub() {
  try {
    const data = await api.get('/community/hub')
    hubPairs.value = Array.isArray(data.pairs) ? data.pairs : []
    hubGroups.value = Array.isArray(data.groups) ? data.groups : []
  } catch {
    hubPairs.value = []
    hubGroups.value = []
  }
}

async function createPair() {
  if (!auth.isLoggedIn()) {
    toast.warn('Login required to create pairs.')
    return
  }
  try {
    const result = await api.post('/community/pair', { startTitle: pairStart.value, endTitle: pairEnd.value })
    if (result?.error) {
      toast.warn(result.error)
      return
    }
    toast.success('Community pair created.')
    pairStart.value = ''
    pairEnd.value = ''
    showCreatorModal.value = false
    loadHub()
  } catch (e) {
    toast.warn(e?.message || 'Failed to create pair.')
  }
}

async function createGroup() {
  if (!auth.isLoggedIn()) {
    toast.warn('Login required to create groups.')
    return
  }
  const cleanPairs = groupPairs.value
    .map(p => ({ startTitle: String(p.startTitle || '').trim(), endTitle: String(p.endTitle || '').trim() }))
    .filter(p => p.startTitle && p.endTitle)
  try {
    const result = await api.post('/community/group', { name: groupName.value, pairs: cleanPairs })
    if (result?.error) {
      toast.warn(result.error)
      return
    }
    toast.success('Community group created.')
    groupName.value = ''
    groupPairs.value = [{ startTitle: '', endTitle: '' }, { startTitle: '', endTitle: '' }]
    showCreatorModal.value = false
    loadHub()
  } catch (e) {
    toast.warn(e?.message || 'Failed to create group.')
  }
}

function playPair(pair) {
  const from = String(pair.startTitle || '').trim()
  const to = String(pair.endTitle || '').trim()
  if (!from || !to) return
  router.push({ name: 'game', params: { mode: 'custom' }, query: { from, to } })
}

function playGroup(group) {
  if (!group?.id) return
  router.push({ name: 'game', params: { mode: 'custom' }, query: { cg: String(group.id), cstep: '1' } })
}

function canDeletePair(pair) {
  const uid = auth.user.value?.id
  if (uid == null || !pair) return false
  if (pair.canDelete) return true
  if (pair.userId == null) return false
  return Number(pair.userId) === Number(uid)
}

async function deletePair(pair) {
  if (!pair?.id) return
  try {
    const result = await api.post('/community/pair/delete', { pairId: pair.id })
    if (result?.error) {
      toast.warn(result.error)
      return
    }
    toast.success('Pair deleted.')
    loadHub()
  } catch (e) {
    toast.warn(e?.message || 'Failed to delete pair.')
  }
}

async function deleteGroup(group) {
  if (!group?.id) return
  try {
    const result = await api.post('/community/group/delete', { groupId: group.id })
    if (result?.error) {
      toast.warn(result.error)
      return
    }
    toast.success('Group deleted.')
    loadHub()
  } catch (e) {
    toast.warn(e?.message || 'Failed to delete group.')
  }
}

async function votePair(pair, vote) {
  if (!pair?.id) return
  if (!auth.isLoggedIn()) {
    toast.warn('Login required to vote on pairs.')
    return
  }
  try {
    const result = await api.post('/community/pair/vote', { pairId: pair.id, vote })
    if (result?.error) {
      toast.warn(result.error)
      return
    }
    await loadHub()
  } catch (e) {
    toast.warn(e?.message || 'Failed to update vote.')
  }
}

async function voteGroup(group, vote) {
  if (!group?.id) return
  if (!auth.isLoggedIn()) {
    toast.warn('Login required to vote on groups.')
    return
  }
  try {
    const result = await api.post('/community/group/vote', { groupId: group.id, vote })
    if (result?.error) {
      toast.warn(result.error)
      return
    }
    await loadHub()
  } catch (e) {
    toast.warn(e?.message || 'Failed to update group vote.')
  }
}

onMounted(() => {
  window.addEventListener('keydown', onEscClose)
  loadHub()
})

onUnmounted(() => {
  window.removeEventListener('keydown', onEscClose)
})

function onEscClose(e) {
  if (e.key !== 'Escape') return
  closeModals()
}
</script>

<style scoped>
.community-view {
  background:
    radial-gradient(circle at 18% 2%, rgba(0, 229, 255, 0.08), transparent 34%),
    radial-gradient(circle at 90% 10%, rgba(255, 191, 0, 0.06), transparent 26%);
}

.community-panel {
  background: linear-gradient(180deg, rgba(13, 14, 21, 0.96), rgba(10, 11, 18, 0.98));
  border: 1.5px solid rgba(37, 39, 56, 0.7);
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.03);
}

.community-hero {
  position: relative;
  overflow: hidden;
  border: 1.5px solid rgba(0, 229, 255, 0.2);
  background: linear-gradient(130deg, rgba(11, 14, 24, 0.96), rgba(13, 14, 21, 0.95));
  box-shadow: 0 18px 45px rgba(0, 0, 0, 0.3);
}

.community-hero__glow {
  position: absolute;
  inset: auto -120px -120px auto;
  width: 260px;
  height: 260px;
  border-radius: 999px;
  background: radial-gradient(circle, rgba(0, 229, 255, 0.16), transparent 68%);
  pointer-events: none;
}

.community-chip {
  font-family: 'Space Mono', monospace;
  font-size: 9px;
  letter-spacing: 0.12em;
  color: #00e5ff;
  text-transform: uppercase;
  padding: 3px 8px;
  border-radius: 999px;
  border: 1px solid rgba(0, 229, 255, 0.25);
  background: rgba(0, 229, 255, 0.08);
}

.community-chip--green {
  color: #39ff14;
  border-color: rgba(57, 255, 20, 0.25);
  background: rgba(57, 255, 20, 0.08);
}

.community-chip--amber {
  color: #ffbf00;
  border-color: rgba(255, 191, 0, 0.25);
  background: rgba(255, 191, 0, 0.08);
}

.community-stat {
  border-radius: 12px;
  padding: 10px;
  background: rgba(13, 14, 21, 0.8);
  border: 1px solid rgba(37, 39, 56, 0.8);
}

.community-stat__value {
  font-family: 'VT323', monospace;
  font-size: 24px;
  line-height: 1;
}

.community-stat__label {
  font-family: 'Space Mono', monospace;
  font-size: 10px;
  text-transform: uppercase;
  letter-spacing: 0.12em;
  color: #6f7396;
  margin-top: 4px;
}

.community-input {
  background: #12131c;
  border: 1px solid #252738;
}

.community-row {
  transition: background 0.18s ease;
}

.community-row:hover {
  background: rgba(255, 255, 255, 0.02);
}

.community-empty {
  padding: 1rem;
  text-align: center;
  border-bottom: 1px solid rgba(37, 39, 56, 0.22);
  background: linear-gradient(180deg, rgba(12, 13, 20, 0.72), rgba(10, 11, 18, 0.52));
}

.community-modal-backdrop {
  position: fixed;
  inset: 0;
  z-index: 45;
  background: rgba(4, 5, 10, 0.72);
  backdrop-filter: blur(2px);
}

.community-modal {
  position: fixed;
  z-index: 50;
  left: 50%;
  top: 50%;
  width: min(760px, calc(100vw - 1.5rem));
  max-height: calc(100vh - 2rem);
  overflow-y: auto;
  transform: translate(-50%, -50%);
}
</style>
