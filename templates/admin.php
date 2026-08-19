<?php

declare(strict_types=1);

/**
 * Souvera Archive — Admin Settings Template
 *
 * Verweist auf Souvera Central für die vollständige Archiv-Konfiguration.
 */

script('souvera_mailarchiv', 'souvera_mailarchiv-admin-settings');
?>

<div id="souvera-archive-admin">
	<p>
		Die E-Mail-Archiv-Einstellungen werden zentral in der
		<a href="<?php p($_['centralUrl']); ?>">Souvera Central Verwaltung</a>
		konfiguriert.
	</p>
</div>
