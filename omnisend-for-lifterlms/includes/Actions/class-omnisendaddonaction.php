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
	public const EMAIL        = 'email';
	public const PHONE_NUMBER = 'phone_number';

	/**
	 * Omnisend service
	 *
	 * @var OmnisendApiService
	 */
	private $omnisend_service;

	/**
	 * Creating an Action
	 */
	public function __construct() {
		$this->omnisend_service = new OmnisendApiService();
	}
}
