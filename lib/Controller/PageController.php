<?php

declare(strict_types=1);

namespace OCA\SouveraArchive\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

class PageController extends Controller
{
	public function __construct(
		string $appName,
		IRequest $request,
		private LoggerInterface $logger,
	) {
		parent::__construct($appName, $request);
	}

	#[NoCSRFRequired]
	#[NoAdminRequired]
	public function index(): TemplateResponse
	{
		return new TemplateResponse('souvera_mailarchiv', 'main', [
			'initialRoute' => 'dashboard',
		]);
	}

	#[NoCSRFRequired]
	#[NoAdminRequired]
	public function search(): TemplateResponse
	{
		return new TemplateResponse('souvera_mailarchiv', 'main', [
			'initialRoute' => 'search',
		]);
	}
}
