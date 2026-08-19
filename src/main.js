/**
 * Souvera Archive — Frontend Bootstrap
 */
import { createApp } from 'vue'
import App from './App.vue'
import './styles/main.css'

const mountEl = document.getElementById('souvera-archive-content')
window.OCA = window.OCA || {}
window.OCA.SouveraArchive = {
    version: mountEl?.dataset?.appVersion || '0.0.0',
}

const app = createApp(App)
app.mount('#souvera-archive-content')
