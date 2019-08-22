<?php

namespace OCA\User_Hiorg\User;

use OCP\ILogger;
use OCP\IConfig;
use OCP\IUserManager;
use OCP\IUser;
use OCP\IGroupManager;

class Hiorg
{
	// How long should we try our cached username-uid?
	const TIMEOUT_CACHE = 86400; //24h

	private $logger;
	private $config;
	private $userManager;
	private $groupManager;
	private $restAPI;
	private $singleSO;

	public function __construct(
		ILogger $logger,
		IConfig $config,
		IUserManager $userManager,
		IGroupManager $groupManager,
		$restAPI,
		$singleSO
	) {
		$this->logger = $logger;
		$this->config = $config;
		$this->userManager = $userManager;
		$this->groupManager = $groupManager;
		$this->restAPI = $restAPI;
		$this->singleSO = $singleSO;
	}

	/**
	 * Check if we are allowed to use our cached username-uid.
	 *
	 * @param  {string}  $uid user id
	 * @return boolean True if we are in our timeframe
	 */
	public function isInTime($uid)
	{
		$lastLogin = $this->config->getUserValue($uid, 'login', 'lastLogin', 0);

		return ($lastLogin + self::TIMEOUT_CACHE) > time();
	}

	public function checkPassword($backend, $username, $password)
	{
		$this->logger->debug('Use Hiorg to check password.');

		// request user information via sso
		$userInfo = $this->singleSO->getUserInfo($username, $password);

		if ($userInfo === false) {
			$this->logger->info('Wrong Hiorg password for {username}.', ['username' => $username]);

			return false;
		}

		$this->logger->debug('Correct password for {username} ({uid}).', ['username' => $username, 'uid' => $userInfo['user_id']]);

		return $this->updateUser($backend, $userInfo, $username, $password);
	}

	private function updateUser($backend, $userinfo, $username, $password)
	{
		$uid = $userinfo ['user_id'];

		if (! $backend->userExists($uid)) {
			if ($backend->createUser($uid, $username, $password)) {
				$this->logger->info('New user ({uid}) created.', ['uid' => $uid]);
			} else {
				$this->logger->warning('Could not create user ({uid}).', ['uid' => $uid]);

				return false;
			}
		} else {
			// update password
			if ($backend->setPassword($uid, $password)) {
				$this->logger->debug("Password updated.");
			} else {
				$this->logger->warning("Could not update password for user ({uid})!", ['uid' => $uid]);
			}
		}

		// set display name
		$backend->setDisplayName($uid, $userinfo ['vorname'] . ' ' . $userinfo ['name']);

		// set email address
		$this->config->setUserValue($uid, 'settings', 'email', $userinfo['email']);

		$user = $this->userManager->get($uid);

		if (!$this->isInTime($uid) || $this->config->getSystemValue('config') === 'true') {
			// update group memberships
			$this->syncGroupMemberships($user, $this->restAPI->getUserData($username, $password));
		}

		return $uid;
	}

	/**
	* Add user to all groups received from HIORG and remove him from all other groups.
	*
	* @param {string} $uid user id
	* @param {object} $data user data
	*/
	private function syncGroupMemberships(IUser $user, $data)
	{
		if ($data === false) {
			$this->logger->warn('Could not sync group memberships, because no valid user data was provided.');

			return;
		}
		$uid = $user->getUID();

		$userGroups = $this->groupManager->getUserGroupIds($user);
		$remoteGroups = [];
		$userGroupKey = intval($data['env']['grp']);

		foreach ($data['grp'] as $grp) {
			$gid = $grp['n'];
			$gkey = intval($grp['i']);

			if (!$this->groupManager->groupExists($gid)) {
				$this->groupManager->createGroup($gid);
			}

			if (($userGroupKey & $gkey) === $gkey) {
				if (!$this->groupManager->isInGroup($uid, $gid)) {
					$group = $this->groupManager->get($gid);

					$group->addUser($user);
				}

				$remoteGroups[] = $gid;
			}
		}

		// remove non-remote group memberships
		$groupsToRemove = array_diff($userGroups, $remoteGroups);
		foreach ($groupsToRemove as $gid) {
			if ($gid !== 'admin') {
				$group = $this->groupManager->get($gid);

				$group->removeUser($user);
			}
		}

		$this->logger->debug('Group memberships successfully synced.');
	}
}
