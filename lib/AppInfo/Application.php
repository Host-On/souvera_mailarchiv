<?php

declare(strict_types=1);

namespace OCA\SouveraArchive\AppInfo;

use OCA\SouveraArchive\Middleware\GroupRestrictionMiddleware;
use OCA\SouveraArchive\Search\ArchiveSearchProvider;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;

class Application extends App implements IBootstrap
{
	public const APP_ID = 'souvera_mailarchiv';
	public const ALLOWED_GROUP = 'souvera-users';
	public const ADMIN_GROUP   = 'souvera-admins';

	public function __construct()
	{
		parent::__construct(self::APP_ID);
	}

	public function register(IRegistrationContext $context): void
	{
		$context->registerSearchProvider(ArchiveSearchProvider::class);
		$context->registerMiddleware(GroupRestrictionMiddleware::class);
	}

	public function boot(IBootContext $context): void
	{
		// Self-update background job (5 min on the dev channel). Runs
		// independently of any user session (cron executes without one).
		// Failure-tolerant: a job-registration hiccup must never take
		// down the app boot.
		$context->injectFn(function (\OCP\BackgroundJob\IJobList $jobList): void {
			try {
				if (!$jobList->has(\OCA\SouveraArchive\DevOps\SelfUpdateJob::class, null)) {
					$jobList->add(\OCA\SouveraArchive\DevOps\SelfUpdateJob::class);
				}
			} catch (\Throwable $e) {
				\OCP\Server::get(\Psr\Log\LoggerInterface::class)->error(
					'souvera_mailarchiv: SelfUpdateJob registration failed: ' . $e->getMessage(),
					['app' => 'souvera_mailarchiv', 'exception' => $e]
				);
			}
		});
	}
}

