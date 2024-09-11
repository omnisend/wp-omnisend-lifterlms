<?php
/**
 * Omnisend Settings service
 *
 * @package OmnisendLifterLMSPlugin
 */

declare(strict_types=1);

namespace Omnisend\LifterLMSAddon\Service;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SettingsService
 *
 * @package Omnisend\OmnisendLifterLMSPlugin\Service
 */
class SettingsService {
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_init', array( $this, 'settings_init' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'add_settings_link' ) );
	}

	public function add_menu(): void {
		add_options_page(
			'Omnisend for Lifter LMS Options',
			'LifterLMS Omnisend',
			'manage_options',
			'omnisend-lifterlms',
			array( $this, 'options_page' )
		);
	}

	public function options_page(): void {
		?>
		<div class="wrap">
			<h1>Omnisend for LifterLMS Options</h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'omnisend_lifterlms_options_group' );
				do_settings_sections( 'omnisend-lifterlms' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	public function settings_init(): void {
		register_setting( 'omnisend_lifterlms_options_group', 'omnisend_lifterlms_options' );

		add_settings_section(
			'omnisend_lifterlms_settings_section',
			'Settings',
			array( $this, 'settings_section_callback' ),
			'omnisend-lifterlms'
		);

		add_settings_field(
			'omnisend_lifterlms_filter_lms_consent_setting',
			'Enable consent collection',
			array( $this, 'filter_lms_consent_setting_callback' ),
			'omnisend-lifterlms',
			'omnisend_lifterlms_settings_section'
		);
	}

	public function settings_section_callback(): void {
		echo '<p class="information-notice">Depending on the privacy laws of your country of operation, it is recommended to enable marketing opt-in checkboxes in Account Creation & Course Checkout forms to collect marketing consent from your customers</p>';
		echo '<p>If you wish to enable consent collection, check below</p>';
	}

	public function filter_lms_consent_setting_callback(): void {
		$options = get_option( 'omnisend_lifterlms_options' );
		if ( isset( $options['filter_lms_consent_setting'] ) && $options['filter_lms_consent_setting'] == '1' ) {
			$checked_form = 'checked';
		} else {
			$checked_form = '';
		}

		?>
		<input type="checkbox" name="omnisend_lifterlms_options[filter_lms_consent_setting]" <?php echo esc_html( $checked_form ); ?> value="1" />
		<?php
	}

	public function add_settings_link( $links ) {
		$settings_link = '<a href="options-general.php?page=omnisend-lifterlms">Settings</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}
}
