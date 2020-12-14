<?php

namespace OCA\User_Hiorg\Tests\Hiorg;

use OCP\ILogger;
use OCP\IConfig;
use OCA\User_Hiorg\DataRetriever;
use OCA\User_Hiorg\Hiorg\AndroidRestAPI;
use PHPUnit\Framework\TestCase;

class AndroidRestAPITest extends TestCase
{
	const AJAXLOGIN = 'https://www.hiorg-server.de/ajax/login.php';

	private $logger;
	private $config;
	private $dataRetriever;

	private $androidRestAPI;

	public function setUp(): void
	{
		parent::setUp();

		$this->logger = $this->createMock(ILogger::class);
		$this->config = $this->createMock(IConfig::class);
		$this->dataRetriever = $this->createMock(DataRetriever::class);

		$this->androidRestAPI = new AndroidRestAPI(
			$this->logger,
			$this->config,
			$this->dataRetriever
		);
	}

	public function testUnavailableServer()
	{
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('user_hiorg', 'ov')
			->willReturn('dummy_ov');

		$this->dataRetriever
			->expects($this->once())
			->method('fetchUrl')
			->with(self::AJAXLOGIN, [
				'username' => 'dummy_user',
				'passmd5' => md5('dummy_password'),
				'ov' => 'dummy_ov'
			])
			->willReturn([
				'body' => false
			]);

		$result = $this->androidRestAPI->getUserData('dummy_user', 'dummy_password');

		$this->assertFalse($result);
	}

	public function testInvalidJSONResponse()
	{
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('user_hiorg', 'ov')
			->willReturn('dummy_ov');

		$this->dataRetriever
			->expects($this->once())
			->method('fetchUrl')
			->with(self::AJAXLOGIN, [
				'username' => 'dummy_user',
				'passmd5' => md5('dummy_password'),
				'ov' => 'dummy_ov'
			])
			->willReturn([
				'body' => '{asdf'
			]);

		$result = $this->androidRestAPI->getUserData('dummy_user', 'dummy_password');

		$this->assertFalse($result);
	}

	public function testInvalidLoginWithoutStatus()
	{
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('user_hiorg', 'ov')
			->willReturn('dummy_ov');

		$this->dataRetriever
			->expects($this->once())
			->method('fetchUrl')
			->with(self::AJAXLOGIN, [
				'username' => 'dummy_user',
				'passmd5' => md5('dummy_password'),
				'ov' => 'dummy_ov'
			])
			->willReturn([
				'body' => '{}'
			]);

		$result = $this->androidRestAPI->getUserData('dummy_user', 'dummy_password');

		$this->assertFalse($result);
	}

	public function testInvalidLogin()
	{
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('user_hiorg', 'ov')
			->willReturn('dummy_ov');

		$this->dataRetriever
			->expects($this->once())
			->method('fetchUrl')
			->with(self::AJAXLOGIN, [
				'username' => 'dummy_user',
				'passmd5' => md5('dummy_password'),
				'ov' => 'dummy_ov'
			])
			->willReturn([
				'body' => '{"status":""}'
			]);

		$result = $this->androidRestAPI->getUserData('dummy_user', 'dummy_password');

		$this->assertFalse($result);
	}

	public function testValidLogin()
	{
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('user_hiorg', 'ov')
			->willReturn('dummy_ov');

		$this->dataRetriever
			->expects($this->once())
			->method('fetchUrl')
			->with(self::AJAXLOGIN, [
				'username' => 'dummy_user',
				'passmd5' => md5('dummy_password'),
				'ov' => 'dummy_ov'
			])
			->willReturn([
				'body' => '{"status":"OK","sess":{"sids":"6ue2gg4svmc173mh06k8v8g0h5"},"env":{"feat":"0","am":"t","sm":"t","grp":"41","lt":"","n":"Klaus Herberth","u":"7727afda1da85323ee465f02820c9248","srv":"HiOrg-Server","bez":"DRK OV Tettnang e.V.","san":"Sanitäter","pro":"t","perm":"1","termmon":"3","showextlist":"f"},"grp":[{"i":"1","n":"Gruppe Tettnang"},{"i":"4","n":"Gruppe Tannau"},{"i":"64","n":"Bereitschaftsleitung"},{"i":"8","n":"SEG"},{"i":"32","n":"HNR"}],"qual":[{"i":"0","n":"x","l":"x"},{"i":"1","n":"EH","l":"Erste-Hilfe"},{"i":"2","n":"SanA","l":"Sanitätshelfer/in"},{"i":"3","n":"SanB","l":"Sanitäter/in (San B)"},{"i":"4","n":"SanC","l":"Sanitäter/in (HNR)"},{"i":"5","n":"RH","l":"Rettungshelfer/in"},{"i":"6","n":"RS","l":"Rettungs-Sanitäter/in"},{"i":"7","n":"RA","l":"Rettungs-Assistent/in"},{"i":"8","n":"LRA","l":"Lehr-Rettungsassistent/in"},{"i":"9","n":"Arzt","l":"Arzt / Ärztin"},{"i":"10","n":"NA","l":"Notarzt / Notärztin"},{"i":"11","n":"x","l":"x"},{"i":"12","n":"x","l":"x"},{"i":"13","n":"x","l":"x"},{"i":"14","n":"x","l":"x"},{"i":"15","n":"x","l":"x"},{"i":"16","n":"x","l":"x"},{"i":"17","n":"x","l":"x"}],"dist":[{"i":"0","n":"x","l":"x"},{"i":"1","n":"Anw","l":"Anwärter/in"},{"i":"2","n":"H","l":"Helfer/in (mit Grundausbildung)"},{"i":"3","n":"TF","l":"Truppführer/in"},{"i":"4","n":"GL","l":"Gruppenleiter/in"},{"i":"5","n":"sBL","l":"stlv. Bereitschaftsleiter/in"},{"i":"6","n":"BL","l":"Bereitschaftsleiter/in"},{"i":"7","n":"sKBL","l":"stlv. Kreisbereitschaftsleiter/in"},{"i":"8","n":"KBL","l":"Kreisbereitschaftsleiter/in"}],"kat":[{"i":"0","n":""},{"i":"5","n":"DA SEG"},{"i":"7","n":"Leitungs- und Führungskräfte"},{"i":"9","n":"HNR"},{"i":"13","n":"DA Tettnang"},{"i":"16","n":"DA Tannau"}],"ical":{"url":"https://www.hiorg-server.de/myical.php?ov=ttt&lab=69OkxJ4Bf0ZSdyS8ZTaOGKMD9jzCqmzFcij%2BO%2Fct18FKOrZ5qE9Kth5ToBtZgat9"},"hash":"fef56df175578f12a04e17e0ce9f08ede9917d13"}'
			]);

		$result = $this->androidRestAPI->getUserData('dummy_user', 'dummy_password');

		$this->assertEquals('OK', $result['status']);
		$this->assertEquals('6ue2gg4svmc173mh06k8v8g0h5', $result['sess']['sids']);
	}
}
