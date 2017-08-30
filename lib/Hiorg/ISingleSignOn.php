<?php

namespace OCA\User_Hiorg\Hiorg;

interface ISingleSignOn
{
	/**
	* Get user information from SSO.
	*
	* @param {string} $username username
	* @param {string} $password password
	* @return {array|false} Return user information if credentials are valid, false otherwise
	*/
	public function getUserInfo($username, $password);
}
