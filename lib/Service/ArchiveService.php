<?php

declare(strict_types=1);

namespace OCA\SouveraArchive\Service;

use OCP\IConfig;
use Psr\Log\LoggerInterface;

class ArchiveService
{
	public function __construct(
		private IConfig $config,
		private LoggerInterface $logger,
	) {}

	private function getAgentUrl(): ?string
	{
		return $this->config->getSystemValue('souvera_central.archive_agent_url', null);
	}

	private function agentGet(string $path, array $query = []): ?array
	{
		$agentUrl = $this->getAgentUrl();
		if (!$agentUrl) {
			return null;
		}
		try {
			$client = \OCP\Server::get(\OCP\Http\Client\IClientService::class)->newClient();
			$url = rtrim($agentUrl, '/') . '/' . $path;
			if ($query) {
				$url .= '?' . http_build_query($query);
			}
			$response = $client->get($url, [
				'timeout' => 15,
				'verify' => false,
			]);
			return json_decode($response->getBody(), true);
		} catch (\Exception $e) {
			$this->logger->error("Archive agent GET {$path} failed: " . $e->getMessage());
			return null;
		}
	}

	private function agentPost(string $path, array $body = []): ?array
	{
		$agentUrl = $this->getAgentUrl();
		if (!$agentUrl) {
			return null;
		}
		try {
			$client = \OCP\Server::get(\OCP\Http\Client\IClientService::class)->newClient();
			$response = $client->post(rtrim($agentUrl, '/') . '/' . $path, [
				'json' => $body,
				'timeout' => 30,
				'verify' => false,
			]);
			return json_decode($response->getBody(), true);
		} catch (\Exception $e) {
			$this->logger->error("Archive agent POST {$path} failed: " . $e->getMessage());
			return null;
		}
	}

	private function agentDelete(string $path): bool
	{
		$agentUrl = $this->getAgentUrl();
		if (!$agentUrl) {
			return false;
		}
		try {
			$client = \OCP\Server::get(\OCP\Http\Client\IClientService::class)->newClient();
			$client->delete(rtrim($agentUrl, '/') . '/' . $path, [
				'timeout' => 15,
				'verify' => false,
			]);
			return true;
		} catch (\Exception $e) {
			$this->logger->error("Archive agent DELETE {$path} failed: " . $e->getMessage());
			return false;
		}
	}

	// ── Search ──────────────────────────────────────────────────────

	public function search(string $tenantId, array $query = []): ?array
	{
		$agentUrl = $this->getAgentUrl();
		if (!$agentUrl) {
			$this->logger->warning('Archive agent URL not configured for search');
			return ['data' => [], 'total' => 0, 'error' => 'Archive-Agent-URL nicht konfiguriert.'];
		}
		try {
			$client = \OCP\Server::get(\OCP\Http\Client\IClientService::class)->newClient();
			$params = [];
			$paramKeys = ['q', 'sender', 'recipient', 'subject', 'date_from', 'date_to', 'limit', 'offset', 'user_email'];
			foreach ($paramKeys as $key) {
				if (isset($query[$key]) && $query[$key] !== '') {
					if ($key === 'limit') {
						$params['limit'] = min((int)$query['limit'], 200);
					} elseif ($key === 'offset') {
						$params['offset'] = max(0, (int)$query['offset']);
					} else {
						$params[$key] = $query[$key];
					}
				}
			}
			$response = $client->get(
				rtrim($agentUrl, '/') . '/search?' . http_build_query($params),
				['timeout' => 15, 'verify' => false]
			);
			$body = json_decode($response->getBody(), true);
			if (!is_array($body)) {
				$this->logger->warning('Archive agent search returned invalid JSON');
				return ['data' => [], 'total' => 0, 'error' => 'Ungültige Antwort vom Archive-Agent.'];
			}
			return [
				'data' => $body['results'] ?? [],
				'total' => $body['total'] ?? 0,
			];
		} catch (\Exception $e) {
			$this->logger->error('Archive agent search failed: ' . $e->getMessage());
			return ['data' => [], 'total' => 0, 'error' => 'Archive-Agent nicht erreichbar: ' . $e->getMessage()];
		}
	}

	// ── Messages ────────────────────────────────────────────────────

	public function getMessage(string $tenantId, string $messageId): ?array
	{
		return $this->agentGet("messages/{$messageId}");
	}

	public function getDownloadUrl(string $tenantId, string $messageId): ?string
	{
		$agentUrl = $this->getAgentUrl();
		return $agentUrl ? rtrim($agentUrl, '/') . "/messages/{$messageId}/download" : null;
	}

	// ── Status / Integrity ──────────────────────────────────────────

	public function getStatus(string $tenantId): ?array
	{
		return $this->agentGet("status");
	}

	public function getIntegrityStatus(string $tenantId): ?array
	{
		return $this->agentGet("integrity");
	}

	public function verifyMessageIntegrity(string $tenantId, string $messageId): ?array
	{
		return $this->agentGet("integrity/verify/{$messageId}");
	}

	// ── Resync ──────────────────────────────────────────────────────

	public function requestResync(string $tenantId): ?array
	{
		return $this->agentPost("resync");
	}

	// ── Restore ─────────────────────────────────────────────────────

	public function restoreMessage(string $tenantId, string $messageId, string $targetEmail): ?array
	{
		$agentUrl = $this->getAgentUrl();
		if (!$agentUrl) {
			return ['error' => 'Archive-Agent-URL nicht konfiguriert.'];
		}

		$eml = $this->downloadEml($agentUrl, $messageId);
		if ($eml === null) {
			return ['error' => 'EML konnte nicht vom Agent geladen werden.'];
		}

		return $this->jmapImportEmail($eml, $targetEmail);
	}

	private function downloadEml(string $agentUrl, string $messageId): ?string
	{
		try {
			$client = \OCP\Server::get(\OCP\Http\Client\IClientService::class)->newClient();
			$response = $client->get(rtrim($agentUrl, '/') . "/messages/{$messageId}/download", [
				'timeout' => 30,
				'verify' => false,
			]);
			return (string) $response->getBody();
		} catch (\Exception $e) {
			$this->logger->error('EML download failed: ' . $e->getMessage());
			return null;
		}
	}

	private function jmapImportEmail(string $eml, string $targetEmail): ?array
	{
		$stalwartApiUrl = $this->config->getSystemValue('souvera_central.stalwart_api_url', '');
		$stalwartUser = $this->config->getSystemValue('souvera_central.stalwart_admin_user', '');
		$stalwartPass = $this->config->getSystemValue('souvera_central.stalwart_admin_password', '');

		if (!$stalwartApiUrl || !$stalwartUser || !$stalwartPass) {
			return ['error' => 'Stalwart-Zugangsdaten nicht konfiguriert.'];
		}

		try {
			$client = \OCP\Server::get(\OCP\Http\Client\IClientService::class)->newClient();

			$sessionUrl = rtrim($stalwartApiUrl, '/') . '/jmap/session';
			$sessionRes = $client->get($sessionUrl, [
				'auth' => [$stalwartUser, $stalwartPass],
				'timeout' => 10,
				'verify' => false,
			]);
			$session = json_decode((string) $sessionRes->getBody(), true);
			$apiUrl = $session['apiUrl'] ?? null;
			$accountId = $session['primaryAccounts']['urn:ietf:params:jmap:mail'] ?? null;

			if (!$apiUrl || !$accountId) {
				$this->logger->warning('Restore: could not resolve JMAP session');
				return ['error' => 'JMAP-Session konnte nicht aufgelöst werden.'];
			}

			$blobId = $this->jmapUpload($client, $apiUrl, $accountId, $stalwartUser, $stalwartPass, $eml);
			if (!$blobId) {
				return ['error' => 'EML-Upload zu Stalwart fehlgeschlagen.'];
			}

			$importRes = $client->post($apiUrl, [
				'auth' => [$stalwartUser, $stalwartPass],
				'json' => [
					'using' => ['urn:ietf:params:jmap:core', 'urn:ietf:params:jmap:mail'],
					'methodCalls' => [[
						'Email/import',
						[
							'accountId' => $accountId,
							'emails' => [
								$targetEmail => [
									'blobId' => $blobId,
									'mailboxIds' => ['inbox' => true],
								],
							],
						],
						'0',
					]],
				],
				'timeout' => 15,
				'verify' => false,
			]);
			$importResult = json_decode((string) $importRes->getBody(), true);

			$this->logger->info('Restore: email {id} restored to {target}', [
				'id' => $messageId,
				'target' => $targetEmail,
			]);

			return [
				'message' => "Nachricht wiederhergestellt in {$targetEmail}.",
				'jmap' => $importResult,
			];
		} catch (\Exception $e) {
			$this->logger->error('Restore failed: ' . $e->getMessage());
			return ['error' => 'Wiederherstellung fehlgeschlagen: ' . $e->getMessage()];
		}
	}

	private function jmapUpload($client, string $apiUrl, string $accountId, string $user, string $pass, string $eml): ?string
	{
		$uploadUrl = preg_replace('#/jmap$#', '/upload', $apiUrl) . '/' . $accountId . '/';
		$uploadRes = $client->post($uploadUrl, [
			'auth' => [$user, $pass],
			'body' => $eml,
			'headers' => ['Content-Type' => 'message/rfc822'],
			'timeout' => 15,
			'verify' => false,
		]);
		$uploadResult = json_decode((string) $uploadRes->getBody(), true);
		return $uploadResult['blobId'] ?? null;
	}

	// ── Export ──────────────────────────────────────────────────────

	public function requestExport(string $tenantId, array $params): ?array
	{
		return $this->agentPost("export", $params);
	}

	public function getExportStatus(string $tenantId, string $jobId): ?array
	{
		return $this->agentGet("export/{$jobId}/status");
	}

	// ── Audit Log ───────────────────────────────────────────────────

	public function getAuditLog(string $tenantId, int $limit = 50, int $offset = 0): ?array
	{
		return $this->agentGet("audit-log", [
			'limit' => $limit,
			'offset' => $offset,
		]);
	}

	// ── Policy ──────────────────────────────────────────────────────

	public function getPolicy(string $tenantId): ?array
	{
		return $this->agentGet("policy");
	}

	public function updatePolicy(string $tenantId, array $data): ?array
	{
		return $this->agentPost("policy", $data);
	}

	// ── Legal Hold ──────────────────────────────────────────────────

	public function setLegalHold(string $tenantId, string $userId): ?array
	{
		return $this->agentPost("legal-hold/{$userId}");
	}

	public function removeLegalHold(string $tenantId, string $userId): ?array
	{
		return $this->agentDelete("legal-hold/{$userId}") ? [] : null;
	}
}
