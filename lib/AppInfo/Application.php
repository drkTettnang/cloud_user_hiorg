<?php

namespace OCA\User_Hiorg\AppInfo;

use OCP\AppFramework\App;
use OCP\IContainer;
use OCA\User_Hiorg\Util\Cache;
use OCA\User_Hiorg\Util\LoggerProxy;
use OCA\User_Hiorg\Hiorg\AndroidRestAPI;
use OCA\User_Hiorg\Hiorg\SingleSignOn;
use OCA\User_Hiorg\User\Proxy;
use OCA\User_Hiorg\User\Hiorg;
use OC\User\Database;

class Application extends App
{
	private static $config = [];

	public function __construct(array $urlParams = [])
	{
		parent::__construct('user_hiorg', $urlParams);
		$container = $this->getContainer();

		$container->registerService('Hiorg_Cache', function (IContainer $c) {
			return new Cache(
				$c->query('DatabaseConnection')
			);
		});

		$container->registerService('Hiorg_Logger', function (IContainer $c) {
			return new LoggerProxy(
				$c->query('AppName'),
				$c->query('ServerContainer')->getLogger()
			);
		});

		$container->registerService('Hiorg_AndroidRestAPI', function (IContainer $c) {
			return new AndroidRestAPI(
				$c->query('Hiorg_Logger')
			);
		});

		$container->registerService('Hiorg_SingleSignOn', function (IContainer $c) {
			return new SingleSignOn(
				$c->query('Hiorg_Logger'),
				$c->query('ServerContainer')->getConfig()
			);
		});

		$container->registerService('Database_Backend', function (IContainer $c) {
			return new Database();
		});

		$container->registerService('Hiorg_Backend', function (IContainer $c) {
			return new Hiorg(
				$c->query('Database_Backend'),
				$c->query('Hiorg_Cache'),
				$c->query('Hiorg_Logger'),
				$c->query('ServerContainer')->getConfig(),
				$c->query('UserManager'),
				$c->query('GroupManager'),
				$c->query('Hiorg_AndroidRestAPI'),
				$c->query('Hiorg_SingleSignOn')
			);
		});

		$container->registerService('Proxy_Backend', function (IContainer $c) {
			return new Proxy(
				$c->query('Hiorg_Logger'),
				$c->query('Database_Backend'),
				$c->query('Hiorg_Backend')
			);
		});
	}
}
