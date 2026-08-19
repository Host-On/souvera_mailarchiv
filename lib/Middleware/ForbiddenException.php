<?php

declare(strict_types=1);

namespace OCA\SouveraArchive\Middleware;

class ForbiddenException extends \Exception
{
	public function __construct(
		public readonly bool $isApi,
	) {
		parent::__construct('Forbidden');
	}
}
