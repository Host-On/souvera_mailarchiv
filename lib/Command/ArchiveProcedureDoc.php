<?php

declare(strict_types=1);

namespace OCA\SouveraArchive\Command;

use OCA\SouveraArchive\Service\IntegrityService;
use OC\Core\Command\Base;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ArchiveProcedureDoc extends Base
{
	public function __construct(
		private IntegrityService $integrityService,
	) {
		parent::__construct();
	}

	protected function configure(): void
	{
		$this
			->setName('souvera_mailarchiv:archive:procedure-doc')
			// Legacy-Alias (Namespace vor der Vereinheitlichung)
			->setAliases(['archive:procedure-doc'])
			->setDescription('Generiert die Verfahrensdokumentation für das E-Mail-Archiv.')
			->addOption('tenant', null, InputOption::VALUE_REQUIRED, 'Tenant-ID')
			->addOption('output', null, InputOption::VALUE_REQUIRED, 'Pfad für die Ausgabedatei (Markdown)')
			->addOption('json', null, InputOption::VALUE_NONE, 'Ausgabe als strukturiertes JSON');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$tenantId = $input->getOption('tenant') ?? 'default';
		$integrity = $this->integrityService->getIntegrityOverview($tenantId);

		$doc = [
			'title' => 'Verfahrensdokumentation — Souvera E-Mail-Archiv',
			'generated_at' => date('Y-m-d\TH:i:s\Z'),
			'tenant_id' => $tenantId,
			'sections' => [
				[
					'heading' => '1. Systembeschreibung',
					'content' => 'Das Souvera E-Mail-Archiv speichert alle ein- und ausgehenden E-Mails revisionssicher in einem S3-kompatiblen Object-Store. Die Archivierung erfolgt via SMTP-Journaling (Sieve redirect :copy) in Echtzeit.',
				],
				[
					'heading' => '2. Datenfluss',
					'content' => 'Stalwart Mail Server → Sieve redirect :copy → Archive Ingest → S3-Speicherung mit SHA-256 → tägliches Merkle-Chain-Sealing mit Ed25519-Signatur.',
				],
				[
					'heading' => '3. Sicherheitsmaßnahmen',
					'content' => implode("\n", [
						'- SHA-256-Hash jeder einzelnen EML-Datei',
						'- Merkle-Tree: tägliche Aggregation aller Hashes',
						'- Ed25519-Signatur des Daily-Roots (Key im CloudManager)',
						'- Manipulationsnachweis durch Chain-of-Proofs',
						'- Chain-Status: ' . ($integrity['chain_status'] ?? 'unbekannt'),
						'- Public-Key-Fingerprint: ' . ($integrity['public_key_fingerprint'] ?? '—'),
					]),
				],
				[
					'heading' => '4. Aufbewahrungsregeln',
					'content' => 'Standard: 10 Jahre (GoBD-konform). Konfigurierbar zwischen 6 und 15 Jahren. Legal-Hold für einzelne Benutzer möglich. Automatische Löschung nach Ablauf der Frist (sofern aktiviert).',
				],
				[
					'heading' => '5. Zugriffskontrolle',
					'content' => 'Zugriff nur über die Souvera-Archive-Oberfläche (Web-UI) oder die API. Authentifizierung über Nextcloud-Login. Berechtigung: Mitglieder der souvera-admins-Gruppe. Alle Zugriffe werden im Audit-Log protokolliert.',
				],
				[
					'heading' => '6. Export-Verfahren',
					'content' => 'GoBD-konformer Export als ZIP-Archiv mit:\n- EML-Dateien (RFC 5322)\n- Index-XML\n- Chain-Proof-Dateien (Merkle-Path + Ed25519-Signaturen)\nExport via occ archive:export oder Web-UI.',
				],
				[
					'heading' => '7. Integritätsstatus',
					'content' => json_encode($integrity, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
				],
			],
		];

		if ($input->getOption('json')) {
			$output->writeln(json_encode($doc, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
			return 0;
		}

		$md = "# {$doc['title']}\n\n";
		$md .= "Erstellt: {$doc['generated_at']}\n";
		$md .= "Tenant: {$doc['tenant_id']}\n\n";
		$md .= "---\n\n";

		foreach ($doc['sections'] as $section) {
			$md .= "## {$section['heading']}\n\n";
			$md .= "{$section['content']}\n\n";
		}

		if ($outputPath = $input->getOption('output')) {
			file_put_contents($outputPath, $md);
			$output->writeln("<info>Verfahrensdokumentation gespeichert: {$outputPath}</info>");
		} else {
			$output->writeln($md);
		}

		return 0;
	}
}
