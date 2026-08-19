<?php

declare(strict_types=1);

namespace OCA\SouveraArchive\Search;

use OCA\SouveraArchive\Db\ArchiveSearchResult;
use OCA\SouveraArchive\Service\ArchiveService;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\Search\IProvider;
use OCP\Search\ISearchQuery;
use OCP\Search\SearchResult;
use OCP\Search\SearchResultEntry;

class ArchiveSearchProvider implements IProvider
{
	public function __construct(
		private ArchiveService $archiveService,
		private IURLGenerator $urlGenerator,
		private IL10N $l10n,
		private IConfig $config,
	) {}

	public function getId(): string
	{
		return 'souvera_mailarchiv';
	}

	public function getName(): string
	{
		return $this->l10n->t('E-Mail-Archiv');
	}

	public function getOrder(string $route, array $routeParameters): int
	{
		return 10;
	}

	public function search(IUser $user, ISearchQuery $query): SearchResult
	{
		$term = $query->getTerm();

		if (mb_strlen($term) < 3) {
			return SearchResult::complete($this->getName(), []);
		}

		$tenantId = $this->config->getSystemValue('souvera_central.tenant_id', 'default');
		$result = $this->archiveService->search($tenantId, [
			'q' => $term,
			'limit' => 10,
			'offset' => 0,
		]);

		$entries = [];
		$items = $result['data'] ?? [];
		foreach ($items as $item) {
			$messageId = $item['id'] ?? '';
			$subject = $item['subject'] ?? $this->l10n->t('Kein Betreff');
			$sender = $item['sender'] ?? '';

			$entries[] = new SearchResultEntry(
				thumbnailUrl: $this->urlGenerator->imagePath('souvera_mailarchiv', 'app.svg'),
				title: $subject,
				subtext: $sender,
				resourceUrl: $this->urlGenerator->linkToRoute('souvera_mailarchiv.page.search', [
					'q' => $term,
				]),
				icon: 'icon-archive',
				isRounded: false,
			);
		}

		return SearchResult::paginated(
			$this->getName(),
			$entries,
			$result['total'] ?? count($entries),
		);
	}
}
