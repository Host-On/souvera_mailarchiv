# Changelog

All notable changes to Souvera Archive will be documented in this file.

Format: [Semantic Versioning](https://semver.org/) — MAJOR.MINOR.PATCH

## [Unreleased]

## [0.4.3] — 2026-08-11

### Fixed
- Responsive Design: Table-Scroll-Wrapper mit `overflow-x: auto` + `min-width` in allen Tabellen-Komponenten
- Responsive: `flex-wrap` auf `.search-row`, `.msg-toolbar`, `.verify-row`, `.pagination` (keine Überlappungen mehr auf Mobilgeräten)
- Shield-angepasste Stat-Cards: `.souvera-stat` jetzt mit `flex-direction: column; gap: 6px`, Label `uppercase` + `letter-spacing`, Value `1.6rem` + `tabular-nums`
- `@media (max-width: 640px)` Breakpoint in allen Komponenten mit Tabellen (Schrift `.82rem`, Padding `8px 10px`)
- Dashboard: `totalMessages`-Fallback auf `status.message_count` wenn Agent `/status` kein `total_messages` liefert
- Dashboard: dynamisches Label "Archivierte Mails" / "Nachrichten in Chain"
- Dashboard: `Promise.allSettled` — `/status`-Fehler bricht nicht mehr das `/integrity`-Rendering

## [0.4.2] — 2026-08-11

### Fixed
- Audit-Log: nur noch für `souvera-admins` und Server-Admins sichtbar (Middleware-Restriktion)
- AuditLogViewer: zeigt jetzt Fehler vom Backend an (statt stumm "Keine Einträge") + Loading-State

## [0.4.1] — 2026-08-11

### Added
- Restore-Feature: "Wiederherstellen"-Button im MessageViewer. Lädt EML vom Agent, importiert via JMAP Email/import in die Ziel-Mailbox des Users (via Stalwart-Admin-Credentials)
- MessageViewer lädt jetzt beim Öffnen den vollständigen Nachrichten-Body via `GET /api/archive/messages/{id}` nach (Search-Results enthalten keinen Body)

### Fixed
- MessageViewer zeigte "(Kein Inhalt verfügbar)" weil nur das Search-Result (ohne Body) übergeben wurde — jetzt wird die volle Message nachgeladen

## [0.4.0] — 2026-08-06

### Breaking
- Permission-Modell: `SouveraAdminMiddleware` ersetzt durch eigenes `AccessControl` + `GroupRestrictionMiddleware`. `souvera-users` und `souvera-admins` Gruppen steuern jetzt den Zugriff (statt Admin-only).
- Server-Admins und `souvera-admins` sehen ALLE Mails; `souvera-users` sehen nur eigene + Shared-Mailbox-Emails

### Added
- `IdentityDiscoveryService`: Entdeckt alle E-Mail-Adressen eines Users (eigene + Aliase + Shared Mailboxes via Stalwart), analog zu Souvera Shield
- Multi-Email-Search: Durchsucht das Archiv für jede entdeckte E-Mail-Adresse und merged/dedupliziert die Ergebnisse
- `GET /api/archive/status`: Neuer Endpoint für Agent-Status (Live-Zähler archivierter Mails)
- Dashboard: Zeigt jetzt "Archivierte Mails" (via `/status`) getrennt von Chain-Daten (via `/integrity`)

### Fixed
- Footer: Exakt wie Shield — rechtsbündig, Border-Top, in `NcAppContent > .souvera-content` (kein separater Wrapper mehr)
- ArchiveService::search(): Alle Such-Parameter (`sender`, `recipient`, `subject`, `date_from`, `date_to`, `offset`) werden jetzt an den Agenten durchgereicht
- MessageViewer: `body`-Fallback für `body_preview` (Kompatibilität mit Agent-Response)

## [0.3.1] — 2026-08-06

### Changed
- Chain-Status 'empty' (leere Chain, Agent erreichbar) wird jetzt als "Leer" angezeigt statt als Error
- Dashboard + IntegrityDashboard: `chain_status`-Label und CSS-Klasse unterscheiden jetzt 'ok', 'empty', 'unknown' korrekt

## [0.3.0] — 2026-08-06

### Breaking
- Komplett CM-unabhängig: Alle API-Calls (Audit-Log, Export, Policy, Legal-Hold, Integrity-Verify) laufen direkt über den Archive-Agent — keine CM-API-URL, CM-API-Key oder Tenant-ID mehr nötig (nur noch `souvera_central.archive_agent_url`)

### Fixed
- Footer-Position: Versionsnummer klebt jetzt zuverlässig am unteren Rand (Flex-Wrapper ausserhalb NcContent)
- ArchiveService::search() error-path: `results`→`data` Key-Mismatch behoben, JSON-Validierung ergänzt
- Agent-Fehler: Dashboard, IntegrityDashboard und Audit-Log zeigen jetzt aussagekräftige Diagnose-Meldungen statt "unbekannt"/"0"/"—"

### Added
- ArchiveService: `agentPost()` und `agentDelete()` Helper für direkte Agent-Kommunikation
- Frontend: Fehlerdiagnose bei nicht erreichbarem Archive-Agent

## [0.2.5] — 2026-08-05

### Fixed
- ArchiveService::search() ruft jetzt direkt den Archive-Agent an (Pattern wie requestResync)
- getMessage(), getDownloadUrl(), getStatus(), getIntegrityStatus() → direkte Agent-Calls statt CM-Proxy
- agentGet()-Helper für einheitliche Agent-HTTP-Calls
- Dashboard zeigt korrekte Nachrichtenanzahl und Chain-Status vom Agent

## [0.1.1] — 2026-08-01 (Deploy-Fähigkeit + Selbst-Update)

Basis-Release, deploy-fähig für den CloudManager (git-clone in `custom_apps`):

### Neue Funktionen
- Revisionssichere, S3-native E-Mail-Archivierung (SMTP-Journaling via Sieve `redirect :copy`, S3-Speicherung mit SHA-256-Integritätsnachweis, Merkle-Chain mit Ed25519-Signaturen, tägliches Sealing).
- Volltextsuche über Absender, Empfänger, Betreff und Zeitraum (App-interne Suche + Nextcloud Unified Search); GoBD-konformer Export (EML + Index-XML + Chain-Proofs); Retention-Policies (6/10 Jahre) mit Legal-Hold; Integritäts-Dashboard + Verfahrensdokumentation.
- Vue-3-Frontend: Dashboard, Archivsuche, Nachrichten-Viewer, Export-Wizard, Integritäts-Dashboard, Audit-Log-Viewer (eigenes Webpack-Bundle, inkl. `<tbody>`-Fix).
- OCC-Kommandos: `ArchiveReconcile`, `ArchiveExport`, `ArchivePolicySet`, `ArchiveProcedureDoc`.
- Aktivierung nur über das CloudManager-Flag (`souvera_central.archive.enabled=1`) — kein Archiv ohne Buchung; Navigation + Admin-Section entsprechend gegated.
- Selbst-Update-Kette nach Souvera-Standard: `occ souvera_archive:self-update`, `occ souvera_archive:devops:channel` (stable: 1×/24h im Wartungsfenster, dev: alle 5 Minuten), Background-Job (fehlertolerante Registrierung), Pre-Update-Repair-Step (`occ app:update` zieht die neueste Version).
- Webpack-Bundle wird committet (`js/souvera_archive-*.js`, nicht mehr gitignored) — Fallback, damit git-clone-Deploys die Vue-UI enthalten.

### Technik / Infrastruktur
- Services: `CmApiClient` (CloudManager-Anbindung), `ArchiveService`, `IntegrityService`, `ArchiveExportService`, `ArchivePolicyService`; API-Controller: `ArchiveApiController`, `PolicyApiController`, `AuditApiController`, `PageController`; `ArchiveSearchResult`-DTO.
- Nextcloud 30–34, PHP 8.2+, AGPL-3.0.
