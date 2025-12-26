<?php
/**

 * Plugin Name: WPSyncSheets Lite For Elementor

 * Plugin URI: https://www.wpsyncsheets.com/wpsyncsheets-for-elementor/

 * Description: Save all Elementor Pro Form entries to Google Spreadsheet

 * Author: Creative Werk Designs

 * Author URI: http://www.creativewerkdesigns.com/

 * Version: 1.5.9.3

 * Text Domain: wpsse

 * Domain Path: /languages
 *
 * @package     wpsyncsheets-elementor

 * @author      Creative Werk Designs

 * @category    Plugin

 * @copyright   Copyright (c) 2025 Creative Werk Designs
 */

if ( ! defined( 'ABSPATH' ) ) {

	exit; // Exit if accessed directly.

}

if ( ! function_exists( 'is_plugin_active' ) ) {

	include_once ABSPATH . 'wp-admin/includes/plugin.php';

}



if ( ! get_option( 'active_wpsyncsheets_elementor' ) && ! is_plugin_active( 'wpsyncsheets-for-elementor/wpsyncsheets-for-elementor.php' ) ) {

	// Changed.
	define( 'WPSSLE_ENVIRONMENT', 'production' ); // or development.

	// Plugin version.

	define( 'WPSSLE_VERSION', '1.5.9.3' );

	define( 'WPSSLE_PLUGIN_ITEM_ID', '1386' );

	// Plugin URL.
	define( 'WPSSLE_URL', plugin_dir_url( __FILE__ ) );

	// Plugin directory.
	define( 'WPSSLE_DIR', plugin_dir_path( __FILE__ ) );

	define( 'WPSSLE_LITE_ROOT', __DIR__ );

	define( 'WPSSLE_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );

	define( 'WPSSLE_DIRECTORY', dirname( plugin_basename( __FILE__ ) ) );

	define( 'WPSSLE_PLUGIN_SLUG', WPSSLE_DIRECTORY . '/' . basename( __FILE__ ) );

	define( 'WPSSLE_BASE_FILE', basename( __DIR__ ) . '/wpsyncsheets-lite-elementor.php' );

	define( 'WPSSLE_PRO_VERSION_URL', 'https://www.wpsyncsheets.com/wpsyncsheets-for-elementor/' );

	define( 'WPSSLE_DOCUMENTATION_URL', 'https://docs.wpsyncsheets.com/wpsse-introduction/' );

	define( 'WPSSLE_DOC_SHEET_SETTING_URL', 'https://docs.wpsyncsheets.com/wpsse-google-sheets-api-settings/' );

	define( 'WPSSLE_SUPPORT_URL', 'https://wordpress.org/support/plugin/wpsyncsheets-elementor/' );

	define( 'WPSSLE_DOC_MENU_URL', 'https://docs.wpsyncsheets.com' );

	define( 'WPSSLE_BUY_PRO_VERSION_URL', 'https://www.wpsyncsheets.com/wpsyncsheets-for-elementor/?utm_source=wp-repo&utm_campaign=wpsyncsheets-for-elementor&utm_medium=description/' );



	if ( ! class_exists( 'WPSSLE_Dependencies' ) ) {

		require_once trailingslashit( __DIR__ ) . 'includes/class-wpssle-dependencies.php';

	}



	// Check Elementor Pro plugin is active or not.

	if ( WPSSLE_Dependencies::wpssle_is_elementor_plugin_active() ) {



		/**

		 * Remove capability.
		 */
		function wpssle_remove_custom_capability_from_all_roles() {

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

					if ( $role->has_cap( 'edit_wpsyncsheets_elementor_lite_main_settings' ) ) {

						$role->remove_cap( 'edit_wpsyncsheets_elementor_lite_main_settings' );

					}

					if ( $role->has_cap( 'edit_wpsyncsheets_elementor_lite_form_settings' ) ) {

						$role->remove_cap( 'edit_wpsyncsheets_elementor_lite_form_settings' );

					}
				}
			}
		}

		register_deactivation_hook( __FILE__, 'wpssle_remove_custom_capability_from_all_roles' );



		/**

		 * Add capability.
		 */
		function wpssle_add_custom_capability_to_specific_roles() {

			$specific_roles = array( 'administrator' );

			foreach ( $specific_roles as $role_name ) {

				$role = get_role( $role_name );

				if ( $role ) {

					if ( ! $role->has_cap( 'edit_wpsyncsheets_elementor_lite_main_settings' ) ) {

						$role->add_cap( 'edit_wpsyncsheets_elementor_lite_main_settings' );

					}

					if ( ! $role->has_cap( 'edit_wpsyncsheets_elementor_lite_form_settings' ) ) {

						$role->add_cap( 'edit_wpsyncsheets_elementor_lite_form_settings' );

					}
				}
			}
		}



		register_activation_hook( __FILE__, 'wpssle_add_custom_capability_to_specific_roles' );

		add_action( 'init', 'wpssle_add_custom_capability_to_specific_roles' );



		// Add methods if Elementor Pro is active.

		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wpssle_add_action_links' );



		/**

		 * Add setings link at plugin page.
		 *
		 * @param array $wpssle_links Add settings link.
		 */
		function wpssle_add_action_links( $wpssle_links ) {

			$wpssle_mylinks = array(

				'<a href="' . esc_url( admin_url( 'admin.php?page=wpsyncsheets-elementor' ) ) . '">' . esc_html__( 'Settings', 'wpsse' ) . '</a>',
				'<a style="font-weight:bold; color: #0040ff;" target="_blank" href="' . WPSSLE_BUY_PRO_VERSION_URL . '">Upgrade to Pro</a>',

			);

			return array_merge( $wpssle_links, $wpssle_mylinks );
		}



		// Define the class and the function.

		require_once __DIR__ . '/src/class-wpsyncsheetselementor.php';

		wpssle();



	} else {



		add_action( 'admin_notices', 'wppse_admin_notice' );

		if ( ! function_exists( 'wppse_admin_notice' ) ) {

			/**

			 * Add notice if Elementor pro plugin not install or active.
			 */
			function wppse_admin_notice() {

				?> 

				<div class="notice error">

					<div>

						<p><?php echo esc_html__( 'WPSyncSheets Lite For Elementor plugin requires', 'wpsse' ); ?> <a href="<?php echo esc_url( 'https://elementor.com/pro/' ); ?>" target = "_blank"><?php echo esc_html__( 'Elementor Pro', 'wpsse' ); ?></a> <?php echo esc_html__( 'plugin to be active!', 'wpsse' ); ?></p>

					</div>

				</div>

				<?php
			}

		}
	}
}
