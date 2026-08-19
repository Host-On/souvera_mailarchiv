<?php

declare(strict_types=1);

namespace OCA\SouveraArchive\Command;

use OCA\SouveraArchive\Service\ArchivePolicyService;
use OC\Core\Command\Base;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ArchivePolicySet extends Base
{
	public function __construct(
		private ArchivePolicyService $policyService,
	) {
		parent::__construct();
	}

	protected function configure(): void
	{
		$this
			->setName('archive:policy:set')
			->setDescription('Setzt die Aufbewahrungs-Policy für einen Tenant.')
			->addOption('tenant', null, InputOption::VALUE_REQUIRED, 'Tenant-ID')
			->addOption('retention', null, InputOption::VALUE_REQUIRED, 'Aufbewahrungsfrist in Jahren (6-15)')
			->addOption('auto-delete', null, InputOption::VALUE_REQUIRED, 'Automatische Löschung: yes|no');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$tenantId = $input->getOption('tenant');
		if (!$tenantId) {
			$output->writeln('<error>--tenant ist erforderlich.</error>');
			return 1;
		}

		$data = [];
		$retention = $input->getOption('retention');
		if ($retention !== null) {
			$years = (int) $retention;
			if (!$this->policyService->validateRetentionYears($years)) {
				$output->writeln('<error>Aufbewahrungsfrist muss zwischen 6 und 15 Jahren liegen.</error>');
				return 1;
			}
			$data['retention_years'] = $years;
		}

		$autoDelete = $input->getOption('auto-delete');
		if ($autoDelete !== null) {
			$data['auto_delete'] = in_array(strtolower($autoDelete), ['yes', 'true', '1', 'on'], true);
		}

		if (empty($data)) {
			$output->writeln('<error>Keine Option angegeben. Mindestens --retention oder --auto-delete erforderlich.</error>');
			return 1;
		}

		$result = $this->policyService->updatePolicy($tenantId, $data);
		if ($result === null) {
			$output->writeln('<error>Policy-Update fehlgeschlagen.</error>');
			return 1;
		}
		if (isset($result['error'])) {
			$output->writeln("<error>{$result['error']}</error>");
			return 1;
		}

		$output->writeln("<info>Policy für Tenant {$tenantId} aktualisiert.</info>");
		$output->writeln(json_encode($result, JSON_PRETTY_PRINT));
		return 0;
	}
}
