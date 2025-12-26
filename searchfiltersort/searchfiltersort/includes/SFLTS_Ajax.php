<?php
/**
 * AJAX handlers for SearchFilterSort.
 *
 * @package searchfiltersort
 */

namespace SearchFilterSort;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use SearchFilterSort\SFLTS_Helpers;
use SearchFilterSort\SFLTS_Query;

/**
 * Class SFLTS_Ajax
 *
 * Handles AJAX requests for filtering results.
 */
class SFLTS_Ajax {

	/**
	 * Singleton instance.
	 *
	 * @var SFLTS_Ajax|null
	 */
	private static $instance = null;

	/**
	 * Accessor for the singleton instance.
	 *
	 * @return SFLTS_Ajax
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
	 * Registers AJAX endpoints.
	 */
	private function __construct() {
		add_action( 'wp_ajax_sflts_filter', array( $this, 'sflts_filter' ) );
		add_action( 'wp_ajax_nopriv_sflts_filter', array( $this, 'sflts_filter' ) );
	}

	/**
	 * AJAX callback to fetch filtered results.
	 *
	 * @return void
	 */
	public function sflts_filter() {

		// Validate nonce (sanitized first).
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'sflts_ajax_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'searchfiltersort' ) ) );
		}

		// Only sanitize expected fields. No mass-unslashing of $_POST.
		$post_type = isset( $_POST['post_type'] ) ? sanitize_text_field( wp_unslash( $_POST['post_type'] ) ) : 'post';
		$taxonomy  = isset( $_POST['taxonomy'] ) ? sanitize_text_field( wp_unslash( $_POST['taxonomy'] ) ) : 'category';
		$paged     = isset( $_POST['paged'] ) ? absint( wp_unslash( $_POST['paged'] ) ) : 1;

		$categories = array();
		if ( isset( $_POST['categories'] ) && ! empty( $_POST['categories'] ) ) {
			if ( is_array( $_POST['categories'] ) ) {
				$categories = array_map( 'absint', wp_unslash( $_POST['categories'] ) );
			} else {
				$categories = array( absint( wp_unslash( $_POST['categories'] ) ) );
			}
			foreach ( $categories as $cat_key => $cat_val ) {
				if ( $cat_val <= 0 ) {
					unset( $categories[ $cat_key ] );
				}
			}
		}

		$price_min = isset( $_POST['price_min'] ) ? floatval( wp_unslash( $_POST['price_min'] ) ) : '';
		$price_max = isset( $_POST['price_max'] ) ? floatval( wp_unslash( $_POST['price_max'] ) ) : '';

		$sort = isset( $_POST['sort'] ) ? sanitize_text_field( wp_unslash( $_POST['sort'] ) ) : 'date';

		// Per-page limit (uses saved settings, hard-capped at 50).
		$saved_ppp      = SFLTS_Helpers::sflts_settings( 'products_per_page' );
		$posted_ppp     = isset( $_POST['per_page'] ) ? absint( wp_unslash( $_POST['per_page'] ) ) : $saved_ppp;
		$posts_per_page = min( $posted_ppp, 50 );

		$args = array(
			'post_type'      => $post_type,
			'taxonomy'       => $taxonomy,
			'paged'          => $paged,
			'categories'     => $categories,
			'price_min'      => $price_min,
			'price_max'      => $price_max,
			'sort'           => $sort,
			'posts_per_page' => $posts_per_page,
		);

		// Build query based on helper class.
		$query = new \WP_Query( SFLTS_Query::sflts_build_args( $args ) );

		ob_start();
		include SFLTS_PLUGIN_DIR . 'templates/frontend/results.php';
		$html = ob_get_clean();

		wp_send_json_success(
			array(
				'html'         => $html,
				'total'        => (int) $query->found_posts,
				'max_pages'    => (int) $query->max_num_pages,
				'current_page' => (int) $paged,
			)
		);
	}
}
