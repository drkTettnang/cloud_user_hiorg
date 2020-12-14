<?php

namespace OCA\User_Hiorg\AppInfo;

use OCP\AppFramework\App;
use OCP\IUserManager;
use OCA\User_Hiorg\User\Proxy;

class Application extends App
{
	public const ID = 'user_hiorg';

	public function __construct(array $urlParams = [])
	{
		parent::__construct(self::ID, $urlParams);
	}

	public function registerBackend()
	{
		$container = $this->getContainer();

		$userProxy = $container->query(Proxy::class);

		\OC::$server->getUserManager()->registerBackend($userProxy);
	}
}
