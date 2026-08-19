<?php

declare(strict_types=1);

namespace OCA\SouveraArchive\Service;

class IntegrityService
{
	public function __construct(
		private ArchiveService $archiveService,
	) {}

	public function verifyMessage(string $tenantId, string $messageId): array
	{
		$result = $this->archiveService->verifyMessageIntegrity($tenantId, $messageId);
		return [
			'valid' => $result['valid'] ?? false,
			'message_hash' => $result['message_hash'] ?? null,
			'chain_day' => $result['chain_day'] ?? null,
			'chain_root' => $result['chain_root'] ?? null,
			'proof_inclusion' => $result['proof_inclusion'] ?? null,
			'day_proof_signature_valid' => $result['day_proof_signature_valid'] ?? false,
		];
	}

	public function verifyDailyProof(string $tenantId, string $day): array
	{
		$result = $this->archiveService->getIntegrityStatus($tenantId);
		return [
			'day' => $day,
			'chain_status' => $result['chain_status'] ?? 'unknown',
			'message_count' => $result['message_count'] ?? 0,
			'last_sealed_at' => $result['last_sealed_at'] ?? null,
			'daily_proof_available' => ($result['chain_status'] ?? '') === 'ok',
		];
	}

	public function getIntegrityOverview(string $tenantId): array
	{
		$result = $this->archiveService->getIntegrityStatus($tenantId);
		return [
			'enabled' => true,
			'chain_status' => $result['chain_status'] ?? 'unknown',
			'message_count' => $result['message_count'] ?? 0,
			'last_sealed_at' => $result['last_sealed_at'] ?? null,
			'public_key_fingerprint' => $result['public_key_fingerprint'] ?? null,
			'day_count' => $result['day_count'] ?? 0,
		];
	}
}
