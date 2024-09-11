<?php
/**
 * Omnisend Response Validator
 *
 * @package OmnisendLifterLMSPlugin
 */

declare(strict_types=1);

namespace Omnisend\LifterLMSAddon\Validator;

use Omnisend\SDK\V1\SaveContactResponse;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ResponseValidator
 *
 * @package Omnisend\OmnisendLifterLMSPlugin\Validator
 */
class ResponseValidator {

	/**
	 * Validates response.
	 *
	 * @param SaveContactResponse $response
	 *
	 * @return bool
	 */
	public function is_valid( SaveContactResponse $response ): bool {
		if ( ! empty( $response->get_wp_error()->get_error_message() ) ) {
			error_log( 'Error in after_submission: ' . $response->get_wp_error()->get_error_message()); // phpcs:ignore

			return false;
		}

		if ( empty( $response->get_contact_id() ) ) {
			return false;
		}

		return true;
	}
}
