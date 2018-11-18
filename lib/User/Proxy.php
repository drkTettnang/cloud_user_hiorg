<?php

namespace OCA\User_Hiorg\User;

use OCP\UserInterface;
use OCP\ILogger;

class Proxy implements UserInterface
{
	private $realBackend = null;
	private $hiorg;

	public function __construct(
	  ILogger $logger,
	  $realBackend,
	  $hiorgBackend
   ) {
		$this->logger = $logger;
		$this->realBackend = $realBackend;
		$this->hiorgBackend = $hiorgBackend;
	}

	public function implementsActions($actions)
	{
		return $this->hiorgBackend->implementsActions($actions);
	}

	public function deleteUser($uid)
	{
		return false;
	}

	public function hasUserListings()
	{
		return false;
	}

	public function getUsers($search = '', $limit = null, $offset = null)
	{
		return $this->realBackend->getUsers($search, $limit, $offset);
	}

	public function userExists($uid)
	{
		return $this->realBackend->userExists($uid);
	}

	public function getHome($uid)
	{
		return $this->realBackend->getHome($uid);
	}

	public function getDisplayName($uid)
	{
		return $this->realBackend->getDisplayName($uid);
	}

	public function getDisplayNames($search = '', $limit = null, $offset = null)
	{
		return $this->realBackend->getDisplayNames($search, $limit, $offset);
	}

	public function checkPassword($username, $password)
	{
		$this->logger->debug('Check password for '.$username);

		if ($this->realBackend->userExists($username)) {
			$this->logger->debug('Use real backend.');

			$ret = $this->realBackend->checkPassword($username, $password);

			if ($ret !== false) {
				return $ret;
			}

			$this->logger->debug('Real backend failed.');
		}

		return $this->hiorgBackend->checkPassword($username, $password);
	}

	public function countUsers()
	{
		return $this->realBackend->countUsers();
	}

	public function getBackendName()
	{
		return "HiOrg";
	}
}
