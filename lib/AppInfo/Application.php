<?php

namespace OCA\User_Hiorg\AppInfo;

use OCP\AppFramework\App;
use OCP\IContainer;
use OCA\User_Hiorg\Util\LoggerProxy;
use OCA\User_Hiorg\Hiorg\AndroidRestAPI;
use OCA\User_Hiorg\Hiorg\SingleSignOn;
use OCA\User_Hiorg\User\Proxy;
use OCA\User_Hiorg\User\Hiorg;
use OCA\User_Hiorg\DataRetriever;
use OCA\User_Hiorg\Controller\SettingsController;
use OCA\User_Hiorg\Controller\ViewController;
use OC\User\Database;

class Application extends App
{
	public function __construct(array $urlParams = [])
	{
		parent::__construct('user_hiorg', $urlParams);
		$container = $this->getContainer();

		$container->registerService('Hiorg_Logger', function (IContainer $c) {
			return new LoggerProxy(
				$c->query('AppName'),
				$c->query('OCP\ILogger')
			);
		});

		$container->registerService('Hiorg_AndroidRestAPI', function (IContainer $c) {
			return new AndroidRestAPI(
				$c->query('Hiorg_Logger'),
				$c->query('OCP\IConfig'),
				$c->query('Hiorg_DataRetriever')
			);
		});

		$container->registerService('Hiorg_SingleSignOn', function (IContainer $c) {
			return new SingleSignOn(
				$c->query('Hiorg_Logger'),
				$c->query('OCP\IConfig'),
				$c->getServer()->getSession(),
				$c->query('Hiorg_DataRetriever')
			);
		});

		$container->registerService('Database_Backend', function (IContainer $c) {
			return new Database();
		});

		$container->registerService('Hiorg_Backend', function (IContainer $c) {
			return new Hiorg(
				$c->query('Hiorg_Logger'),
				$c->query('OCP\IConfig'),
				$c->query('OCP\IUserManager'),
				$c->query('OCP\IGroupManager'),
				$c->query('Hiorg_AndroidRestAPI'),
				$c->query('Hiorg_SingleSignOn')
			);
		});

		$container->registerService('Proxy_Backend', function (IContainer $c) {
			return new Proxy(
				$c->query('Hiorg_Backend'),
				$c->query('Hiorg_Logger'),
				$c->query('OCP\IDBConnection'),
				$c->query('OCP\IConfig')
			);
		});

		$container->registerService('Hiorg_DataRetriever', function (IContainer $c) {
			return new DataRetriever();
		});

		/**
		 * Controllers
		 */
		$container->registerService('SettingsController', function (IContainer $c) {
			return new SettingsController(
				$c->query('AppName'),
				$c->query('Request'),
				$c->query('OCP\IConfig')
			);
		});

		$container->registerService('ViewController', function (IContainer $c) {
			return new ViewController(
				$c->query('AppName'),
				$c->query('Request'),
				$c->query('OCP\IConfig'),
				$c->getServer()->getSession()
			);
		});
	}
}
