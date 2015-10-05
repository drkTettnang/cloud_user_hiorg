<?php 
error_reporting(E_ALL);error_reporting(E_ALL); 
ini_set("display_errors", 1); 

require_once '../lib/hiorg.php';

class OC_Group {
   private static $groups = array();
   private static $userGroup = array();
   
   public static function cleanup() {
      self::$groups = array();
      self::$userGroup = array();
   }
   
   public static function groupExists($g){
      return in_array($g, self::$groups);
   }
   
   public static function createGroup($g){
      if (!self::groupExists($g)) {
         self::$groups[] = $g;
      }
   }
   
   public static function inGroup($u, $g){
      return in_array($g, self::$userGroup[$u]);
   }
   
   public static function getUserGroups($u){
      return (is_array(self::$userGroup[$u]))?self::$userGroup[$u]:array();
   }
   
   public static function addToGroup($u, $g){
      if (!array_key_exists($u, self::$userGroup)) {
         $userGroup[$u] = array();
      }
      
      if (!self::inGroup($u, $g)) {
         self::$userGroup[$u][] = $g;
      }
   }
   
   public static function removeFromGroup($u, $g){
      if (self::inGroup($u, $g)) {
         unset(self::$userGroup[$u][array_search($g, self::$userGroup[$u])]);
      }
   }
}

class OC_Log {
   const INFO = 'info';
   const WARN = 'warn';
   
   public static function write($app, $msg, $level) {
      echo $msg.'<br />';
   }   
}

class HIORGTest {//extends PHPUnit_Framework_TestCase {
   public function testSyncGroupMemberships() {
      $json = '{"status":"OK","sess":{"sids":"6ue2gg4svmc173mh06k8v8g0h5"},"env":{"feat":"0","am":"t","sm":"t","grp":"41","lt":"","n":"Klaus Herberth","u":"7727afda1da85323ee465f02820c9248","srv":"HiOrg-Server","bez":"DRK OV Tettnang e.V.","san":"Sanitäter","pro":"t","perm":"1","termmon":"3","showextlist":"f"},"grp":[{"i":"1","n":"Gruppe Tettnang"},{"i":"4","n":"Gruppe Tannau"},{"i":"64","n":"Bereitschaftsleitung"},{"i":"8","n":"SEG"},{"i":"32","n":"HNR"}],"qual":[{"i":"0","n":"x","l":"x"},{"i":"1","n":"EH","l":"Erste-Hilfe"},{"i":"2","n":"SanA","l":"Sanitätshelfer/in"},{"i":"3","n":"SanB","l":"Sanitäter/in (San B)"},{"i":"4","n":"SanC","l":"Sanitäter/in (HNR)"},{"i":"5","n":"RH","l":"Rettungshelfer/in"},{"i":"6","n":"RS","l":"Rettungs-Sanitäter/in"},{"i":"7","n":"RA","l":"Rettungs-Assistent/in"},{"i":"8","n":"LRA","l":"Lehr-Rettungsassistent/in"},{"i":"9","n":"Arzt","l":"Arzt / Ärztin"},{"i":"10","n":"NA","l":"Notarzt / Notärztin"},{"i":"11","n":"x","l":"x"},{"i":"12","n":"x","l":"x"},{"i":"13","n":"x","l":"x"},{"i":"14","n":"x","l":"x"},{"i":"15","n":"x","l":"x"},{"i":"16","n":"x","l":"x"},{"i":"17","n":"x","l":"x"}],"dist":[{"i":"0","n":"x","l":"x"},{"i":"1","n":"Anw","l":"Anwärter/in"},{"i":"2","n":"H","l":"Helfer/in (mit Grundausbildung)"},{"i":"3","n":"TF","l":"Truppführer/in"},{"i":"4","n":"GL","l":"Gruppenleiter/in"},{"i":"5","n":"sBL","l":"stlv. Bereitschaftsleiter/in"},{"i":"6","n":"BL","l":"Bereitschaftsleiter/in"},{"i":"7","n":"sKBL","l":"stlv. Kreisbereitschaftsleiter/in"},{"i":"8","n":"KBL","l":"Kreisbereitschaftsleiter/in"}],"kat":[{"i":"0","n":""},{"i":"5","n":"DA SEG"},{"i":"7","n":"Leitungs- und Führungskräfte"},{"i":"9","n":"HNR"},{"i":"13","n":"DA Tettnang"},{"i":"16","n":"DA Tannau"}],"ical":{"url":"https://www.hiorg-server.de/myical.php?ov=ttt&lab=69OkxJ4Bf0ZSdyS8ZTaOGKMD9jzCqmzFcij%2BO%2Fct18FKOrZ5qE9Kth5ToBtZgat9"},"hash":"fef56df175578f12a04e17e0ce9f08ede9917d13"}';
      $data = json_decode($json);
      
      OC_Group::cleanup();
      OC_Group::createGroup('SEG');
      OC_Group::addToGroup('7727afda1da85323ee465f02820c9248', 'SEG');
      OC_Group::addToGroup('7727afda1da85323ee465f02820c9248', 'admin');
      OC_Group::addToGroup('7727afda1da85323ee465f02820c9248', 'local');
      OC_Group::addToGroup('7727afda1da85323ee465f02820c9248', 'Gruppe Tannau');
      
      print_r(OC_Group::getUserGroups('7727afda1da85323ee465f02820c9248'));
      echo '<br />';
      echo '<br />';
      
      HIORG::syncGroupMemberships('7727afda1da85323ee465f02820c9248', $data);
      
      //$this->assertEquals(array('SEG', 'Gruppe Tettnang', 'HNR'), OC_Group::getUserGroups('7727afda1da85323ee465f02820c9248'));
      
      //var_dump($data);
      
      print_r($data->grp);
      echo '<br />';
      print_r(array('SEG', 'admin', 'Gruppe Tettnang', 'HNR'));
      echo '<br />';
      print_r(OC_Group::getUserGroups('7727afda1da85323ee465f02820c9248'));
      echo '<br />';
      echo '<br />';
   }
}
var_dump(intval('4'));
var_dump((intval('20') & intval('4')) === intval('4'));

$test = new HIORGTest();
$test->testSyncGroupMemberships();

?>
