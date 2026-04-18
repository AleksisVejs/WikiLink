import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '../views/HomeView.vue'

const GameView = () => import('../views/GameView.vue')
const ProfileView = () => import('../views/ProfileView.vue')
const CommunityView = () => import('../views/CommunityView.vue')
const NotFoundView = () => import('../views/NotFoundView.vue')

const routes = [
  { path: '/', name: 'home', component: HomeView },
  { path: '/community', name: 'community', component: CommunityView },
  { path: '/play/:mode', name: 'game', component: GameView, props: true },
  {
    path: '/profile',
    name: 'profile',
    component: ProfileView,
    beforeEnter: () => {
      if (!localStorage.getItem('wikilink_token')) return { name: 'home' }
    },
  },
  {
    path: '/profile/:username',
    name: 'public-profile',
    component: ProfileView,
    props: true,
  },
  { path: '/:pathMatch(.*)*', name: 'not-found', component: NotFoundView },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

export default router
