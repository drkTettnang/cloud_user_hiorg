<?php

// require_once OC_App::getAppPath('user_hiorg').'/lib/hiorg.php';
// require_once OC_App::getAppPath('user_hiorg').'/lib/user_hiorg.php';

// OCP\Util::connectHook('OC_User', 'logout', '\OCA\user_hiorg\Hooks', 'logout');

OCA\User_Hiorg\HiorgBackend::register();

// OCP\App::addNavigationEntry( array(
// 	'id' => 'user_hiorg',
// 	'order' => 74,
// 	'href' => OCP\Util::linkTo( 'user_hiorg', 'index.php' ),
// 	'icon' => OCP\Util::imagePath( 'user_hiorg', 'hiorg-icon.png' ),
// 	'name' => 'HiOrg Server'
// ));
