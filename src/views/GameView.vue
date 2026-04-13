<template>
  <div class="min-h-screen flex flex-col">

    <!-- Navbar -->
    <nav class="sticky top-0 z-[40] glass-header"
         style="border-bottom: 1px solid rgba(37,39,56,0.5);">
      <div class="max-w-6xl mx-auto px-2 sm:px-4">

        <!-- Main controls row -->
        <div class="relative flex items-center justify-between h-11 sm:h-12">

          <!-- Left: Action buttons -->
          <div class="flex items-center gap-1 sm:gap-1.5 shrink-0">
            <button @click="confirmQuit"
                    class="nav-action-btn nav-action-btn--red"
                    title="Exit game (Esc)">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
              <span class="nav-action-label">ESC</span>
            </button>

            <button @click="handleGoBack"
                    :disabled="game.state.path.length <= 1 || !game.isPlaying.value || game.backDisabled.value"
                    class="nav-action-btn nav-action-btn--cyan"
                    title="Go back (Backspace)">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
              </svg>
              <span class="nav-action-label">BACK</span>
            </button>

            <button v-if="canReroll && game.isPlaying.value"
                    @click="rerollPair"
                    :disabled="rerolling"
                    class="nav-action-btn nav-action-btn--amber"
                    title="New pair (R)">
              <svg class="w-3.5 h-3.5" :class="{ 'animate-spin': rerolling }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
              <span class="nav-action-label">NEW</span>
            </button>

            <button v-if="currentMode?.noTarget && game.isPlaying.value"
                    @click="finishFreeplay"
                    class="nav-action-btn nav-action-btn--green"
                    title="Finish session">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
              <span class="nav-action-label">DONE</span>
            </button>
          </div>

          <!-- Center: Route indicator (desktop) -->
          <div v-if="!currentMode?.noTarget && !initialLoading"
               class="hidden md:flex items-center absolute left-1/2 -translate-x-1/2 nav-route-pill">
            <div class="flex items-center gap-1.5 min-w-0 wiki-point">
              <span class="w-1.5 h-1.5 rounded-full bg-crt-green shrink-0" style="box-shadow: 0 0 6px rgba(57,255,20,0.5);"></span>
              <span class="truncate font-mono text-[11px] text-crt-green max-w-[180px]">
                {{ game.state.startArticle?.title }}
              </span>
              <span class="wiki-point-full">{{ game.state.startArticle?.title }}</span>
            </div>
            <div class="flex items-center gap-1 mx-2.5 shrink-0">
              <span class="w-4 h-px bg-retro-border/30"></span>
              <svg class="w-3.5 h-3.5 text-retro-muted/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
              </svg>
              <span class="w-4 h-px bg-retro-border/30"></span>
            </div>
            <div class="flex items-center gap-1.5 min-w-0 wiki-point">
              <span class="w-1.5 h-1.5 rounded-full shrink-0"
                    :class="game.isWon.value ? 'bg-crt-green' : 'animate-blink'"
                    :style="game.isWon.value ? 'box-shadow: 0 0 6px rgba(57,255,20,0.5)' : 'background: #ff2ecc; box-shadow: 0 0 6px rgba(255,46,204,0.5);'"></span>
              <span class="truncate font-mono text-[11px] max-w-[180px]"
                    :class="game.isWon.value ? 'text-crt-green' : 'text-crt-magenta'">
                {{ game.state.targetArticle?.title }}
              </span>
              <span class="wiki-point-full">{{ game.state.targetArticle?.title }}</span>
            </div>
          </div>

          <!-- Right: Stats & controls -->
          <div class="flex items-center gap-1.5 sm:gap-2 shrink-0">
            <!-- Hint button -->
            <div v-if="game.state.targetArticle && game.isPlaying.value" class="relative">
              <button @click="showHintMenu = !showHintMenu"
                      :disabled="game.state.hints <= 0"
                      class="hint-trigger-btn flex items-center gap-1 px-1.5 sm:px-2 py-1 sm:py-1.5 rounded-lg transition-all duration-200 disabled:opacity-25 disabled:pointer-events-none touch-manipulation group"
                      title="Use a hint">
                <svg class="w-3.5 h-3.5 text-crt-amber group-hover:drop-shadow-[0_0_6px_rgba(255,191,0,0.5)] transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                </svg>
                <span class="font-terminal text-sm text-crt-amber leading-none">{{ game.state.hints }}</span>
              </button>

              <!-- Hint dropdown -->
              <transition name="hint-menu">
                <div v-if="showHintMenu" class="hint-dropdown absolute right-0 top-full mt-2 z-50">
                  <div class="hint-dropdown-header">
                    <svg class="w-3 h-3 text-crt-amber/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                    </svg>
                    <span class="font-pixel text-[7px] text-crt-amber/60 tracking-[0.15em]">USE HINT</span>
                    <span class="ml-auto font-terminal text-[11px] text-crt-amber/40">{{ game.state.hints }} left</span>
                  </div>
                  <div class="p-1.5 space-y-1">
                    <button @click="useHintAction('category')" class="hint-option group/opt">
                      <div class="hint-option-icon" style="background: rgba(0,229,255,0.06); border-color: rgba(0,229,255,0.15);">
                        <svg class="w-4 h-4 text-crt-cyan" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                        </svg>
                      </div>
                      <div class="flex-1 min-w-0">
                        <div class="font-mono text-[11px] text-retro-light group-hover/opt:text-crt-cyan transition-colors">Category Reveal</div>
                        <div class="font-mono text-[9px] text-retro-muted/50 mt-0.5">Shows the target's topic category</div>
                      </div>
                      <svg class="w-3 h-3 text-retro-border/40 group-hover/opt:text-crt-cyan/50 transition-colors shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                      </svg>
                    </button>
                    <button @click="useHintAction('hotcold')" class="hint-option group/opt">
                      <div class="hint-option-icon" style="background: rgba(255,68,68,0.06); border-color: rgba(255,68,68,0.15);">
                        <svg class="w-4 h-4 text-crt-red" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z" />
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.879 16.121A3 3 0 1012.015 11L11 14H9c0 .768.293 1.536.879 2.121z" />
                        </svg>
                      </div>
                      <div class="flex-1 min-w-0">
                        <div class="font-mono text-[11px] text-retro-light group-hover/opt:text-crt-red transition-colors">Hot / Cold</div>
                        <div class="font-mono text-[9px] text-retro-muted/50 mt-0.5">How close you are to the target</div>
                      </div>
                      <svg class="w-3 h-3 text-retro-border/40 group-hover/opt:text-crt-red/50 transition-colors shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                      </svg>
                    </button>
                  </div>
                </div>
              </transition>
            </div>

            <!-- Stats group -->
            <div class="nav-stats-group">
              <div class="flex items-center gap-1">
                <svg class="w-3 h-3 text-retro-muted/40 hidden sm:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122" />
                </svg>
                <span class="font-terminal text-base sm:text-lg font-bold leading-none" :class="clickLimitClass">
                  {{ game.state.clicks }}<span v-if="game.state.effectiveClickLimit" class="text-retro-muted text-[10px] sm:text-xs">/{{ game.state.effectiveClickLimit }}</span>
                </span>
              </div>
              <div class="nav-stats-divider"></div>
              <div class="flex items-center gap-1">
                <svg class="w-3 h-3 text-retro-muted/40 hidden sm:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span v-if="game.formattedRemaining.value !== null"
                      class="font-terminal text-base sm:text-lg font-bold leading-none" :class="timerClass">
                  {{ game.formattedRemaining.value }}
                </span>
                <span v-else class="font-terminal text-base sm:text-lg font-bold leading-none text-crt-white">{{ game.formattedTime.value }}</span>
              </div>
            </div>

            <!-- Mute (desktop) -->
            <button @click="sound.toggleMute()" class="hidden sm:flex items-center justify-center w-8 h-8 rounded-lg transition-all hover:bg-retro-surface/50" :title="sound.muted.value ? 'Unmute' : 'Mute'">
              <svg v-if="!sound.muted.value" class="w-3.5 h-3.5 text-retro-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072M12 6l-4 4H4v4h4l4 4V6z" />
              </svg>
              <svg v-else class="w-3.5 h-3.5 text-retro-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707A1 1 0 0112 5v14a1 1 0 01-1.707.707L5.586 15zM17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2" />
              </svg>
            </button>

            <!-- Status dot -->
            <span class="w-2 h-2 rounded-full shrink-0"
                  :style="game.isPlaying.value
                    ? 'background: #39ff14; box-shadow: 0 0 6px rgba(57,255,20,0.5);'
                    : 'background: #555770;'"></span>

            <!-- Active modifiers (desktop) -->
            <div v-if="game.state.modifiers.length > 0" class="hidden sm:flex items-center gap-1">
              <span v-for="modId in game.state.modifiers" :key="modId"
                    class="w-6 h-6 rounded flex items-center justify-center"
                    style="background: rgba(255,191,0,0.08); border: 1px solid rgba(255,191,0,0.2);"
                    :title="game.MODIFIERS[modId]?.name">
                <svg class="w-3.5 h-3.5 text-crt-amber" fill="none" stroke="currentColor" viewBox="0 0 24 24" v-html="modifierSvgPath(modId)"></svg>
              </span>
            </div>
          </div>
        </div>

        <!-- Route row (mobile) -->
        <div v-if="!currentMode?.noTarget && !initialLoading"
             class="md:hidden flex items-center justify-center pb-1.5 -mt-0.5">
          <div class="nav-route-pill nav-route-pill--mobile">
            <div class="flex items-center gap-1.5 min-w-0 wiki-point">
              <span class="w-1.5 h-1.5 rounded-full bg-crt-green shrink-0" style="box-shadow: 0 0 5px rgba(57,255,20,0.5);"></span>
              <span class="truncate font-mono text-[10px] text-crt-green max-w-[32vw]">{{ game.state.startArticle?.title }}</span>
              <span class="wiki-point-full">{{ game.state.startArticle?.title }}</span>
            </div>
            <div class="flex items-center gap-0.5 mx-1.5 shrink-0">
              <span class="w-2.5 h-px bg-retro-border/25"></span>
              <svg class="w-3 h-3 text-retro-muted/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
              </svg>
              <span class="w-2.5 h-px bg-retro-border/25"></span>
            </div>
            <div class="flex items-center gap-1.5 min-w-0 wiki-point">
              <span class="w-1.5 h-1.5 rounded-full shrink-0"
                    :class="game.isWon.value ? 'bg-crt-green' : 'animate-blink'"
                    :style="game.isWon.value ? 'box-shadow: 0 0 5px rgba(57,255,20,0.5)' : 'background: #ff2ecc; box-shadow: 0 0 5px rgba(255,46,204,0.5);'"></span>
              <span class="truncate font-mono text-[10px] max-w-[32vw]"
                    :class="game.isWon.value ? 'text-crt-green' : 'text-crt-magenta'">{{ game.state.targetArticle?.title }}</span>
              <span class="wiki-point-full">{{ game.state.targetArticle?.title }}</span>
            </div>
          </div>
        </div>

        <!-- Breadcrumb -->
        <div v-if="game.state.path.length > 1 && !initialLoading"
             class="nav-breadcrumb">
          <template v-for="(article, idx) in displayPath" :key="idx">
            <span v-if="idx > 0" class="text-retro-border/30 shrink-0 text-[8px] sm:text-[10px] select-none">&rsaquo;</span>
            <span v-if="article.type === 'collapsed'"
                  class="shrink-0 px-1.5 py-0.5 rounded text-retro-muted/40 font-mono text-[9px] sm:text-[10px] whitespace-nowrap">
              {{ article.label }}
            </span>
            <span v-else
                  class="shrink-0 px-1.5 sm:px-2 py-0.5 rounded-md font-mono text-[9px] sm:text-[10px] whitespace-nowrap transition-colors"
                  :class="article.active ? 'text-crt-green' : 'text-retro-muted/60'"
                  :style="article.active ? 'background: rgba(57,255,20,0.06); border: 1px solid rgba(57,255,20,0.1);' : ''">
              {{ article.label }}
            </span>
          </template>
        </div>
      </div>
    </nav>

    <!-- Hint result banner -->
    <transition name="hint-banner">
      <div v-if="hintResult" class="sticky top-12 z-30 mx-auto max-w-4xl px-3 sm:px-4">
        <div class="hint-banner mt-2" :class="'hint-banner--' + hintResult.type">
          <div class="hint-banner-glow"></div>
          <div class="relative flex items-center gap-3 px-4 py-3">
            <!-- Icon -->
            <div class="hint-banner-icon shrink-0" :class="'hint-banner-icon--' + hintResult.type">
              <svg v-if="hintResult.type === 'category'" class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
              </svg>
              <svg v-else class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z" />
              </svg>
            </div>
            <!-- Content -->
            <div class="flex-1 min-w-0">
              <div class="font-pixel text-[7px] tracking-[0.15em] mb-0.5"
                   :class="hintResult.type === 'category' ? 'text-crt-cyan/50' : 'text-crt-amber/50'">
                {{ hintResult.type === 'category' ? 'CATEGORY HINT' : 'TEMPERATURE CHECK' }}
              </div>
              <div class="font-mono text-[12px] sm:text-[13px] leading-snug"
                   :class="hintBannerTextClass">
                {{ hintResult.text }}
              </div>
            </div>
            <!-- Dismiss -->
            <button @click="hintResult = null"
                    class="shrink-0 p-1 rounded-md transition-colors hover:bg-white/5">
              <svg class="w-3.5 h-3.5 text-retro-muted/40 hover:text-retro-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          <!-- Temperature bar for hot/cold -->
          <div v-if="hintResult.type === 'hotcold'" class="hint-temp-bar">
            <div class="hint-temp-fill" :style="{ width: hintTemperature + '%' }"></div>
          </div>
        </div>
      </div>
    </transition>

    <!-- Content area -->
    <main class="flex-1" :class="{ 'screen-shake': showScreenShake }"
          @click.self="showHintMenu = false">

      <!-- Boot sequence -->
      <div v-if="initialLoading" class="flex items-center justify-center" style="min-height: calc(100vh - 3rem);">
        <div class="text-center max-w-sm px-4">
          <div class="inline-flex items-center gap-2 mb-6 px-4 py-2 rounded-lg"
               style="background: rgba(57,255,20,0.04); border: 1px solid rgba(57,255,20,0.12);">
            <span class="w-2 h-2 rounded-full bg-crt-green/60 animate-glow-pulse" style="box-shadow: 0 0 6px rgba(57,255,20,0.4);"></span>
            <span class="font-pixel text-[9px] text-crt-green tracking-[0.2em]">INITIALIZING</span>
          </div>
          <div class="font-mono text-sm text-retro-muted space-y-3 text-left rounded-xl p-5"
               style="background: rgba(18,19,28,0.5); border: 1px solid rgba(37,39,56,0.4);">
            <p v-if="bootStep >= 1" class="animate-fade-in flex items-center gap-3">
              <span class="w-6 h-6 rounded-md flex items-center justify-center shrink-0" style="background: rgba(57,255,20,0.08); border: 1px solid rgba(57,255,20,0.15);">
                <svg class="w-3.5 h-3.5 text-crt-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                </svg>
              </span>
              <span>Connecting to Wikipedia API...</span>
            </p>
            <p v-if="bootStep >= 2" class="animate-fade-in flex items-center gap-3">
              <span class="w-6 h-6 rounded-md flex items-center justify-center shrink-0" style="background: rgba(0,229,255,0.08); border: 1px solid rgba(0,229,255,0.15);">
                <svg class="w-3.5 h-3.5 text-crt-cyan" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
              </span>
              <span>Fetching articles...</span>
            </p>
            <p v-if="bootStep >= 3" class="animate-fade-in flex items-center gap-3">
              <span class="w-6 h-6 rounded-md flex items-center justify-center shrink-0" style="background: rgba(255,191,0,0.08); border: 1px solid rgba(255,191,0,0.15);">
                <svg class="w-3.5 h-3.5 text-crt-amber" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                </svg>
              </span>
              <span>{{ props.mode === 'daily' ? 'Loading daily challenge' : props.mode === 'trending' ? 'Fetching trending articles' : genreId !== 'random' ? `Scanning ${genre.name} topics` : 'Randomizing challenge' }}...</span>
            </p>
          </div>
          <div class="mt-6 retro-divider max-w-[200px] mx-auto"></div>
          <p class="font-terminal text-xl text-crt-green animate-blink mt-4">_</p>
        </div>
      </div>

      <!-- Article content -->
      <div v-else class="max-w-4xl mx-auto px-3 sm:px-5 py-4 sm:py-8">

        <!-- Loading skeleton -->
        <div v-if="wiki.loading.value" class="space-y-4 animate-fade-in">
          <div class="skeleton h-10 w-3/4 rounded-lg"></div>
          <div class="skeleton h-px w-full"></div>
          <div class="space-y-3">
            <div class="skeleton h-4 w-full"></div>
            <div class="skeleton h-4 w-11/12"></div>
            <div class="skeleton h-4 w-4/5"></div>
            <div class="skeleton h-4 w-full"></div>
            <div class="skeleton h-4 w-3/4"></div>
          </div>
          <div class="skeleton h-4 w-1/3 mt-6"></div>
          <div class="space-y-3">
            <div class="skeleton h-4 w-full"></div>
            <div class="skeleton h-4 w-5/6"></div>
            <div class="skeleton h-4 w-full"></div>
          </div>
        </div>

        <!-- Error -->
        <div v-else-if="wiki.error.value" class="text-center py-24">
          <div class="font-pixel text-[9px] text-crt-red mb-3 tracking-widest">ERROR</div>
          <p class="font-mono text-sm text-retro-light mb-5">{{ wiki.error.value }}</p>
          <button @click="loadArticle(game.state.currentArticle)" class="btn-retro-secondary">RETRY</button>
        </div>

        <!-- Article -->
        <article v-else-if="articleData" class="animate-fade-in">
          <h1 class="font-terminal text-2xl sm:text-4xl text-crt-cyan mb-4 sm:mb-5 pb-3 sm:pb-4 relative"
              style="text-shadow: 0 0 12px rgba(0,229,255,0.25);"
              v-html="articleData.displayTitle"></h1>
          <div class="retro-divider mb-5 -mt-1"></div>
          <div class="wiki-content"
               :class="{ 'fog-mode': game.state.modifiers.includes('fog') }"
               ref="contentRef"
               @click="handleLinkClick"
               @mouseover="handleLinkHover"
               @mouseout="handleLinkHoverEnd"
               v-html="processedHtml"></div>
        </article>
      </div>
    </main>

    <!-- Article preview tooltip -->
    <div v-if="previewData && previewPos"
         class="wiki-preview-tooltip"
         :style="{ top: previewPos.y + 'px', left: previewPos.x + 'px' }">
      <div class="flex gap-2.5">
        <img v-if="previewData.thumbnail" :src="previewData.thumbnail" class="preview-thumb" alt="">
        <div class="min-w-0">
          <div class="preview-title">{{ previewData.title }}</div>
          <div v-if="previewData.description" class="font-mono text-[9px] text-crt-amber/60 mb-1">{{ previewData.description }}</div>
          <div class="preview-extract">{{ previewData.extract }}</div>
        </div>
      </div>
    </div>

    <!-- Victory / Loss / Freeplay Summary Modal -->
    <Teleport to="body">
      <transition name="fade">
        <div v-if="showEndModal" class="fixed inset-0 z-[60] flex items-center justify-center p-3 sm:p-4">
          <div class="absolute inset-0 bg-black/85 backdrop-blur-sm"></div>
          <div class="relative rounded-2xl max-w-[420px] w-full max-h-[90dvh] overflow-y-auto overscroll-contain animate-scale-in"
               :style="endModalStyle">

            <!-- Header -->
            <div class="px-5 pt-5 pb-3 text-center">
              <template v-if="game.isWon.value && !freeplayFinished">
                <div class="font-pixel text-[8px] text-crt-green/60 mb-1 tracking-[0.3em]">MISSION</div>
                <h2 class="font-pixel text-lg sm:text-xl text-neon-green mb-2.5">COMPLETE!</h2>
              </template>
              <template v-else-if="freeplayFinished">
                <div class="font-pixel text-[8px] text-crt-cyan/60 mb-1 tracking-[0.3em]">SESSION</div>
                <h2 class="font-pixel text-lg sm:text-xl text-neon-cyan mb-2.5">SUMMARY</h2>
              </template>
              <template v-else>
                <div class="font-pixel text-[8px] text-crt-red/60 mb-1 tracking-[0.3em]">GAME</div>
                <h2 class="font-pixel text-lg sm:text-xl mb-2.5"
                    style="color: #ff4444; text-shadow: 0 0 15px rgba(255,68,68,0.5), 0 0 40px rgba(255,68,68,0.2);">OVER</h2>
              </template>

              <!-- Route -->
              <div v-if="!freeplayFinished" class="flex items-center justify-center gap-2 font-mono text-[11px] mb-1">
                <span class="text-crt-white truncate max-w-[140px] wiki-point">
                  {{ game.state.startArticle?.title }}
                  <span class="wiki-point-full">{{ game.state.startArticle?.title }}</span>
                </span>
                <svg class="w-3.5 h-3.5 shrink-0" :class="game.isWon.value ? 'text-crt-green' : 'text-crt-red'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                </svg>
                <span class="truncate max-w-[140px] wiki-point" :class="game.isWon.value ? 'text-crt-green' : 'text-crt-magenta'">
                  {{ game.state.targetArticle?.title }}
                  <span class="wiki-point-full">{{ game.state.targetArticle?.title }}</span>
                </span>
              </div>
              <p v-if="!game.isWon.value && !freeplayFinished" class="font-mono text-[10px] text-retro-muted/60">
                {{ game.state.effectiveTimeLimit ? 'Time ran out!' : 'No more clicks!' }}
              </p>
              <p v-if="freeplayFinished" class="font-mono text-[11px] text-retro-muted">
                You explored <span class="text-crt-green">{{ game.state.path.length }}</span> articles freely.
              </p>
            </div>

            <!-- Stats row -->
            <div class="px-5 pb-3">
              <div class="grid grid-cols-3 gap-2">
                <div class="rounded-lg py-2.5 px-1 text-center" style="background: #12131c; border: 1px solid rgba(57,255,20,0.1);">
                  <svg class="w-4 h-4 text-crt-green/40 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122" />
                  </svg>
                  <div class="font-terminal text-xl text-crt-green leading-none">{{ game.state.clicks }}</div>
                  <div class="font-mono text-[8px] text-retro-muted mt-1 tracking-wider">CLICKS</div>
                </div>
                <div class="rounded-lg py-2.5 px-1 text-center" style="background: #12131c; border: 1px solid rgba(255,191,0,0.1);">
                  <svg class="w-4 h-4 text-crt-amber/40 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <div class="font-terminal text-xl text-crt-amber leading-none">{{ game.formattedTime.value }}</div>
                  <div class="font-mono text-[8px] text-retro-muted mt-1 tracking-wider">TIME</div>
                </div>
                <div class="rounded-lg py-2.5 px-1 text-center" style="background: #12131c; border: 1px solid rgba(255,46,204,0.1);">
                  <svg class="w-4 h-4 text-crt-magenta/40 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                  <div class="font-terminal text-xl text-crt-magenta leading-none">{{ game.state.path.length }}</div>
                  <div class="font-mono text-[8px] text-retro-muted mt-1 tracking-wider">PAGES</div>
                </div>
              </div>
            </div>

            <!-- Details section -->
            <div class="px-5 pb-3 space-y-2">

              <!-- XP + Details combined -->
              <div v-if="xpRewardData && xpRewardData.totalReward > 0"
                   class="flex items-center justify-between rounded-lg px-3 py-2.5"
                   style="background: rgba(57,255,20,0.03); border: 1px solid rgba(57,255,20,0.1);">
                <div class="flex items-center gap-2">
                  <svg class="w-4 h-4 text-crt-green/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                  </svg>
                  <span class="font-pixel text-[7px] text-crt-green/70 tracking-wider">XP EARNED</span>
                </div>
                <span class="font-terminal text-base text-crt-green">+{{ xpRewardData.totalReward }}</span>
              </div>

              <!-- Game meta chips -->
              <div class="flex flex-wrap items-center gap-1.5">
                <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md font-mono text-[10px]"
                      style="background: #12131c; border: 1px solid #252738;">
                  <svg class="w-3 h-3 text-crt-amber/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                  </svg>
                  <span class="text-retro-muted">Hints</span>
                  <span :class="game.state.hintsUsed > 0 ? 'text-crt-amber' : 'text-crt-green'">{{ game.state.hintsUsed }}</span>
                </span>
                <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md font-mono text-[10px]"
                      style="background: #12131c; border: 1px solid #252738;">
                  <svg class="w-3 h-3 text-crt-cyan/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                  </svg>
                  <span class="text-retro-muted">Combo</span>
                  <span class="text-crt-cyan">{{ game.state.combo }}x</span>
                </span>
                <span v-for="modId in game.state.modifiers" :key="modId"
                      class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md font-mono text-[10px]"
                      style="background: rgba(255,191,0,0.04); border: 1px solid rgba(255,191,0,0.15);">
                  <svg class="w-3 h-3 text-crt-amber" fill="none" stroke="currentColor" viewBox="0 0 24 24" v-html="modifierSvgPath(modId)"></svg>
                  <span class="text-crt-amber">{{ game.MODIFIERS[modId]?.name }}</span>
                </span>
              </div>

              <!-- Path (compact horizontal) -->
              <div class="rounded-lg px-3 py-2" style="background: #12131c; border: 1px solid #252738;">
                <div class="flex items-center gap-1 overflow-x-auto pb-0.5 font-mono text-[10px]">
                  <template v-for="(article, idx) in game.state.path" :key="idx">
                    <span v-if="idx > 0" class="text-retro-border/40 shrink-0 text-[8px]">&rsaquo;</span>
                    <span class="shrink-0 whitespace-nowrap px-1 py-0.5 rounded"
                          :class="idx === 0 ? 'text-crt-white' : idx === game.state.path.length - 1 ? (game.isWon.value ? 'text-crt-green' : 'text-crt-red') : 'text-retro-muted'"
                          :style="idx === game.state.path.length - 1 && game.isWon.value ? 'background: rgba(57,255,20,0.06);' : ''">
                      {{ article.replace(/_/g, ' ') }}
                    </span>
                  </template>
                </div>
              </div>

              <!-- Daily leaderboard preview -->
              <div v-if="props.mode === 'daily' && dailyLeaderboard.length" class="rounded-lg px-3 py-2" style="background: #12131c; border: 1px solid #252738;">
                <div class="font-pixel text-[6px] text-crt-amber tracking-[0.15em] mb-1.5">DAILY LEADERBOARD</div>
                <div class="space-y-0.5 font-mono text-[11px]">
                  <div v-for="entry in dailyLeaderboard.slice(0, 3)" :key="entry.rank" class="flex items-center gap-2">
                    <span class="text-retro-muted w-3 text-right shrink-0">#{{ entry.rank }}</span>
                    <span class="text-crt-white flex-1 truncate">{{ entry.username }}</span>
                    <span class="text-crt-green shrink-0">{{ entry.clicks }}</span>
                    <span class="text-retro-muted shrink-0">{{ formatLeaderboardTime(entry.time) }}</span>
                  </div>
                </div>
              </div>

              <!-- Match result -->
              <div v-if="matchResult" class="rounded-lg px-3 py-2" style="background: #12131c; border: 1px solid rgba(180,76,255,0.2);">
                <div v-if="matchResult.status === 'finished'">
                  <div class="text-center mb-1.5">
                    <span class="font-pixel text-[8px]" :class="matchResult.winner === 'you' ? 'text-crt-green' : matchResult.winner === 'tie' ? 'text-crt-amber' : 'text-crt-red'">
                      {{ matchResult.winner === 'you' ? 'YOU WIN!' : matchResult.winner === 'tie' ? 'TIE!' : 'OPPONENT WINS' }}
                    </span>
                  </div>
                  <div class="grid grid-cols-2 gap-2 text-center">
                    <div class="rounded px-2 py-1.5" style="background: rgba(57,255,20,0.03); border: 1px solid rgba(57,255,20,0.1);">
                      <div class="font-pixel text-[6px] text-crt-green/60">YOU</div>
                      <div v-if="matchResult.you?.username" class="font-mono text-[9px] text-retro-muted truncate mb-0.5">{{ matchResult.you.username }}</div>
                      <div class="font-terminal text-base text-crt-white">{{ matchResult.you?.clicks }} <span class="text-retro-muted text-xs">/ {{ formatLeaderboardTime(matchResult.you?.time || 0) }}</span></div>
                    </div>
                    <div class="rounded px-2 py-1.5" style="background: rgba(180,76,255,0.03); border: 1px solid rgba(180,76,255,0.1);">
                      <div class="font-pixel text-[6px] text-arcade-purple/60">OPP</div>
                      <div v-if="matchResult.opponent?.username" class="font-mono text-[9px] text-arcade-purple/70 truncate mb-0.5">{{ matchResult.opponent.username }}</div>
                      <div class="font-terminal text-base text-crt-white">{{ matchResult.opponent?.clicks }} <span class="text-retro-muted text-xs">/ {{ formatLeaderboardTime(matchResult.opponent?.time || 0) }}</span></div>
                    </div>
                  </div>
                </div>
                <div v-else class="text-center py-1">
                  <span class="font-mono text-[11px] text-retro-muted animate-blink">Waiting for {{ matchResult.opponent?.username || 'opponent' }}...</span>
                  <span class="font-terminal text-sm text-arcade-purple ml-2">{{ matchResult.code }}</span>
                </div>
              </div>

              <!-- Group lobby result -->
              <div v-if="lobbyResult" class="rounded-lg px-3 py-2" style="background: #12131c; border: 1px solid rgba(0,229,255,0.2);">
                <template v-if="lobbyResult.status === 'finished' && lobbyResult.leaderboard?.length">
                  <div class="flex items-center justify-between gap-2 mb-1.5">
                    <div class="font-pixel text-[6px] text-crt-cyan tracking-[0.15em]">GROUP LEADERBOARD</div>
                    <span v-if="lobbyYourRank != null" class="font-mono text-[9px] text-crt-green">You: #{{ lobbyYourRank }}</span>
                  </div>
                  <div class="space-y-0.5 font-mono text-[11px] max-h-[220px] overflow-y-auto">
                    <div v-for="row in lobbyResult.leaderboard" :key="row.user_id"
                         class="flex items-center gap-2 py-1 rounded px-1"
                         :class="{ 'bg-crt-green/[0.04] border-l-2 border-l-crt-green': auth.user.value && row.user_id === auth.user.value.id }">
                      <span class="text-retro-muted w-7 text-right shrink-0 flex items-center justify-end">
                        <template v-if="row.rank === 1">
                          <svg class="w-4 h-4 text-arcade-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                          </svg>
                        </template>
                        <template v-else-if="row.rank === 2">
                          <svg class="w-4 h-4 text-retro-light" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                          </svg>
                        </template>
                        <template v-else-if="row.rank === 3">
                          <svg class="w-4 h-4 text-arcade-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                          </svg>
                        </template>
                        <span v-else class="font-terminal text-sm text-retro-muted">#{{ row.rank }}</span>
                      </span>
                      <span class="flex-1 truncate" :class="auth.user.value && row.user_id === auth.user.value.id ? 'text-crt-green' : 'text-crt-white'">{{ row.username }}</span>
                      <span class="text-crt-green shrink-0 tabular-nums">{{ row.clicks }}</span>
                      <span class="text-retro-muted shrink-0 tabular-nums w-12 text-right">{{ formatLeaderboardTime(row.time) }}</span>
                    </div>
                  </div>
                </template>
                <div v-else class="text-center py-1">
                  <span class="font-mono text-[11px] text-retro-muted animate-blink">Waiting for other players...</span>
                  <span class="font-terminal text-sm text-crt-cyan ml-2">{{ lobbyResult.code }}</span>
                </div>
              </div>
            </div>

            <!-- Bottom actions -->
            <div class="px-5 pb-5 pt-2">
              <div v-if="!freeplayFinished && game.state.targetArticle" class="flex gap-2 mb-2.5">
                <button @click="copyShareCard" class="btn-retro-ghost flex-1 flex items-center justify-center gap-1.5 !py-1.5 !text-[9px] !px-2">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                  </svg>
                  SHARE
                </button>
                <button @click="shareChallenge" class="btn-retro-ghost flex-1 flex items-center justify-center gap-1.5 !py-1.5 !text-[9px] !px-2">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                  </svg>
                  CHALLENGE
                </button>
              </div>
              <div class="flex gap-2.5 pb-[env(safe-area-inset-bottom)]">
                <button @click="goHome" class="btn-secondary flex-1 !py-3 sm:!py-2.5 !text-sm touch-manipulation">HOME</button>
                <button @click="playAgain" class="btn-primary flex-1 !py-3 sm:!py-2.5 !text-sm touch-manipulation">PLAY AGAIN</button>
              </div>
            </div>
          </div>

          <!-- Confetti -->
          <div v-if="game.isWon.value && !freeplayFinished && showConfetti" class="confetti-container" aria-hidden="true">
            <div v-for="i in 40" :key="i" class="confetti-piece" :style="confettiStyle(i)"></div>
          </div>
        </div>
      </transition>
    </Teleport>

    <!-- Combo counter -->
    <transition name="fade">
      <div v-if="comboDisplay" :key="comboDisplay.key" class="combo-float"
           style="right: 120px; top: 60px;"
           :style="{ color: comboDisplay.count >= 5 ? '#ff2ecc' : comboDisplay.count >= 3 ? '#ffbf00' : '#00e5ff' }">
        COMBO x{{ comboDisplay.count }}
      </div>
    </transition>

    <!-- Red vignette on loss -->
    <div v-if="showRedVignette" class="red-vignette"></div>

    <!-- Level up overlay -->
    <transition name="fade">
      <div v-if="showLevelUp" class="level-up-overlay">
        <div class="text-center animate-scale-in">
          <div class="w-14 h-14 rounded-xl mx-auto mb-3 flex items-center justify-center" style="background: rgba(57,255,20,0.1); border: 1px solid rgba(57,255,20,0.25); box-shadow: 0 0 30px rgba(57,255,20,0.15);">
            <svg class="w-7 h-7 text-crt-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
            </svg>
          </div>
          <div class="font-pixel text-[9px] text-crt-green tracking-[0.3em] mb-2">LEVEL UP!</div>
          <div class="font-pixel text-4xl text-neon-green mb-1">{{ progression.level.value }}</div>
          <div class="font-mono text-sm text-retro-light">Keep playing to unlock more</div>
        </div>
      </div>
    </transition>

    <!-- Achievement unlock toasts -->
    <TransitionGroup name="fade" tag="div" class="fixed top-4 left-1/2 -translate-x-1/2 z-[75] flex flex-col gap-2">
      <div v-for="a in achievementToasts" :key="a.key" class="achievement-toast">
        <div class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0" style="background: rgba(255,215,0,0.1); border: 1px solid rgba(255,215,0,0.25);">
          <svg class="w-5 h-5 text-arcade-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
          </svg>
        </div>
        <div>
          <div class="font-pixel text-[7px] text-arcade-gold tracking-wider">ACHIEVEMENT UNLOCKED</div>
          <div class="font-mono text-[12px] text-crt-white">{{ a.name }}</div>
          <div class="font-mono text-[9px] text-crt-green">+{{ a.xp }} XP</div>
        </div>
      </div>
    </TransitionGroup>

    <!-- Quit Confirm Modal -->
    <Teleport to="body">
      <transition name="fade">
        <div v-if="showQuitConfirm" class="fixed inset-0 z-[60] flex items-center justify-center p-3 sm:p-4">
          <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" @click="showQuitConfirm = false"></div>
          <div class="relative rounded-2xl p-6 max-w-sm w-full animate-scale-in text-center"
               style="background: #0d0e15; border: 1.5px solid rgba(255,191,0,0.25); box-shadow: 0 0 40px rgba(255,191,0,0.08), 0 12px 40px rgba(0,0,0,0.6);">
            <div class="inline-flex items-center gap-2 mb-3 px-3 py-1.5 rounded-lg" style="background: rgba(255,191,0,0.06); border: 1px solid rgba(255,191,0,0.12);">
              <svg class="w-4 h-4 text-crt-amber" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
              </svg>
              <span class="font-pixel text-[8px] text-crt-amber tracking-[0.2em]">WARNING</span>
            </div>
            <p class="font-mono text-sm text-retro-light mb-6">Abort current mission?<br><span class="text-retro-muted text-xs mt-1 inline-block">Your progress will be lost.</span></p>
            <div class="flex gap-3 pb-[env(safe-area-inset-bottom)]">
              <button @click="showQuitConfirm = false" class="btn-secondary flex-1 touch-manipulation">CANCEL</button>
              <button @click="goHome" class="btn-primary flex-1 touch-manipulation" style="background: linear-gradient(180deg, #ff6644, #ff4444); border-color: #ff4444; box-shadow: 0 0 15px rgba(255,68,68,0.3), 0 3px 0 #aa2211;">
                QUIT
              </button>
            </div>
          </div>
        </div>
      </transition>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useWikipedia } from '../composables/useWikipedia'
import { useGame } from '../composables/useGame'
import { useSound } from '../composables/useSound'
import { useToast } from '../composables/useToast'
import { useAuth } from '../composables/useAuth'
import { useApi } from '../composables/useApi'
import { useTrending } from '../composables/useTrending'
import { useProgression } from '../composables/useProgression'
import { useAchievements } from '../composables/useAchievements'

const props = defineProps({
  mode: { type: String, required: true }
})

const router = useRouter()
const route = useRoute()
const wiki = useWikipedia()
const game = useGame()
const sound = useSound()
const toast = useToast()

const genreId = computed(() => route.query.genre || 'random')
const genre = computed(() => game.GENRES[genreId.value] || game.GENRES.random)
const difficulty = computed(() => route.query.difficulty || 'normal')

const customLimitsFromRoute = computed(() => ({
  timeLimit: route.query.timeLimit != null && route.query.timeLimit !== ''
    ? Number(route.query.timeLimit)
    : undefined,
  clickLimit: route.query.clickLimit != null && route.query.clickLimit !== ''
    ? Number(route.query.clickLimit)
    : undefined,
}))

const articleData = ref(null)
const contentRef = ref(null)
const initialLoading = ref(true)
const showQuitConfirm = ref(false)
const freeplayFinished = ref(false)
const validLinks = ref(new Set())
const bootStep = ref(0)

// Preview tooltip state
const previewData = ref(null)
const previewPos = ref(null)
let hoverTimer = null
let hoveredLink = null
let previewCache = {}

const auth = useAuth()
const api = useApi()
const trending = useTrending()
const progression = useProgression()
const achievements = useAchievements()

const rerolling = ref(false)
const showConfetti = ref(false)
const dailyLeaderboard = ref([])
const matchCode = computed(() => route.query.match || null)
const lobbyCode = computed(() => route.query.lobby || null)
const matchResult = ref(null)
const matchPolling = ref(false)
let matchPollTimerId = null
const lobbyResult = ref(null)
const lobbyPolling = ref(false)
let lobbyPollTimerId = null

const lobbyYourRank = computed(() => {
  const lb = lobbyResult.value?.leaderboard
  const uid = auth.user.value?.id
  if (!lb || uid == null) return null
  const row = lb.find(r => r.user_id === uid)
  return row ? row.rank : null
})
const showScreenShake = ref(false)
const showRedVignette = ref(false)
const comboDisplay = ref(null)
const xpRewardData = ref(null)
const achievementToasts = ref([])
const showLevelUp = ref(false)
const showHintMenu = ref(false)
const hintResult = ref(null)
const targetCategoriesCache = ref(null)
const activeModifiers = computed(() => {
  const modParam = route.query.modifiers
  if (!modParam) return []
  const valid = modParam.split(',').filter(id => id && game.MODIFIERS[id])
  if (valid.length !== modParam.split(',').filter(Boolean).length) {
    toast.error('Invalid modifier detected — removed unknown modifiers')
  }
  return valid
})

const currentMode = computed(() => game.currentMode.value)

const MODIFIER_SVG_PATHS = {
  fog: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />',
  blackout: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />',
  noback: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />',
  speedDecay: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />',
}
function modifierSvgPath(modId) {
  return MODIFIER_SVG_PATHS[modId] || MODIFIER_SVG_PATHS.fog
}

const hintTemperature = computed(() => {
  if (!hintResult.value || hintResult.value.type !== 'hotcold') return 0
  const text = hintResult.value.text.toLowerCase()
  if (text.includes('burning')) return 100
  if (text.includes('hot')) return 75
  if (text.includes('warm')) return 55
  if (text.includes('lukewarm')) return 35
  if (text.includes('cold')) return 10
  if (text.includes('checking')) return 50
  return 50
})

const hintBannerTextClass = computed(() => {
  if (!hintResult.value) return ''
  if (hintResult.value.type === 'category') return 'text-crt-cyan'
  const text = hintResult.value.text.toLowerCase()
  if (text.includes('burning')) return 'text-crt-red'
  if (text.includes('hot')) return 'text-crt-amber'
  if (text.includes('warm')) return 'text-crt-amber/80'
  if (text.includes('lukewarm')) return 'text-retro-light'
  if (text.includes('cold')) return 'text-crt-cyan'
  return 'text-retro-light'
})

const canReroll = computed(() => {
  const m = props.mode
  return m !== 'daily' && m !== 'custom' && m !== 'freeplay' && m !== 'trending'
})

const showEndModal = computed(() => game.isGameOver.value || freeplayFinished.value)

const endModalStyle = computed(() => {
  if (freeplayFinished.value) return 'background: #0d0e15; border: 2px solid rgba(0,229,255,0.4); box-shadow: 0 0 40px rgba(0,229,255,0.15);'
  return game.isWon.value
    ? 'background: #0d0e15; border: 2px solid rgba(57,255,20,0.4); box-shadow: 0 0 40px rgba(57,255,20,0.15), 0 0 80px rgba(57,255,20,0.05);'
    : 'background: #0d0e15; border: 2px solid rgba(255,68,68,0.4); box-shadow: 0 0 40px rgba(255,68,68,0.15), 0 0 80px rgba(255,68,68,0.05);'
})

const displayPath = computed(() => {
  const path = game.state.path
  if (!path.length) return []

  return path.map((a, i) => ({
    type: 'article',
    label: a.replace(/_/g, ' '),
    active: i === path.length - 1,
  }))
})

const clickLimitClass = computed(() => {
  if (!game.state.effectiveClickLimit) return 'text-crt-white'
  const ratio = game.state.clicks / game.state.effectiveClickLimit
  if (ratio >= 0.8) return 'text-crt-red'
  if (ratio >= 0.5) return 'text-crt-amber'
  return 'text-crt-white'
})

const timerClass = computed(() => {
  if (game.state.timeRemaining === null) return ''
  if (game.state.timeRemaining <= 5) return 'text-crt-red timer-critical'
  if (game.state.timeRemaining <= 15) return 'text-crt-red timer-urgent'
  if (game.state.timeRemaining <= 30) return 'text-crt-amber'
  return 'text-crt-white'
})

// Play sounds on game state changes; submit daily score; show confetti; award XP; check achievements
watch(() => game.state.status, (val) => {
  if (val === 'won') {
    sound.playWin()
    showConfetti.value = true
    setTimeout(() => showConfetti.value = false, 3500)
    submitDailyScore()
    submitMatchResult()
    submitLobbyResult()
    loadDailyLeaderboard()
    processPostGame(true)
  } else if (val === 'lost') {
    sound.playLose()
    showScreenShake.value = true
    showRedVignette.value = true
    setTimeout(() => { showScreenShake.value = false; showRedVignette.value = false }, 600)
    submitMatchResult()
    submitLobbyResult()
    loadDailyLeaderboard()
    processPostGame(false)
  }
})

watch(() => game.state.combo, (val) => {
  if (val >= 2) {
    sound.playCombo(val)
    comboDisplay.value = { count: val, key: Date.now() }
    setTimeout(() => { if (comboDisplay.value?.count === val) comboDisplay.value = null }, 1000)
  }
})

function processPostGame(won) {
  const stats = game.getStats()

  const xpResult = progression.awardXp({
    won,
    mode: game.state.mode,
    difficulty: game.state.difficulty,
    clicks: game.state.clicks,
    elapsed: game.state.elapsed,
    streak: auth.streak?.value || 0,
    hintsUsed: game.state.hintsUsed,
    modifiers: game.state.modifiers,
  })
  xpRewardData.value = xpResult

  if (xpResult.leveledUp) {
    showLevelUp.value = true
    sound.playLevelUp()
    setTimeout(() => showLevelUp.value = false, 2500)
  }

  const dailyData = game.getDailyStatus()
  let dailyCompletions = 0
  try {
    const allDaily = JSON.parse(localStorage.getItem('wikilink_daily') || '{}')
    dailyCompletions = Object.values(allDaily).filter(d => d.completed).length
  } catch { /* ignore */ }

  const newUnlocks = achievements.checkAchievements({
    won,
    mode: game.state.mode,
    clicks: game.state.clicks,
    elapsed: game.state.elapsed,
    timeRemaining: game.state.timeRemaining,
    stats,
    hintsUsed: game.state.hintsUsed,
    modifiers: game.state.modifiers,
    dailyCompletions,
  })

  if (newUnlocks.length > 0) {
    const unlocked = achievements.consumeRecentUnlocks()
    unlocked.forEach((a, i) => {
      setTimeout(() => {
        sound.playAchievement()
        achievementToasts.value.push({ ...a, key: Date.now() })
        setTimeout(() => achievementToasts.value.shift(), 3500)
      }, i * 800)
    })
  }
}

function buildProcessedHtml(data) {
  if (!data?.html) return { html: '', allowedTitles: new Set() }
  const parser = new DOMParser()
  const doc = parser.parseFromString(data.html, 'text/html')

  doc.querySelectorAll(
    '.mw-editsection, .reference, .reflist, .navbox, .sistersitebox, ' +
    '.mw-empty-elt, .noprint, .mw-authority-control, .catlinks, ' +
    '#coordinates, .metadata, .ambox, .dmbox, .tmbox, .mbox-small, ' +
    '.portal, .wikitable.collapsible, .sidebar-collapse, .mw-references-wrap'
  ).forEach(el => el.remove())

  const links = doc.querySelectorAll('a[href]')
  const allowedTitles = new Set(data.links || [])

  links.forEach(link => {
    const href = link.getAttribute('href')
    if (href && href.startsWith('/wiki/')) {
      const title = decodeURIComponent(href.replace('/wiki/', '').replace(/_/g, ' '))
      if (allowedTitles.has(title) || allowedTitles.has(title.replace(/ /g, '_'))) {
        link.setAttribute('data-wiki-title', title)
        link.setAttribute('href', 'javascript:void(0)')
        link.removeAttribute('title')
        link.classList.add('wiki-link')
      } else {
        link.removeAttribute('href')
        link.removeAttribute('title')
        link.style.color = '#555770'
        link.style.textDecoration = 'none'
        link.style.cursor = 'default'
      }
    } else if (href && (href.startsWith('http') || href.startsWith('//'))) {
      link.removeAttribute('href')
      link.removeAttribute('title')
      link.style.color = '#555770'
      link.style.textDecoration = 'none'
      link.style.cursor = 'default'
    }
  })

  if (game.state.modifiers.includes('blackout')) {
    const wikiLinks = doc.querySelectorAll('.wiki-link')
    const seed = game.state.clicks + game.state.path.length
    wikiLinks.forEach((link, idx) => {
      const hash = (idx * 2654435761 + seed * 131) >>> 0
      if ((hash % 100) < 30) {
        link.classList.add('blackout-disabled')
        link.removeAttribute('data-wiki-title')
      }
    })
  }

  return { html: doc.body.innerHTML, allowedTitles }
}

const processedResult = computed(() => buildProcessedHtml(articleData.value))
const processedHtml = computed(() => processedResult.value.html)

watch(processedResult, (result) => {
  validLinks.value = result.allowedTitles
})

async function initializeGame() {
  initialLoading.value = true
  freeplayFinished.value = false
  bootStep.value = 0
  previewCache = {}
  targetCategoriesCache.value = null

  setTimeout(() => bootStep.value = 1, 300)
  setTimeout(() => bootStep.value = 2, 800)
  setTimeout(() => bootStep.value = 3, 1300)

  try {
    if (props.mode === 'daily') {
      let pair = null
      try {
        const serverDaily = await api.get('/daily')
        if (serverDaily.start && serverDaily.end) {
          pair = { start: serverDaily.start, end: serverDaily.end }
        }
      } catch { /* fall through */ }
      if (!pair) {
        pair = await wiki.getDailyPair()
        if (pair) {
          try { await api.post('/daily', { start: pair.start, end: pair.end }) } catch { /* ignore */ }
        }
      }
      if (!pair) { toast.error('Failed to load daily challenge'); router.push({ name: 'home' }); return }
      game.initGame('daily', pair.start, pair.end, 'random', 'normal')
      await loadArticle(pair.start.title)
    } else if (props.mode === 'custom') {
      const codeQ = typeof route.query.code === 'string' ? route.query.code.trim() : ''
      let fromQ = typeof route.query.from === 'string' ? route.query.from.trim() : ''
      let toQ = typeof route.query.to === 'string' ? route.query.to.trim() : ''
      if (codeQ && (!fromQ || !toQ)) {
        try {
          const ch = await api.get(`/challenge/${codeQ}`)
          fromQ = ch.start_title || fromQ
          toQ = ch.end_title || toQ
        } catch {
          toast.error('Challenge not found')
          router.push({ name: 'home' })
          return
        }
      }
      if (!fromQ || !toQ) { toast.error('Custom game needs start and target articles'); router.push({ name: 'home' }); return }
      if (fromQ.toLowerCase() === toQ.toLowerCase()) { toast.error('Start and target must be different articles'); router.push({ name: 'home' }); return }
      const [startArticle, targetArticle] = await Promise.all([
        wiki.getArticleSummary(fromQ),
        wiki.getArticleSummary(toQ),
      ])
      if (!startArticle || !targetArticle) { toast.error('Could not find one or both articles. Check the titles and try again.'); router.push({ name: 'home' }); return }
      game.initGame('custom', startArticle, targetArticle, 'random', 'normal')
      await loadArticle(startArticle.title)
    } else if (props.mode === 'trending') {
      const pair = await trending.getTrendingPair()
      if (!pair) {
        toast.warn('Trending data unavailable, falling back to random')
        const fallback = await wiki.getRandomPairByGenre(genre.value)
        if (!fallback) { toast.error('Could not find article pair'); router.push({ name: 'home' }); return }
        game.initGame('trending', fallback.start, fallback.end, 'random', 'normal', {}, activeModifiers.value)
        await loadArticle(fallback.start.title)
      } else {
        game.initGame('trending', pair.start, pair.end, 'random', 'normal', {}, activeModifiers.value)
        await loadArticle(pair.start.title)
      }
    } else if (game.GAME_MODES[props.mode]?.noTarget) {
      const startArticle = await wiki.getRandomArticleByGenre(genre.value)
      if (!startArticle) { toast.error('Failed to load article'); router.push({ name: 'home' }); return }
      game.initGame(props.mode, startArticle, null, genreId.value, difficulty.value, customLimitsFromRoute.value, activeModifiers.value)
      await loadArticle(startArticle.title)
    } else {
      const pair = await wiki.getRandomPairByGenre(genre.value)
      if (!pair) { toast.error('Could not find article pair'); router.push({ name: 'home' }); return }
      game.initGame(props.mode, pair.start, pair.end, genreId.value, difficulty.value, customLimitsFromRoute.value, activeModifiers.value)
      await loadArticle(pair.start.title)
    }

    sound.playStart()
  } finally {
    initialLoading.value = false
  }
}

async function loadArticle(title) {
  const data = await wiki.getArticleContent(title)
  if (data) {
    articleData.value = data
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }
}

function dismissPreview() {
  clearTimeout(hoverTimer)
  hoveredLink = null
  previewData.value = null
  previewPos.value = null
}

function handleLinkClick(event) {
  dismissPreview()
  showHintMenu.value = false
  const link = event.target.closest('[data-wiki-title]')
  if (!link || game.isGameOver.value) return

  const title = link.getAttribute('data-wiki-title')
  if (!title) return

  const normalizedTitle = title.replace(/ /g, '_')
  const isValid = validLinks.value.has(title) || validLinks.value.has(normalizedTitle)
    || validLinks.value.has(title.replace(/_/g, ' '))
  if (!isValid) {
    toast.error('Invalid link — this page is not reachable from here')
    return
  }

  sound.playNavigate()
  const gameOver = game.navigateTo(title)
  if (!gameOver || game.isWon.value) {
    loadArticle(title)
  }
}

function handleLinkHover(event) {
  const link = event.target.closest('[data-wiki-title]')
  if (!link || link === hoveredLink) return

  hoveredLink = link
  clearTimeout(hoverTimer)
  previewData.value = null
  previewPos.value = null

  const title = link.getAttribute('data-wiki-title')
  if (!title) return

  hoverTimer = setTimeout(async () => {
    if (hoveredLink !== link) return

    const rect = link.getBoundingClientRect()
    const x = Math.min(rect.left, window.innerWidth - 340)
    const y = rect.bottom + 8
    const pos = { x: Math.max(8, x), y: Math.min(y, window.innerHeight - 140) }

    if (previewCache[title]) {
      previewData.value = previewCache[title]
      previewPos.value = pos
      return
    }

    const summary = await wiki.getArticleSummary(title)
    if (summary && hoveredLink === link) {
      previewCache[title] = summary
      previewData.value = summary
      previewPos.value = pos
    }
  }, 500)
}

function handleLinkHoverEnd(event) {
  const related = event.relatedTarget
  if (related && hoveredLink && hoveredLink.contains(related)) return
  dismissPreview()
}

async function handleGoBack() {
  if (game.backDisabled.value) {
    const reason = game.state.modifiers.includes('noback')
      ? 'No Back modifier is active!'
      : `Back is disabled in ${game.GAME_MODES[game.state.mode]?.name || 'this'} mode!`
    toast.warn(reason)
    return
  }
  const title = game.goBack()
  if (title) {
    sound.playBack()
    toast.info('Went back one page')
    await loadArticle(title)
  }
}

async function fetchArticleCategories(title) {
  try {
    const params = new URLSearchParams({
      action: 'query', titles: title, prop: 'categories',
      cllimit: '50', clshow: '!hidden',
      format: 'json', origin: '*',
    })
    const res = await fetch(`https://en.wikipedia.org/w/api.php?${params}`)
    if (!res.ok) return []
    const data = await res.json()
    const page = Object.values(data.query?.pages || {})[0]
    return (page?.categories || []).map(c => c.title.replace('Category:', ''))
  } catch {
    return []
  }
}

async function useHintAction(type) {
  if (!game.useHint()) return
  sound.playHint()
  showHintMenu.value = false

  if (type === 'category' && game.state.targetArticle) {
    hintResult.value = { type: 'category', text: `Target category: ${game.state.targetArticle.description || 'Unknown'}` }
  } else if (type === 'hotcold') {
    const targetTitle = game.state.targetArticle?.title
    if (!targetTitle) return

    const currentLinks = articleData.value?.links || []
    const targetNorm = targetTitle.replace(/_/g, ' ').toLowerCase()

    if (currentLinks.some(l => l.replace(/_/g, ' ').toLowerCase() === targetNorm)) {
      hintResult.value = { type: 'hotcold', text: 'BURNING HOT! The target is linked on this page!' }
    } else {
      hintResult.value = { type: 'hotcold', text: 'Checking temperature...' }
      try {
        if (!targetCategoriesCache.value) {
          targetCategoriesCache.value = await fetchArticleCategories(targetTitle)
        }
        const currentCats = await fetchArticleCategories(game.state.currentArticle)
        const targetSet = new Set(targetCategoriesCache.value.map(c => c.toLowerCase()))
        const overlap = currentCats.filter(c => targetSet.has(c.toLowerCase())).length

        if (overlap >= 5) hintResult.value = { type: 'hotcold', text: `Hot! You share ${overlap} categories with the target.` }
        else if (overlap >= 3) hintResult.value = { type: 'hotcold', text: `Warm. You share ${overlap} categories with the target.` }
        else if (overlap >= 1) hintResult.value = { type: 'hotcold', text: `Lukewarm. You share ${overlap} categor${overlap === 1 ? 'y' : 'ies'} with the target.` }
        else hintResult.value = { type: 'hotcold', text: 'Cold. No shared categories with the target.' }
      } catch {
        hintResult.value = { type: 'hotcold', text: 'Could not determine temperature.' }
      }
    }
  }
  setTimeout(() => hintResult.value = null, 8000)
}

function generateShareText(challengeUrl) {
  const mode = game.GAME_MODES[game.state.mode]?.name || game.state.mode
  const won = game.isWon.value
  const clicks = game.state.clicks
  const time = game.formattedTime.value
  const path = game.state.path
  const start = game.state.startArticle?.title || path[0]?.replace(/_/g, ' ') || '?'
  const target = game.state.targetArticle?.title || '?'

  const header = won
    ? `[+] WikiLink - ${mode} WIN`
    : `[x] WikiLink - ${mode}`

  const route = `${start} -> ${target}`

  const stats = [
    `${clicks} click${clicks !== 1 ? 's' : ''}`,
    time,
    `${path.length} page${path.length !== 1 ? 's' : ''}`,
  ].join('  |  ')

  const pathSteps = path.map((a, i) => {
    const name = a.replace(/_/g, ' ')
    if (i === 0) return `> ${name}`
    if (i === path.length - 1) return `${won ? '>> ' : 'x '}${name}`
    return `- ${name}`
  })
  const maxPathDisplay = 8
  let pathText
  if (pathSteps.length <= maxPathDisplay) {
    pathText = pathSteps.join(' -> ')
  } else {
    const head = pathSteps.slice(0, 3)
    const tail = pathSteps.slice(-2)
    pathText = [...head, `... ${pathSteps.length - 5} more ...`, ...tail].join(' -> ')
  }

  const link = challengeUrl || 'https://wikilink.fraksis.com'

  return [
    header,
    route,
    '',
    stats,
    '',
    pathText,
    '',
    `Can you beat me? ${link}`,
  ].join('\n')
}

async function copyShareCard() {
  const start = game.state.startArticle?.title
  const end = game.state.targetArticle?.title
  let challengeUrl = null

  if (start && end) {
    try {
      const data = await api.post('/challenge', { startTitle: start, endTitle: end })
      challengeUrl = `${window.location.origin}/play/custom?code=${data.code}`
    } catch {
      challengeUrl = `${window.location.origin}/play/custom?from=${encodeURIComponent(start)}&to=${encodeURIComponent(end)}`
    }
  }

  const text = generateShareText(challengeUrl)
  try {
    await navigator.clipboard.writeText(text)
    toast.success('Result copied to clipboard!')
  } catch {
    toast.warn('Could not copy to clipboard')
  }
}

async function shareChallenge() {
  const start = game.state.startArticle?.title
  const end = game.state.targetArticle?.title
  if (!start || !end) return
  try {
    const data = await api.post('/challenge', { startTitle: start, endTitle: end })
    const url = `${window.location.origin}/play/custom?code=${data.code}`
    await navigator.clipboard.writeText(url)
    toast.success('Challenge link copied!')
  } catch {
    const url = `${window.location.origin}/play/custom?from=${encodeURIComponent(start)}&to=${encodeURIComponent(end)}`
    await navigator.clipboard.writeText(url).catch(() => {})
    toast.success('Challenge link copied!')
  }
}

function finishFreeplay() {
  game.endFreeplay()
  freeplayFinished.value = true
}

const confettiColors = ['#39ff14', '#00e5ff', '#ffbf00', '#ff2ecc', '#4c9fff', '#ff6b2b']
function confettiStyle(i) {
  const color = confettiColors[i % confettiColors.length]
  const left = Math.random() * 100
  const delay = Math.random() * 0.5
  const size = 4 + Math.random() * 6
  const dur = 1.5 + Math.random() * 1.5
  return { '--c-color': color, left: `${left}%`, animationDelay: `${delay}s`, width: `${size}px`, height: `${size}px`, animationDuration: `${dur}s` }
}

function formatLeaderboardTime(seconds) {
  const m = Math.floor(seconds / 60)
  const s = seconds % 60
  return `${m}:${s.toString().padStart(2, '0')}`
}

async function submitMatchResult() {
  if (!matchCode.value || !auth.isLoggedIn()) return
  try {
    const result = await api.post(`/match/submit/${matchCode.value}`, {
      clicks: game.state.clicks,
      time: game.state.elapsed,
      path: [...game.state.path],
    })
    matchResult.value = result
    if (result.status !== 'finished') {
      matchPolling.value = true
      pollMatchResult()
    }
  } catch { /* ignore */ }
}

async function pollMatchResult() {
  if (!matchCode.value || !matchPolling.value) return
  try {
    const result = await api.get(`/match/${matchCode.value}`)
    matchResult.value = result
    if (result.status === 'finished') {
      matchPolling.value = false
      return
    }
  } catch { /* ignore */ }
  matchPollTimerId = setTimeout(pollMatchResult, 3000)
}

async function submitLobbyResult() {
  if (!lobbyCode.value || !auth.isLoggedIn()) return
  try {
    const result = await api.post(`/lobby/submit/${lobbyCode.value}`, {
      clicks: game.state.clicks,
      time: game.state.elapsed,
      path: [...game.state.path],
    })
    lobbyResult.value = result
    if (result.status !== 'finished') {
      lobbyPolling.value = true
      pollLobbyResult()
    }
  } catch { /* ignore */ }
}

async function pollLobbyResult() {
  if (!lobbyCode.value || !lobbyPolling.value) return
  try {
    const result = await api.get(`/lobby/${lobbyCode.value}`)
    lobbyResult.value = result
    if (result.status === 'finished') {
      lobbyPolling.value = false
      return
    }
  } catch { /* ignore */ }
  lobbyPollTimerId = setTimeout(pollLobbyResult, 3000)
}

async function submitDailyScore() {
  if (props.mode !== 'daily' || !auth.isLoggedIn()) return
  try {
    await api.post('/daily/score', {
      date: new Date().toISOString().slice(0, 10),
      clicks: game.state.clicks,
      time: game.state.elapsed,
      path: [...game.state.path],
    })
    auth.refreshStreak()
  } catch { /* ignore */ }
}

async function loadDailyLeaderboard() {
  if (props.mode !== 'daily') return
  try {
    const data = await api.get('/daily/leaderboard')
    dailyLeaderboard.value = data.scores || []
  } catch { /* ignore */ }
}

async function rerollPair() {
  if (!canReroll.value || rerolling.value || !game.isPlaying.value) return
  rerolling.value = true
  try {
    const pair = await wiki.getRandomPairByGenre(genre.value)
    if (!pair) { toast.error('Could not find a new pair'); return }
    game.initGame(props.mode, pair.start, pair.end, genreId.value, difficulty.value, customLimitsFromRoute.value, activeModifiers.value)
    previewCache = {}
    sound.playStart()
    await loadArticle(pair.start.title)
  } finally {
    rerolling.value = false
  }
}

function confirmQuit() {
  if (game.isPlaying.value) {
    showQuitConfirm.value = true
  } else {
    goHome()
  }
}

function goHome() {
  matchPolling.value = false
  if (matchPollTimerId) { clearTimeout(matchPollTimerId); matchPollTimerId = null }
  lobbyPolling.value = false
  if (lobbyPollTimerId) { clearTimeout(lobbyPollTimerId); lobbyPollTimerId = null }
  game.resetGame()
  router.push({ name: 'home' })
}

async function playAgain() {
  game.resetGame()
  freeplayFinished.value = false
  articleData.value = null
  matchResult.value = null
  matchPolling.value = false
  if (matchPollTimerId) { clearTimeout(matchPollTimerId); matchPollTimerId = null }
  lobbyResult.value = null
  lobbyPolling.value = false
  if (lobbyPollTimerId) { clearTimeout(lobbyPollTimerId); lobbyPollTimerId = null }
  const q = { ...route.query }
  if ('match' in q || 'lobby' in q) {
    delete q.match
    delete q.lobby
    await router.replace({ name: 'game', params: { mode: props.mode }, query: q })
  }
  await initializeGame()
}

function handleKeydown(e) {
  if (e.key === 'Escape') {
    if (showQuitConfirm.value) { showQuitConfirm.value = false; return }
    if (showEndModal.value) { goHome(); return }
    confirmQuit()
  }
  if (e.key === 'Backspace' && !showEndModal.value && !showQuitConfirm.value) {
    e.preventDefault()
    handleGoBack()
  }
  if (e.key === 'Enter' && showEndModal.value) playAgain()
  if ((e.key === 'r' || e.key === 'R') && !showEndModal.value && !showQuitConfirm.value) rerollPair()
}

onMounted(() => {
  if (!game.GAME_MODES[props.mode]) {
    toast.warn('Invalid game mode')
    router.push({ name: 'home' })
    return
  }
  window.addEventListener('keydown', handleKeydown)
  initializeGame()
})

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeydown)
  clearTimeout(hoverTimer)
  matchPolling.value = false
  if (matchPollTimerId) { clearTimeout(matchPollTimerId); matchPollTimerId = null }
  lobbyPolling.value = false
  if (lobbyPollTimerId) { clearTimeout(lobbyPollTimerId); lobbyPollTimerId = null }
  game.resetGame()
})
</script>
