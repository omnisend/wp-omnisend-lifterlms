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
use Omnisend\SDK\V1\Batch;
use WP_User;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Omnisend API Service.
 */
class OmnisendApiService {
	private const CONTACT_BATCH_LIMIT = 60;

	/**
	 * Contact mapper.
	 *
	 * @var ContactMapper
	 */
	private $contact_mapper;

	/**
	 * Omnisend client
	 *
	 * @var Client
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
	 * @return void
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
	 *
	 * @return void
	 */
	public function create_users_as_omnisend_contacts(): void {
		$cycle = 0;

		while ( true ) {
			$contacts  = array();
			$all_users = get_users(
				array(
					'number' => self::CONTACT_BATCH_LIMIT,
					'offset' => self::CONTACT_BATCH_LIMIT * $cycle++,
				)
			);

			$non_admin_users = array_filter(
				$all_users,
				function ( $user ) {
					return ! in_array( 'administrator', $user->roles );
				}
			);

			if ( empty( $non_admin_users ) ) {
				return;
			}

			$all_users = array();

			foreach ( $non_admin_users as $user ) {
				$user_info  = $this->get_user_info( $user );
				$contacts[] = $this->contact_mapper->create_contact_from_user_info( $user_info );
			}

			$this->send_batch( $contacts, Batch::POST_METHOD );
		}
	}

	/**
	 * Gets user array with LLMS data for use as Omnisend contact
	 *
	 * @param WP_User $user
	 *
	 * @return array
	 */
	private function get_user_info( WP_User $user ): array {
		$user_id = $user->ID;

		return array(
			'email'       => $user->data->user_email,
			'first_name'  => get_user_meta( $user_id, 'first_name', true ),
			'last_name'   => get_user_meta( $user_id, 'last_name', true ),
			'address1'    => get_user_meta( $user_id, 'llms_billing_address_1', true ),
			'address2'    => get_user_meta( $user_id, 'llms_billing_address_2', true ),
			'city'        => get_user_meta( $user_id, 'llms_billing_city', true ),
			'state'       => get_user_meta( $user_id, 'llms_billing_state', true ),
			'zipcode'     => get_user_meta( $user_id, 'llms_billing_zip', true ),
			'country'     => get_user_meta( $user_id, 'llms_billing_country', true ),
			'phone'       => get_user_meta( $user_id, 'llms_phone', true ),
			'memberships' => $this->get_student_memberships( $user_id ),
			'courses'     => $this->get_student_courses( $user_id ),
		);
	}

	/**
	 * Sends batch to Omnisend
	 *
	 * @param array  $items
	 * @param string $method
	 *
	 * @return void
	 */
	private function send_batch( array $items, string $method ): void {
		if ( empty( $items ) ) {
			return;
		}

		$batch = new Batch();
		$batch->set_items( $items );
		$batch->set_method( $method );

		$this->client->send_batch( $batch );
	}

	/**
	 * Get student memberships by user id
	 *
	 * @return array all enrolled membership names.
	 */
	public function get_student_memberships( $user_id ): array {
		$student              = new LLMS_Student( $user_id );
		$memberships          = $student->get_memberships();
		$all_user_memberships = array();

		if ( array_key_exists( 'results', $memberships ) && ! empty( $memberships['results'] ) ) {
			foreach ( $memberships['results'] as $membership_id ) {
				$membership_title = get_the_title( $membership_id );

				if ( $membership_title != '' ) {
					$all_user_memberships[] = $membership_title;
				}
			}
		}

		return $all_user_memberships;
	}

	/**
	 * Get student courses by user id
	 *
	 * @return array all enrolled courses names.
	 */
	public function get_student_courses( $user_id ): array {
		$student          = new LLMS_Student( $user_id );
		$courses          = $student->get_courses();
		$all_user_courses = array();

		if ( array_key_exists( 'results', $courses ) && ! empty( $courses['results'] ) ) {
			foreach ( $courses['results'] as $course_id ) {
				$course_title = get_the_title( $course_id );

				if ( $course_title != '' ) {
					$all_user_courses[] = $course_title;
				}
			}
		}

		return $all_user_courses;
	}

	/**
	 * Get an Omnisend contact by email.
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


	/**
	 * Update an Omnisend contact consent data.
	 *
	 * @param array $update_data The user consent data.
	 * @param string $user_email The user email address.
	 *
	 * @return void
	 */
	public function update_consent( array $update_data, string $user_email ): void {
		$response = $this->client->get_contact_by_email( $user_email );

		if ( $response->get_contact()->get_email_status() == 'subscribed' ) {
			$update_data['llmsconsentEmail'] = 1;
		}

		if ( $response->get_contact()->get_phone_status() == 'subscribed' ) {
			$update_data['llmsconsentPhone'] = 1;
		}

		$contact = $this->contact_mapper->update_contact( $update_data );
		$this->client->save_contact( $contact );
	}
}
