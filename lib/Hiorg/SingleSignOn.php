<?php

namespace OCA\User_Hiorg\Hiorg;

use OCP\ILogger;
use OCP\IConfig;

class SingleSignOn implements ISingleSignOn
{
	const URL = 'https://www.hiorg-server.de/';
	const SSOURL = 'https://www.hiorg-server.de/logmein.php';

	private $logger;
	private $config;

	public function __construct(
		ILogger $logger,
		IConfig $config
	) {
		$this->logger = $logger;
		$this->config = $config;
	}


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

		$context = stream_context_create([
			'http' => [
				'method' => 'POST',
				'header' => 'Content-type: application/x-www-form-urlencoded',
				'content' => http_build_query([
					'username' => $username,
					'password' => $password,
					'submit' => 'Login'
				], '', '&')
			]
		]);

		$result = file_get_contents(self::SSOURL . '?' . $reqParam, false, $context);

		if (mb_substr($result, 0, 2) !== 'OK') {
			$this->logger->info('Wrong HIORG password.');

			return false;
		}

		$token = null;
		foreach ($http_response_header as $header) {
			if (preg_match('/^([^:]+): *(.*)/', $header, $output)) {
				if ($output [1] === 'Location') {
					parse_str(parse_url($output [2], PHP_URL_QUERY), $query);

					if (isset($query ['token']) && preg_match('/[0-9a-z_\-]+/i', $query ['token'])) {
						$token = $query ['token'];
						break;
					}
				}
			}
		}

		if ($token === null) {
			$this->logger->warn('No token provided');

			return false;
		}

		// save token for hiorg-server web access
		\OC::$server->getSession()->set('user_hiorg_token', $token);

		$userinfo = unserialize(base64_decode(mb_substr($result, 3)));

		if ($userinfo ['ov'] !== $ov) {
			self::warn('Wrong ov');

			return false;
		}

		return $userinfo;
	}
}
