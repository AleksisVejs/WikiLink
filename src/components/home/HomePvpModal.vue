<template>
  <Teleport to="body">
    <transition name="fade">
      <div v-if="open" class="fixed inset-0 z-[60] flex items-center justify-center p-3 sm:p-4">
        <div class="absolute inset-0 bg-black/85 backdrop-blur-sm" @click="$emit('close')"></div>
        <div class="relative rounded-2xl p-4 sm:p-6 max-w-[520px] w-full max-h-[90vh] overflow-y-auto animate-scale-in" style="background: #0d0e15; border: 1.5px solid rgba(180,76,255,0.25); box-shadow: 0 0 60px rgba(0,0,0,0.8), 0 0 30px rgba(180,76,255,0.06);">
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
                <ul class="font-mono text-[11px] text-crt-white space-y-1.5 max-h-[180px] overflow-y-auto">
                  <li
                    v-for="p in groupLobbyData?.players || []"
                    :key="p.user_id"
                    class="rounded-lg px-2 py-1.5"
                    :style="nameplateStyle(p.profile_nameplate_border)"
                  >
                    <div class="flex items-center justify-between gap-2">
                      <span class="truncate">
                        {{ p.username }}
                        <span v-if="p.user_id === currentUserId" class="text-crt-green/60 text-[9px] ml-1">(you)</span>
                      </span>
                      <span v-if="groupLobbyData?.host_id === p.user_id" class="font-pixel text-[6px] text-crt-amber shrink-0">HOST</span>
                    </div>
                    <div class="mt-1 flex items-center gap-2 text-[9px]">
                      <span class="font-pixel text-[6px] tracking-wider text-retro-muted">{{ formatTitle(p.profile_title) }}</span>
                      <span v-if="p.profile_pinned_badge" class="inline-flex items-center gap-1 text-arcade-gold">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                        </svg>
                        <span class="truncate max-w-[120px]">{{ formatPinnedBadge(p.profile_pinned_badge) }}</span>
                      </span>
                    </div>
                  </li>
                </ul>
              </div>
              <div v-if="groupLobbyData" class="rounded-lg px-3 py-2.5 mb-3 text-left" style="background: linear-gradient(180deg, #12131c, #0f1018); border: 1px solid #252738;">
                <div class="flex items-center justify-between gap-2 mb-2">
                  <div class="font-pixel text-[6px] text-retro-muted tracking-wider">LOBBY SETTINGS</div>
                  <span class="font-mono text-[9px] text-crt-cyan/70">{{ canEditRoomSettings ? 'Host controls' : 'View only' }}</span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                  <div>
                    <p class="font-mono text-[9px] text-retro-muted/70 mb-1">Genre</p>
                    <select :value="roomSettings.genre" :disabled="!canEditRoomSettings || roomSettings.mode === 'custom'" @change="$emit('update-room-settings', { genre: $event.target.value })" class="w-full px-2 py-1.5 rounded-lg font-mono text-[10px] bg-[#0a0b11] border border-retro-border text-crt-white disabled:opacity-50">
                      <option v-for="g in roomGenres" :key="g.id" :value="g.id">{{ g.name }}</option>
                    </select>
                  </div>
                  <div>
                    <p class="font-mono text-[9px] text-retro-muted/70 mb-1">Game mode</p>
                    <select :value="roomSettings.mode" :disabled="!canEditRoomSettings" @change="$emit('update-room-settings', { mode: $event.target.value })" class="w-full px-2 py-1.5 rounded-lg font-mono text-[10px] bg-[#0a0b11] border border-retro-border text-crt-white disabled:opacity-50">
                      <option v-for="m in roomModes" :key="m.id" :value="m.id">{{ m.name }}</option>
                    </select>
                  </div>
                </div>
                <div v-if="roomSettings.mode === 'sprint'" class="mt-2">
                  <p class="font-mono text-[9px] text-retro-muted/70 mb-1">Sprint time (seconds)</p>
                  <input
                    :value="roomSettings.timeLimit"
                    :disabled="!canEditRoomSettings"
                    @input="$emit('update-room-settings', { timeLimit: Number($event.target.value) || 120 })"
                    type="number"
                    min="10"
                    max="3600"
                    class="w-full px-2 py-1.5 rounded-lg font-mono text-[10px] bg-[#0a0b11] border border-retro-border text-crt-white disabled:opacity-50"
                  />
                </div>
                <div v-else-if="roomSettings.mode === 'challenge'" class="mt-2">
                  <p class="font-mono text-[9px] text-retro-muted/70 mb-1">Click limit</p>
                  <input
                    :value="roomSettings.clickLimit"
                    :disabled="!canEditRoomSettings"
                    @input="$emit('update-room-settings', { clickLimit: Number($event.target.value) || 6 })"
                    type="number"
                    min="1"
                    max="200"
                    class="w-full px-2 py-1.5 rounded-lg font-mono text-[10px] bg-[#0a0b11] border border-retro-border text-crt-white disabled:opacity-50"
                  />
                </div>
                <div v-else-if="roomSettings.mode === 'custom'" class="mt-2 grid grid-cols-1 gap-2">
                  <div>
                    <p class="font-mono text-[9px] text-retro-muted/70 mb-1">Custom start article</p>
                    <input
                      :value="roomSettings.customStartTitle"
                      :disabled="!canEditRoomSettings"
                      @input="$emit('update-room-settings', { customStartTitle: $event.target.value })"
                      type="text"
                      class="w-full px-2 py-1.5 rounded-lg font-mono text-[10px] bg-[#0a0b11] border border-retro-border text-crt-white disabled:opacity-50"
                    />
                  </div>
                  <div>
                    <p class="font-mono text-[9px] text-retro-muted/70 mb-1">Custom target article</p>
                    <input
                      :value="roomSettings.customEndTitle"
                      :disabled="!canEditRoomSettings"
                      @input="$emit('update-room-settings', { customEndTitle: $event.target.value })"
                      type="text"
                      class="w-full px-2 py-1.5 rounded-lg font-mono text-[10px] bg-[#0a0b11] border border-retro-border text-crt-white disabled:opacity-50"
                    />
                  </div>
                </div>
                <div class="flex flex-wrap gap-1.5 mt-2">
                  <button
                    v-for="mod in roomModifiers"
                    :key="mod.id"
                    type="button"
                    @click="$emit('toggle-room-modifier', mod.id)"
                    :disabled="!canEditRoomSettings"
                    class="px-2 py-1 rounded font-mono text-[9px] border transition-all"
                    :style="roomSettings.modifiers?.includes(mod.id)
                      ? 'color:#39ff14;border-color:rgba(57,255,20,0.35);background:rgba(57,255,20,0.08);'
                      : 'color:#9ca3af;border-color:#2a2d3f;background:#0a0b11;'">
                    {{ mod.name }}
                  </button>
                </div>
                <div class="mt-2 space-y-1.5">
                  <div v-for="mod in roomModifiers" :key="`desc-lobby-${mod.id}`" class="text-left">
                    <p class="font-mono text-[9px] leading-snug" :class="roomSettings.modifiers?.includes(mod.id) ? 'text-crt-green/85' : 'text-retro-muted/65'">
                      <span class="text-crt-cyan/75">{{ mod.name }}:</span> {{ mod.description || 'No description available.' }}
                    </p>
                  </div>
                </div>
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
              <div v-if="canInviteFriends && groupLobbyData?.is_host" class="mt-3 rounded-lg px-3 py-2.5 text-left" style="background: #12131c; border: 1px solid #252738;">
                <div class="font-pixel text-[6px] text-retro-muted tracking-wider mb-2">INVITE FRIEND TO LOBBY</div>
                <div class="flex items-center gap-2">
                  <select v-model="selectedGroupInviteFriend" class="min-w-0 flex-1 px-2 py-1.5 rounded-lg font-mono text-[10px] bg-[#0a0b11] border border-retro-border text-crt-white">
                    <option value="">Select friend</option>
                    <option v-for="f in friends" :key="`group-friend-${f.friend_user_id}`" :value="f.username">{{ f.username }}</option>
                  </select>
                  <button
                    type="button"
                    @click="$emit('invite-friend-to-group', selectedGroupInviteFriend)"
                    :disabled="inviteBusy || !selectedGroupInviteFriend"
                    class="px-3 py-1.5 rounded-lg font-pixel text-[8px] tracking-[0.12em] transition-all disabled:opacity-40"
                    style="background: rgba(0,229,255,0.08); border: 1.5px solid rgba(0,229,255,0.35); color: #00e5ff;"
                  >
                    {{ inviteBusy ? '...' : 'INVITE' }}
                  </button>
                </div>
                <p v-if="inviteError" class="font-mono text-[10px] text-crt-red mt-1.5">{{ inviteError }}</p>
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

              <div v-if="matchStatus === 'waiting' || matchStatus === 'joined_waiting' || matchStatus === 'ready'" class="mb-4 py-2">
                <div v-if="matchStatus === 'ready'" class="flex flex-col items-center justify-center gap-1">
                  <div class="flex items-center justify-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-crt-green" style="box-shadow: 0 0 6px rgba(57,255,20,0.5);"></span>
                    <span class="font-mono text-[11px] text-crt-green">Opponent is ready!</span>
                  </div>
                  <span v-if="matchOpponentUsername" class="font-mono text-[10px] text-arcade-purple/80">{{ matchOpponentUsername }}</span>
                </div>
                <div v-else-if="matchStatus === 'joined_waiting'" class="flex items-center justify-center gap-2">
                  <span class="w-2 h-2 rounded-full bg-crt-cyan animate-blink"></span>
                  <span class="font-mono text-[11px] text-crt-cyan/80 animate-blink-slow">Waiting for host to press Start...</span>
                </div>
                <div v-else class="flex items-center justify-center gap-2">
                  <span class="w-2 h-2 rounded-full bg-crt-amber animate-blink"></span>
                  <span class="font-mono text-[11px] text-crt-amber/70 animate-blink-slow">Scanning for opponent...</span>
                </div>
              </div>
              <div class="rounded-lg px-3 py-2.5 mb-3 text-left" style="background: linear-gradient(180deg, #12131c, #0f1018); border: 1px solid #252738;">
                <div class="flex items-center justify-between gap-2 mb-2">
                  <div class="font-pixel text-[6px] text-retro-muted tracking-wider">MATCH SETTINGS</div>
                  <span class="font-mono text-[9px] text-crt-cyan/70">{{ canEditRoomSettings ? 'Host controls' : 'View only' }}</span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                  <div>
                    <p class="font-mono text-[9px] text-retro-muted/70 mb-1">Genre</p>
                    <select :value="roomSettings.genre" :disabled="!canEditRoomSettings || roomSettings.mode === 'custom'" @change="$emit('update-room-settings', { genre: $event.target.value })" class="w-full px-2 py-1.5 rounded-lg font-mono text-[10px] bg-[#0a0b11] border border-retro-border text-crt-white disabled:opacity-50">
                      <option v-for="g in roomGenres" :key="g.id" :value="g.id">{{ g.name }}</option>
                    </select>
                  </div>
                  <div>
                    <p class="font-mono text-[9px] text-retro-muted/70 mb-1">Game mode</p>
                    <select :value="roomSettings.mode" :disabled="!canEditRoomSettings" @change="$emit('update-room-settings', { mode: $event.target.value })" class="w-full px-2 py-1.5 rounded-lg font-mono text-[10px] bg-[#0a0b11] border border-retro-border text-crt-white disabled:opacity-50">
                      <option v-for="m in roomModes" :key="m.id" :value="m.id">{{ m.name }}</option>
                    </select>
                  </div>
                </div>
                <div v-if="roomSettings.mode === 'sprint'" class="mt-2">
                  <p class="font-mono text-[9px] text-retro-muted/70 mb-1">Sprint time (seconds)</p>
                  <input
                    :value="roomSettings.timeLimit"
                    :disabled="!canEditRoomSettings"
                    @input="$emit('update-room-settings', { timeLimit: Number($event.target.value) || 120 })"
                    type="number"
                    min="10"
                    max="3600"
                    class="w-full px-2 py-1.5 rounded-lg font-mono text-[10px] bg-[#0a0b11] border border-retro-border text-crt-white disabled:opacity-50"
                  />
                </div>
                <div v-else-if="roomSettings.mode === 'challenge'" class="mt-2">
                  <p class="font-mono text-[9px] text-retro-muted/70 mb-1">Click limit</p>
                  <input
                    :value="roomSettings.clickLimit"
                    :disabled="!canEditRoomSettings"
                    @input="$emit('update-room-settings', { clickLimit: Number($event.target.value) || 6 })"
                    type="number"
                    min="1"
                    max="200"
                    class="w-full px-2 py-1.5 rounded-lg font-mono text-[10px] bg-[#0a0b11] border border-retro-border text-crt-white disabled:opacity-50"
                  />
                </div>
                <div v-else-if="roomSettings.mode === 'custom'" class="mt-2 grid grid-cols-1 gap-2">
                  <div>
                    <p class="font-mono text-[9px] text-retro-muted/70 mb-1">Custom start article</p>
                    <input
                      :value="roomSettings.customStartTitle"
                      :disabled="!canEditRoomSettings"
                      @input="$emit('update-room-settings', { customStartTitle: $event.target.value })"
                      type="text"
                      class="w-full px-2 py-1.5 rounded-lg font-mono text-[10px] bg-[#0a0b11] border border-retro-border text-crt-white disabled:opacity-50"
                    />
                  </div>
                  <div>
                    <p class="font-mono text-[9px] text-retro-muted/70 mb-1">Custom target article</p>
                    <input
                      :value="roomSettings.customEndTitle"
                      :disabled="!canEditRoomSettings"
                      @input="$emit('update-room-settings', { customEndTitle: $event.target.value })"
                      type="text"
                      class="w-full px-2 py-1.5 rounded-lg font-mono text-[10px] bg-[#0a0b11] border border-retro-border text-crt-white disabled:opacity-50"
                    />
                  </div>
                </div>
                <div class="flex flex-wrap gap-1.5 mt-2">
                  <button
                    v-for="mod in roomModifiers"
                    :key="mod.id"
                    type="button"
                    @click="$emit('toggle-room-modifier', mod.id)"
                    :disabled="!canEditRoomSettings"
                    class="px-2 py-1 rounded font-mono text-[9px] border transition-all"
                    :style="roomSettings.modifiers?.includes(mod.id)
                      ? 'color:#39ff14;border-color:rgba(57,255,20,0.35);background:rgba(57,255,20,0.08);'
                      : 'color:#9ca3af;border-color:#2a2d3f;background:#0a0b11;'">
                    {{ mod.name }}
                  </button>
                </div>
                <div class="mt-2 space-y-1.5">
                  <div v-for="mod in roomModifiers" :key="`desc-match-${mod.id}`" class="text-left">
                    <p class="font-mono text-[9px] leading-snug" :class="roomSettings.modifiers?.includes(mod.id) ? 'text-crt-green/85' : 'text-retro-muted/65'">
                      <span class="text-crt-cyan/75">{{ mod.name }}:</span> {{ mod.description || 'No description available.' }}
                    </p>
                  </div>
                </div>
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
              <div v-if="canInviteFriends && matchCode" class="mt-3 rounded-lg px-3 py-2.5 text-left" style="background: #12131c; border: 1px solid #252738;">
                <div class="font-pixel text-[6px] text-retro-muted tracking-wider mb-2">INVITE FRIEND TO 1v1</div>
                <div class="flex items-center gap-2">
                  <select v-model="selectedMatchInviteFriend" class="min-w-0 flex-1 px-2 py-1.5 rounded-lg font-mono text-[10px] bg-[#0a0b11] border border-retro-border text-crt-white">
                    <option value="">Select friend</option>
                    <option v-for="f in friends" :key="`match-friend-${f.friend_user_id}`" :value="f.username">{{ f.username }}</option>
                  </select>
                  <button
                    type="button"
                    @click="$emit('invite-friend-to-match', selectedMatchInviteFriend)"
                    :disabled="inviteBusy || !selectedMatchInviteFriend"
                    class="px-3 py-1.5 rounded-lg font-pixel text-[8px] tracking-[0.12em] transition-all disabled:opacity-40"
                    style="background: rgba(180,76,255,0.08); border: 1.5px solid rgba(180,76,255,0.35); color: #b44cff;"
                  >
                    {{ inviteBusy ? '...' : 'INVITE' }}
                  </button>
                </div>
                <p v-if="inviteError" class="font-mono text-[10px] text-crt-red mt-1.5">{{ inviteError }}</p>
              </div>
            </div>
          </template>

          <template v-else>
            <div class="mb-4 rounded-xl px-3 py-3" style="background: rgba(0,229,255,0.04); border: 1px solid rgba(0,229,255,0.16);">
              <div class="font-pixel text-[7px] text-crt-cyan tracking-[0.14em] mb-2">ROOM FLOW</div>
              <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                <div class="rounded-lg px-2.5 py-2" style="background: #12131c; border: 1px solid #252738;">
                  <p class="font-pixel text-[6px] tracking-wider text-crt-cyan mb-1">1. CHOOSE TYPE</p>
                  <p class="font-mono text-[10px] text-retro-muted">Pick Duel or Group.</p>
                </div>
                <div class="rounded-lg px-2.5 py-2" style="background: #12131c; border: 1px solid #252738;">
                  <p class="font-pixel text-[6px] tracking-wider text-crt-amber mb-1">2. CREATE OR JOIN</p>
                  <p class="font-mono text-[10px] text-retro-muted">Create a room or enter a code.</p>
                </div>
                <div class="rounded-lg px-2.5 py-2" style="background: #12131c; border: 1px solid #252738;">
                  <p class="font-pixel text-[6px] tracking-wider text-crt-green mb-1">3. START ROUND</p>
                  <p class="font-mono text-[10px] text-retro-muted">Host starts when everyone is ready.</p>
                </div>
              </div>
            </div>

            <div class="flex p-1 mb-5 rounded-xl gap-1" style="background: #12131c; border: 1px solid #252738;">
              <button type="button" class="flex-1 font-pixel text-[8px] tracking-[0.12em] py-2.5 rounded-lg transition-all duration-200" :class="matchTab === 'duel' ? 'text-crt-white' : 'text-retro-muted hover:text-crt-white/75'" :style="matchTab === 'duel' ? 'background: linear-gradient(180deg, rgba(180,76,255,0.28), rgba(180,76,255,0.08)); border: 1px solid rgba(180,76,255,0.4); box-shadow: 0 0 16px rgba(180,76,255,0.12);' : 'border: 1px solid transparent;'" @click="$emit('switch-hub', 'duel')">1v1 DUEL</button>
              <button type="button" class="flex-1 font-pixel text-[8px] tracking-[0.12em] py-2.5 rounded-lg transition-all duration-200" :class="matchTab === 'group' && groupView === 'menu' ? 'text-crt-white' : 'text-retro-muted hover:text-crt-white/75'" :style="matchTab === 'group' && groupView === 'menu' ? 'background: linear-gradient(180deg, rgba(57,255,20,0.22), rgba(57,255,20,0.06)); border: 1px solid rgba(57,255,20,0.38); box-shadow: 0 0 16px rgba(57,255,20,0.1);' : 'border: 1px solid transparent;'" @click="$emit('switch-hub', 'group')">GROUP</button>
            </div>

            <div v-if="matchTab === 'duel'" class="space-y-4">
              <div class="rounded-lg px-3 py-2.5" style="background: #12131c; border: 1px solid #252738;">
                <p class="font-pixel text-[7px] tracking-wider text-arcade-purple mb-1.5">DUEL RULES</p>
                <p class="font-mono text-[11px] text-retro-muted leading-relaxed">
                  Same random article pair for two players. Fewest link-clicks wins; faster time breaks ties.
                </p>
              </div>
              <button type="button" @click="$emit('create-match')" :disabled="matchLoading" class="btn-retro-primary w-full !py-3 font-pixel text-[8px] tracking-[0.15em]">
                {{ matchLoading ? 'CREATING...' : 'CREATE ROOM' }}
              </button>
            </div>

            <div v-if="matchTab === 'group' && groupView === 'menu'" class="space-y-4">
              <div class="rounded-lg px-3 py-2.5" style="background: #12131c; border: 1px solid #252738;">
                <p class="font-pixel text-[7px] tracking-wider text-crt-green mb-1.5">GROUP RULES</p>
                <p class="font-mono text-[11px] text-retro-muted leading-relaxed">
                  2-8 players, same pair. When everyone finishes, the leaderboard ranks by clicks, then time.
                </p>
              </div>
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
            <div class="flex flex-col sm:flex-row gap-2 mt-3">
              <input :value="joinRoomCode" @input="$emit('update:join-room-code', $event.target.value)" type="text" placeholder="ROOM CODE" maxlength="6" autocomplete="off" class="min-w-0 flex-1 px-3 py-2.5 rounded-lg font-terminal text-lg text-center tracking-[0.28em] bg-[#12131c] border border-retro-border text-crt-white focus:border-crt-cyan focus:outline-none uppercase" />
              <button type="button" @click="$emit('join-by-code', matchTab === 'group' ? 'lobby' : 'match')" :disabled="joinBusy" class="shrink-0 w-full sm:w-auto px-4 py-2.5 rounded-lg font-pixel text-[8px] tracking-[0.15em] transition-all touch-manipulation disabled:opacity-40" style="background: rgba(0,229,255,0.08); border: 1.5px solid rgba(0,229,255,0.35); color: #00e5ff;">
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
import { ref } from 'vue'

const selectedMatchInviteFriend = ref('')
const selectedGroupInviteFriend = ref('')

const NAMEPLATE_BORDER_STYLE = {
  default: 'border: 1px solid rgba(255,255,255,0.1); background: #10121b;',
  dashed: 'border: 1px dashed rgba(255,255,255,0.28); background: #10121b;',
  double: 'border: 3px double rgba(255,255,255,0.28); background: #10121b;',
  glow: 'border: 1px solid rgba(57,255,20,0.45); background: #10121b; box-shadow: 0 0 10px rgba(57,255,20,0.2);',
}

function nameplateStyle(borderId) {
  return NAMEPLATE_BORDER_STYLE[borderId] || NAMEPLATE_BORDER_STYLE.default
}

function formatTitle(value) {
  const raw = String(value || 'newcomer')
  return raw.replace(/_/g, ' ').toUpperCase()
}

function formatPinnedBadge(value) {
  const raw = String(value || '')
  if (!raw) return ''
  return raw.replace(/_/g, ' ').toUpperCase()
}

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
  roomSettings: { type: Object, required: true },
  canEditRoomSettings: { type: Boolean, default: false },
  roomSettingsSaving: { type: Boolean, default: false },
  roomGenres: { type: Array, default: () => [] },
  roomModes: { type: Array, default: () => [] },
  roomModifiers: { type: Array, default: () => [] },
  friends: { type: Array, default: () => [] },
  inviteBusy: { type: Boolean, default: false },
  inviteError: { type: String, default: '' },
  canInviteFriends: { type: Boolean, default: false },
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
  'update-room-settings',
  'toggle-room-modifier',
  'save-room-settings',
  'invite-friend-to-match',
  'invite-friend-to-group',
])
</script>
