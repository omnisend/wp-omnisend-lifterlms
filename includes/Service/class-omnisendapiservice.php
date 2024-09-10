<?php
/**
 * Omnisend Api service
 *
 * @package OmnisendLifterLMSPlugin
 */

declare(strict_types=1);

namespace Omnisend\LifterLMSAddon\Service;

use LLMS_Student;
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
	 * Creates Omnisend contacts from existing users when plugin is activated.
	 */
	public function create_users_as_omnisend_contacts(): void {
		$all_users       = get_users();
		$non_admin_users = array_filter(
			$all_users,
			function ( $user ) {
				return ! in_array( 'administrator', $user->roles );
			}
		);

		if ( empty( $non_admin_users ) ) {
			return;
		}

		foreach ( $non_admin_users as $user ) {
			$all_user_memberships = $this->get_student_memberships( $user->ID );
			$all_user_courses     = $this->get_student_courses( $user->ID );

			$user_info = array(
				'first_name'  => get_user_meta( $user->ID, 'first_name', true ),
				'last_name'   => get_user_meta( $user->ID, 'last_name', true ),
				'address1'    => get_user_meta( $user->ID, 'llms_billing_address_1', true ),
				'address2'    => get_user_meta( $user->ID, 'llms_billing_address_2', true ),
				'city'        => get_user_meta( $user->ID, 'llms_billing_city', true ),
				'state'       => get_user_meta( $user->ID, 'llms_billing_state', true ),
				'zipcode'     => get_user_meta( $user->ID, 'llms_billing_zip', true ),
				'country'     => get_user_meta( $user->ID, 'llms_billing_country', true ),
				'phone'       => get_user_meta( $user->ID, 'llms_phone', true ),
				'email'       => $user->data->user_email,
				'memberships' => $all_user_memberships,
				'courses'     => $all_user_courses,
			);

			$contact = $this->contact_mapper->create_contact_from_user_info( $user_info );
			$this->client->save_contact( $contact );
		}
	}

	/**
	 * get student memberships by user id
	 *
	 * @return array all enrolled membership names.
	 */
	public function get_student_memberships( $user_id ): array {
		$student              = new LLMS_Student( $user_id );
		$memberships          = $student->get_memberships();
		$all_user_memberships = array();

		if ( ! empty( $memberships ) ) {
			foreach ( $memberships as $membership_id ) {
				$membership_title = get_the_title( $membership_id );
				if ( $membership_title != '' ) {
					$all_user_memberships[] = $membership_title;
				}
			}
		}

		return $all_user_memberships;
	}

	/**
	 * get student courses by user id
	 *
	 * @return array all enrolled courses names.
	 */
	public function get_student_courses( $user_id ): array {
		$student          = new LLMS_Student( $user_id );
		$courses          = $student->get_courses();
		$all_user_courses = array();

		if ( ! empty( $courses ) ) {
			foreach ( $courses as $course_id ) {
				$course_title = get_the_title( $course_id );
				if ( $course_title != '' ) {
					$all_user_courses[] = $course_title;
				}
			}
		}

		return $all_user_courses;
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
