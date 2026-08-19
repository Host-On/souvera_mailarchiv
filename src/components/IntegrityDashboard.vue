<template>
  <div class="integrity-dashboard">
    <h2>Integritäts-Dashboard</h2>

    <NcNoteCard v-if="loading" type="info">Lade Integritätsdaten...</NcNoteCard>

    <NcNoteCard v-else-if="agentError" type="warning">
      {{ agentError }}
    </NcNoteCard>

    <div v-else>
      <div class="souvera-stats">
        <div class="souvera-stat">
          <span class="souvera-stat__label">Chain-Status</span>
          <span class="souvera-stat__value" :class="chainClass">
            {{ chainLabel }}
          </span>
        </div>
        <div class="souvera-stat">
          <span class="souvera-stat__label">Nachrichten</span>
          <span class="souvera-stat__value">{{ overview.message_count?.toLocaleString() || '0' }}</span>
        </div>
        <div class="souvera-stat">
          <span class="souvera-stat__label">Tage gesiegelt</span>
          <span class="souvera-stat__value">{{ overview.day_count || '0' }}</span>
        </div>
        <div class="souvera-stat">
          <span class="souvera-stat__label">Letztes Sealing</span>
          <span class="souvera-stat__value">{{ fmtDate(overview.last_sealed_at) }}</span>
        </div>
      </div>

      <div class="pubkey-section">
        <h3>Öffentlicher Schlüssel (Ed25519)</h3>
        <code class="pubkey">{{ overview.public_key_fingerprint || '—' }}</code>
      </div>

      <div class="verify-section">
        <h3>Einzelmail prüfen</h3>
        <div class="verify-row">
          <NcTextField v-model="verifyId" label="Nachrichten-ID" />
          <NcButton @click="doVerify" :disabled="verifying">
            {{ verifying ? 'Prüfe...' : 'Prüfen' }}
          </NcButton>
        </div>
        <NcNoteCard v-if="verifyError" type="warning">
          {{ verifyError }}
        </NcNoteCard>
        <NcNoteCard v-else-if="verifyResult" :type="verifyResult.valid ? 'success' : 'error'">
          <template v-if="verifyResult.valid">
            SHA-256: {{ verifyResult.message_hash }} — Chain-Day: {{ verifyResult.chain_day }}
          </template>
          <template v-else>
            Prüfung fehlgeschlagen — Nachricht nicht in der Chain gefunden oder Hash-Mismatch.
          </template>
        </NcNoteCard>
      </div>
    </div>
  </div>
</template>

<script>
import { NcButton, NcNoteCard, NcTextField } from '@nextcloud/vue'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

export default {
  name: 'IntegrityDashboard',
  components: { NcButton, NcNoteCard, NcTextField },
  data() {
    return { overview: null, loading: true, agentError: null, verifyId: '', verifying: false, verifyResult: null, verifyError: null }
  },
  computed: {
    chainLabel() {
      const s = this.overview?.chain_status
      if (s === 'ok') return 'OK'
      if (s === 'empty') return 'Leer'
      if (s === 'unknown') return 'Nicht erreichbar'
      return s || '—'
    },
    chainClass() {
      const s = this.overview?.chain_status
      if (s === 'ok') return 'text-ok'
      if (s === 'empty') return 'text-warn'
      return 'text-err'
    },
  },
  async mounted() {
    try {
      const { data } = await axios.get(generateUrl('/apps/souvera_mailarchiv/api/archive/integrity'))
      this.overview = data
      if (!data?.last_sealed_at && !data?.public_key_fingerprint && data?.chain_status === 'unknown') {
        this.agentError = 'Archive-Agent nicht erreichbar — bitte Konfiguration prüfen (souvera_central.archive_agent_url).'
      }
    } catch (e) { console.error('Integrity overview failed', e); this.agentError = 'Fehler beim Laden der Integritätsdaten.' }
    this.loading = false
  },
  methods: {
    async doVerify() {
      if (!this.verifyId.trim()) return
      this.verifying = true
      this.verifyResult = null
      this.verifyError = null
      try {
        const { data } = await axios.get(generateUrl(`/apps/souvera_mailarchiv/api/archive/integrity/verify/${this.verifyId}`))
        if (data.error) {
          this.verifyError = 'Prüfung nicht möglich: ' + data.error
        } else {
          this.verifyResult = data
        }
      } catch (e) {
        console.error('Verify failed', e)
        this.verifyError = 'Fehler bei der Integritätsprüfung.'
      }
      finally { this.verifying = false }
    },
    fmtDate(ts) { return ts ? new Date(ts).toLocaleString() : '—' },
  },
}
</script>

<style scoped>
.pubkey-section, .verify-section { margin-top: 24px; }
.pubkey-section h3, .verify-section h3 { font-size: 16px; margin-bottom: 8px; }
.pubkey { font-size: 11px; font-family: monospace; word-break: break-all; padding: 8px; background: var(--color-background-dark); display: block; border-radius: 4px; }
.verify-row { display: flex; gap: 8px; align-items: flex-end; flex-wrap: wrap; }
.verify-row > :first-child { flex: 1 1 200px; }
</style>
