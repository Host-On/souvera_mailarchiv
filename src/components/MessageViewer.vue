<template>
  <div class="message-viewer">
    <div class="msg-toolbar">
      <NcButton @click="downloadEm" variant="tertiary">
        <template #icon><Download :size="20" /></template>
        EML herunterladen
      </NcButton>
      <NcButton @click="verifyIntegrity" variant="tertiary" :disabled="verifying">
        <template #icon><ShieldCheck :size="20" /></template>
        {{ verifying ? 'Prüfe...' : 'Integrität prüfen' }}
      </NcButton>
      <NcButton @click="restoreMessage" variant="tertiary" :disabled="restoring">
        <template #icon><Restore :size="20" /></template>
        {{ restoring ? 'Stelle wieder her...' : 'Wiederherstellen' }}
      </NcButton>
    </div>

    <NcNoteCard v-if="restoreMsg" :type="restoreOk ? 'success' : 'error'">
      {{ restoreMsg }}
    </NcNoteCard>

    <table class="msg-headers">
      <tbody>
      <tr><th>Von</th><td>{{ fullMessage.sender }}</td></tr>
      <tr><th>An</th><td>{{ fullMessage.recipient }}</td></tr>
      <tr v-if="fullMessage.cc"><th>CC</th><td>{{ fullMessage.cc }}</td></tr>
      <tr><th>Datum</th><td>{{ fmtDate(fullMessage.date_received) }}</td></tr>
      <tr><th>Betreff</th><td>{{ fullMessage.subject }}</td></tr>
      <tr v-if="fullMessage.message_hash"><th>SHA-256</th><td class="mono">{{ fullMessage.message_hash }}</td></tr>
      </tbody>
    </table>

    <div class="msg-body">
      <pre v-if="bodyLoading">Lade Inhalt...</pre>
      <pre v-else>{{ fullMessage.body_preview || fullMessage.body || fullMessage.text_body || fullMessage.content || '(Kein Inhalt verfügbar)' }}</pre>
    </div>

    <NcNoteCard v-if="integrityResult" :type="integrityResult.valid ? 'success' : 'error'">
      <template v-if="integrityResult.valid">
        Integrität bestätigt — Chain-Day: {{ integrityResult.chain_day }}, Proof validiert.
      </template>
      <template v-else>
        Integritätsfehler! Die Nachricht wurde möglicherweise manipuliert.
      </template>
    </NcNoteCard>
  </div>
</template>

<script>
import { NcButton, NcNoteCard } from '@nextcloud/vue'
import Download from 'vue-material-design-icons/Download.vue'
import ShieldCheck from 'vue-material-design-icons/ShieldCheck.vue'
import Restore from 'vue-material-design-icons/Restore.vue'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

export default {
  name: 'MessageViewer',
  components: { NcButton, NcNoteCard, Download, ShieldCheck, Restore },
  props: { message: { type: Object, required: true } },
  data() {
    return {
      fullMessage: { ...this.message },
      bodyLoading: true,
      integrityResult: null,
      verifying: false,
      restoring: false,
      restoreMsg: null,
      restoreOk: false,
    }
  },
  async mounted() {
    try {
      const { data } = await axios.get(generateUrl(`/apps/souvera_mailarchiv/api/archive/messages/${this.message.id}`))
      if (data && !data.error) {
        this.fullMessage = { ...this.message, ...data }
      }
    } catch (e) {
      console.error('Failed to load full message', e)
    }
    this.bodyLoading = false
  },
  methods: {
    async downloadEm() {
      try {
        const { data } = await axios.get(generateUrl(`/apps/souvera_mailarchiv/api/archive/messages/${this.fullMessage.id}/download`))
        if (data.url) window.open(data.url, '_blank')
      } catch (e) { console.error('Download failed', e) }
    },
    async verifyIntegrity() {
      this.verifying = true
      try {
        const { data } = await axios.get(generateUrl(`/apps/souvera_mailarchiv/api/archive/integrity/verify/${this.fullMessage.id}`))
        this.integrityResult = data
      } catch (e) { console.error('Integrity verify failed', e) }
      finally { this.verifying = false }
    },
    async restoreMessage() {
      this.restoring = true
      this.restoreMsg = null
      try {
        const { data } = await axios.post(generateUrl(`/apps/souvera_mailarchiv/api/archive/restore/${this.fullMessage.id}`))
        this.restoreOk = !data.error
        this.restoreMsg = data.error || data.message || 'Nachricht wiederhergestellt.'
      } catch (e) {
        this.restoreOk = false
        this.restoreMsg = 'Fehler: ' + (e.response?.data?.error || e.message)
      }
      this.restoring = false
    },
    fmtDate(ts) { return ts ? new Date(ts).toLocaleString() : '—' },
  },
}
</script>

<style scoped>
.msg-toolbar { display: flex; gap: 8px; margin-bottom: 12px; flex-wrap: wrap; }
.msg-headers { width: 100%; margin-bottom: 12px; }
.msg-headers th { text-align: left; padding: 4px 8px; font-size: 12px; color: var(--color-text-maxcontrast); width: 60px; vertical-align: top; }
.msg-headers td { padding: 4px 8px; font-size: 13px; }
.msg-body pre { white-space: pre-wrap; font-family: inherit; font-size: 13px; line-height: 1.5; }
.mono { font-family: monospace; font-size: 11px; word-break: break-all; }

@media (max-width: 640px) {
  .msg-headers th,
  .msg-headers td { padding: 6px 8px; font-size: .82rem; }
}
</style>
