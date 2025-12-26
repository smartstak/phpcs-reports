<?php
/**
 * Plugin Name: WPSyncSheets Lite For Gravity Forms
 * Plugin URI: https://www.wpsyncsheets.com/wpsyncsheets-for-gravity-forms/
 * Description: An automated, run-time solution for Gravity Forms entries. Users can export Gravity Forms entries into a single Google Spreadsheet.
 * Version: 1.6.9.4
 * Author: Creative Werk Designs
 * Author URI: http://www.creativewerkdesigns.com/
 * Text Domain: wpssg
 * Domain Path: /languages
 *
 * @package     wpsyncsheets-gravity-forms
 * @author      Creative Werk Designs
 * @category    Plugin
 * @copyright   Copyright (c) 2025 Creative Werk Designs
 */

if( !defined( 'ABSPATH' ) ) {
	exit;
}


 if (!function_exists('is_plugin_active')) {
    include_once(ABSPATH . 'wp-admin/includes/plugin.php');
}

if ( ! get_option( 'active_wpsyncsheets_gravityforms' ) && ! is_plugin_active( 'wpsyncsheets-for-gravityforms/wpsyncsheets-for-gravityforms.php' ) ) {
	
	// Changed.
	define( 'WPSSLGF_ENVIRONMENT', 'production' ); // or development.
	// Plugin version.
	define( 'WPSSLGF_VERSION', '1.6.9.4' );
	define( 'WPSSLGF_PLUGIN_ITEM_ID', '1388' );
	// Plugin URL.
	define( 'WPSSLGF_URL', plugin_dir_url( __FILE__ ) );
	// Plugin directory.
	define( 'WPSSLGF_DIR', plugin_dir_path( __FILE__ ) );
	define( 'WPSSLGF_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
	define( 'WPSSLGF_DIRECTORY', dirname( plugin_basename( __FILE__ ) ) );
	define( 'WPSSLGF_BASE_FILE', basename( dirname( __FILE__ ) ) . '/wpsyncsheets-lite-gravity-forms.php' );
	define( 'WPSSLGF_DOC_MENU_URL', 'https://docs.wpsyncsheets.com' );
	define( 'WPSSLGF_PRO_VERSION_URL', 'https://www.wpsyncsheets.com/wpsyncsheets-for-gravity-forms/' );
	define( 'WPSSLGF_BUY_PRO_VERSION_URL', 'https://www.wpsyncsheets.com/wpsyncsheets-for-gravity-forms/?utm_source=liteplugin&utm_medium=admindescription&utm_campaign=wpsyncsheets_lite_for_gravity_forms' );
	
	if ( ! class_exists( 'WPSSLGF_Dependencies' ) ) {
		require_once trailingslashit( dirname( __FILE__ ) ) . 'includes/class-wpsslgf-dependencies.php';
	}
	if ( WPSSLGF_Dependencies::wpsslgf_is_gravityform_plugin_active() ) {
		
		/**
		 * Remove capability.
		 */
		function wpsslgf_remove_custom_capability_from_all_roles() {
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
					if ( $role->has_cap( 'edit_wpsyncsheets_gravity_forms_lite_main_settings' ) ) {
						$role->remove_cap( 'edit_wpsyncsheets_gravity_forms_lite_main_settings' );
					}
				}
			}
		}
		register_deactivation_hook( __FILE__, 'wpsslgf_remove_custom_capability_from_all_roles' );

		/**
		 * Add capability.
		 */
		function wpsslgf_add_custom_capability_to_specific_roles() {
			$specific_roles = array( 'administrator' );

			foreach ( $specific_roles as $role_name ) {
				$role = get_role( $role_name );
				if ( $role ) {
					if ( ! $role->has_cap( 'edit_wpsyncsheets_gravity_forms_lite_main_settings' ) ) {
						$role->add_cap( 'edit_wpsyncsheets_gravity_forms_lite_main_settings' );
					}
				}
			}
		}
		register_activation_hook( __FILE__, 'wpsslgf_add_custom_capability_to_specific_roles' );
		add_action( 'init', 'wpsslgf_add_custom_capability_to_specific_roles' );

		// Add methods if Gravity Forms is active.
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wpsslgf_add_action_links' );
		/**
		 * Add plugin settings link at plugin page.
		 *
		 * @param array $links plugin related links.
		 */
		function wpsslgf_add_action_links( $links ) {
			$mylinks = array(
				'<a href="' . esc_url( admin_url( 'admin.php?page=wpsyncsheets-gravity-forms' ) ) . '">' . esc_html__( 'Settings', 'wpsse' ) . '</a>',
				'<a style="font-weight:bold; color: #0040ff;" target="_blank" href="' . WPSSLGF_BUY_PRO_VERSION_URL . '">Upgrade to Pro</a>',
			);
			return array_merge( $links,$mylinks );
		}
		// Define the class and the function.
		require_once dirname( __FILE__ ) . '/src/class-wpsyncsheetsgravityforms.php';
		wpsslgf();
	} else {
		add_action( 'admin_notices', 'wpsslgf_admin_notice' );
		if ( ! function_exists( 'wpsslgf_admin_notice' ) ) {
			/**
			 * Add Gravity Forms plugin missing notice.
			 */
			function wpsslgf_admin_notice() {
				echo '<div class="notice error">
					<div class="wpsslgf-notice">
						<p>WPSyncSheets Lite For Gravity Forms plugin requires <a href="' . esc_url( 'https://www.gravityforms.com/' ) . '" target= "_blank">Gravity Forms</a> plugin to be active!</p>
					</div>
				</div>';
			}
		}
	}
}
