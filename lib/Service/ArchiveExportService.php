<?php

declare(strict_types=1);

namespace OCA\SouveraArchive\Service;

use Psr\Log\LoggerInterface;

/**
 * Handles GoBD-compliant export requests (EML + Index-XML + Chain-Proofs).
 */
class ArchiveExportService
{
	public function __construct(
		private ArchiveService $archiveService,
		private LoggerInterface $logger,
	) {}

	public function requestExport(string $tenantId, array $params): ?array
	{
		$exportParams = array_merge([
			'format' => $params['format'] ?? 'gobd',
			'date_from' => $params['date_from'] ?? null,
			'date_to' => $params['date_to'] ?? null,
			'user_id' => $params['user_id'] ?? null,
			'sender' => $params['sender'] ?? null,
			'recipient' => $params['recipient'] ?? null,
			'subject' => $params['subject'] ?? null,
		], $params);

		return $this->archiveService->requestExport($tenantId, $exportParams);
	}

	public function pollExportStatus(string $tenantId, string $jobId): ?array
	{
		return $this->archiveService->getExportStatus($tenantId, $jobId);
	}

	public function validateExportParams(array $params): array
	{
		$errors = [];
		$format = $params['format'] ?? 'gobd';
		if (!in_array($format, ['gobd', 'eml'], true)) {
			$errors[] = 'Invalid format. Must be "gobd" or "eml".';
		}
		if (isset($params['date_from']) && !strtotime($params['date_from'])) {
			$errors[] = 'Invalid date_from format.';
		}
		if (isset($params['date_to']) && !strtotime($params['date_to'])) {
			$errors[] = 'Invalid date_to format.';
		}
		return $errors;
	}
}
