<?php
/**
 * Handling all the Notification calls in WPSyncSheets.
 *
 * @package wpsyncsheets-elementor
 * @since 1.5.9.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WPSSLE_Notification' ) ) :

	/**
	 * Handle Notification for Inline.
	 */
	class WPSSLE_Notification {

		/**
		 * Class constructor
		 */
		public function __construct() {

			$this->notification_hooks();
		}

		/**
		 * Hook into actions and filters
		 *
		 * @since  1.5.9.1
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
			$this->wpssle_review_notice();
		}

		/**
		 * Ask users to review our plugin on wordpress.org
		 *
		 * @since 1.5.9.1
		 * @version 1.5.9.1
		 */
		public function wpssle_review_notice() {

			$this->wpssle_review_dismissal();
			$this->wpssle_review_pending();

			$activation_time  = get_site_option( 'wpssle_active_time' );
			$review_dismissal = get_site_option( 'wpssle_review_dismiss' );

			// Update the $review_dismissal value in 1.5.9.1
			if ( 'yes_v2_1_0' === $review_dismissal ) :
				return;
			endif;

			if ( ! $activation_time ) :

				$activation_time = time();
				add_site_option( 'wpssle_active_time', $activation_time );
			endif;

			// 1296000 = 15 Days in seconds.
			if ( ( time() - $activation_time > 1296000 ) && current_user_can( 'manage_options' ) ) :

				wp_enqueue_style(
					'wpssle_review_style',
					WPSSLE_Assets_URL::assets_url(
						'css',
						'wpssle-admin-review',
						'assets/css/',
						'assets/css/build/'
					),
					array(),
					WPSSLE_VERSION
				);

				add_action( 'admin_notices', array( $this, 'wpssle_review_notice_message' ) );
			endif;
		}

		/**
		 * Check and Dismiss review message.
		 *
		 * @since 1.5.9.1
		 * @version 1.5.9.1
		 */
		private function wpssle_review_dismissal() {

			if ( ! is_admin() ||
				! current_user_can( 'manage_options' ) ||
				! isset( $_GET['_wpnonce'] ) ||
				! wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ), 'wpssle-review-nonce' ) ||
				! isset( $_GET['wpssle_review_dismiss'] ) ) :

				return;
			endif;

			// Update the $review_dismissal value in 1.5.9.1
			update_site_option( 'wpssle_review_dismiss', 'yes_v2_1_0' );
		}

		/**
		 * Set time to current so review notice will popup after 14 days
		 *
		 * @since 1.5.9.1
		 */
		private function wpssle_review_pending() {

			if ( ! is_admin() ||
				! current_user_can( 'manage_options' ) ||
				! isset( $_GET['_wpnonce'] ) ||
				! wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ), 'wpssle-review-nonce' ) ||
				! isset( $_GET['wpssle_review_later'] ) ) :

				return;
			endif;

			// Reset Time to current time.
			update_site_option( 'wpssle_active_time', time() );
		}

		/**
		 * Review notice message
		 *
		 * @since 1.5.9.1
		 * @version 1.5.9.1
		 */
		public function wpssle_review_notice_message() {

			$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
			$dismiss_url = wp_nonce_url(
				add_query_arg( 'wpssle_review_dismiss', 'yes_v2_1_0', $request_uri ),
				'wpssle-review-nonce'
			);
			$later_url   = wp_nonce_url(
				add_query_arg( 'wpssle_review_later', 'yes', $request_uri ),
				'wpssle-review-nonce'
			);
			?>

			<div class="wpssle-review-notice">
				<div class="wpssle-review-thumbnail">
					<img src="<?php echo esc_url( plugins_url( '../assets/images/logo.svg', __FILE__ ) ); ?>" alt="Inline Logo">
				</div>
				<div class="wpssle-review-text">
					<h3><?php esc_html_e( 'Leave A Review?', 'wpsse' ); ?></h3>
					<p><?php esc_html_e( 'We hope you\'ve enjoyed using Inline WPSyncSheets Lite For Elementor! Would you consider leaving us a review on WordPress.org?', 'wpsse' ); ?></p>
					<ul class="wpssle-review-ul">
						<li><a href="https://wordpress.org/support/plugin/wpsyncsheets-elementor/reviews/?rate=5#rate-response" target="_blank"><span class="dashicons dashicons-external"></span><?php esc_html_e( 'Sure! I\'d love to!', 'wpsse' ); ?></a></li>
						<li><a href="<?php echo esc_url( $dismiss_url ); ?>"><span class="dashicons dashicons-smiley"></span><?php esc_html_e( 'I\'ve already left a review', 'wpsse' ); ?></a></li>
						<li><a href="<?php echo esc_url( $later_url ); ?>"><span class="dashicons dashicons-calendar-alt"></span><?php esc_html_e( 'Maybe Later', 'wpsse' ); ?></a></li>
						<li><a href="<?php echo esc_url( $dismiss_url ); ?>"><span class="dashicons dashicons-dismiss"></span><?php esc_html_e( 'Never show again', 'wpsse' ); ?></a></li>
					</ul>
				</div>
			</div>
			<?php
		}
	}
endif;
new WPSSLE_Notification();