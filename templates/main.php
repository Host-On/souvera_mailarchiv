<?php
declare(strict_types=1);
$appVersion = \OCP\Server::get(\OCP\App\IAppManager::class)->getAppVersion('souvera_mailarchiv');
script('souvera_mailarchiv', 'souvera_mailarchiv-main');
?>
<div id="souvera-archive-content" data-app-version="<?php p($appVersion) ?>"></div>
