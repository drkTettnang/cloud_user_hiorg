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

namespace OCA\user_hiorg;

use OC\User\Backend;

/**
 * This class implements a custom user backend which authenticates against @link.
 *
 * @link https://www.hiorg-server.de
 */
class User_HiOrg implements \OCP\UserInterface {

   private $_realBackend = null;

   public function __construct() {
      $this->_realBackend = new \OC\User\Database();
   }

   /**
	* Check if backend implements actions
	* @param int $actions bitwise-or'ed actions
	* @return boolean
	*
	* Returns the supported actions as int to be
	* compared with OC_USER_BACKEND_CREATE_USER etc.
	*/
	public function implementsActions($actions) {
		return (bool)((Backend::CHECK_PASSWORD
			| Backend::GET_HOME
			| Backend::GET_DISPLAYNAME
			// | Backend::PROVIDE_AVATAR
			| Backend::COUNT_USERS)
			& $actions);
	}

   /**
	 * delete a user
	 * @param string $uid The username of the user to delete
	 * @return bool
	 * @since 4.5.0
	 */
	public function deleteUser($uid) {
      return false;
   }

   /**
	 * Check if a user list is available or not
	 * @return boolean if users can be listed or not
	 * @since 4.5.0
	 */
	public function hasUserListings() {
      return false;
   }

   public function getUsers($search = '', $limit = null, $offset = null) {
      return $this->_realBackend->getUsers ( $search, $limit, $offset );
   }

   public function userExists($uid) {
      return $this->_realBackend->userExists ( $uid );
   }

   public function getHome($uid) {
      return $this->_realBackend->getHome ( $uid );
   }

   public function getDisplayName($uid) {
      return $this->_realBackend->getDisplayName ( $uid );
   }

   public function getDisplayNames($search = '', $limit = null, $offset = null) {
      return $this->_realBackend->getDisplayNames ( $search, $limit, $offset );
   }

   public function checkPassword($username, $password) {
      if ($this->_realBackend->userExists ( $username )) {
         HIORG::info('Use real backend.');

         $ret = $this->_realBackend->checkPassword ( $username, $password );

         if ($ret !== false)
            return $ret;

         HIORG::info('Real backend failed.');
      }

      return HIORG::checkPassword($this->_realBackend, $username, $password);
   }

   public function countUsers() {
      return $this->_realBackend->countUsers ();
   }

   public function getBackendName() {
      return "HiOrg-Server";
   }
}

?>
