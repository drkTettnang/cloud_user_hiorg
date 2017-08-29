<?php

namespace OCA\User_Hiorg\Tests\Hiorg;

use OCP\ILogger;
use OCP\IConfig;
use OCP\ISession;
use OCA\User_Hiorg\IDataRetriever;
use OCA\User_Hiorg\Hiorg\SingleSignOn;
use PHPUnit\Framework\TestCase;

class SingleSignOnTest extends TestCase
{
	const AJAXLOGIN = 'https://www.hiorg-server.de/ajax/login.php';

	private $logger;
	private $config;
	private $session;
	private $dataRetriever;

	private $androidRestAPI;

	public function setUp()
	{
		parent::setUp();

		$this->logger = $this->createMock(ILogger::class);
		$this->config = $this->createMock(IConfig::class);
		$this->session = $this->createMock(ISession::class);
		$this->dataRetriever = $this->createMock(IDataRetriever::class);

		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('user_hiorg', 'ov')
			->willReturn('dummy_ov');

		$this->androidRestAPI = new SingleSignOn(
			$this->logger,
			$this->config,
			$this->session,
			$this->dataRetriever
		);
	}

	public function testUnavailableSSO()
	{
		$this->mockDataRetriever([
			'body' => false
		]);
		$this->mockLogWarning('Hiorg SSO not reachable.');

		$result = $this->androidRestAPI->getUserInfo('dummy_user', 'dummy_password');

		$this->assertFalse($result);
	}

	public function testInvalidCredentials()
	{
		$this->mockDataRetriever([
			'body' => 'foo'
		]);
		$this->mockLogInfo('Wrong HIORG password.');

		$result = $this->androidRestAPI->getUserInfo('dummy_user', 'dummy_password');

		$this->assertFalse($result);
	}

	public function testMissingToken()
	{
		$this->mockDataRetriever([
			'body' => 'OK:',
			'headers' => [
				'Location' => 'foo'
			]
		]);
		$this->mockLogWarning('No token provided');

		$result = $this->androidRestAPI->getUserInfo('dummy_user', 'dummy_password');

		$this->assertFalse($result);
	}

	public function testInvalidToken()
	{
		$this->mockDataRetriever([
			'body' => 'OK:',
			'headers' => [
				'Location' => 'foo.html?token=%$sdf23'
			]
		]);
		$this->mockLogWarning('No token provided');

		$result = $this->androidRestAPI->getUserInfo('dummy_user', 'dummy_password');

		$this->assertFalse($result);
	}

	public function testEmptyResponse()
	{
		$this->mockDataRetriever([
			'body' => ''
		]);
		$this->mockLogInfo('Wrong HIORG password.');

		$result = $this->androidRestAPI->getUserInfo('dummy_user', 'dummy_password');

		$this->assertFalse($result);
	}

	public function testInvalidBase64Response()
	{
		$this->mockDataRetriever([
			'body' => 'OK: kjasdm3ms3kda',
			'headers' => [
				'Location' => 'foo.html?token=xs3dXasdA3'
			]
		]);
		$this->mockLogWarning('Could not decode response.');

		$result = $this->androidRestAPI->getUserInfo('dummy_user', 'dummy_password');

		$this->assertFalse($result);
	}

	public function testInvalidJSONResponse()
	{
		$this->mockDataRetriever([
			'body' => 'OK: '.base64_encode('{asdf'),
			'headers' => [
				'Location' => 'foo.html?token=xs3dXasdA3'
			]
		]);
		$this->mockLogWarning('Could not decode response.');

		$result = $this->androidRestAPI->getUserInfo('dummy_user', 'dummy_password');

		$this->assertFalse($result);
	}

	public function testWrongOv()
	{
		$this->mockDataRetriever([
			'body' => 'OK: '.base64_encode(serialize([
					'ov' => 'false_ov'
			])),
			'headers' => [
				'Location' => 'foo.html?token=xs3dXasdA3'
			]
		]);
		$this->mockLogWarning('Wrong ov');

		$result = $this->androidRestAPI->getUserInfo('dummy_user', 'dummy_password');

		$this->assertFalse($result);
	}

	public function testValidSSO()
	{
		$token = 'xs3dXasdA3';
		$userInfo = [
				'ov' => 'dummy_ov',
				'name' => 'Bar',
				'vorname' => 'Foo',
				'kuerzel' => 'FooBa',
				'gruppe' => '41',
				'perms' => 'helfer',
				'username' => 'foo.bar',
				'email' => 'foo@bar',
				'quali' => '3',
				'user_id' => '832ajksbe383jkasb3kjb3k3',
				'login_expires' => time() + 60 * 30
		];

		$this->mockDataRetriever([
			'body' => 'OK: '.base64_encode(serialize($userInfo)),
			'headers' => [
				'Location' => 'foo.html?token='.$token
			]
		]);

		$this->session
			->expects($this->once())
			->method('set')
			->with('user_hiorg_token', $token);

		$result = $this->androidRestAPI->getUserInfo('dummy_user', 'dummy_password');

		$this->assertEquals($userInfo, $result);
	}

	private function mockDataRetriever($result = [])
	{
		$this->dataRetriever
			->expects($this->once())
			->method('fetchUrl')
			->with('https://www.hiorg-server.de/logmein.php?ov=dummy_ov&weiter=https%3A%2F%2Fwww.hiorg-server.de%2Flogmein.php&getuserinfo=name%2Cvorname%2Cgruppe%2Cperms%2Cusername%2Cemail%2Cuser_id', [
				'username' => 'dummy_user',
				'password' => 'dummy_password',
				'submit' => 'Login'
			])
			->willReturn($result);
	}

	private function mockLogWarning($message)
	{
		$this->logger
			->expects($this->once())
			->method('warning')
			->with($message);
	}

	private function mockLogInfo($message)
	{
		$this->logger
			->expects($this->once())
			->method('info')
			->with($message);
	}
}
