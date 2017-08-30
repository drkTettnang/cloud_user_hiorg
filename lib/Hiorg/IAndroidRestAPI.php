<?php

namespace OCA\User_Hiorg\Hiorg;

interface IAndroidRestAPI
{
	/**
	* Get user data from REST API (android app).
	*
	* @param {string} $username username
	* @param {string} $password password
	* @return {object|false} Return user data as object if credentials are valid, false otherwise
	*/
	public function getUserData($username, $password);
}
