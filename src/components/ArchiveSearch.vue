<template>
  <div class="archive-search">
    <h2>Archiv-Suche</h2>

    <div class="search-form">
      <div class="search-row">
        <NcTextField v-model="query.q" label="Volltext" class="search-field" @keyup.enter="doSearch" />
        <NcButton type="primary" @click="doSearch" :disabled="loading">
          {{ loading ? 'Suche...' : 'Suchen' }}
        </NcButton>
        <NcButton @click="$emit('start-export')">
          Export
        </NcButton>
      </div>
      <div class="search-filters">
        <NcTextField v-model="query.sender" label="Absender" />
        <NcTextField v-model="query.recipient" label="Empfänger" />
        <NcTextField v-model="query.subject" label="Betreff" />
        <NcTextField v-model="query.dateFrom" label="Von (YYYY-MM-DD)" type="date" />
        <NcTextField v-model="query.dateTo" label="Bis (YYYY-MM-DD)" type="date" />
      </div>
    </div>

    <NcEmptyContent v-if="searched && results.length === 0" name="Keine Ergebnisse" />

    <div v-if="results.length" class="table-scroll">
    <table class="archive-table">
      <thead>
        <tr>
          <th>Datum</th><th>Von</th><th>An</th><th>Betreff</th><th>Größe</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="row in results" :key="row.id" @click="$emit('view-message', row)" class="clickable">
          <td class="nowrap">{{ fmtDate(row.date_received) }}</td>
          <td>{{ truncate(row.sender, 30) }}</td>
          <td>{{ truncate(row.recipient, 30) }}</td>
          <td>{{ truncate(row.subject, 50) }}</td>
          <td class="nowrap">{{ fmtSize(row.size_bytes) }}</td>
        </tr>
      </tbody>
    </table>
    </div>

    <div v-if="total > results.length" class="pagination">
      <NcButton :disabled="offset === 0" @click="offset = Math.max(0, offset - 50); doSearch()">
        Zurück
      </NcButton>
      <span class="page-info">{{ offset + 1 }} – {{ offset + results.length }} von {{ total }}</span>
      <NcButton :disabled="offset + results.length >= total" @click="offset += 50; doSearch()">
        Weiter
      </NcButton>
    </div>
  </div>
</template>

<script>
import { NcButton, NcEmptyContent, NcTextField } from '@nextcloud/vue'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

export default {
  name: 'ArchiveSearch',
  components: { NcButton, NcEmptyContent, NcTextField },
  emits: ['view-message', 'start-export'],
  data() {
    return {
      query: { q: '', sender: '', recipient: '', subject: '', dateFrom: '', dateTo: '' },
      results: [],
      total: 0,
      offset: 0,
      loading: false,
      searched: false,
    }
  },
  methods: {
    async doSearch() {
      this.loading = true
      try {
        const params = { limit: 50, offset: this.offset }
        for (const [k, v] of Object.entries(this.query)) {
          if (v) params[k] = v
        }
        const { data } = await axios.get(generateUrl('/apps/souvera_mailarchiv/api/archive/search'), { params })
        this.results = data.data || []
        this.total = data.total || 0
        this.searched = true
      } catch (e) { console.error('Search failed', e); this.results = [] }
      finally { this.loading = false }
    },
    fmtDate(ts) { return ts ? new Date(ts).toLocaleString() : '—' },
    fmtSize(b) { if (!b) return ''; const u = ['B', 'KB', 'MB', 'GB']; let i = 0; while (b > 1024 && i < 3) { b /= 1024; i++ }; return b.toFixed(1) + ' ' + u[i] },
    truncate(s, n) { return s && s.length > n ? s.slice(0, n) + '...' : s },
  },
}
</script>

<style scoped>
.search-form { margin-bottom: 16px; }
.search-row { display: flex; gap: 8px; align-items: flex-end; margin-bottom: 12px; flex-wrap: wrap; }
.search-field { flex: 1 1 200px; }
.search-filters { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 8px; }
.nowrap { white-space: nowrap; }
.clickable { cursor: pointer; }
.clickable:hover { background: var(--color-background-hover); }
.pagination { display: flex; gap: 12px; align-items: center; justify-content: center; margin-top: 16px; flex-wrap: wrap; }
.page-info { font-size: 13px; color: var(--color-text-maxcontrast); }

@media (max-width: 640px) {
  .archive-table { font-size: .82rem; }
  .archive-table th,
  .archive-table td { padding: 8px 10px; }
}
</style>
