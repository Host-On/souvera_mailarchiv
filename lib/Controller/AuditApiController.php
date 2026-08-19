<?php

declare(strict_types=1);

namespace OCA\SouveraArchive\Controller;

use OCA\SouveraArchive\Service\ArchiveService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IConfig;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

class AuditApiController extends Controller
{
	public function __construct(
		string $appName,
		IRequest $request,
		private ArchiveService $archiveService,
		private IConfig $config,
		private LoggerInterface $logger,
	) {
		parent::__construct($appName, $request);
	}

	private function getTenantId(): string
	{
		return $this->config->getSystemValue('souvera_central.tenant_id', 'default');
	}

	#[NoCSRFRequired]
	#[NoAdminRequired]
	public function list(
		int $limit = 50,
		int $offset = 0,
	): JSONResponse {
		$result = $this->archiveService->getAuditLog(
			$this->getTenantId(),
			max(1, min(200, $limit)),
			max(0, $offset),
		);
		if ($result === null) {
			return new JSONResponse(['data' => [], 'total' => 0, 'error' => 'Archive-Agent nicht erreichbar.']);
		}
		return new JSONResponse($result);
	}
}
