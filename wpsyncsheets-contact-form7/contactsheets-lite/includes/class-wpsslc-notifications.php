<?php
/**
 * Handling all the Notification calls in WPSyncSheets.
 *
 * @package contactsheets-lite
 * @since 1.6.9.3
 */

if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}

if ( ! class_exists( 'WPSSLC_Notification' ) ) :

	/**
	 * Handle Notification for Inline.
	 */
	class WPSSLC_Notification {

		/**
		 * Class constructor
		 */
		public function __construct() {

			$this->notification_hooks();
		}

		/**
		 * Hook into actions and filters
		 *
		 * @since  1.6.9.3
		 */
		private function notification_hooks() {
			add_action( 'admin_init', array( $this, 'wpsslc_review_notice' ) );
		}

		/**
		 * Ask users to review our plugin on wordpress.org
		 *
		 * @since 1.6.9.3
		 * @version 2.1.0
		 */
		public function wpsslc_review_notice() {

			$this->wpsslc_review_dismissal();
			$this->wpsslc_review_pending();

			$activation_time  = get_site_option( 'wpsslc_active_time' );
			$review_dismissal = get_site_option( 'wpsslc_review_dismiss' );

			// Update the $review_dismissal value in 2.1.0
			if ( 'yes_v2_1_0' === $review_dismissal ) :
				return;
			endif;

			if ( ! $activation_time ) :

				$activation_time = time();
				add_site_option( 'wpsslc_active_time', $activation_time );
			endif;

			// 1296000 = 15 Days in seconds.
			if ( ( time() - $activation_time > 1296000 ) && current_user_can( 'manage_options' ) ) :

				wp_enqueue_style(
					'wpsslc_review_style',
					WPSSLC_Assets_URL::assets_url(
						'css',
						'wpsslc-admin-review',
						'assets/css/',
						'assets/css/build/'
					),
					array(),
					WPSSLC_VERSION
				);
				add_action( 'admin_notices', array( $this, 'wpsslc_review_notice_message' ) );
			endif;

		}

		/**
		 * Check and Dismiss review message.
		 *
		 * @since 1.6.9.3
		 * @version 2.1.0
		 */
		private function wpsslc_review_dismissal() {

			if ( ! is_admin() ||
				! current_user_can( 'manage_options' ) ||
				! isset( $_GET['_wpnonce'] ) ||
				! wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ), 'wpsslc-review-nonce' ) ||
				! isset( $_GET['wpsslc_review_dismiss'] ) ) :

				return;
			endif;

			// Update the $review_dismissal value in 2.1.0
			update_site_option( 'wpsslc_review_dismiss', 'yes_v2_1_0' );
		}

		/**
		 * Set time to current so review notice will popup after 14 days
		 *
		 * @since 1.6.9.3
		 */
		private function wpsslc_review_pending() {

			if ( ! is_admin() ||
				! current_user_can( 'manage_options' ) ||
				! isset( $_GET['_wpnonce'] ) ||
				! wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ), 'wpsslc-review-nonce' ) ||
				! isset( $_GET['wpsslc_review_later'] ) ) :

				return;
			endif;

			// Reset Time to current time.
			update_site_option( 'wpsslc_active_time', time() );
		}

		/**
		 * Review notice message
		 *
		 * @since 1.6.9.3
		 * @version 3.1.3
		 */
		public function wpsslc_review_notice_message() {

			$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
			$scheme      = ( wp_parse_url( $request_uri, PHP_URL_QUERY ) ) ? '&' : '?';
			// Update the wpsslc_review_dismiss value in 2.1.0
			$url         = esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) . $scheme . 'wpsslc_review_dismiss=yes_v2_1_0';
			$dismiss_url = wp_nonce_url( $url, 'wpsslc-review-nonce' );

			$_later_link = esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) . $scheme . 'wpsslc_review_later=yes';
			$later_url   = wp_nonce_url( $_later_link, 'wpsslc-review-nonce' ); ?>

			<div class="wpsslc-review-notice">
				<div class="wpsslc-review-thumbnail">
					<img src="<?php echo esc_url( plugins_url( '../assets/images/logo.svg', __FILE__ ) ); ?>" alt="Inline Logo">
				</div>
				<div class="wpsslc-review-text">
					<h3><?php esc_html_e( 'Leave A Review?', 'wpssc' ); ?></h3>
					<p><?php esc_html_e( 'We hope you\'ve enjoyed using Inline WPSyncSheets Lite For Contact Form 7! Would you consider leaving us a review on WordPress.org?', 'wpssc' ); ?></p>
					<ul class="wpsslc-review-ul">
						<li><a href="https://wordpress.org/support/plugin/contactsheets-lite/reviews/?rate=5#rate-response" target="_blank"><span class="dashicons dashicons-external"></span><?php esc_html_e( 'Sure! I\'d love to!', 'wpssc' ); ?></a></li>
						<li><a href="<?php echo esc_url( $dismiss_url ); ?>"><span class="dashicons dashicons-smiley"></span><?php esc_html_e( 'I\'ve already left a review', 'wpssc' ); ?></a></li>
						<li><a href="<?php echo esc_url( $later_url ); ?>"><span class="dashicons dashicons-calendar-alt"></span><?php esc_html_e( 'Maybe Later', 'wpssc' ); ?></a></li>
						<li><a href="<?php echo esc_url( $dismiss_url ); ?>"><span class="dashicons dashicons-dismiss"></span><?php esc_html_e( 'Never show again', 'wpssc' ); ?></a></li>
					</ul>
				</div>
			</div>
			<?php
		}
	}
endif;
new WPSSLC_Notification();