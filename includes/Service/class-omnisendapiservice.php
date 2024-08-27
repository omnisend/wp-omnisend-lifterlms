<?php
/**
 * Omnisend Api service
 *
 * @package OmnisendLifterLMSPlugin
 */

declare(strict_types=1);

namespace Omnisend\LifterLMSAddon\Service;

use Omnisend\LifterLMSAddon\Actions\OmnisendAddOnAction;
use Omnisend\LifterLMSAddon\Mapper\ContactMapper;
use Omnisend\LifterLMSAddon\Validator\ResponseValidator;
use Omnisend\SDK\V1\Omnisend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Omnisend API Service.
 */
class OmnisendApiService {
	/**
	 * Contact mapper.
	 *
	 * @var ContactMapper
	 */
	private $contact_mapper;

	/**
	 * Omnisend client
	 *
	 * @var Omnisend
	 */
	private $client;

	/**
	 * Response validator
	 *
	 * @var ResponseValidator
	 */
	private $response_validator;

	/**
	 * OmnisendApiService class constructor.
	 */
	public function __construct() {
		$this->contact_mapper     = new ContactMapper();
		$this->response_validator = new ResponseValidator();
		$this->client             = Omnisend::get_client(
			OMNISEND_LIFTERLMS_ADDON_NAME,
			OMNISEND_LIFTERLMS_ADDON_VERSION
		);
	}

	/**
	 * Creates an Omnisend contact.
	 *
	 * @param array $form_data The form data.
	 *
	 * @return array Tracker data.
	 */
	public function create_omnisend_contact( array $form_data ): array {
		$contact  = $this->contact_mapper->create_contact( $form_data );
		$response = $this->client->save_contact( $contact );

		if ( ! $this->response_validator->is_valid( $response ) ) {
			return array();
		}

		return array(
			OmnisendAddOnAction::EMAIL        => $form_data['email_address'],
			OmnisendAddOnAction::PHONE_NUMBER => $form_data['llms_phone'],
		);
	}

	/**
	 * Update an Omnisend contact.
	 *
	 * @param array $form_data The form data.
	 *
	 */
	public function update_omnisend_contact( array $form_data ): void {
		$contact = $this->contact_mapper->update_contact( $form_data );
		$this->client->save_contact( $contact );
	}

	/**
	 * Update an Omnisend contact for courses data.
	 *
	 * @param string $user_email The user email address.
	 * @param int $course_id The enrolled course name.
	 * @param string $action The enrolled course name.
	 *
	 * @return array|null Tracker data.
	 */
	public function update_omnisend_enrolment_data( string $user_email, int $course_id, string $action = 'add' ): ?array {
		$response      = $this->client->get_contact_by_email( $user_email );
		$contract_data = $response->get_contact();

		$contact  = $this->contact_mapper->update_courses_omnisend_contract( $contract_data, $course_id, $action );
		$response = $this->client->save_contact( $contact );

		if ( ! $this->response_validator->is_valid( $response ) ) {
			return array();
		}

		return null;
	}

	/**
	 * Update an Omnisend contact for membership data.
	 *
	 * @param string $user_email The user email address.
	 * @param int $membership_id The enrolled course name.
	 * @param string $action The enrolled course name.
	 *
	 * @return array|null Tracker data.
	 */
	public function update_omnisend_memberships_data( string $user_email, int $membership_id, string $action = 'add' ): ?array {
		$response      = $this->client->get_contact_by_email( $user_email );
		$contract_data = $response->get_contact();

		$contact  = $this->contact_mapper->update_memberships_omnisend_contract( $contract_data, $membership_id, $action );
		$response = $this->client->save_contact( $contact );

		if ( ! $this->response_validator->is_valid( $response ) ) {
			return array();
		}

		return null;
	}

	/**
	 * get an Omnisend contact by email.
	 *
	 * @return array omnisend contact consent data.
	 */
	public function get_omnisend_contact_consent(): array {
		$current_user = wp_get_current_user();

		if ( isset( $current_user->user_email ) ) {
			$user_email = $current_user->user_email;
			$response   = $this->client->get_contact_by_email( $user_email );

			$contract_data['sms']   = $response->get_contact()->get_phone_status();
			$contract_data['email'] = $response->get_contact()->get_email_status();
		} else {
			$contract_data['sms']   = false;
			$contract_data['email'] = false;
		}
		return $contract_data;
	}
}
