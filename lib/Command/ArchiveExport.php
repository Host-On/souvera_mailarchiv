<?php

declare(strict_types=1);

namespace OCA\SouveraArchive\Command;

use OCA\SouveraArchive\Service\ArchiveExportService;
use OC\Core\Command\Base;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ArchiveExport extends Base
{
	public function __construct(
		private ArchiveExportService $exportService,
	) {
		parent::__construct();
	}

	protected function configure(): void
	{
		$this
			->setName('souvera_mailarchiv:archive:export')
			// Legacy-Alias (Namespace vor der Vereinheitlichung)
			->setAliases(['archive:export'])
			->setDescription('Startet einen GoBD-konformen Archiv-Export.')
			->addOption('tenant', null, InputOption::VALUE_REQUIRED, 'Tenant-ID')
			->addOption('from', null, InputOption::VALUE_REQUIRED, 'Startdatum (YYYY-MM-DD)')
			->addOption('to', null, InputOption::VALUE_REQUIRED, 'Enddatum (YYYY-MM-DD)')
			->addOption('format', null, InputOption::VALUE_REQUIRED, 'Export-Format: gobd (Standard) oder eml', 'gobd')
			->addOption('output', null, InputOption::VALUE_REQUIRED, 'Lokaler Pfad für gespeicherte Export-Dateien (optional)')
			->addOption('user', null, InputOption::VALUE_REQUIRED, 'Nur E-Mails dieses Benutzers exportieren')
			->addOption('sender', null, InputOption::VALUE_REQUIRED, 'Nach Absender filtern')
			->addOption('recipient', null, InputOption::VALUE_REQUIRED, 'Nach Empfänger filtern')
			->addOption('subject', null, InputOption::VALUE_REQUIRED, 'Nach Betreff filtern');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$tenantId = $input->getOption('tenant');
		if (!$tenantId) {
			$output->writeln('<error>--tenant ist erforderlich.</error>');
			return 1;
		}

		$params = array_filter([
			'format' => $input->getOption('format'),
			'date_from' => $input->getOption('from'),
			'date_to' => $input->getOption('to'),
			'user_id' => $input->getOption('user'),
			'sender' => $input->getOption('sender'),
			'recipient' => $input->getOption('recipient'),
			'subject' => $input->getOption('subject'),
		], fn($v) => $v !== null);

		$errors = $this->exportService->validateExportParams($params);
		if (!empty($errors)) {
			foreach ($errors as $error) {
				$output->writeln("<error>$error</error>");
			}
			return 1;
		}

		$output->writeln("<info>Starte Export für Tenant {$tenantId}...</info>");
		$result = $this->exportService->requestExport($tenantId, $params);
		if ($result === null) {
			$output->writeln('<error>Export-Anfrage fehlgeschlagen.</error>');
			return 1;
		}

		$jobId = $result['job_id'] ?? null;
		if ($jobId) {
			$output->writeln("Job-ID: {$jobId}");
			$output->writeln('Polling auf Fertigstellung...');
			for ($i = 0; $i < 60; $i++) {
				sleep(5);
				$status = $this->exportService->pollExportStatus($tenantId, $jobId);
				if ($status === null) {
					continue;
				}
				$progress = $status['progress'] ?? 0;
				$output->writeln("  Fortschritt: {$progress}%");
				if (($status['status'] ?? '') === 'completed') {
					$url = $status['download_url'] ?? null;
					if ($url) {
						$output->writeln("<info>Export abgeschlossen.</info>");
						$output->writeln("Download: {$url}");
						if ($input->getOption('output')) {
							$output->writeln("Lokaler Pfad: " . $input->getOption('output'));
						}
					}
					return 0;
				}
				if (($status['status'] ?? '') === 'failed') {
					$output->writeln('<error>Export fehlgeschlagen.</error>');
					return 1;
				}
			}
			$output->writeln('<error>Timeout: Export nach 5 Minuten nicht abgeschlossen.</error>');
			return 1;
		}

		$output->writeln(json_encode($result, JSON_PRETTY_PRINT));
		return 0;
	}
}
