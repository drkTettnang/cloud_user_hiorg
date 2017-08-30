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
namespace OCA\User_Hiorg;

class Hooks
{
	public static function logout()
	{
		$token = \OC::$server->getSession()->get('user_hiorg_token');

		if (isset($token)) {
			$url = \OCA\User_Hiorg\HIORG::SSOURL . "?logout=1&token=$token";

			file_get_contents($url);

			\OC::$server->getSession()->remove('user_hiorg_token');
		}
	}
}
