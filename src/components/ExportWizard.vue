<template>
  <div class="export-wizard">
    <NcNoteCard v-if="errors.length" type="error">
      <ul><li v-for="e in errors" :key="e">{{ e }}</li></ul>
    </NcNoteCard>

    <div class="export-form">
      <NcSelect v-model="format" :options="formatOptions" label="Export-Format" />

      <NcTextField v-model="dateFrom" label="Startdatum (YYYY-MM-DD)" type="date" />
      <NcTextField v-model="dateTo" label="Enddatum (YYYY-MM-DD)" type="date" />

      <NcTextField v-model="filterSender" label="Absender (optional)" />
      <NcTextField v-model="filterRecipient" label="Empfänger (optional)" />
      <NcTextField v-model="filterSubject" label="Betreff (optional)" />
      <NcTextField v-model="filterUser" label="Benutzer-ID (optional)" />

      <NcButton variant="primary" @click="startExport" :disabled="exporting">
        {{ exporting ? 'Exportiere...' : 'Export starten' }}
      </NcButton>
    </div>

    <NcNoteCard v-if="status" :type="statusType">
      <div v-if="status.status === 'running'">
        Fortschritt: {{ status.progress || 0 }}%
      </div>
      <div v-else-if="status.status === 'completed'">
        Export abgeschlossen!
        <a v-if="status.download_url" :href="status.download_url" target="_blank">Download</a>
      </div>
      <div v-else>
        Fehler beim Export.
      </div>
    </NcNoteCard>
  </div>
</template>

<script>
import { NcButton, NcNoteCard, NcSelect, NcTextField } from '@nextcloud/vue'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

export default {
  name: 'ExportWizard',
  components: { NcButton, NcNoteCard, NcSelect, NcTextField },
  data() {
    return {
      format: { value: 'gobd', label: 'GoBD (ZIP mit EML + Index-XML + Proofs)' },
      formatOptions: [
        { value: 'gobd', label: 'GoBD (ZIP mit EML + Index-XML + Proofs)' },
        { value: 'eml', label: 'Nur EML-Dateien' },
      ],
      dateFrom: '',
      dateTo: '',
      filterSender: '',
      filterRecipient: '',
      filterSubject: '',
      filterUser: '',
      errors: [],
      exporting: false,
      status: null,
      pollTimer: null,
    }
  },
  computed: {
    statusType() {
      if (this.status?.status === 'completed') return 'success'
      if (this.status?.status === 'failed') return 'error'
      return 'info'
    },
  },
  methods: {
    async startExport() {
      this.errors = []
      const payload = {
        format: this.format.value,
        date_from: this.dateFrom || undefined,
        date_to: this.dateTo || undefined,
        sender: this.filterSender || undefined,
        recipient: this.filterRecipient || undefined,
        subject: this.filterSubject || undefined,
        user_id: this.filterUser || undefined,
      }
      this.exporting = true
      try {
        const { data } = await axios.post(generateUrl('/apps/souvera_mailarchiv/api/archive/export'), payload)
        if (data.error) { this.errors = Array.isArray(data.error) ? data.error : [data.error]; return }
        this.status = data
        if (data.job_id) this.pollStatus(data.job_id)
      } catch (e) {
        this.errors = ['Export-Anfrage fehlgeschlagen.']
      } finally { this.exporting = false }
    },
    async pollStatus(jobId) {
      this.pollTimer = setInterval(async () => {
        try {
          const { data } = await axios.get(generateUrl(`/apps/souvera_mailarchiv/api/archive/export/${jobId}/status`))
          this.status = data
          if (data.status === 'completed' || data.status === 'failed') clearInterval(this.pollTimer)
        } catch (e) { console.error('Export poll failed', e) }
      }, 5000)
    },
  },
  beforeUnmount() { clearInterval(this.pollTimer) },
}
</script>

<style scoped>
.export-form { display: flex; flex-direction: column; gap: 12px; }
.export-form > * { margin-bottom: 0; }
</style>
