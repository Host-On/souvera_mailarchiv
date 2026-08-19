<?php

declare(strict_types=1);

namespace OCA\SouveraArchive\Controller;

use OCA\SouveraArchive\Service\ArchivePolicyService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IConfig;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

class PolicyApiController extends Controller
{
	public function __construct(
		string $appName,
		IRequest $request,
		private ArchivePolicyService $policyService,
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
	public function get(): JSONResponse
	{
		$result = $this->policyService->getPolicy($this->getTenantId());
		return new JSONResponse($result);
	}

	#[NoAdminRequired]
	public function update(): JSONResponse
	{
		$data = $this->request->getParams();
		$result = $this->policyService->updatePolicy($this->getTenantId(), $data);
		if ($result === null) {
			return new JSONResponse(['error' => 'Policy update failed.'], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
		if (isset($result['error'])) {
			return new JSONResponse($result, Http::STATUS_BAD_REQUEST);
		}
		return new JSONResponse($result);
	}

	#[NoAdminRequired]
	public function legalHold(string $userId): JSONResponse
	{
		$result = $this->policyService->setLegalHold($this->getTenantId(), $userId);
		if ($result === null) {
			return new JSONResponse(['error' => 'Legal hold request failed.'], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
		return new JSONResponse($result);
	}

	#[NoAdminRequired]
	public function legalHoldRemove(string $userId): JSONResponse
	{
		$result = $this->policyService->removeLegalHold($this->getTenantId(), $userId);
		if ($result === null) {
			return new JSONResponse(['error' => 'Legal hold removal failed.'], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
		return new JSONResponse($result);
	}
}
