<template>
  <NcContent app-name="souvera_mailarchiv">
    <NcAppNavigation>
      <template #list>
        <NcAppNavigationItem name="Dashboard"
          :active="currentView === 'dashboard'"
          @click="currentView = 'dashboard'">
          <template #icon><ViewDashboard :size="20" /></template>
        </NcAppNavigationItem>
        <NcAppNavigationItem name="Suche"
          :active="currentView === 'search'"
          @click="currentView = 'search'">
          <template #icon><Magnify :size="20" /></template>
        </NcAppNavigationItem>
        <NcAppNavigationItem name="Integrität"
          :active="currentView === 'integrity'"
          @click="currentView = 'integrity'">
          <template #icon><ShieldCheck :size="20" /></template>
        </NcAppNavigationItem>
        <NcAppNavigationItem name="Audit-Log"
          :active="currentView === 'audit'"
          @click="currentView = 'audit'">
          <template #icon><TextBoxSearch :size="20" /></template>
        </NcAppNavigationItem>
      </template>
    </NcAppNavigation>
    <NcAppContent>
      <div class="souvera-content">
        <Dashboard v-if="currentView === 'dashboard'" />
        <ArchiveSearch v-else-if="currentView === 'search'"
          @view-message="openMessage" @start-export="openExport" />
        <IntegrityDashboard v-else-if="currentView === 'integrity'" />
        <AuditLogViewer v-else-if="currentView === 'audit'" />
        <footer class="souvera-archive-footer">
          <span>v{{ appVersion }}</span>
        </footer>
      </div>
    </NcAppContent>

    <NcDialog v-if="selectedMessage" :open="true"
               :name="selectedMessage.subject || 'Nachricht'"
               size="large"
               @close="selectedMessage = null">
      <MessageViewer :message="selectedMessage" @close="selectedMessage = null" />
    </NcDialog>

    <NcDialog v-if="showExport" :open="true"
               name="Export"
               size="normal"
               @close="showExport = false">
      <ExportWizard @close="showExport = false" />
    </NcDialog>
  </NcContent>
</template>

<script>
import {
  NcContent, NcAppContent, NcAppNavigation, NcAppNavigationItem, NcDialog,
} from '@nextcloud/vue'

import ViewDashboard from 'vue-material-design-icons/ViewDashboard.vue'
import Magnify from 'vue-material-design-icons/Magnify.vue'
import ShieldCheck from 'vue-material-design-icons/ShieldCheck.vue'
import TextBoxSearch from 'vue-material-design-icons/TextBoxSearch.vue'

import Dashboard from './components/Dashboard.vue'
import ArchiveSearch from './components/ArchiveSearch.vue'
import IntegrityDashboard from './components/IntegrityDashboard.vue'
import AuditLogViewer from './components/AuditLogViewer.vue'
import MessageViewer from './components/MessageViewer.vue'
import ExportWizard from './components/ExportWizard.vue'

export default {
  name: 'SouveraArchiveApp',
  components: {
    NcContent, NcAppContent, NcAppNavigation, NcAppNavigationItem, NcDialog,
    ViewDashboard, Magnify, ShieldCheck, TextBoxSearch,
    Dashboard, ArchiveSearch, IntegrityDashboard, AuditLogViewer,
    MessageViewer, ExportWizard,
  },
  data() {
    return {
      currentView: 'dashboard',
      selectedMessage: null,
      showExport: false,
    }
  },
  computed: {
    appVersion() {
      return window.OCA?.SouveraArchive?.version || '0.4.3'
    },
  },
  methods: {
    openMessage(msg) { this.selectedMessage = msg },
    openExport() { this.showExport = true },
  },
}
</script>
<style scoped>
.souvera-archive-footer {
    margin-top: 48px;
    padding-top: 16px;
    border-top: 1px solid var(--color-border, var(--color-background-dark));
    color: var(--color-text-maxcontrast);
    font-size: .78rem;
    text-align: right;
}
</style>
