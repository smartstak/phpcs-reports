<?php
	/**
	 * Main WPSyncSheetsGravityForms namespace.
	 *
	 * @since 1.0.0
	 * @package wpsyncsheets-gravity-forms
	 */

namespace WPSyncSheetsGravityForms {
	/**
	 * Main WPSyncSheetsGravityForms class.
	 *
	 * @since 1.0.0
	 * @package wpsyncsheets-gravity-forms
	 */
	final class WPSyncSheetsGravityForms {
		/**
		 * Store Instance of class.
		 *
		 * @since 1.0.0
		 *
		 * @var \WPSyncSheetsGravityForms\WPSyncSheetsGravityForms
		 */
		private static $instance;

		/**
		 * Plugin version for enqueueing, etc.
		 * The value is got from WPSSLGF_VERSION constant.
		 *
		 * @since 1.0.0
		 *
		 * @var string
		 */
		public $version = '';

		/**
		 * Main WPSyncSheetsGravityForms Instance.
		 *
		 * Only one instance of WPSyncSheetsGravityForms exists in memory at any one time.
		 * Also prevent the need to define globals all over the place.
		 *
		 * @since 1.0.0
		 *
		 * @return WPSyncSheetsGravityForms
		 */
		public static function instance() {

			if ( null === self::$instance || ! self::$instance instanceof self ) {

				self::$instance = new self();
				self::$instance->constants();
				self::$instance->includes();

				add_action( 'init', array( self::$instance, 'load_textdomain' ), 10 );
			}

			return self::$instance;
		}

		/**
		 * Setup plugin constants.
		 * All the path/URL related constants are defined in main plugin file.
		 *
		 * @since 1.0.0
		 */
		private function constants() {
			$this->version = WPSSLGF_VERSION;
		}

		/**
		 * Load the plugin language files.
		 *
		 * @since 1.0.0
		 */
		public function load_textdomain() {

			// If the user is logged in, unset the current text-domains before loading our text domain.
			// This feels hacky, but this way a user's set language in their profile will be used,
			// rather than the site-specific language.
			if ( is_user_logged_in() ) {
				unload_textdomain( 'wpssg' );
			}
			load_plugin_textdomain( 'wpssg', false, WPSSLGF_DIRECTORY . '/languages/' );
		}

		/**
		 * Include files.
		 *
		 * @since 1.0.0
		 */
		private function includes() {

			// Global Includes.
			require_once WPSSLGF_PATH . '/includes/class-wpsslgf-google-api.php';
			require_once WPSSLGF_PATH . '/includes/class-wpsslgf-feed-settings.php';
			require_once WPSSLGF_PATH . '/includes/class-wpsslgf-google-api-functions.php';
			require_once WPSSLGF_PATH . '/includes/class-wpsslgf-assets-url.php'; // Changed.
			require_once WPSSLGF_PATH . '/includes/class-wpsslgf-plugin-settings.php';
			require_once WPSSLGF_PATH . '/includes/class-wpsslgf-notifications.php';
			require_once WPSSLGF_PATH . '/feedback/users-feedback.php';
		}
	}
}

namespace {

	/**
	 * The function which returns the one WPSSLGF instance.
	 *
	 * @since 1.0.0
	 *
	 * @return WPSSLGF\wpsslgf
	 */
	function wpsslgf() {
		return WPSyncSheetsGravityForms\WPSyncSheetsGravityForms::instance();
	}
	class_alias( 'WPSyncSheetsGravityForms\WPSyncSheetsGravityForms', 'WPSyncSheetsGravityForms' );
}
