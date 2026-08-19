<?php

declare(strict_types=1);

namespace OCA\SouveraArchive\Db;

/**
 * Lightweight DTO representing a single archive search result row.
 * Used by ArchiveSearchProvider to return typed results.
 */
class ArchiveSearchResult
{
	public function __construct(
		public readonly string $id,
		public readonly string $tenantId,
		public readonly string $sender,
		public readonly string $recipient,
		public readonly string $subject,
		public readonly string $dateReceived,
		public readonly int $sizeBytes,
		public readonly string $messageHash,
	) {}
}
