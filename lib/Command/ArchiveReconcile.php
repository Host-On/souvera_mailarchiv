<?php

declare(strict_types=1);

namespace OCA\SouveraArchive\Command;

use OCA\SouveraArchive\Service\ArchiveService;
use OC\Core\Command\Base;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ArchiveReconcile extends Base
{
	public function __construct(
		private ArchiveService $archiveService,
	) {
		parent::__construct();
	}

	protected function configure(): void
	{
		$this
			->setName('archive:reconcile')
			->setDescription('Prüft den S3-Index gegen die gespeicherten E-Mails und meldet Inkonsistenzen.')
			->addOption('tenant', null, InputOption::VALUE_REQUIRED, 'Tenant-ID (default: Systemkonfiguration)')
			->addOption('json', null, InputOption::VALUE_NONE, 'Ausgabe als JSON');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$tenantId = $input->getOption('tenant') ?? 'default';
		$asJson = (bool) $input->getOption('json');

		$result = $this->archiveService->getIntegrityStatus($tenantId);
		if ($result === null) {
			$output->writeln('<error>Keine Antwort vom CloudManager. CM-API konfiguriert?</error>');
			return 1;
		}

		if ($asJson) {
			$output->writeln(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
			return 0;
		}

		$output->writeln('<info>Archive Reconcile Report</info>');
		$output->writeln("Tenant:        {$tenantId}");
		$output->writeln('Chain-Status:  ' . ($result['chain_status'] ?? 'unbekannt'));
		$output->writeln('Nachrichten:   ' . ($result['message_count'] ?? '?'));
		$output->writeln('Letztes Seal:  ' . ($result['last_sealed_at'] ?? 'nie'));

		return 0;
	}
}
