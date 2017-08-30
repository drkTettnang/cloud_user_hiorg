<?php

use OCA\User_Hiorg\AppInfo\Application;

// OCP\Util::connectHook('OC_User', 'logout', '\OCA\user_hiorg\Hooks', 'logout');

$app = new Application();
$container = $app->getContainer();
// $container->query('UserHooks')->register();

$userManager = \OC::$server->getUserManager();
$userManager->clearBackends();
$userManager->registerBackend($container->query('Proxy_Backend'));

// OCP\App::addNavigationEntry( array(
// 	'id' => 'user_hiorg',
// 	'order' => 74,
// 	'href' => OCP\Util::linkTo( 'user_hiorg', 'index.php' ),
// 	'icon' => OCP\Util::imagePath( 'user_hiorg', 'hiorg-icon.png' ),
// 	'name' => 'HiOrg Server'
// ));
