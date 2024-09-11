<?php
/**
 * Omnisend Consent service
 *
 * @package OmnisendLifterLMSPlugin
 */

declare(strict_types=1);

namespace Omnisend\LifterLMSAddon\Service;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ConsentService
 *
 * @package Omnisend\OmnisendLifterLMSPlugin\Service
 */
class ConsentService {
	public function __construct() {
		$options = get_option( 'omnisend_lifterlms_options' );
		if ( isset( $options['filter_lms_consent_setting'] ) ) {
			add_filter( 'llms_get_form_html', array( $this, 'add_consent_llms_form_fields' ), 10, 2 );
		}

		add_action( 'lifterlms_user_registered', array( $this, 'omnisend_save_register_fields' ) );
		add_action( 'llms_before_user_account_update_submit', array( $this, 'omnisend_update_register_fields' ) );

		add_action( 'llms_user_enrolled_in_course', array( $this, 'omnisend_add_enrollment_data' ), 10, 2 );
		add_action( 'llms_user_removed_from_course', array( $this, 'user_removed_from_course' ), 10, 2 );

		add_action( 'llms_user_added_to_membership_level', array( $this, 'add_omnisend_memberships_data' ), 10, 2 );
		add_action( 'llms_user_removed_from_membership', array( $this, 'user_removed_from_membership' ), 10, 2 );
	}

	public function add_consent_llms_form_fields( $html ): string {
		$omnisend_api  = new OmnisendApiService();
		$contract_data = $omnisend_api->get_omnisend_contact_consent();

		$email_consent = '';
		if ( $contract_data['email'] ) {
			$email_consent = 'checked';
		}

		$sms_consent = '';
		if ( $contract_data['sms'] ) {
			$sms_consent = 'checked';
		}

		$custom_fields_html = '<div class="llms-form-field type-checkbox llms-cols-12 llms-cols-last">
			<label for="llmsconsentEmail">' . esc_html( __( 'Subscribe me to your mailing lists', 'omnisend-lifter_lms' ) ) . '</label>
			<input id="llmsconsentEmail" name="llmsconsentEmail" ' . esc_html( $email_consent ) . ' type="checkbox" class="input" value="1">
		</div>
		<div class="llms-form-field type-checkbox llms-cols-12 llms-cols-last">
			<label for="llmsconsentPhone">' . esc_html( __( 'Subscribe me to your SMS lists', 'omnisend-lifter_lms' ) ) . '</label>
			<input id="llmsconsentPhone" name="llmsconsentPhone" ' . esc_html( $sms_consent ) . ' type="checkbox" class="input" value="1">
		</div>';

		$html .= $custom_fields_html;

		return $html;
	}


	/**
	 * Custom function to trigger when a user is removed from a course.
	 *
	 * @param int $user_id  The ID of the user being removed.
	 * @param int $course_id The ID of the course from which the user is being removed.
	 */
	public function user_removed_from_course( int $user_id, int $course_id ): void {
		$user_info  = get_userdata( $user_id );
		$user_email = $user_info->data->user_email;

		$omnisend_api = new OmnisendApiService();
		$omnisend_api->update_omnisend_enrolment_data( $user_email, $course_id, 'remove' );
	}

	/**
	 * Custom function to trigger when a user is removed from a membership.
	 *
	 * @param int $user_id  The ID of the user being removed.
	 * @param int $course_id The ID of the course from which the user is being removed.
	 */
	public function user_removed_from_membership( int $user_id, int $course_id ): void {
		$user_info  = get_userdata( $user_id );
		$user_email = $user_info->data->user_email;

		$omnisend_api = new OmnisendApiService();
		$omnisend_api->update_omnisend_memberships_data( $user_email, $course_id, 'remove' );
	}

	/**
	 * Custom function to trigger when a user is added to membership.
	 *
	 * @param int $user_id  The ID of the user being removed.
	 * @param int $membership_id The ID of the course from which the user is being removed.
	 */
	public function add_omnisend_memberships_data( int $user_id, int $membership_id ): void {
		$user_info  = get_userdata( $user_id );
		$user_email = $user_info->data->user_email;

		$omnisend_api = new OmnisendApiService();
		$omnisend_api->update_omnisend_memberships_data( $user_email, $membership_id, 'add' );
	}

	/**
	 * Custom function to trigger when a user just registered for first time.
	 *
	 * @param int $user_id  The ID of the user being removed.
	 */
	public function omnisend_save_register_fields( $user_id ): void {
		if ( isset( $user_id ) && isset( $_POST['_llms_register_person_nonce'] ) && check_admin_referer( 'llms_register_person', '_llms_register_person_nonce' ) ) {
			$omnisend_api = new OmnisendApiService();
			$omnisend_api->create_omnisend_contact( $_REQUEST );
		}
	}

	/**
	 * Custom function to trigger when a user editing his profile.
	 *
	 * @param int $user_id The ID of the user profile.
	 */
	public function omnisend_update_register_fields( $user_id ): void {
		if ( isset( $user_id ) && isset( $_POST['_llms_update_person_nonce'] ) && check_admin_referer( 'llms_update_person', '_llms_update_person_nonce' ) ) {
			$omnisend_api = new OmnisendApiService();
			$omnisend_api->update_omnisend_contact( $_REQUEST );
		}
	}

	/**
	 * Custom function to trigger when a user just enrolled to course.
	 *
	 * @param int $user_id  The ID of the user being enrolled.
	 * @param int $course_id The ID of the course where user is enrolled.
	 */
	public function omnisend_add_enrollment_data( int $user_id, $course_id ): void {
		$user_info  = get_userdata( $user_id );
		$user_email = $user_info->data->user_email;

		if ( isset( $user_email ) && isset( $course_id ) ) {
			$omnisend_api = new OmnisendApiService();
			$omnisend_api->update_omnisend_enrolment_data( $user_email, $course_id );
		}
	}
}
