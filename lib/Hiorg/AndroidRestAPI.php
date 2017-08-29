<?php

namespace OCA\User_Hiorg\Hiorg;

use OCP\ILogger;
use OCP\IConfig;
use OCA\User_Hiorg\IDataRetriever;

class AndroidRestAPI implements IAndroidRestAPI
{
	const AJAXLOGIN = 'https://www.hiorg-server.de/ajax/login.php';
	const AJAXCONTACT = 'https://www.hiorg-server.de/ajax/getcontacts.php';
	const AJAXMISSION = 'https://www.hiorg-server.de/ajax/geteinsatzliste.php';

	private $logger;
	private $config;
	private $dataRetriever;

	public function __construct(
		ILogger $logger,
	  IConfig $config,
	  IDataRetriever $dataRetriever
	) {
		$this->logger = $logger;
		$this->config = $config;
		$this->dataRetriever = $dataRetriever;
	}

	public function getUserData($username, $password)
	{
		$result = $this->dataRetriever->fetchUrl(self::AJAXLOGIN, [
				'username' => $username,
				'passmd5' => md5($password),
				'ov' => $this->config->getAppValue('user_hiorg', 'ov')
		]);

		$ajaxresult = $result['body'];

		if ($ajaxresult === false) {
			$this->logger->warning('Could not connect to ajax login.');

			return false;
		}

		$ajaxdata = json_decode($ajaxresult);

		if (is_null($ajaxdata)) {
			$this->logger->warning('Could not unserialize ajaxdata.');

			return false;
		}

		if (!isset($ajaxdata->status) || $ajaxdata->status !== 'OK') {
			$this->logger->warning('Could not login through rest api.');

			return false;
		}

		return $ajaxdata;
	}
}
