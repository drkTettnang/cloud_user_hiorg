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

class HIORG {
   private static $userData = null;
   
   // How long should we try our cached username-uid?
   const TIMEOUT_CACHE = 86400; //24h

   const URL = 'https://www.hiorg-server.de/';
   const SSOURL = 'https://www.hiorg-server.de/logmein.php';
   const AJAXLOGIN = 'https://www.hiorg-server.de/ajax/login.php';
   const AJAXCONTACT = 'https://www.hiorg-server.de/ajax/getcontacts.php';
   const AJAXMISSION = 'https://www.hiorg-server.de/ajax/geteinsatzliste.php';
   
   /**
    * Helper to get db connection.
    * 
    * @return db connection
    */
   private static function db() {
      return \OC::$server->getDatabaseConnection();
   }
   
   /**
    * Write message with level "warn" to oc log file.
    * 
    * @param  {string} $msg Message
    */
   public static function warn($msg) {
      OC_Log::write ( 'user_hiorg', $msg, OC_Log::WARN );
   }
   
   /**
    * Write message with level "info" to oc log file.
    * 
    * @param  {string} $msg Message
    */
   public static function info($msg) {
      OC_Log::write ( 'user_hiorg', $msg, OC_Log::INFO );
   }
   
   /**
    * Check if we are allowed to use our cached username-uid.
    * 
    * @param  {string}  $uid user id
    * @return boolean True if we are in our timeframe
    */
   public static function isInTime($uid) {
      $lastLogin = OCP\Config::getUserValue($uid, 'login', 'lastLogin', 0);
      
      return $lastLogin + self::TIMEOUT_CACHE > time();
   }
   
   /**
    * Get cached user id from username.
    * 
    * @param  {string} $username username
    * @return {string|false} Returns uid on success, otherwise false
    */
   public static function getUid($username) {
      $sql = 'SELECT uid FROM `*PREFIX*user_hiorg_username_uid` WHERE username = ?';
      $args = array($username);
      
      $query = self::db()->prepare($sql);
      
      if ($query->execute($args)) {
         $row = $query->fetchAll();
         
         return (count($row) > 0) ? $row[0]['uid'] : false;
      }
      
      return false;
   }
   
   /**
    * Write username-uid tuple to cache.
    * 
    * @param {string} $username username
    * @param {string} $uid user id
    */
   public static function setUid($username, $uid) {
      self::db()->executeUpdate('DELETE FROM `*PREFIX*user_hiorg_username_uid` WHERE username = ?', array($username));
      
      $sql = 'INSERT INTO `*PREFIX*user_hiorg_username_uid` (username, uid) VALUES (?, ?)';
      $result = self::db()->executeUpdate($sql, array($username, $uid));
      
      return $result;
   }
   
   /**
    * Check password against hiorg-server. 
    * 
    * @param $backend real user backend
    * @param {string} $username username
    * @param {string} $password password
    * @return {string|false} Return user id on success, false otherwise
    */
   public static function checkPassword($backend, $username, $password) {
      // get cached uid
      $uid = self::getUid($username);
      
      // try cached credentials, if still valid
      if ($uid) {
         if (self::isInTime($uid)) {
            if ($backend->checkPassword($uid, $password)) {
               self::info("Correct cached credentials for $username ($uid).");
               
               return $uid;
            }
         }
      }
      
      $uid = null;
   
      // request user information via sso
      $userinfo = self::getSSOUserInfo($username, $password);
      
      if ($userinfo === false) {
         return false;
      }
      
      $uid = $userinfo ['user_id'];
      
      if (! $backend->userExists ( $uid )) {
         if ($backend->createUser ( $uid, $password )) {
            self::info("New user ($uid) created.");
         } else {
            self::warn("Could not create user ($uid).");

            return false;
         }
      } else {
         // update password
         $backend->setPassword($uid, $password);
      }
      
      self::info("Correct password for $username ($uid).");
      
      // set display name
      $backend->setDisplayName ( $uid, $userinfo ['vorname'] . ' ' . $userinfo ['name'] );
      
      // set email address
      OCP\Config::setUserValue($uid, 'settings', 'email', $userinfo['email']);
      
      // update group memberships    
      self::syncGroupMemberships($uid, self::getUserData($username, $password));

      return $uid;
   }
   
   /**
    * Get user information from SSO.
    * 
    * @param {string} $username username
    * @param {string} $password password
    * @return {array|false} Return user information if credentials are valid, false otherwise
    */
   private static function getSSOUserInfo($username, $password) {
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
         self::info('Wrong HIORG password.');
         
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
         self::warn('No token provided');
         
         return false;
      }
      
      // save token for hiorg-server web access
      \OC::$server->getSession ()->set ( 'user_hiorg_token', $token );
      
      $userinfo = unserialize ( base64_decode ( mb_substr ( $result, 3 ) ) );
      
      if ($userinfo ['ov'] !== OCP\Config::getAppValue ( 'user_hiorg', 'ov' )) {
         self::warn('Wrong ov');
         
         return false;
      }
      
      // cache uid
      self::setUid($username, $userinfo['user_id']);
      
      return $userinfo;
   }
   
   /**
    * Get user data from REST API (android app).
    * 
    * @param {string} $username username
    * @param {string} $password password
    * @return {object|false} Return user data as object if credentials are valid, false otherwise
    */
   private static function getUserData($username, $password) {
      if (self::$userData !== null) {
         self::info('Use already requested data.');
         
         return self::$userData;
      }

      $ajaxcontext = stream_context_create ( array (
            'http' => array (
                  'method' => 'POST',
                  'header' => 'Content-type: application/x-www-form-urlencoded',
                  'content' => http_build_query ( array (
                        'username' => $username,
                        'passmd5' => md5($password),
                        'ov' => OCP\Config::getAppValue ( 'user_hiorg', 'ov' )
                  ), '', '&' ) 
            ) 
      ) );

      $ajaxresult = file_get_contents ( self::AJAXLOGIN, false, $ajaxcontext );

      $ajaxdata = json_decode ( $ajaxresult );

      if (is_null($ajaxdata)) {
         self::warn('Could not unserialize ajaxdata.');
         
         return false;
      }
      
      if ($ajaxdata->status !== 'OK') {
         self::warn('Could not login through rest api.');
         
         return false;
      }
      
      self::$userData = $ajaxdata;

      return $ajaxdata;
   }
   
   /**
    * Add user to all groups received from HIORG and remove him from all other groups.
    * 
    * @param {string} $uid user id
    * @param {object} $data user data
    */
   public static function syncGroupMemberships($uid, $data) {
      if ($data === false) {
         self::warn('Could not sync group memberships, because no valid user data was provided.');
         
         return;
      }

      $userGroups = OC_Group::getUserGroups($uid);
      $remoteGroups = array();
      $userGroupKey = intval($data->env->grp);

      foreach ($data->grp as $grp) {
         $gid = $grp->n;
         $gkey = intval($grp->i);
         
         if (!OC_Group::groupExists($gid)) {
            OC_Group::createGroup($gid);
         }
         
         if (($userGroupKey & $gkey) === $gkey) {
            if (!OC_Group::inGroup($uid, $gid)) {
               OC_Group::addToGroup($uid, $gid);
            }
            
            $remoteGroups[] = $gid;
         }
      }

      // remove non-remote group memberships
      $groupsToRemove = array_diff($userGroups, $remoteGroups);
      foreach ($groupsToRemove as $gid) {
         if ($gid !== 'admin') {
            OC_Group::removeFromGroup($uid, $gid);
         }
      }
      
      self::info('Group memberships successfully synced.');
   }
}

?>
