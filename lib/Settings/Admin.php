<?php

namespace OCA\User_Hiorg\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\Settings\ISettings;
use OCP\IConfig;

class Admin implements ISettings
{
	/** @var IConfig */
	private $config;

	public function __construct(IConfig $config)
	{
		$this->config = $config;
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm()
	{
		$parameters = [
			'ov' => $this->config->getAppValue('user_hiorg', 'ov')
		];

		return new TemplateResponse('user_hiorg', 'settings/admin', $parameters);
	}

	/**
	 * @return string the section ID, e.g. 'sharing'
	 */
	public function getSection()
	{
		return 'additional';
	}

	/**
	 * @return int whether the form should be rather on the top or bottom of
	 * the admin section. The forms are arranged in ascending order of the
	 * priority values. It is required to return a value between 0 and 100.
	 *
	 * E.g.: 70
	 */
	public function getPriority()
	{
		return 50;
	}
}
