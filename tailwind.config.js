/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{vue,js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        retro: {
          black: '#08090e',
          dark: '#0d0e15',
          panel: '#12131c',
          surface: '#181a25',
          border: '#252738',
          muted: '#555770',
          light: '#8888a8',
        },
        crt: {
          green: '#39ff14',
          amber: '#ffbf00',
          cyan: '#00e5ff',
          magenta: '#ff2ecc',
          red: '#ff4444',
          blue: '#4c9fff',
          white: '#d4d4e8',
          warm: '#ffa64d',
        },
        arcade: {
          gold: '#ffd700',
          orange: '#ff6b2b',
          pink: '#ff5caa',
          purple: '#b44cff',
          teal: '#00d4aa',
        }
      },
      fontFamily: {
        pixel: ['"Press Start 2P"', 'monospace'],
        terminal: ['VT323', 'monospace'],
        mono: ['"Space Mono"', 'monospace'],
      },
      animation: {
        'blink': 'blink 1s step-end infinite',
        'blink-slow': 'blink 2s step-end infinite',
        'glow-pulse': 'glowPulse 2.5s ease-in-out infinite',
        'glow-breathe': 'glowBreathe 4s ease-in-out infinite',
        'float': 'float 4s ease-in-out infinite',
        'slide-up': 'slideUp 0.5s cubic-bezier(0.22, 1, 0.36, 1)',
        'slide-up-delayed': 'slideUp 0.5s cubic-bezier(0.22, 1, 0.36, 1) 0.1s both',
        'slide-up-delayed-2': 'slideUp 0.5s cubic-bezier(0.22, 1, 0.36, 1) 0.2s both',
        'scale-in': 'scaleIn 0.35s cubic-bezier(0.22, 1, 0.36, 1)',
        'fade-in': 'fadeIn 0.5s ease-out',
        'fade-in-slow': 'fadeIn 1s ease-out',
        'crt-on': 'crtOn 0.4s ease-out',
        'flicker-in': 'flickerIn 0.3s ease-out',
        'typewriter': 'typewriter 0.5s steps(20) both',
        'shimmer': 'shimmer 3s ease-in-out infinite',
        'noise': 'noise 0.5s steps(4) infinite',
      },
      keyframes: {
        blink: {
          '0%, 100%': { opacity: '1' },
          '50%': { opacity: '0' },
        },
        glowPulse: {
          '0%, 100%': { opacity: '0.5' },
          '50%': { opacity: '1' },
        },
        glowBreathe: {
          '0%, 100%': { opacity: '0.3', transform: 'scale(1)' },
          '50%': { opacity: '0.6', transform: 'scale(1.05)' },
        },
        float: {
          '0%, 100%': { transform: 'translateY(0)' },
          '50%': { transform: 'translateY(-6px)' },
        },
        slideUp: {
          '0%': { opacity: '0', transform: 'translateY(24px)' },
          '100%': { opacity: '1', transform: 'translateY(0)' },
        },
        scaleIn: {
          '0%': { opacity: '0', transform: 'scale(0.92)' },
          '100%': { opacity: '1', transform: 'scale(1)' },
        },
        fadeIn: {
          '0%': { opacity: '0' },
          '100%': { opacity: '1' },
        },
        crtOn: {
          '0%': { filter: 'brightness(20) saturate(0)', opacity: '0' },
          '15%': { filter: 'brightness(3) saturate(0.5)', opacity: '1' },
          '100%': { filter: 'brightness(1) saturate(1)', opacity: '1' },
        },
        flickerIn: {
          '0%': { opacity: '0' },
          '10%': { opacity: '0.8' },
          '20%': { opacity: '0.2' },
          '40%': { opacity: '0.9' },
          '60%': { opacity: '0.5' },
          '100%': { opacity: '1' },
        },
        shimmer: {
          '0%': { backgroundPosition: '-200% 0' },
          '100%': { backgroundPosition: '200% 0' },
        },
        noise: {
          '0%': { transform: 'translate(0, 0)' },
          '25%': { transform: 'translate(-2px, 2px)' },
          '50%': { transform: 'translate(2px, -1px)' },
          '75%': { transform: 'translate(-1px, -2px)' },
          '100%': { transform: 'translate(1px, 1px)' },
        },
      },
      boxShadow: {
        'neon-green': '0 0 5px #39ff14, 0 0 20px rgba(57,255,20,0.25), 0 0 60px rgba(57,255,20,0.08)',
        'neon-cyan': '0 0 5px #00e5ff, 0 0 20px rgba(0,229,255,0.25), 0 0 60px rgba(0,229,255,0.08)',
        'neon-amber': '0 0 5px #ffbf00, 0 0 20px rgba(255,191,0,0.25), 0 0 60px rgba(255,191,0,0.08)',
        'neon-magenta': '0 0 5px #ff2ecc, 0 0 20px rgba(255,46,204,0.25), 0 0 60px rgba(255,46,204,0.08)',
      }
    },
  },
  plugins: [],
}
