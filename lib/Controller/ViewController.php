<?php
namespace OCA\User_Hiorg\Controller;

use OCP\IRequest;
use OCP\IConfig;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Controller;
use OCA\User_Hiorg\Hiorg\SingleSignOn;

class ViewController extends Controller
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

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function index()
	{
		$ov = $this->config->getAppValue($this->appName, 'ov');
		$token = ''; // we can't use the token, because the hiorg server is verifing the login ip
		$url = SingleSignOn::SSOURL."?ov=$ov&login=1&token=$token";

		$csp = new ContentSecurityPolicy();
		$csp->addAllowedFrameDomain(SingleSignOn::SSOURL);

		$response = new TemplateResponse('user_hiorg', 'view/index', ['url' => $url]);
		$response->setContentSecurityPolicy($csp);

		return $response;
	}
}
