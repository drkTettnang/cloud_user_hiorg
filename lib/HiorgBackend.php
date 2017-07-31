<?php

namespace OCA\User_Hiorg;

use OCA\User_Hiorg\Util\Cache;
use OCA\User_Hiorg\Util\LoggerProxy;
use OCA\User_Hiorg\Hiorg\AndroidRestAPI;
use OCA\User_Hiorg\Hiorg\SingleSignOn;
use OCA\User_Hiorg\User\Proxy;
use OCA\User_Hiorg\User\Hiorg;
use OC\User\Database;

class HiorgBackend {
   public static function register() {

      $userManager = \OC::$server->getUserManager();

      $cache = new Cache(
      	\OC::$server->getDatabaseConnection()
      );

      $logger = new LoggerProxy(
      	'user_hiorg',
      	\OC::$server->getLogger()
      );
\OC::$server->getLogger()->info('foobar');
      $restAPI = new AndroidRestAPI(
      	$logger
      );

      $singleSO = new SingleSignOn(
      	$logger,
      	\OC::$server->getConfig()
      );

      $realBackend = new Database();

      $hiorgBackend = new Hiorg(
         $realBackend,
         $cache,
         $logger,
         \OC::$server->getConfig(),
         $userManager,
         \OC::$server->getGroupManager(),
         $restAPI,
         $singleSO
      );

      $userBackend = new Proxy(
      	$logger,
         $realBackend,
         $hiorgBackend
      );

      $userManager->clearBackends();
      $userManager->registerBackend($userBackend);
   }
}
