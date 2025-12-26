<?php
/**
 * Frontend container template.
 *
 * Outputs the main SearchFilterSort layout including filters, results grid,
 * and pagination based on shortcode arguments and settings.
 *
 * @package searchfiltersort
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sflts_helper   = \SearchFilterSort\SFLTS_Helpers::class;
$sflts_settings = $sflts_helper::sflts_settings();

$sflts_container_id = 'sflts-' . uniqid();
?>

<div

	id="<?php echo esc_attr( $sflts_container_id ); ?>"

	class="sflts-container alignwide position-<?php echo esc_attr( $args['filter_position'] ); ?>"

	data-post-type="<?php echo esc_attr( $args['post_type'] ); ?>"

	data-taxonomy="<?php echo esc_attr( $args['taxonomy'] ); ?>"

	data-columns="<?php echo esc_attr( $args['columns'] ); ?>"

	data-filter-position="<?php echo esc_attr( $args['filter_position'] ); ?>"

	data-pagination="<?php echo esc_attr( $sflts_settings['pagination_type'] ); ?>"

	data-per-page="<?php echo esc_attr( $sflts_settings['products_per_page'] ); ?>"

>

	<?php if ( 'top' === $args['filter_position'] ) : ?>

		<?php include SFLTS_PLUGIN_DIR . 'templates/frontend/filters.php'; ?>

	<?php endif; ?>



	<div class="sflts-main">

		<?php if ( 'left' === $args['filter_position'] ) : ?>

			<?php include SFLTS_PLUGIN_DIR . 'templates/frontend/filters.php'; ?>

		<?php endif; ?>


		<div class="sflts-results">

			<div class="sflts-toolbar">

				<span class="sflts-count"></span>

			</div>



			<div class="sflts-grid"></div>



			<div class="sflts-pagination-area">

				<button class="sflts-load-more sflts-btn" type="button"><?php esc_html_e( 'Load More', 'searchfiltersort' ); ?></button>

				<div class="sflts-pagination-links"></div>

			</div>

		</div>



		<?php if ( 'right' === $args['filter_position'] ) : ?>

			<?php include SFLTS_PLUGIN_DIR . 'templates/frontend/filters.php'; ?>

		<?php endif; ?>

	</div>



	<?php if ( 'below' === $args['filter_position'] ) : ?>

		<?php include SFLTS_PLUGIN_DIR . 'templates/frontend/filters.php'; ?>

	<?php endif; ?>

</div>
