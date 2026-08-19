<?php

declare(strict_types=1);

namespace OCA\SouveraArchive\Middleware;

use OCA\SouveraArchive\Controller\ArchiveApiController;
use OCA\SouveraArchive\Controller\AuditApiController;
use OCA\SouveraArchive\Controller\PageController;
use OCA\SouveraArchive\Controller\PolicyApiController;
use OCA\SouveraArchive\Service\AccessControl;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Middleware;
use OCP\IRequest;

class GroupRestrictionMiddleware extends Middleware
{
	public function __construct(
		private AccessControl $access,
		private IRequest $request,
	) {}

	public function beforeController(Controller $controller, string $methodName): void
	{
		if (!($controller instanceof ArchiveApiController)
			&& !($controller instanceof AuditApiController)
			&& !($controller instanceof PageController)
			&& !($controller instanceof PolicyApiController)) {
			return;
		}

		$isAdminController = $controller instanceof AuditApiController;

		if ($isAdminController) {
			if ($this->access->isCurrentUserAdmin()) {
				return;
			}
			throw new ForbiddenException(true);
		}

		if ($this->access->isCurrentUserAllowed()) {
			return;
		}
		throw new ForbiddenException($controller instanceof ArchiveApiController
			|| $controller instanceof PolicyApiController);
	}

	public function afterException(Controller $controller, string $methodName, \Throwable $exception): Response
	{
		if ($exception instanceof ForbiddenException) {
			if ($exception->isApi) {
				return new JSONResponse(
					['error' => 'Forbidden: membership in the souvera-users group is required.'],
					403,
				);
			}
			return new TemplateResponse('core', '403', [], 'guest');
		}
		throw $exception;
	}
}
