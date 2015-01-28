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

OCP\User::checkLoggedIn();

$token = \OC::$server->getSession()->get('user_hiorg_token');
$ov = OCP\Config::getAppValue ( 'user_hiorg', 'ov' );

OCP\App::setActiveNavigationEntry( 'user_hiorg' );

$url = OC_USER_HIORG::SSOURL."?ov=$ov&login=1&token=$token";

$tmpl = new OCP\Template( 'user_hiorg', 'main', 'user' );
$tmpl->assign( 'url', $url );
$tmpl->printPage();
