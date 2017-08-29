<?php

namespace OCA\User_Hiorg\Hiorg;

use OCP\ILogger;
use OCP\IConfig;

class AndroidRestAPI implements IAndroidRestAPI
{
	const AJAXLOGIN = 'https://www.hiorg-server.de/ajax/login.php';
	const AJAXCONTACT = 'https://www.hiorg-server.de/ajax/getcontacts.php';
	const AJAXMISSION = 'https://www.hiorg-server.de/ajax/geteinsatzliste.php';

	private $logger;
	private $config;

	public function __construct(
		ILogger $logger,
	  IConfig $config
	) {
		$this->logger = $logger;
		$this->config = $config;

	}

	public function getUserData($username, $password)
	{
		$ajaxcontext = stream_context_create([
			'http' => [
				'method' => 'POST',
				'header' => 'Content-type: application/x-www-form-urlencoded',
				'content' => http_build_query([
						'username' => $username,
						'passmd5' => md5($password),
						'ov' => $this->config->getAppValue('user_hiorg', 'ov')
				], '', '&')
			]
		]);

		$ajaxresult = file_get_contents(self::AJAXLOGIN, false, $ajaxcontext);

		if ($ajaxresult === false) {
			$this->logger->warning('Could not connect to ajax login.');

			return false;
		}

		$ajaxdata = json_decode($ajaxresult);

		if (is_null($ajaxdata)) {
			$this->logger->warning('Could not unserialize ajaxdata.');

			return false;
		}

		if ($ajaxdata->status !== 'OK') {
			$this->logger->warning('Could not login through rest api.');

			return false;
		}

		return $ajaxdata;
	}
}
