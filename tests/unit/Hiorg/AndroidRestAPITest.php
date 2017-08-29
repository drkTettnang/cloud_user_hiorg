<?php

namespace OCA\User_Hiorg\Tests\Hiorg;

use OCP\ILogger;
use OCP\IConfig;
use OCA\User_Hiorg\IDataRetriever;
use OCA\User_Hiorg\Hiorg\AndroidRestAPI;
use PHPUnit\Framework\TestCase;

class AndroidRestAPITest extends TestCase
{
	const AJAXLOGIN = 'https://www.hiorg-server.de/ajax/login.php';

	private $logger;
	private $config;
	private $dataRetriever;

	private $androidRestAPI;

	public function setUp()
	{
		parent::setUp();

		$this->logger = $this->createMock(ILogger::class);
		$this->config = $this->createMock(IConfig::class);
		$this->dataRetriever = $this->createMock(IDataRetriever::class);

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
				'body' => '{"status":"OK","data":"data"}'
			]);

		$result = $this->androidRestAPI->getUserData('dummy_user', 'dummy_password');

		$this->assertEquals('OK', $result->status);
		$this->assertEquals('data', $result->data);
	}
}
