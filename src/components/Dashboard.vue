<template>
  <div class="archive-dashboard">
    <h2>Archiv-Dashboard</h2>

    <NcNoteCard v-if="loading" type="info">Lade Archiv-Status...</NcNoteCard>

    <NcNoteCard v-else-if="agentError" type="warning">
      {{ agentError }}
    </NcNoteCard>

    <div v-else>
      <div class="souvera-stats">
        <div class="souvera-stat">
          <span class="souvera-stat__label">{{ totalLabel }}</span>
          <span class="souvera-stat__value">{{ totalMessages }}</span>
        </div>
        <div class="souvera-stat">
          <span class="souvera-stat__label">Chain-Status</span>
          <span class="souvera-stat__value" :class="chainClass">{{ chainLabel }}</span>
        </div>
        <div class="souvera-stat">
          <span class="souvera-stat__label">Tage gesiegelt</span>
          <span class="souvera-stat__value">{{ status.day_count || '0' }}</span>
        </div>
        <div class="souvera-stat">
          <span class="souvera-stat__label">Letztes Sealing</span>
          <span class="souvera-stat__value">{{ fmtDate(status.last_sealed_at) }}</span>
        </div>
        <div class="souvera-stat">
          <span class="souvera-stat__label">Public-Key</span>
          <span class="souvera-stat__value pubkey">{{ status.public_key_fingerprint || '—' }}</span>
        </div>
        <div v-if="resyncMsg" class="souvera-stat">
          <span class="souvera-stat__value">{{ resyncMsg }}</span>
        </div>
      </div>

      <button :disabled="resyncBusy" @click="doResync" class="resync-btn">
        {{ resyncBusy ? 'Resync läuft...' : 'Bestehende Mails archivieren' }}
      </button>
    </div>
  </div>
</template>

<script>
import { NcNoteCard } from '@nextcloud/vue'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

export default {
  name: 'ArchiveDashboard',
  components: { NcNoteCard },
  data() {
    return { status: null, agentStatus: null, agentStatusError: null, loading: true, agentError: null, resyncBusy: false, resyncMsg: null }
  },
  computed: {
    totalMessages() {
      const v = this.agentStatus?.total_messages
      if (v != null) return Number(v).toLocaleString()
      const fallback = this.status?.message_count
      return (fallback != null) ? Number(fallback).toLocaleString() : '—'
    },
    totalLabel() {
      return this.agentStatus?.total_messages != null ? 'Archivierte Mails' : 'Nachrichten in Chain'
    },
    chainLabel() {
      const s = this.status?.chain_status
      if (s === 'ok') return 'OK'
      if (s === 'empty') return 'Leer'
      if (s === 'unknown') return 'Nicht erreichbar'
      return s || '—'
    },
    chainClass() {
      const s = this.status?.chain_status
      if (s === 'ok') return 'text-ok'
      if (s === 'empty') return 'text-warn'
      return 'text-err'
    },
  },
  async mounted() {
    try {
      const results = await Promise.allSettled([
        axios.get(generateUrl('/apps/souvera_mailarchiv/api/archive/integrity')),
        axios.get(generateUrl('/apps/souvera_mailarchiv/api/archive/status')),
      ])
      const integrityRes = results[0]
      const statusRes = results[1]

      if (integrityRes.status === 'fulfilled') {
        this.status = integrityRes.value.data
        if (this.status?.chain_status === 'unknown' && !this.status?.last_sealed_at && !this.status?.public_key_fingerprint) {
          this.agentError = 'Archive-Agent nicht erreichbar — bitte Konfiguration prüfen (souvera_central.archive_agent_url).'
        }
      } else {
        this.agentError = 'Integritäts-Endpoint nicht erreichbar.'
      }

      if (statusRes.status === 'fulfilled') {
        this.agentStatus = statusRes.value.data
        if (statusRes.value.data?.error) {
          this.agentStatusError = statusRes.value.data.error
        }
      } else {
        this.agentStatusError = 'Status-Endpoint nicht erreichbar.'
      }
    } catch (e) {
      console.error('Dashboard load failed', e)
      this.agentError = 'Fehler beim Laden des Archiv-Status: ' + (e?.message || 'Unbekannter Fehler')
    }
    this.loading = false
  },
  methods: {
    fmtDate(ts) { return ts ? new Date(ts).toLocaleString() : '—' },
    async doResync() {
      this.resyncBusy = true
      this.resyncMsg = null
      try {
        const { data } = await axios.post(generateUrl('/apps/souvera_mailarchiv/api/archive/resync'))
        if (data.ok) {
          this.resyncMsg = 'Resync-Job gestartet. Mails werden im Hintergrund archiviert.'
        } else {
          this.resyncMsg = 'Resync konnte nicht gestartet werden.'
        }
      } catch (e) {
        this.resyncMsg = 'Fehler: ' + (e.response?.data?.error || e.message)
      }
      this.resyncBusy = false
    },
  },
}
</script>

<style scoped>
.pubkey { font-size: 11px; font-family: monospace; word-break: break-all; }
.resync-btn { margin-top: 12px; padding: 8px 16px; background: var(--color-primary); color: #fff; border: none; border-radius: 4px; cursor: pointer; font-size: 13px; }
.resync-btn:disabled { opacity: 0.5; cursor: wait; }
</style>
