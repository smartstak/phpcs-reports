<?php
/**
 * Handling all the Notification calls in WPSyncSheets.
 *
 * @package wpsyncsheets-gravity-forms
 * @since 1.6.9.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}

if ( ! class_exists( 'WPSSLGF_Notification' ) ) :

	/**
	 * Handle Notification for Inline.
	 */
	class WPSSLGF_Notification {

		/**
		 * Class constructor
		 */
		public function __construct() {

			$this->notification_hooks();
		}

		/**
		 * Hook into actions and filters
		 *
		 * @since  1.6.9.2
		 */
		private function notification_hooks() {
			add_action( 'admin_init', array( $this, 'maybe_run_review_notice' ) );
		}

		public function maybe_run_review_notice() {
			global $pagenow;
			// Skip everything unless on the Plugins page.
			if ( 'plugins.php' !== $pagenow ) {
				return;
			}
			$this->wpsslgf_review_notice();
		}

		/**
		 * Ask users to review our plugin on wordpress.org
		 *
		 * @since 1.6.9.2
		 * @version 1.6.9.2
		 */
		public function wpsslgf_review_notice() {

			$this->wpsslgf_review_dismissal();
			$this->wpsslgf_review_pending();

			$activation_time  = get_site_option( 'wpsslgf_active_time' );
			$review_dismissal = get_site_option( 'wpsslgf_review_dismiss' );

			// Update the $review_dismissal value in 1.6.9.2
			if ( 'yes_v2_1_0' === $review_dismissal ) :
				return;
			endif;

			if ( ! $activation_time ) :

				$activation_time = time();
				add_site_option( 'wpsslgf_active_time', $activation_time );
			endif;

			// 1296000 = 15 Days in seconds.
			if ( ( time() - $activation_time > 1296000 ) && current_user_can( 'manage_options' ) ) :

				wp_enqueue_style(
					'wpsslgf_review_style',
					WPSSLGF_Assets_URL::assets_url(
						'css',
						'wpsslgf-admin-review',
						'assets/css/',
						'assets/css/build/'
					),
					array(),
					WPSSLGF_VERSION
				);
				add_action( 'admin_notices', array( $this, 'wpsslgf_review_notice_message' ) );
			endif;

		}

		/**
		 * Check and Dismiss review message.
		 *
		 * @since 1.6.9.2
		 * @version 1.6.9.2
		 */
		private function wpsslgf_review_dismissal() {

			if ( ! is_admin() ||
				! current_user_can( 'manage_options' ) ||
				! isset( $_GET['_wpnonce'] ) ||
				! wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ), 'wpsslgf-review-nonce' ) ||
				! isset( $_GET['wpsslgf_review_dismiss'] ) ) :

				return;
			endif;

			// Update the $review_dismissal value in 1.6.9.2
			update_site_option( 'wpsslgf_review_dismiss', 'yes_v2_1_0' );
		}

		/**
		 * Set time to current so review notice will popup after 14 days
		 *
		 * @since 1.6.9.2
		 */
		private function wpsslgf_review_pending() {

			if ( ! is_admin() ||
				! current_user_can( 'manage_options' ) ||
				! isset( $_GET['_wpnonce'] ) ||
				! wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ), 'wpsslgf-review-nonce' ) ||
				! isset( $_GET['wpsslgf_review_later'] ) ) :

				return;
			endif;

			// Reset Time to current time.
			update_site_option( 'wpsslgf_active_time', time() );
		}

		/**
		 * Review notice message
		 *
		 * @since 1.6.9.2
		 * @version 1.6.9.2
		 */
		public function wpsslgf_review_notice_message() {

			$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
			$dismiss_url = wp_nonce_url(
				add_query_arg( 'wpsslgf_review_dismiss', 'yes_v2_1_0', $request_uri ),
				'wpsslgf-review-nonce'
			);
			$later_url = wp_nonce_url(
				add_query_arg( 'wpsslgf_review_later', 'yes', $request_uri ),
				'wpsslgf-review-nonce'
			);
			?>

			<div class="wpsslgf-review-notice">
				<div class="wpsslgf-review-thumbnail">
					<img src="<?php echo esc_url( plugins_url( '../assets/images/logo.svg', __FILE__ ) ); ?>" alt="Inline Logo">
				</div>
				<div class="wpsslgf-review-text">
					<h3><?php esc_html_e( 'Leave A Review?', 'wpssg' ); ?></h3>
					<p><?php esc_html_e( 'We hope you\'ve enjoyed using Inline WPSyncSheets Lite For Gravity Forms! Would you consider leaving us a review on WordPress.org?', 'wpssg' ); ?></p>
					<ul class="wpsslgf-review-ul">
						<li><a href="https://wordpress.org/support/plugin/wpsyncsheets-gravity-forms/reviews/?rate=5#rate-response" target="_blank"><span class="dashicons dashicons-external"></span><?php esc_html_e( 'Sure! I\'d love to!', 'wpssg' ); ?></a></li>
						<li><a href="<?php echo esc_url( $dismiss_url ); ?>"><span class="dashicons dashicons-smiley"></span><?php esc_html_e( 'I\'ve already left a review', 'wpssg' ); ?></a></li>
						<li><a href="<?php echo esc_url( $later_url ); ?>"><span class="dashicons dashicons-calendar-alt"></span><?php esc_html_e( 'Maybe Later', 'wpssg' ); ?></a></li>
						<li><a href="<?php echo esc_url( $dismiss_url ); ?>"><span class="dashicons dashicons-dismiss"></span><?php esc_html_e( 'Never show again', 'wpssg' ); ?></a></li>
					</ul>
				</div>
			</div>
			<?php
		}
	}
endif;
new WPSSLGF_Notification();