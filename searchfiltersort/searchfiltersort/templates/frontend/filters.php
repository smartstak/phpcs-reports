<?php
/**
 * Filter panel template for SearchFilterSort plugin.
 *
 * @package searchfiltersort
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Ensure $args exists and sanitize.
$sflts_args   = wp_parse_args(
	$args ?? array(),
	array(
		'taxonomy'        => '',
		'filter_position' => 'top',
	)
);
$sflts_helper = \SearchFilterSort\SFLTS_Helpers::class;


$sflts_settings = $sflts_helper::sflts_settings();

$sflts_terms = array();
if ( ! empty( $sflts_args['taxonomy'] ) ) {
	$sflts_terms = get_terms(
		array(
			'taxonomy'   => sanitize_text_field( $sflts_args['taxonomy'] ),
			'hide_empty' => false,
		)
	);
}
?>

<div class="sflts-filters-panel" data-position="<?php echo esc_attr( $sflts_args['filter_position'] ); ?>">

	<?php if ( ! empty( $sflts_settings['enable_category_filter'] ) && ! is_wp_error( $sflts_terms ) && ! empty( $sflts_terms ) ) : ?>
		<div class="sflts-filter-block">
			<h4><?php esc_html_e( 'Categories', 'searchfiltersort' ); ?></h4>
			<ul class="sflts-filter-category-ul">
			<?php foreach ( $sflts_terms as $sflts_filter_term ) : ?>
				<li><?php // Changed. ?>
					<label>
						<input type="checkbox" class="sflts-filter-category" value="<?php echo esc_attr( $sflts_filter_term->term_id ); ?>" />
						<span class="sflts-filter-category-input-checkbox__label">
							<?php echo esc_html( $sflts_filter_term->name ); ?>
							<span class="sflts-filter-category-input-checkbox__count">(<?php echo esc_attr( $sflts_filter_term->count ); ?>)</span>
						</span>
					</label>
				</li>
			<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>

	<?php
	if ( ! empty( $sflts_settings['enable_price_filter'] ) ) :
		$sflts_range = $sflts_helper::sflts_get_price_range();
		$sflts_min   = isset( $sflts_range['min'] ) ? floatval( $sflts_range['min'] ) : 0;
		$sflts_max   = isset( $sflts_range['max'] ) ? floatval( $sflts_range['max'] ) : 100;
		?>
		<div class="sflts-filter-block">
			<h4><?php esc_html_e( 'Price Range', 'searchfiltersort' ); ?></h4>
			<div class="sflts-price-slider" data-min="<?php echo esc_attr( $sflts_min ); ?>" data-max="<?php echo esc_attr( $sflts_max ); ?>"></div>
			<div class="sflts-price-inputs">
				<input type="number" class="sflts-price-min" data-default="<?php echo esc_attr( $sflts_min ); ?>" placeholder="<?php esc_attr_e( 'Min', 'searchfiltersort' ); ?>" />
				<input type="number" class="sflts-price-max" data-default="<?php echo esc_attr( $sflts_max ); ?>" placeholder="<?php esc_attr_e( 'Max', 'searchfiltersort' ); ?>" />
			</div>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $sflts_settings['enable_sort'] ) ) : ?>
		<div class="sflts-filter-block">
			<h4><?php esc_html_e( 'Sort By', 'searchfiltersort' ); ?></h4>
			<select class="sflts-sort-select">
				<option value="date"><?php esc_html_e( 'Date (Newest)', 'searchfiltersort' ); ?></option>
				<option value="title"><?php esc_html_e( 'Title (A–Z)', 'searchfiltersort' ); ?></option>
				<option value="price_asc"><?php esc_html_e( 'Price (Low to High)', 'searchfiltersort' ); ?></option>
				<option value="price_desc"><?php esc_html_e( 'Price (High to Low)', 'searchfiltersort' ); ?></option>
			</select>
		</div>
	<?php endif; ?>

	<button class="sflts-reset sflts-btn" type="button"><?php esc_html_e( 'Reset All', 'searchfiltersort' ); ?></button>

</div>
