<?php

namespace OCA\User_Hiorg\User;

use OC\User\Backend;
use OCP\ILogger;
use OCP\IDBConnection;
use OCP\IConfig;
use OCP\User\Backend\ABackend;
use OCP\User\Backend\ICheckPasswordBackend;
use OCP\User\Backend\ICountUsersBackend;
use OCP\User\Backend\IGetDisplayNameBackend;
use OCP\User\Backend\IGetHomeBackend;

class Proxy extends ABackend implements
	ICheckPasswordBackend,
	ICountUsersBackend,
	IGetDisplayNameBackend,
	IGetHomeBackend
{
	private $hiorg;
	private $table = 'user_hiorg_users';
	private $cache = [];
	private $config;

	public function __construct(
	  $hiorg,
	  ILogger $logger,
	  IDBConnection $database,
	  IConfig $config
   ) {
		$this->hiorg = $hiorg;
		$this->logger = $logger;
		$this->database = $database;
		$this->config = $config;
	}

	public function implementsActions($actions): bool
	{
		return (bool)((Backend::CHECK_PASSWORD
		| Backend::GET_HOME
		| Backend::GET_DISPLAYNAME
		// | Backend::PROVIDE_AVATAR
		| Backend::COUNT_USERS)
		& $actions);
	}

	public function createUser(string $uid, string $username, string $password, string $displayName = null) : bool
	{
		if ($this->userExists($uid)) {
			return false;
		}

		// $event = new GenericEvent($password);
		// $this->eventDispatcher->dispatch('OCP\PasswordPolicy::validate', $event);

		$qb = $this->database->getQueryBuilder();
		$qb->insert($this->table)
			->values([
				'uid' => $qb->createNamedParameter($uid),
				'username' => $qb->createNamedParameter($username),
				'password' => $qb->createNamedParameter(\OC::$server->getHasher()->hash($password)),
				'displayname' => $qb->createNamedParameter($displayName),
			]);
		$result = $qb->execute();

		// Clear cache
		unset($this->cache[$uid]);

		$this->logger->info("created user $uid: " . $result);

		return $result ? true : false;
	}

	/**
	 * @inheritdoc
	 */
	public function deleteUser($uid)
	{
		return false;
	}

	/**
	 * @inheritdoc
	 */
	public function setPassword(string $uid, string $password): bool
	{
		if (!$this->userExists($uid)) {
			return false;
		}

		// $event = new GenericEvent($password);
		// $this->eventDispatcher->dispatch('OCP\PasswordPolicy::validate', $event);

		$hashedPassword = \OC::$server->getHasher()->hash($password);

		return $this->updatePassword($uid, $hashedPassword);
	}

	public function setDisplayName(string $uid, string $displayName): bool
	{
		if (!$this->userExists($uid)) {
			return false;
		}

		$query = $this->database->getQueryBuilder();
		$query->update($this->table)
			->set('displayname', $query->createNamedParameter($displayName))
			->where($query->expr()->eq('uid', $query->createNamedParameter($uid)));
		$query->execute();
		$this->cache[$uid]['displayname'] = $displayName;

		return true;
	}

	/**
	 * @inheritdoc
	 */
	public function getDisplayName($uid): string
	{
		$uid = (string)$uid;

		$this->loadUser($uid);

		if (!empty($this->cache[$uid]['displayname'])) {
			return $this->cache[$uid]['displayname'];
		}

		if (!empty($this->cache[$uid]['username'])) {
			return $this->cache[$uid]['username'];
		}

		return $uid;
	}

	/**
	 * @inheritdoc
	 */
	public function getDisplayNames($search = '', $limit = null, $offset = null)
	{
		$query = $this->database->getQueryBuilder();
		$query->select('uid', 'displayname')
			->from($this->table)
			->where($query->expr()->iLike('username', $query->createPositionalParameter('%' . $this->database->escapeLikeParameter($search) . '%')))
			->orWhere($query->expr()->iLike('displayname', $query->createPositionalParameter('%' . $this->database->escapeLikeParameter($search) . '%')))
			->orderBy($query->func()->lower('displayname'), 'ASC')
			->setMaxResults($limit)
			->setFirstResult($offset);

		$result = $query->execute();
		$displayNames = [];
		while ($row = $result->fetch()) {
			$displayNames[(string)$row['uid']] = (string)$row['displayname'];
		}

		return $displayNames;
	}

	/**
	 * @inheritdoc
	 */
	public function checkPassword(string $username, string $password)
	{
		$qb = $this->database->getQueryBuilder();
		$qb->select('uid', 'password')
			->from($this->table)
			->where(
				$qb->expr()->eq(
					'username',
					$qb->createNamedParameter($username)
				)
			);

		$result = $qb->execute();
		$row = $result->fetch();
		$result->closeCursor();

		if ($row) {
			$uid = $row['uid'];

			if ($this->hiorg->isInTime($uid)) {
				$storedHash = $row['password'];
				$newHash = '';

				if (\OC::$server->getHasher()->verify($password, $storedHash, $newHash)) {
					if (!empty($newHash)) {
						$this->updatePassword($uid, $newHash);
					}

					$this->logger->info("Cached password for $username is valid. Uid is " . $row['uid']);

					return (string)$row['uid'];
				}
			}
		}

		return $this->hiorg->checkPassword($this, $username, $password);
	}

	/**
	 * @inheritdoc
	 */
	public function getUsers($search = '', $limit = null, $offset = null)
	{
		$users = $this->getDisplayNames($search, $limit, $offset);

		$userIds = array_map(function ($uid) {
			return (string)$uid;
		}, array_keys($users));

		sort($userIds, SORT_STRING | SORT_FLAG_CASE);

		return $userIds;
	}

	/**
	 * @inheritdoc
	 */
	public function userExists($uid)
	{
		$this->loadUser($uid);

		return $this->cache[$uid] !== false;
	}

	/**
	 * @inheritdoc
	 */
	public function getHome(string $uid)
	{
		if ($this->userExists($uid)) {
			$root = \OC::$server->getConfig()->getSystemValue('datadirectory', \OC::$SERVERROOT . '/data');

			return $root . '/' . $uid;
		}

		return false;
	}

	/**
	 * @inheritdoc
	 */
	public function hasUserListings()
	{
		return false;
	}

	/**
	 * @inheritdoc
	 */
	public function countUsers()
	{
		$query = $this->database->getQueryBuilder();
		$query->select($query->func()->count('uid'))
			->from($this->table);
		$result = $query->execute();

		return $result->fetchColumn();
	}

	/**
	 * @inheritdoc
	 */
	public function getBackendName()
	{
		return "HiOrg";
	}

	private function loadUser(string $uid)
	{
		if (isset($this->cache[$uid])) {
			return true;
		}

		//guests $uid could be NULL or ''
		if ($uid === '') {
			$this->cache[$uid] = false;
			return true;
		}

		$qb = $this->database->getQueryBuilder();
		$qb->select('uid', 'username', 'displayname')
			->from($this->table)
			->where(
				$qb->expr()->eq(
					'uid',
					$qb->createNamedParameter($uid)
				)
			);
		$result = $qb->execute();
		$row = $result->fetch();
		$result->closeCursor();

		$this->cache[$uid] = false;

		if ($row !== false) {
			$this->cache[$uid]['uid'] = (string)$row['uid'];
			$this->cache[$uid]['displayname'] = (string)$row['displayname'];
		} else {
			$this->logger->warning("Could not load user with uid $uid");

			return false;
		}
	}

	private function updatePassword(string $uid, string $passwordHash): bool
	{
		$query = $this->database->getQueryBuilder();
		$query->update($this->table)
			->set('password', $query->createNamedParameter($passwordHash))
			->where($query->expr()->eq('uid', $query->createNamedParameter(mb_strtolower($uid))));
		$result = $query->execute();

		return $result ? true : false;
	}
}
