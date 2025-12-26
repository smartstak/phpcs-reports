<?php
/**
 * Main WPSyncSheetsGravityForms namespace.
 *
 * @package wpsyncsheets-gravity-forms
 */

/**
 * WPSSG Dependency Checker
 */
class WPSSLGF_Dependencies {
	/**
	 * Active plugins
	 *
	 * @var $active_plugins
	 */
	private static $active_plugins;
	/**
	 * Initialization
	 */
	public static function init() {
		if ( is_multisite() ) {
			if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
				require_once ABSPATH . '/wp-admin/includes/plugin.php';
			}
			// Check Gravity Forms active at the network site.
			if ( is_plugin_active_for_network( 'gravityforms/gravityforms.php' ) ) {
				self::$active_plugins = (array) get_site_option( 'active_sitewide_plugins', array() );
			} else { // Check Gravity Forms active at the network individual site.
				self::$active_plugins = (array) get_option( 'active_plugins', array() );
			}
		} else {
			self::$active_plugins = (array) get_option( 'active_plugins', array() );
		}
	}
	/**
	 * Check gravity-forms exist
	 *
	 * @return Boolean
	 */
	public static function wpsslgf_gravityform_active_check() {
		if ( ! self::$active_plugins ) {
			self::init();
		}
		if ( in_array( 'gravityforms/gravityforms.php', self::$active_plugins, true ) || array_key_exists( 'gravityforms/gravityforms.php', self::$active_plugins ) ) {
			return true;
		}
		return false;
	}
	/**
	 * Check if gravity-forms active
	 *
	 * @return Boolean
	 */
	public static function wpsslgf_is_gravityform_plugin_active() {
		return self::wpsslgf_gravityform_active_check();
	}

	/**
	 * Check the install and activation status of a plugin in WordPress.
	 *
	 * @param string $plugin_slug Folder name of the plugin.
	 * @param string $plugin_file Full plugin path relative to plugins directory, e.g., 'folder/plugin-file.php'.
	 * @return array Returns an associative array with keys:
	 *               - 'installed' => bool
	 *               - 'active' => bool
	 *               - 'network_active' => bool
	**/
	public static function check_plugin_install_and_activation_status($plugin_slug, $plugin_file) {
		// Ensure required plugin functions are available
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		// Full plugin path: e.g., wp-content/plugins/plugin-folder/plugin-file.php
		$plugin_path = WP_PLUGIN_DIR . '/' . $plugin_file;
		
		// Check if plugin file exists (i.e., plugin is installed)
		$is_installed = file_exists($plugin_path);

		// Check if plugin is active on the current site
		$is_active = is_plugin_active($plugin_file);

		// Check if plugin is network activated (in multisite)
		$is_network_active = is_multisite() && is_plugin_active_for_network($plugin_file);

		return [
			'installed'        => $is_installed,
			'active'           => $is_active,
			'network_active'   => $is_network_active,
		];
	}
}
