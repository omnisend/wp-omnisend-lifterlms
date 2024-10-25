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
	public function create_contact( array $mapped_fields ): Contact {
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

		if ( isset( $options['filter_lms_consent_setting'] ) ) {
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

	/**LifterLMSAddon
	 * Update Contact object
	 *
	 * @param array  $mapped_fields
	 *
	 * @return Contact object
	 */
	public function update_contact( array $mapped_fields ): Contact {
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

		if ( isset( $options['filter_lms_consent_setting'] ) ) {
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
	 * Create all users as Omnisend contacts as non-subscribers
	 *
	 * @param array $user_info
	 *
	 * @return Contact object
	 */
	public function create_contact_from_user_info( array $user_info ): Contact {
		$contact = new Contact();

		$contact->set_email( $user_info['email'] );
		$contact->set_phone( $user_info['phone'] );
		$contact->set_first_name( $user_info['first_name'] );
		$contact->set_last_name( $user_info['last_name'] );
		$contact->set_city( $user_info['city'] );
		$contact->set_state( $user_info['state'] );
		$contact->set_postal_code( $user_info['zipcode'] );
		$contact->set_country( $user_info['country'] );
		$contact->set_address( $user_info['address1'] . ' ' . $user_info['address2'] );

		$contact->add_custom_property( 'memberships', $user_info['memberships'] );
		$contact->add_custom_property( 'courses', $user_info['courses'] );
		$contact->add_tag( self::CUSTOM_PREFIX );

		return $contact;
	}

	/**
	 * Custom function to add/remove memberships depending on current saved courses
	 *
	 * @param Contact $contract_data
	 * @param int $course_id
	 * @param string $action
	 *
	 * @return Contact object
	 */
	public function update_courses_omnisend_contract( Contact $contract_data, int $course_id = 0, string $action = 'add' ): Contact {
		$custom_properties        = $contract_data->get_custom_properties();
		$current_contract_courses = isset( $custom_properties['courses'] ) ? $custom_properties['courses'] : array();
		$user_email               = $contract_data->get_email();

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
	 * @param Contact $contract_data
	 * @param int $membership_id
	 * @param string $action
	 *
	 * @return Contact object
	 */
	public function update_memberships_omnisend_contract( Contact $contract_data, int $membership_id = 0, string $action = 'add' ): Contact {
		$custom_properties            = $contract_data->get_custom_properties();
		$current_contract_memberships = isset( $custom_properties['memberships'] ) ? $custom_properties['memberships'] : array();
		$user_email                   = $contract_data->get_email();

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
