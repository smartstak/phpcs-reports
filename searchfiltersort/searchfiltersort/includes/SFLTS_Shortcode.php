<?php
/**
 * Shortcode renderer for the SearchFilterSort plugin.
 *
 * Handles the [SearchFilterSort] shortcode and outputs the frontend container.
 *
 * @package searchfiltersort
 */

namespace SearchFilterSort;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles SearchFilterSort shortcode.
 *
 * Renders the frontend wrapper and passes shortcode attributes to the template.
 */
class SFLTS_Shortcode {

	/**
	 * Singleton instance.
	 *
	 * @var SFLTS_Shortcode|null
	 */
	private static $instance = null;

	/**
	 * Returns a singleton instance of this class.
	 *
	 * @return SFLTS_Shortcode
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
	 * Registers the shortcode.
	 */
	private function __construct() {
		add_shortcode( 'SearchFilterSort', array( $this, 'sflts_render_shortcode' ) );
	}

	/**
	 * Renders the shortcode output.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output of the shortcode.
	 */
	public function sflts_render_shortcode( $atts ) {

		$settings = SFLTS_Helpers::sflts_settings();

		$atts = shortcode_atts(
			array(
				'post_type'       => $settings['post_type'] ?? '',
				'taxonomy'        => $settings['taxonomy'] ?? '',
				'filter_position' => $settings['filter_position'] ?? 'top',
				'columns'         => $settings['columns'] ?? 3,
			),
			$atts,
			'SearchFilterSort'
		);

		$args = array(
			'post_type'       => sanitize_text_field( $atts['post_type'] ),
			'taxonomy'        => sanitize_text_field( $atts['taxonomy'] ),
			'filter_position' => sanitize_text_field( $atts['filter_position'] ),
			'columns'         => max( 2, min( 4, absint( $atts['columns'] ) ) ),
		);

		// Include frontend template safely.
		ob_start();
		$shortcode_args = $args; // Pass sanitized args to template.
		include SFLTS_PLUGIN_DIR . 'templates/frontend/shortcode.php';
		return ob_get_clean();
	}
}
