<?php

declare(strict_types=1);

namespace OCA\SouveraArchive\Service;

use OCP\IUserSession;
use Psr\Log\LoggerInterface;

class IdentityDiscoveryService
{
	private array $cache = [];

	public function __construct(
		private IUserSession $userSession,
		private LoggerInterface $logger,
	) {}

	public function discover(): array
	{
		$user = $this->userSession->getUser();
		if ($user === null) {
			return [];
		}

		$userId = $user->getUID();
		$cacheKey = 'identities:' . $userId;
		if (isset($this->cache[$cacheKey])) {
			return $this->cache[$cacheKey];
		}

		$ncEmail = $user->getEMailAddress();
		if ($ncEmail === null || $ncEmail === '') {
			return [];
		}

		$emails = [$ncEmail];

		if ($this->centralAvailable()) {
			try {
				$deadline = \time() + 15;
				$stalwart = \OCP\Server::get('OCA\SouveraCentral\Service\StalwartService');

				$userEmails = $stalwart->getEmails($ncEmail);
				foreach ($userEmails as $e) {
					if ($e !== '' && !\in_array($e, $emails, true)) {
						$emails[] = $e;
					}
				}

				if (\time() > $deadline) {
					return $this->cache[$cacheKey] = $emails;
				}

				$accountId = $stalwart->findAccountId($ncEmail, 'User');
				if ($accountId !== null) {
					$account = $stalwart->getAccountById($accountId);

					$groupIds = \is_array($account['memberGroupIds'] ?? null)
						? \array_keys($account['memberGroupIds'])
						: [];

					if (!empty($groupIds)) {
						$groups = $stalwart->jmapSingle('x:Account/get', [
							'ids' => $groupIds,
							'properties' => ['emailAddress', 'name'],
						]);
						foreach ($groups['list'] ?? [] as $group) {
							$gEmail = \trim((string) ($group['emailAddress'] ?? ''));
							if ($gEmail !== '' && !\in_array($gEmail, $emails, true)) {
								$emails[] = $gEmail;
							}
						}
					}
				}
			} catch (\Throwable $e) {
				$this->logger->warning('IdentityDiscovery: Stalwart lookup failed', [
					'exception' => $e->getMessage(),
				]);
			}
		}

		$this->logger->debug('IdentityDiscovery: found identities for {user}', [
			'user' => $userId,
			'count' => count($emails),
		]);

		return $this->cache[$cacheKey] = $emails;
	}

	private function centralAvailable(): bool
	{
		return \class_exists('OCA\SouveraCentral\Service\StalwartService');
	}
}
