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
   const URL = 'https://www.hiorg-server.de/';
   const SSOURL = 'https://www.hiorg-server.de/logmein.php';

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
         OC_Log::write ( 'user_hiorg', 'use real backend.', OC_Log::INFO );
         
         $ret = $this->_realBackend->checkPassword ( $username, $password );
         
         if ($ret !== false)
            return $ret;
         
         OC_Log::write ( 'user_hiorg', 'real backend failed.', OC_Log::INFO );
      }
      
      $reqUserinfo = array (
            'name',
            'vorname',
            'gruppe',
            'perms',
            'username',
            'email',
            'user_id' 
      );
      $reqParam = http_build_query ( array (
            'ov' => OCP\Config::getAppValue ( 'user_hiorg', 'ov' ),
            'weiter' => self::SSOURL,
            'getuserinfo' => implode ( ',', $reqUserinfo ) 
      ) );
      
      $context = stream_context_create ( array (
            'http' => array (
                  'method' => 'POST',
                  'header' => 'Content-type: application/x-www-form-urlencoded',
                  'content' => http_build_query ( array (
                        'username' => $username,
                        'password' => $password,
                        'submit' => 'Login' 
                  ), '', '&' ) 
            ) 
      ) );
      
      $result = file_get_contents ( self::SSOURL . '?' . $reqParam, false, $context );
      
      if (mb_substr ( $result, 0, 2 ) != 'OK') {
         OC_Log::write ( 'user_hiorg', 'Wrong password.', OC_Log::INFO );
         
         return false;
      }
      
      $token = null;
      foreach ( $http_response_header as $header ) {
         if (preg_match ( '/^([^:]+): *(.*)/', $header, $output )) {
            if ($output [1] == 'Location') {
               parse_str ( parse_url ( $output [2], PHP_URL_QUERY ), $query );
               
               if (isset ( $query ['token'] ) && preg_match ( '/[0-9a-z_\-]+/i', $query ['token'] )) {
                  $token = $query ['token'];
                  break;
               }
            }
         }
      }
      
      if ($token == null) {
         OC_Log::write ( 'user_hiorg', 'No token provided', OC_Log::WARN );
         
         return false;
      }
      
      $userinfo = unserialize ( base64_decode ( mb_substr ( $result, 3 ) ) );
      
      if ($userinfo ['ov'] !== OCP\Config::getAppValue ( 'user_hiorg', 'ov' )) {
         OC_Log::write ( 'user_hiorg', 'Wrong ov', OC_Log::WARN );
         
         return false;
      }
      
      $uid = $userinfo ['user_id'];
      
      if (! $this->userExists ( $uid )) {
         if ($this->_realBackend->createUser ( $uid, $password )) {
            OC_Log::write ( 'user_hiorg', "New user ($uid) created.", OC_Log::INFO );
         } else {
            OC_Log::write ( 'user_hiorg', "Could not create user ($uid).", OC_Log::WARN );
         }
      }
      
      $this->_realBackend->setDisplayName ( $uid, $userinfo ['vorname'] . ' ' . $userinfo ['name'] );
      
      \OC::$server->getSession ()->set ( 'user_hiorg_token', $token );
      
      OC_Log::write ( 'user_hiorg', "Correct password for $username ($uid).", OC_Log::INFO );
      
      return $uid;
   }

   public function countUsers() {
      return $this->_realBackend->countUsers ();
   }
}

?>