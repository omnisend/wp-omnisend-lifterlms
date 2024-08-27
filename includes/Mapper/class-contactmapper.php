<?php
/**
 * Contact mapper
 *
 * @package OmnisendLifterLMSPlugin
 */

declare(strict_types=1);

namespace Omnisend\LifterLMSAddon\Mapper;

use Omnisend\SDK\V1\Contact;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ContactMapper
 */
class ContactMapper {
	private const CUSTOM_PREFIX  = 'lifter_lms';
	private const CONSENT_PREFIX = 'lifter_lms';

	/**LifterLMSAddon
	 * Get Contact object
	 *
	 * @param array  $mapped_fields
	 *
	 * @return Contact object
	 */
	public function get_omnisend_contact( array $mapped_fields ): Contact {
		$options = get_option( 'omnisend_lifterlms_options' );
		$contact = new Contact();

		$contact->set_email( $mapped_fields['email_address'] );
		$contact->set_phone( $mapped_fields['llms_phone'] );
		$contact->set_first_name( $mapped_fields['first_name'] ?? '' );
		$contact->set_last_name( $mapped_fields['last_name'] ?? '' );
		$contact->set_postal_code( $mapped_fields['llms_billing_zip'] ?? '' );
		$contact->set_address( $mapped_fields['llms_billing_address_1'] . ' ' . $mapped_fields['llms_billing_address_2'] ?? '' );
		$contact->set_state( $mapped_fields['llms_billing_state'] ?? '' );
		$contact->set_country( $mapped_fields['llms_billing_country'] ?? '' );
		$contact->set_city( $mapped_fields['llms_billing_city'] ?? '' );

		$contact->set_welcome_email( true );

		if ( ( isset( $mapped_fields['llmsconsentEmail'] ) && isset( $mapped_fields['email_address'] ) ) || ! isset( $options['filter_lms_consent_setting'] ) ) {
			$contact->set_email_consent( self::CONSENT_PREFIX );
			$contact->set_email_opt_in( $mapped_fields['email_address'] );
		} else {
			$contact->set_email_consent( self::CONSENT_PREFIX );
			$contact->set_email_opt_in( '' );
		}

		if ( ( isset( $mapped_fields['llmsconsentPhone'] ) && isset( $mapped_fields['llms_phone'] ) ) || ! isset( $options['filter_lms_consent_setting'] ) ) {
			$contact->set_phone_consent( self::CONSENT_PREFIX );
			$contact->set_phone_opt_in( $mapped_fields['llms_phone'] );
		} else {
			$contact->set_phone_consent( self::CONSENT_PREFIX );
			$contact->set_phone_opt_in( '' );
		}

		$contact->add_tag( self::CUSTOM_PREFIX );

		return $contact;
	}

	/**LifterLMSAddon
	 * Update Contact object
	 *
	 * @param array  $mapped_fields
	 *
	 * @return Contact object
	 */
	public function get_update_omnisend_contact( array $mapped_fields ): Contact {
		$options = get_option( 'omnisend_lifterlms_options' );
		$contact = new Contact();

		$current_user = wp_get_current_user();
		$user_email   = $current_user->user_email;

		$contact->set_email( $user_email );
		$contact->set_phone( $mapped_fields['llms_phone'] );
		$contact->set_first_name( $mapped_fields['first_name'] ?? '' );
		$contact->set_last_name( $mapped_fields['last_name'] ?? '' );
		$contact->set_postal_code( $mapped_fields['llms_billing_zip'] ?? '' );
		$contact->set_address( $mapped_fields['llms_billing_address_1'] . ' ' . $mapped_fields['llms_billing_address_2'] ?? '' );
		$contact->set_state( $mapped_fields['llms_billing_state'] ?? '' );
		$contact->set_country( $mapped_fields['llms_billing_country'] ?? '' );
		$contact->set_city( $mapped_fields['llms_billing_city'] ?? '' );
		$contact->set_welcome_email( true );

		if ( ! isset( $options['filter_lms_consent_setting'] ) ) {
			if ( isset( $mapped_fields['llmsconsentEmail'] ) ) {
				$contact->set_email_subscriber();
				$contact->set_email_consent( self::CONSENT_PREFIX );
			} else {
				$contact->set_email_consent( self::CONSENT_PREFIX );
				$contact->set_email_unsubscriber();
			}

			if ( isset( $mapped_fields['llmsconsentPhone'] ) ) {
				$contact->set_phone_consent( self::CONSENT_PREFIX );
				$contact->set_phone_subscriber();
			} else {
				$contact->set_phone_consent( self::CONSENT_PREFIX );
				$contact->set_phone_unsubscriber();
			}
		} else {
			$contact->set_email_consent( self::CONSENT_PREFIX );
			$contact->set_email_subscriber();

			$contact->set_phone_consent( self::CONSENT_PREFIX );
			$contact->set_phone_subscriber();
		}

		$contact->add_tag( self::CUSTOM_PREFIX );

		return $contact;
	}

	/**
	 * Custom function to add/remove memberships depending on current saved courses
	 *
	 * @param array $contract_data
	 * @param int $course_id
	 * @param string $action
	 *
	 * @return Contact object
	 */
	public function update_courses_omnisend_contract( array $contract_data, int $course_id = 0, string $action = 'add' ): Contact {
		$current_contract_courses = $contract_data['customProperties']['courses'] ?? array();
		$user_email               = $contract_data['email'];

		$course_name = get_the_title( $course_id );
		if ( ! in_array( $course_name, $current_contract_courses ) && '' != $course_name ) {
			if ( 'add' == $action ) {
				$current_contract_courses[] = $course_name;
			}
		} elseif ( in_array( $course_name, $current_contract_courses ) && 'remove' == $action ) {
			$current_contract_courses = array_diff( $current_contract_courses, array( $course_name ) );
		}

		$contact = new Contact();
		$contact->set_email( $user_email );

		if ( empty( $current_contract_courses ) || null ) {
			$current_contract_courses = '';
		}
		$contact->add_custom_property( 'courses', $current_contract_courses );
		$contact->add_tag( self::CUSTOM_PREFIX );

		return $contact;
	}

	/**
	 * Custom function to add/remove memberships depending on current saved memberships
	 *
	 * @param array $contract_data
	 * @param int $membership_id
	 * @param string $action
	 *
	 * @return Contact object
	 */
	public function update_memberships_omnisend_contract( array $contract_data, int $membership_id = 0, string $action = 'add' ): Contact {
		$current_contract_memberships = $contract_data['customProperties']['memberships'] ?? array();
		$user_email                   = $contract_data['email'];

		$membership_name = get_the_title( $membership_id );
		if ( ! in_array( $membership_name, $current_contract_memberships ) && '' != $membership_name ) {
			if ( 'add' == $action ) {
				$current_contract_memberships[] = $membership_name;
			}
		} elseif ( in_array( $membership_name, $current_contract_memberships ) && 'remove' == $action ) {
			$current_contract_memberships = array_diff( $current_contract_memberships, array( $membership_name ) );
		}

		$contact = new Contact();
		$contact->set_email( $user_email );

		if ( empty( $current_contract_memberships ) || null ) {
			$current_contract_memberships = '';
		}
		$contact->add_custom_property( 'memberships', $current_contract_memberships );
		$contact->add_tag( self::CUSTOM_PREFIX );

		return $contact;
	}
}
