<?php

namespace OCA\User_Hiorg\Tests\User;

use OC\User\Backend;
use OCP\IUserBackend;
use OCP\ILogger;
use OCP\IConfig;
use OCP\IUserManager;
use OCP\IUser;
use OCP\IGroupManager;
use OCP\IGroup;
use OCP\UserInterface;
use OCA\User_Hiorg\Hiorg\IAndroidRestAPI;
use OCA\User_Hiorg\Hiorg\ISingleSignOn;
use OCA\User_Hiorg\Util\ICache;
use OCA\User_Hiorg\User\Hiorg;
use PHPUnit\Framework\TestCase;

interface IBackend extends IUserBackend, UserInterface
{
	public function createUser($uid, $password);
	public function setPassword($uid, $password);
	public function checkPassword($uid, $password);
	public function setDisplayName($uid, $displayName);
}

class HiorgTest extends TestCase
{
	private $realBackend;
	private $cache;
	private $logger;
	private $config;
	private $userManager;
	private $groupManager;
	private $restAPI;
	private $singleSO;
	private $hiorg;

	public function setUp()
	{
		parent::setUp();

		$this->realBackend = $this->createMock(IBackend::class);
		$this->cache = $this->createMock(ICache::class);
		$this->logger = $this->createMock(ILogger::class);
		$this->config = $this->createMock(IConfig::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->restAPI = $this->createMock(IAndroidRestAPI::class);
		$this->singleSO = $this->createMock(ISingleSignOn::class);

		$this->hiorg = new Hiorg(
			$this->realBackend,
			$this->cache,
			$this->logger,
			$this->config,
			$this->userManager,
			$this->groupManager,
			$this->restAPI,
			$this->singleSO
		);
	}

	public function testImplementsActions()
	{
		$this->assertTrue($this->hiorg->implementsActions(Backend::CHECK_PASSWORD));
		$this->assertTrue($this->hiorg->implementsActions(Backend::GET_HOME));
		$this->assertTrue($this->hiorg->implementsActions(Backend::GET_DISPLAYNAME));
		$this->assertTrue($this->hiorg->implementsActions(Backend::COUNT_USERS));
	}

	public function testCheckValidPasswordNotCachedAndNotExists()
	{
		$this->cache
		 ->expects($this->once())
		 ->method('getUid')
		 ->with('dummy_username')
		 ->willReturn(false);
		$this->singleSO
		 ->expects($this->once())
		 ->method('getUserInfo')
		 ->with('dummy_username', 'dummy_password')
		 ->willReturn([
			'user_id' => 'dummy_uid',
			'vorname' => 'dummy_first',
			'name' => 'dummy_last',
			'email' => 'dummy@email'
		 ]);
		$this->realBackend
		 ->expects($this->once())
		 ->method('userExists')
		 ->with('dummy_uid')
		 ->willReturn(false);
		$this->realBackend
		 ->expects($this->once())
		 ->method('createUser')
		 ->with('dummy_uid', 'dummy_password')
		 ->willReturn(true);
		$this->cache
		 ->expects($this->once())
		 ->method('setUid')
		 ->with('dummy_username', 'dummy_uid');
		$this->realBackend
		 ->expects($this->once())
		 ->method('setDisplayName')
		 ->with('dummy_uid', 'dummy_first dummy_last');
		$this->config
		 ->expects($this->once())
		 ->method('setUserValue')
		 ->with('dummy_uid', 'settings', 'email', 'dummy@email');
		$this->config
		 ->expects($this->once())
		 ->method('getUserValue')
		 ->with('dummy_uid', 'login', 'lastLogin', 0)
		 ->willReturn(0);
		$user = $this->createMock(IUser::class);
		$user->expects($this->once())
			->method('getUID')
			->willReturn('dummy_uid');
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('dummy_uid')
			->willReturn($user);
		$this->restAPI
			->expects($this->once())
			->method('getUserData')
			->with('dummy_username', 'dummy_password')
			->willReturn([
				'env' => [
					// group permission as bitmask
					// dummy user is member of 1 and 4
					'grp' => '5'
				],
				'grp' => [
					[
						'i' => '1',
						'n' => 'Gruppe A'
					], [
						'i' => '4',
						'n' => 'Gruppe B'
					], [
						'i' => '16',
						'n' => 'Gruppe C'
					]
				]
			]);
		$this->groupManager
			->expects($this->once())
			->method('getUserGroupIds')
			->with($user)
			->willReturn([]);
		$this->groupManager
			->expects($this->exactly(3))
			->method('groupExists')
			->will($this->returnValueMap([
			  ['Gruppe A', true],
			  ['Gruppe B', true],
			  ['Gruppe C', false]
		  ]));
		$this->groupManager
			->expects($this->once())
			->method('createGroup')
			->with('Gruppe C');
		$this->groupManager
			->expects($this->exactly(2))
			->method('isInGroup')
			->will($this->returnValueMap([
			  ['dummy_uid', 'Gruppe A', true],
			  ['dummy_uid', 'Gruppe B', false]
		  ]));
		$groupB = $this->createMock(IGroup::class);
		$groupB->expects($this->once())
			->method('addUser')
			->with($user);
		$this->groupManager
			->expects($this->once())
			->method('get')
			->with('Gruppe B')
			->willReturn($groupB);

		$result = $this->hiorg->checkPassword('dummy_username', 'dummy_password');

		$this->assertEquals('dummy_uid', $result);
	}

	public function testCheckValidPasswordCached()
	{
		$this->cache
		 ->expects($this->once())
		 ->method('getUid')
		 ->with('dummy_username')
		 ->willReturn('dummy_uid');
		$this->realBackend
		 ->expects($this->once())
		 ->method('checkPassword')
		 ->with('dummy_uid', 'dummy_password')
		 ->willReturn(true);
		$this->config
		 ->expects($this->once())
		 ->method('getUserValue')
		 ->with('dummy_uid', 'login', 'lastLogin', 0)
		 ->willReturn(time());

		$result = $this->hiorg->checkPassword('dummy_username', 'dummy_password');

		$this->assertEquals('dummy_uid', $result);
	}

	public function testCheckInvalidPasswordNotCached()
	{
		$this->cache
		 ->expects($this->once())
		 ->method('getUid')
		 ->with('dummy_username')
		 ->willReturn(false);
		$this->singleSO
		 ->expects($this->once())
		 ->method('getUserInfo')
		 ->with('dummy_username', 'dummy_password')
		 ->willReturn(false);

		$result = $this->hiorg->checkPassword('dummy_username', 'dummy_password');

		$this->assertFalse($result);
	}

	public function testCheckInvalidPasswordCached()
	{
		$this->cache
		 ->expects($this->once())
		 ->method('getUid')
		 ->with('dummy_username')
		 ->willReturn('dummy_uid');
		$this->realBackend
		 ->expects($this->once())
		 ->method('checkPassword')
		 ->with('dummy_uid', 'dummy_password')
		 ->willReturn(false);
		$this->config
		 ->expects($this->once())
		 ->method('getUserValue')
		 ->with('dummy_uid', 'login', 'lastLogin', 0)
		 ->willReturn(time());
		$this->singleSO
		 ->expects($this->once())
		 ->method('getUserInfo')
		 ->with('dummy_username', 'dummy_password')
		 ->willReturn(false);

		$result = $this->hiorg->checkPassword('dummy_username', 'dummy_password');

		$this->assertFalse($result);
	}
}
