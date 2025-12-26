<?php
/**
 * Admin settings page handler.
 *
 * Registers the Settings submenu, sanitizes options, and renders the settings page.
 *
 * @package searchfiltersort
 */

namespace SearchFilterSort;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the admin settings page for the SearchFilterSort plugin.
 *
 * @package searchfiltersort
 */
class SFLTS_Settings {

	/**
	 * Singleton instance.
	 *
	 * @var SFLTS_Settings|null
	 */
	private static $instance = null;

	/**
	 * Returns the singleton instance.
	 *
	 * @return SFLTS_Settings
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * Registers admin hooks.
	 */
	private function __construct() {
		add_action( 'admin_menu', array( $this, 'sflts_menu' ) );
		add_action( 'admin_init', array( $this, 'sflts_register' ) );
	}

	/**
	 * Registers submenu page and removes unwanted default menu items.
	 *
	 * @return void
	 */
	public function sflts_menu() {
		// Ensure CPT slug constant exists before using it.
		$cpt_parent = defined( 'SFLTS_CPT_SLUG' ) ? SFLTS_CPT_SLUG : 'sflts-filters';

		add_submenu_page(
			'edit.php?post_type=' . $cpt_parent,
			__( 'Settings', 'searchfiltersort' ),
			__( 'Settings', 'searchfiltersort' ),
			'manage_options',
			'sflts-settings',
			array( $this, 'sflts_render' )
		);

		// Remove unwanted auto-generated CPT submenu items safely.
		remove_submenu_page( 'edit.php?post_type=' . $cpt_parent, 'edit.php?post_type=' . $cpt_parent );
		remove_submenu_page( 'edit.php?post_type=' . $cpt_parent, 'post-new.php?post_type=' . $cpt_parent );
	}

	/**
	 * Registers settings and validates access URL.
	 *
	 * @return void
	 */
	public function sflts_register() {

		register_setting( 'sflts_settings_group', 'sflts_settings', array( $this, 'sflts_sanitize' ) );

		// Validate GET params and redirect to canonical settings URL when needed.
		if ( isset( $_GET['page'] ) && 'sflts-settings' === sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) { // phpcs:ignore

			$post_type_get = isset( $_GET['post_type'] ) ? sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) : ''; // phpcs:ignore

			$expected_post_type = defined( 'SFLTS_CPT_SLUG' ) ? SFLTS_CPT_SLUG : 'sflts-filters';

			if ( $expected_post_type !== $post_type_get ) {

				$correct_url = admin_url(
					'edit.php?post_type=' . $expected_post_type . '&page=sflts-settings'
				);

				wp_safe_redirect( $correct_url );
				exit;
			}
		}
	}

	/**
	 * Sanitizes settings before saving.
	 *
	 * @param array $input Unsanitized form values.
	 * @return array Sanitized values.
	 */
	public function sflts_sanitize( $input ) {

		$nonce = isset( $_POST['sflts_settings_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['sflts_settings_nonce'] ) ) : '';

		// Verify nonce safely.
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'sflts_save_settings' ) ) {
			// return defaults if nonce invalid to avoid saving bad input.
			return SFLTS_Helpers::sflts_defaults();
		}

		// Make sure $input is an array before using it.
		$input = is_array( $input ) ? $input : array();

		$defaults = SFLTS_Helpers::sflts_defaults();
		$clean    = array();

		$clean['post_type']              = sanitize_text_field( $input['post_type'] ?? $defaults['post_type'] );
		$clean['post_status']            = sanitize_text_field( $input['post_status'] ?? $defaults['post_status'] );
		$clean['taxonomy']               = sanitize_text_field( $input['taxonomy'] ?? $defaults['taxonomy'] );
		$clean['products_per_page']      = max( 1, absint( $input['products_per_page'] ?? $defaults['products_per_page'] ) );
		$clean['enable_category_filter'] = ! empty( $input['enable_category_filter'] );
		$clean['enable_price_filter']    = ! empty( $input['enable_price_filter'] );
		$clean['enable_sort']            = ! empty( $input['enable_sort'] );
		$clean['columns']                = min( 4, max( 2, absint( $input['columns'] ?? $defaults['columns'] ) ) );
		$clean['padding']                = absint( $input['padding'] ?? $defaults['padding'] );
		$clean['margin']                 = absint( $input['margin'] ?? $defaults['margin'] );
		$clean['bg_color']               = sanitize_hex_color( $input['bg_color'] ?? $defaults['bg_color'] );
		$clean['text_color']             = sanitize_hex_color( $input['text_color'] ?? $defaults['text_color'] );
		$clean['title_color']            = sanitize_hex_color( $input['title_color'] ?? $defaults['title_color'] );
		$clean['image_hover']            = ! empty( $input['image_hover'] );

		// Validate pagination_type against allowed keys (sanitize first).
		$pagination_type_raw      = sanitize_text_field( $input['pagination_type'] ?? '' );
		$allowed_pagination       = array_keys( SFLTS_Helpers::sflts_pagination_types() );
		$clean['pagination_type'] = in_array( $pagination_type_raw, $allowed_pagination, true ) ? $pagination_type_raw : $defaults['pagination_type'];

		// Validate filter_position against allowed keys (sanitize first).
		$filter_position_raw      = sanitize_text_field( $input['filter_position'] ?? '' );
		$allowed_positions        = array_keys( SFLTS_Helpers::sflts_filter_positions() );
		$clean['filter_position'] = in_array( $filter_position_raw, $allowed_positions, true ) ? $filter_position_raw : $defaults['filter_position'];

		$clean['button_type']      = sanitize_text_field( $input['button_type'] ?? $defaults['button_type'] );
		$clean['button_label']     = sanitize_text_field( $input['button_label'] ?? $defaults['button_label'] );
		$clean['button_alignment'] = sanitize_text_field( $input['button_alignment'] ?? $defaults['button_alignment'] );

		return $clean;
	}


	/**
	 * Renders the settings page.
	 *
	 * @return void
	 */
	public function sflts_render() {

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$options = SFLTS_Helpers::sflts_settings();
		include SFLTS_PLUGIN_DIR . 'templates/admin/settings-page.php';
	}
}
