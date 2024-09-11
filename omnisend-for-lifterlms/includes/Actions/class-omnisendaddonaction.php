<?php
/**
 * Omnisend Addon Action
 *
 * @package OmnisendLifterLMSPlugin
 */

declare(strict_types=1);

namespace Omnisend\LifterLMSAddon\Actions;

use Omnisend\LifterLMSAddon\Service\OmnisendApiService;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Omnisend Addon
 */
class OmnisendAddOnAction {
	const EMAIL           = 'email';
	const PHONE_NUMBER    = 'phone_number';
	const EMAIL_CONSENT   = 'email_consent';
	const PHONE_CONSENT   = 'phone_consent';
	const OMNISEND_FIELDS = array(
		self::EMAIL         => 'Email',
		self::PHONE_NUMBER  => 'Phone Number',
		self::EMAIL_CONSENT => 'Email Consent',
		self::PHONE_CONSENT => 'Phone Consent',
	);
	/**
	 * Omnisend service
	 *
	 * @var OmnisendApiService
	 */
	private $omnisend_service;
	/**
	 * Snippet path
	 *
	 * @var string
	 */
	private $snippet_path;
	/**
	 * Settings Provider
	 *
	 * @var OmnisendActionSettingsProvider
	 */
	private $settings;
	/**
	 * Creating an Action
	 */
	public function __construct() {
		$this->omnisend_service = new OmnisendApiService();
	}
}
