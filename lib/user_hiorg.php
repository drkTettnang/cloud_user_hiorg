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

/**
 * This class implements a custom user backend which authenticates against @link.
 *
 * @link https://www.hiorg-server.de
 */
class OC_USER_HIORG extends OC_User_Backend {

   private $_realBackend = null;

   public function __construct() {
      $this->_realBackend = new OC_User_Database ();
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
      if ($this->userExists ( $username )) {
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
