<?php

namespace OCA\User_Hiorg\Util;

use OCP\IDBConnection;

class Cache implements ICache
{
	private $database;

	public function __construct(
	  IDBConnection $database
   ) {
		$this->database = $database;
	}

	public function getUid($username)
	{
		$sql = 'SELECT uid FROM `*PREFIX*user_hiorg_username_uid` WHERE username = ?';
		$args = [$username];

		$query = $this->database->prepare($sql);

		if ($query->execute($args)) {
			$row = $query->fetchAll();

			return (count($row) > 0) ? $row[0]['uid'] : false;
		}

		return false;
	}

	public function setUid($username, $uid)
	{
		$this->database->executeUpdate('DELETE FROM `*PREFIX*user_hiorg_username_uid` WHERE username = ?', [$username]);

		$sql = 'INSERT INTO `*PREFIX*user_hiorg_username_uid` (username, uid) VALUES (?, ?)';
		$result = $this->database->executeUpdate($sql, [$username, $uid]);

		return $result;
	}
}
