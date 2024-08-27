<?php
/**
 * Plugin Name: Omnisend for LifterLMS Add-On
 * Description: A LifterLMS add-on to sync contacts with Omnisend. In collaboration with LifterLMS plugin it enables better customer tracking
 * Version: 1.0.0
 * Author: Omnisend
 * Author URI: https://www.omnisend.com
 * Developer: Omnisend
 * Developer URI: https://developers.omnisend.com
 * Text Domain: omnisend-for-lifterlms-add-on
 * ------------------------------------------------------------------------
 * Copyright 2024 Omnisend
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package OmnisendLifterLMSPlugin
 */

use Omnisend\LifterLMSAddon\Actions\OmnisendAddOnAction;
use Omnisend\LifterLMSAddon\Service\SettingsService;
use Omnisend\LifterLMSAddon\Service\ConsentService;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'OMNISEND_LIFTERLMS_ADDON_NAME', 'Omnisend for Lifter LMS Add-On' );
define( 'OMNISEND_LIFTERLMS_ADDON_VERSION', '1.0.0' );

spl_autoload_register( array( 'Omnisend_LifterLMSAddOn', 'autoloader' ) );
add_action( 'plugins_loaded', array( 'Omnisend_LifterLMSAddOn', 'check_plugin_requirements' ) );
add_action( 'admin_enqueue_scripts', array( 'Omnisend_LifterLMSAddOn', 'load_custom_wp_admin_style' ) );

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( 'Omnisend_LifterLMSAddOn', 'add_settings_link' ) );

$omnisend_lifterlms_addon_settings = new SettingsService();
$omnisend_lifterlms_addon_consent  = new ConsentService();

/**
 * Class Omnisend_LifterLMSAddOn
 */
class Omnisend_LifterLMSAddOn {

	/**
	 * Register actions for the Omnisend Lifter LMS Add-On.
	 *
	 * @param array $actions The array of actions.
	 *
	 * @return array The modified array of actions.
	 */
	public static function register_actions( array $actions ): array {
		new OmnisendAddOnAction();

		return $actions;
	}

	/**
	 * Autoloader function to load classes dynamically.
	 *
	 * @param string $class_name The name of the class to load.
	 */
	public static function autoloader( string $class_name ): void {
		$namespace = 'Omnisend\LifterLMSAddon';

		if ( strpos( $class_name, $namespace ) !== 0 ) {
			return;
		}

		$class       = str_replace( $namespace . '\\', '', $class_name );
		$class_parts = explode( '\\', $class );
		$class_file  = 'class-' . strtolower( array_pop( $class_parts ) ) . '.php';

		$directory = plugin_dir_path( __FILE__ );
		$path      = $directory . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . implode( DIRECTORY_SEPARATOR, $class_parts ) . DIRECTORY_SEPARATOR . $class_file;

		if ( file_exists( $path ) ) {
			require_once $path;
		}
	}

	/**
	 * Check plugin requirements.
	 */
	public static function check_plugin_requirements(): void {
		require_once ABSPATH . '/wp-admin/includes/plugin.php';
		$lifterlms_addon_plugin = 'omnisend-for-lifterlms-add-on/class-omnisend-lifterlmsaddon.php';

		$omnisend_plugin = 'omnisend/class-omnisend-core-bootstrap.php';

		if ( ! file_exists( WP_PLUGIN_DIR . '/' . $omnisend_plugin ) || ! is_plugin_active( $omnisend_plugin ) ) {
			deactivate_plugins( $lifterlms_addon_plugin );
			add_action( 'admin_notices', array( 'Omnisend_LifterLMSAddOn', 'omnisend_notice' ) );

			return;
		}

		if ( ! Omnisend\SDK\V1\Omnisend::is_connected() ) {
			deactivate_plugins( $lifterlms_addon_plugin );
			add_action( 'admin_notices', array( 'Omnisend_LifterLMSAddOn', 'omnisend_api_key_notice' ) );

			return;
		}

		$lifterlms_forms_plugin = 'lifterlms/lifterlms.php';

		if ( ! file_exists( WP_PLUGIN_DIR . '/' . $lifterlms_forms_plugin ) || ! is_plugin_active( $lifterlms_forms_plugin ) ) {
			deactivate_plugins( $lifterlms_addon_plugin );
			add_action( 'admin_notices', array( 'Omnisend_LifterLMSAddOn', 'lifterlms_notice' ) );
		}

		add_action( 'lms_registered_form_actions', array( 'Omnisend_LifterLMSAddOn', 'register_actions' ), 10, 1 );
	}

	/**
	 * Display a notice for the missing Omnisend API key.
	 */
	public static function omnisend_api_key_notice(): void {
		echo '<div class="error"><p>' . esc_html__( 'Your Omnisend is not configured properly. Please configure it firstly', 'omnisend-lifterlms' ) . '</p></div>';
	}

	/**
	 * Display a notice for the missing Omnisend Plugin.
	 */
	public static function omnisend_notice(): void {
		echo '<div class="error"><p>' . esc_html__( 'Plugin Omnisend is deactivated. Please install and activate ', 'omnisend-lifterlms' ) . '<a href="https://wordpress.org/plugins/omnisend/">' . esc_html__( 'Omnisend plugin.', 'omnisend-lifterlms' ) . '</a></p></div>';
	}

	/**
	 * Display a notice for the missing LifterLMS plugin.
	 */
	public static function lifterlms_notice(): void {
		echo '<div class="error"><p>' . esc_html__( 'Plugin Omnisend for LifterLMS Add-On is deactivated. Please install and activate LifterLMS plugin.', 'omnisend-lifterlms' ) . '</p></div>';
	}

	/**
	 * Loading styles in admin.
	 */
	public static function load_custom_wp_admin_style(): void {
		wp_register_style( 'omnisend-lifterlms-addon', plugins_url( 'css/omnisend-lifterlms-addon.css', __FILE__ ), array(), OMNISEND_LIFTERLMS_ADDON_VERSION );
		wp_enqueue_style( 'omnisend-lifterlms-addon' );
	}

	public static function add_settings_link( $links ): array {
		$settings_link = '<a href="options-general.php?page=omnisend-lifterlms">Settings</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}
}
