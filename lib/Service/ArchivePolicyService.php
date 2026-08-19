<?php

declare(strict_types=1);

namespace OCA\SouveraArchive\Service;

use Psr\Log\LoggerInterface;

/**
 * Manages retention policies and legal hold for archive data.
 */
class ArchivePolicyService
{
	public function __construct(
		private ArchiveService $archiveService,
		private LoggerInterface $logger,
	) {}

	public function getPolicy(string $tenantId): ?array
	{
		$result = $this->archiveService->getPolicy($tenantId);
		if ($result === null) {
			return [
				'retention_years' => 10,
				'auto_delete' => true,
				'legal_holds' => [],
			];
		}
		return $result;
	}

	public function updatePolicy(string $tenantId, array $data): ?array
	{
		$validKeys = ['retention_years', 'auto_delete'];
		$policy = array_intersect_key($data, array_flip($validKeys));

		if (isset($policy['retention_years'])) {
			$years = (int) $policy['retention_years'];
			if ($years < 6 || $years > 15) {
				return ['error' => 'Retention must be between 6 and 15 years.'];
			}
		}

		return $this->archiveService->updatePolicy($tenantId, $policy);
	}

	public function setLegalHold(string $tenantId, string $userId): ?array
	{
		return $this->archiveService->setLegalHold($tenantId, $userId);
	}

	public function removeLegalHold(string $tenantId, string $userId): ?array
	{
		return $this->archiveService->removeLegalHold($tenantId, $userId);
	}

	public function validateRetentionYears(int $years): bool
	{
		return $years >= 6 && $years <= 15;
	}
}
