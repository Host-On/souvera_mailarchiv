<?php

declare(strict_types=1);

namespace OCA\SouveraArchive\Service;

use OCA\SouveraArchive\AppInfo\Application;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserSession;

class AccessControl
{
	public function __construct(
		private IGroupManager $groupManager,
		private IUserSession $userSession,
	) {}

	public function isCurrentUserAllowed(): bool
	{
		return $this->isAllowed($this->userSession->getUser());
	}

	public function isAllowed(?IUser $user): bool
	{
		if ($user === null) {
			return false;
		}
		if ($this->groupManager->isAdmin($user->getUID())) {
			return true;
		}
		return $this->groupManager->isInGroup($user->getUID(), Application::ALLOWED_GROUP)
			|| $this->groupManager->isInGroup($user->getUID(), Application::ADMIN_GROUP);
	}

	public function isCurrentUserAdmin(): bool
	{
		return $this->isAdmin($this->userSession->getUser());
	}

	public function isAdmin(?IUser $user): bool
	{
		if ($user === null) {
			return false;
		}
		if ($this->groupManager->isAdmin($user->getUID())) {
			return true;
		}
		return $this->groupManager->isInGroup($user->getUID(), Application::ADMIN_GROUP);
	}
}
