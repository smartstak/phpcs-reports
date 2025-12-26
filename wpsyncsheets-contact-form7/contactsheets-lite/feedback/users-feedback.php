<?php

namespace WPSS\feedback;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class WPSSLC_UsersFeedback {

	private $plugin_url         = WPSSLC_URL;
	private $plugin_version     = WPSSLC_VERSION;
	private $plugin_name        = 'WPSyncSheets Lite For Contact Form 7';
	private $plugin_slug        = 'wpssc';
	private $plugin_download_id = WPSSLC_PLUGIN_ITEM_ID;
	/*
	|-----------------------------------------------------------------|
	|   Use this constructor to fire all actions and filters          |
	|-----------------------------------------------------------------|
	*/
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_feedback_scripts' ) );

		add_action( 'wp_ajax_' . $this->plugin_slug . '_submit_deactivation_response', array( $this, 'submit_deactivation_response' ) );
		add_action( 'admin_init', array( $this, 'onInit' ) );
	}
	public function onInit() {
		add_action( 'admin_head', array( $this, 'show_deactivate_feedback_popup' ) );
	}
	/*
	|-----------------------------------------------------------------|
	|   Enqueue all scripts and styles to required page only          |
	|-----------------------------------------------------------------|
	*/
	function enqueue_feedback_scripts() {
		$screen = get_current_screen();
		if ( isset( $screen ) && $screen->id == 'plugins' ) {
			wp_enqueue_script(
				$this->plugin_slug . '-feedback-script',
				\WPSSLC_Assets_URL::assets_url(
					'js',
					'admin-feedback',
					'assets/js/',
					'assets/js/build/'
				),
				array( 'jquery' ),
				WPSSLC_VERSION
			);
			wp_enqueue_style(
				$this->plugin_slug . '-feedback-style',
				\WPSSLC_Assets_URL::assets_url(
					'css',
					'admin-feedback-style',
					'assets/css/',
					'assets/css/build/'
				),
				null,
				WPSSLC_VERSION
			);
		}
	}

	/*
	|-----------------------------------------------------------------|
	|   HTML for creating feedback popup form                         |
	|-----------------------------------------------------------------|
	*/
	public function show_deactivate_feedback_popup() {

		$screen = get_current_screen();
		if ( ! isset( $screen ) || $screen->id != 'plugins' ) {
			return;
		}
		$deactivate_reasons = self::get_deactivate_reasons_arr();		
		?>
		<div id="<?php echo $this->plugin_slug; ?>-deactivate-feedback-dialog-wrapper" class="hide-feedback-popup">
		  	<div class="<?php echo $this->plugin_slug; ?>-deactivation-container">
				<div class="<?php echo $this->plugin_slug; ?>-deactivation-response">
					<div id="<?php echo $this->plugin_slug; ?>-deactivate-feedback-dialog-header">
						<div class="<?php echo $this->plugin_slug; ?>-form-title--icon-wrapper">
							<img class="<?php echo $this->plugin_slug; ?>-icon" src="<?php echo plugins_url( '../feedback/images/deactive-header-logo.png', __FILE__ ); ?>" alt="Header Logo">
							<h2 class="<?php echo $this->plugin_slug; ?>-title"><?php echo esc_html( __( 'Quick Feedback', 'wpssc' ) ); ?></h2>
						</div>
						<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="<?php echo $this->plugin_slug; ?>-deactivate-feedback-dialog-close">
							<path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"></path>
						</svg>
					</div>
					<div id="<?php echo $this->plugin_slug; ?>-loader-wrapper">
						<div class="<?php echo $this->plugin_slug; ?>-loader-container">
								<img class="<?php echo $this->plugin_slug; ?>-preloader" src="<?php echo WPSSLC_URL; ?>feedback/images/preloader.gif">
						</div>
					</div>
					<div id="<?php echo $this->plugin_slug; ?>-form-wrapper" class="<?php echo $this->plugin_slug; ?>-form-wrapper-cls">
						<form id="<?php echo $this->plugin_slug; ?>-deactivate-feedback-dialog-form" method="post">
							<?php
							wp_nonce_field( 'wpss_deactivate_feedback_nonce', "$this->plugin_slug-wpnonce" );
							?>
							<input type="hidden" name="action" value="wpss_deactivate_feedback" />
							<div id="<?php echo $this->plugin_slug; ?>-deactivate-feedback-dialog-form-caption"><?php echo esc_html( __( 'If you have a moment, please share why you are deactivating this plugin.', 'wpssc' ) ); ?></div>
							<div id="<?php echo $this->plugin_slug; ?>-deactivate-feedback-dialog-form-body">
								<?php
								$reason_key_arr = array( 'didnt_work_as_expected', 'found_a_better_plugin', 'couldnt_get_the_plugin_to_work' );
								foreach ( $deactivate_reasons as $reason_key => $reason ) :
									?>
									<div class="<?php echo $this->plugin_slug; ?>-deactivate-feedback-dialog-input-wrapper">
										<input id="<?php echo $this->plugin_slug; ?>-deactivate-feedback-<?php echo esc_attr( $reason_key ); ?>" class="<?php echo $this->plugin_slug; ?>-deactivate-feedback-dialog-input" type="radio" name="reason_key" value="<?php echo esc_attr( $reason_key ); ?>" />
										<label for="<?php echo $this->plugin_slug; ?>-deactivate-feedback-<?php echo esc_attr( $reason_key ); ?>" class="<?php echo $this->plugin_slug; ?>-deactivate-feedback-dialog-label"><?php echo esc_html( $reason['title'] ); ?></label>
										<?php if ( ! empty( $reason['input_placeholder'] ) ) : ?>
											<textarea class="<?php echo $this->plugin_slug; ?>-feedback-text" type="textarea" name="reason_<?php echo esc_attr( $reason_key ); ?>" placeholder="<?php echo esc_attr( $reason['input_placeholder'] ); ?>"></textarea>
											<?php
											if ( in_array( $reason_key, $reason_key_arr, true ) ) {
												$twae_plugin_url = 'https://wordpress.org/plugins/contactsheets-lite/';
											}
										endif;
										?>
										<?php if ( ! empty( $reason['alert'] ) ) : ?>
											<div class="<?php echo $this->plugin_slug; ?>-feedback-text"><?php echo esc_html( $reason['alert'] ); ?></div>
										<?php endif; ?>
									</div>
								<?php endforeach; ?>
								<input class="<?php echo $this->plugin_slug; ?>-GDPR-data-notice" id="<?php echo $this->plugin_slug; ?>-GDPR-data-notice" type="checkbox"><label for="<?php echo $this->plugin_slug; ?>-GDPR-data-notice"><?php echo esc_html( __( 'I consent to WPSyncSheets plugins collecting all the information I submit through this form and using it to respond to my inquiry.', 'wpssc' ) ); ?></label>
								<div class="<?php echo $this->plugin_slug; ?>-uninstall-feedback-privacy-policy">
										We value your feedback and do not collect personal data. <a href="https://www.wpsyncsheets.com/privacy-policy/" target="_blank">Privacy Policy</a>
								</div>
								<div class="<?php echo $this->plugin_slug; ?>-required-field-messages">
								</div>
							</div>
							<div class="<?php echo $this->plugin_slug; ?>-plugin-popup-button-wrapper">
								<a class="<?php echo $this->plugin_slug; ?>-button " id="<?php echo $this->plugin_slug; ?>-plugin-submitNdeactivate"><?php echo esc_html__( 'Submit and Deactivate', 'wpssc' ); ?></a>
								<a class="<?php echo $this->plugin_slug; ?>-button" id="<?php echo $this->plugin_slug; ?>-plugin-skipNdeactivate"><?php echo esc_html__( 'Skip and Deactivate', 'wpssc' ); ?></a>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
		<?php
	}


	function submit_deactivation_response() {
		

		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'wpss_deactivate_feedback_nonce' ) ) {
			wp_send_json_error();
		} else {
			$reason             = sanitize_text_field( $_POST['reason'] ); // Sanitize reason input
			$message            = isset( $_POST['message'] ) ? sanitize_textarea_field( $_POST['message'] ) : ''; // Sanitize message input
			$deactivate_reasons = self::get_deactivate_reasons_arr();

			$deativation_reason = array_key_exists( $reason, $deactivate_reasons ) ? $reason : 'other';

			$sanitized_message = sanitize_text_field( $_POST['message'] ) == '' ? 'N/A' : sanitize_text_field( $_POST['message'] );
			$admin_email       = sanitize_email( get_option( 'admin_email' ) );
			$site_url          = esc_url( site_url() );
			$feedback_url      = esc_url( 'https://feedback.wpsyncsheets.com/wp-json/wpssplugins-feedback/v1/feedback' );
			$response          = wp_remote_post(
				$feedback_url,
				array(
					'timeout' => 30,
					'body'    => array(
						'plugin_version'    => WPSSLC_VERSION,
						'plugin_name'       => $this->plugin_name,
						'plugin_download_id'=>  $this->plugin_download_id,
						'plugin_type'       => 'lite',
						'plugin_slug'       => 'wpsslc',
						'reason'            => $deativation_reason,
						'review'            => $sanitized_message,
						'email'             => $admin_email,
						'domain'            => $site_url,
					),
				)
			);

			die( json_encode( array( 'response' => $response ) ) );
		}
	}
	public function get_deactivate_reasons_arr(){
		$deactivate_reasons = array(
			'didnt_work_as_expected'         => array(
				'title'             => esc_html( __( 'The plugin didn\'t work as expected', 'wpssc' ) ),
				'input_placeholder' => esc_html( __( 'What did you expect?', 'wpssc' ) ),
			),
			'found_a_better_plugin'          => array(
				'title'             => esc_html( __( 'I found a better plugin', 'wpssc' ) ),
				'input_placeholder' => esc_html( __( 'Please share which plugin', 'wpssc' ) ),
			),
			'couldnt_get_the_plugin_to_work' => array(
				'title'             => esc_html( __( 'The plugin is not working', 'wpssc' ) ),
				'input_placeholder' => esc_html( __( 'Please share your issue. So we can fix that for other users.', 'wpssc' ) ),
			),
			'temporary_deactivation'         => array(
				'title'             => esc_html( __( 'It\'s a temporary deactivation', 'wpssc' ) ),
				'input_placeholder' => '',
			),
			'other'                          => array(
				'title'             => esc_html( __( 'Other', 'wpssc' ) ),
				'input_placeholder' => esc_html( __( 'Please share the reason', 'wpssc' ) ),
			),
		);
		return $deactivate_reasons;
	}
}
new WPSSLC_UsersFeedback();