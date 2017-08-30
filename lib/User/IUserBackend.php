<?php

interface IHiorgUserBackend
{
	public function createUser($uid, $password);
	public function deleteUser($uid);
	public function setPassword($uid, $password);
	public function setDisplayName($uid, $displayName);
	public function getDisplayName($uid);
	public function getDisplayNames($search = '', $limit = null, $offset = null);
	public function checkPassword($uid, $password);
	public function getUsers($search = '', $limit = null, $offset = null);
	public function userExists($uid);
	public function getHome($uid);
	public function hasUserListings();
	public function countUsers();
	public function loginName2UserName($loginName);
	public function getBackendName();
}
