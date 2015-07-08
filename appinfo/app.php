<?php

/**
 * ownCloud -user_hiorg
 *
 * @author Klaus Herberth
 * @copyright 2015 Klaus Herberth <klaus@herberth.eu>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

require_once OC_App::getAppPath('user_hiorg').'/lib/hiorg.php';
require_once OC_App::getAppPath('user_hiorg').'/lib/user_hiorg.php';

OCP\Util::connectHook('OC_User', 'logout', '\OCA\user_hiorg\Hooks', 'logout');

OC_User::clearBackends();
OC_User::registerBackend("HIORG");
OC_User::useBackend( "HIORG" );

OCP\App::registerAdmin( 'user_hiorg', 'settings' );

OCP\App::addNavigationEntry( array( 
	'id' => 'user_hiorg',
	'order' => 74,
	'href' => OCP\Util::linkTo( 'user_hiorg', 'index.php' ),
	'icon' => OCP\Util::imagePath( 'user_hiorg', 'hiorg-icon.png' ),
	'name' => 'HiOrg Server'
));
