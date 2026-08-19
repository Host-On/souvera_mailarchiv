<?php

declare(strict_types=1);

namespace OCA\SouveraArchive\Controller;

use OCA\SouveraArchive\Service\AccessControl;
use OCA\SouveraArchive\Service\ArchiveExportService;
use OCA\SouveraArchive\Service\ArchiveService;
use OCA\SouveraArchive\Service\IdentityDiscoveryService;
use OCA\SouveraArchive\Service\IntegrityService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IConfig;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

class ArchiveApiController extends Controller
{
	public function __construct(
		string $appName,
		IRequest $request,
		private ArchiveService $archiveService,
		private IntegrityService $integrityService,
		private ArchiveExportService $exportService,
		private AccessControl $access,
		private IdentityDiscoveryService $identityDiscovery,
		private IConfig $config,
		private LoggerInterface $logger,
	) {
		parent::__construct($appName, $request);
	}

	private function getTenantId(): string
	{
		return $this->config->getSystemValue('souvera_central.tenant_id', 'default');
	}

	private function getUserEmails(): ?array
	{
		if ($this->access->isCurrentUserAdmin()) {
			return null;
		}
		return $this->identityDiscovery->discover();
	}

	#[NoCSRFRequired]
	#[NoAdminRequired]
	public function search(
		?string $q = null,
		?string $sender = null,
		?string $recipient = null,
		?string $subject = null,
		?string $dateFrom = null,
		?string $dateTo = null,
		int $limit = 50,
		int $offset = 0,
	): JSONResponse {
		$baseQuery = array_filter([
			'q' => $q,
			'sender' => $sender,
			'recipient' => $recipient,
			'subject' => $subject,
			'date_from' => $dateFrom,
			'date_to' => $dateTo,
			'limit' => max(1, min(200, $limit)),
			'offset' => max(0, $offset),
		], fn($v) => $v !== null && $v !== '');

		$userEmails = $this->getUserEmails();

		if ($userEmails === null) {
			$result = $this->archiveService->search($this->getTenantId(), $baseQuery);
			return new JSONResponse($result ?? ['data' => [], 'total' => 0]);
		}

		if (empty($userEmails)) {
			return new JSONResponse(['data' => [], 'total' => 0]);
		}

		$allItems = [];
		$seen = [];
		$deadline = \time() + 10;

		foreach ($userEmails as $email) {
			if (\time() > $deadline) break;
			try {
				$query = $baseQuery;
				$query['user_email'] = $email;
				$result = $this->archiveService->search($this->getTenantId(), $query);
				foreach ($result['data'] ?? [] as $item) {
					$id = (string) ($item['id'] ?? '');
					if (isset($seen[$id])) continue;
					$seen[$id] = true;
					$item['_pmail'] = $email;
					$allItems[] = $item;
				}
			} catch (\Throwable $e) {
				$this->logger->warning('Search failed for {email}', ['email' => $email, 'exception' => $e->getMessage()]);
			}
		}

		\usort($allItems, static fn(array $a, array $b): int =>
			\strcmp((string)($b['date_received'] ?? ''), (string)($a['date_received'] ?? '')));

		$total = count($allItems);
		$page = array_slice($allItems, $baseQuery['offset'] ?? 0, $baseQuery['limit'] ?? 50);

		return new JSONResponse(['data' => $page, 'total' => $total]);
	}

	#[NoCSRFRequired]
	#[NoAdminRequired]
	public function get(string $id): JSONResponse
	{
		$result = $this->archiveService->getMessage($this->getTenantId(), $id);
		if ($result === null) {
			return new JSONResponse(['error' => 'Message not found.'], Http::STATUS_NOT_FOUND);
		}
		return new JSONResponse($result);
	}

	#[NoCSRFRequired]
	#[NoAdminRequired]
	public function download(string $id): JSONResponse
	{
		$url = $this->archiveService->getDownloadUrl($this->getTenantId(), $id);
		if ($url === null) {
			return new JSONResponse(['error' => 'Download URL not available.'], Http::STATUS_NOT_FOUND);
		}
		return new JSONResponse(['url' => $url]);
	}

	#[NoAdminRequired]
	public function export(): JSONResponse
	{
		$params = $this->request->getParams();
		$errors = $this->exportService->validateExportParams($params);
		if (!empty($errors)) {
			return new JSONResponse(['error' => $errors], Http::STATUS_BAD_REQUEST);
		}
		$result = $this->exportService->requestExport($this->getTenantId(), $params);
		if ($result === null) {
			return new JSONResponse(['error' => 'Export request failed.'], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
		return new JSONResponse($result);
	}

	#[NoCSRFRequired]
	#[NoAdminRequired]
	public function status(): JSONResponse
	{
		$result = $this->archiveService->getStatus($this->getTenantId());
		return new JSONResponse($result ?? ['error' => 'Agent not reachable']);
	}

	#[NoCSRFRequired]
	#[NoAdminRequired]
	public function integrityStatus(): JSONResponse
	{
		$result = $this->integrityService->getIntegrityOverview($this->getTenantId());
		return new JSONResponse($result);
	}

	#[NoCSRFRequired]
	#[NoAdminRequired]
	public function integrityVerify(string $messageId): JSONResponse
	{
		$result = $this->integrityService->verifyMessage($this->getTenantId(), $messageId);
		return new JSONResponse($result);
	}

	#[NoCSRFRequired]
	#[NoAdminRequired]
	public function exportStatus(string $jobId): JSONResponse
	{
		$result = $this->exportService->pollExportStatus($this->getTenantId(), $jobId);
		if ($result === null) {
			return new JSONResponse(['error' => 'Export job not found.'], Http::STATUS_NOT_FOUND);
		}
		return new JSONResponse($result);
	}

	#[NoAdminRequired]
	public function restore(string $id): JSONResponse
	{
		$user = $this->access->isCurrentUserAdmin()
			? ($this->request->getParam('target_email') ?: $this->getDefaultEmail())
			: $this->getDefaultEmail();

		if (!$user) {
			return new JSONResponse(['error' => 'Keine Ziel-Mailbox ermittelbar.'], Http::STATUS_BAD_REQUEST);
		}

		$result = $this->archiveService->restoreMessage($this->getTenantId(), $id, $user);
		if (isset($result['error'])) {
			return new JSONResponse($result, Http::STATUS_INTERNAL_SERVER_ERROR);
		}
		return new JSONResponse($result);
	}

	private function getDefaultEmail(): ?string
	{
		$emails = $this->identityDiscovery->discover();
		return $emails[0] ?? null;
	}

	#[NoAdminRequired]
	public function resync(): JSONResponse
	{
		// Resync goes directly to the archive agent — no CM dependency.
		// The archive agent handles JMAP polling, S3 storage, ES indexing.
		$result = $this->archiveService->requestResync($this->getTenantId());
		if (!$result || isset($result['error'])) {
			return new JSONResponse($result ?? ['error' => 'Resync fehlgeschlagen.'], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
		return new JSONResponse($result);
	}
}
