<?php
	/**
	 * Main ContactsheetsLite namespace.
	 *
	 * @since 1.0.0
	 * @package contactsheets-lite
	 */

namespace ContactsheetsLite {
	/**
	 * Main ContactsheetsLite class.
	 *
	 * @since 1.0.0
	 * @package contactsheets-lite
	 */
	final class ContactsheetsLite {
		/**
		 * One is the loneliest number that you'll ever do.
		 *
		 * @since 1.0.0
		 *
		 * @var \ContactsheetsLite\ContactsheetsLite
		 */
		private static $instance;
		/**
		 * Plugin version for enqueueing, etc.
		 *
		 * @since 1.0.0
		 *
		 * @var string
		 */
		public $version = '';
		/**
		 * Main ContactsheetsLite Instance.
		 *
		 * Only one instance of ContactsheetsLite exists in memory at any one time.
		 * Also prevent the need to define globals all over the place.
		 *
		 * @since 1.0.0
		 *
		 * @return ContactsheetsLite
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
			$this->version = WPSSLC_VERSION;
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
				unload_textdomain( 'wpssc' );
			}
			load_plugin_textdomain( 'wpssc', false, WPSSLC_DIRECTORY . '/languages/' );
		}
		/**
		 * Include files.
		 *
		 * @since 1.0.0
		 */
		private function includes() {
			// Global Includes.
			require_once WPSSLC_PATH . '/includes/class-wpsslc-google-api.php';
			require_once WPSSLC_PATH . '/includes/class-wpsslc-utility.php';
			require_once WPSSLC_PATH . '/includes/class-wpsslc-google-api-functions.php';
			require_once WPSSLC_PATH . '/includes/class-wpsslc-assets-url.php'; // Changed.
			require_once WPSSLC_PATH . '/includes/class-wpsslc-service.php';
			require_once WPSSLC_PATH . '/includes/class-wpsslc-plugin-settings.php';
            require_once WPSSLC_PATH . '/includes/class-wpsslc-notifications.php';
			require_once WPSSLC_PATH . '/feedback/users-feedback.php';
		}
	}
}

namespace {
	/**
	 * The function which returns the one WPSSLC instance.
	 *
	 * @since 1.0.0
	 *
	 * @return WPSSLC\wpsslc
	 */
	function wpsslc() {
		return ContactsheetsLite\ContactsheetsLite::instance();
	}
	class_alias( 'ContactsheetsLite\ContactsheetsLite', 'ContactsheetsLite' );
}
