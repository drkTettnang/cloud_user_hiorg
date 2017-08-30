<?php

namespace OCA\User_Hiorg\Util;

interface ICache
{
	public function getUid($username);

	public function setUid($username, $uid);
}
