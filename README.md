# Souvera Archive — revisionssichere E-Mail-Archivierung

GoBD-konforme, S3-native E-Mail-Archivierung mit kryptografischer Integritätskette.

## Features

- SMTP-Journaling aller Mails (Sieve redirect :copy)
- S3-Speicherung mit SHA-256-Integritätsnachweis
- Merkle-Chain mit Ed25519-Signaturen (tägliches Sealing)
- Volltextsuche über Absender, Empfänger, Betreff, Zeitraum
- Audit-Trail (wer hat wann gesucht/exportiert)
- GoBD-konformer Export (EML + Index-XML + Chain-Proofs)
- Retention-Policies (6/10 Jahre) mit Legal-Hold
- Integritäts-Dashboard + Verfahrensdokumentation

## Entwicklung

```bash
cd souvera_mailarchiv
npm install
npm run build
```

## Commands

- `occ archive:reconcile` — S3-Index-Abgleich
- `occ archive:export` — GoBD-konformer Export
- `occ archive:policy:set` — Retention-Policy setzen
- `occ archive:procedure-doc` — Verfahrensdokumentation generieren
