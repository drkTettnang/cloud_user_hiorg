<?php

use OCA\User_Hiorg\AppInfo\Application;

$app = new Application();
$container = $app->getContainer();

$userManager = \OC::$server->getUserManager();
$userManager->clearBackends();
$userManager->registerBackend($container->query('Proxy_Backend'));
