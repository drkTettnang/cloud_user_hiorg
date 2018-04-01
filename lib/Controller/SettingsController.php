<?php

namespace OCA\User_Hiorg\Controller;

use OCP\AppFramework\Controller;
use OCP\IConfig;
use OCP\IRequest;

class SettingsController extends Controller
{
	private $config;

	public function __construct(
		$appName,
		IRequest $request,
		IConfig $config
	) {
		parent::__construct($appName, $request);
		$this->config = $config;
	}

	public function update()
	{
		$ov = trim($this->request->getParam('ov'));

		$this->setAppValue('ov', $ov);

		return [
			'status' => 'success',
		];
	}

	private function getAppValue($key, $default = null)
	{
		$value = $this->config->getAppValue($this->appName, $key, $default);
		return (empty($value)) ? $default : $value;
	}

	private function setAppValue($key, $value)
	{
		return $this->config->setAppValue($this->appName, $key, $value);
	}
}
