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
                <div class="community-stat__value text-crt-cyan">{{ hubStats.pairCount }}</div>
                <div class="community-stat__label">Pairs</div>
              </div>
              <div class="community-stat">
                <div class="community-stat__value text-crt-amber">{{ hubStats.groupCount }}</div>
                <div class="community-stat__label">Groups</div>
              </div>
              <div class="community-stat">
                <div class="community-stat__value text-crt-green">{{ hubStats.routeCount }}</div>
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
          <button type="button" @click="openCreatorModal" class="btn-retro-primary !py-1.5 !px-3 !text-[10px] w-full sm:w-auto">OPEN CREATOR</button>
        </section>

        <section class="grid gap-4 lg:grid-cols-2">
          <div class="community-panel rounded-xl overflow-hidden">
            <div class="px-4 py-2.5 border-b border-retro-border/30 flex items-center justify-between gap-2">
              <div class="font-pixel text-[8px] text-crt-green tracking-[0.15em]">HIGHEST RATED PAIRS</div>
              <div class="font-mono text-[9px] text-retro-muted/80 hidden sm:block">by score</div>
            </div>
            <div class="max-h-[260px] overflow-y-auto">
              <div v-if="!topPairs.length" class="community-empty !border-b-0">
                <p class="font-mono text-[11px] text-retro-muted">No community pairs yet.</p>
              </div>
              <div v-for="(pair, idx) in topPairs" :key="`toppair-${pair.id}-${idx}`" class="px-4 py-2.5 flex flex-wrap items-start gap-2 border-b border-retro-border/10 community-row">
                <div class="flex-1 min-w-0">
                  <div class="community-pair-route">
                    <span class="community-pair-route__start">{{ formatWikiTitle(pair.startTitle) }}</span>
                    <span class="community-pair-route__arrow" aria-hidden="true">
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
                    </span>
                    <span class="community-pair-route__end">{{ formatWikiTitle(pair.endTitle) }}</span>
                  </div>
                  <div class="font-mono text-[9px] text-retro-muted/90 mt-0.5">
                    <span class="text-crt-green">{{ pair.score }}</span> pts
                    <span class="mx-1">·</span>
                    +{{ pair.likes || 0 }} / -{{ pair.dislikes || 0 }}
                  </div>
                  <router-link :to="`/profile/${pair.username}`" class="font-mono text-[10px] text-retro-muted hover:text-crt-cyan transition-colors truncate inline-block">by {{ pair.username }}</router-link>
                </div>
                <div class="flex flex-wrap items-center gap-1 shrink-0 ml-auto">
                  <button
                    type="button"
                    title="Run leaderboard"
                    class="btn-retro-ghost !py-1 !px-2 !text-[9px] sm:!text-[10px] border border-crt-green/25"
                    @click="openPairLeaderboardModal(pair)"
                  >
                    Leaderboard
                  </button>
                  <button type="button" @click="playPair(pair)" class="btn-retro-ghost !py-1 !px-2 !text-[9px] sm:!text-[10px]">PLAY</button>
                </div>
              </div>
            </div>
          </div>
          <div class="community-panel rounded-xl overflow-hidden">
            <div class="px-4 py-2.5 border-b border-retro-border/30 flex items-center justify-between gap-2">
              <div class="font-pixel text-[8px] text-crt-amber tracking-[0.15em]">HIGHEST RATED GROUPS</div>
              <div class="font-mono text-[9px] text-retro-muted/80 hidden sm:block">by score</div>
            </div>
            <div class="max-h-[260px] overflow-y-auto">
              <div v-if="!topGroups.length" class="community-empty !border-b-0">
                <p class="font-mono text-[11px] text-retro-muted">No groups yet.</p>
              </div>
              <div v-for="group in topGroups" :key="`topgroup-${group.id}`" class="px-4 py-2.5 border-b border-retro-border/10 community-row">
                <div class="flex flex-wrap items-center gap-2">
                  <div class="font-mono text-[11px] text-crt-white flex-1 min-w-0 truncate">
                    {{ group.name }}
                    <router-link :to="`/profile/${group.username}`" class="text-retro-muted hover:text-crt-cyan transition-colors"> by {{ group.username }}</router-link>
                  </div>
                  <div class="font-mono text-[9px] text-retro-muted/90 order-last sm:order-none w-full sm:w-auto">
                    <span class="text-crt-amber">{{ group.score }}</span> pts
                    <span class="mx-1">·</span>
                    +{{ group.likes || 0 }} / -{{ group.dislikes || 0 }}
                  </div>
                  <div class="flex flex-wrap items-center gap-1 shrink-0 ml-auto">
                    <button
                      type="button"
                      title="Group run leaderboard"
                      class="btn-retro-ghost !py-0.5 !px-2 !text-[9px] border border-crt-amber/25"
                      @click="openGroupLeaderboardModal(group)"
                    >
                      Leaderboard
                    </button>
                    <button type="button" @click="playGroup(group)" class="btn-retro-ghost !py-0.5 !px-2 !text-[9px]">PLAY GROUP</button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>

        <section class="community-panel rounded-xl overflow-hidden">
          <div class="px-4 py-2.5 border-b border-retro-border/30 flex flex-wrap items-center justify-between gap-2">
            <div class="font-pixel text-[8px] text-crt-cyan tracking-[0.15em]">COMMUNITY PAIRS</div>
            <div class="font-mono text-[10px] text-retro-muted">
              {{ pairTotal }} total
              <span v-if="pairTotal > 0" class="text-retro-muted/70"> · p. {{ pairPage }}/{{ pairTotalPages }}</span>
            </div>
          </div>
          <div>
            <div v-if="!hubPairs.length" class="community-empty">
              <div class="font-pixel text-[8px] tracking-[0.14em] text-retro-muted/80">NO COMMUNITY PAIRS YET</div>
              <p class="font-mono text-[11px] text-retro-muted mt-1">Be the first to publish one from the create panel.</p>
            </div>
            <div v-for="(pair, idx) in hubPairs" :key="`pair-${idx}`" class="px-4 py-2.5 flex flex-wrap items-start gap-2 border-b border-retro-border/10 community-row">
              <div class="flex-1 min-w-0">
                <div class="community-pair-route">
                  <span class="community-pair-route__start">{{ formatWikiTitle(pair.startTitle) }}</span>
                  <span class="community-pair-route__arrow" aria-hidden="true">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
                  </span>
                  <span class="community-pair-route__end">{{ formatWikiTitle(pair.endTitle) }}</span>
                </div>
                <router-link :to="`/profile/${pair.username}`" class="font-mono text-[10px] text-retro-muted hover:text-crt-cyan transition-colors truncate inline-block">by {{ pair.username }}</router-link>
              </div>
              <div class="flex items-center gap-1 shrink-0">
                <button
                  type="button"
                  @click="votePair(pair, pair.yourVote === 1 ? 0 : 1)"
                  class="font-mono text-[10px] px-1.5 py-0.5 rounded"
                  :class="pair.yourVote === 1 ? 'text-crt-green bg-crt-green/10' : 'text-retro-muted bg-retro-surface/40'"
                >
                  +{{ pair.likes || 0 }}
                </button>
                <button
                  type="button"
                  @click="votePair(pair, pair.yourVote === -1 ? 0 : -1)"
                  class="font-mono text-[10px] px-1.5 py-0.5 rounded"
                  :class="pair.yourVote === -1 ? 'text-crt-red bg-crt-red/10' : 'text-retro-muted bg-retro-surface/40'"
                >
                  -{{ pair.dislikes || 0 }}
                </button>
              </div>
              <div class="flex flex-wrap items-center gap-1 shrink-0 ml-auto">
                <button
                  type="button"
                  title="Run leaderboard"
                  class="btn-retro-ghost !py-1 !px-2 !text-[9px] sm:!text-[10px] border border-crt-green/25"
                  @click="openPairLeaderboardModal(pair)"
                >
                  Leaderboard
                </button>
                <button type="button" @click="playPair(pair)" class="btn-retro-ghost !py-1 !px-2 !text-[9px] sm:!text-[10px]">PLAY</button>
                <button
                  v-if="canDeletePair(pair)"
                  type="button"
                  @click="deletePair(pair)"
                  class="btn-retro-ghost !py-1 !px-2 !text-[9px] sm:!text-[10px]"
                >
                  DELETE
                </button>
              </div>
            </div>
          </div>
          <div v-if="pairTotalPages > 1" class="flex flex-wrap items-center justify-between gap-2 px-4 py-2.5 border-t border-retro-border/20 bg-[#0c0d12]/50">
            <button
              type="button"
              class="btn-retro-ghost !py-1 !px-2 !text-[10px] disabled:opacity-40 disabled:pointer-events-none"
              :disabled="pairPage <= 1"
              @click="goPairPage(-1)"
            >
              Prev
            </button>
            <span class="font-mono text-[10px] text-retro-muted">{{ pairRangeLabel }}</span>
            <button
              type="button"
              class="btn-retro-ghost !py-1 !px-2 !text-[10px] disabled:opacity-40 disabled:pointer-events-none"
              :disabled="pairPage >= pairTotalPages"
              @click="goPairPage(1)"
            >
              Next
            </button>
          </div>
        </section>

        <section class="community-panel rounded-xl overflow-hidden">
          <div class="px-4 py-2.5 border-b border-retro-border/30 flex flex-wrap items-center justify-between gap-2">
            <div class="font-pixel text-[8px] text-crt-amber tracking-[0.15em]">PAIR GROUPS</div>
            <div class="font-mono text-[10px] text-retro-muted">
              {{ groupTotal }} total
              <span v-if="groupTotal > 0" class="text-retro-muted/70"> · p. {{ groupPage }}/{{ groupTotalPages }}</span>
            </div>
          </div>
          <div>
            <div v-if="!hubGroups.length" class="community-empty">
              <div class="font-pixel text-[8px] tracking-[0.14em] text-retro-muted/80">NO GROUPS YET</div>
              <p class="font-mono text-[11px] text-retro-muted mt-1">Bundle several routes and share a mini challenge set.</p>
            </div>
            <div v-for="group in hubGroups" :key="`group-${group.id}`" class="px-4 py-3 border-b border-retro-border/10 community-row">
              <div class="flex flex-wrap items-center gap-2">
                <div class="font-mono text-[11px] text-crt-white flex-1 min-w-0 truncate">
                  {{ group.name }}
                  <router-link :to="`/profile/${group.username}`" class="text-retro-muted hover:text-crt-cyan transition-colors"> by {{ group.username }}</router-link>
                </div>
                <div class="flex items-center gap-1 shrink-0">
                  <button
                    type="button"
                    @click="voteGroup(group, group.yourVote === 1 ? 0 : 1)"
                    class="font-mono text-[10px] px-1.5 py-0.5 rounded"
                    :class="group.yourVote === 1 ? 'text-crt-green bg-crt-green/10' : 'text-retro-muted bg-retro-surface/40'"
                  >
                    +{{ group.likes || 0 }}
                  </button>
                  <button
                    type="button"
                    @click="voteGroup(group, group.yourVote === -1 ? 0 : -1)"
                    class="font-mono text-[10px] px-1.5 py-0.5 rounded"
                    :class="group.yourVote === -1 ? 'text-crt-red bg-crt-red/10' : 'text-retro-muted bg-retro-surface/40'"
                  >
                    -{{ group.dislikes || 0 }}
                  </button>
                </div>
                <div class="flex flex-wrap items-center gap-1 shrink-0 ml-auto">
                  <button
                    type="button"
                    title="Group run leaderboard"
                    class="btn-retro-ghost !py-0.5 !px-2 !text-[9px] sm:!text-[10px] border border-crt-amber/25"
                    @click="openGroupLeaderboardModal(group)"
                  >
                    Leaderboard
                  </button>
                  <button type="button" @click="playGroup(group)" class="btn-retro-ghost !py-0.5 !px-2 !text-[9px] sm:!text-[10px]">PLAY GROUP</button>
                  <button v-if="auth.user.value && Number(auth.user.value.id) === Number(group.userId)" type="button" @click="deleteGroup(group)" class="btn-retro-ghost !py-0.5 !px-2 !text-[9px] sm:!text-[10px]">DELETE</button>
                </div>
              </div>
              <div class="mt-1 space-y-2">
                <div v-for="item in group.items" :key="`item-${group.id}-${item.position}`" class="community-group-pair">
                  <span class="community-group-pair__idx">{{ item.position }}</span>
                  <div class="community-pair-route community-pair-route--sm min-w-0 flex-1">
                    <span class="community-pair-route__start">{{ formatWikiTitle(item.startTitle) }}</span>
                    <span class="community-pair-route__arrow" aria-hidden="true">
                      <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
                    </span>
                    <span class="community-pair-route__end">{{ formatWikiTitle(item.endTitle) }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div v-if="groupTotalPages > 1" class="flex flex-wrap items-center justify-between gap-2 px-4 py-2.5 border-t border-retro-border/20 bg-[#0c0d12]/50">
            <button
              type="button"
              class="btn-retro-ghost !py-1 !px-2 !text-[10px] disabled:opacity-40 disabled:pointer-events-none"
              :disabled="groupPage <= 1"
              @click="goGroupPage(-1)"
            >
              Prev
            </button>
            <span class="font-mono text-[10px] text-retro-muted">{{ groupRangeLabel }}</span>
            <button
              type="button"
              class="btn-retro-ghost !py-1 !px-2 !text-[10px] disabled:opacity-40 disabled:pointer-events-none"
              :disabled="groupPage >= groupTotalPages"
              @click="goGroupPage(1)"
            >
              Next
            </button>
          </div>
        </section>
      </div>

      <div v-if="showCreatorModal || showLeaderboardModal" class="community-modal-backdrop" @click="closeModals" />

      <section v-if="showLeaderboardModal" class="community-modal community-panel rounded-xl p-4 sm:p-5">
        <div class="flex items-start justify-between gap-3 mb-3">
          <div class="min-w-0">
            <h3 class="font-pixel text-[8px] tracking-[0.15em]" :class="leaderboardModalKind === 'pair' ? 'text-crt-green' : 'text-crt-amber'">
              {{ leaderboardModalKind === 'pair' ? 'PAIR RUN LEADERBOARD' : 'GROUP RUN LEADERBOARD' }}
            </h3>
            <p class="font-mono text-[9px] text-retro-muted/85 mt-1">
              {{ leaderboardModalKind === 'pair' ? 'Best run per player: fewest clicks, then fastest time.' : 'Full group: total clicks across all legs, then total time.' }}
            </p>
            <div v-if="leaderboardModalKind === 'pair' && leaderboardModalPair" class="community-pair-route mt-2">
              <span class="community-pair-route__start">{{ formatWikiTitle(leaderboardModalPair.startTitle) }}</span>
              <span class="community-pair-route__arrow" aria-hidden="true">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
              </span>
              <span class="community-pair-route__end">{{ formatWikiTitle(leaderboardModalPair.endTitle) }}</span>
            </div>
            <p v-else-if="leaderboardModalKind === 'group' && leaderboardModalGroup" class="font-mono text-[11px] text-crt-white mt-2 truncate">{{ leaderboardModalGroup.name }}</p>
          </div>
          <button type="button" @click="closeModals" class="btn-retro-ghost !py-1 !px-2 !text-[10px] shrink-0">CLOSE</button>
        </div>
        <div v-if="leaderboardModalLoading" class="font-mono text-[11px] text-retro-muted py-6 text-center">Loading…</div>
        <div v-else class="max-h-[min(360px,55vh)] overflow-y-auto overflow-x-auto -mx-1 px-1">
          <table v-if="leaderboardModalKind === 'pair' && pairRunRows.length" class="community-lb-table w-full min-w-[280px]">
            <thead>
              <tr>
                <th class="w-8">#</th>
                <th>Player</th>
                <th class="text-right">Clicks</th>
                <th class="text-right w-[4.5rem]">Time</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in pairRunRows" :key="`pr-${row.userId}-${row.rank}`">
                <td class="font-terminal text-retro-muted tabular-nums">{{ row.rank }}</td>
                <td>
                  <router-link :to="`/profile/${row.username}`" class="font-mono text-[11px] text-crt-cyan hover:underline">{{ row.username }}</router-link>
                </td>
                <td class="text-right font-mono text-[11px] text-crt-green tabular-nums">{{ row.bestClicks }}</td>
                <td class="text-right font-mono text-[11px] text-retro-muted tabular-nums">{{ formatRunTime(row.bestTimeSeconds) }}</td>
              </tr>
            </tbody>
          </table>
          <table v-else-if="leaderboardModalKind === 'group' && groupRunRows.length" class="community-lb-table w-full min-w-[280px]">
            <thead>
              <tr>
                <th class="w-8">#</th>
                <th>Player</th>
                <th class="text-right">Clicks</th>
                <th class="text-right w-[4.5rem]">Time</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in groupRunRows" :key="`gr-${row.userId}-${row.rank}`">
                <td class="font-terminal text-retro-muted tabular-nums">{{ row.rank }}</td>
                <td>
                  <router-link :to="`/profile/${row.username}`" class="font-mono text-[11px] text-crt-cyan hover:underline">{{ row.username }}</router-link>
                </td>
                <td class="text-right font-mono text-[11px] text-crt-amber tabular-nums">{{ row.bestTotalClicks }}</td>
                <td class="text-right font-mono text-[11px] text-retro-muted tabular-nums">{{ formatRunTime(row.bestTotalTimeSeconds) }}</td>
              </tr>
            </tbody>
          </table>
          <p v-else class="font-mono text-[11px] text-retro-muted py-4 text-center">
            {{ leaderboardModalKind === 'pair' ? 'No finished runs logged yet. Win a custom game while logged in to record a best.' : 'No full group runs yet. Finish every leg in a group while logged in.' }}
          </p>
        </div>
      </section>

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
import { formatWikiTitle } from '../utils/wikiTitle'

const api = useApi()
const auth = useAuth()
const toast = useToast()
const progression = useProgression()
const router = useRouter()

const PAIR_PAGE_SIZE = 15
const GROUP_PAGE_SIZE = 10

const pairStart = ref('')
const pairEnd = ref('')
const groupName = ref('')
const groupPairs = ref([{ startTitle: '', endTitle: '' }, { startTitle: '', endTitle: '' }])
const hubPairs = ref([])
const hubGroups = ref([])
const topPairs = ref([])
const topGroups = ref([])
const hubStats = ref({ pairCount: 0, groupCount: 0, routeCount: 0 })
const pairTotal = ref(0)
const groupTotal = ref(0)
const pairPage = ref(1)
const groupPage = ref(1)
const pairRunRows = ref([])
const groupRunRows = ref([])
const showLeaderboardModal = ref(false)
const leaderboardModalKind = ref('pair')
const leaderboardModalPair = ref(null)
const leaderboardModalGroup = ref(null)
const leaderboardModalLoading = ref(false)
const showCreatorModal = ref(false)
const creatorMode = ref('pair')

const pairTotalPages = computed(() => Math.max(1, Math.ceil(pairTotal.value / PAIR_PAGE_SIZE)))
const groupTotalPages = computed(() => Math.max(1, Math.ceil(groupTotal.value / GROUP_PAGE_SIZE)))

const pairRangeLabel = computed(() => {
  if (pairTotal.value <= 0) return '0 pairs'
  const start = (pairPage.value - 1) * PAIR_PAGE_SIZE + 1
  const end = Math.min(pairPage.value * PAIR_PAGE_SIZE, pairTotal.value)
  return `${start}–${end} of ${pairTotal.value}`
})

const groupRangeLabel = computed(() => {
  if (groupTotal.value <= 0) return '0 groups'
  const start = (groupPage.value - 1) * GROUP_PAGE_SIZE + 1
  const end = Math.min(groupPage.value * GROUP_PAGE_SIZE, groupTotal.value)
  return `${start}–${end} of ${groupTotal.value}`
})
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

function openCreatorModal() {
  showLeaderboardModal.value = false
  leaderboardModalPair.value = null
  leaderboardModalGroup.value = null
  showCreatorModal.value = true
}

function closeModals() {
  showCreatorModal.value = false
  showLeaderboardModal.value = false
  leaderboardModalPair.value = null
  leaderboardModalGroup.value = null
}

function formatRunTime(sec) {
  const s = Math.max(0, Number(sec) || 0)
  const m = Math.floor(s / 60)
  const r = s % 60
  return `${m}:${r.toString().padStart(2, '0')}`
}

async function loadPairRunLbForPairId(pairId) {
  if (!pairId) {
    pairRunRows.value = []
    return
  }
  try {
    const data = await api.get(`/community/leaderboard/pair?pairId=${encodeURIComponent(pairId)}&limit=20`)
    pairRunRows.value = Array.isArray(data.rows) ? data.rows : []
  } catch {
    pairRunRows.value = []
  }
}

async function loadGroupRunLbForGroupId(groupId) {
  if (!groupId) {
    groupRunRows.value = []
    return
  }
  try {
    const data = await api.get(`/community/leaderboard/group?groupId=${encodeURIComponent(groupId)}&limit=20`)
    groupRunRows.value = Array.isArray(data.rows) ? data.rows : []
  } catch {
    groupRunRows.value = []
  }
}

async function openPairLeaderboardModal(pair) {
  if (!pair?.id) return
  showCreatorModal.value = false
  leaderboardModalKind.value = 'pair'
  leaderboardModalPair.value = pair
  leaderboardModalGroup.value = null
  groupRunRows.value = []
  showLeaderboardModal.value = true
  leaderboardModalLoading.value = true
  pairRunRows.value = []
  try {
    await loadPairRunLbForPairId(pair.id)
  } finally {
    leaderboardModalLoading.value = false
  }
}

async function openGroupLeaderboardModal(group) {
  if (!group?.id) return
  showCreatorModal.value = false
  leaderboardModalKind.value = 'group'
  leaderboardModalGroup.value = group
  leaderboardModalPair.value = null
  pairRunRows.value = []
  showLeaderboardModal.value = true
  leaderboardModalLoading.value = true
  groupRunRows.value = []
  try {
    await loadGroupRunLbForGroupId(group.id)
  } finally {
    leaderboardModalLoading.value = false
  }
}

async function loadHub(options = { retry: true }) {
  try {
    const po = (pairPage.value - 1) * PAIR_PAGE_SIZE
    const go = (groupPage.value - 1) * GROUP_PAGE_SIZE
    const q = new URLSearchParams({
      pairLimit: String(PAIR_PAGE_SIZE),
      pairOffset: String(po),
      groupLimit: String(GROUP_PAGE_SIZE),
      groupOffset: String(go),
      topRatedPairLimit: '6',
      topRatedGroupLimit: '6',
    })
    const data = await api.get(`/community/hub?${q.toString()}`)
    hubPairs.value = Array.isArray(data.pairs) ? data.pairs : []
    hubGroups.value = Array.isArray(data.groups) ? data.groups : []
    topPairs.value = Array.isArray(data.topPairs) ? data.topPairs : []
    topGroups.value = Array.isArray(data.topGroups) ? data.topGroups : []
    pairTotal.value = typeof data.pairTotal === 'number' ? data.pairTotal : 0
    groupTotal.value = typeof data.groupTotal === 'number' ? data.groupTotal : 0
    if (data.stats && typeof data.stats === 'object') {
      hubStats.value = {
        pairCount: Number(data.stats.pairCount) || pairTotal.value,
        groupCount: Number(data.stats.groupCount) || groupTotal.value,
        routeCount: Number(data.stats.routeCount) || pairTotal.value,
      }
    } else {
      hubStats.value = {
        pairCount: pairTotal.value,
        groupCount: groupTotal.value,
        routeCount: pairTotal.value,
      }
    }

    const maxPairPage = Math.max(1, Math.ceil(pairTotal.value / PAIR_PAGE_SIZE))
    const maxGroupPage = Math.max(1, Math.ceil(groupTotal.value / GROUP_PAGE_SIZE))
    if (options.retry && pairPage.value > maxPairPage) {
      pairPage.value = maxPairPage
      return loadHub({ retry: false })
    }
    if (options.retry && groupPage.value > maxGroupPage) {
      groupPage.value = maxGroupPage
      return loadHub({ retry: false })
    }
  } catch {
    hubPairs.value = []
    hubGroups.value = []
    topPairs.value = []
    topGroups.value = []
    pairTotal.value = 0
    groupTotal.value = 0
    hubStats.value = { pairCount: 0, groupCount: 0, routeCount: 0 }
  }
}

function goPairPage(delta) {
  const next = pairPage.value + delta
  if (next < 1 || next > pairTotalPages.value) return
  pairPage.value = next
  loadHub()
}

function goGroupPage(delta) {
  const next = groupPage.value + delta
  if (next < 1 || next > groupTotalPages.value) return
  groupPage.value = next
  loadHub()
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
    pairPage.value = 1
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
    groupPage.value = 1
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

onMounted(async () => {
  window.addEventListener('keydown', onEscClose)
  await loadHub()
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

/* Pair route: start → target (horizontal, wrap on narrow width) */
.community-pair-route {
  display: flex;
  flex-direction: row;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.35rem 0.5rem;
  min-width: 0;
}
.community-pair-route__start,
.community-pair-route__end {
  font-family: 'Space Mono', monospace;
  font-size: 11px;
  line-height: 1.35;
  word-break: break-word;
  min-width: 0;
}
.community-pair-route__start {
  color: rgba(255, 255, 255, 0.95);
}
.community-pair-route__end {
  color: rgba(0, 229, 255, 0.88);
}
.community-pair-route__arrow {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: rgba(111, 115, 150, 0.65);
  flex-shrink: 0;
}
.community-pair-route--sm .community-pair-route__start,
.community-pair-route--sm .community-pair-route__end {
  font-size: 10px;
  line-height: 1.35;
}
.community-pair-route--sm .community-pair-route__start {
  color: rgba(255, 255, 255, 0.88);
}
.community-pair-route--sm .community-pair-route__end {
  color: rgba(0, 229, 255, 0.75);
}
.community-group-pair {
  display: flex;
  align-items: flex-start;
  gap: 0.5rem;
  padding: 0.35rem 0 0.35rem 0.5rem;
  border-left: 2px solid rgba(255, 191, 0, 0.12);
  border-radius: 0 4px 4px 0;
}
.community-group-pair__idx {
  font-family: 'VT323', monospace;
  font-size: 14px;
  line-height: 1.2;
  color: rgba(255, 191, 0, 0.55);
  min-width: 1.25rem;
  text-align: right;
}

.community-lb-table {
  border-collapse: collapse;
  font-size: 11px;
}
.community-lb-table th {
  font-family: 'Press Start 2P', monospace;
  font-size: 6px;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: #6f7396;
  padding: 0.35rem 0.5rem;
  border-bottom: 1px solid rgba(37, 39, 56, 0.6);
}
.community-lb-table td {
  padding: 0.45rem 0.5rem;
  border-bottom: 1px solid rgba(37, 39, 56, 0.35);
  vertical-align: middle;
}
.community-lb-table tbody tr:last-child td {
  border-bottom: none;
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
