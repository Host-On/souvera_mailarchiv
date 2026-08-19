<?php

declare(strict_types=1);

namespace OCA\SouveraArchive\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\Settings\ISettings;

/**
 * Admin-Einstellungen für das Souvera E-Mail-Archiv.
 *
 * Die eigentliche Konfiguration (Retention, Auto-Delete, S3-Bucket)
 * erfolgt zentral in Souvera Central (§2.2 ARCHIVE_PLAN). Diese
 * AdminSection zeigt lediglich den aktuellen Archiv-Status und einen
 * Link zur Central-Admin-Oberfläche.
 */
class AdminSection implements ISettings
{
	public function __construct(
		private \OCP\IURLGenerator $urlGenerator,
		private \OCP\IConfig $config,
	) {}

	public function getForm(): TemplateResponse
	{
		return new TemplateResponse('souvera_mailarchiv', 'admin', [
			'centralUrl' => $this->urlGenerator->linkToRoute('souvera_central.page.settings'),
			'enabled' => $this->isArchiveEnabled(),
		], 'blank');
	}

	public function getSection(): string
	{
		if (!$this->isArchiveEnabled()) {
			return '';
		}
		return 'souvera_mailarchiv';
	}

	public function getPriority(): int
	{
		return 50;
	}

	/**
	 * Prüft ob das Archiv für diesen Tenant gebucht/aktiviert ist.
	 * Wird vom CloudManager via CM-API auf souvera_central gesetzt.
	 */
	private function isArchiveEnabled(): bool
	{
		return $this->config->getAppValue('souvera_central', 'archive.enabled', '0') === '1';
	}
}
