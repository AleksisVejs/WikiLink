<template>
  <Teleport to="body">
    <transition name="fade">
      <div v-if="open" class="fixed inset-0 z-[60] flex items-center justify-center p-3 sm:p-4">
        <div class="absolute inset-0 bg-black/85 backdrop-blur-sm" @click="$emit('close')"></div>
        <div class="relative rounded-2xl p-5 sm:p-6 max-w-[400px] w-full animate-scale-in" style="background: #0d0e15; border: 1.5px solid rgba(180,76,255,0.25); box-shadow: 0 0 60px rgba(0,0,0,0.8), 0 0 30px rgba(180,76,255,0.06);">
          <div class="flex items-center justify-between mb-5">
            <div class="flex items-center gap-2.5">
              <div class="w-1 h-4 rounded-full bg-arcade-purple"></div>
              <h2 class="font-pixel text-[9px] text-arcade-purple tracking-[0.2em]">PVP</h2>
            </div>
            <button @click="$emit('close')" class="btn-ghost p-1.5">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <template v-if="matchTab === 'group' && groupView === 'waiting'">
            <div class="text-center py-3">
              <div class="font-pixel text-[8px] mb-2 tracking-wider text-crt-cyan">GROUP LOBBY</div>
              <div class="font-terminal text-3xl text-arcade-purple mb-2 tracking-[0.25em]">{{ groupLobbyCode }}</div>
              <p class="font-mono text-[10px] text-retro-muted mb-3">Share the code. Host starts when everyone is ready (min. 2 players).</p>
              <div class="rounded-lg px-3 py-2 mb-3 text-left" style="background: #12131c; border: 1px solid #252738;">
                <div class="font-pixel text-[6px] text-retro-muted mb-1.5 tracking-wider">PLAYERS ({{ groupLobbyData?.players?.length || 0 }} / {{ groupLobbyData?.max_players || '—' }})</div>
                <ul class="font-mono text-[11px] text-crt-white space-y-1 max-h-[140px] overflow-y-auto">
                  <li v-for="p in groupLobbyData?.players || []" :key="p.user_id" class="flex items-center justify-between gap-2">
                    <span class="truncate">{{ p.username }}<span v-if="p.user_id === currentUserId" class="text-crt-green/60 text-[9px] ml-1">(you)</span></span>
                    <span v-if="groupLobbyData?.host_id === p.user_id" class="font-pixel text-[6px] text-crt-amber shrink-0">HOST</span>
                  </li>
                </ul>
              </div>
              <div v-if="groupLobbyData?.status === 'waiting'" class="flex flex-col gap-2">
                <div v-if="groupLobbyData?.is_host" class="flex gap-2">
                  <button type="button" @click="$emit('copy-group-code')" class="btn-retro-ghost flex-1 !py-2 text-[10px]">COPY CODE</button>
                  <button type="button" @click="$emit('start-group')" :disabled="groupStartLoading || (groupLobbyData?.players?.length || 0) < 2" class="flex-1 !py-2 !text-[9px]" :class="(groupLobbyData?.players?.length || 0) >= 2 ? 'btn-retro-primary' : 'btn-retro-ghost opacity-40 cursor-not-allowed'">
                    {{ groupStartLoading ? '...' : 'START' }}
                  </button>
                </div>
                <div v-else class="font-mono text-[10px] text-crt-amber/80 animate-blink-slow">Waiting for host to start...</div>
              </div>
              <div v-if="groupError" class="font-mono text-[11px] text-crt-red mt-2">{{ groupError }}</div>
            </div>
          </template>

          <template v-else-if="matchTab === 'waiting'">
            <div class="text-center py-4">
              <div class="font-pixel text-[8px] mb-3 tracking-wider" :class="matchStatus === 'ready' ? 'text-crt-green' : 'text-crt-amber'">
                {{ matchStatus === 'joined_waiting' ? 'WAITING FOR HOST' : (matchStatus === 'ready' ? 'OPPONENT JOINED!' : 'WAITING FOR OPPONENT') }}
              </div>
              <div class="font-terminal text-4xl text-arcade-purple mb-3 tracking-[0.3em]">{{ matchCode || joinedMatchCode }}</div>
              <p class="font-mono text-xs text-retro-muted mb-2">
                {{ matchStatus === 'joined_waiting' ? 'Host will start the match for both players' : 'Share this code with your opponent' }}
              </p>

              <div v-if="matchStatus !== 'ready' && matchStatus !== 'joined_waiting'" class="flex items-center justify-center gap-2 mb-4 py-2">
                <span class="w-2 h-2 rounded-full bg-crt-amber animate-blink"></span>
                <span class="font-mono text-[11px] text-crt-amber/70 animate-blink-slow">Scanning for opponent...</span>
              </div>
              <div v-if="matchStatus === 'joined_waiting'" class="flex items-center justify-center gap-2 mb-4 py-2">
                <span class="w-2 h-2 rounded-full bg-crt-cyan animate-blink"></span>
                <span class="font-mono text-[11px] text-crt-cyan/80 animate-blink-slow">Waiting for host to press Start...</span>
              </div>

              <div v-else class="flex flex-col items-center justify-center gap-1 mb-4 py-2">
                <div class="flex items-center justify-center gap-2">
                  <span class="w-2 h-2 rounded-full bg-crt-green" style="box-shadow: 0 0 6px rgba(57,255,20,0.5);"></span>
                  <span class="font-mono text-[11px] text-crt-green">Opponent is ready!</span>
                </div>
                <span v-if="matchOpponentUsername" class="font-mono text-[10px] text-arcade-purple/80">{{ matchOpponentUsername }}</span>
              </div>

              <div class="flex gap-2">
                <button @click="$emit('copy-match-code')" class="btn-retro-ghost flex-1 flex items-center justify-center gap-1.5 !py-2 text-[10px]">
                  COPY CODE
                </button>
                <button v-if="matchStatus !== 'joined_waiting'" @click="$emit('start-created-match')" :disabled="matchStatus !== 'ready'" class="flex-1 !py-2 !px-4 !text-[9px]" :class="matchStatus === 'ready' ? 'btn-retro-primary' : 'btn-retro-ghost opacity-40 cursor-not-allowed'">
                  {{ matchStatus === 'ready' ? 'START' : 'WAITING...' }}
                </button>
                <button v-else type="button" class="flex-1 !py-2 !px-4 !text-[9px] btn-retro-ghost opacity-60 cursor-not-allowed">
                  HOST STARTS
                </button>
              </div>
            </div>
          </template>

          <template v-else>
            <div class="flex p-1 mb-5 rounded-xl gap-1" style="background: #12131c; border: 1px solid #252738;">
              <button type="button" class="flex-1 font-pixel text-[8px] tracking-[0.12em] py-2.5 rounded-lg transition-all duration-200" :class="matchTab === 'duel' ? 'text-crt-white' : 'text-retro-muted hover:text-crt-white/75'" :style="matchTab === 'duel' ? 'background: linear-gradient(180deg, rgba(180,76,255,0.28), rgba(180,76,255,0.08)); border: 1px solid rgba(180,76,255,0.4); box-shadow: 0 0 16px rgba(180,76,255,0.12);' : 'border: 1px solid transparent;'" @click="$emit('switch-hub', 'duel')">1v1 DUEL</button>
              <button type="button" class="flex-1 font-pixel text-[8px] tracking-[0.12em] py-2.5 rounded-lg transition-all duration-200" :class="matchTab === 'group' && groupView === 'menu' ? 'text-crt-white' : 'text-retro-muted hover:text-crt-white/75'" :style="matchTab === 'group' && groupView === 'menu' ? 'background: linear-gradient(180deg, rgba(57,255,20,0.22), rgba(57,255,20,0.06)); border: 1px solid rgba(57,255,20,0.38); box-shadow: 0 0 16px rgba(57,255,20,0.1);' : 'border: 1px solid transparent;'" @click="$emit('switch-hub', 'group')">GROUP</button>
            </div>

            <div v-if="matchTab === 'duel'" class="space-y-4">
              <p class="font-mono text-[11px] text-retro-muted leading-relaxed">
                Same random article pair for two players. Fewest link-clicks wins; faster time breaks ties.
              </p>
              <button type="button" @click="$emit('create-match')" :disabled="matchLoading" class="btn-retro-primary w-full !py-3 font-pixel text-[8px] tracking-[0.15em]">
                {{ matchLoading ? 'CREATING...' : 'CREATE ROOM' }}
              </button>
            </div>

            <div v-if="matchTab === 'group' && groupView === 'menu'" class="space-y-4">
              <p class="font-mono text-[11px] text-retro-muted leading-relaxed">
                2–8 players, same pair. When everyone finishes, the leaderboard ranks by clicks, then time.
              </p>
              <div class="flex items-center justify-between gap-3 rounded-lg px-3 py-2.5" style="background: #12131c; border: 1px solid #252738;">
                <span class="font-mono text-[10px] text-retro-muted">Max players</span>
                <select :value="groupMaxPlayers" @change="$emit('update:group-max-players', Number($event.target.value))" class="min-w-[4.5rem] px-2 py-1.5 rounded-lg font-terminal text-sm bg-[#0a0b11] border border-retro-border text-crt-green focus:border-crt-green focus:outline-none">
                  <option v-for="n in 7" :key="n + 1" :value="n + 1">{{ n + 1 }}</option>
                </select>
              </div>
              <button type="button" @click="$emit('create-group-lobby')" :disabled="groupLoading" class="btn-retro-primary w-full !py-3 font-pixel text-[8px] tracking-[0.15em]" style="border-color: rgba(57,255,20,0.45);">
                {{ groupLoading ? 'CREATING...' : 'CREATE LOBBY' }}
              </button>
            </div>

            <div class="relative py-0.5 mt-4">
              <div class="absolute inset-x-0 top-1/2 -translate-y-1/2 border-t border-retro-border/20"></div>
              <div class="relative flex justify-center">
                <span class="font-pixel text-[6px] tracking-[0.2em] text-retro-muted/80 px-2" style="background: #0d0e15;">ENTER CODE</span>
              </div>
            </div>
            <div class="flex gap-2 mt-3">
              <input :value="joinRoomCode" @input="$emit('update:join-room-code', $event.target.value)" type="text" placeholder="ROOM CODE" maxlength="6" autocomplete="off" class="min-w-0 flex-1 px-3 py-2.5 rounded-lg font-terminal text-lg text-center tracking-[0.28em] bg-[#12131c] border border-retro-border text-crt-white focus:border-crt-cyan focus:outline-none uppercase" />
              <button type="button" @click="$emit('join-by-code', matchTab === 'group' ? 'lobby' : 'match')" :disabled="joinBusy" class="shrink-0 px-4 py-2.5 rounded-lg font-pixel text-[8px] tracking-[0.15em] transition-all touch-manipulation disabled:opacity-40" style="background: rgba(0,229,255,0.08); border: 1.5px solid rgba(0,229,255,0.35); color: #00e5ff;">
                {{ joinBusy ? '...' : 'JOIN' }}
              </button>
            </div>
            <p v-if="unifiedJoinError" class="font-mono text-[10px] text-crt-red mt-2">{{ unifiedJoinError }}</p>
            <button v-if="showLeaveCurrentRoomButton" type="button" @click="$emit('leave-current-room')" :disabled="joinBusy" class="mt-2 w-full px-3 py-2 rounded-lg font-pixel text-[8px] tracking-[0.12em] transition-all touch-manipulation disabled:opacity-40" style="background: rgba(255,68,68,0.08); border: 1.5px solid rgba(255,68,68,0.35); color: #ff6666;">
              {{ joinBusy ? 'LEAVING...' : 'LEAVE CURRENT ROOM' }}
            </button>
          </template>
        </div>
      </div>
    </transition>
  </Teleport>
</template>

<script setup>
defineProps({
  open: { type: Boolean, required: true },
  matchTab: { type: String, required: true },
  groupView: { type: String, required: true },
  groupLobbyCode: { type: String, required: true },
  groupLobbyData: { type: Object, default: null },
  currentUserId: { type: Number, default: null },
  groupStartLoading: { type: Boolean, required: true },
  groupError: { type: String, required: true },
  matchStatus: { type: String, default: null },
  matchCode: { type: String, required: true },
  joinedMatchCode: { type: String, required: true },
  matchOpponentUsername: { type: String, required: true },
  matchLoading: { type: Boolean, required: true },
  groupMaxPlayers: { type: Number, required: true },
  groupLoading: { type: Boolean, required: true },
  joinRoomCode: { type: String, required: true },
  joinBusy: { type: Boolean, required: true },
  unifiedJoinError: { type: String, required: true },
  showLeaveCurrentRoomButton: { type: Boolean, required: true },
})

defineEmits([
  'close',
  'copy-group-code',
  'start-group',
  'copy-match-code',
  'start-created-match',
  'switch-hub',
  'create-match',
  'create-group-lobby',
  'update:group-max-players',
  'update:join-room-code',
  'join-by-code',
  'leave-current-room',
])
</script>
