<?php
/**
 * Registers the admin-side Custom Post Type container
 * used for storing SearchFilterSort configurations.
 *
 * @package searchfiltersort
 */

namespace SearchFilterSort;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define the CPT slug safely.
if ( ! defined( 'SFLTS_CPT_SLUG' ) ) {
	define( 'SFLTS_CPT_SLUG', 'sflts-filters' );
}

/**
 * Utility class for register the SearchFilterSort admin CPT.
 */
final class SFLTS_CPT {

	/**
	 * Register the SearchFilterSort admin CPT.
	 *
	 * This CPT is not public and is used only inside the admin panel
	 * to store filter, sorting, and display configuration entries.
	 *
	 * @return void
	 */
	public function sflts_register_cpt() {

		$labels = array(
			'name'          => esc_html__( 'SearchFilterSort', 'searchfiltersort' ),
			'singular_name' => esc_html__( 'SearchFilterSort', 'searchfiltersort' ),
			'menu_name'     => esc_html__( 'SearchFilterSort', 'searchfiltersort' ),
		);

		$args = array(
			'labels'          => $labels,
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => true,
			'supports'        => array( 'title' ),
			'menu_icon'       => 'dashicons-filter',
			'capability_type' => 'post',
		);

		register_post_type( SFLTS_CPT_SLUG, $args );
	}
}
