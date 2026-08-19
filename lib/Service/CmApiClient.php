<?php

declare(strict_types=1);

namespace OCA\SouveraArchive\Service;

use OCP\Http\Client\IClientService;
use OCP\IConfig;
use Psr\Log\LoggerInterface;

class CmApiClient
{
	private const TIMEOUT = 15;

	public function __construct(
		private IClientService $clientService,
		private IConfig $config,
		private LoggerInterface $logger,
	) {}

	private function getBaseUrl(): ?string
	{
		$url = $this->config->getSystemValue('souvera_central.cm_api_url', '');
		return $url ? rtrim($url, '/') : null;
	}

	private function getApiKey(): ?string
	{
		return $this->config->getSystemValue('souvera_central.cm_api_key', null);
	}

	public function get(string $path, array $query = []): ?array
	{
		$base = $this->getBaseUrl();
		if (!$base) {
			$this->logger->warning('CmApiClient: CM API URL not configured');
			return null;
		}
		$client = $this->clientService->newClient();
		try {
			$url = "{$base}/{$path}";
			if ($query) {
				$url .= '?' . http_build_query($query);
			}
			$response = $client->get($url, [
				'headers' => [
					'Authorization' => 'Bearer ' . $this->getApiKey(),
					'Accept' => 'application/json',
				],
				'timeout' => self::TIMEOUT,
				'verify' => false,
			]);
			return json_decode($response->getBody(), true);
		} catch (\Exception $e) {
			$this->logger->error('CmApiClient GET failed: ' . $e->getMessage());
			return null;
		}
	}

	public function post(string $path, array $body = []): ?array
	{
		$base = $this->getBaseUrl();
		if (!$base) {
			return null;
		}
		$client = $this->clientService->newClient();
		try {
			$response = $client->post("{$base}/{$path}", [
				'headers' => [
					'Authorization' => 'Bearer ' . $this->getApiKey(),
					'Content-Type' => 'application/json',
					'Accept' => 'application/json',
				],
				'json' => $body,
				'timeout' => self::TIMEOUT,
				'verify' => false,
			]);
			return json_decode($response->getBody(), true);
		} catch (\Exception $e) {
			$this->logger->error('CmApiClient POST failed: ' . $e->getMessage());
			return null;
		}
	}

	public function delete(string $path): bool
	{
		$base = $this->getBaseUrl();
		if (!$base) {
			return false;
		}
		$client = $this->clientService->newClient();
		try {
			$client->delete("{$base}/{$path}", [
				'headers' => [
					'Authorization' => 'Bearer ' . $this->getApiKey(),
					'Accept' => 'application/json',
				],
				'timeout' => self::TIMEOUT,
				'verify' => false,
			]);
			return true;
		} catch (\Exception $e) {
			$this->logger->error('CmApiClient DELETE failed: ' . $e->getMessage());
			return false;
		}
	}
}
