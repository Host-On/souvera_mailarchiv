<template>
  <div class="audit-log-viewer">
    <h2>Audit-Log</h2>

    <NcNoteCard v-if="loading" type="info">Lade Audit-Log...</NcNoteCard>

    <NcNoteCard v-else-if="auditError" type="warning">
      {{ auditError }}
    </NcNoteCard>

    <NcEmptyContent v-else-if="loaded && entries.length === 0" name="Keine Audit-Einträge" />

    <div v-if="entries.length" class="table-scroll">
    <table class="audit-table">
      <thead>
        <tr><th>Datum</th><th>User</th><th>Aktion</th><th>Details</th></tr>
      </thead>
      <tbody>
        <tr v-for="row in entries" :key="row.id">
          <td class="nowrap">{{ fmtDate(row.timestamp) }}</td>
          <td>{{ row.user_id || '—' }}</td>
          <td>{{ row.action || '—' }}</td>
          <td>{{ row.details || '—' }}</td>
        </tr>
      </tbody>
    </table>
    </div>

    <div v-if="total > entries.length" class="pagination">
      <NcButton :disabled="offset === 0" @click="loadPage(Math.max(0, offset - 50))">Zurück</NcButton>
      <span>{{ offset + 1 }} – {{ offset + entries.length }} von {{ total }}</span>
      <NcButton :disabled="offset + entries.length >= total" @click="loadPage(offset + 50)">Weiter</NcButton>
    </div>
  </div>
</template>

<script>
import { NcButton, NcEmptyContent, NcNoteCard } from '@nextcloud/vue'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

export default {
  name: 'AuditLogViewer',
  components: { NcButton, NcEmptyContent, NcNoteCard },
  data() {
    return { entries: [], total: 0, offset: 0, loaded: false, loading: true, auditError: null }
  },
  async mounted() { await this.loadPage(0) },
  methods: {
    async loadPage(off) {
      this.offset = off
      this.auditError = null
      try {
        const { data } = await axios.get(generateUrl('/apps/souvera_mailarchiv/api/archive/audit-log'), {
          params: { limit: 50, offset: this.offset },
        })
        if (data.error) {
          this.auditError = data.error
        } else {
          this.entries = data.data || []
          this.total = data.total || 0
        }
        this.loaded = true
      } catch (e) {
        console.error('Audit log load failed', e)
        this.auditError = 'Fehler beim Laden des Audit-Logs.'
      }
      this.loading = false
    },
    fmtDate(ts) { return ts ? new Date(ts).toLocaleString() : '—' },
  },
}
</script>

<style scoped>
.nowrap { white-space: nowrap; }
.pagination { display: flex; gap: 12px; align-items: center; justify-content: center; margin-top: 16px; font-size: 13px; color: var(--color-text-maxcontrast); flex-wrap: wrap; }

@media (max-width: 640px) {
  .audit-table { font-size: .82rem; }
  .audit-table th,
  .audit-table td { padding: 8px 10px; }
}
</style>
