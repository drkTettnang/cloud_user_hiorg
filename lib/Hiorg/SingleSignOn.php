<?php

namespace OCA\User_Hiorg\Hiorg;

use OCP\ILogger;
use OCP\IConfig;
use OCP\ISession;
use OCA\User_Hiorg\IDataRetriever;

class SingleSignOn implements ISingleSignOn
{
	const SSOURL = 'https://www.hiorg-server.de/logmein.php';

	private $logger;
	private $config;
	private $session;
	private $dataRetriever;

	public function __construct(
		ILogger $logger,
		IConfig $config,
		ISession $session,
		IDataRetriever $dataRetriever
	) {
		$this->logger = $logger;
		$this->config = $config;
		$this->session = $session;
		$this->dataRetriever = $dataRetriever;
	}


	/**
	 * @UseSession
	 */
	public function getUserInfo($username, $password)
	{
		$ov = $this->config->getAppValue('user_hiorg', 'ov');

		$reqUserinfo = [
			'name',
			'vorname',
			'gruppe',
			'perms',
			'username',
			'email',
			'user_id'
		];
		$reqParam = http_build_query([
			'ov' => $ov,
			'weiter' => self::SSOURL,
			'getuserinfo' => implode(',', $reqUserinfo)
		]);

		$result = $this->dataRetriever->fetchUrl(self::SSOURL . '?' . $reqParam, [
			'username' => $username,
			'password' => $password,
			'submit' => 'Login'
		]);

		if ($result['body'] === false) {
			$this->logger->warning('Hiorg SSO not reachable.');

			return false;
		}

		if (mb_substr($result['body'], 0, 2) !== 'OK') {
			$this->logger->info('Wrong HIORG password.');

			return false;
		}

		$token = null;
		if (isset($result['headers']['Location'])) {
			parse_str(parse_url($result['headers']['Location'], PHP_URL_QUERY), $query);

			if (isset($query['token']) && preg_match('/^[0-9a-z_\-]+$/i', $query['token'])) {
				$token = $query['token'];
			}
		}

		if ($token === null) {
			$this->logger->warning('No token provided');

			return false;
		}

		$jsonString = base64_decode(mb_substr($result['body'], 3));
		$userinfo = @unserialize($jsonString);

		if ($userinfo === false) {
			$this->logger->warning('Could not decode response.');

			return false;
		}

		if ($userinfo['ov'] !== $ov) {
			$this->logger->warning('Wrong ov');

			return false;
		}

		return $userinfo;
	}
}
