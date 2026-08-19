<?php

declare(strict_types=1);

namespace OCA\SouveraArchive\Command;

use OCA\SouveraArchive\DevOps\SelfUpdateTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * `occ souvera_mailarchiv:self-update` — explicitly pull the newest GitHub
 * version of the Souvera Archive app.
 *
 * Nextcloud's built-in `occ app:update` does nothing for custom apps
 * ("is up-to-date or no updates could be found" — they are not in the
 * App Store), so this is the reliable manual trigger. The pre-update
 * repair step additionally runs on the automatic version-difference
 * path during normal app loading.
 */
class SelfUpdate extends Command {

    use SelfUpdateTrait;

    protected function configure(): void {
        $this
            ->setName('souvera_mailarchiv:self-update')
            ->setDescription('Pull newest GitHub version of Souvera Archive');
    }

    protected function getAppId(): string {
        return 'souvera_mailarchiv';
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        try {
            $output->writeln('checking souvera_mailarchiv …');
            $config = \OCP\Server::get(\OCP\IConfig::class);
            // Explicit manual run: always check, ignore the 24h throttle.
            $config->setAppValue('souvera_mailarchiv', 'devops.last_check', '0');
            $result = $this->checkAndUpdate(true);
            $output->writeln('souvera_mailarchiv: ' . json_encode($result, JSON_UNESCAPED_SLASHES));
            return empty($result['error']) ? Command::SUCCESS : Command::FAILURE;
        } catch (\Throwable $e) {
            $output->writeln('<error>souvera_mailarchiv: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }
}
