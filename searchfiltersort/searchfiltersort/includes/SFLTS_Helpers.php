<?php
/**
 * Helper utilities for plugin defaults, settings, formatting, taxonomies,
 * and general helper methods used across the plugin.
 *
 * @package searchfiltersort
 */

namespace SearchFilterSort;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Helper utility class for common plugin functions.
 */
final class SFLTS_Helpers {

	/**
	 * Default plugin settings.
	 *
	 * @return array Default settings.
	 */
	public static function sflts_defaults() {
		return array(
			'post_type'              => 'post',
			'post_status'            => 'publish',
			'taxonomy'               => 'category',
			'products_per_page'      => 12,
			'enable_category_filter' => true,
			'enable_price_filter'    => true,
			'enable_sort'            => true,
			'columns'                => 3,
			'padding'                => 15,
			'margin'                 => 15,
			'bg_color'               => '#ffffff',
			'text_color'             => '#333333',
			'title_color'            => '#111111',
			'image_hover'            => true,
			'pagination_type'        => 'load_more',
			'filter_position'        => 'left',
			'button_type'            => 'view',
			'button_label'           => 'View',
			'button_alignment'       => 'Left',
		);
	}

	/**
	 * Retrieve plugin settings.
	 *
	 * @param string|null $key         Specific key to return.
	 * @param mixed|null  $default_val Default value if key not found.
	 *
	 * @return mixed Full settings array or a single value.
	 */
	public static function sflts_settings( $key = null, $default_val = null ) {
		$options = get_option( 'sflts_settings', self::sflts_defaults() );

		if ( null === $key ) {
			return $options;
		}

		return $options[ $key ] ?? $default_val;
	}

	/**
	 * Get all public post types.
	 *
	 * @return array Key/value array of post type names => labels.
	 */
	public static function sflts_post_types() {
		$types   = get_post_types( array( 'public' => true ), 'objects' );
		$options = array();

		foreach ( $types as $type ) {
			$options[ $type->name ] = esc_html( $type->labels->singular_name );
		}

		return $options;
	}

	/**
	 * Get global public taxonomies mapped with their associated post types.
	 *
	 * @return array List of taxonomies with formatted labels.
	 */
	public static function sflts_global_taxonomies() {
		$taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );
		$options    = array();

		foreach ( $taxonomies as $tax ) {
			$post_types = get_taxonomy( $tax->name )->object_type;

			if ( ! empty( $post_types ) ) {
				foreach ( $post_types as $pt ) {
					$pt_obj = get_post_type_object( $pt );
					if ( $pt_obj ) {
						$options[ $tax->name ] = esc_html( $pt_obj->labels->singular_name . ': ' . $tax->labels->singular_name );
					}
				}
			} else {
				$options[ $tax->name ] = esc_html( $tax->labels->singular_name );
			}
		}

		return $options;
	}

	/**
	 * Get available pagination display types.
	 *
	 * @return array Pagination type labels.
	 */
	public static function sflts_pagination_types() {
		return array(
			'load_more'  => esc_html__( 'Load More Button', 'searchfiltersort' ),
			'pagination' => esc_html__( 'Numbered Pagination', 'searchfiltersort' ),
		);
	}

	/**
	 * Get allowed filter sidebar positions.
	 *
	 * @return array Available positions.
	 */
	public static function sflts_filter_positions() {
		$positions = array(
			'left'  => esc_html__( 'Left', 'searchfiltersort' ),
			'right' => esc_html__( 'Right', 'searchfiltersort' ),
		);

		return apply_filters( 'sflts_filter_positions', $positions );
	}

	/**
	 * Check if the shortcode is present on the current page.
	 *
	 * @return bool True if shortcode exists.
	 */
	public static function sflts_has_shortcode() {
		global $post;

		return is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'SearchFilterSort' );
	}

	/**
	 * Check if a specific feature flag is enabled.
	 *
	 * @param string $flag Feature flag key.
	 *
	 * @return bool True if enabled.
	 */
	public static function sflts_feature_enabled( $flag ) {
		return ! empty( SFLTS_FEATURE_FLAGS[ $flag ] );
	}

	/**
	 * Format a number into a price string.
	 *
	 * @param float|string $price Raw price.
	 *
	 * @return string Formatted price.
	 */
	public static function sflts_format_price( $price ) {
		return number_format( (float) $price, 2, '.', ',' );
	}

	/**
	 * Fetch minimum and maximum product prices from the database with caching.
	 *
	 * @return array Array with 'min' and 'max' prices.
	 */
	public static function sflts_get_price_range() {
		$cache_key   = 'sflts_price_range';
		$price_range = wp_cache_get( $cache_key, 'sflts' );
		if ( false === $price_range ) {
			global $wpdb;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$min = (float) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT MIN(CAST(meta_value AS DECIMAL(10,2))) FROM {$wpdb->postmeta} WHERE meta_key = %s",
					'_price'
				)
			);
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$max = (float) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT MAX(CAST(meta_value AS DECIMAL(10,2))) FROM {$wpdb->postmeta} WHERE meta_key = %s",
					'_price'
				)
			);

			$price_range = array(
				'min' => ( ! empty( $min ) ? $min : 0 ),
				'max' => ( ! empty( $max ) ? $max : 1000 ),
			);

			wp_cache_set( $cache_key, $price_range, 'sflts', 300 );
		}

		return $price_range;
	}
}
