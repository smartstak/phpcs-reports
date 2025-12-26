<?php
/**
 * Query builder for SearchFilterSort.
 *
 * Generates WP_Query arguments based on shortcode attributes and AJAX requests.
 *
 * @package searchfiltersort
 */

namespace SearchFilterSort;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use SearchFilterSort\SFLTS_Helpers;

/**
 * Builds and returns the query arguments for filtered search requests.
 *
 * @package searchfiltersort
 */
final class SFLTS_Query {

	/**
	 * Builds the WP_Query arguments based on provided filters.
	 *
	 * @param array $args Incoming parameters (post_type, taxonomy, filters, pagination, etc.).
	 *
	 * @return array Filtered WP_Query arguments.
	 */
	public static function sflts_build_args( $args = array() ) {

		$settings = SFLTS_Helpers::sflts_settings();

		$post_type = isset( $args['post_type'] ) ? sanitize_text_field( $args['post_type'] ) : ( $settings['post_type'] ?? 'post' );
		$taxonomy  = isset( $args['taxonomy'] ) ? sanitize_text_field( $args['taxonomy'] ) : ( $settings['taxonomy'] ?? '' );

		$posts_per_page = isset( $args['posts_per_page'] ) ? absint( $args['posts_per_page'] ) : ( $settings['products_per_page'] ?? 10 );

		$query_args = array(
			'post_type'      => $post_type,
			'post_status'    => $settings['post_status'] ?? 'publish',
			'posts_per_page' => min( absint( $posts_per_page ), 50 ), // Max 50 allowed.
			'paged'          => isset( $args['paged'] ) ? absint( $args['paged'] ) : 1,
		);

		// Keyword search.
		if ( ! empty( $args['search'] ) ) {
			$query_args['s'] = sanitize_text_field( $args['search'] );
		}

		// Taxonomy filter.
		$tax_terms = isset( $args['categories'] ) ? array_map( 'absint', (array) $args['categories'] ) : array();

		if ( ! empty( $tax_terms ) ) {
			// phpcs:ignore
			$query_args['tax_query'] = array(
				array(
					'taxonomy'         => $taxonomy,
					'field'            => 'term_id',
					'terms'            => $tax_terms,
					'operator'         => 'IN',
					'include_children' => true,
				),
			);
		}

		// Price filter.
		$meta_query = array();

		$price_min = $args['price_min'] ?? '';
		$price_max = $args['price_max'] ?? '';

		$valid_min = ( '' !== $price_min && is_numeric( $price_min ) );
		$valid_max = ( '' !== $price_max && is_numeric( $price_max ) );

		// Only proceed if at least one value is valid.
		if ( $valid_min || $valid_max ) {

			$min = $valid_min ? floatval( $price_min ) : 0.0;
			$max = $valid_max ? floatval( $price_max ) : 0.0;

			// Skip filter if both are zero.
			if ( ! ( 0.0 === $min && 0.0 === $max ) ) {
				// Both min and max valid & max >= min → BETWEEN .
				if ( $valid_min && $valid_max && $max >= $min ) {
					$meta_query[] = array(
						'key'     => '_price',
						'value'   => array( $min, $max ),
						'type'    => 'NUMERIC',
						'compare' => 'BETWEEN',
					);
				} elseif ( $valid_min ) { // Only min is valid → >= .
					$meta_query[] = array(
						'key'     => '_price',
						'value'   => $min,
						'type'    => 'NUMERIC',
						'compare' => '>=',
					);
				} elseif ( $valid_max ) { // Only max is valid → <= .
					$meta_query[] = array(
						'key'     => '_price',
						'value'   => $max,
						'type'    => 'NUMERIC',
						'compare' => '<=',
					);
				}
			}
		}

		if ( ! empty( $meta_query ) ) {
			$query_args['meta_query'] = $meta_query; // phpcs:ignore
		}

		// Sorting options.
		if ( ! empty( $args['sort'] ) ) {

			switch ( $args['sort'] ) {

				case 'title':
					$query_args['orderby'] = 'title';
					$query_args['order']   = 'ASC';
					break;

				case 'price_asc':
					$query_args['orderby']  = 'meta_value_num';
					$query_args['meta_key'] = '_price'; // phpcs:ignore
					$query_args['order']    = 'ASC';
					break;

				case 'price_desc':
					$query_args['orderby']  = 'meta_value_num';
					$query_args['meta_key'] = '_price'; // phpcs:ignore
					$query_args['order']    = 'DESC';
					break;

				default:
					$query_args['orderby'] = 'date';
					$query_args['order']   = 'DESC';
					break;
			}
		}

		/**
		 * Filter query builder arguments.
		 *
		 * @param array $query_args The WP_Query args.
		 * @param array $args       The raw incoming arguments.
		 */
		return apply_filters( 'sflts_query_args', $query_args, $args );
	}
}
