<?php

declare(strict_types=1);

return [
	'routes' => [
		['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
		['name' => 'page#search', 'url' => '/search', 'verb' => 'GET'],

		['name' => 'archive_api#search', 'url' => '/api/archive/search', 'verb' => 'GET'],
		['name' => 'archive_api#get', 'url' => '/api/archive/messages/{id}', 'verb' => 'GET'],
		['name' => 'archive_api#download', 'url' => '/api/archive/messages/{id}/download', 'verb' => 'GET'],
		['name' => 'archive_api#export', 'url' => '/api/archive/export', 'verb' => 'POST'],
		['name' => 'archive_api#exportStatus', 'url' => '/api/archive/export/{jobId}/status', 'verb' => 'GET'],

		['name' => 'policy_api#get', 'url' => '/api/archive/policy', 'verb' => 'GET'],
		['name' => 'policy_api#update', 'url' => '/api/archive/policy', 'verb' => 'PUT'],
		['name' => 'policy_api#legalHold', 'url' => '/api/archive/legal-hold/{userId}', 'verb' => 'POST'],
		['name' => 'policy_api#legalHoldRemove', 'url' => '/api/archive/legal-hold/{userId}', 'verb' => 'DELETE'],

		['name' => 'archive_api#integrityStatus', 'url' => '/api/archive/integrity', 'verb' => 'GET'],
		['name' => 'archive_api#integrityVerify', 'url' => '/api/archive/integrity/verify/{messageId}', 'verb' => 'GET'],
		['name' => 'archive_api#status', 'url' => '/api/archive/status', 'verb' => 'GET'],

		['name' => 'audit_api#list', 'url' => '/api/archive/audit-log', 'verb' => 'GET'],

		['name' => 'archive_api#resync', 'url' => '/api/archive/resync', 'verb' => 'POST'],
		['name' => 'archive_api#restore', 'url' => '/api/archive/restore/{id}', 'verb' => 'POST'],
	]
];
