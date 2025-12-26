<?php
/**
 * Plugin Name: WPSyncSheets Lite For Contact Form 7
 * Plugin URI: https://www.wpsyncsheets.com/wpsyncsheets-for-contact-form-7/
 * Description: Send your Contact Form 7 data to your Google Sheets spreadsheet.
 * Version: 1.6.9.5
 * Author: Creative Werk Designs
 * Author URI: https://www.creativewerkdesigns.com/
 * Text Domain: wpssc
 * Domain Path: /languages
 *
 * @package     contactsheets-lite
 * @author      Creative Werk Designs
 * @Category    Plugin
 * @copyright   Copyright (c) 2025 Creative Werk Designs
 */

if( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'is_plugin_active' ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}
if ( ! get_option( 'active_wpsyncsheets_contactform7' ) && ! is_plugin_active( 'wpsyncsheets-for-contact-form-7/wpsyncsheets-for-contact-form-7.php' )) {
	// Changed.
	define( 'WPSSLC_ENVIRONMENT', 'production' ); // or development.
	// Plugin version.
	define( 'WPSSLC_VERSION', '1.6.9.5' );
	define( 'WPSSLC_DB_VERSION', '1.0' );
	define( 'WPSSLC_PLUGIN_ITEM_ID', '1390' );
	define( 'WPSSLC_DIR', plugin_dir_path( __FILE__ ) );
	define( 'WPSSLC_ROOT', dirname( __FILE__ ) );
	define( 'WPSSLC_URL', plugins_url( '/', __FILE__ ) );
	define( 'WPSSLC_BASE_FILE', basename( dirname( __FILE__ ) ) . '/wpsyncsheets-lite-contact-form-7.php' );
	define( 'WPSSLC_BASE_NAME', plugin_basename( __FILE__ ) );
	define( 'WPSSLC_PATH', plugin_dir_path( __FILE__ ) );// use for include files to other files.
	define( 'WPSSLC_DIRECTORY', dirname( plugin_basename( __FILE__ ) ) );
	define( 'WPSSLC_DOCS_LINK', 'https://docs.wpsyncsheets.com/wpssc-setup-guide/' );
	define( 'WPSSLC_DOC_MENU_URL', 'https://docs.wpsyncsheets.com' );
	define( 'WPSSLC_SUPPORT_MENU_URL', 'https://wordpress.org/support/plugin/contactsheets-lite/' );
	define( 'WPSSLC_PRO_VERSION_URL', 'https://www.wpsyncsheets.com/wpsyncsheets-for-contact-form-7/?utm_source=liteplugin&utm_medium=admindescription&utm_campaign=wpsyncsheets_lite_for_contact_form_7' );
	define( 'WPSSLC_PRO_VERSION_BUY_URL', 'https://www.wpsyncsheets.com/wpsyncsheets-for-contact-form-7/?utm_source=liteplugin&utm_medium=admindescription&utm_campaign=wpsyncsheets_lite_for_contact_form_7' );

	if ( ! class_exists( 'WPSSLC_Dependencies' ) ) {
		require_once trailingslashit( dirname( __FILE__ ) ) . 'includes/class-wpsslc-dependencies.php';
	}

	if ( WPSSLC_Dependencies::wpsslc_is_contact_form_7_plugin_active() ) {

		/**
		 * Remove capability.
		 */
		function wpsslc_remove_custom_capability_from_all_roles() {
			// Get all roles.
			global $wp_roles;

			// Ensure $wp_roles is properly initialized.
			if ( ! $wp_roles instanceof WP_Roles ) {
				return;
			}

			// Iterate through each role.
			foreach ( $wp_roles->roles as $role_name => $role_info ) {
				$role = get_role( $role_name );
				if ( $role ) {
					if ( $role->has_cap( 'edit_wpsyncsheets_contact_form_7_lite_main_settings' ) ) {
						$role->remove_cap( 'edit_wpsyncsheets_contact_form_7_lite_main_settings' );
					}
					if ( $role->has_cap( 'edit_wpsyncsheets_contact_form_7_lite_form_settings' ) ) {
						$role->remove_cap( 'edit_wpsyncsheets_contact_form_7_lite_form_settings' );
					}
				}
			}
		}
		register_deactivation_hook( __FILE__, 'wpsslc_remove_custom_capability_from_all_roles' );

		/**
		 * Add capability.
		 */
		function wpsslc_add_custom_capability_to_specific_roles() {
			$specific_roles = array( 'administrator' );

			foreach ( $specific_roles as $role_name ) {
				$role = get_role( $role_name );
				if ( $role ) {
					if ( ! $role->has_cap( 'edit_wpsyncsheets_contact_form_7_lite_main_settings' ) ) {
						$role->add_cap( 'edit_wpsyncsheets_contact_form_7_lite_main_settings' );
					}
					if ( ! $role->has_cap( 'edit_wpsyncsheets_contact_form_7_lite_form_settings' ) ) {
						$role->add_cap( 'edit_wpsyncsheets_contact_form_7_lite_form_settings' );
					}
				}
			}
		}
		register_activation_hook( __FILE__, 'wpsslc_add_custom_capability_to_specific_roles' );
		add_action( 'init', 'wpsslc_add_custom_capability_to_specific_roles' );

		// Add methods if Contact Form 7 is active.
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wpsslc_add_action_links' );
		/**
		 *  Add action link if Contact Form 7 is active.
		 *
		 * @param array $links .
		 * @return array
		 */
		function wpsslc_add_action_links( $links ) {
			$mylinks = array(
				'<a href="' . esc_url( admin_url( 'admin.php?page=wpsyncsheets-contact-form-7' ) ) . '">' . esc_html__( 'Settings', 'wpssc' ) . '</a>',
				'<a style="font-weight:bold; color: #0040ff;" target="_blank" href="' . WPSSLC_PRO_VERSION_BUY_URL . '">Upgrade to Pro</a>',
			);
			return array_merge( $links,$mylinks);
		}

		// Define the class and the function.
		require_once dirname( __FILE__ ) . '/src/class-contactsheetslite.php';
		wpsslc();
	} else {
		add_action( 'admin_notices', 'wpsslc_admin_notice' );
		if ( ! function_exists( 'wpsslc_admin_notice' ) ) {
			/**
			 *  Add admin notice.
			 */
			function wpsslc_admin_notice() {
				echo '<div class="notice error wpsslc-error">
					<div class="wpsslc-message-icon" >
						<p>WPSyncSheets Lite For Contact Form 7 plugin requires <a href=' . esc_url( 'https://wordpress.org/plugins/contact-form-7/' ) . '>Contact Form 7</a> plugin to be active!</p>
						</div>
				</div>';
			}
		}
	}
}
