<?php
/**
 * Utilities class for WPSyncSheets Lite For Contact Form 7
 *
 * @since       1.0
 * @package contactsheets-lite
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Utilities class - singleton class
 *
 * @since 1.0
 */
class WPSSLC_Utility {

	/**
	 *  Set things up.
	 *
	 *  @since 1.0
	 */
	private function __construct() {
		// Do Nothing.
	}

	/**
	 * Instance of WPSSLC_Utility class.
	 *
	 * @var $instance Instance variable of class.
	 */
	protected static $instance = null;
	/**
	 * Get an instance of this class.
	 *
	 * @return instance
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Display error or success message in the admin section
	 *
	 * @param array $data containing type and message.
	 * @return string with html containing the error message
	 *
	 * @since 1.0 initial version
	 */
	public function admin_notice( $data = array() ) {
		// extract message and type from the $data array.
		$message      = isset( $data['message'] ) ? $data['message'] : '';
		$message_type = isset( $data['type'] ) ? $data['type'] : '';
		switch ( $message_type ) {
			case 'error':
				$admin_notice = '<div id="message" class="error notice is-dismissible">';
				break;
			case 'update':
				$admin_notice = '<div id="message" class="updated notice is-dismissible">';
				break;
			case 'update-nag':
				$admin_notice = '<div id="message" class="update-nag">';
				break;
			default:
				$message      = __( 'There\'s something wrong with your code...', 'wpssc' );
				$admin_notice = "<div id=\"message\" class=\"error\">\n";
				break;
		}

		$admin_notice .= '    <p>' . $message . "</p>\n";
		$admin_notice .= "</div>\n";
		return $admin_notice;
	}

	/**
	 * Utility function to get the current user's role
	 *
	 * @since 1.0
	 */
	public function get_current_user_role() {
		global $wp_roles;
		foreach ( $wp_roles->role_names as $role => $name ) :
			if ( current_user_can( $role ) ) {
				return $role;
			}
		endforeach;
	}
}
